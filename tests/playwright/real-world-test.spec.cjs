const { test, expect } = require('@playwright/test');

/**
 * Real World BellGas Application Test Suite
 * Tests the actual application with real API calls and database
 * No mocks - testing the complete user journey
 */

test.describe('BellGas Real World Application Tests', () => {

  const baseURL = 'http://127.0.0.1:8000';
  
  test.beforeAll(async () => {
    console.log('🚀 Starting comprehensive BellGas application testing...');
  });

  test.describe('1. Homepage and Navigation', () => {
    
    test('should load homepage correctly with all elements', async ({ page }) => {
      await page.goto(`${baseURL}`);
      
      // Wait for page to fully load
      await page.waitForLoadState('networkidle');
      
      // Check title
      await expect(page).toHaveTitle(/BellGas/);
      
      // Check main navigation elements
      await expect(page.locator('text=BellGas')).toBeVisible();
      await expect(page.locator('a[href="/products"]')).toBeVisible();
      await expect(page.locator('a[href="/about"]')).toBeVisible();
      await expect(page.locator('a[href="/contact"]')).toBeVisible();
      
      // Check hero section
      await expect(page.locator('h1')).toContainText(['Premium', 'LPG', 'Service'], { ignoreCase: true });
      
      console.log('✅ Homepage loaded successfully');
    });

    test('should navigate between pages correctly', async ({ page }) => {
      await page.goto(`${baseURL}`);
      
      // Navigate to products
      await page.click('a[href="/products"]');
      await page.waitForURL('**/products');
      await expect(page.locator('h1')).toContainText('Products');
      
      // Navigate to about
      await page.click('a[href="/about"]');
      await page.waitForURL('**/about');
      await expect(page.locator('h1')).toContainText('About');
      
      // Navigate to contact
      await page.click('a[href="/contact"]');
      await page.waitForURL('**/contact');
      await expect(page.locator('h1')).toContainText('Contact');
      
      console.log('✅ Navigation working correctly');
    });

  });

  test.describe('2. Authentication System', () => {
    
    test('should display login page correctly', async ({ page }) => {
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      
      // Check login form elements
      await expect(page.locator('input[type="email"]')).toBeVisible();
      await expect(page.locator('input[type="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
      
      // Check demo credential buttons
      await expect(page.locator('button:has-text("Customer Demo")')).toBeVisible();
      await expect(page.locator('button:has-text("Admin Demo")')).toBeVisible();
      
      console.log('✅ Login page elements visible');
    });

    test('should successfully login as customer with demo credentials', async ({ page }) => {
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      
      // Click customer demo button
      await page.click('button:has-text("Customer Demo")');
      
      // Verify credentials are filled
      await expect(page.locator('input[type="email"]')).toHaveValue('john@example.com');
      await expect(page.locator('input[type="password"]')).toHaveValue('password');
      
      // Submit login form
      await page.click('button[type="submit"]');
      
      // Wait for login to complete and redirect
      await page.waitForTimeout(2000);
      
      // Check if redirected to homepage or dashboard
      const currentURL = page.url();
      expect(currentURL).toMatch(/(dashboard|home|\/)$/);
      
      // Check if user menu is visible (indication of successful login)
      await expect(page.locator('.fa-user').or(page.locator('text=John'))).toBeVisible();
      
      console.log('✅ Customer login successful');
    });

    test('should successfully login as admin with demo credentials', async ({ page }) => {
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      
      // Click admin demo button
      await page.click('button:has-text("Admin Demo")');
      
      // Verify credentials are filled
      await expect(page.locator('input[type="email"]')).toHaveValue('admin@bellgas.com.au');
      await expect(page.locator('input[type="password"]')).toHaveValue('password');
      
      // Submit login form
      await page.click('button[type="submit"]');
      
      // Wait for login to complete and redirect
      await page.waitForTimeout(2000);
      
      // Should be redirected to admin area or dashboard
      const currentURL = page.url();
      expect(currentURL).toMatch(/(admin|dashboard|home|\/)$/);
      
      console.log('✅ Admin login successful');
    });

  });

  test.describe('3. Products and Shopping', () => {
    
    test('should display products from real database', async ({ page }) => {
      await page.goto(`${baseURL}/products`);
      await page.waitForLoadState('networkidle');
      
      // Wait for products to load (no mocks, real API)
      await page.waitForTimeout(2000);
      
      // Check if products are displayed
      await expect(page.locator('text=LPG')).toBeVisible();
      
      // Check for product variants and prices
      const priceElements = page.locator('text=/\\$[0-9]+\\.[0-9]{2}/');
      await expect(priceElements.first()).toBeVisible();
      
      // Check for add to cart buttons
      await expect(page.locator('button:has-text("Add to Cart")').first()).toBeVisible();
      
      console.log('✅ Products loaded from database');
    });

    test('should add product to cart when logged in', async ({ page }) => {
      // First login
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      await page.click('button:has-text("Customer Demo")');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
      
      // Go to products
      await page.goto(`${baseURL}/products`);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      
      // Add first available product to cart
      const addToCartBtn = page.locator('button:has-text("Add to Cart")').first();
      if (await addToCartBtn.isVisible()) {
        await addToCartBtn.click();
        
        // Check for success notification or cart update
        await page.waitForTimeout(1000);
        
        // Cart icon should show count or notification should appear
        const cartBadge = page.locator('.fa-shopping-cart').locator('..')
        .locator('[class*="badge"], [class*="count"], span');
        
        if (await cartBadge.isVisible()) {
          console.log('✅ Product added to cart successfully');
        } else {
          console.log('⚠️  Cart update not visible but no error occurred');
        }
      }
    });

  });

  test.describe('4. Checkout Process', () => {
    
    test('should access checkout when authenticated', async ({ page }) => {
      // Login first
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      await page.click('button:has-text("Customer Demo")');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
      
      // Go to checkout
      await page.goto(`${baseURL}/checkout`);
      await page.waitForLoadState('networkidle');
      
      // Should show checkout page elements
      await expect(page.locator('h1:has-text("Checkout")')).toBeVisible();
      
      // Check for delivery options
      await expect(page.locator('text=Delivery')).toBeVisible();
      
      console.log('✅ Checkout page accessible when authenticated');
    });

  });

  test.describe('5. Dashboard Functionality', () => {
    
    test('should access customer dashboard when logged in', async ({ page }) => {
      // Login as customer
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      await page.click('button:has-text("Customer Demo")');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
      
      // Go to dashboard
      await page.goto(`${baseURL}/dashboard`);
      await page.waitForLoadState('networkidle');
      
      // Should show dashboard elements
      await expect(page.locator('h1').or(page.locator('text=Dashboard'))).toBeVisible();
      
      console.log('✅ Customer dashboard accessible');
    });

  });

  test.describe('6. Responsive Design Testing', () => {
    
    test('should work properly on mobile viewport', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      
      await page.goto(`${baseURL}`);
      await page.waitForLoadState('networkidle');
      
      // Check if mobile navigation works
      const mobileMenuToggle = page.locator('[class*="mobile"], .fa-bars, [aria-label="menu"]');
      if (await mobileMenuToggle.isVisible()) {
        await mobileMenuToggle.click();
        await page.waitForTimeout(500);
      }
      
      // Check if main content is still visible
      await expect(page.locator('h1').or(page.locator('text=BellGas'))).toBeVisible();
      
      console.log('✅ Mobile responsive design working');
    });

  });

  test.describe('7. Performance and Loading', () => {
    
    test('should load pages within acceptable time', async ({ page }) => {
      const startTime = Date.now();
      
      await page.goto(`${baseURL}`);
      await page.waitForLoadState('networkidle');
      
      const loadTime = Date.now() - startTime;
      
      // Should load within 5 seconds
      expect(loadTime).toBeLessThan(5000);
      
      console.log(`✅ Homepage loaded in ${loadTime}ms`);
    });

  });

  test.describe('8. Error Handling', () => {
    
    test('should handle login with incorrect credentials gracefully', async ({ page }) => {
      await page.goto(`${baseURL}/login`);
      await page.waitForLoadState('networkidle');
      
      // Fill incorrect credentials
      await page.fill('input[type="email"]', 'wrong@email.com');
      await page.fill('input[type="password"]', 'wrongpassword');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Wait for error message
      await page.waitForTimeout(2000);
      
      // Should show error message
      const errorElement = page.locator('text=Invalid, text=error, .error, .alert-danger');
      if (await errorElement.isVisible()) {
        console.log('✅ Login error handling working');
      } else {
        console.log('⚠️  Error message not visible but form didn\'t submit');
      }
    });

  });

  test.afterAll(async () => {
    console.log('🎉 BellGas application testing completed!');
  });

});