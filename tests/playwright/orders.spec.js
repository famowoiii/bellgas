const { test, expect } = require('@playwright/test');

test.describe('Orders Management', () => {

  const mockUser = {
    id: 1,
    first_name: 'Test',
    last_name: 'User',
    email: 'test@bellgas.com',
    role: 'CUSTOMER'
  };

  const mockOrders = [
    {
      id: 1,
      order_number: 'BG-TEST001',
      status: 'DELIVERED',
      fulfillment_method: 'DELIVERY',
      total_aud: '132.45',
      subtotal_aud: '89.95',
      shipping_cost_aud: '42.50',
      customer_notes: 'Please ring doorbell twice',
      created_at: '2024-12-01T10:00:00Z',
      updated_at: '2024-12-03T15:30:00Z',
      address: {
        id: 1,
        name: 'Home',
        street_address: '123 Test Street',
        suburb: 'Sydney',
        state: 'NSW',
        postcode: '2000',
        country: 'Australia',
        delivery_instructions: 'Leave at front door',
        full_address: '123 Test Street, Sydney NSW 2000, Australia'
      },
      items: [
        {
          id: 1,
          quantity: 1,
          unit_price_aud: '89.95',
          total_price_aud: '89.95',
          productVariant: {
            id: 1,
            name: '9kg Cylinder',
            weight_kg: 9,
            product: {
              name: 'LPG Full Tank',
              category: 'FULL_TANK'
            }
          }
        }
      ],
      stripe_payment_intent_id: 'pi_test_1234567890',
      events: [
        {
          id: 1,
          event_type: 'CREATED',
          description: 'Order created and payment intent generated',
          created_at: '2024-12-01T10:00:00Z',
          metadata: {
            payment_intent_id: 'pi_test_1234567890',
            total_amount: 132.45
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
      subtotal_aud: '45.95',
      shipping_cost_aud: '0.00',
      created_at: '2024-12-15T14:30:00Z',
      updated_at: '2024-12-15T16:00:00Z',
      items: [
        {
          id: 2,
          quantity: 1,
          unit_price_aud: '45.95',
          total_price_aud: '45.95',
          productVariant: {
            id: 2,
            name: '9kg Refill',
            weight_kg: 9,
            product: {
              name: 'LPG Refill Service',
              category: 'REFILL'
            }
          }
        }
      ],
      pickup_code: 'P123456'
    }
  ];

  test.beforeEach(async ({ page }) => {
    // Set auth token
    await page.addInitScript(() => {
      localStorage.setItem('access_token', 'mock_token');
    });

    // Mock auth
    await page.route('**/api/auth/me', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ user: mockUser })
      });
    });

    // Mock orders API
    await page.route('**/api/orders**', async route => {
      if (route.request().url().includes('limit=5')) {
        // Dashboard orders request
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockOrders.slice(0, 2)
          })
        });
      } else {
        // Full orders list
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockOrders
          })
        });
      }
    });
  });

  test.describe('Orders List Page', () => {

    test('should load orders page correctly', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1000);
      
      await expect(page).toHaveTitle(/My Orders - BellGas/);
      await expect(page.locator('h1')).toContainText('My Orders');
    });

    test('should show loading state initially', async ({ page }) => {
      await page.goto('/orders');
      
      await expect(page.locator('text=Loading orders')).toBeVisible();
    });

    test('should display orders after loading', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should show order cards
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
      await expect(page.locator('text=BG-TEST002')).toBeVisible();
      
      // Should show order details
      await expect(page.locator('text=DELIVERED')).toBeVisible();
      await expect(page.locator('text=PROCESSING')).toBeVisible();
      await expect(page.locator('text=$132.45')).toBeVisible();
      await expect(page.locator('text=$45.95')).toBeVisible();
    });

    test('should show order status badges with correct colors', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Check status badges
      const deliveredBadge = page.locator('span:has-text("DELIVERED")');
      const processingBadge = page.locator('span:has-text("PROCESSING")');
      
      await expect(deliveredBadge).toBeVisible();
      await expect(processingBadge).toBeVisible();
      
      // Should have appropriate CSS classes for colors
      await expect(deliveredBadge).toHaveClass(/bg-green-100.*text-green-800/);
      await expect(processingBadge).toHaveClass(/bg-yellow-100.*text-yellow-800/);
    });

    test('should show fulfillment method icons', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should show truck icon for delivery
      await expect(page.locator('.fa-truck')).toBeVisible();
      
      // Should show store icon for pickup
      await expect(page.locator('.fa-store')).toBeVisible();
      
      await expect(page.locator('text=DELIVERY')).toBeVisible();
      await expect(page.locator('text=PICKUP')).toBeVisible();
    });

    test('should show order items summary', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should show items count
      await expect(page.locator('text=Items (1)')).toBeVisible();
      
      // Should show product names
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=LPG Refill Service')).toBeVisible();
      
      // Should show variant details
      await expect(page.locator('text=9kg Cylinder')).toBeVisible();
      await expect(page.locator('text=9kg Refill')).toBeVisible();
    });

    test('should have action buttons', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should show view details buttons
      await expect(page.locator('button:has-text("View Details")')).toBeVisible();
      
      // Should show receipt button for delivered order
      await expect(page.locator('button:has-text("Receipt")')).toBeVisible();
      
      // Should show reorder button for delivered order
      await expect(page.locator('button:has-text("Reorder")')).toBeVisible();
    });

    test('should show customer notes when present', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('text=Order Notes')).toBeVisible();
      await expect(page.locator('text=Please ring doorbell twice')).toBeVisible();
    });

  });

  test.describe('Orders Filtering', () => {

    test('should show filter controls', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1000);
      
      // Should show filter dropdowns
      await expect(page.locator('select').nth(0)).toBeVisible(); // Status filter
      await expect(page.locator('select').nth(1)).toBeVisible(); // Method filter
      await expect(page.locator('select').nth(2)).toBeVisible(); // Date range filter
      await expect(page.locator('input[placeholder*="Order number"]')).toBeVisible(); // Search
    });

    test('should filter by status', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Select DELIVERED status
      await page.selectOption('select:nth-of-type(1)', 'DELIVERED');
      
      // Should show only delivered orders
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
      await expect(page.locator('text=DELIVERED')).toBeVisible();
    });

    test('should filter by fulfillment method', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Select PICKUP method
      await page.selectOption('select:nth-of-type(2)', 'PICKUP');
      
      // Should show only pickup orders
      await expect(page.locator('text=BG-TEST002')).toBeVisible();
      await expect(page.locator('text=PICKUP')).toBeVisible();
    });

    test('should search by order number', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Search for specific order
      await page.fill('input[placeholder*="Order number"]', 'TEST001');
      
      // Wait for debounced search
      await page.waitForTimeout(400);
      
      // Should show only matching order
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
    });

    test('should clear filters', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Apply filters
      await page.selectOption('select:nth-of-type(1)', 'DELIVERED');
      await page.fill('input[placeholder*="Order number"]', 'TEST001');
      
      // Mock empty results to show clear filters button
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
      
      await page.waitForTimeout(1000);
      
      // Should show no results with clear option
      await expect(page.locator('text=No orders found matching your filters')).toBeVisible();
      
      const clearBtn = page.locator('button:has-text("Clear Filters")');
      if (await clearBtn.isVisible()) {
        await clearBtn.click();
      }
    });

  });

  test.describe('Order Actions', () => {

    test('should open order details modal', async ({ page }) => {
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Click view details button
      await page.locator('button:has-text("View Details")').first().click();
      
      // Should open modal with order details
      await expect(page.locator('h3:has-text("Order Details")')).toBeVisible();
      await expect(page.locator('text=Order Information')).toBeVisible();
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
    });

    test('should download receipt', async ({ page }) => {
      // Mock receipt API
      await page.route('**/api/receipts/order/1', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Receipt retrieved successfully',
            receipt: {
              receipt_info: {
                receipt_number: 'RCP-BG-TEST001',
                order_number: 'BG-TEST001'
              },
              business_info: {
                name: 'BellGas'
              },
              order_details: {
                pricing: {
                  total: '132.45'
                }
              }
            }
          })
        });
      });

      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Set up to handle new window
      const [newPage] = await Promise.all([
        page.waitForEvent('popup'),
        page.locator('button:has-text("Receipt")').first().click()
      ]);
      
      // New window should open with receipt content
      await expect(newPage.locator('text=BellGas')).toBeVisible();
    });

    test('should handle reorder', async ({ page }) => {
      // Mock reorder API
      await page.route('**/api/orders/1/reorder', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Items added to cart successfully'
          })
        });
      });

      // Mock cart API
      await page.route('**/api/cart', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: { items: [], count: 1 }
          })
        });
      });

      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Click reorder button
      await page.locator('button:has-text("Reorder")').first().click();
      
      // Should show success message
      await expect(page.locator('text=Items added to cart')).toBeVisible();
    });

    test('should handle order cancellation', async ({ page }) => {
      // Add a cancellable order to mock data
      const cancellableOrder = {
        ...mockOrders[0],
        id: 3,
        order_number: 'BG-TEST003',
        status: 'PAID'
      };

      await page.route('**/api/orders**', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: [cancellableOrder, ...mockOrders]
          })
        });
      });

      // Mock cancel API
      await page.route('**/api/orders/3/cancel', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Order cancelled successfully'
          })
        });
      });

      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Set up dialog handler
      page.on('dialog', async dialog => {
        expect(dialog.type()).toBe('confirm');
        await dialog.accept();
      });
      
      // Click cancel button
      await page.locator('button:has-text("Cancel")').first().click();
      
      // Should show success message
      await expect(page.locator('text=Order cancelled successfully')).toBeVisible();
    });

  });

  test.describe('Order Details Page', () => {

    test.beforeEach(async ({ page }) => {
      // Mock individual order API
      await page.route('**/api/orders/1', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockOrders[0]
          })
        });
      });
    });

    test('should load order details page', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page).toHaveTitle(/Order Details - BellGas/);
      await expect(page.locator('h1')).toContainText('BG-TEST001');
    });

    test('should show order header information', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      // Check header elements
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
      await expect(page.locator('text=DELIVERED')).toBeVisible();
      await expect(page.locator('text=DELIVERY')).toBeVisible();
      await expect(page.locator('text=$132.45')).toBeVisible();
    });

    test('should show order progress tracking', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      // Should show progress section
      await expect(page.locator('h2:has-text("Order Progress")')).toBeVisible();
      
      // Should show progress steps
      await expect(page.locator('text=Order Placed')).toBeVisible();
      await expect(page.locator('text=Payment Confirmed')).toBeVisible();
      await expect(page.locator('text=Processing')).toBeVisible();
      await expect(page.locator('text=Delivered')).toBeVisible();
    });

    test('should show delivery address for delivery orders', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Delivery Address")')).toBeVisible();
      await expect(page.locator('text=Home')).toBeVisible();
      await expect(page.locator('text=123 Test Street')).toBeVisible();
      await expect(page.locator('text=Sydney NSW 2000')).toBeVisible();
      await expect(page.locator('text=Leave at front door')).toBeVisible();
    });

    test('should show pickup information for pickup orders', async ({ page }) => {
      // Mock pickup order
      await page.route('**/api/orders/2', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockOrders[1]
          })
        });
      });

      await page.goto('/orders/2');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Pickup Information")')).toBeVisible();
      await expect(page.locator('text=BellGas Store Location')).toBeVisible();
      await expect(page.locator('text=Pickup Code:')).toBeVisible();
      await expect(page.locator('text=P123456')).toBeVisible();
    });

    test('should show order summary breakdown', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Order Summary")')).toBeVisible();
      await expect(page.locator('text=Subtotal:')).toBeVisible();
      await expect(page.locator('text=$89.95')).toBeVisible();
      await expect(page.locator('text=Shipping:')).toBeVisible();
      await expect(page.locator('text=$42.50')).toBeVisible();
      await expect(page.locator('text=Total:')).toBeVisible();
      await expect(page.locator('text=$132.45')).toBeVisible();
    });

    test('should show order items table', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Order Items")')).toBeVisible();
      
      // Check table headers
      await expect(page.locator('th:has-text("Product")')).toBeVisible();
      await expect(page.locator('th:has-text("Quantity")')).toBeVisible();
      await expect(page.locator('th:has-text("Unit Price")')).toBeVisible();
      await expect(page.locator('th:has-text("Total")')).toBeVisible();
      
      // Check item data
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=9kg Cylinder')).toBeVisible();
      await expect(page.locator('text=1').nth(0)).toBeVisible(); // Quantity
      await expect(page.locator('text=$89.95')).toBeVisible();
    });

    test('should show customer notes when present', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Customer Notes")')).toBeVisible();
      await expect(page.locator('text=Please ring doorbell twice')).toBeVisible();
    });

    test('should show order events timeline', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('h3:has-text("Order History")')).toBeVisible();
      await expect(page.locator('text=Order created and payment intent generated')).toBeVisible();
    });

    test('should have back to orders link', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      const backLink = page.locator('a:has-text("Back to Orders")');
      await expect(backLink).toBeVisible();
      await expect(backLink).toHaveAttribute('href', '/orders');
    });

    test('should have print functionality', async ({ page }) => {
      await page.goto('/orders/1');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('button:has-text("Print")')).toBeVisible();
    });

    test('should handle order not found', async ({ page }) => {
      // Mock 404 response
      await page.route('**/api/orders/999', async route => {
        await route.fulfill({
          status: 404,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Order not found'
          })
        });
      });

      await page.goto('/orders/999');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('text=Order Not Found')).toBeVisible();
      await expect(page.locator('a[href="/orders"]:has-text("View All Orders")')).toBeVisible();
    });

  });

  test.describe('Empty States', () => {

    test('should show empty state when no orders', async ({ page }) => {
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

      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      await expect(page.locator('text=No orders yet')).toBeVisible();
      await expect(page.locator('text=Start shopping to see your orders here')).toBeVisible();
      await expect(page.locator('a[href="/products"]:has-text("Shop Now")')).toBeVisible();
    });

  });

  test.describe('Responsive Design', () => {

    test('should be mobile responsive', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should show mobile layout
      await expect(page.locator('h1:has-text("My Orders")')).toBeVisible();
      await expect(page.locator('text=BG-TEST001')).toBeVisible();
      
      // Filters should stack vertically
      await expect(page.locator('select').first()).toBeVisible();
    });

    test('should maintain functionality on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/orders');
      await page.waitForTimeout(1500);
      
      // Should be able to filter
      await page.selectOption('select:nth-of-type(1)', 'DELIVERED');
      
      // Should be able to view details
      await page.locator('button:has-text("View Details")').first().click();
      await expect(page.locator('h3:has-text("Order Details")')).toBeVisible();
    });

  });

});