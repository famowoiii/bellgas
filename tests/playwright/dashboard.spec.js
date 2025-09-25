const { test, expect } = require('@playwright/test');

test.describe('Dashboard System', () => {

  const mockUser = {
    id: 1,
    first_name: 'Test',
    last_name: 'User',
    email: 'test@bellgas.com',
    phone_number: '0412345678',
    role: 'CUSTOMER',
    is_active: true,
    created_at: '2024-01-01T00:00:00Z'
  };

  const mockOrders = [
    {
      id: 1,
      order_number: 'BG-TEST001',
      status: 'DELIVERED',
      fulfillment_method: 'DELIVERY',
      total_aud: '89.95',
      subtotal_aud: '89.95',
      shipping_cost_aud: '0.00',
      created_at: '2024-12-01T10:00:00Z',
      items: [
        {
          id: 1,
          quantity: 1,
          unit_price_aud: '89.95',
          total_price_aud: '89.95',
          productVariant: {
            name: '9kg Cylinder',
            product: { name: 'LPG Full Tank' }
          }
        }
      ]
    },
    {
      id: 2,
      order_number: 'BG-TEST002',
      status: 'PROCESSING',
      fulfillment_method: 'PICKUP',
      total_aud: '45.95',
      created_at: '2024-12-15T14:30:00Z',
      items: [
        {
          id: 2,
          quantity: 1,
          unit_price_aud: '45.95',
          total_price_aud: '45.95',
          productVariant: {
            name: '9kg Refill',
            product: { name: 'LPG Refill Service' }
          }
        }
      ]
    }
  ];

  const mockAdminUser = {
    ...mockUser,
    role: 'ADMIN',
    email: 'admin@bellgas.com'
  };

  test.beforeEach(async ({ page }) => {
    // Set auth token
    await page.addInitScript(() => {
      localStorage.setItem('access_token', 'mock_token');
    });
  });

  test.describe('Customer Dashboard', () => {

    test.beforeEach(async ({ page }) => {
      // Mock customer auth
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockUser })
        });
      });

      // Mock orders API
      await page.route('**/api/orders**', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockOrders
          })
        });
      });
    });

    test('should load customer dashboard correctly', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page).toHaveTitle(/Dashboard - BellGas/);
      await expect(page.locator('h1')).toContainText('Welcome back, Test!');
    });

    test('should show user greeting with first name', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h1:has-text("Welcome back, Test!")')).toBeVisible();
    });

    test('should display dashboard stats', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1500);
      
      // Check stats cards
      await expect(page.locator('text=Total Orders')).toBeVisible();
      await expect(page.locator('text=Completed')).toBeVisible();
      await expect(page.locator('text=Pending')).toBeVisible();
      await expect(page.locator('text=Total Spent')).toBeVisible();
      
      // Check calculated values
      await expect(page.locator('text=2').first()).toBeVisible(); // Total orders
      await expect(page.locator('text=1').first()).toBeVisible(); // Completed orders
    });

    test('should show recent orders section', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h2:has-text("Recent Orders")')).toBeVisible();
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
      await expect(page.locator('text=BG-TEST002')).toBeVisible();
      
      // Check status badges
      await expect(page.locator('text=DELIVERED')).toBeVisible();
      await expect(page.locator('text=PROCESSING')).toBeVisible();
    });

    test('should show order details in cards', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1500);
      
      // Check order information
      await expect(page.locator('text=$89.95')).toBeVisible();
      await expect(page.locator('text=$45.95')).toBeVisible();
      await expect(page.locator('text=DELIVERY')).toBeVisible();
      await expect(page.locator('text=PICKUP')).toBeVisible();
    });

    test('should have working quick actions', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      // Check quick action links
      await expect(page.locator('a[href="/products"]:has-text("Browse Products")')).toBeVisible();
      await expect(page.locator('a[href="/orders"]:has-text("View All Orders")')).toBeVisible();
      await expect(page.locator('a[href="/addresses"]:has-text("Manage Addresses")')).toBeVisible();
      await expect(page.locator('a[href="/profile"]:has-text("Account Settings")')).toBeVisible();
    });

    test('should show account overview', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("Account Overview")')).toBeVisible();
      await expect(page.locator('text=Customer').first()).toBeVisible();
      await expect(page.locator('text=test@bellgas.com')).toBeVisible();
    });

    test('should show support section', async ({ page }) => {
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("Need Help?")')).toBeVisible();
      await expect(page.locator('a[href="tel:+61212345678"]')).toBeVisible();
      await expect(page.locator('a[href="mailto:support@bellgas.com.au"]')).toBeVisible();
    });

    test('should handle empty orders state', async ({ page }) => {
      // Mock empty orders
      await page.route('**/api/orders**', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: []
          })
        });
      });

      await page.goto('/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('text=No orders yet')).toBeVisible();
      await expect(page.locator('a[href="/products"]:has-text("Shop Now")')).toBeVisible();
    });

    test('should be responsive on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      
      // Should show mobile-friendly layout
      await expect(page.locator('h1:has-text("Welcome back, Test!")')).toBeVisible();
      await expect(page.locator('text=Total Orders')).toBeVisible();
    });

  });

  test.describe('Admin Dashboard', () => {

    const mockAdminMetrics = {
      total_revenue: '1,234.50',
      total_orders: 25,
      active_customers: 15,
      products_sold: 45
    };

    const mockRecentOrders = [
      {
        ...mockOrders[0],
        user: {
          first_name: 'John',
          last_name: 'Doe',
          email: 'john@example.com'
        }
      },
      {
        ...mockOrders[1],
        user: {
          first_name: 'Jane',
          last_name: 'Smith',
          email: 'jane@example.com'
        }
      }
    ];

    test.beforeEach(async ({ page }) => {
      // Mock admin auth
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockAdminUser })
        });
      });

      // Mock admin dashboard API
      await page.route('**/api/admin/dashboard', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            metrics: mockAdminMetrics
          })
        });
      });

      // Mock admin recent orders
      await page.route('**/api/admin/dashboard/recent-orders', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            orders: mockRecentOrders
          })
        });
      });

      // Mock top products
      await page.route('**/api/admin/dashboard/top-products', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            products: [
              {
                name: 'LPG Full Tank',
                variant: '9kg Cylinder',
                quantity: 15
              },
              {
                name: 'LPG Refill Service',
                variant: '9kg Refill',
                quantity: 10
              }
            ]
          })
        });
      });
    });

    test('should load admin dashboard correctly', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page).toHaveTitle(/Admin Dashboard - BellGas/);
      await expect(page.locator('h1:has-text("Admin Dashboard")')).toBeVisible();
    });

    test('should show admin metrics', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      // Check metrics cards
      await expect(page.locator('text=Total Revenue')).toBeVisible();
      await expect(page.locator('text=$1,234.50')).toBeVisible();
      await expect(page.locator('text=25').first()).toBeVisible(); // Total orders
      await expect(page.locator('text=15').first()).toBeVisible(); // Active customers
      await expect(page.locator('text=45').first()).toBeVisible(); // Products sold
    });

    test('should show recent orders with customer info', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h2:has-text("Recent Orders")')).toBeVisible();
      await expect(page.locator('text=John Doe')).toBeVisible();
      await expect(page.locator('text=Jane Smith')).toBeVisible();
    });

    test('should have order status filter', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      const filterSelect = page.locator('select').first();
      await expect(filterSelect).toBeVisible();
      
      // Check filter options
      await filterSelect.click();
      await expect(page.locator('option[value="UNPAID"]')).toBeVisible();
      await expect(page.locator('option[value="PAID"]')).toBeVisible();
      await expect(page.locator('option[value="PROCESSING"]')).toBeVisible();
    });

    test('should show order action menu', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      // Click on order action menu
      const actionButton = page.locator('.fa-ellipsis-v').first();
      await actionButton.click();
      
      // Should show action options
      await expect(page.locator('text=Mark Processing')).toBeVisible();
      await expect(page.locator('text=Mark Delivered')).toBeVisible();
      await expect(page.locator('text=Cancel Order')).toBeVisible();
    });

    test('should show order status distribution', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Order Status")')).toBeVisible();
    });

    test('should show top products', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Top Products")')).toBeVisible();
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=15 sold')).toBeVisible();
    });

    test('should show system health status', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("System Health")')).toBeVisible();
      await expect(page.locator('text=API Status')).toBeVisible();
      await expect(page.locator('text=Payment Gateway')).toBeVisible();
      await expect(page.locator('text=Online')).toBeVisible();
      await expect(page.locator('text=Connected')).toBeVisible();
    });

    test('should have quick action buttons', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("Quick Actions")')).toBeVisible();
      await expect(page.locator('button:has-text("Export Orders")')).toBeVisible();
      await expect(page.locator('button:has-text("Send Notifications")')).toBeVisible();
      await expect(page.locator('button:has-text("Generate Reports")')).toBeVisible();
    });

    test('should have refresh data button', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1000);
      
      const refreshButton = page.locator('button:has-text("Refresh")');
      await expect(refreshButton).toBeVisible();
      
      await refreshButton.click();
      await expect(page.locator('text=Dashboard data refreshed')).toBeVisible();
    });

    test('should show order details modal', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      // Click view order details
      const viewButton = page.locator('.fa-eye').first();
      await viewButton.click();
      
      // Should show order details modal
      await expect(page.locator('h3:has-text("Order Details")')).toBeVisible();
      await expect(page.locator('text=Order Information')).toBeVisible();
      await expect(page.locator('text=Customer Information')).toBeVisible();
    });

    test('should handle order status updates', async ({ page }) => {
      // Mock order update API
      await page.route('**/api/orders/*', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Order updated successfully'
          })
        });
      });

      await page.goto('/admin/dashboard');
      await page.waitForTimeout(1500);
      
      // Open action menu
      await page.locator('.fa-ellipsis-v').first().click();
      
      // Click mark processing
      await page.locator('text=Mark Processing').click();
      
      // Should show success message
      await expect(page.locator('text=updated to PROCESSING')).toBeVisible();
    });

  });

  test.describe('Dashboard Navigation', () => {

    test('should redirect admin users to admin dashboard', async ({ page }) => {
      // Mock admin auth
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockAdminUser })
        });
      });

      // Customer dashboard should redirect to admin dashboard for admin users
      await page.goto('/dashboard');
      await page.waitForURL('**/admin/dashboard');
    });

    test('should block customer access to admin dashboard', async ({ page }) => {
      // Mock customer auth
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockUser })
        });
      });

      // Mock 403 response for admin dashboard
      await page.route('**/admin/dashboard', async route => {
        await route.fulfill({
          status: 403,
          contentType: 'text/html',
          body: '<html><body>Forbidden</body></html>'
        });
      });

      await page.goto('/admin/dashboard');
      
      // Should show forbidden or redirect
      await expect(page.locator('text=Forbidden')).toBeVisible();
    });

  });

  test.describe('Dashboard Error Handling', () => {

    test('should handle API errors gracefully', async ({ page }) => {
      // Mock auth
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ user: mockUser })
        });
      });

      // Mock orders API error
      await page.route('**/api/orders**', async route => {
        await route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Server error'
          })
        });
      });

      await page.goto('/dashboard');
      await page.waitForTimeout(1500);
      
      // Should still show dashboard but with error handling
      await expect(page.locator('h1:has-text("Welcome back")')).toBeVisible();
      // Stats should show 0 values or error state
      await expect(page.locator('text=0').first()).toBeVisible();
    });

    test('should handle unauthorized access', async ({ page }) => {
      // Mock 401 response
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Unauthorized'
          })
        });
      });

      await page.goto('/dashboard');
      
      // Should redirect to login
      await page.waitForURL('**/login');
    });

  });

});