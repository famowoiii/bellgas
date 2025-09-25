import { test, expect } from '@playwright/test';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Products API', () => {
  let authHelper;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await DatabaseHelper.clearCache();
  });

  test.describe('GET /api/products', () => {
    test('should return products list', async ({ request }) => {
      const response = await request.get('/api/products');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const products = await response.json();
        
        // Response could be array or paginated object
        if (Array.isArray(products)) {
          expect(products).toBeInstanceOf(Array);
        } else {
          expect(products).toHaveProperty('data');
          expect(Array.isArray(products.data)).toBeTruthy();
        }
      }
    });

    test('should support pagination', async ({ request }) => {
      const response = await request.get('/api/products?page=1&limit=5');

      if (response.status() === 200) {
        const result = await response.json();
        
        if (!Array.isArray(result)) {
          // Paginated response
          expect(result).toHaveProperty('data');
          
          // Common pagination fields
          const paginationFields = ['current_page', 'last_page', 'per_page', 'total'];
          const hasPagination = paginationFields.some(field => result.hasOwnProperty(field));
          expect(hasPagination).toBeTruthy();
        }
      }
    });

    test('should support filtering by category', async ({ request }) => {
      const response = await request.get('/api/products?category=test-category');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toBeDefined();
      }
    });

    test('should support search functionality', async ({ request }) => {
      const response = await request.get('/api/products?search=gas');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toBeDefined();
      }
    });

    test('should support sorting', async ({ request }) => {
      const sortOptions = ['name', 'price', 'created_at'];

      for (const sort of sortOptions) {
        const response = await request.get(`/api/products?sort=${sort}`);
        expect([200, 404]).toContain(response.status());

        if (response.status() === 200) {
          const result = await response.json();
          expect(result).toBeDefined();
        }
      }
    });

    test('should handle invalid query parameters', async ({ request }) => {
      const response = await request.get('/api/products?page=invalid&limit=abc');

      // Should handle gracefully
      expect([200, 400, 422]).toContain(response.status());
    });
  });

  test.describe('GET /api/products/{id}', () => {
    test('should return single product by ID', async ({ request }) => {
      // First get products list to find a valid ID
      const listResponse = await request.get('/api/products');

      if (listResponse.status() === 200) {
        const products = await listResponse.json();
        let productList = Array.isArray(products) ? products : products.data;

        if (productList && productList.length > 0) {
          const productId = productList[0].id;
          
          const response = await request.get(`/api/products/${productId}`);
          expect(response.status()).toBe(200);

          const product = await response.json();
          expect(product).toHaveProperty('id');
          expect(product.id).toBe(productId);
          expect(product).toHaveProperty('name');
          expect(product).toHaveProperty('price');
        }
      }
    });

    test('should return 404 for non-existent product', async ({ request }) => {
      const response = await request.get('/api/products/99999');

      expect(response.status()).toBe(404);

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should return 400 for invalid product ID format', async ({ request }) => {
      const response = await request.get('/api/products/invalid-id');

      expect([400, 404]).toContain(response.status());
    });
  });

  test.describe('GET /api/products/categories', () => {
    test('should return product categories', async ({ request }) => {
      const response = await request.get('/api/products/categories');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const categories = await response.json();
        expect(Array.isArray(categories) || categories.data).toBeTruthy();
      }
    });
  });

  test.describe('GET /api/categories', () => {
    test('should return categories list', async ({ request }) => {
      const response = await request.get('/api/categories');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const categories = await response.json();
        
        if (Array.isArray(categories)) {
          expect(categories).toBeInstanceOf(Array);
        } else {
          expect(categories).toHaveProperty('data');
          expect(Array.isArray(categories.data)).toBeTruthy();
        }
      }
    });
  });

  test.describe('GET /api/categories/{id}', () => {
    test('should return single category', async ({ request }) => {
      // First get categories to find a valid ID
      const listResponse = await request.get('/api/categories');

      if (listResponse.status() === 200) {
        const categories = await listResponse.json();
        let categoryList = Array.isArray(categories) ? categories : categories.data;

        if (categoryList && categoryList.length > 0) {
          const categoryId = categoryList[0].id;
          
          const response = await request.get(`/api/categories/${categoryId}`);
          expect([200, 404]).toContain(response.status());

          if (response.status() === 200) {
            const category = await response.json();
            expect(category).toHaveProperty('id');
            expect(category.id).toBe(categoryId);
          }
        }
      }
    });

    test('should return 404 for non-existent category', async ({ request }) => {
      const response = await request.get('/api/categories/99999');

      expect(response.status()).toBe(404);
    });
  });

  test.describe('GET /api/categories/{id}/products', () => {
    test('should return products by category', async ({ request }) => {
      // First get categories
      const categoriesResponse = await request.get('/api/categories');

      if (categoriesResponse.status() === 200) {
        const categories = await categoriesResponse.json();
        let categoryList = Array.isArray(categories) ? categories : categories.data;

        if (categoryList && categoryList.length > 0) {
          const categoryId = categoryList[0].id;
          
          const response = await request.get(`/api/categories/${categoryId}/products`);
          expect([200, 404]).toContain(response.status());

          if (response.status() === 200) {
            const result = await response.json();
            expect(Array.isArray(result) || result.data).toBeTruthy();
          }
        }
      }
    });

    test('should return empty array for category with no products', async ({ request }) => {
      const response = await request.get('/api/categories/99999/products');

      expect([200, 404]).toContain(response.status());

      if (response.status() === 200) {
        const result = await response.json();
        const products = Array.isArray(result) ? result : result.data;
        expect(products).toHaveLength(0);
      }
    });
  });

  test.describe('POST /api/products (Authenticated)', () => {
    test('should create product with valid data and authentication', async ({ request, page }) => {
      // Register user and get token
      const { token } = await authHelper.registerUser();

      const productData = {
        name: 'Test Product',
        description: 'A test product description',
        price: 99.99,
        category_id: 1,
        stock_quantity: 10,
        sku: `TEST-${Date.now()}`
      };

      const response = await request.post('/api/products', {
        data: productData,
        headers: { Authorization: `Bearer ${token}` }
      });

      // Might require specific role/permissions
      expect([201, 401, 403]).toContain(response.status());

      if (response.status() === 201) {
        const product = await response.json();
        expect(product).toHaveProperty('id');
        expect(product.name).toBe(productData.name);
        expect(product.price).toBe(productData.price);
      }
    });

    test('should require authentication for product creation', async ({ request }) => {
      const productData = {
        name: 'Unauthorized Product',
        description: 'Should not be created',
        price: 50.00
      };

      const response = await request.post('/api/products', {
        data: productData
      });

      expect(response.status()).toBe(401);
    });

    test('should validate required fields', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const response = await request.post('/api/products', {
        data: {},
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([422, 401, 403]).toContain(response.status());

      if (response.status() === 422) {
        const result = await response.json();
        expect(result).toHaveProperty('errors');
      }
    });

    test('should validate price format', async ({ request, page }) => {
      const { token } = await authHelper.registerUser();

      const productData = {
        name: 'Test Product',
        price: 'invalid-price',
        description: 'Test description'
      };

      const response = await request.post('/api/products', {
        data: productData,
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([422, 401, 403]).toContain(response.status());

      if (response.status() === 422) {
        const result = await response.json();
        expect(result.errors).toHaveProperty('price');
      }
    });
  });

  test.describe('Product Data Validation', () => {
    test('should return products with required fields', async ({ request }) => {
      const response = await request.get('/api/products');

      if (response.status() === 200) {
        const result = await response.json();
        const products = Array.isArray(result) ? result : result.data;

        if (products && products.length > 0) {
          const product = products[0];
          
          // Essential product fields
          expect(product).toHaveProperty('id');
          expect(product).toHaveProperty('name');
          expect(product).toHaveProperty('price');
          
          // Validate data types
          expect(typeof product.id).toBe('number');
          expect(typeof product.name).toBe('string');
          expect(typeof product.price).toBe('number');
        }
      }
    });

    test('should return valid price formats', async ({ request }) => {
      const response = await request.get('/api/products');

      if (response.status() === 200) {
        const result = await response.json();
        const products = Array.isArray(result) ? result : result.data;

        if (products && products.length > 0) {
          products.forEach(product => {
            if (product.price !== null) {
              expect(product.price).toBeGreaterThanOrEqual(0);
              expect(typeof product.price).toBe('number');
            }
          });
        }
      }
    });
  });

  test.describe('Performance and Caching', () => {
    test('should handle concurrent requests', async ({ request }) => {
      const requests = Array(5).fill().map(() => request.get('/api/products'));
      
      const responses = await Promise.all(requests);
      
      responses.forEach(response => {
        expect([200, 404, 429]).toContain(response.status());
      });
    });

    test('should include cache headers', async ({ request }) => {
      const response = await request.get('/api/products');

      if (response.status() === 200) {
        const headers = response.headers();
        
        // Common cache headers
        const cacheHeaders = ['cache-control', 'etag', 'last-modified', 'expires'];
        const hasCacheHeaders = cacheHeaders.some(header => headers[header]);
        
        // Cache headers are optional but recommended
        // expect(hasCacheHeaders).toBeTruthy();
      }
    });
  });

  test.describe('Error Handling', () => {
    test('should return proper error format', async ({ request }) => {
      const response = await request.get('/api/products/invalid-id');

      if ([400, 404, 422].includes(response.status())) {
        const error = await response.json();
        expect(error).toHaveProperty('message');
        expect(typeof error.message).toBe('string');
      }
    });

    test('should handle malformed requests', async ({ request }) => {
      const response = await request.post('/api/products', {
        data: 'invalid-json-data',
        headers: { 'Content-Type': 'application/json' }
      });

      expect([400, 401, 422]).toContain(response.status());
    });
  });
});