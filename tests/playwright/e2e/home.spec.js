import { test, expect } from '@playwright/test';
import { HomePage } from '../pages/HomePage.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Home Page', () => {
  let homePage;

  test.beforeEach(async ({ page }) => {
    homePage = new HomePage(page);
    await DatabaseHelper.clearCache();
    await homePage.goto();
  });

  test('should load home page correctly', async () => {
    await homePage.verifyHomePageLoaded();
    
    // Check basic page elements
    await expect(page.locator('title')).toContainText(/BellGas|Home/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('should display navigation menu', async () => {
    await expect(homePage.page.locator(homePage.navigationMenu)).toBeVisible();
    
    // Check main navigation links
    const expectedLinks = ['Products', 'Cart', 'Login'];
    for (const linkText of expectedLinks) {
      await expect(homePage.page.locator(`nav a:has-text("${linkText}")`)).toBeVisible();
    }
  });

  test('should navigate to products page', async () => {
    await homePage.navigateToProducts();
    await expect(homePage.page).toHaveURL(/.*\/products/);
  });

  test('should navigate to cart page', async () => {
    await homePage.navigateToCart();
    await expect(homePage.page).toHaveURL(/.*\/cart/);
  });

  test('should navigate to login page', async () => {
    await homePage.navigateToLogin();
    await expect(homePage.page).toHaveURL(/.*\/login/);
  });

  test('should display featured products if available', async ({ page }) => {
    // Check if featured products section exists
    const featuredSection = page.locator('.featured, .hero, [data-testid="featured"]');
    if (await featuredSection.count() > 0) {
      await expect(featuredSection).toBeVisible();
    }
  });

  test('should display categories if available', async ({ page }) => {
    // Check if categories section exists
    const categoriesSection = page.locator('.categories, [data-testid="categories"]');
    if (await categoriesSection.count() > 0) {
      await expect(categoriesSection).toBeVisible();
    }
  });

  test('should be responsive on mobile', async ({ page, browserName }) => {
    if (browserName === 'chromium') {
      // Test mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      await homePage.goto();
      
      await homePage.verifyHomePageLoaded();
      
      // Check if mobile menu exists or main menu is still accessible
      const mobileMenu = page.locator('.mobile-menu, .hamburger, [data-testid="mobile-menu"]');
      const mainMenu = page.locator(homePage.navigationMenu);
      
      const hasMobileMenu = await mobileMenu.count() > 0;
      const hasVisibleMainMenu = await mainMenu.isVisible();
      
      expect(hasMobileMenu || hasVisibleMainMenu).toBeTruthy();
    }
  });

  test('should handle search functionality if available', async ({ page }) => {
    const searchBox = page.locator(homePage.searchBox);
    
    if (await searchBox.count() > 0) {
      await searchBox.fill('gas');
      await page.keyboard.press('Enter');
      
      // Should either redirect to products page or show search results
      await page.waitForLoadState('networkidle');
      const currentUrl = page.url();
      
      expect(
        currentUrl.includes('/products') || 
        currentUrl.includes('/search') ||
        page.locator('.search-results, [data-testid="search-results"]').count() > 0
      ).toBeTruthy();
    }
  });

  test('should load all critical assets', async ({ page }) => {
    const response = await page.goto('/');
    expect(response.status()).toBe(200);
    
    // Check for critical CSS
    const stylesheets = page.locator('link[rel="stylesheet"]');
    const stylesheetCount = await stylesheets.count();
    expect(stylesheetCount).toBeGreaterThan(0);
    
    // Check for critical JS
    const scripts = page.locator('script[src]');
    const scriptCount = await scripts.count();
    expect(scriptCount).toBeGreaterThan(0);
  });

  test('should not have console errors', async ({ page }) => {
    const consoleMessages = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleMessages.push(msg.text());
      }
    });

    await homePage.goto();
    await page.waitForTimeout(2000); // Wait for any async operations
    
    // Filter out known acceptable errors
    const criticalErrors = consoleMessages.filter(msg => 
      !msg.includes('favicon') && 
      !msg.includes('404') &&
      !msg.includes('AdBlock')
    );
    
    expect(criticalErrors).toHaveLength(0);
  });
});