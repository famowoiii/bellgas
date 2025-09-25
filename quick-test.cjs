const { chromium } = require('playwright');

(async () => {
  console.log('🚀 Quick customer checkout test...');

  const browser = await chromium.launch({
    headless: false,
    timeout: 10000
  });
  const page = await browser.newPage();
  page.setDefaultTimeout(10000);

  try {
    // Step 1: Login
    console.log('🔑 Logging in...');
    await page.goto('http://localhost:8000/quick-login/customer');
    await page.waitForTimeout(3000);
    console.log('✅ Login attempted');

    // Step 2: Products page
    console.log('🛍️ Going to products...');
    await page.goto('http://localhost:8000/products');
    await page.waitForTimeout(2000);
    console.log('✅ Products page loaded');

    // Quick screenshot
    await page.screenshot({ path: 'products.png' });

    // Step 3: Try to add to cart
    console.log('🛒 Looking for Add to Cart...');
    const buttons = await page.$$('button');
    console.log(`Found ${buttons.length} buttons`);

    // Click first button that might be Add to Cart
    for (let i = 0; i < Math.min(buttons.length, 5); i++) {
      try {
        const text = await buttons[i].textContent();
        console.log(`Button ${i}: "${text}"`);

        if (text && (text.includes('Add') || text.includes('🛒'))) {
          console.log(`🛒 Clicking button: ${text}`);
          await buttons[i].click();
          await page.waitForTimeout(2000);
          break;
        }
      } catch (e) {
        // Skip this button
      }
    }

    // Step 4: Checkout page
    console.log('💳 Going to checkout...');
    await page.goto('http://localhost:8000/checkout');
    await page.waitForTimeout(3000);
    console.log('✅ Checkout page loaded');

    // Take screenshot
    await page.screenshot({ path: 'checkout.png', fullPage: true });

    // Quick analysis
    const text = await page.textContent('body');
    const indicators = {
      'Authenticated': text.includes('true'),
      'Cart count': /Final cart count:\s*(\d+)/.test(text),
      'Place Order': text.includes('Place Order'),
      'Debug Info': text.includes('Debug Info')
    };

    console.log('📊 Checkout Analysis:');
    Object.entries(indicators).forEach(([key, found]) => {
      console.log(`  ${key}: ${found ? '✅' : '❌'}`);
    });

    // Extract cart count
    const match = text.match(/Final cart count:\s*(\d+)/);
    if (match) {
      console.log(`📊 Cart has ${match[1]} items`);
    }

    console.log('🎉 Quick test completed!');
    console.log('📸 Screenshots saved: products.png, checkout.png');

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'error.png' });
  } finally {
    await browser.close();
  }
})();