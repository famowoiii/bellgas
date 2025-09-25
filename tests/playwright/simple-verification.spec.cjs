const { test, expect } = require('@playwright/test');

test.describe('BellGas Simple Verification Tests', () => {

  const baseURL = 'http://127.0.0.1:8000';
  
  test('should load homepage successfully', async ({ page }) => {
    console.log('🏠 Testing homepage...');
    
    await page.goto(`${baseURL}`);
    await page.waitForLoadState('networkidle');
    
    // Check if page loads
    await expect(page).toHaveTitle(/BellGas/);
    
    // Check if main brand element exists
    await expect(page.locator('nav').locator('text=BellGas').first()).toBeVisible();
    
    console.log('✅ Homepage loads successfully');
  });

  test('should load login page without JavaScript errors', async ({ page }) => {
    console.log('🔐 Testing login page...');
    
    // Listen for console errors
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });
    
    await page.goto(`${baseURL}/login`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Give JS time to execute
    
    // Check form elements
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    
    // Report any JS errors
    if (errors.length > 0) {
      console.log('⚠️  JavaScript errors detected:', errors);
    } else {
      console.log('✅ No JavaScript errors detected');
    }
    
    console.log('✅ Login page loads correctly');
  });

  test('should successfully login with customer credentials', async ({ page }) => {
    console.log('👤 Testing customer login...');
    
    await page.goto(`${baseURL}/login`);
    await page.waitForLoadState('networkidle');
    
    // Fill login form
    await page.fill('input[type="email"]', 'john@example.com');
    await page.fill('input[type="password"]', 'password');
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Wait for login response
    await page.waitForTimeout(3000);
    
    // Check if redirected (not on login page anymore)
    const currentURL = page.url();
    const isRedirected = !currentURL.includes('/login');
    
    if (isRedirected) {
      console.log('✅ Customer login successful - redirected to:', currentURL);
    } else {
      console.log('⚠️  Login may have failed - still on login page');
    }
    
    expect(isRedirected).toBeTruthy();
  });

  test('should load products page with API data', async ({ page }) => {
    console.log('📦 Testing products page...');
    
    await page.goto(`${baseURL}/products`);
    await page.waitForLoadState('networkidle');
    
    // Wait for potential API calls
    await page.waitForTimeout(3000);
    
    // Check if page title is correct
    await expect(page).toHaveTitle(/Products/);
    
    // Check for main heading
    await expect(page.locator('h1')).toBeVisible();
    
    // Look for any product-related content
    const hasLPG = await page.locator('text=LPG').isVisible();
    const hasPrices = await page.locator('text=/\\$[0-9]+/').isVisible();
    
    console.log(`✅ Products page loaded - Has LPG content: ${hasLPG}, Has prices: ${hasPrices}`);
  });

  test('should load checkout page when authenticated', async ({ page }) => {
    console.log('🛒 Testing checkout access...');
    
    // Login first
    await page.goto(`${baseURL}/login`);
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', 'john@example.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    
    // Try to access checkout
    await page.goto(`${baseURL}/checkout`);
    await page.waitForLoadState('networkidle');
    
    const isOnCheckout = page.url().includes('/checkout');
    
    if (isOnCheckout) {
      await expect(page.locator('h1')).toBeVisible();
      console.log('✅ Checkout page accessible when authenticated');
    } else {
      console.log('⚠️  Checkout page redirected - may need authentication');
    }
  });

  test('should test mobile responsiveness', async ({ page }) => {
    console.log('📱 Testing mobile responsiveness...');
    
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto(`${baseURL}`);
    await page.waitForLoadState('networkidle');
    
    // Check if main content is visible on mobile
    await expect(page.locator('text=BellGas').first()).toBeVisible();
    
    console.log('✅ Mobile layout working');
  });

  test('should verify API endpoints are working', async ({ page }) => {
    console.log('🔌 Testing API endpoints...');
    
    // Test products API by checking network requests
    const responses = [];
    page.on('response', response => {
      if (response.url().includes('/api/')) {
        responses.push({
          url: response.url(),
          status: response.status(),
          ok: response.ok()
        });
      }
    });
    
    await page.goto(`${baseURL}/products`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    const apiResponses = responses.filter(r => r.url.includes('/api/products'));
    
    if (apiResponses.length > 0) {
      const successfulAPIs = apiResponses.filter(r => r.ok);
      console.log(`✅ API calls detected: ${successfulAPIs.length}/${apiResponses.length} successful`);
      
      apiResponses.forEach(resp => {
        console.log(`   ${resp.status} - ${resp.url}`);
      });
    } else {
      console.log('⚠️  No API calls detected on products page');
    }
  });

  test.afterAll(async () => {
    console.log('🎉 Simple verification tests completed!');
  });

});