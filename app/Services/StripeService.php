<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Stripe\Event;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\CardException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret_key'));
    }

    /**
     * Create a payment intent with specified amount, currency, and metadata
     */
    public function createPaymentIntent(
        int $amount,
        string $currency = 'aud',
        array $metadata = [],
        string $idempotencyKey = null
    ): PaymentIntent {
        try {
            $params = [
                'amount' => $amount,
                'currency' => strtolower($currency),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => $metadata,
            ];

            $requestOptions = [];
            if ($idempotencyKey) {
                $requestOptions['idempotency_key'] = $idempotencyKey;
            }

            $paymentIntent = $this->stripe->paymentIntents->create($params, $requestOptions);

            Log::info('Payment Intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
                'metadata' => $metadata
            ]);

            return $paymentIntent;
        } catch (CardException $e) {
            Log::error('Stripe Card Error', [
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Stripe Payment Intent Creation Failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a payment intent by ID
     */
    public function retrieve(string $paymentIntentId): PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (\Exception $e) {
            Log::error('Stripe Payment Intent Retrieval Failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a payment intent
     */
    public function cancel(string $paymentIntentId): PaymentIntent
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->cancel($paymentIntentId);

            Log::info('Payment Intent cancelled', [
                'payment_intent_id' => $paymentIntentId
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Stripe Payment Intent Cancellation Failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Construct webhook event from payload and signature
     */
    public function constructWebhookEvent(string $payload, string $signature, string $webhookSecret): Event
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );

            Log::info('Webhook event constructed', [
                'event_id' => $event->id,
                'event_type' => $event->type
            ]);

            return $event;
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => $signature
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Webhook event construction failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test Stripe connection
     */
    public function testConnection(): array
    {
        try {
            // Create a minimal payment intent to test connection
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => 100,
                'currency' => 'aud',
                'metadata' => [
                    'test' => 'true',
                    'source' => 'connection_test'
                ]
            ]);

            // Immediately cancel it since it's just a test
            $this->stripe->paymentIntents->cancel($paymentIntent->id);

            return [
                'success' => true,
                'message' => 'Stripe connection successful',
                'data' => [
                    'test_payment_intent_id' => $paymentIntent->id,
                    'api_version' => \Stripe\Stripe::getApiVersion()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get test card numbers for development
     */
    public function getTestCards(): array
    {
        return [
            'visa' => [
                'number' => '4242424242424242',
                'cvc' => '123',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'Visa - Succeeds'
            ],
            'visa_debit' => [
                'number' => '4000056655665556',
                'cvc' => '123',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'Visa Debit - Succeeds'
            ],
            'mastercard' => [
                'number' => '5555555555554444',
                'cvc' => '123',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'Mastercard - Succeeds'
            ],
            'amex' => [
                'number' => '378282246310005',
                'cvc' => '1234',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'American Express - Succeeds'
            ],
            'declined' => [
                'number' => '4000000000000002',
                'cvc' => '123',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'Generic decline'
            ],
            'insufficient_funds' => [
                'number' => '4000000000009995',
                'cvc' => '123',
                'exp_month' => '12',
                'exp_year' => date('Y') + 2,
                'description' => 'Insufficient funds decline'
            ]
        ];
    }

    // Legacy method compatibility
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->retrieve($paymentIntentId);
    }

    public function cancelPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->cancel($paymentIntentId);
    }

    public function calculateAmountInCents(float $amountAud): int
    {
        return (int) round($amountAud * 100);
    }

    public function formatAmountFromCents(int $amountCents): float
    {
        return round($amountCents / 100, 2);
    }
}