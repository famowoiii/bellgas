<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeTestController extends Controller
{
    public function __construct(
        private StripeApiService $stripeService
    ) {}

    public function simulatePaymentSuccess(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string'
            ]);

            $paymentIntentId = $request->payment_intent_id;

            // Simulasi konfirmasi pembayaran dengan test card
            $confirmedPayment = $this->confirmPaymentWithTestCard($paymentIntentId);

            return response()->json([
                'message' => 'Payment simulation completed successfully',
                'payment_intent' => $confirmedPayment,
                'status' => $confirmedPayment['status'],
                'amount_received' => $confirmedPayment['amount_received'] ?? 0,
                'test_info' => [
                    'card_used' => '4242424242424242 (Visa Test Card)',
                    'simulation' => true,
                    'note' => 'This payment was completed using Stripe test card for demonstration'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment simulation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function confirmPaymentWithTestCard(string $paymentIntentId): array
    {
        // Gunakan test payment method token yang sudah disediakan Stripe
        // pm_card_visa adalah test token untuk Visa card yang selalu berhasil
        $testPaymentMethodId = 'pm_card_visa';

        // Konfirmasi payment intent dengan test payment method
        $confirmedPayment = $this->stripeService->confirmPaymentIntent($paymentIntentId, [
            'payment_method' => $testPaymentMethodId,
            'return_url' => 'http://localhost:8000/api/payment-return'
        ]);

        return $confirmedPayment;
    }

    public function getTestCards(): JsonResponse
    {
        return response()->json([
            'message' => 'Stripe test card numbers',
            'test_cards' => [
                [
                    'number' => '4242424242424242',
                    'brand' => 'visa',
                    'description' => 'Succeeds and immediately processes the payment',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000000069',
                    'brand' => 'visa',
                    'description' => 'Expired card',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000000127',
                    'brand' => 'visa',
                    'description' => 'Incorrect CVC',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000000002',
                    'brand' => 'visa',
                    'description' => 'Generic card declined',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ]
            ],
            'usage_notes' => [
                'Use these cards in test mode only',
                'Any CVC and valid expiry date will work',
                'Cards simulate different payment outcomes'
            ]
        ]);
    }
}