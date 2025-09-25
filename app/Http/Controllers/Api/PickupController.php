<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PickupToken;
use App\Models\OrderEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PickupController extends Controller
{
    /**
     * Generate pickup token after payment
     */
    public function generatePickupToken($order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            if ($order->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check if pickup token already exists and is active
            if ($order->pickupToken && $order->pickupToken->isActive()) {
                $token = $order->pickupToken;
                $token->generateQrCode();
                
                return response()->json([
                    'message' => 'Pickup token already exists',
                    'pickup_token' => [
                        'pickup_code' => $token->pickup_code,
                        'qr_code_url' => $token->qr_code_url,
                        'expires_at' => $token->expires_at->toISOString(),
                        'status' => $token->status_text,
                        'order_number' => $order->order_number
                    ]
                ]);
            }

            // Create new pickup token
            $token = PickupToken::create(['order_id' => $order->id]);
            $token->generateQrCode();

            // Log event
            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'PICKUP_TOKEN_GENERATED',
                'description' => "Pickup token generated: {$token->pickup_code}",
                'metadata' => [
                    'pickup_code' => $token->pickup_code,
                    'expires_at' => $token->expires_at->toISOString()
                ]
            ]);

            return response()->json([
                'message' => 'Pickup token generated successfully',
                'pickup_token' => [
                    'pickup_code' => $token->pickup_code,
                    'qr_code_url' => $token->qr_code_url,
                    'expires_at' => $token->expires_at->toISOString(),
                    'status' => $token->status_text,
                    'order_number' => $order->order_number,
                    'instructions' => 'Show this code to merchant for pickup/delivery verification'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate pickup token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pickup token for customer
     */
    public function getPickupToken($order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $token = $order->pickupToken;

            if (!$token) {
                return response()->json([
                    'message' => 'No pickup token found for this order'
                ], 404);
            }

            return response()->json([
                'message' => 'Pickup token retrieved successfully',
                'pickup_token' => [
                    'pickup_code' => $token->pickup_code,
                    'qr_code_url' => $token->qr_code_url,
                    'expires_at' => $token->expires_at->toISOString(),
                    'status' => $token->status_text,
                    'order_number' => $order->order_number,
                    'is_active' => $token->isActive(),
                    'verified_at' => $token->verified_at?->toISOString(),
                    'verified_by' => $token->verifiedBy?->first_name . ' ' . $token->verifiedBy?->last_name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve pickup token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify pickup code (for merchants/staff)
     */
    public function verifyPickupCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pickup_code' => 'required|string',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            
            // Only staff/admin can verify
            if (!$user->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'message' => 'Only authorized staff can verify pickup codes'
                ], 403);
            }

            $token = PickupToken::where('pickup_code', $request->pickup_code)
                ->with(['order.user', 'order.items.productVariant.product'])
                ->first();

            if (!$token) {
                return response()->json([
                    'message' => 'Invalid pickup code',
                    'pickup_code' => $request->pickup_code
                ], 404);
            }

            if (!$token->isActive()) {
                return response()->json([
                    'message' => 'Pickup code is expired or already used',
                    'pickup_code' => $request->pickup_code,
                    'status' => $token->status_text
                ], 400);
            }

            // Mark token as used
            $token->markAsUsed($user->id, $request->notes);

            // Update order status
            $order = $token->order;
            $newStatus = $order->fulfillment_method === 'PICKUP' ? 'COMPLETED' : 'READY';
            $order->update(['status' => $newStatus]);

            // Log event
            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'PICKUP_VERIFIED',
                'description' => "Pickup verified by {$user->first_name} {$user->last_name}",
                'metadata' => [
                    'pickup_code' => $token->pickup_code,
                    'verified_by' => $user->id,
                    'verification_notes' => $request->notes
                ]
            ]);

            return response()->json([
                'message' => 'Pickup code verified successfully',
                'verification' => [
                    'pickup_code' => $token->pickup_code,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'customer_phone' => $order->user->phone_number,
                    'order_total' => $order->total_aud,
                    'items_count' => $order->items->count(),
                    'fulfillment_method' => $order->fulfillment_method,
                    'new_order_status' => $newStatus,
                    'verified_by' => $user->first_name . ' ' . $user->last_name,
                    'verified_at' => now()->toISOString(),
                    'notes' => $request->notes
                ],
                'order_items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->productVariant->product->name,
                        'variant_name' => $item->productVariant->name,
                        'quantity' => $item->quantity,
                        'weight_kg' => $item->productVariant->weight_kg
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to verify pickup code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending pickups (for merchant dashboard)
     */
    public function getPendingPickups(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user->hasRole(['admin', 'staff'])) {
                return response()->json([
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $pendingTokens = PickupToken::where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->with(['order.user', 'order.items.productVariant.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            $pickups = $pendingTokens->map(function ($token) {
                $order = $token->order;
                return [
                    'pickup_code' => $token->pickup_code,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->first_name . ' ' . $order->user->last_name,
                    'customer_phone' => $order->user->phone_number,
                    'order_total' => $order->total_aud,
                    'fulfillment_method' => $order->fulfillment_method,
                    'items_count' => $order->items->count(),
                    'created_at' => $token->created_at->toISOString(),
                    'expires_at' => $token->expires_at->toISOString(),
                    'days_remaining' => now()->diffInDays($token->expires_at)
                ];
            });

            return response()->json([
                'message' => 'Pending pickups retrieved successfully',
                'pending_pickups' => $pickups,
                'total_count' => $pickups->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve pending pickups',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
