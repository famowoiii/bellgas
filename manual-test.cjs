const { chromium } = require('playwright');

(async () => {
  console.log('🚀 Manual step-by-step test...');
  console.log('This will open a browser where you can manually navigate and test');

  const browser = await chromium.launch({
    headless: false,
    slowMo: 1000 // Slow down actions
  });
  const page = await browser.newPage();

  // Set longer timeout for manual testing
  page.setDefaultTimeout(60000);

  console.log('📋 TEST STEPS:');
  console.log('1. Browser will open and navigate to login');
  console.log('2. You can manually add items to cart in products page');
  console.log('3. Then navigate to checkout to verify cart items');
  console.log('4. Press Ctrl+C when done to close browser');

  try {
    // Step 1: Manual login
    console.log('🔑 Opening login page...');
    await page.goto('http://localhost:8000/quick-login/customer');
    await page.waitForTimeout(5000);

    console.log('✅ Login completed - you should now be logged in');

    // Step 2: Go to products
    console.log('🛍️ Navigating to products page...');
    await page.goto('http://localhost:8000/products');
    await page.waitForTimeout(3000);

    console.log('✅ Products page loaded');
    console.log('📋 MANUAL TEST: Please add some items to cart using the UI');

    // Wait for user interaction
    console.log('⏳ Waiting 30 seconds for you to add items to cart...');
    await page.waitForTimeout(30000);

    // Step 3: Go to checkout
    console.log('💳 Now navigating to checkout page...');
    await page.goto('http://localhost:8000/checkout');
    await page.waitForTimeout(5000);

    console.log('✅ Checkout page loaded');

    // Take screenshot for analysis
    await page.screenshot({ path: 'manual-checkout.png', fullPage: true });
    console.log('📸 Checkout screenshot saved as manual-checkout.png');

    // Analyze checkout page
    console.log('🔍 Analyzing checkout page...');
    const bodyText = await page.textContent('body');

    // Extract debug info
    const debugMatches = {
      authenticated: bodyText.match(/Window\.isAuthenticated:\s*(true|false)/),
      userExists: bodyText.match(/Window\.app\.user:\s*(true|false)/),
      cartItems: bodyText.match(/Window\.app\.cartItems:\s*(true|false)/),
      finalCount: bodyText.match(/Final cart count:\s*(\d+)/),
      canPlace: bodyText.match(/Can place order:\s*(true|false)/)
    };

    console.log('📊 CHECKOUT ANALYSIS:');
    Object.entries(debugMatches).forEach(([key, match]) => {
      const value = match ? match[1] : 'Not found';
      console.log(`  ${key}: ${value}`);
    });

    // Check for cart items in UI
    const cartItemElements = await page.$$('.border-b.border-gray-100, [x-text*="productVariant"]');
    console.log(`🛒 Found ${cartItemElements.length} potential cart item elements`);

    // Check for place order button
    const placeOrderButtons = await page.$$('button:has-text("Place Order")');
    console.log(`🛒 Found ${placeOrderButtons.length} Place Order buttons`);

    if (placeOrderButtons.length > 0) {
      const isEnabled = await placeOrderButtons[0].isEnabled();
      console.log(`🛒 Place Order button enabled: ${isEnabled}`);
    }

    console.log('⏳ Keeping browser open for 60 more seconds for manual inspection...');
    await page.waitForTimeout(60000);

    console.log('🎉 Manual test completed!');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'manual-error.png' });
  } finally {
    console.log('🔚 Closing browser...');
    await browser.close();
  }
})();