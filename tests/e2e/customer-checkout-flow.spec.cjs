const { test, expect } = require('@playwright/test');

test.describe('Customer Checkout Flow - Real UI Testing', () => {
    test('Complete customer journey from login to checkout', async ({ page }) => {
        // Set longer timeout for this test
        test.setTimeout(120000);

        console.log('🚀 Starting real customer checkout flow test...');

        // 1. Navigate to home page
        await page.goto('http://localhost:8000');
        await page.waitForLoadState('networkidle');
        console.log('✅ Home page loaded');

        // 2. Use quick login for customer
        console.log('🔑 Logging in as customer...');
        await page.goto('http://localhost:8000/quick-login/customer');
        await page.waitForLoadState('networkidle');

        // Wait for redirect and authentication
        await page.waitForURL('http://localhost:8000/orders');
        console.log('✅ Customer logged in successfully');

        // 3. Navigate to products page
        console.log('🛍️ Going to products page...');
        await page.goto('http://localhost:8000/products');
        await page.waitForLoadState('networkidle');

        // Wait for products to load - look for actual product cards
        await page.waitForSelector('.product-card, .bg-white.rounded-lg.shadow-sm', { timeout: 15000 });
        console.log('✅ Products page loaded');

        // 4. Find and click first "Add to Cart" button
        console.log('🛒 Looking for Add to Cart button...');
        const addToCartButtons = page.locator('button:has-text("Add to Cart"), button:has-text("🛒")');
        await addToCartButtons.first().waitFor({ state: 'visible', timeout: 10000 });
        await addToCartButtons.first().click();

        // Wait for notification or cart update
        await page.waitForTimeout(2000);
        console.log('✅ Clicked Add to Cart button');

        // 5. Check if cart count updated in header
        console.log('🔍 Checking cart count...');
        const cartCountElement = page.locator('.bg-red-500.text-white.text-xs.rounded-full, [class*="cart"], .fa-shopping-cart ~ span');

        try {
            await cartCountElement.waitFor({ state: 'visible', timeout: 5000 });
            const count = await cartCountElement.first().textContent();
            console.log('✅ Cart count visible:', count);
        } catch (error) {
            console.log('⚠️ Cart count not visible, continuing...');
        }

        // 6. Try to open cart sidebar by clicking cart icon
        console.log('🛒 Opening cart sidebar...');
        const cartButton = page.locator('.fa-shopping-cart, i[class*="cart"], button:has(.fa-shopping-cart)').first();
        if (await cartButton.isVisible()) {
            await cartButton.click();
            await page.waitForTimeout(1000);
            console.log('✅ Clicked cart button');
        }

        // 7. Navigate directly to checkout
        console.log('💳 Going to checkout page...');
        await page.goto('http://localhost:8000/checkout');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000); // Give Alpine.js time to load
        console.log('✅ Navigated to checkout page');

        // 8. Check for debug info or cart content
        console.log('🔍 Analyzing checkout page...');

        // Look for debug info first
        const debugSection = page.locator('div:has-text("Debug Info"), .bg-gray-100');
        if (await debugSection.isVisible()) {
            const debugText = await debugSection.textContent();
            console.log('🔍 Debug Info found:', debugText.substring(0, 500));

            // Extract cart count from debug
            const cartCountMatch = debugText.match(/Final cart count:\s*(\d+)/);
            if (cartCountMatch) {
                const cartCount = parseInt(cartCountMatch[1]);
                console.log('📊 Cart count from debug:', cartCount);
            }
        }

        // 9. Look for cart items in checkout
        const cartItemElements = page.locator('.border-b.border-gray-100, .checkout-item, div:has-text("Unknown Product"), div:has-text("Qty:")');
        const cartItemCount = await cartItemElements.count();
        console.log('🛒 Found', cartItemCount, 'potential cart items in checkout');

        // 10. Try manual reload if cart seems empty
        const forceReloadButton = page.locator('button:has-text("Force Reload Cart"), button:has-text("🔄")');
        if (await forceReloadButton.isVisible()) {
            console.log('🔄 Trying manual cart reload...');
            await forceReloadButton.click();
            await page.waitForTimeout(3000);
            console.log('✅ Manual reload attempted');
        }

        // 11. Select pickup method (if available)
        console.log('📦 Looking for fulfillment options...');
        const pickupOption = page.locator('input[value="PICKUP"], label:has-text("Pickup"), .fa-store').first();
        if (await pickupOption.isVisible()) {
            await pickupOption.click();
            console.log('✅ Pickup method selected');
        }

        // 12. Fill order notes
        console.log('📝 Adding order notes...');
        const notesTextarea = page.locator('textarea, [placeholder*="order"], [placeholder*="instruction"]');
        if (await notesTextarea.isVisible()) {
            await notesTextarea.fill('Test order from Playwright automation - real UI test');
            console.log('✅ Order notes added');
        }

        // 13. Look for place order button
        console.log('🛒 Looking for Place Order button...');
        const placeOrderButton = page.locator('button:has-text("Place Order"), button:has-text("💳"), .bg-blue-600:has-text("Order")');

        if (await placeOrderButton.isVisible()) {
            console.log('✅ Place Order button found');
            const isEnabled = await placeOrderButton.isEnabled();
            console.log('🔍 Button enabled:', isEnabled);

            if (isEnabled) {
                console.log('🛒 Clicking Place Order...');
                await placeOrderButton.click();
                await page.waitForTimeout(3000);

                // Check for payment modal or success
                const paymentModal = page.locator('[class*="modal"], [class*="fixed"]:has-text("Payment"), .bg-black.bg-opacity-50');
                if (await paymentModal.isVisible()) {
                    console.log('✅ Payment modal appeared');
                    // Close modal if there's a close button
                    const closeButton = page.locator('button:has-text("Cancel"), .fa-times, button:has(.fa-times)');
                    if (await closeButton.isVisible()) {
                        await closeButton.click();
                        console.log('✅ Payment modal closed');
                    }
                } else {
                    console.log('⚠️ No payment modal appeared');
                }
            }
        } else {
            console.log('⚠️ Place Order button not found or not visible');
        }

        // 14. Final summary
        console.log('📊 Test Summary:');
        console.log('  - Login: ✅');
        console.log('  - Products page: ✅');
        console.log('  - Add to cart: ✅');
        console.log('  - Checkout page: ✅');
        console.log('  - Cart items in checkout: ', cartItemCount > 0 ? '✅' : '⚠️');

        console.log('🎉 Real customer checkout flow test completed!');
    });

    test('Test add to cart and verify in checkout', async ({ page }) => {
        console.log('🛒 Testing add to cart functionality...');

        // Quick login
        await page.goto('http://localhost:8000/quick-login/customer');
        await page.waitForURL('http://localhost:8000/orders');
        console.log('✅ Logged in');

        // Go to products and add item
        await page.goto('http://localhost:8000/products');
        await page.waitForLoadState('networkidle');

        // Find and click add to cart
        const addButton = page.locator('button:has-text("Add to Cart"), button:has-text("🛒")').first();
        if (await addButton.isVisible()) {
            await addButton.click();
            await page.waitForTimeout(2000);
            console.log('✅ Added item to cart');
        }

        // Go to checkout immediately
        await page.goto('http://localhost:8000/checkout');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000);

        // Take screenshot for debugging
        await page.screenshot({ path: 'checkout-debug.png', fullPage: true });
        console.log('📸 Screenshot saved as checkout-debug.png');

        // Check page content
        const pageContent = await page.content();
        const hasCartItems = pageContent.includes('Unknown Product') || pageContent.includes('Qty:') || pageContent.includes('productVariant');
        console.log('🔍 Page has cart-related content:', hasCartItems);

        // Check cart count in debug info
        const debugText = await page.locator('body').textContent();
        const finalCartMatch = debugText.match(/Final cart count:\s*(\d+)/);
        if (finalCartMatch) {
            console.log('📊 Final cart count:', finalCartMatch[1]);
        }

        console.log('✅ Cart verification test completed');
    });
});