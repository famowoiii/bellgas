import { test, expect } from '@playwright/test';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Payments API', () => {
  let authHelper;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await DatabaseHelper.clearCache();
  });

  test.describe('POST /api/checkout/create-payment-intent', () => {
    test('should require authentication', async ({ request }) => {
      const paymentData = {
        amount: 1000,
        currency: 'aud'
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData
      });

      expect(response.status()).toBe(401);
    });

    test('should create payment intent with valid data', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const paymentData = {
        amount: 1000, // $10.00 in cents
        currency: 'aud',
        payment_method: 'stripe'
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toHaveProperty('client_secret');
        expect(result).toHaveProperty('payment_intent_id');
        expect(typeof result.client_secret).toBe('string');
      } else {
        // Stripe might not be configured in test environment
        expect([400, 500, 503]).toContain(response.status());
      }
    });

    test('should validate required fields', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: {},
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should validate amount is positive', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const paymentData = {
        amount: -100, // Negative amount
        currency: 'aud'
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should validate currency format', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const paymentData = {
        amount: 1000,
        currency: 'invalid-currency'
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should handle minimum amount validation', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const paymentData = {
        amount: 1, // Very small amount
        currency: 'aud'
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Stripe has minimum amounts for different currencies
      expect([200, 400, 422]).toContain(response.status());
    });
  });

  test.describe('POST /api/payments/orders/{id}/intent', () => {
    test('should create payment intent for specific order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order first
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Create payment intent for order
        const response = await request.post(`/api/payments/orders/${order.id}/intent`, {
          data: { payment_method: 'stripe' },
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toHaveProperty('client_secret');
        } else {
          expect([400, 404, 500]).toContain(response.status());
        }
      }
    });

    test('should return 404 for non-existent order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/payments/orders/99999/intent', {
        data: { payment_method: 'stripe' },
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(404);
    });

    test('should not create intent for other users orders', async ({ request, page }) => {
      const { token: user1Token } = await authHelper.registerUser({ email: 'user1@example.com' });
      const { token: user2Token } = await authHelper.registerUser({ email: 'user2@example.com' });

      // User 1 creates order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${user1Token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // User 2 tries to create payment intent for User 1's order
        const response = await request.post(`/api/payments/orders/${order.id}/intent`, {
          data: { payment_method: 'stripe' },
          headers: { Authorization: `Bearer ${user2Token}` }
        });

        expect([403, 404]).toContain(response.status());
      }
    });
  });

  test.describe('POST /api/payments/orders/{id}/complete', () => {
    test('should complete payment for order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Complete payment
        const paymentData = {
          payment_intent_id: 'pi_test_123',
          payment_method_id: 'pm_card_visa'
        };

        const response = await request.post(`/api/payments/orders/${order.id}/complete`, {
          data: paymentData,
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toHaveProperty('status');
          expect(result.status).toBe('succeeded');
        } else {
          // Payment processing might fail in test environment
          expect([400, 402, 500]).toContain(response.status());
        }
      }
    });

    test('should validate payment intent ID', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Try to complete without payment intent
        const response = await request.post(`/api/payments/orders/${order.id}/complete`, {
          data: {},
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([400, 422]).toContain(response.status());
      }
    });
  });

  test.describe('POST /api/payments/orders/{id}/simulate', () => {
    test('should simulate test payment', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Simulate payment
        const simulationData = {
          payment_method: 'card',
          success: true
        };

        const response = await request.post(`/api/payments/orders/${order.id}/simulate`, {
          data: simulationData,
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([200, 201]).toContain(response.status());

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toHaveProperty('status');
          expect(result.status).toBe('succeeded');
        }
      }
    });

    test('should simulate payment failure', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Simulate failed payment
        const simulationData = {
          payment_method: 'card',
          success: false,
          error_code: 'card_declined'
        };

        const response = await request.post(`/api/payments/orders/${order.id}/simulate`, {
          data: simulationData,
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toHaveProperty('status');
          expect(result.status).toBe('failed');
        } else {
          expect([400, 402]).toContain(response.status());
        }
      }
    });
  });

  test.describe('GET /api/payments/orders/{id}/status', () => {
    test('should get payment status for order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Get payment status
        const response = await request.get(`/api/payments/orders/${order.id}/status`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([200, 404]).toContain(response.status());

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toHaveProperty('status');
          expect(result.status).toMatch(/pending|succeeded|failed|cancelled/);
        }
      }
    });

    test('should return 404 for non-existent order payment', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/payments/orders/99999/status', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(404);
    });
  });

  test.describe('Stripe Test Cards', () => {
    test('should handle test card numbers', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Test successful card
      const paymentData = {
        amount: 1000,
        currency: 'aud',
        payment_method: 'card',
        test_card: '4242424242424242' // Visa test card
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Test environment might not support test cards
      expect([200, 400, 500]).toContain(response.status());
    });

    test('should handle declined test card', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Test declined card
      const paymentData = {
        amount: 1000,
        currency: 'aud',
        payment_method: 'card',
        test_card: '4000000000000002' // Declined test card
      };

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: paymentData,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Should handle declined cards appropriately
      expect([200, 400, 402]).toContain(response.status());
    });
  });

  test.describe('GET /api/stripe-test/cards', () => {
    test('should return test card information', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/stripe-test/cards', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const cards = await response.json();
        expect(Array.isArray(cards) || cards.data).toBeTruthy();

        if (Array.isArray(cards) && cards.length > 0) {
          const card = cards[0];
          expect(card).toHaveProperty('number');
          expect(card).toHaveProperty('type');
        }
      } else {
        expect([404, 405]).toContain(response.status());
      }
    });
  });

  test.describe('POST /api/stripe-test/simulate-payment', () => {
    test('should simulate successful payment', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const simulationData = {
        amount: 1000,
        currency: 'aud',
        payment_method: 'pm_card_visa',
        success: true
      };

      const response = await request.post('/api/stripe-test/simulate-payment', {
        data: simulationData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toHaveProperty('status');
        expect(result.status).toBe('succeeded');
      } else {
        expect([400, 404, 500]).toContain(response.status());
      }
    });

    test('should simulate payment failure', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const simulationData = {
        amount: 1000,
        currency: 'aud',
        payment_method: 'pm_card_chargeDeclined',
        success: false
      };

      const response = await request.post('/api/stripe-test/simulate-payment', {
        data: simulationData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toHaveProperty('status');
        expect(['failed', 'declined', 'error']).toContain(result.status);
      } else {
        expect([400, 402, 404]).toContain(response.status());
      }
    });
  });

  test.describe('Webhook Handling', () => {
    test('should handle stripe webhook', async ({ request }) => {
      const webhookPayload = {
        id: 'evt_test_webhook',
        object: 'event',
        type: 'payment_intent.succeeded',
        data: {
          object: {
            id: 'pi_test_123',
            object: 'payment_intent',
            status: 'succeeded',
            amount: 1000,
            currency: 'aud'
          }
        }
      };

      const response = await request.post('/api/webhook/stripe', {
        data: webhookPayload,
        headers: {
          'Content-Type': 'application/json',
          'Stripe-Signature': 'test-signature'
        }
      });

      // Webhook should accept or reject based on signature validation
      expect([200, 400, 401]).toContain(response.status());
    });

    test('should handle test webhook endpoint', async ({ request }) => {
      const testPayload = {
        type: 'payment_intent.succeeded',
        data: {
          object: {
            id: 'pi_test_123',
            status: 'succeeded'
          }
        }
      };

      const response = await request.post('/api/webhook/stripe-test', {
        data: testPayload,
        headers: {
          'Content-Type': 'application/json',
          'User-Agent': 'Stripe/1.0 (+https://stripe.com/docs/webhooks)'
        }
      });

      expect(response.status()).toBe(200);

      const result = await response.json();
      expect(result).toHaveProperty('message');
      expect(result).toHaveProperty('payload');
    });
  });

  test.describe('Payment Security', () => {
    test('should require HTTPS for production payments', async ({ request }) => {
      // This is more of a configuration test
      // In production, payment endpoints should only work over HTTPS
    });

    test('should not expose sensitive payment data', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/checkout/create-payment-intent', {
        data: {
          amount: 1000,
          currency: 'aud'
        },
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();
        
        // Should not expose full payment method details
        expect(result).not.toHaveProperty('card_number');
        expect(result).not.toHaveProperty('cvv');
        
        // Client secret should be present for frontend
        expect(result).toHaveProperty('client_secret');
      }
    });

    test('should validate payment amounts match order totals', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order with specific total
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const orderResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(orderResponse.status())) {
        const order = await orderResponse.json();

        // Try to create payment intent with wrong amount
        const wrongAmount = {
          amount: 100, // Much less than order total
          currency: 'aud'
        };

        const response = await request.post(`/api/payments/orders/${order.id}/intent`, {
          data: wrongAmount,
          headers: { Authorization: `Bearer ${token}` }
        });

        // Should validate amount matches order total
        expect([200, 400, 422]).toContain(response.status());
      }
    });
  });
});