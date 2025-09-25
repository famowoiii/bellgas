import { test, expect } from '@playwright/test';

test.describe('Cart Persistence Issue', () => {
    let authHelper;
    let dbHelper;

    test.beforeEach(async ({ page, browser }) => {
        // Import helpers
        const { AuthHelper } = await import('./helpers/auth.js');
        const { DatabaseHelper } = await import('./helpers/database.js');
        
        authHelper = new AuthHelper(page);
        dbHelper = new DatabaseHelper();
        
        // Reset database
        await dbHelper.migrate();
        await dbHelper.seed();
        
        // Login
        console.log('🔐 Logging in user...');
        await authHelper.loginUser('stripetester@bellgas.com', 'password123');
        
        // Wait for authentication to complete
        await page.waitForFunction(() => window.app && window.app.user, { timeout: 5000 });
        console.log('✅ User authenticated');
    });

    test('Cart persistence across page navigation', async ({ page }) => {
        console.log('🧪 Testing cart persistence across page navigation');
        
        // Go to products page
        console.log('📦 Going to products page...');
        await page.goto('http://localhost:8000/products');
        
        // Wait for page to load
        await page.waitForSelector('[x-data="productsIndex()"]');
        
        // Add product to cart
        console.log('🛒 Adding first product to cart...');
        const addToCartButton = page.locator('.add-to-cart-btn').first();
        await addToCartButton.click();
        
        // Wait for cart to update
        await page.waitForFunction(() => {
            return window.app && window.app.cart && window.app.cart.length > 0;
        }, { timeout: 10000 });
        
        console.log('✅ Product added to cart');
        
        // Verify cart has items
        const cartCount = await page.evaluate(() => window.app.cart.length);
        console.log(`📊 Cart has ${cartCount} items`);
        expect(cartCount).toBeGreaterThan(0);
        
        // Navigate to orders page
        console.log('📄 Navigating to My Orders page...');
        await page.goto('http://localhost:8000/orders');
        
        // Wait for orders page to load
        await page.waitForSelector('[x-data="simpleOrdersPage()"]');
        
        // Check if cart is still populated
        console.log('🔍 Checking if cart persists after navigation...');
        const cartCountAfterNavigation = await page.evaluate(() => {
            if (!window.app) {
                console.log('❌ window.app not available');
                return 0;
            }
            if (!window.app.cart) {
                console.log('❌ window.app.cart not available');
                return 0;
            }
            console.log('Current cart:', window.app.cart);
            return window.app.cart.length;
        });
        
        console.log(`📊 Cart after navigation has ${cartCountAfterNavigation} items`);
        
        // This should NOT be zero if cart persistence is working
        if (cartCountAfterNavigation === 0) {
            console.log('❌ Cart persistence issue detected!');
            
            // Check authentication status
            const userStatus = await page.evaluate(() => {
                return {
                    hasUser: !!window.app.user,
                    hasToken: !!localStorage.getItem('access_token'),
                    axiosHeader: axios.defaults.headers.common['Authorization']
                };
            });
            console.log('Auth status:', userStatus);
            
            // Try manually loading cart
            console.log('🔄 Trying manual cart load...');
            await page.evaluate(() => {
                if (window.app && window.app.loadCart) {
                    return window.app.loadCart();
                }
            });
            
            // Wait a bit and check again
            await page.waitForTimeout(2000);
            const cartCountAfterReload = await page.evaluate(() => window.app?.cart?.length || 0);
            console.log(`📊 Cart after manual reload has ${cartCountAfterReload} items`);
        }
        
        expect(cartCountAfterNavigation).toBeGreaterThan(0);
    });

    test('API cart endpoints work correctly', async ({ page }) => {
        console.log('🧪 Testing cart API endpoints directly');
        
        // Test cart API directly
        const cartApiResponse = await page.evaluate(async () => {
            try {
                const response = await axios.get('/api/cart');
                return {
                    success: true,
                    data: response.data,
                    status: response.status
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.response?.data || error.message,
                    status: error.response?.status
                };
            }
        });
        
        console.log('Cart API response:', cartApiResponse);
        expect(cartApiResponse.success).toBe(true);
    });
});