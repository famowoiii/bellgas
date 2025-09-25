<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Services\StripeApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private StripeApiService $stripeService
    ) {}

    public function createPaymentIntent($order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this order'
                ], 403);
            }

            if ($order->stripe_payment_intent_id) {
                $paymentIntent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);
                
                return response()->json([
                    'message' => 'Payment intent already exists',
                    'payment_intent' => $paymentIntent,
                    'client_secret' => $paymentIntent['client_secret']
                ]);
            }

            $paymentIntent = $this->stripeService->createPaymentIntent(
                $this->stripeService->calculateAmountInCents($order->total_aud),
                'aud',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_id' => $order->user_id
                ]
            );

            $order->update([
                'stripe_payment_intent_id' => $paymentIntent['id']
            ]);

            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'PAYMENT_INTENT_CREATED',
                'description' => 'Payment intent created',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount']
                ]
            ]);

            return response()->json([
                'message' => 'Payment intent created successfully',
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent['client_secret']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function completePayment(Request $request, $order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            $request->validate([
                'payment_method_id' => 'required|string'
            ]);

            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this order'
                ], 403);
            }

            if (!$order->stripe_payment_intent_id) {
                return response()->json([
                    'message' => 'No payment intent found for this order'
                ], 400);
            }

            // Attach payment method to payment intent and confirm
            $confirmedPayment = $this->stripeService->confirmPaymentIntent(
                $order->stripe_payment_intent_id,
                [
                    'payment_method' => $request->payment_method_id
                ]
            );

            if ($confirmedPayment['status'] === 'succeeded') {
                $order->update(['status' => 'PAID']);
                
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'PAID',
                    'description' => 'Payment completed successfully',
                    'metadata' => [
                        'payment_intent_id' => $confirmedPayment['id'],
                        'amount_received' => $confirmedPayment['amount_received'],
                        'payment_method' => $confirmedPayment['payment_method']
                    ]
                ]);

                // Fire PaymentCompleted event for real-time notifications
                event(new \App\Events\PaymentCompleted(
                    $order->load('user'), 
                    'stripe', 
                    $order->total_aud
                ));

                return response()->json([
                    'message' => 'Payment completed successfully',
                    'payment_intent' => $confirmedPayment,
                    'order_status' => 'PAID'
                ]);
            }

            return response()->json([
                'message' => 'Payment requires further action',
                'payment_intent' => $confirmedPayment,
                'requires_action' => $confirmedPayment['status'] === 'requires_action'
            ]);

        } catch (\Exception $e) {
            Log::error("Payment completion failed for order {$order->order_number}: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Payment completion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function simulateTestPayment(Request $request, $order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this order'
                ], 403);
            }

            if (!$order->stripe_payment_intent_id) {
                $paymentIntent = $this->stripeService->createPaymentIntent(
                    $this->stripeService->calculateAmountInCents($order->total_aud),
                    'aud',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_id' => $order->user_id
                    ]
                );

                $order->update([
                    'stripe_payment_intent_id' => $paymentIntent['id']
                ]);
            }

            // Use Stripe test card to complete payment
            $confirmedPayment = $this->stripeService->confirmPaymentIntent(
                $order->stripe_payment_intent_id,
                [
                    'payment_method' => 'pm_card_visa',
                    'return_url' => config('app.url') . '/payment/return'
                ]
            );

            if ($confirmedPayment['status'] === 'succeeded') {
                $order->update(['status' => 'PAID']);
                
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'PAID',
                    'description' => 'Test payment completed successfully',
                    'metadata' => [
                        'payment_intent_id' => $confirmedPayment['id'],
                        'amount_received' => $confirmedPayment['amount_received'],
                        'test_payment' => true,
                        'test_card' => '4242424242424242'
                    ]
                ]);

                // Fire PaymentCompleted event for real-time notifications
                event(new \App\Events\PaymentCompleted(
                    $order->load('user'), 
                    'stripe_test', 
                    $order->total_aud
                ));

                Log::info("Test payment completed for order {$order->order_number}");

                return response()->json([
                    'message' => 'Test payment completed successfully',
                    'payment_intent' => $confirmedPayment,
                    'order_status' => 'PAID',
                    'test_info' => [
                        'card_used' => '4242424242424242 (Visa Test Card)',
                        'simulation' => true,
                        'note' => 'This payment was completed using Stripe test card'
                    ]
                ]);
            }

            return response()->json([
                'message' => 'Test payment requires further action',
                'payment_intent' => $confirmedPayment
            ]);

        } catch (\Exception $e) {
            Log::error("Test payment failed for order {$order->order_number}: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Test payment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentStatus($order): JsonResponse
    {
        try {
            $order = Order::findOrFail($order);
            
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this order'
                ], 403);
            }

            if (!$order->stripe_payment_intent_id) {
                return response()->json([
                    'message' => 'No payment intent found',
                    'order_status' => $order->status,
                    'payment_status' => null
                ]);
            }

            $paymentIntent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);

            return response()->json([
                'message' => 'Payment status retrieved',
                'order_status' => $order->status,
                'payment_intent' => $paymentIntent,
                'payment_status' => $paymentIntent['status']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}