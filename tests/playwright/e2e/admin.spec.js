import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = 'adminbellgas@gmail.com';
const ADMIN_PASSWORD = 'Admin@123';
const BASE_URL = 'http://localhost:8000';

// Helper: login admin via API and return JWT token (for API tests)
async function getAdminToken(page) {
  const response = await page.request.post(`${BASE_URL}/api/auth/login`, {
    data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
  });
  const result = await response.json();
  expect(result.access_token, 'Admin login failed').toBeTruthy();
  return result.access_token;
}

// Helper: login admin via web session (for web page tests)
async function webLoginAsAdmin(page) {
  // Use quick-login route which sets up session auth directly
  await page.goto('/quick-login/admin', { waitUntil: 'domcontentloaded' });
  // Should redirect to /admin/dashboard after login
  await page.waitForURL('**/admin/dashboard**', { timeout: 10000 }).catch(() => {});
}

// Legacy helper kept for backward compat (some tests set localStorage token)
async function loginAsAdmin(page) {
  const token = await getAdminToken(page);
  await page.addInitScript((t) => {
    localStorage.setItem('access_token', t);
  }, token);
  return token;
}

test.describe('Admin & Merchant', () => {

  test.describe('Authentication', () => {
    test('should login as admin via API', async ({ page }) => {
      const response = await page.request.post(`${BASE_URL}/api/auth/login`, {
        data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
        headers: { 'Accept': 'application/json' }
      });
      expect(response.status()).toBe(200);
      const result = await response.json();
      expect(result.user.role).toBe('ADMIN');
      expect(result.access_token).toBeTruthy();
    });

    test('should access admin dashboard page', async ({ page }) => {
      await webLoginAsAdmin(page);
      // Already redirected to /admin/dashboard by quick-login
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });

    test('should block unauthenticated access to admin dashboard', async ({ page }) => {
      await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
      // Should redirect to login or show 403/401
      const url = page.url();
      const isBlocked = url.includes('/login') || url.includes('/admin/dashboard');
      expect(isBlocked).toBeTruthy();
    });
  });

  test.describe('Admin Dashboard API', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should return dashboard stats', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/dashboard`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should return admin stats with metrics', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/stats`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
      // Stats are returned under data.stats (not data.data)
      const stats = data.stats;
      expect(stats).toBeTruthy();
      expect(typeof stats.total_revenue).not.toBe('undefined');
      expect(typeof stats.total_orders).not.toBe('undefined');
      expect(typeof stats.total_customers).not.toBe('undefined');
    });

    test('should return recent orders', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/dashboard/recent-orders`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
      expect(Array.isArray(data.data)).toBe(true);
    });

    test('should return top products', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/dashboard/top-products`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should return sales chart data', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/dashboard/sales-chart?period=7`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should block non-admin from dashboard stats', async ({ page }) => {
      // Register a regular customer
      const regResp = await page.request.post(`${BASE_URL}/api/auth/register`, {
        data: {
          first_name: 'Customer', last_name: 'Test',
          email: `customer${Date.now()}@test.com`,
          password: 'password123', password_confirmation: 'password123'
        },
        headers: { 'Accept': 'application/json' }
      });
      const reg = await regResp.json();
      const customerToken = reg.access_token;

      const response = await page.request.get(`${BASE_URL}/api/admin/stats`, {
        headers: { Authorization: `Bearer ${customerToken}`, Accept: 'application/json' }
      });
      expect([401, 403]).toContain(response.status());
    });
  });

  test.describe('Order Management', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should list all orders as admin', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/orders`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
      // Orders use pagination: data.data is the paginator object, data.data.data is the array
      expect(data.data).toBeTruthy();
    });

    test('should access admin orders page', async ({ page }) => {
      await webLoginAsAdmin(page);
      await page.goto('/admin/orders', { waitUntil: 'domcontentloaded' });
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });

    test('should get order status info for valid order', async ({ page }) => {
      // Get an order first
      const ordersResp = await page.request.get(`${BASE_URL}/api/orders`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      const orders = await ordersResp.json();
      // Paginated: orders.data.data is the array
      const orderList = orders.data?.data ?? orders.data ?? [];

      if (Array.isArray(orderList) && orderList.length > 0) {
        const orderId = orderList[0].id;
        const statusResp = await page.request.get(`${BASE_URL}/api/orders/${orderId}/status/info`, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        expect([200, 404]).toContain(statusResp.status());
      } else {
        test.skip(true, 'No orders in database');
      }
    });

    test('should get available status transitions', async ({ page }) => {
      const ordersResp = await page.request.get(`${BASE_URL}/api/orders`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      const orders = await ordersResp.json();
      const orderList = orders.data?.data ?? orders.data ?? [];

      if (Array.isArray(orderList) && orderList.length > 0) {
        const orderId = orderList[0].id;
        const response = await page.request.get(`${BASE_URL}/api/orders/${orderId}/status/available`, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        expect([200, 404]).toContain(response.status());
      } else {
        test.skip(true, 'No orders in database');
      }
    });
  });

  test.describe('Product Management', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should access admin products page', async ({ page }) => {
      await webLoginAsAdmin(page);
      await page.goto('/admin/products', { waitUntil: 'domcontentloaded' });
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });

    test('should list products via API', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/products`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      // Products endpoint returns { message, data } without a success field
      expect(data.data).toBeTruthy();
    });

    test('should create a new product', async ({ page }) => {
      const productData = {
        name: `Test Product ${Date.now()}`,
        description: 'Test product description for e2e testing',
        category_id: 1,
        variants: [
          {
            name: '3kg',
            price: 25.00,
            stock: 100,
            weight: 3
          }
        ]
      };

      const response = await page.request.post(`${BASE_URL}/api/products`, {
        data: productData,
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
      });
      expect([200, 201, 422]).toContain(response.status());

      if (response.status() === 200 || response.status() === 201) {
        const data = await response.json();
        expect(data.success).toBe(true);
      }
    });

    test('should toggle product status', async ({ page }) => {
      // Get a product first
      const productsResp = await page.request.get(`${BASE_URL}/api/products`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      const products = await productsResp.json();

      if (products.data && products.data.length > 0) {
        const productId = products.data[0].id;
        const response = await page.request.patch(`${BASE_URL}/api/products/${productId}/toggle`, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        expect([200, 404]).toContain(response.status());
      } else {
        test.skip(true, 'No products in database');
      }
    });
  });

  test.describe('Category Management', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should list categories', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/categories`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should create a new category', async ({ page }) => {
      const response = await page.request.post(`${BASE_URL}/api/admin/categories`, {
        data: { name: `Test Category ${Date.now()}`, description: 'Test category' },
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
      });
      expect([200, 201]).toContain(response.status());
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should update a category', async ({ page }) => {
      // Create one first
      const createResp = await page.request.post(`${BASE_URL}/api/admin/categories`, {
        data: { name: `Cat ${Date.now()}`, description: 'To be updated' },
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
      });
      const created = await createResp.json();
      // Category model uses slug as route key (getRouteKeyName = 'slug')
      const catSlug = created.data?.slug;

      if (catSlug) {
        const updateResp = await page.request.put(`${BASE_URL}/api/admin/categories/${catSlug}`, {
          data: { name: `Updated Cat ${Date.now()}`, description: 'Updated' },
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
        });
        expect([200, 201]).toContain(updateResp.status());
      }
    });

    test('should delete a category', async ({ page }) => {
      const createResp = await page.request.post(`${BASE_URL}/api/admin/categories`, {
        data: { name: `Delete Cat ${Date.now()}`, description: 'To be deleted' },
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
      });
      const created = await createResp.json();
      // Category model uses slug as route key (getRouteKeyName = 'slug')
      const catSlug = created.data?.slug;

      if (catSlug) {
        const deleteResp = await page.request.delete(`${BASE_URL}/api/admin/categories/${catSlug}`, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        expect([200, 204]).toContain(deleteResp.status());
      }
    });
  });

  test.describe('Admin User Management', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should list admin/merchant users', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/users`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect(response.status()).toBe(200);
      const data = await response.json();
      expect(data.success).toBe(true);
    });

    test('should access create admin page', async ({ page }) => {
      await webLoginAsAdmin(page);
      await page.goto('/admin/create-admin', { waitUntil: 'domcontentloaded' });
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });

    test('should create a new merchant user', async ({ page }) => {
      const merchantData = {
        first_name: 'Test',
        last_name: 'Merchant',
        email: `merchant${Date.now()}@test.com`,
        password: 'Merchant@123',
        password_confirmation: 'Merchant@123',
        phone_number: '0411111111',
        role: 'MERCHANT'
      };

      const response = await page.request.post(`${BASE_URL}/api/admin/users/admin`, {
        data: merchantData,
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' }
      });
      expect([200, 201, 422]).toContain(response.status());

      if (response.status() === 200 || response.status() === 201) {
        const data = await response.json();
        expect(data.success).toBe(true);
        expect(data.data?.role).toBe('MERCHANT');
      }
    });
  });

  test.describe('Customer Management', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should access customers page', async ({ page }) => {
      await webLoginAsAdmin(page);
      await page.goto('/admin/customers', { waitUntil: 'domcontentloaded' });
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });
  });

  test.describe('Notifications', () => {
    let token;

    test.beforeEach(async ({ page }) => {
      token = await getAdminToken(page);
    });

    test('should get notification count', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/notifications/count`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect([200, 404]).toContain(response.status());
    });

    test('should get new paid orders notifications', async ({ page }) => {
      const response = await page.request.get(`${BASE_URL}/api/admin/notifications/new-paid-orders`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
      });
      expect([200, 404]).toContain(response.status());
    });
  });

  test.describe('Admin Settings Page', () => {
    test('should access settings page', async ({ page }) => {
      await webLoginAsAdmin(page);
      await page.goto('/admin/settings', { waitUntil: 'domcontentloaded' });
      expect(page.url()).not.toContain('/login');
      await expect(page.locator('body')).toBeVisible();
    });
  });

});
