<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PickupToken;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class PickupTokenService
{
    /**
     * Generate a pickup token for an order (OTP + JWT for QR)
     */
    public function generatePickupToken(Order $order): PickupToken
    {
        try {
            // Generate 6-digit OTP
            $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Generate JWT token for QR code
            $jwtPayload = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'otp' => $otpCode,
                'type' => 'pickup_token',
                'iat' => now()->timestamp,
                'exp' => now()->addHours(48)->timestamp, // 48 hours TTL
            ];

            $jwtToken = JWTAuth::claims($jwtPayload)->fromUser($order->user);

            // Create or update pickup token
            $pickupToken = PickupToken::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'otp_code' => $otpCode,
                    'jwt_token' => $jwtToken,
                    'expires_at' => now()->addHours(48),
                    'is_used' => false,
                    'used_at' => null,
                ]
            );

            Log::info('Pickup token generated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'expires_at' => $pickupToken->expires_at
            ]);

            return $pickupToken;
        } catch (\Exception $e) {
            Log::error('Failed to generate pickup token', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify pickup token by OTP
     */
    public function verifyByOtp(string $orderNumber, string $otpCode): ?PickupToken
    {
        try {
            $order = Order::where('order_number', $orderNumber)->first();
            
            if (!$order) {
                Log::warning('Order not found for OTP verification', [
                    'order_number' => $orderNumber,
                    'otp' => $otpCode
                ]);
                return null;
            }

            $pickupToken = PickupToken::where('order_id', $order->id)
                ->where('otp_code', $otpCode)
                ->active()
                ->first();

            if ($pickupToken) {
                Log::info('Pickup token verified by OTP', [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber
                ]);
            } else {
                Log::warning('Invalid or expired OTP', [
                    'order_number' => $orderNumber,
                    'otp' => $otpCode
                ]);
            }

            return $pickupToken;
        } catch (\Exception $e) {
            Log::error('Error verifying pickup token by OTP', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verify pickup token by JWT (QR code)
     */
    public function verifyByJwt(string $jwtToken): ?PickupToken
    {
        try {
            $payload = JWTAuth::setToken($jwtToken)->getPayload();
            
            $orderId = $payload->get('order_id');
            $otpCode = $payload->get('otp');
            $tokenType = $payload->get('type');

            if ($tokenType !== 'pickup_token') {
                Log::warning('Invalid token type for pickup verification', [
                    'token_type' => $tokenType
                ]);
                return null;
            }

            $pickupToken = PickupToken::where('order_id', $orderId)
                ->where('otp_code', $otpCode)
                ->where('jwt_token', $jwtToken)
                ->active()
                ->first();

            if ($pickupToken) {
                Log::info('Pickup token verified by JWT', [
                    'order_id' => $orderId
                ]);
            } else {
                Log::warning('Invalid or expired JWT token', [
                    'order_id' => $orderId
                ]);
            }

            return $pickupToken;
        } catch (\Exception $e) {
            Log::error('Error verifying pickup token by JWT', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate QR code data URL
     */
    public function generateQrCode(PickupToken $pickupToken): string
    {
        $qrData = [
            'type' => 'pickup_token',
            'jwt' => $pickupToken->jwt_token,
            'order_number' => $pickupToken->order->order_number,
            'expires_at' => $pickupToken->expires_at->toISOString(),
        ];

        // Return JSON string that can be encoded to QR code
        return json_encode($qrData);
    }

    /**
     * Mark pickup token as used
     */
    public function markAsUsed(PickupToken $pickupToken): void
    {
        $pickupToken->markAsUsed();

        Log::info('Pickup token marked as used', [
            'order_id' => $pickupToken->order_id,
            'used_at' => $pickupToken->used_at
        ]);
    }

    /**
     * Get pickup instructions
     */
    public function getPickupInstructions(Order $order): array
    {
        return [
            'title' => 'Order Ready for Pickup',
            'instructions' => [
                '1. Bring a valid ID that matches your order details',
                '2. Show the QR code below to staff, OR provide the 6-digit OTP',
                '3. Our staff will verify your identity and prepare your order',
                '4. Check your items before leaving the store',
            ],
            'location' => [
                'store_name' => 'BellGas Store',
                'address' => config('app.store_address', '123 Main St, Your City'),
                'phone' => config('app.store_phone', '+61 2 1234 5678'),
                'hours' => 'Mon-Fri: 9AM-6PM, Sat: 9AM-4PM, Sun: Closed',
            ],
            'important_notes' => [
                'This pickup token expires in 48 hours',
                'One-time use only',
                'Keep this information secure',
                'Contact us if you cannot collect within 48 hours',
            ],
            'order_details' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->user->full_name,
                'total_amount' => $order->total_amount,
                'items_count' => $order->items->count(),
            ]
        ];
    }

    /**
     * Check if order is eligible for pickup
     */
    public function canGeneratePickupToken(Order $order): bool
    {
        // Order must be paid and ready for pickup
        $eligibleStatuses = ['PAID', 'PROCESSING', 'READY_FOR_PICKUP'];
        
        return in_array($order->status, $eligibleStatuses) && 
               $order->fulfillment_method === 'PICKUP';
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $expiredCount = PickupToken::where('expires_at', '<', now())
            ->where('is_used', false)
            ->count();

        if ($expiredCount > 0) {
            PickupToken::where('expires_at', '<', now())
                ->where('is_used', false)
                ->delete();

            Log::info('Cleaned up expired pickup tokens', [
                'count' => $expiredCount
            ]);
        }

        return $expiredCount;
    }
}