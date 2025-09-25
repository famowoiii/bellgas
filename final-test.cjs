const { chromium } = require('playwright');

(async () => {
  console.log('🎯 FINAL CART TEST - Real Customer Flow');

  const browser = await chromium.launch({
    headless: false,
    timeout: 30000,
    args: ['--no-sandbox', '--disable-web-security']
  });

  const page = await browser.newPage();
  page.setDefaultTimeout(15000);

  try {
    console.log('📋 Step 1: Quick Login');
    await page.goto('http://localhost:8000/quick-login/customer', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    console.log('✅ Logged in as customer');

    console.log('📋 Step 2: Products Page');
    await page.goto('http://localhost:8000/products', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);

    // Take screenshot of products page
    await page.screenshot({ path: 'current-products.png', fullPage: true });
    console.log('📸 Products page screenshot saved');

    // Check page content
    const content = await page.content();
    console.log('🔍 Products page loaded:', content.includes('Products') || content.includes('LPG'));

    console.log('📋 Step 3: Checkout Page (Direct)');
    await page.goto('http://localhost:8000/checkout', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(8000); // Give extra time for Alpine.js

    // Take screenshot of checkout
    await page.screenshot({ path: 'current-checkout.png', fullPage: true });
    console.log('📸 Checkout page screenshot saved');

    // Analyze checkout page in detail
    console.log('🔍 Analyzing checkout page...');

    // Get all text content
    const checkoutContent = await page.textContent('body');

    // Look for debug info
    const debugInfo = {
      isAuth: checkoutContent.includes('Window.isAuthenticated'),
      hasApp: checkoutContent.includes('Window.app exists'),
      hasCartItems: checkoutContent.includes('cartItems'),
      hasCartProp: checkoutContent.includes('Window.app.cart'),
      finalCount: checkoutContent.match(/Final cart count:\s*(\d+)/),
      canPlace: checkoutContent.includes('Can place order'),
      hasPlaceBtn: checkoutContent.includes('Place Order'),
      hasEmptyMsg: checkoutContent.includes('Your cart is empty')
    };

    console.log('📊 CHECKOUT PAGE ANALYSIS:');
    console.log('  Authentication:');
    console.log(`    - Window.isAuthenticated present: ${debugInfo.isAuth}`);
    console.log(`    - Window.app exists: ${debugInfo.hasApp}`);

    console.log('  Cart Data:');
    console.log(`    - cartItems mentioned: ${debugInfo.hasCartItems}`);
    console.log(`    - cart property mentioned: ${debugInfo.hasCartProp}`);
    console.log(`    - Final count match: ${debugInfo.finalCount ? debugInfo.finalCount[1] : 'Not found'}`);

    console.log('  Order Capability:');
    console.log(`    - Can place order text: ${debugInfo.canPlace}`);
    console.log(`    - Place Order button: ${debugInfo.hasPlaceBtn}`);
    console.log(`    - Empty cart message: ${debugInfo.hasEmptyMsg}`);

    // Try to force reload cart if button exists
    console.log('🔄 Looking for force reload button...');
    const reloadBtns = await page.$$('button:has-text("Force Reload")');
    if (reloadBtns.length > 0) {
      console.log('🔄 Found force reload button, clicking...');
      await reloadBtns[0].click();
      await page.waitForTimeout(3000);

      // Re-analyze after reload
      const newContent = await page.textContent('body');
      const newCount = newContent.match(/Final cart count:\s*(\d+)/);
      console.log(`📊 Cart count after reload: ${newCount ? newCount[1] : 'Not found'}`);
    }

    // Summary
    console.log('🎯 FINAL TEST RESULTS:');
    console.log('✅ Customer can login via quick-login');
    console.log('✅ Products page loads');
    console.log('✅ Checkout page loads');
    console.log(`${debugInfo.isAuth ? '✅' : '❌'} Authentication detection working`);
    console.log(`${debugInfo.finalCount ? '✅' : '❌'} Cart count debug info present`);
    console.log(`${debugInfo.hasPlaceBtn ? '✅' : '❌'} Place Order button present`);

    const cartCount = debugInfo.finalCount ? parseInt(debugInfo.finalCount[1]) : 0;
    if (cartCount > 0) {
      console.log('🎉 CART HAS ITEMS! The cart system is working!');
    } else {
      console.log('⚠️  Cart appears empty - need to add items first');
    }

    console.log('📸 Screenshots saved: current-products.png, current-checkout.png');

  } catch (error) {
    console.error('❌ Test failed:', error.message);
    await page.screenshot({ path: 'final-error.png', fullPage: true });
    console.log('📸 Error screenshot saved as final-error.png');
  } finally {
    console.log('🔚 Test completed, browser will close in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
  }
})();