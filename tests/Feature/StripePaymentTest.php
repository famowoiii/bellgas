<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StripeApiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;
    private Product $product;
    private ProductVariant $variant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);

        $this->product = Product::create([
            'name' => 'LPG Full Tank',
            'slug' => 'lpg-full-tank-test',
            'description' => 'Test LPG product',
            'category' => 'FULL_TANK',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => '9kg Cylinder',
            'weight_kg' => 9.00,
            'price_aud' => 89.95,
            'stock_quantity' => 25,
            'is_active' => true,
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_create_payment_intent_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout/create-payment-intent', [
            'fulfillment_method' => 'PICKUP',
            'items' => [
                ['product_variant_id' => $this->variant->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_create_payment_intent_for_pickup_order_successfully(): void
    {
        $mockStripe = Mockery::mock(StripeApiService::class);
        $mockStripe->shouldReceive('calculateAmountInCents')
            ->with(89.95)
            ->andReturn(8995);
        $mockStripe->shouldReceive('createPaymentIntent')
            ->once()
            ->with(8995, 'aud', Mockery::type('array'))
            ->andReturn([
                'id' => 'pi_test_123456',
                'client_secret' => 'pi_test_123456_secret_abc',
                'amount' => 8995,
                'currency' => 'aud',
                'status' => 'requires_payment_method',
            ]);

        $this->app->instance(StripeApiService::class, $mockStripe);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'clientSecret',
                'order' => [
                    'id',
                    'order_number',
                    'status',
                    'fulfillment_method',
                    'subtotal_aud',
                    'shipping_cost_aud',
                    'total_aud',
                    'items',
                ],
                'paymentIntent' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                ],
            ])
            ->assertJson([
                'message' => 'Payment intent created successfully',
                'clientSecret' => 'pi_test_123456_secret_abc',
                'order' => [
                    'status' => 'PENDING',
                    'fulfillment_method' => 'PICKUP',
                    'subtotal_aud' => '89.95',
                    'shipping_cost_aud' => '0.00',
                    'total_aud' => '89.95',
                ],
                'paymentIntent' => [
                    'id' => 'pi_test_123456',
                    'amount' => 8995,
                    'currency' => 'aud',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'PENDING',
            'fulfillment_method' => 'PICKUP',
            'stripe_payment_intent_id' => 'pi_test_123456',
        ]);

        $this->assertDatabaseHas('order_events', [
            'event_type' => 'CREATED',
        ]);
    }

    public function test_create_payment_intent_with_multiple_items(): void
    {
        $variant2 = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => '15kg Cylinder',
            'weight_kg' => 15.00,
            'price_aud' => 129.95,
            'stock_quantity' => 15,
            'is_active' => true,
        ]);

        $expectedTotal = (89.95 * 2) + 129.95; // 309.85
        $expectedCents = (int) round($expectedTotal * 100); // 30985

        $mockStripe = Mockery::mock(StripeApiService::class);
        $mockStripe->shouldReceive('calculateAmountInCents')
            ->with($expectedTotal)
            ->andReturn($expectedCents);
        $mockStripe->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'id' => 'pi_test_multi',
                'client_secret' => 'pi_test_multi_secret',
                'amount' => $expectedCents,
                'currency' => 'aud',
                'status' => 'requires_payment_method',
            ]);

        $this->app->instance(StripeApiService::class, $mockStripe);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 2],
                    ['product_variant_id' => $variant2->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(201);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertEquals('309.85', $order->subtotal_aud);
        $this->assertCount(2, $order->items);
    }

    public function test_create_payment_intent_fails_when_insufficient_stock(): void
    {
        $limitedVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Limited Stock',
            'weight_kg' => 9.00,
            'price_aud' => 89.95,
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $limitedVariant->id, 'quantity' => 5],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'Insufficient stock for LPG Full Tank - Limited Stock. Available: 2']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_payment_intent_fails_with_inactive_product(): void
    {
        $inactiveVariant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Inactive Variant',
            'weight_kg' => 9.00,
            'price_aud' => 89.95,
            'stock_quantity' => 10,
            'is_active' => false,
        ]);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $inactiveVariant->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'One or more products are not available']);
    }

    public function test_create_payment_intent_fails_with_invalid_product_variant(): void
    {
        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => 99999, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
    }

    public function test_create_payment_intent_fails_with_missing_items(): void
    {
        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [],
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
    }

    public function test_create_payment_intent_fails_with_invalid_fulfillment_method(): void
    {
        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'INVALID',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(422);
    }

    public function test_create_payment_intent_returns_503_when_stripe_not_configured(): void
    {
        config(['services.stripe.secret' => 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY']);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(503)
            ->assertJsonFragment(['error' => 'Stripe configuration required']);
    }

    public function test_create_payment_intent_when_stripe_api_throws_exception(): void
    {
        $mockStripe = Mockery::mock(StripeApiService::class);
        $mockStripe->shouldReceive('calculateAmountInCents')->andReturn(8995);
        $mockStripe->shouldReceive('createPaymentIntent')
            ->andThrow(new \Exception('Stripe API unavailable'));

        $this->app->instance(StripeApiService::class, $mockStripe);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent',
            [
                'fulfillment_method' => 'PICKUP',
                'items' => [
                    ['product_variant_id' => $this->variant->id, 'quantity' => 1],
                ],
            ],
            $this->authHeader()
        );

        $response->assertStatus(500)
            ->assertJsonFragment(['message' => 'Failed to create payment intent']);
    }

    public function test_create_payment_intent_for_existing_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout/create-payment-intent-for-order', [
            'order_number' => 'BG-TEST1234',
            'amount' => 8995,
        ]);

        $response->assertStatus(401);
    }

    public function test_create_payment_intent_for_existing_order_successfully(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => 'PENDING',
            'fulfillment_method' => 'PICKUP',
            'subtotal_aud' => 89.95,
            'shipping_cost_aud' => 0,
            'total_aud' => 89.95,
        ]);

        $mockStripe = Mockery::mock(StripeApiService::class);
        $mockStripe->shouldReceive('createPaymentIntent')
            ->once()
            ->with(8995, 'aud', Mockery::type('array'))
            ->andReturn([
                'id' => 'pi_existing_order',
                'client_secret' => 'pi_existing_secret',
                'amount' => 8995,
                'currency' => 'aud',
                'status' => 'requires_payment_method',
            ]);

        $this->app->instance(StripeApiService::class, $mockStripe);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent-for-order',
            [
                'order_number' => $order->order_number,
                'amount' => 8995,
            ],
            $this->authHeader()
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'clientSecret',
                'paymentIntent' => ['id', 'amount', 'currency', 'status'],
            ])
            ->assertJson([
                'message' => 'Payment intent created successfully',
                'clientSecret' => 'pi_existing_secret',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'stripe_payment_intent_id' => 'pi_existing_order',
        ]);
    }

    public function test_create_payment_intent_for_existing_order_not_found(): void
    {
        $response = $this->postJson(
            '/api/checkout/create-payment-intent-for-order',
            [
                'order_number' => 'BG-NOTEXIST',
                'amount' => 8995,
            ],
            $this->authHeader()
        );

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Order not found or not available for payment']);
    }

    public function test_create_payment_intent_for_existing_order_already_paid(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => 'PAID',
            'fulfillment_method' => 'PICKUP',
            'subtotal_aud' => 89.95,
            'shipping_cost_aud' => 0,
            'total_aud' => 89.95,
        ]);

        $response = $this->postJson(
            '/api/checkout/create-payment-intent-for-order',
            [
                'order_number' => $order->order_number,
                'amount' => 8995,
            ],
            $this->authHeader()
        );

        $response->assertStatus(404);
    }

    public function test_get_test_cards_returns_card_list(): void
    {
        $response = $this->getJson('/api/stripe-test/cards', $this->authHeader());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'test_cards' => [
                    '*' => ['number', 'brand', 'description', 'cvc', 'expiry'],
                ],
                'usage_notes',
            ]);

        $cards = $response->json('test_cards');
        $this->assertNotEmpty($cards);

        $cardNumbers = array_column($cards, 'number');
        $this->assertContains('4242424242424242', $cardNumbers);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
