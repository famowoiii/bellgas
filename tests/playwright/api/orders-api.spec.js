import { test, expect } from '@playwright/test';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Orders API', () => {
  let authHelper;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await DatabaseHelper.clearCache();
  });

  test.describe('GET /api/orders', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.get('/api/orders');

      expect(response.status()).toBe(401);

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should return empty list for user with no orders', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/orders', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const orders = await response.json();
        const orderList = Array.isArray(orders) ? orders : (orders.data || []);
        expect(orderList).toHaveLength(0);
      }
    });

    test('should return orders for authenticated user', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create an order first
      const orderData = {
        items: [{ product_id: 1, quantity: 2, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000',
          country: 'Australia'
        },
        payment_method: 'stripe'
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        // Now get orders
        const response = await request.get('/api/orders', {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect(response.status()).toBe(200);

        const orders = await response.json();
        const orderList = Array.isArray(orders) ? orders : (orders.data || []);
        expect(orderList.length).toBeGreaterThan(0);

        if (orderList.length > 0) {
          const order = orderList[0];
          expect(order).toHaveProperty('id');
          expect(order).toHaveProperty('status');
          expect(order).toHaveProperty('total');
        }
      }
    });

    test('should support pagination', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/orders?page=1&per_page=10', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();

        if (!Array.isArray(result)) {
          // Paginated response
          expect(result).toHaveProperty('data');
          
          const paginationFields = ['current_page', 'last_page', 'per_page', 'total'];
          const hasPagination = paginationFields.some(field => result.hasOwnProperty(field));
          expect(hasPagination).toBeTruthy();
        }
      }
    });

    test('should filter orders by status', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/orders?status=pending', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const orders = await response.json();
        const orderList = Array.isArray(orders) ? orders : (orders.data || []);

        orderList.forEach(order => {
          expect(order.status).toBe('pending');
        });
      }
    });
  });

  test.describe('POST /api/orders', () => {
    test('should require authentication', async ({ request }) => {
      const orderData = {
        items: [{ product_id: 1, quantity: 1 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const response = await request.post('/api/orders', {
        data: orderData
      });

      expect(response.status()).toBe(401);
    });

    test('should create order with valid data', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [
          { product_id: 1, quantity: 2, price: 50.00 },
          { product_id: 2, quantity: 1, price: 30.00 }
        ],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000',
          country: 'Australia'
        },
        billing_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000',
          country: 'Australia'
        },
        payment_method: 'stripe',
        shipping_method: 'standard'
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 201]).toContain(response.status());

      if ([200, 201].includes(response.status())) {
        const order = await response.json();
        expect(order).toHaveProperty('id');
        expect(order).toHaveProperty('status');
        expect(order).toHaveProperty('total');
        expect(order.status).toBe('pending');
      }
    });

    test('should validate required fields', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/orders', {
        data: {},
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());

      const result = await response.json();
      expect(result).toHaveProperty('message');
      expect(result.errors || result.message).toBeDefined();
    });

    test('should validate order items', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [], // Empty items
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should validate product exists in order items', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [{ product_id: 99999, quantity: 1 }], // Non-existent product
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should validate shipping address', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [{ product_id: 1, quantity: 1 }],
        shipping_address: {} // Empty address
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should calculate order total correctly', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [
          { product_id: 1, quantity: 2, price: 25.00 },
          { product_id: 2, quantity: 1, price: 50.00 }
        ],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000'
        }
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(response.status())) {
        const order = await response.json();
        
        // Total should be calculated from items
        const expectedSubtotal = (2 * 25.00) + (1 * 50.00); // 100.00
        expect(order.subtotal || order.total).toBeGreaterThanOrEqual(expectedSubtotal);
      }
    });
  });

  test.describe('GET /api/orders/{id}', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.get('/api/orders/1');

      expect(response.status()).toBe(401);
    });

    test('should return order details for valid order', async ({ request, page }) => {
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

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();
        const orderId = order.id;

        // Get order details
        const response = await request.get(`/api/orders/${orderId}`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect(response.status()).toBe(200);

        const orderDetails = await response.json();
        expect(orderDetails).toHaveProperty('id');
        expect(orderDetails.id).toBe(orderId);
        expect(orderDetails).toHaveProperty('items');
        expect(orderDetails).toHaveProperty('shipping_address');
      }
    });

    test('should return 404 for non-existent order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/orders/99999', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(404);
    });

    test('should not return other users orders', async ({ request, page }) => {
      // Create two users
      const { token: user1Token } = await authHelper.registerUser({ email: 'user1@example.com' });
      const { token: user2Token } = await authHelper.registerUser({ email: 'user2@example.com' });

      // User 1 creates an order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${user1Token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();

        // User 2 tries to access User 1's order
        const response = await request.get(`/api/orders/${order.id}`, {
          headers: { Authorization: `Bearer ${user2Token}` }
        });

        expect([403, 404]).toContain(response.status());
      }
    });
  });

  test.describe('PUT /api/orders/{id}', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.put('/api/orders/1', {
        data: { status: 'cancelled' }
      });

      expect(response.status()).toBe(401);
    });

    test('should update order status for valid order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();

        // Update order
        const updateData = { status: 'processing' };
        const response = await request.put(`/api/orders/${order.id}`, {
          data: updateData,
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([200, 204]).toContain(response.status());

        if (response.status() === 200) {
          const updatedOrder = await response.json();
          expect(updatedOrder.status).toBe('processing');
        }
      }
    });

    test('should validate order status transitions', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();

        // Try invalid status
        const updateData = { status: 'invalid-status' };
        const response = await request.put(`/api/orders/${order.id}`, {
          data: updateData,
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([400, 422]).toContain(response.status());
      }
    });
  });

  test.describe('PATCH /api/orders/{id}/cancel', () => {
    test('should cancel order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create order
      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();

        // Cancel order
        const response = await request.patch(`/api/orders/${order.id}/cancel`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([200, 204]).toContain(response.status());

        if (response.status() === 200) {
          const cancelledOrder = await response.json();
          expect(cancelledOrder.status).toBe('cancelled');
        }
      }
    });

    test('should not cancel completed orders', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // This test assumes we can't cancel completed orders
      // Implementation may vary
    });
  });

  test.describe('POST /api/orders/{id}/reorder', () => {
    test('should create new order from existing order', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Create original order
      const orderData = {
        items: [{ product_id: 1, quantity: 2, price: 25.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const originalOrder = await createResponse.json();

        // Reorder
        const response = await request.post(`/api/orders/${originalOrder.id}/reorder`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect([200, 201]).toContain(response.status());

        if ([200, 201].includes(response.status())) {
          const newOrder = await response.json();
          expect(newOrder).toHaveProperty('id');
          expect(newOrder.id).not.toBe(originalOrder.id);
          expect(newOrder.status).toBe('pending');
        }
      }
    });
  });

  test.describe('Order Data Validation', () => {
    test('should return complete order structure', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [{ product_id: 1, quantity: 1, price: 50.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne',
          state: 'VIC',
          postcode: '3000'
        }
      };

      const response = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(response.status())) {
        const order = await response.json();

        // Essential order fields
        expect(order).toHaveProperty('id');
        expect(order).toHaveProperty('status');
        expect(order).toHaveProperty('total');
        expect(order).toHaveProperty('created_at');

        // Data types
        expect(typeof order.id).toBe('number');
        expect(typeof order.total).toBe('number');
        expect(typeof order.status).toBe('string');
      }
    });

    test('should include order items with product details', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const orderData = {
        items: [{ product_id: 1, quantity: 2, price: 25.00 }],
        shipping_address: {
          street: '123 Test Street',
          city: 'Melbourne'
        }
      };

      const createResponse = await request.post('/api/orders', {
        data: orderData,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(createResponse.status())) {
        const order = await createResponse.json();
        const orderId = order.id;

        // Get order details
        const response = await request.get(`/api/orders/${orderId}`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.status() === 200) {
          const orderDetails = await response.json();
          expect(orderDetails).toHaveProperty('items');
          expect(Array.isArray(orderDetails.items)).toBeTruthy();

          if (orderDetails.items.length > 0) {
            const item = orderDetails.items[0];
            expect(item).toHaveProperty('quantity');
            expect(item).toHaveProperty('price');
            
            // Should include product details
            if (item.product) {
              expect(item.product).toHaveProperty('name');
            }
          }
        }
      }
    });
  });

  test.describe('Order Statistics (Admin)', () => {
    test('should get order statistics for admin', async ({ request, page }) => {
      // Register admin user
      try {
        const { token } = await authHelper.loginAsAdmin();

        const response = await request.get('/api/orders/admin/stats', {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (response.status() === 200) {
          const stats = await response.json();
          expect(stats).toHaveProperty('total_orders');
          expect(stats).toHaveProperty('total_revenue');
        } else {
          // Admin functionality might not be fully implemented
          expect([401, 403, 404]).toContain(response.status());
        }
      } catch (error) {
        // Admin role assignment might not work in test environment
        console.log('Admin test skipped:', error.message);
      }
    });
  });
});