const { test, expect } = require('@playwright/test');

test.describe('BellGas Homepage', () => {
  
  test('should load homepage successfully', async ({ page }) => {
    await page.goto('/');
    
    // Check page title
    await expect(page).toHaveTitle(/BellGas - Premium LPG Services/);
    
    // Check main elements are present
    await expect(page.locator('h1')).toContainText('Premium LPG Services');
    await expect(page.locator('nav')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();
  });

  test('should have working navigation menu', async ({ page }) => {
    await page.goto('/');
    
    // Check navigation links
    const nav = page.locator('nav');
    await expect(nav.locator('a[href="/products"]')).toBeVisible();
    await expect(nav.locator('a[href="/about"]')).toBeVisible();
    await expect(nav.locator('a[href="/contact"]')).toBeVisible();
    
    // Check BellGas logo/brand
    await expect(nav.locator('text=BellGas')).toBeVisible();
  });

  test('should show login/register buttons when not authenticated', async ({ page }) => {
    await page.goto('/');
    
    // Check auth buttons in navigation
    await expect(page.locator('a[href="/login"]')).toBeVisible();
    await expect(page.locator('a[href="/register"]')).toBeVisible();
  });

  test('should have shopping cart icon', async ({ page }) => {
    await page.goto('/');
    
    // Check cart icon is present
    await expect(page.locator('.fas.fa-shopping-cart')).toBeVisible();
  });

  test('should display featured products section', async ({ page }) => {
    await page.goto('/');
    
    // Wait for products to load (simulated)
    await page.waitForTimeout(2000);
    
    // Check products section
    await expect(page.locator('text=Our Products')).toBeVisible();
    
    // Should show "View All Products" link
    await expect(page.locator('a[href="/products"]')).toBeVisible();
  });

  test('should have working CTA buttons', async ({ page }) => {
    await page.goto('/');
    
    // Check main CTA buttons
    const shopNowButtons = page.locator('a[href="/products"]:has-text("Shop Now")');
    await expect(shopNowButtons.first()).toBeVisible();
    
    const learnMoreButton = page.locator('a[href="/about"]:has-text("Learn More")');
    await expect(learnMoreButton).toBeVisible();
  });

  test('should be mobile responsive', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/');
    
    // Check mobile menu button appears
    await expect(page.locator('.md\\:hidden button')).toBeVisible();
    
    // Click mobile menu
    await page.locator('.md\\:hidden button').click();
    
    // Check mobile menu opens
    await expect(page.locator('.md\\:hidden + div')).toBeVisible();
  });

  test('should load footer information', async ({ page }) => {
    await page.goto('/');
    
    const footer = page.locator('footer');
    
    // Check footer sections
    await expect(footer.locator('text=BellGas')).toBeVisible();
    await expect(footer.locator('text=Quick Links')).toBeVisible();
    await expect(footer.locator('text=Contact Info')).toBeVisible();
    
    // Check contact information
    await expect(footer.locator('text=+61 2 1234 5678')).toBeVisible();
    await expect(footer.locator('text=support@bellgas.com.au')).toBeVisible();
    
    // Check copyright
    await expect(footer.locator('text=© 2025 BellGas')).toBeVisible();
  });

  test('should have working "How It Works" section', async ({ page }) => {
    await page.goto('/');
    
    // Check "How It Works" section
    await expect(page.locator('text=How It Works')).toBeVisible();
    
    // Check the 4 steps
    await expect(page.locator('text=Browse & Select')).toBeVisible();
    await expect(page.locator('text=Secure Payment')).toBeVisible();
    await expect(page.locator('text=Fast Delivery')).toBeVisible();
    await expect(page.locator('text=Enjoy Service')).toBeVisible();
  });

  test('should display features section', async ({ page }) => {
    await page.goto('/');
    
    // Check features section
    await expect(page.locator('text=Why Choose BellGas?')).toBeVisible();
    
    // Check feature cards
    await expect(page.locator('text=Fast Delivery')).toBeVisible();
    await expect(page.locator('text=Safety First')).toBeVisible();
    await expect(page.locator('text=Best Prices')).toBeVisible();
  });

});