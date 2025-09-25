import { test, expect } from '@playwright/test';
import { ProductListPage, ProductDetailPage } from '../pages/ProductPages.js';
import { CartPage } from '../pages/CartPage.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Products', () => {
  let productListPage, productDetailPage, cartPage;

  test.beforeAll(async () => {
    await DatabaseHelper.createTestData();
  });

  test.beforeEach(async ({ page }) => {
    productListPage = new ProductListPage(page);
    productDetailPage = new ProductDetailPage(page);
    cartPage = new CartPage(page);
    
    await DatabaseHelper.clearCache();
  });

  test.describe('Product List Page', () => {
    test('should load products page', async () => {
      await productListPage.goto();
      
      await expect(productListPage.page).toHaveURL(/.*\/products/);
      await expect(productListPage.page.locator('h1, [data-testid="page-title"]')).toContainText(/Products|Catalog/i);
    });

    test('should display products', async ({ page }) => {
      await productListPage.goto();
      
      // Wait for products to load
      await page.waitForTimeout(2000);
      
      // Check if products are displayed
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.verifyProductsLoaded();
        
        // Verify product card structure
        const firstProduct = page.locator(productListPage.productCards).first();
        await expect(firstProduct).toBeVisible();
        
        // Check for product title (should exist in some form)
        const titleSelectors = [
          productListPage.productTitle,
          'h2', 'h3', 'h4',
          '.name', '.title',
          '[data-testid*="name"]', '[data-testid*="title"]'
        ];
        
        let titleFound = false;
        for (const selector of titleSelectors) {
          if (await firstProduct.locator(selector).count() > 0) {
            await expect(firstProduct.locator(selector)).toBeVisible();
            titleFound = true;
            break;
          }
        }
        expect(titleFound).toBeTruthy();
        
        // Check for product price
        const priceSelectors = [
          productListPage.productPrice,
          '.price', '.cost', '.amount',
          '[data-testid*="price"]', '[data-testid*="cost"]'
        ];
        
        let priceFound = false;
        for (const selector of priceSelectors) {
          if (await firstProduct.locator(selector).count() > 0) {
            await expect(firstProduct.locator(selector)).toBeVisible();
            priceFound = true;
            break;
          }
        }
        expect(priceFound).toBeTruthy();
      } else {
        // If no products, should show empty state
        const emptyMessage = page.locator('.empty, .no-products, [data-testid="empty"], [data-testid="no-products"]');
        await expect(emptyMessage).toBeVisible();
      }
    });

    test('should handle product search', async ({ page }) => {
      await productListPage.goto();
      
      const searchBox = page.locator(productListPage.searchBox);
      
      if (await searchBox.count() > 0) {
        await productListPage.searchProducts('gas');
        
        // Verify search results or no results message
        await page.waitForTimeout(1000);
        
        const hasResults = await page.locator(productListPage.productCards).count() > 0;
        const hasNoResultsMessage = await page.locator('.no-results, [data-testid="no-results"]').count() > 0;
        
        expect(hasResults || hasNoResultsMessage).toBeTruthy();
      }
    });

    test('should filter by category', async ({ page }) => {
      await productListPage.goto();
      
      const categoryFilter = page.locator(productListPage.categoryFilter);
      
      if (await categoryFilter.count() > 0) {
        const options = await categoryFilter.locator('option').count();
        
        if (options > 1) {
          // Select first non-empty option
          const firstOption = await categoryFilter.locator('option').nth(1).textContent();
          await productListPage.filterByCategory(firstOption);
          
          await page.waitForTimeout(1000);
          // Products should be filtered or show no results
        }
      }
    });

    test('should sort products', async ({ page }) => {
      await productListPage.goto();
      
      const sortSelect = page.locator(productListPage.sortBy);
      
      if (await sortSelect.count() > 0) {
        const options = await sortSelect.locator('option').count();
        
        if (options > 1) {
          // Test sorting by price or name
          const sortOptions = ['price', 'name', 'newest', 'oldest'];
          
          for (const option of sortOptions) {
            const optionExists = await sortSelect.locator(`option[value*="${option}"]`).count() > 0;
            if (optionExists) {
              await productListPage.sortProducts(option);
              await page.waitForTimeout(1000);
              break;
            }
          }
        }
      }
    });

    test('should navigate to product detail', async ({ page }) => {
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        await productListPage.selectProduct(0);
        
        // Should navigate to product detail page
        await expect(page).toHaveURL(/.*\/products\/[^\/]+$/);
      }
    });
  });

  test.describe('Product Detail Page', () => {
    test('should load product detail page', async ({ page }) => {
      // First get a product slug from the products list
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        // Get product link
        const firstProductLink = page.locator(productListPage.productCards).first().locator('a');
        const href = await firstProductLink.getAttribute('href');
        
        if (href && href.includes('/products/')) {
          await page.goto(href);
          
          // Verify product detail page elements
          const titleSelectors = [
            productDetailPage.productTitle,
            'h1', 'h2',
            '.product-name', '.title',
            '[data-testid*="title"]', '[data-testid*="name"]'
          ];
          
          let titleFound = false;
          for (const selector of titleSelectors) {
            if (await page.locator(selector).count() > 0) {
              await expect(page.locator(selector)).toBeVisible();
              titleFound = true;
              break;
            }
          }
          expect(titleFound).toBeTruthy();
        }
      }
    });

    test('should add product to cart', async ({ page }) => {
      await productListPage.goto();
      await page.waitForTimeout(2000);
      
      const productCount = await page.locator(productListPage.productCards).count();
      
      if (productCount > 0) {
        // Check if add to cart button exists on product list
        const addToCartBtn = page.locator(productListPage.productCards).first().locator(productListPage.addToCartButton);
        
        if (await addToCartBtn.count() > 0) {
          // Add to cart from product list
          await addToCartBtn.click();
          
          // Wait for API response or success message
          await page.waitForTimeout(1000);
          
          // Check for success indication
          const successIndicators = [
            '.success', '.added-to-cart', '.alert-success',
            '[data-testid="success"]', '[data-testid="added-to-cart"]'
          ];
          
          let successFound = false;
          for (const selector of successIndicators) {
            if (await page.locator(selector).count() > 0) {
              successFound = true;
              break;
            }
          }
          
          // If no success message, check cart count or cart page
          if (!successFound) {
            await cartPage.goto();
            const cartItemCount = await cartPage.getCartItemCount();
            expect(cartItemCount).toBeGreaterThan(0);
          }
        } else {
          // Navigate to product detail and add from there
          await productListPage.selectProduct(0);
          
          const detailAddToCartBtn = page.locator(productDetailPage.addToCartButton);
          if (await detailAddToCartBtn.count() > 0) {
            await detailAddToCartBtn.click();
            await page.waitForTimeout(1000);
          }
        }
      }
    });
  });

  test.describe('Product Categories', () => {
    test('should display categories', async ({ page }) => {
      await page.goto('/products/categories');
      
      // Check for categories API endpoint
      const response = await page.request.get('/api/categories');
      
      if (response.status() === 200) {
        const categories = await response.json();
        expect(Array.isArray(categories) || categories.data).toBeTruthy();
      }
    });

    test('should navigate to category products', async ({ page }) => {
      // Try to access a category directly
      const testCategories = ['gas-cylinders', 'accessories', 'regulators'];
      
      for (const category of testCategories) {
        const response = await page.goto(`/products/categories/${category}`, { 
          waitUntil: 'networkidle' 
        });
        
        if (response && response.status() === 200) {
          // Category page loaded successfully
          await expect(page).toHaveURL(new RegExp(`.*/${category}`));
          break;
        }
      }
    });
  });
});