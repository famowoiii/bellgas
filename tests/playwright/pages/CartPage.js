import { expect } from '@playwright/test';

export class CartPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.cartItems = '.cart-item, [data-testid="cart-item"]';
    this.itemName = '.item-name, [data-testid="item-name"]';
    this.itemPrice = '.item-price, [data-testid="item-price"]';
    this.itemQuantity = 'input[name*="quantity"], [data-testid="quantity"]';
    this.quantityIncrement = '.quantity-increment, [data-testid="quantity-increment"]';
    this.quantityDecrement = '.quantity-decrement, [data-testid="quantity-decrement"]';
    this.removeButton = 'button:has-text("Remove"), [data-testid="remove-item"]';
    this.subtotal = '.subtotal, [data-testid="subtotal"]';
    this.tax = '.tax, [data-testid="tax"]';
    this.shipping = '.shipping, [data-testid="shipping"]';
    this.total = '.total, [data-testid="total"]';
    this.checkoutButton = 'button:has-text("Checkout"), [data-testid="checkout"]';
    this.continueShoppingButton = 'a:has-text("Continue Shopping"), button:has-text("Continue Shopping"), [data-testid="continue-shopping"]';
    this.clearCartButton = 'button:has-text("Clear Cart"), [data-testid="clear-cart"]';
    this.emptyCartMessage = 'h3:has-text("Your cart is empty"), .empty-cart, [data-testid="empty-cart"]';
    this.couponInput = 'input[name="coupon"], [data-testid="coupon"]';
    this.applyCouponButton = 'button:has-text("Apply"), [data-testid="apply-coupon"]';
  }

  async goto() {
    // Use domcontentloaded to avoid waiting for font/image resources
    await this.page.goto('/cart', { waitUntil: 'domcontentloaded' });
    // Wait for Alpine.js to initialize (x-show directives to be processed)
    await this.page.waitForFunction(() => {
      // Alpine has initialized when it processes x-show and sets display styles
      const loadingEl = document.querySelector('[x-show="loading"]');
      if (!loadingEl) return false; // Element should exist
      // Alpine sets display:none when x-show is false
      return loadingEl.style.display === 'none';
    }, { timeout: 10000 }).catch(() => {});
    await this.page.waitForTimeout(200);
  }

  async updateQuantity(itemIndex, quantity) {
    const quantityInput = this.page.locator(this.cartItems).nth(itemIndex).locator(this.itemQuantity);
    await quantityInput.fill(quantity.toString());
    await quantityInput.blur();
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async incrementQuantity(itemIndex) {
    const incrementBtn = this.page.locator(this.cartItems).nth(itemIndex).locator(this.quantityIncrement);
    await incrementBtn.click();
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async decrementQuantity(itemIndex) {
    const decrementBtn = this.page.locator(this.cartItems).nth(itemIndex).locator(this.quantityDecrement);
    await decrementBtn.click();
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async removeItem(itemIndex) {
    const removeBtn = this.page.locator(this.cartItems).nth(itemIndex).locator(this.removeButton);
    await removeBtn.click();
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async clearCart() {
    await this.page.click(this.clearCartButton);
    // Confirm dialog if exists
    await this.page.click('button:has-text("Yes"), button:has-text("Confirm")');
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async applyCoupon(couponCode) {
    await this.page.fill(this.couponInput, couponCode);
    await this.page.click(this.applyCouponButton);
    await this.page.waitForResponse(response => response.url().includes('/api/cart') && response.status() === 200);
  }

  async proceedToCheckout() {
    await this.page.click(this.checkoutButton);
    await this.page.waitForURL('**/checkout**');
  }

  async continueShopping() {
    // Wait for the first visible Continue Shopping button
    const btn = this.page.locator(this.continueShoppingButton).first();
    await btn.waitFor({ state: 'visible', timeout: 10000 });
    await btn.click();
    await this.page.waitForURL('**/products**');
  }

  async verifyCartItems(expectedCount) {
    await expect(this.page.locator(this.cartItems)).toHaveCount(expectedCount);
  }

  async verifyItemInCart(itemName, quantity, price) {
    const item = this.page.locator(this.cartItems).filter({ hasText: itemName });
    await expect(item).toBeVisible();
    
    if (quantity !== undefined) {
      await expect(item.locator(this.itemQuantity)).toHaveValue(quantity.toString());
    }
    
    if (price !== undefined) {
      await expect(item.locator(this.itemPrice)).toContainText(price);
    }
  }

  async verifyEmptyCart() {
    // Use first() to avoid strict mode violation (also appears in cart sidebar)
    await expect(this.page.locator(this.emptyCartMessage).first()).toBeVisible({ timeout: 10000 });
    await expect(this.page.locator(this.cartItems)).toHaveCount(0);
  }

  async verifyTotal(expectedTotal) {
    await expect(this.page.locator(this.total)).toContainText(expectedTotal);
  }

  async getCartTotal() {
    return await this.page.locator(this.total).textContent();
  }

  async getCartItemCount() {
    return await this.page.locator(this.cartItems).count();
  }
}