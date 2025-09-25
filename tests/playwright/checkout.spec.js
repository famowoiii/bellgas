const { test, expect } = require('@playwright/test');

test.describe('Checkout Process', () => {

  // Mock cart items
  const mockCartItems = [
    {
      id: 1,
      quantity: 2,
      price: '89.95',
      total: '179.90',
      productVariant: {
        id: 1,
        name: '9kg Cylinder',
        product: {
          name: 'LPG Full Tank'
        }
      }
    }
  ];

  // Mock addresses
  const mockAddresses = [
    {
      id: 1,
      name: 'Home',
      street_address: '123 Test Street',
      suburb: 'Sydney',
      state: 'NSW',
      postcode: '2000',
      country: 'Australia',
      delivery_instructions: 'Ring doorbell twice',
      full_address: '123 Test Street, Sydney NSW 2000, Australia'
    }
  ];

  test.beforeEach(async ({ page }) => {
    // Set auth token
    await page.addInitScript(() => {
      localStorage.setItem('access_token', 'mock_token');
    });

    // Mock auth check
    await page.route('**/api/auth/me', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          user: {
            id: 1,
            first_name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            role: 'CUSTOMER'
          }
        });
      });
    });

    // Mock cart API
    await page.route('**/api/cart', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            items: mockCartItems,
            total: '179.90',
            count: 2
          }
        })
      });
    });

    // Mock addresses API
    await page.route('**/api/addresses', async route => {
      if (route.request().method() === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: mockAddresses
          })
        });
      } else if (route.request().method() === 'POST') {
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: {
              id: 2,
              name: 'New Address',
              ...JSON.parse(route.request().postData())
            }
          })
        });
      }
    });
  });

  test.describe('Checkout Page Access', () => {

    test('should load checkout page when authenticated', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      await expect(page).toHaveTitle(/Checkout - BellGas/);
      await expect(page.locator('h1')).toContainText('Checkout');
    });

    test('should redirect unauthenticated users', async ({ page }) => {
      // Remove auth token
      await page.addInitScript(() => {
        localStorage.removeItem('access_token');
      });

      // Mock unauthorized response
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ message: 'Unauthorized' })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show login required message
      await expect(page.locator('text=Please login to continue')).toBeVisible();
      await expect(page.locator('a[href="/login"]')).toBeVisible();
    });

  });

  test.describe('Delivery Method Selection', () => {

    test('should show delivery method options', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show delivery and pickup options
      await expect(page.locator('input[value="DELIVERY"]')).toBeVisible();
      await expect(page.locator('input[value="PICKUP"]')).toBeVisible();
      await expect(page.locator('text=Delivery')).toBeVisible();
      await expect(page.locator('text=Pickup')).toBeVisible();
    });

    test('should default to delivery method', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Delivery should be selected by default
      await expect(page.locator('input[value="DELIVERY"]')).toBeChecked();
    });

    test('should switch to pickup method', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Click pickup option
      await page.locator('label:has(input[value="PICKUP"])').click();
      
      // Pickup should be selected
      await expect(page.locator('input[value="PICKUP"]')).toBeChecked();
    });

  });

  test.describe('Delivery Address Management', () => {

    test('should show existing addresses', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show address selection
      await expect(page.locator('h3:has-text("Delivery Address")')).toBeVisible();
      await expect(page.locator('text=Home')).toBeVisible();
      await expect(page.locator('text=123 Test Street')).toBeVisible();
    });

    test('should auto-select first address', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // First address should be selected
      const firstAddressRadio = page.locator('input[type="radio"]').first();
      await expect(firstAddressRadio).toBeChecked();
    });

    test('should show add new address button', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('button:has-text("Add New Address")')).toBeVisible();
    });

    test('should open add address form', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Click add new address
      await page.locator('button:has-text("Add New Address")').click();
      
      // Should show address form
      await expect(page.locator('h4:has-text("Add New Address")')).toBeVisible();
      await expect(page.locator('input[placeholder*="Address Name"]')).toBeVisible();
      await expect(page.locator('input[placeholder*="Street Address"]')).toBeVisible();
    });

    test('should fill and submit new address form', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Open form
      await page.locator('button:has-text("Add New Address")').click();
      
      // Fill form
      await page.fill('input[placeholder*="Address Name"]', 'Work');
      await page.fill('input[placeholder*="Street Address"]', '456 Work Street');
      await page.fill('input[placeholder*="Suburb"]', 'Melbourne');
      await page.fill('input[placeholder*="Postcode"]', '3000');
      await page.selectOption('select', 'VIC');
      await page.fill('textarea[placeholder*="Delivery Instructions"]', 'Reception desk');
      
      // Submit form
      await page.locator('button:has-text("Add Address")').click();
      
      // Should close form and show success
      await expect(page.locator('text=Address added successfully')).toBeVisible();
    });

    test('should handle address form validation', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Open form
      await page.locator('button:has-text("Add New Address")').click();
      
      // Try to submit empty form
      await page.locator('button:has-text("Add Address")').click();
      
      // Should show HTML5 validation
      const nameInput = page.locator('input[placeholder*="Address Name"]');
      await expect(nameInput).toHaveAttribute('required');
    });

    test('should show no addresses message when empty', async ({ page }) => {
      // Mock empty addresses
      await page.route('**/api/addresses', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: []
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show no addresses message
      await expect(page.locator('text=No delivery addresses found')).toBeVisible();
      await expect(page.locator('button:has-text("Add Your First Address")')).toBeVisible();
    });

  });

  test.describe('Order Summary', () => {

    test('should show cart items in order summary', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show order summary
      await expect(page.locator('h3:has-text("Order Summary")')).toBeVisible();
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=9kg Cylinder')).toBeVisible();
      await expect(page.locator('text=Qty: 2')).toBeVisible();
    });

    test('should show pricing breakdown', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show pricing
      await expect(page.locator('text=Subtotal:')).toBeVisible();
      await expect(page.locator('text=$179.90')).toBeVisible();
    });

    test('should show empty cart message', async ({ page }) => {
      // Mock empty cart
      await page.route('**/api/cart', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: {
              items: [],
              total: '0.00',
              count: 0
            }
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show empty cart message
      await expect(page.locator('text=Your cart is empty')).toBeVisible();
      await expect(page.locator('a[href="/products"]:has-text("Shop Now")')).toBeVisible();
    });

    test('should calculate shipping for delivery', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Ensure delivery is selected
      await page.locator('label:has(input[value="DELIVERY"])').click();
      
      // Should show shipping calculation
      await expect(page.locator('text=Delivery:')).toBeVisible();
    });

    test('should not show shipping for pickup', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Select pickup method
      await page.locator('label:has(input[value="PICKUP"])').click();
      
      // Should not show shipping line
      await expect(page.locator('text=Delivery:')).toBeHidden();
    });

  });

  test.describe('Order Notes', () => {

    test('should show customer notes field', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("Order Notes")')).toBeVisible();
      await expect(page.locator('textarea[placeholder*="special instructions"]')).toBeVisible();
    });

    test('should accept customer notes input', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      const notesField = page.locator('textarea[placeholder*="special instructions"]');
      await notesField.fill('Please call before delivery');
      
      await expect(notesField).toHaveValue('Please call before delivery');
    });

  });

  test.describe('Payment Method', () => {

    test('should show payment method section', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      await expect(page.locator('h3:has-text("Payment Method")')).toBeVisible();
      await expect(page.locator('text=Secure Card Payment')).toBeVisible();
      await expect(page.locator('text=Powered by Stripe')).toBeVisible();
    });

  });

  test.describe('Place Order', () => {

    test('should show place order button when ready', async ({ page }) => {
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      const placeOrderBtn = page.locator('button:has-text("Place Order")');
      await expect(placeOrderBtn).toBeVisible();
      await expect(placeOrderBtn).toContainText('$179.90');
    });

    test('should disable place order when cart is empty', async ({ page }) => {
      // Mock empty cart
      await page.route('**/api/cart', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: {
              items: [],
              total: '0.00',
              count: 0
            }
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      const placeOrderBtn = page.locator('button:has-text("Place Order")');
      await expect(placeOrderBtn).toBeHidden();
    });

    test('should require address for delivery', async ({ page }) => {
      // Mock no addresses
      await page.route('**/api/addresses', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            success: true,
            data: []
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Ensure delivery is selected
      await page.locator('label:has(input[value="DELIVERY"])').click();
      
      // Place order button should be disabled
      const placeOrderBtn = page.locator('button:has-text("Place Order")');
      await expect(placeOrderBtn).toBeDisabled();
    });

    test('should create payment intent when placing order', async ({ page }) => {
      // Mock payment intent creation
      await page.route('**/api/checkout/create-payment-intent', async route => {
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Payment intent created successfully',
            clientSecret: 'pi_test_1234567890_secret_abcd',
            order: {
              id: 1,
              order_number: 'BG-TEST123',
              status: 'UNPAID',
              total_aud: '179.90'
            },
            paymentIntent: {
              id: 'pi_test_1234567890',
              amount: 17990,
              currency: 'aud',
              status: 'requires_payment_method'
            }
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Click place order
      await page.locator('button:has-text("Place Order")').click();
      
      // Should show payment modal
      await expect(page.locator('text=Complete Payment')).toBeVisible();
      await expect(page.locator('text=Order Total: $179.90')).toBeVisible();
    });

    test('should handle payment intent creation error', async ({ page }) => {
      // Mock error response
      await page.route('**/api/checkout/create-payment-intent', async route => {
        await route.fulfill({
          status: 400,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Insufficient stock for some items'
          })
        });
      });

      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Click place order
      await page.locator('button:has-text("Place Order")').click();
      
      // Should show error message
      await expect(page.locator('text=Insufficient stock for some items')).toBeVisible();
    });

  });

  test.describe('Responsive Design', () => {

    test('should be mobile responsive', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should show mobile layout
      await expect(page.locator('h1:has-text("Checkout")')).toBeVisible();
      
      // Sections should stack vertically
      await expect(page.locator('h3:has-text("Delivery Method")')).toBeVisible();
      await expect(page.locator('h3:has-text("Order Summary")')).toBeVisible();
    });

    test('should maintain functionality on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/checkout');
      await page.waitForTimeout(1000);
      
      // Should be able to select delivery method
      await page.locator('label:has(input[value="PICKUP"])').click();
      await expect(page.locator('input[value="PICKUP"]')).toBeChecked();
      
      // Should be able to open add address form
      await page.locator('button:has-text("Add New Address")').click();
      await expect(page.locator('h4:has-text("Add New Address")')).toBeVisible();
    });

  });

});