import { expect } from '@playwright/test';

export class ProductListPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.productCards = '.product-card, [data-testid="product-card"]';
    this.productTitle = '.product-title, [data-testid="product-title"]';
    this.productPrice = '.product-price, [data-testid="product-price"]';
    this.productImage = '.product-image, [data-testid="product-image"]';
    this.addToCartButton = 'button:has-text("Add to Cart"), [data-testid="add-to-cart"]';
    this.searchBox = 'input[type="search"], input[placeholder*="search" i]';
    this.categoryFilter = '.category-filter, [data-testid="category-filter"]';
    this.sortBy = 'select[name="sort"], [data-testid="sort-by"]';
    this.priceFilter = '.price-filter, [data-testid="price-filter"]';
    this.loadMoreButton = 'button:has-text("Load More"), [data-testid="load-more"]';
    this.pagination = '.pagination, [data-testid="pagination"]';
  }

  async goto() {
    await this.page.goto('/products');
    await this.page.waitForLoadState('networkidle');
  }

  async searchProducts(query) {
    await this.page.fill(this.searchBox, query);
    await this.page.keyboard.press('Enter');
    await this.page.waitForLoadState('networkidle');
  }

  async filterByCategory(category) {
    await this.page.selectOption(this.categoryFilter, category);
    await this.page.waitForLoadState('networkidle');
  }

  async sortProducts(sortOption) {
    await this.page.selectOption(this.sortBy, sortOption);
    await this.page.waitForLoadState('networkidle');
  }

  async selectProduct(productIndex = 0) {
    const product = this.page.locator(this.productCards).nth(productIndex);
    await product.click();
    await this.page.waitForLoadState('networkidle');
  }

  async addProductToCart(productIndex = 0) {
    const addToCartBtn = this.page.locator(this.productCards).nth(productIndex).locator(this.addToCartButton);
    await addToCartBtn.click();
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async verifyProductsLoaded() {
    await expect(this.page.locator(this.productCards).first()).toBeVisible();
  }

  async verifyProductCount(expectedCount) {
    await expect(this.page.locator(this.productCards)).toHaveCount(expectedCount);
  }

  async verifySearchResults(searchTerm) {
    const products = await this.page.locator(this.productTitle).allTextContents();
    expect(products.some(title => title.toLowerCase().includes(searchTerm.toLowerCase()))).toBeTruthy();
  }
}

export class ProductDetailPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.productTitle = 'h1, [data-testid="product-title"]';
    this.productPrice = '.price, [data-testid="product-price"]';
    this.productDescription = '.description, [data-testid="product-description"]';
    this.productImage = '.product-image img, [data-testid="product-image"]';
    this.quantityInput = 'input[name="quantity"], [data-testid="quantity"]';
    this.addToCartButton = 'button:has-text("Add to Cart"), [data-testid="add-to-cart"]';
    this.buyNowButton = 'button:has-text("Buy Now"), [data-testid="buy-now"]';
    this.stockStatus = '.stock-status, [data-testid="stock-status"]';
    this.variantSelector = '.variant-selector, [data-testid="variant-selector"]';
    this.breadcrumb = '.breadcrumb, [data-testid="breadcrumb"]';
    this.relatedProducts = '.related-products, [data-testid="related-products"]';
  }

  async goto(productSlug) {
    await this.page.goto(`/products/${productSlug}`);
    await this.page.waitForLoadState('networkidle');
  }

  async setQuantity(quantity) {
    await this.page.fill(this.quantityInput, quantity.toString());
  }

  async selectVariant(variantName) {
    if (await this.page.locator(this.variantSelector).isVisible()) {
      await this.page.selectOption(this.variantSelector, variantName);
    }
  }

  async addToCart(quantity = 1) {
    await this.setQuantity(quantity);
    await this.page.click(this.addToCartButton);
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async buyNow(quantity = 1) {
    await this.setQuantity(quantity);
    await this.page.click(this.buyNowButton);
    await this.page.waitForURL('**/checkout**');
  }

  async verifyProductDetails(productData) {
    await expect(this.page.locator(this.productTitle)).toContainText(productData.name);
    await expect(this.page.locator(this.productPrice)).toContainText(productData.price);
    await expect(this.page.locator(this.productImage)).toBeVisible();
  }

  async verifyStockStatus(status) {
    await expect(this.page.locator(this.stockStatus)).toContainText(status);
  }

  async verifyAddToCartSuccess() {
    // Look for success message or cart count update
    await expect(this.page.locator('.cart-success, .alert-success, [data-testid="cart-success"]')).toBeVisible();
  }
}