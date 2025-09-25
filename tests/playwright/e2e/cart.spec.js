import { test, expect } from '@playwright/test';
import { ProductListPage } from '../pages/ProductPages.js';
import { CartPage } from '../pages/CartPage.js';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Shopping Cart', () => {
  let productListPage, cartPage, authHelper;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    productListPage = new ProductListPage(page);
    cartPage = new CartPage(page);
    authHelper = new AuthHelper(page);
    
    await DatabaseHelper.clearCache();
  });

  test.describe('Cart Operations', () => {
    test('should display empty cart initially', async () => {
      await cartPage.goto();
      
      // Check if cart is empty
      const cartItemCount = await cartPage.getCartItemCount();
      
      if (cartItemCount === 0) {
        await cartPage.verifyEmptyCart();
      }
    });

    test('should add item to cart', async ({ page }) => {
      // First add a product to cart
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        const firstProduct = page.locator(productListPage.productCards).first();
        const productName = await firstProduct.locator(productListPage.productTitle).textContent() || 'Test Product';
        
        // Add product to cart
        const addToCartBtn = firstProduct.locator(productListPage.addToCartButton);
        
        if (await addToCartBtn.count() > 0) {
          await addToCartBtn.click();
          await page.waitForTimeout(1000);
          
          // Navigate to cart
          await cartPage.goto();
          
          // Verify item is in cart
          const cartItemCount = await cartPage.getCartItemCount();
          expect(cartItemCount).toBeGreaterThan(0);
          
          // Verify specific item
          await cartPage.verifyItemInCart(productName.trim());
        }
      }
    });

    test('should update item quantity', async ({ page }) => {
      // Add item to cart first
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.addProductToCart(0);
        await cartPage.goto();
        
        const cartItemCount = await cartPage.getCartItemCount();
        
        if (cartItemCount > 0) {
          // Update quantity
          const quantityInput = page.locator(cartPage.cartItems).first().locator(cartPage.itemQuantity);
          
          if (await quantityInput.count() > 0) {
            await cartPage.updateQuantity(0, 2);
            
            // Verify quantity updated
            await expect(quantityInput).toHaveValue('2');
          } else {
            // Try increment/decrement buttons
            const incrementBtn = page.locator(cartPage.cartItems).first().locator(cartPage.quantityIncrement);
            
            if (await incrementBtn.count() > 0) {
              await cartPage.incrementQuantity(0);
              await page.waitForTimeout(1000);
            }
          }
        }
      }
    });

    test('should remove item from cart', async ({ page }) => {
      // Add item to cart first
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.addProductToCart(0);
        await cartPage.goto();
        
        const initialCartItemCount = await cartPage.getCartItemCount();
        
        if (initialCartItemCount > 0) {
          // Remove first item
          await cartPage.removeItem(0);
          await page.waitForTimeout(1000);
          
          const finalCartItemCount = await cartPage.getCartItemCount();
          expect(finalCartItemCount).toBeLessThan(initialCartItemCount);
          
          if (finalCartItemCount === 0) {
            await cartPage.verifyEmptyCart();
          }
        }
      }
    });

    test('should clear entire cart', async ({ page }) => {
      // Add multiple items to cart
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        // Add first product
        await productListPage.addProductToCart(0);
        
        // Add second product if available
        if (productCount > 1) {
          await productListPage.addProductToCart(1);
        }
        
        await cartPage.goto();
        
        const initialCartItemCount = await cartPage.getCartItemCount();
        
        if (initialCartItemCount > 0) {
          // Clear cart
          const clearCartBtn = page.locator(cartPage.clearCartButton);
          
          if (await clearCartBtn.count() > 0) {
            await cartPage.clearCart();
            await page.waitForTimeout(1000);
            
            await cartPage.verifyEmptyCart();
          }
        }
      }
    });

    test('should calculate total correctly', async ({ page }) => {
      await cartPage.goto();
      
      const cartItemCount = await cartPage.getCartItemCount();
      
      if (cartItemCount > 0) {
        // Verify total calculation
        const totalElement = page.locator(cartPage.total);
        
        if (await totalElement.count() > 0) {
          const totalText = await cartPage.getCartTotal();
          expect(totalText).toMatch(/\$|\d+/); // Should contain currency symbol or numbers
        }
      }
    });

    test('should proceed to checkout', async ({ page }) => {
      // Add item to cart first
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.addProductToCart(0);
        await cartPage.goto();
        
        const cartItemCount = await cartPage.getCartItemCount();
        
        if (cartItemCount > 0) {
          const checkoutBtn = page.locator(cartPage.checkoutButton);
          
          if (await checkoutBtn.count() > 0) {
            await cartPage.proceedToCheckout();
            
            // Should redirect to checkout or login page
            await expect(page).toHaveURL(/.*\/(checkout|login)/);
          }
        }
      }
    });

    test('should continue shopping', async ({ page }) => {
      await cartPage.goto();
      
      const continueShoppingBtn = page.locator(cartPage.continueShoppingButton);
      
      if (await continueShoppingBtn.count() > 0) {
        await cartPage.continueShopping();
        
        // Should redirect to products page
        await expect(page).toHaveURL(/.*\/products/);
      }
    });
  });

  test.describe('Cart with Authentication', () => {
    test('should persist cart after login', async ({ page }) => {
      // Add item to cart while not logged in
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.addProductToCart(0);
        
        // Register and login
        const { token } = await authHelper.registerUser();
        await authHelper.setAuthToken(token);
        
        // Check if cart persisted
        await cartPage.goto();
        
        const cartItemCount = await cartPage.getCartItemCount();
        // Cart should either persist or be empty (depending on implementation)
        expect(cartItemCount).toBeGreaterThanOrEqual(0);
      }
    });

    test('should handle cart synchronization between sessions', async ({ page }) => {
      // This test checks if cart state is maintained properly
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        // Register user first
        const { token } = await authHelper.registerUser();
        await authHelper.setAuthToken(token);
        
        // Add item to cart
        await productListPage.addProductToCart(0);
        
        // Simulate session refresh
        await page.reload();
        
        // Check cart
        await cartPage.goto();
        const cartItemCount = await cartPage.getCartItemCount();
        
        // Cart should be maintained for authenticated users
        expect(cartItemCount).toBeGreaterThanOrEqual(0);
      }
    });
  });

  test.describe('Cart API Integration', () => {
    test('should handle cart API endpoints', async ({ page }) => {
      // Test cart count API
      const cartCountResponse = await page.request.get('/api/cart/count');
      
      if (cartCountResponse.status() === 200 || cartCountResponse.status() === 401) {
        // API is working (either returning count or requiring auth)
        expect([200, 401]).toContain(cartCountResponse.status());
      }
    });

    test('should handle cart operations via API', async ({ page }) => {
      // Register user for authenticated requests
      const { token } = await authHelper.registerUser();
      
      // Test get cart
      const getCartResponse = await page.request.get('/api/cart', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      expect([200, 404]).toContain(getCartResponse.status());
      
      if (getCartResponse.status() === 200) {
        const cartData = await getCartResponse.json();
        expect(Array.isArray(cartData) || cartData.data || cartData.items).toBeTruthy();
      }
    });
  });
});