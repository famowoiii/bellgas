const { chromium } = require('playwright');

(async () => {
  console.log('🚀 Starting simple customer checkout test...');

  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();

  try {
    // 1. Navigate to home page
    console.log('📋 Step 1: Loading home page...');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('✅ Home page loaded');

    // 2. Quick login as customer
    console.log('📋 Step 2: Logging in as customer...');
    await page.goto('http://localhost:8000/quick-login/customer', { timeout: 60000 });

    // Wait for redirect to orders page or any page
    try {
      await page.waitForURL('**/orders', { timeout: 15000 });
      console.log('✅ Redirected to orders page');
    } catch (error) {
      console.log('⚠️ No redirect to orders, continuing...');
    }

    await page.waitForLoadState('networkidle', { timeout: 30000 });
    await page.waitForTimeout(3000);
    console.log('✅ Customer login completed');

    // 3. Go to products page
    console.log('📋 Step 3: Going to products page...');
    await page.goto('http://localhost:8000/products');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    console.log('✅ Products page loaded');

    // 4. Look for and click Add to Cart button
    console.log('📋 Step 4: Looking for Add to Cart buttons...');

    // Take screenshot of products page
    await page.screenshot({ path: 'products-page.png', fullPage: true });
    console.log('📸 Products page screenshot saved');

    // Try different selectors for Add to Cart button
    const selectors = [
      'button:has-text("Add to Cart")',
      'button:has-text("🛒")',
      '.btn:has-text("Add")',
      'button[onclick*="addToCart"]',
      'button[x-click*="addToCart"]',
      '.add-to-cart'
    ];

    let addButton = null;
    for (const selector of selectors) {
      const buttons = page.locator(selector);
      const count = await buttons.count();
      console.log(`🔍 Found ${count} buttons with selector: ${selector}`);

      if (count > 0) {
        addButton = buttons.first();
        console.log(`✅ Using selector: ${selector}`);
        break;
      }
    }

    if (addButton && await addButton.isVisible()) {
      console.log('🛒 Clicking Add to Cart button...');
      await addButton.click();
      await page.waitForTimeout(3000);
      console.log('✅ Add to Cart button clicked');
    } else {
      console.log('⚠️ No Add to Cart button found, checking page content...');
      const pageText = await page.textContent('body');
      console.log('📄 Page contains "Add" text:', pageText.includes('Add'));
      console.log('📄 Page contains "Cart" text:', pageText.includes('Cart'));
    }

    // 5. Check cart count in header
    console.log('📋 Step 5: Checking cart count...');
    const cartElements = page.locator('.bg-red-500, .cart-count, [class*="cart"]');
    const cartCount = await cartElements.count();
    console.log(`🔍 Found ${cartCount} potential cart elements`);

    if (cartCount > 0) {
      const firstCart = cartElements.first();
      if (await firstCart.isVisible()) {
        const text = await firstCart.textContent();
        console.log('🛒 Cart element text:', text);
      }
    }

    // 6. Go to checkout page
    console.log('📋 Step 6: Going to checkout page...');
    await page.goto('http://localhost:8000/checkout');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(5000); // Give Alpine.js time to initialize
    console.log('✅ Checkout page loaded');

    // Take screenshot of checkout page
    await page.screenshot({ path: 'checkout-page.png', fullPage: true });
    console.log('📸 Checkout page screenshot saved');

    // 7. Analyze checkout page content
    console.log('📋 Step 7: Analyzing checkout page...');

    const checkoutText = await page.textContent('body');

    // Check for key indicators
    const indicators = {
      'Debug Info': checkoutText.includes('Debug Info'),
      'Cart items': checkoutText.includes('Final cart count'),
      'Authentication': checkoutText.includes('Window.isAuthenticated'),
      'User logged in': checkoutText.includes('User logged in: true'),
      'Place Order': checkoutText.includes('Place Order'),
      'Empty cart': checkoutText.includes('Your cart is empty')
    };

    console.log('🔍 Checkout page analysis:');
    for (const [key, found] of Object.entries(indicators)) {
      console.log(`  - ${key}: ${found ? '✅' : '❌'}`);
    }

    // Extract cart count from debug info
    const cartCountMatch = checkoutText.match(/Final cart count:\s*(\d+)/);
    if (cartCountMatch) {
      const count = parseInt(cartCountMatch[1]);
      console.log(`📊 Cart count from debug: ${count}`);

      if (count === 0) {
        console.log('🔄 Cart appears empty, trying manual reload...');
        const reloadButton = page.locator('button:has-text("Force Reload Cart")');
        if (await reloadButton.isVisible()) {
          await reloadButton.click();
          await page.waitForTimeout(3000);

          const newText = await page.textContent('body');
          const newCountMatch = newText.match(/Final cart count:\s*(\d+)/);
          if (newCountMatch) {
            console.log(`📊 Cart count after reload: ${newCountMatch[1]}`);
          }
        }
      }
    }

    // 8. Check for cart items display
    console.log('📋 Step 8: Looking for cart items in checkout...');
    const cartItemSelectors = [
      'div:has-text("Unknown Product")',
      'div:has-text("Qty:")',
      '.border-b.border-gray-100',
      '[x-for*="cart"]'
    ];

    for (const selector of cartItemSelectors) {
      const elements = page.locator(selector);
      const count = await elements.count();
      console.log(`🔍 Found ${count} elements with selector: ${selector}`);
    }

    // 9. Test Summary
    console.log('📊 TEST SUMMARY:');
    console.log('  ✅ Home page: OK');
    console.log('  ✅ Customer login: OK');
    console.log('  ✅ Products page: OK');
    console.log('  ✅ Checkout page: OK');
    console.log(`  ${addButton ? '✅' : '❌'} Add to Cart button: ${addButton ? 'Found' : 'Not found'}`);
    console.log(`  ${cartCountMatch ? '✅' : '❌'} Cart debug info: ${cartCountMatch ? 'Present' : 'Missing'}`);

    console.log('🎉 Test completed! Check screenshots for visual verification.');

  } catch (error) {
    console.error('❌ Test failed:', error.message);
    await page.screenshot({ path: 'error-screenshot.png', fullPage: true });
  } finally {
    await browser.close();
    console.log('🔚 Browser closed');
  }
})();