<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Helpers\ShippingHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function simulateStripePayment(Request $request): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'address_id' => 'required|exists:addresses,id',
                'items' => 'required|array|min:1',
                'items.*.product_variant_id' => 'required|exists:product_variants,id',
                'items.*.quantity' => 'required|integer|min:1',
                'test_payment_status' => 'required|in:success,fail,processing'
            ]);

            $user = User::findOrFail($request->user_id);
            
            // Calculate totals
            $subtotal = 0;
            $totalWeight = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $variant = ProductVariant::findOrFail($item['product_variant_id']);
                $itemTotal = $variant->price_aud * $item['quantity'];
                $itemWeight = $variant->weight_kg * $item['quantity'];
                
                $subtotal += $itemTotal;
                $totalWeight += $itemWeight;
                
                $orderItems[] = [
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'unit_price' => $variant->price_aud,
                    'total_price' => $itemTotal
                ];
            }

            // Get address for shipping
            $address = $user->addresses()->findOrFail($request->address_id);
            
            // Calculate shipping
            $shippingCost = ShippingHelper::calculateShippingCost($address->postcode, $totalWeight);
            $totalAmount = $subtotal + $shippingCost;

            // Simulate different payment statuses
            $paymentIntentId = 'pi_test_' . uniqid();
            $clientSecret = $paymentIntentId . '_secret_' . uniqid();
            
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'BG-TEST-' . strtoupper(substr(uniqid(), -8)),
                'address_id' => $address->id,
                'status' => $request->test_payment_status === 'success' ? 'PAID' : 'UNPAID',
                'fulfillment_method' => 'DELIVERY',
                'subtotal_aud' => $subtotal,
                'shipping_cost_aud' => $shippingCost,
                'total_aud' => $totalAmount,
                'stripe_payment_intent_id' => $paymentIntentId,
                'customer_notes' => 'Test Stripe payment simulation'
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'unit_price_aud' => $item['unit_price'],
                    'total_price_aud' => $item['total_price']
                ]);
            }

            // Simulate payment responses
            if ($request->test_payment_status === 'success') {
                return response()->json([
                    'message' => 'Test payment simulation successful',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'total_aud' => number_format($order->total_aud, 2),
                        'stripe_payment_intent_id' => $paymentIntentId
                    ],
                    'payment_simulation' => [
                        'status' => 'succeeded',
                        'payment_intent_id' => $paymentIntentId,
                        'amount_received' => $totalAmount * 100, // in cents
                        'currency' => 'aud'
                    ]
                ]);
            } elseif ($request->test_payment_status === 'fail') {
                return response()->json([
                    'message' => 'Test payment simulation failed',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status
                    ],
                    'payment_simulation' => [
                        'status' => 'failed',
                        'error' => 'Test payment declined by issuer'
                    ]
                ], 400);
            } else {
                return response()->json([
                    'message' => 'Test payment processing',
                    'clientSecret' => $clientSecret,
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'total_aud' => number_format($order->total_aud, 2)
                    ],
                    'payment_simulation' => [
                        'status' => 'requires_payment_method',
                        'payment_intent_id' => $paymentIntentId,
                        'client_secret' => $clientSecret
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Test payment simulation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStripeTestCards(): JsonResponse
    {
        return response()->json([
            'message' => 'Stripe test card numbers for testing',
            'test_cards' => [
                [
                    'number' => '4242424242424242',
                    'brand' => 'visa',
                    'description' => 'Generic successful card',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '5555555555554444',
                    'brand' => 'mastercard', 
                    'description' => 'Generic successful card',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000000002',
                    'brand' => 'visa',
                    'description' => 'Card declined',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000009995',
                    'brand' => 'visa',
                    'description' => 'Insufficient funds',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ],
                [
                    'number' => '4000000000009987',
                    'brand' => 'visa',
                    'description' => 'Lost card',
                    'cvc' => '123',
                    'expiry' => '12/30'
                ]
            ],
            'usage_notes' => [
                'Use any valid future expiry date',
                'Use any 3-digit CVC code',
                'Use any billing postal code for Australia (e.g., 2000)',
                'These cards will simulate different payment outcomes'
            ]
        ]);
    }

    public function getPaymentTestScenarios(): JsonResponse
    {
        return response()->json([
            'message' => 'BellGas payment test scenarios',
            'scenarios' => [
                [
                    'name' => 'Successful Payment',
                    'description' => 'Single 9kg LPG cylinder delivery to Sydney',
                    'test_data' => [
                        'items' => [['product_variant_id' => 1, 'quantity' => 1]],
                        'address_postcode' => '2000',
                        'expected_shipping' => 37.50,
                        'expected_total' => 127.45
                    ]
                ],
                [
                    'name' => 'Multiple Items',
                    'description' => 'Mixed order with cylinder and refill',
                    'test_data' => [
                        'items' => [
                            ['product_variant_id' => 1, 'quantity' => 1],
                            ['product_variant_id' => 4, 'quantity' => 2]
                        ],
                        'expected_subtotal' => 155.85,
                        'expected_shipping_weight' => 27.0
                    ]
                ],
                [
                    'name' => 'High Value Order',
                    'description' => 'Large 45kg cylinder with premium shipping',
                    'test_data' => [
                        'items' => [['product_variant_id' => 3, 'quantity' => 1]],
                        'expected_subtotal' => 249.95,
                        'expected_shipping_weight' => 45.0
                    ]
                ],
                [
                    'name' => 'Remote Delivery',
                    'description' => 'Delivery to remote area with higher shipping',
                    'test_data' => [
                        'items' => [['product_variant_id' => 7, 'quantity' => 5]],
                        'address_postcode' => '0872', // Remote NT
                        'expected_shipping_zone' => 'remote',
                        'expected_shipping_extra' => 25.00
                    ]
                ]
            ],
            'api_endpoints' => [
                'simulate_payment' => 'POST /api/test/stripe-simulation',
                'test_cards' => 'GET /api/test/stripe-cards',
                'real_checkout' => 'POST /api/checkout/create-payment-intent'
            ]
        ]);
    }
}