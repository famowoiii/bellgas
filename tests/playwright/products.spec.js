const { test, expect } = require('@playwright/test');

test.describe('Products & Shopping', () => {

  // Mock products data
  const mockProducts = [
    {
      id: 1,
      name: 'LPG Full Tank',
      slug: 'lpg-full-tank',
      description: 'Premium quality LPG full tank for home and business use',
      category: 'FULL_TANK',
      is_active: true,
      variants: [
        {
          id: 1,
          name: '9kg Cylinder',
          weight_kg: 9,
          price_aud: '89.95',
          stock_quantity: 50,
          available_stock: 45,
          is_active: true
        },
        {
          id: 2,
          name: '18kg Cylinder',
          weight_kg: 18,
          price_aud: '159.95',
          stock_quantity: 30,
          available_stock: 25,
          is_active: true
        }
      ]
    },
    {
      id: 2,
      name: 'LPG Refill Service',
      slug: 'lpg-refill-service',
      description: 'Convenient LPG refill service - bring your empty cylinder',
      category: 'REFILL',
      is_active: true,
      variants: [
        {
          id: 3,
          name: '9kg Refill',
          weight_kg: 9,
          price_aud: '45.95',
          stock_quantity: 100,
          available_stock: 95,
          is_active: true
        }
      ]
    }
  ];

  test.beforeEach(async ({ page }) => {
    // Mock products API
    await page.route('**/api/products**', async route => {
      const url = route.request().url();
      
      if (url.includes('categories')) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Categories retrieved successfully',
            data: ['FULL_TANK', 'REFILL']
          })
        });
      } else {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Products retrieved successfully',
            data: mockProducts,
            pagination: {
              current_page: 1,
              last_page: 1,
              per_page: 15,
              total: 2
            }
          })
        });
      }
    });
  });

  test.describe('Products Page', () => {

    test('should load products page correctly', async ({ page }) => {
      await page.goto('/products');
      
      await expect(page).toHaveTitle(/Products - BellGas/);
      await expect(page.locator('h1')).toContainText('Our LPG Products');
    });

    test('should show loading state initially', async ({ page }) => {
      await page.goto('/products');
      
      // Should show loading spinner briefly
      await expect(page.locator('text=Loading products')).toBeVisible();
      
      // Wait for products to load
      await page.waitForSelector('[x-show="!loading && products.length > 0"]');
    });

    test('should display products after loading', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000); // Wait for data to load
      
      // Should show products
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=LPG Refill Service')).toBeVisible();
    });

    test('should show product filters', async ({ page }) => {
      await page.goto('/products');
      
      // Check filter elements
      await expect(page.locator('input[placeholder*="Search products"]')).toBeVisible();
      await expect(page.locator('select')).toHaveCount(2); // Category and Sort dropdowns
    });

    test('should filter products by category', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Select FULL_TANK category
      await page.selectOption('select:nth-of-type(1)', 'FULL_TANK');
      
      // Should show filtered products
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
    });

    test('should search products', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Search for "refill"
      await page.fill('input[placeholder*="Search products"]', 'refill');
      
      // Wait for debounced search
      await page.waitForTimeout(600);
      
      // Should show search results
      await expect(page.locator('text=LPG Refill Service')).toBeVisible();
    });

    test('should show product variants and prices', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Check product variants are shown
      await expect(page.locator('text=9kg Cylinder')).toBeVisible();
      await expect(page.locator('text=18kg Cylinder')).toBeVisible();
      await expect(page.locator('text=$89.95')).toBeVisible();
      await expect(page.locator('text=$159.95')).toBeVisible();
    });

    test('should have add to cart buttons', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Check add to cart buttons
      const addToCartButtons = page.locator('button:has-text("Add to Cart")');
      await expect(addToCartButtons.first()).toBeVisible();
    });

    test('should show stock availability', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Check stock information
      await expect(page.locator('text=45 available')).toBeVisible();
      await expect(page.locator('text=25 available')).toBeVisible();
    });

    test('should be responsive on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Should show mobile-friendly layout
      await expect(page.locator('h1')).toBeVisible();
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
    });

  });

  test.describe('Shopping Cart', () => {

    test.beforeEach(async ({ page }) => {
      // Mock cart API
      await page.route('**/api/cart**', async route => {
        const method = route.request().method();
        
        if (method === 'GET') {
          await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              data: {
                items: [
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
                ],
                total: '179.90',
                count: 2
              }
            })
          });
        } else if (method === 'POST') {
          await route.fulfill({
            status: 201,
            contentType: 'application/json',
            body: JSON.stringify({
              success: true,
              message: 'Item added to cart!'
            })
          });
        }
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
              email: 'test@example.com'
            }
          })
        });
      });

      // Set auth token
      await page.addInitScript(() => {
        localStorage.setItem('access_token', 'mock_token');
      });
    });

    test('should add item to cart when logged in', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1500); // Wait for auth and products to load
      
      // Click add to cart button
      const addToCartBtn = page.locator('button:has-text("Add to Cart")').first();
      await addToCartBtn.click();
      
      // Should show success notification
      await expect(page.locator('text=Item added to cart')).toBeVisible();
    });

    test('should show cart count in navigation', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Should show cart count badge
      await expect(page.locator('.fa-shopping-cart').locator('..').locator('span')).toContainText('2');
    });

    test('should open cart sidebar', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Click cart icon
      await page.locator('.fa-shopping-cart').click();
      
      // Should open cart sidebar
      await expect(page.locator('text=Shopping Cart')).toBeVisible();
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
      await expect(page.locator('text=$179.90')).toBeVisible();
    });

    test('should update item quantity in cart', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Open cart
      await page.locator('.fa-shopping-cart').click();
      
      // Click increase quantity button
      await page.locator('button:has-text("+")').click();
      
      // Should update quantity (mocked)
      await expect(page.locator('span:has-text("2")')).toBeVisible();
    });

    test('should remove item from cart', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Open cart
      await page.locator('.fa-shopping-cart').click();
      
      // Click remove button
      await page.locator('button:has-text("Remove")').click();
      
      // Should show success message
      await expect(page.locator('text=Item removed from cart')).toBeVisible();
    });

    test('should redirect to checkout', async ({ page }) => {
      await page.goto('/');
      await page.waitForTimeout(1000);
      
      // Open cart
      await page.locator('.fa-shopping-cart').click();
      
      // Click checkout button
      await page.locator('button:has-text("Checkout")').click();
      
      // Should redirect to checkout
      await page.waitForURL('**/checkout');
    });

    test('should require login for cart actions', async ({ page }) => {
      // Remove auth token
      await page.addInitScript(() => {
        localStorage.removeItem('access_token');
      });

      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Try to add to cart without login
      const addToCartBtn = page.locator('button:has-text("Add to Cart")').first();
      await addToCartBtn.click();
      
      // Should show login required message
      await expect(page.locator('text=Please login to add items to cart')).toBeVisible();
    });

  });

  test.describe('Product Categories', () => {

    test('should show category filter options', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Check category dropdown
      const categorySelect = page.locator('select').first();
      await categorySelect.click();
      
      await expect(page.locator('option[value="FULL_TANK"]')).toBeVisible();
      await expect(page.locator('option[value="REFILL"]')).toBeVisible();
    });

    test('should filter by FULL_TANK category', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Mock filtered API response
      await page.route('**/api/products?category=FULL_TANK', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [mockProducts[0]], // Only full tank product
            pagination: { current_page: 1, last_page: 1, per_page: 15, total: 1 }
          })
        });
      });
      
      await page.selectOption('select:nth-of-type(1)', 'FULL_TANK');
      
      // Should show only full tank products
      await expect(page.locator('text=LPG Full Tank')).toBeVisible();
    });

    test('should clear filters', async ({ page }) => {
      await page.goto('/products');
      await page.waitForTimeout(1000);
      
      // Apply filters first
      await page.selectOption('select:nth-of-type(1)', 'FULL_TANK');
      await page.fill('input[placeholder*="Search products"]', 'tank');
      
      // If no results show empty state with clear filter button
      await page.route('**/api/products**', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            data: [],
            pagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 }
          })
        });
      });
      
      await page.waitForTimeout(1000);
      
      // Should show clear filters option
      await expect(page.locator('text=No products found')).toBeVisible();
      
      const clearBtn = page.locator('button:has-text("Clear Filters")');
      if (await clearBtn.isVisible()) {
        await clearBtn.click();
      }
    });

  });

});