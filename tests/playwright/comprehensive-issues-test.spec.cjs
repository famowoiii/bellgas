const { test, expect } = require('@playwright/test');

test.describe('BellGas Comprehensive Issues Test', () => {
    const BASE_URL = 'http://127.0.0.1:8000';
    let adminToken = '';
    let customerToken = '';

    test.beforeAll(async ({ request }) => {
        // Get admin token
        const adminLogin = await request.post(`${BASE_URL}/api/v1/auth/login`, {
            data: {
                email: 'admin@bellgas.com',
                password: 'password'
            }
        });
        const adminData = await adminLogin.json();
        adminToken = adminData.access_token;

        // Get customer token
        const customerLogin = await request.post(`${BASE_URL}/api/v1/auth/login`, {
            data: {
                email: 'customer@bellgas.com',
                password: 'password'
            }
        });
        const customerData = await customerLogin.json();
        customerToken = customerData.access_token;
    });

    test('Issue 1: Admin Dashboard - Missing Product Management Features', async ({ page }) => {
        console.log('Testing admin dashboard issues...');
        
        // Login as admin via web UI
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'admin@bellgas.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        // Wait for redirect and check if we're still on login page
        await page.waitForTimeout(2000);
        const currentUrl = page.url();
        console.log('After login URL:', currentUrl);
        
        // Try to access admin dashboard
        await page.goto(`${BASE_URL}/admin/dashboard`);
        await page.waitForTimeout(2000);
        
        // Check console errors
        const messages = [];
        page.on('console', msg => messages.push(msg.text()));
        page.on('pageerror', error => console.log('Page error:', error.message));
        
        // Take screenshot of admin dashboard
        await page.screenshot({ path: '/d/sopek/bellgas-laravel/test-results/admin-dashboard.png' });
        
        // Check for missing elements
        const hasProductManagement = await page.locator('text=Product Management').count();
        const hasOrderConfirmation = await page.locator('text=Order Confirmation').count();
        const hasSalesReports = await page.locator('text=Sales Reports').count();
        
        console.log('Admin Dashboard Issues:');
        console.log('- Product Management present:', hasProductManagement > 0);
        console.log('- Order Confirmation present:', hasOrderConfirmation > 0);
        console.log('- Sales Reports present:', hasSalesReports > 0);
        console.log('- Console messages:', messages);
    });

    test('Issue 2: Cart Quantity Increment Not Working', async ({ page, request }) => {
        console.log('Testing cart quantity increment...');
        
        // Add item to cart via API
        await request.post(`${BASE_URL}/api/v1/cart`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`,
                'Content-Type': 'application/json'
            },
            data: {
                product_variant_id: 1,
                quantity: 1
            }
        });
        
        // Login via web
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'customer@bellgas.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        await page.waitForTimeout(2000);
        
        // Go to cart page
        await page.goto(`${BASE_URL}/cart`);
        await page.waitForTimeout(3000);
        
        // Look for quantity increment button
        const incrementButton = page.locator('button[data-action="increase"], .quantity-plus, .btn-plus');
        const quantityInput = page.locator('input[type="number"], .quantity-input');
        
        console.log('Cart elements found:');
        console.log('- Increment buttons:', await incrementButton.count());
        console.log('- Quantity inputs:', await quantityInput.count());
        
        // Try to increment quantity
        if (await incrementButton.count() > 0) {
            await incrementButton.first().click();
            await page.waitForTimeout(1000);
        }
        
        await page.screenshot({ path: '/d/sopek/bellgas-laravel/test-results/cart-issues.png' });
        
        // Check console for errors
        const messages = [];
        page.on('console', msg => messages.push(msg.text()));
        console.log('Cart console messages:', messages);
    });

    test('Issue 3: 422 Error When Placing Orders', async ({ page, request }) => {
        console.log('Testing order placement 422 error...');
        
        // Clear cart and add item
        await request.delete(`${BASE_URL}/api/v1/cart`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`
            }
        });
        
        await request.post(`${BASE_URL}/api/v1/cart`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`,
                'Content-Type': 'application/json'
            },
            data: {
                product_variant_id: 1,
                quantity: 1
            }
        });
        
        // Login and go to checkout
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'customer@bellgas.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        await page.waitForTimeout(2000);
        await page.goto(`${BASE_URL}/checkout`);
        await page.waitForTimeout(3000);
        
        // Try to place order
        const placeOrderButton = page.locator('button:has-text("Place Order"), .btn-place-order');
        
        if (await placeOrderButton.count() > 0) {
            // Listen for network responses
            page.on('response', response => {
                if (response.status() === 422) {
                    console.log('422 Error on:', response.url());
                    response.json().then(data => console.log('422 Response:', data));
                }
            });
            
            await placeOrderButton.first().click();
            await page.waitForTimeout(3000);
        }
        
        await page.screenshot({ path: '/d/sopek/bellgas-laravel/test-results/checkout-issues.png' });
    });

    test('Issue 4: Address Addition 422 Error', async ({ page, request }) => {
        console.log('Testing address addition 422 error...');
        
        // Login and go to addresses page
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'customer@bellgas.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        await page.waitForTimeout(2000);
        await page.goto(`${BASE_URL}/addresses`);
        await page.waitForTimeout(3000);
        
        // Look for add address form
        const addAddressButton = page.locator('button:has-text("Add Address"), .btn-add-address');
        
        if (await addAddressButton.count() > 0) {
            await addAddressButton.first().click();
            await page.waitForTimeout(1000);
            
            // Fill address form if present
            const addressForm = page.locator('form, .address-form');
            if (await addressForm.count() > 0) {
                // Listen for 422 errors
                page.on('response', response => {
                    if (response.status() === 422) {
                        console.log('Address 422 Error on:', response.url());
                        response.json().then(data => console.log('Address 422 Response:', data));
                    }
                });
                
                // Fill form with test data
                await page.fill('input[name="title"], input[placeholder*="title"]', 'Home');
                await page.fill('input[name="address_line_1"], input[placeholder*="address"]', '123 Test Street');
                await page.fill('input[name="city"], input[placeholder*="city"]', 'Test City');
                await page.fill('input[name="state"], input[placeholder*="state"]', 'Test State');
                await page.fill('input[name="postal_code"], input[placeholder*="postal"]', '12345');
                await page.fill('input[name="country"], input[placeholder*="country"]', 'Australia');
                
                // Submit form
                const submitButton = page.locator('button[type="submit"], .btn-save');
                if (await submitButton.count() > 0) {
                    await submitButton.first().click();
                    await page.waitForTimeout(3000);
                }
            }
        }
        
        await page.screenshot({ path: '/d/sopek/bellgas-laravel/test-results/address-issues.png' });
    });

    test('Issue 5: Login/Register Redirect Problems', async ({ page }) => {
        console.log('Testing login/register redirect issues...');
        
        // Test registration redirect
        await page.goto(`${BASE_URL}/register`);
        
        // Fill registration form
        await page.fill('input[name="first_name"]', 'Test');
        await page.fill('input[name="last_name"]', 'User');
        await page.fill('input[name="email"]', `test${Date.now()}@test.com`);
        await page.fill('input[name="phone_number"]', '1234567890');
        await page.fill('input[name="password"]', 'password');
        await page.fill('input[name="password_confirmation"]', 'password');
        
        // Listen for redirects
        page.on('response', response => {
            console.log('Registration response:', response.url(), response.status());
        });
        
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        
        console.log('After registration URL:', page.url());
        
        // Test login redirect
        await page.goto(`${BASE_URL}/login`);
        await page.fill('input[name="email"]', 'customer@bellgas.com');
        await page.fill('input[name="password"]', 'password');
        
        page.on('response', response => {
            console.log('Login response:', response.url(), response.status());
        });
        
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        
        console.log('After login URL:', page.url());
        
        // Check if user is actually logged in
        const isLoggedIn = await page.locator('text=Logout, .user-menu, .logged-in').count() > 0;
        console.log('User appears logged in:', isLoggedIn);
        
        await page.screenshot({ path: '/d/sopek/bellgas-laravel/test-results/auth-redirect-issues.png' });
    });

    test('API Endpoints Direct Testing', async ({ request }) => {
        console.log('Testing API endpoints directly...');
        
        // Test cart increment
        const cartResponse = await request.put(`${BASE_URL}/api/v1/cart/1`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`,
                'Content-Type': 'application/json'
            },
            data: {
                quantity: 2
            }
        });
        console.log('Cart update status:', cartResponse.status());
        if (cartResponse.status() !== 200) {
            console.log('Cart update error:', await cartResponse.text());
        }
        
        // Test order creation
        const orderResponse = await request.post(`${BASE_URL}/api/v1/orders`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`,
                'Content-Type': 'application/json'
            },
            data: {
                delivery_address: {
                    title: 'Home',
                    address_line_1: '123 Test St',
                    city: 'Test City',
                    state: 'Test State',
                    postal_code: '12345',
                    country: 'Australia'
                },
                delivery_method: 'PICKUP',
                payment_method: 'STRIPE'
            }
        });
        console.log('Order creation status:', orderResponse.status());
        if (orderResponse.status() !== 201) {
            console.log('Order creation error:', await orderResponse.text());
        }
        
        // Test address creation
        const addressResponse = await request.post(`${BASE_URL}/api/v1/addresses`, {
            headers: {
                'Authorization': `Bearer ${customerToken}`,
                'Content-Type': 'application/json'
            },
            data: {
                title: 'Test Address',
                address_line_1: '123 Test Street',
                city: 'Test City',
                state: 'Test State',
                postal_code: '12345',
                country: 'Australia',
                is_default: false
            }
        });
        console.log('Address creation status:', addressResponse.status());
        if (addressResponse.status() !== 201) {
            console.log('Address creation error:', await addressResponse.text());
        }
    });
});