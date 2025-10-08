<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentEvent;
use App\Services\StripeService;
use App\Events\NewPaidOrderEvent;
use Stripe\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (empty($signature) || empty($webhookSecret)) {
            Log::error('Stripe webhook: Missing signature or webhook secret');
            return response()->json(['error' => 'Invalid request'], 400);
        }

        try {
            // Verify webhook signature using Stripe library directly
            $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Check for duplicate events (idempotency)
        $existingEvent = PaymentEvent::where('stripe_event_id', $event->id)->first();
        if ($existingEvent) {
            Log::info("Stripe webhook: Duplicate event {$event->id}, skipping");
            return response()->json(['message' => 'Event already processed'], 200);
        }

        // Store the payment event
        $paymentEvent = PaymentEvent::create([
            'stripe_event_id' => $event->id,
            'stripe_payment_intent_id' => $event->data->object->id ?? null,
            'event_type' => $event->type,
            'stripe_data' => $event->data->object,
            'processed' => false,
        ]);

        try {
            // Process the event
            $processed = $this->processEvent($event);
            
            if ($processed) {
                $paymentEvent->markAsProcessed();
                Log::info("Stripe webhook: Successfully processed event {$event->id}");
            }

            return response()->json(['message' => 'Event processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error("Stripe webhook: Failed to process event {$event->id}: " . $e->getMessage());
            return response()->json(['error' => 'Event processing failed'], 500);
        }
    }

    private function processEvent(\Stripe\Event $event): bool
    {
        $paymentIntent = $event->data->object;

        if (!isset($paymentIntent->id)) {
            Log::warning("Stripe webhook: No payment intent ID found in event {$event->id}");
            return false;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        
        if (!$order) {
            Log::warning("Stripe webhook: No order found for payment intent {$paymentIntent->id}");
            return false;
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($order, $paymentIntent);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($order, $paymentIntent);

            case 'payment_intent.canceled':
                return $this->handlePaymentCanceled($order, $paymentIntent);

            case 'payment_intent.requires_action':
                return $this->handlePaymentRequiresAction($order, $paymentIntent);

            default:
                Log::info("Stripe webhook: Unhandled event type {$event->type} for payment intent {$paymentIntent->id}");
                return true; // Mark as processed even if we don't handle it
        }
    }

    private function handlePaymentSucceeded(Order $order, $paymentIntent): bool
    {
        if ($order->status === 'PAID') {
            Log::info("Order {$order->order_number} already marked as paid");
            return true;
        }

        $order->update(['status' => 'PAID']);

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'PAID',
            'description' => 'Payment completed successfully',
            'metadata' => [
                'payment_intent_id' => $paymentIntent->id,
                'amount_received' => $paymentIntent->amount_received ?? $paymentIntent->amount,
                'payment_method' => $paymentIntent->charges->data[0]->payment_method_details->type ?? 'unknown',
            ],
        ]);

        // Load relationships for the event
        $order->load(['items.productVariant.product', 'address']);

        // Broadcast real-time notification to admin
        try {
            broadcast(new NewPaidOrderEvent($order))->toOthers();
            Log::info("Real-time notification sent for paid order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to broadcast new paid order event: " . $e->getMessage());
        }

        // If it's a pickup order, it can be moved to READY status after payment
        if ($order->fulfillment_method === 'PICKUP') {
            // You might want to add additional business logic here
            // For now, just log that it's ready for processing
            Log::info("Order {$order->order_number} is ready for pickup processing");
        }

        Log::info("Payment succeeded for order {$order->order_number}");
        return true;
    }

    private function handlePaymentFailed(Order $order, $paymentIntent): bool
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'PAYMENT_FAILED',
            'description' => 'Payment failed',
            'metadata' => [
                'payment_intent_id' => $paymentIntent->id,
                'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                'failure_code' => $paymentIntent->last_payment_error->code ?? null,
            ],
        ]);

        Log::warning("Payment failed for order {$order->order_number}");
        return true;
    }

    private function handlePaymentCanceled(Order $order, $paymentIntent): bool
    {
        if ($order->status !== 'CANCELLED') {
            $order->update(['status' => 'CANCELLED']);
        }

        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'CANCELLED',
            'description' => 'Payment was canceled',
            'metadata' => [
                'payment_intent_id' => $paymentIntent->id,
                'cancellation_reason' => $paymentIntent->cancellation_reason ?? 'Manual cancellation',
            ],
        ]);

        Log::info("Payment canceled for order {$order->order_number}");
        return true;
    }

    private function handlePaymentRequiresAction(Order $order, $paymentIntent): bool
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'event_type' => 'PAYMENT_ACTION_REQUIRED',
            'description' => 'Payment requires additional action',
            'metadata' => [
                'payment_intent_id' => $paymentIntent->id,
                'next_action_type' => $paymentIntent->next_action->type ?? 'unknown',
            ],
        ]);

        Log::info("Payment requires action for order {$order->order_number}");
        return true;
    }
}
