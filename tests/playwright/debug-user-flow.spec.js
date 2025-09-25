import { test, expect } from '@playwright/test';

test.describe('Debug User Flow Issues', () => {
    let authToken;

    test('Debug complete user flow - Login, Add Address, Add to Cart, Orders', async ({ request, page }) => {
        console.log('\n=== DEBUGGING USER FLOW ISSUES ===\n');

        // Step 1: Test Login and Dashboard Redirect
        console.log('1. Testing Login and Dashboard Redirect...');
        await page.goto('http://localhost:8000/login');
        await page.waitForTimeout(2000);

        // Fill login form
        await page.fill('input[name="email"]', 'stripetester@bellgas.com');
        await page.fill('input[name="password"]', 'password123');
        
        // Take screenshot before login
        await page.screenshot({ path: 'debug-flow/01-before-login.png', fullPage: true });
        
        // Click login
        await page.click('button[type="submit"]');
        await page.waitForTimeout(5000); // Wait longer for redirect

        // Check current URL after login
        const currentUrl = page.url();
        console.log('Current URL after login:', currentUrl);
        
        // Take screenshot after login attempt
        await page.screenshot({ path: 'debug-flow/02-after-login-attempt.png', fullPage: true });

        // Check if we're on dashboard
        if (currentUrl.includes('/dashboard')) {
            console.log('✅ Login redirect to dashboard working');
        } else {
            console.log('❌ Login redirect failed - still on:', currentUrl);
        }

        // Step 2: Check authentication state in browser
        console.log('\n2. Checking authentication state...');
        const authState = await page.evaluate(() => {
            return {
                hasToken: !!localStorage.getItem('access_token'),
                user: window.app?.user,
                cart: window.app?.cart,
                axiosHeader: axios.defaults.headers.common['Authorization']
            };
        });
        console.log('Auth state:', authState);

        // Step 3: Try to access dashboard directly
        console.log('\n3. Testing direct dashboard access...');
        await page.goto('http://localhost:8000/dashboard');
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'debug-flow/03-dashboard-access.png', fullPage: true });
        console.log('Dashboard URL:', page.url());

        // Step 4: Try to access products page and add to cart
        console.log('\n4. Testing add to cart functionality...');
        await page.goto('http://localhost:8000/products');
        await page.waitForTimeout(3000);

        // Wait for products to load
        await page.waitForSelector('[data-product-id]', { timeout: 10000 });
        await page.screenshot({ path: 'debug-flow/04-products-page.png', fullPage: true });

        // Try to click add to cart button
        const addToCartButtons = await page.locator('button:has-text("Add to Cart")');
        const buttonCount = await addToCartButtons.count();
        console.log('Add to Cart buttons found:', buttonCount);

        if (buttonCount > 0) {
            // Check if button is enabled
            const isEnabled = await addToCartButtons.first().isEnabled();
            console.log('First Add to Cart button enabled:', isEnabled);
            
            if (isEnabled) {
                await addToCartButtons.first().click();
                await page.waitForTimeout(2000);
                console.log('✅ Clicked Add to Cart button');
                await page.screenshot({ path: 'debug-flow/05-after-add-to-cart.png', fullPage: true });
            } else {
                console.log('❌ Add to Cart button is disabled');
            }
        }

        // Step 5: Check cart state
        console.log('\n5. Checking cart state...');
        const cartState = await page.evaluate(() => {
            return {
                cartItems: window.app?.cart || [],
                cartCount: window.app?.cartCount || 0
            };
        });
        console.log('Cart state:', cartState);

        // Step 6: Try to access My Orders
        console.log('\n6. Testing My Orders access...');
        await page.goto('http://localhost:8000/orders');
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'debug-flow/06-my-orders-page.png', fullPage: true });
        console.log('My Orders URL:', page.url());

        // Step 7: Check browser console for errors
        console.log('\n7. Checking for JavaScript errors...');
        const browserErrors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                browserErrors.push(msg.text());
            }
        });

        // Wait a bit to collect any errors
        await page.waitForTimeout(2000);
        
        if (browserErrors.length > 0) {
            console.log('❌ JavaScript errors found:');
            browserErrors.forEach(error => console.log('  -', error));
        } else {
            console.log('✅ No JavaScript errors detected');
        }

        // Step 8: Test API endpoints directly
        console.log('\n8. Testing API endpoints...');
        
        // Get fresh token
        const loginResponse = await request.post('http://localhost:8000/api/auth/login', {
            data: {
                email: 'stripetester@bellgas.com',
                password: 'password123'
            }
        });

        if (loginResponse.ok()) {
            const loginData = await loginResponse.json();
            authToken = loginData.access_token;
            console.log('✅ Got fresh API token');

            // Test address endpoints
            const addressResponse = await request.get('http://localhost:8000/api/addresses', {
                headers: { 'Authorization': `Bearer ${authToken}` }
            });
            console.log('Addresses API status:', addressResponse.status());

            // Test cart endpoints
            const cartResponse = await request.get('http://localhost:8000/api/cart', {
                headers: { 'Authorization': `Bearer ${authToken}` }
            });
            console.log('Cart API status:', cartResponse.status());

            // Test orders endpoints
            const ordersResponse = await request.get('http://localhost:8000/api/orders', {
                headers: { 'Authorization': `Bearer ${authToken}` }
            });
            console.log('Orders API status:', ordersResponse.status());

        } else {
            console.log('❌ Failed to get API token');
        }

        console.log('\n=== DEBUG FLOW COMPLETED ===');
    });
});