import { expect } from '@playwright/test';

export class HomePage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.logo = 'a[href="/"]';
    this.navigationMenu = 'nav';
    this.productsLink = 'a[href*="/products"]';
    this.cartLink = 'a[href*="/cart"]';
    this.loginLink = 'a[href*="/login"]';
    this.registerLink = 'a[href*="/register"]';
    this.userMenu = '[data-testid="user-menu"]';
    this.searchBox = 'input[type="search"], input[placeholder*="search" i]';
    this.categoryCards = '.category-card, [data-testid="category-card"]';
    this.featuredProducts = '.featured-product, [data-testid="featured-product"]';
    this.heroSection = '.hero, [data-testid="hero"]';
  }

  async goto() {
    await this.page.goto('/');
    await this.page.waitForLoadState('networkidle');
  }

  async navigateToProducts() {
    await this.page.click(this.productsLink);
    await this.page.waitForURL('**/products**');
  }

  async navigateToCart() {
    await this.page.click(this.cartLink);
    await this.page.waitForURL('**/cart**');
  }

  async navigateToLogin() {
    await this.page.click(this.loginLink);
    await this.page.waitForURL('**/login**');
  }

  async navigateToRegister() {
    await this.page.click(this.registerLink);
    await this.page.waitForURL('**/register**');
  }

  async searchProducts(query) {
    await this.page.fill(this.searchBox, query);
    await this.page.keyboard.press('Enter');
    await this.page.waitForLoadState('networkidle');
  }

  async selectCategory(categoryName) {
    await this.page.click(`${this.categoryCards}:has-text("${categoryName}")`);
    await this.page.waitForLoadState('networkidle');
  }

  async selectFeaturedProduct(productName) {
    await this.page.click(`${this.featuredProducts}:has-text("${productName}")`);
    await this.page.waitForLoadState('networkidle');
  }

  async verifyHomePageLoaded() {
    await expect(this.page.locator(this.logo)).toBeVisible();
    await expect(this.page.locator(this.navigationMenu)).toBeVisible();
  }

  async verifyUserAuthenticated(userName) {
    await expect(this.page.locator(this.userMenu)).toContainText(userName);
  }

  async verifyFeaturedProductsVisible() {
    await expect(this.page.locator(this.featuredProducts).first()).toBeVisible();
  }

  async verifyCategoriesVisible() {
    await expect(this.page.locator(this.categoryCards).first()).toBeVisible();
  }
}