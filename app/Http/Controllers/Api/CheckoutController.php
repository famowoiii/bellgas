<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ShippingHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CreatePaymentIntentRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\StripeApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        private StripeApiService $stripeService
    ) {}

    public function createPaymentIntent(CreatePaymentIntentRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            try {
                \Log::info('Checkout request started', [
                    'user_id' => auth()->id(),
                    'request_data' => $request->all()
                ]);

                // Check if Stripe is properly configured
                $stripeSecretKey = config('services.stripe.secret');
                if (!$stripeSecretKey || $stripeSecretKey === 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY') {
                    \Log::error('Stripe not configured properly', [
                        'user_id' => auth()->id(),
                        'stripe_key_status' => $stripeSecretKey ? 'placeholder' : 'missing'
                    ]);
                    
                    return response()->json([
                        'message' => 'Payment system not configured. Please contact administrator.',
                        'error' => 'Stripe configuration required'
                    ], 503);
                }

                $user = auth()->user();
                $address = $request->address_id ? Address::find($request->address_id) : null;

                // Calculate totals
                $subtotal = 0;
                $totalWeightKg = 0;
                $orderItems = [];

                foreach ($request->items as $item) {
                    $variant = ProductVariant::with('product')->find($item['product_variant_id']);

                    if (!$variant || !$variant->is_active || !$variant->product->is_active) {
                        return response()->json([
                            'message' => 'One or more products are not available',
                        ], 400);
                    }

                    // Check stock availability
                    if ($variant->available_stock < $item['quantity']) {
                        return response()->json([
                            'message' => "Insufficient stock for {$variant->product->name} - {$variant->name}. Available: {$variant->available_stock}",
                        ], 400);
                    }

                    $itemTotal = $variant->price_aud * $item['quantity'];
                    $itemWeight = $variant->weight_kg * $item['quantity'];

                    $subtotal += $itemTotal;
                    $totalWeightKg += $itemWeight;

                    $orderItems[] = [
                        'product_variant_id' => $variant->id,
                        'quantity' => $item['quantity'],
                        'unit_price_aud' => $variant->price_aud,
                        'total_price_aud' => $itemTotal,
                    ];
                }

                // Calculate shipping cost
                $shippingCost = 0;
                if ($request->fulfillment_method === 'DELIVERY' && $address) {
                    $shippingCost = ShippingHelper::calculateShippingCost($address->postcode, $totalWeightKg);
                }

                $total = $subtotal + $shippingCost;

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'address_id' => $address ? $address->id : null,
                    'status' => 'PENDING',
                    'fulfillment_method' => $request->fulfillment_method,
                    'subtotal_aud' => $subtotal,
                    'shipping_cost_aud' => $shippingCost,
                    'total_aud' => $total,
                    'customer_notes' => $request->customer_notes,
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }

                // Create payment intent with Stripe
                $paymentIntentData = $this->stripeService->createPaymentIntent(
                    $this->stripeService->calculateAmountInCents($total),
                    'aud',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'user_id' => $user->id,
                        'fulfillment_method' => $request->fulfillment_method,
                    ]
                );

                // Update order with payment intent ID
                $order->update([
                    'stripe_payment_intent_id' => $paymentIntentData['id'],
                ]);

                // Create order event
                OrderEvent::create([
                    'order_id' => $order->id,
                    'event_type' => 'CREATED',
                    'description' => 'Order created and payment intent generated',
                    'metadata' => [
                        'payment_intent_id' => $paymentIntentData['id'],
                        'total_amount' => $total,
                    ],
                ]);

                // Load order with relationships for response
                $order->load(['items.productVariant.product', 'address', 'user']);

                return response()->json([
                    'message' => 'Payment intent created successfully',
                    'clientSecret' => $paymentIntentData['client_secret'],
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'fulfillment_method' => $order->fulfillment_method,
                        'subtotal_aud' => $order->subtotal_aud,
                        'shipping_cost_aud' => $order->shipping_cost_aud,
                        'total_aud' => $order->total_aud,
                        'customer_notes' => $order->customer_notes,
                        'address' => $address ? [
                            'id' => $address->id,
                            'name' => $address->name,
                            'full_address' => $address->full_address,
                            'delivery_instructions' => $address->delivery_instructions,
                        ] : null,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'quantity' => $item->quantity,
                                'unit_price_aud' => $item->unit_price_aud,
                                'total_price_aud' => $item->total_price_aud,
                                'product' => [
                                    'name' => $item->productVariant->product->name,
                                    'category' => $item->productVariant->product->category,
                                ],
                                'variant' => [
                                    'name' => $item->productVariant->name,
                                    'weight_kg' => $item->productVariant->weight_kg,
                                ],
                            ];
                        }),
                        'created_at' => $order->created_at,
                    ],
                    'paymentIntent' => [
                        'id' => $paymentIntentData['id'],
                        'amount' => $paymentIntentData['amount'],
                        'currency' => $paymentIntentData['currency'],
                        'status' => $paymentIntentData['status'],
                    ],
                ], 201);

            } catch (\Exception $e) {
                \Log::error('Checkout payment intent creation failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'message' => 'Failed to create payment intent',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
    }

    public function createPaymentIntentForOrder(): JsonResponse
    {
        try {
            $orderNumber = request('order_number');
            $amount = request('amount'); // Amount in cents
            
            \Log::info('Creating payment intent for existing order', [
                'user_id' => auth()->id(),
                'order_number' => $orderNumber,
                'amount' => $amount
            ]);

            // Check if Stripe is properly configured
            $stripeSecretKey = config('services.stripe.secret');
            if (!$stripeSecretKey || $stripeSecretKey === 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY') {
                return response()->json([
                    'message' => 'Payment system not configured. Please contact administrator.',
                    'error' => 'Stripe configuration required'
                ], 503);
            }

            // Find the order and verify ownership
            $order = Order::where('order_number', $orderNumber)
                         ->where('user_id', auth()->id())
                         ->where('status', 'PENDING')
                         ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found or not available for payment',
                ], 404);
            }

            // Create payment intent with Stripe
            $paymentIntentData = $this->stripeService->createPaymentIntent(
                $amount,
                'aud',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => auth()->id(),
                    'payment_type' => 'existing_order'
                ]
            );

            // Update order with new payment intent ID
            $order->update([
                'stripe_payment_intent_id' => $paymentIntentData['id'],
            ]);

            // Create order event
            OrderEvent::create([
                'order_id' => $order->id,
                'event_type' => 'PAYMENT_INTENT_CREATED',
                'description' => 'Payment intent created for existing order',
                'metadata' => [
                    'payment_intent_id' => $paymentIntentData['id'],
                    'amount_cents' => $amount,
                ],
            ]);

            return response()->json([
                'message' => 'Payment intent created successfully',
                'clientSecret' => $paymentIntentData['client_secret'],
                'paymentIntent' => [
                    'id' => $paymentIntentData['id'],
                    'amount' => $paymentIntentData['amount'],
                    'currency' => $paymentIntentData['currency'],
                    'status' => $paymentIntentData['status'],
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Payment intent creation for existing order failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => request()->all()
            ]);

            return response()->json([
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}