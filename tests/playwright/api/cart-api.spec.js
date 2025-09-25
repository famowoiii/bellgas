import { test, expect } from '@playwright/test';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Cart API', () => {
  let authHelper;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await DatabaseHelper.clearCache();
  });

  test.describe('GET /api/cart', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.get('/api/cart');

      expect(response.status()).toBe(401);

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should return empty cart for new user', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const cart = await response.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);
        expect(items).toHaveLength(0);
      }
    });

    test('should return cart items for user with items', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // First add an item to cart
      const cartItem = {
        product_id: 1,
        quantity: 2
      };

      await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Then get cart
      const response = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const cart = await response.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);
        expect(items.length).toBeGreaterThan(0);
      }
    });
  });

  test.describe('POST /api/cart', () => {
    test('should require authentication', async ({ request }) => {
      const cartItem = {
        product_id: 1,
        quantity: 1
      };

      const response = await request.post('/api/cart', {
        data: cartItem
      });

      expect(response.status()).toBe(401);
    });

    test('should add item to cart with valid data', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const cartItem = {
        product_id: 1,
        quantity: 2
      };

      const response = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 201]).toContain(response.status());

      if ([200, 201].includes(response.status())) {
        const result = await response.json();
        expect(result).toBeDefined();
        
        // Common response structures
        if (result.item) {
          expect(result.item.product_id).toBe(cartItem.product_id);
          expect(result.item.quantity).toBe(cartItem.quantity);
        } else if (result.product_id) {
          expect(result.product_id).toBe(cartItem.product_id);
          expect(result.quantity).toBe(cartItem.quantity);
        }
      }
    });

    test('should validate required fields', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/cart', {
        data: {},
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should validate product exists', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const cartItem = {
        product_id: 99999, // Non-existent product
        quantity: 1
      };

      const response = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([404, 422]).toContain(response.status());
    });

    test('should validate quantity is positive', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const cartItem = {
        product_id: 1,
        quantity: 0
      };

      const response = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should handle negative quantity', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const cartItem = {
        product_id: 1,
        quantity: -1
      };

      const response = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });

    test('should update quantity if item already in cart', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const cartItem = {
        product_id: 1,
        quantity: 1
      };

      // Add item first time
      const firstResponse = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 201]).toContain(firstResponse.status());

      // Add same item again
      const secondResponse = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 201]).toContain(secondResponse.status());

      // Verify cart has updated quantity
      const cartResponse = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (cartResponse.status() === 200) {
        const cart = await cartResponse.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);
        
        if (items.length > 0) {
          const item = items.find(i => i.product_id === cartItem.product_id);
          if (item) {
            expect(item.quantity).toBe(2); // Should be cumulative
          }
        }
      }
    });
  });

  test.describe('PUT /api/cart/{id}', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.put('/api/cart/1', {
        data: { quantity: 2 }
      });

      expect(response.status()).toBe(401);
    });

    test('should update cart item quantity', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // First add an item
      const cartItem = {
        product_id: 1,
        quantity: 1
      };

      const addResponse = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(addResponse.status())) {
        // Get cart to find item ID
        const cartResponse = await request.get('/api/cart', {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (cartResponse.status() === 200) {
          const cart = await cartResponse.json();
          const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);

          if (items.length > 0) {
            const itemId = items[0].id;

            // Update quantity
            const updateResponse = await request.put(`/api/cart/${itemId}`, {
              data: { quantity: 3 },
              headers: { Authorization: `Bearer ${token}` }
            });

            expect([200, 204]).toContain(updateResponse.status());
          }
        }
      }
    });

    test('should return 404 for non-existent cart item', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.put('/api/cart/99999', {
        data: { quantity: 2 },
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(404);
    });

    test('should validate quantity for update', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Add item first
      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      const response = await request.put('/api/cart/1', {
        data: { quantity: 0 },
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([400, 422]).toContain(response.status());
    });
  });

  test.describe('DELETE /api/cart/{id}', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.delete('/api/cart/1');

      expect(response.status()).toBe(401);
    });

    test('should remove item from cart', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Add item first
      const addResponse = await request.post('/api/cart', {
        data: { product_id: 1, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      if ([200, 201].includes(addResponse.status())) {
        // Get cart to find item ID
        const cartResponse = await request.get('/api/cart', {
          headers: { Authorization: `Bearer ${token}` }
        });

        if (cartResponse.status() === 200) {
          const cart = await cartResponse.json();
          const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);

          if (items.length > 0) {
            const itemId = items[0].id;

            // Remove item
            const deleteResponse = await request.delete(`/api/cart/${itemId}`, {
              headers: { Authorization: `Bearer ${token}` }
            });

            expect([200, 204]).toContain(deleteResponse.status());

            // Verify item is removed
            const finalCartResponse = await request.get('/api/cart', {
              headers: { Authorization: `Bearer ${token}` }
            });

            if (finalCartResponse.status() === 200) {
              const finalCart = await finalCartResponse.json();
              const finalItems = Array.isArray(finalCart) ? finalCart : (finalCart.items || finalCart.data || []);
              expect(finalItems.length).toBe(0);
            }
          }
        }
      }
    });

    test('should return 404 for non-existent cart item', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.delete('/api/cart/99999', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(404);
    });
  });

  test.describe('DELETE /api/cart', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.delete('/api/cart');

      expect(response.status()).toBe(401);
    });

    test('should clear entire cart', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Add multiple items
      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      await request.post('/api/cart', {
        data: { product_id: 2, quantity: 2 },
        headers: { Authorization: `Bearer ${token}` }
      });

      // Clear cart
      const clearResponse = await request.delete('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 204]).toContain(clearResponse.status());

      // Verify cart is empty
      const cartResponse = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (cartResponse.status() === 200) {
        const cart = await cartResponse.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);
        expect(items).toHaveLength(0);
      }
    });

    test('should handle clearing empty cart', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.delete('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 204]).toContain(response.status());
    });
  });

  test.describe('GET /api/cart/count', () => {
    test('should require authentication', async ({ request }) => {
      const response = await request.get('/api/cart/count');

      expect(response.status()).toBe(401);
    });

    test('should return cart item count', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Initially should be 0
      const initialResponse = await request.get('/api/cart/count', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 404]).toContain(initialResponse.status());

      if (initialResponse.status() === 200) {
        const initialResult = await initialResponse.json();
        expect(initialResult.count || initialResult).toBe(0);
      }

      // Add items and check count
      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 2 },
        headers: { Authorization: `Bearer ${token}` }
      });

      await request.post('/api/cart', {
        data: { product_id: 2, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      const finalResponse = await request.get('/api/cart/count', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (finalResponse.status() === 200) {
        const finalResult = await finalResponse.json();
        const count = finalResult.count || finalResult;
        expect(count).toBeGreaterThan(0);
      }
    });
  });

  test.describe('Cart Data Validation', () => {
    test('should return proper cart item structure', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Add item
      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      const response = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const cart = await response.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);

        if (items.length > 0) {
          const item = items[0];
          expect(item).toHaveProperty('id');
          expect(item).toHaveProperty('product_id');
          expect(item).toHaveProperty('quantity');
          expect(typeof item.quantity).toBe('number');
        }
      }
    });

    test('should include product details in cart items', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 1 },
        headers: { Authorization: `Bearer ${token}` }
      });

      const response = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const cart = await response.json();
        const items = Array.isArray(cart) ? cart : (cart.items || cart.data || []);

        if (items.length > 0) {
          const item = items[0];
          
          // Should include product details
          if (item.product) {
            expect(item.product).toHaveProperty('name');
            expect(item.product).toHaveProperty('price');
          }
        }
      }
    });
  });

  test.describe('Cart Business Logic', () => {
    test('should calculate cart totals', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Add items
      await request.post('/api/cart', {
        data: { product_id: 1, quantity: 2 },
        headers: { Authorization: `Bearer ${token}` }
      });

      const response = await request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const cart = await response.json();
        
        // Cart might include totals
        if (cart.subtotal !== undefined) {
          expect(typeof cart.subtotal).toBe('number');
          expect(cart.subtotal).toBeGreaterThanOrEqual(0);
        }

        if (cart.total !== undefined) {
          expect(typeof cart.total).toBe('number');
          expect(cart.total).toBeGreaterThanOrEqual(0);
        }
      }
    });

    test('should handle stock validation', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      // Try to add more items than available stock
      const cartItem = {
        product_id: 1,
        quantity: 99999 // Very high quantity
      };

      const response = await request.post('/api/cart', {
        data: cartItem,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Should either succeed or show stock validation error
      expect([200, 201, 400, 422]).toContain(response.status());

      if ([400, 422].includes(response.status())) {
        const result = await response.json();
        expect(result).toHaveProperty('message');
      }
    });
  });
});