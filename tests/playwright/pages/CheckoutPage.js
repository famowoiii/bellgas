import { expect } from '@playwright/test';

export class CheckoutPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.billingForm = '.billing-form, [data-testid="billing-form"]';
    this.shippingForm = '.shipping-form, [data-testid="shipping-form"]';
    this.paymentForm = '.payment-form, [data-testid="payment-form"]';
    
    // Billing fields
    this.firstName = 'input[name="first_name"], input[name="billing_first_name"]';
    this.lastName = 'input[name="last_name"], input[name="billing_last_name"]';
    this.email = 'input[name="email"], input[name="billing_email"]';
    this.phone = 'input[name="phone"], input[name="billing_phone"]';
    this.address = 'input[name="address"], input[name="billing_address"]';
    this.city = 'input[name="city"], input[name="billing_city"]';
    this.state = 'select[name="state"], input[name="billing_state"]';
    this.zipCode = 'input[name="zip_code"], input[name="billing_zip_code"]';
    this.country = 'select[name="country"], input[name="billing_country"]';
    
    // Shipping options
    this.sameAsBilling = 'input[name="same_as_billing"], input[type="checkbox"][name*="same"]';
    this.shippingMethod = 'input[name="shipping_method"], [data-testid="shipping-method"]';
    
    // Payment fields
    this.paymentMethod = 'input[name="payment_method"], [data-testid="payment-method"]';
    this.cardNumber = 'input[name="card_number"], [data-testid="card-number"]';
    this.expiryDate = 'input[name="expiry"], [data-testid="expiry"]';
    this.cvv = 'input[name="cvv"], [data-testid="cvv"]';
    this.cardName = 'input[name="card_name"], [data-testid="card-name"]';
    
    // Stripe Elements (if using Stripe)
    this.stripeCardElement = 'iframe[name*="__privateStripeFrame"]';
    
    // Order summary
    this.orderSummary = '.order-summary, [data-testid="order-summary"]';
    this.orderItems = '.order-item, [data-testid="order-item"]';
    this.subtotalAmount = '.subtotal-amount, [data-testid="subtotal"]';
    this.taxAmount = '.tax-amount, [data-testid="tax"]';
    this.shippingAmount = '.shipping-amount, [data-testid="shipping"]';
    this.totalAmount = '.total-amount, [data-testid="total"]';
    
    // Buttons
    this.placeOrderButton = 'button:has-text("Place Order"), [data-testid="place-order"]';
    this.backToCartButton = 'button:has-text("Back to Cart"), [data-testid="back-to-cart"]';
    
    // Messages
    this.errorMessage = '.error, .alert-danger, [data-testid="error"]';
    this.loadingIndicator = '.loading, [data-testid="loading"]';
  }

  async goto() {
    await this.page.goto('/checkout');
    await this.page.waitForLoadState('networkidle');
  }

  async fillBillingInformation(billingData) {
    const {
      firstName,
      lastName,
      email,
      phone,
      address,
      city,
      state,
      zipCode,
      country = 'Australia'
    } = billingData;

    await this.page.fill(this.firstName, firstName);
    await this.page.fill(this.lastName, lastName);
    await this.page.fill(this.email, email);
    if (phone) await this.page.fill(this.phone, phone);
    await this.page.fill(this.address, address);
    await this.page.fill(this.city, city);
    
    if (await this.page.locator(this.state).isVisible()) {
      await this.page.selectOption(this.state, state);
    }
    
    await this.page.fill(this.zipCode, zipCode);
    
    if (await this.page.locator(this.country).isVisible()) {
      await this.page.selectOption(this.country, country);
    }
  }

  async selectShippingMethod(method) {
    await this.page.check(`${this.shippingMethod}[value="${method}"]`);
  }

  async selectPaymentMethod(method) {
    await this.page.check(`${this.paymentMethod}[value="${method}"]`);
  }

  async fillCreditCardDetails(cardData) {
    const { number, expiry, cvv, name } = cardData;
    
    // Check if using Stripe Elements
    if (await this.page.locator(this.stripeCardElement).count() > 0) {
      await this.fillStripeCard(cardData);
    } else {
      // Regular form inputs
      await this.page.fill(this.cardNumber, number);
      await this.page.fill(this.expiryDate, expiry);
      await this.page.fill(this.cvv, cvv);
      if (name) await this.page.fill(this.cardName, name);
    }
  }

  async fillStripeCard(cardData) {
    const { number, expiry, cvv } = cardData;
    
    // Switch to Stripe iframe context
    const stripeFrame = this.page.frameLocator(this.stripeCardElement);
    await stripeFrame.locator('input[name="cardnumber"]').fill(number);
    await stripeFrame.locator('input[name="exp-date"]').fill(expiry);
    await stripeFrame.locator('input[name="cvc"]').fill(cvv);
  }

  async placeOrder() {
    await this.page.click(this.placeOrderButton);
    
    // Wait for order processing
    await this.page.waitForLoadState('networkidle');
    
    // Wait for either success page or error message
    try {
      await this.page.waitForURL('**/order/**', { timeout: 10000 });
    } catch {
      // If not redirected, check for errors
      await expect(this.page.locator(this.errorMessage)).toBeVisible();
    }
  }

  async backToCart() {
    await this.page.click(this.backToCartButton);
    await this.page.waitForURL('**/cart**');
  }

  async verifyOrderSummary(expectedItems) {
    await expect(this.page.locator(this.orderSummary)).toBeVisible();
    
    if (expectedItems) {
      for (const item of expectedItems) {
        await expect(this.page.locator(this.orderItems).filter({ hasText: item.name })).toBeVisible();
      }
    }
  }

  async verifyTotalAmount(expectedTotal) {
    await expect(this.page.locator(this.totalAmount)).toContainText(expectedTotal);
  }

  async verifyPaymentError(errorMessage) {
    await expect(this.page.locator(this.errorMessage)).toContainText(errorMessage);
  }

  async verifyOrderSuccess() {
    // Should be redirected to order confirmation page
    await this.page.waitForURL(url => url.includes('/order/') || url.includes('/success'));
  }

  // Test card data
  static getTestCardData() {
    return {
      validVisa: {
        number: '4242424242424242',
        expiry: '12/25',
        cvv: '123',
        name: 'Test User'
      },
      invalidCard: {
        number: '4000000000000002',
        expiry: '12/25',
        cvv: '123',
        name: 'Test User'
      },
      expiredCard: {
        number: '4000000000000069',
        expiry: '12/20',
        cvv: '123',
        name: 'Test User'
      }
    };
  }
}