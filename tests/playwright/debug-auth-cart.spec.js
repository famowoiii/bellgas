import { test, expect } from '@playwright/test';

test.describe('Debug Authentication & Cart Issues', () => {
    let authToken;
    let productVariantId;

    test('Test complete flow - Login + Add to Cart + My Orders', async ({ request, page }) => {
        console.log('\n=== TESTING AUTHENTICATION & CART FUNCTIONALITY ===\n');

        // Step 1: Test API Health Check
        console.log('1. Testing API Health Check...');
        const healthResponse = await request.get('http://localhost:8000/api/health');
        expect(healthResponse.ok()).toBeTruthy();
        const healthData = await healthResponse.json();
        console.log('✅ API Health:', healthData.message);

        // Step 2: Test User Registration/Login
        console.log('\n2. Testing User Authentication...');
        
        // Try login with demo credentials first
        const loginResponse = await request.post('http://localhost:8000/api/auth/login', {
            data: {
                email: 'stripetester@bellgas.com',
                password: 'password123'
            }
        });

        console.log('Login Response Status:', loginResponse.status());
        const loginText = await loginResponse.text();
        console.log('Login Response Body:', loginText);

        let loginData;
        try {
            loginData = JSON.parse(loginText);
        } catch (e) {
            console.log('❌ Login response is not valid JSON');
            throw new Error('Invalid JSON response from login endpoint');
        }

        if (loginResponse.ok() && loginData.success) {
            authToken = loginData.data.access_token;
            console.log('✅ Login successful, got token:', authToken?.substring(0, 20) + '...');
        } else {
            console.log('❌ Login failed:', loginData.message || 'Unknown error');
            console.log('Trying to register user first...');
            
            // Try to register user
            const registerResponse = await request.post('http://localhost:8000/api/auth/register', {
                data: {
                    name: 'Test User',
                    email: 'stripetester@bellgas.com',
                    password: 'password123',
                    password_confirmation: 'password123'
                }
            });

            const registerData = await registerResponse.json();
            console.log('Register Response:', registerData);

            if (registerResponse.ok() && registerData.success) {
                authToken = registerData.data.access_token;
                console.log('✅ Registration successful, got token');
            } else {
                throw new Error('Both login and registration failed');
            }
        }

        // Step 3: Test Get Products for Cart
        console.log('\n3. Testing Products API...');
        const productsResponse = await request.get('http://localhost:8000/api/products');
        expect(productsResponse.ok()).toBeTruthy();
        const productsData = await productsResponse.json();
        console.log('✅ Products loaded:', productsData.data.length, 'products');
        
        // Get first product variant ID
        if (productsData.data.length > 0) {
            const firstProduct = productsData.data[0];
            console.log('First product:', firstProduct.name);
            
            // Get product variants
            const variantResponse = await request.get(`http://localhost:8000/api/products/${firstProduct.id}`);
            const variantData = await variantResponse.json();
            
            if (variantData.data.variants && variantData.data.variants.length > 0) {
                productVariantId = variantData.data.variants[0].id;
                console.log('✅ Product variant ID:', productVariantId);
            }
        }

        // Step 4: Test Add to Cart (with authentication)
        console.log('\n4. Testing Add to Cart with authentication...');
        if (productVariantId && authToken) {
            const addToCartResponse = await request.post('http://localhost:8000/api/cart', {
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                data: {
                    product_variant_id: productVariantId,
                    quantity: 1,
                    is_preorder: false,
                    notes: 'Test cart item from Playwright'
                }
            });

            console.log('Add to Cart Status:', addToCartResponse.status());
            const cartText = await addToCartResponse.text();
            console.log('Add to Cart Response:', cartText);

            if (addToCartResponse.ok()) {
                const cartData = JSON.parse(cartText);
                if (cartData.success) {
                    console.log('✅ Add to Cart successful!');
                    console.log('Cart item:', cartData.data.quantity, 'x', cartData.data.productVariant?.product?.name);
                } else {
                    console.log('❌ Add to Cart failed:', cartData.message);
                }
            } else {
                console.log('❌ Add to Cart HTTP error:', addToCartResponse.status());
            }
        }

        // Step 5: Test Get Cart
        console.log('\n5. Testing Get Cart...');
        if (authToken) {
            const getCartResponse = await request.get('http://localhost:8000/api/cart', {
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            });

            console.log('Get Cart Status:', getCartResponse.status());
            const getCartData = await getCartResponse.json();
            console.log('Cart contents:', getCartData);

            if (getCartResponse.ok() && getCartData.success) {
                console.log('✅ Cart retrieved successfully!');
                console.log('Cart items count:', getCartData.data.count);
                console.log('Cart total:', getCartData.data.total);
            }
        }

        // Step 6: Test My Orders
        console.log('\n6. Testing My Orders...');
        if (authToken) {
            const ordersResponse = await request.get('http://localhost:8000/api/orders', {
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Accept': 'application/json'
                }
            });

            console.log('Orders Status:', ordersResponse.status());
            const ordersData = await ordersResponse.json();
            console.log('Orders Response:', ordersData);

            if (ordersResponse.ok() && ordersData.success) {
                console.log('✅ Orders retrieved successfully!');
                console.log('Total orders:', ordersData.data.data?.length || 0);
            } else {
                console.log('❌ Orders failed:', ordersData.message);
            }
        }

        // Step 7: Test frontend login
        console.log('\n7. Testing frontend login page...');
        await page.goto('http://localhost:8000/login');
        await page.waitForTimeout(2000);

        // Try to login via frontend
        await page.fill('input[name="email"]', 'stripetester@bellgas.com');
        await page.fill('input[name="password"]', 'password123');
        
        // Take screenshot before clicking
        await page.screenshot({ path: 'debug-test/login-form-filled.png', fullPage: true });
        
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);

        // Check for any errors
        const currentUrl = page.url();
        console.log('Current URL after login:', currentUrl);
        
        // Take screenshot after login attempt
        await page.screenshot({ path: 'debug-test/after-login-attempt.png', fullPage: true });

        // Check console errors
        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.log('❌ Frontend Console Error:', msg.text());
            }
        });

        console.log('\n=== DEBUG TEST COMPLETED ===');
    });
});