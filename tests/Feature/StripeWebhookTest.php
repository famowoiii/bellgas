<?php

namespace Tests\Feature;

use App\Events\NewPaidOrderEvent;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use DatabaseMigrations;

    private string $webhookSecret = 'whsec_test_webhook_secret_for_testing';
    private User $user;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => $this->webhookSecret]);

        $this->user = User::factory()->create();

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'status' => 'PENDING',
            'fulfillment_method' => 'PICKUP',
            'subtotal_aud' => 89.95,
            'shipping_cost_aud' => 0,
            'total_aud' => 89.95,
            'stripe_payment_intent_id' => 'pi_test_webhook123',
        ]);
    }

    private function buildStripeEvent(string $type, array $paymentIntentData = []): array
    {
        $defaultData = [
            'id' => 'pi_test_webhook123',
            'object' => 'payment_intent',
            'amount' => 8995,
            'amount_received' => 8995,
            'currency' => 'aud',
            'status' => 'succeeded',
            'metadata' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ],
        ];

        return [
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => [
                'object' => array_merge($defaultData, $paymentIntentData),
            ],
        ];
    }

    private function signedWebhookRequest(array $payload): array
    {
        $payloadJson = json_encode($payload);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payloadJson}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        $stripeSignature = "t={$timestamp},v1={$signature}";

        return [
            'payload' => $payloadJson,
            'signature' => $stripeSignature,
        ];
    }

    public function test_webhook_rejects_request_without_signature(): void
    {
        $payload = $this->buildStripeEvent('payment_intent.succeeded');

        $response = $this->postJson('/api/webhook/stripe', $payload, [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = $this->buildStripeEvent('payment_intent.succeeded');

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 't=123456,v1=invalidsignature', 'CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response->assertStatus(400);
    }

    public function test_webhook_handles_payment_succeeded_and_updates_order_to_paid(): void
    {
        Event::fake([NewPaidOrderEvent::class]);

        $eventPayload = $this->buildStripeEvent('payment_intent.succeeded', [
            'status' => 'succeeded',
            'amount_received' => 8995,
        ]);

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'PAID',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'event_type' => 'PAID',
        ]);

        $this->assertDatabaseHas('payment_events', [
            'stripe_payment_intent_id' => 'pi_test_webhook123',
            'event_type' => 'payment_intent.succeeded',
            'processed' => 1,
        ]);
    }

    public function test_webhook_handles_payment_failed_and_creates_event(): void
    {
        $eventPayload = $this->buildStripeEvent('payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
            'last_payment_error' => [
                'message' => 'Your card was declined.',
                'code' => 'card_declined',
            ],
        ]);

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200);

        // Order should remain PENDING on payment failure
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'event_type' => 'PAYMENT_FAILED',
        ]);
    }

    public function test_webhook_handles_payment_canceled_and_updates_order_status(): void
    {
        $eventPayload = $this->buildStripeEvent('payment_intent.canceled', [
            'status' => 'canceled',
            'cancellation_reason' => 'abandoned',
        ]);

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'CANCELLED',
        ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'event_type' => 'CANCELLED',
        ]);
    }

    public function test_webhook_handles_payment_requires_action(): void
    {
        $eventPayload = $this->buildStripeEvent('payment_intent.requires_action', [
            'status' => 'requires_action',
            'next_action' => ['type' => 'use_stripe_sdk'],
        ]);

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $this->order->id,
            'event_type' => 'PAYMENT_ACTION_REQUIRED',
        ]);
    }

    public function test_webhook_skips_duplicate_event(): void
    {
        $eventId = 'evt_test_duplicate_123';

        PaymentEvent::create([
            'stripe_event_id' => $eventId,
            'stripe_payment_intent_id' => 'pi_test_webhook123',
            'event_type' => 'payment_intent.succeeded',
            'stripe_data' => [],
            'processed' => true,
        ]);

        $eventPayload = $this->buildStripeEvent('payment_intent.succeeded');
        $eventPayload['id'] = $eventId;

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Event already processed']);

        // Order status should NOT have changed - still PENDING
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_webhook_handles_unknown_payment_intent_gracefully(): void
    {
        $eventPayload = $this->buildStripeEvent('payment_intent.succeeded', [
            'id' => 'pi_nonexistent_payment_intent',
        ]);

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        // Should return 200 even if order not found (Stripe expects 200)
        $response->assertStatus(200);
    }

    public function test_webhook_does_not_reprocess_already_paid_order(): void
    {
        $this->order->update(['status' => 'PAID']);

        $eventPayload = $this->buildStripeEvent('payment_intent.succeeded');

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $response = $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $response->assertStatus(200);

        // Order should remain PAID, no duplicate PAID event
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'PAID',
        ]);
    }

    public function test_payment_event_is_stored_for_each_webhook(): void
    {
        $eventPayload = $this->buildStripeEvent('payment_intent.succeeded');

        ['payload' => $payloadJson, 'signature' => $signature] = $this->signedWebhookRequest($eventPayload);

        $this->call(
            'POST',
            '/api/webhook/stripe',
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payloadJson
        );

        $this->assertDatabaseCount('payment_events', 1);

        $paymentEvent = PaymentEvent::first();
        $this->assertEquals('payment_intent.succeeded', $paymentEvent->event_type);
        $this->assertEquals('pi_test_webhook123', $paymentEvent->stripe_payment_intent_id);
        $this->assertTrue($paymentEvent->processed);
    }
}
