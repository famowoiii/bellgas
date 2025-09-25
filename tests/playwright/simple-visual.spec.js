import { test, expect } from '@playwright/test';

test.use({ 
  video: 'on',
  screenshot: 'on'
});

test('🎬 BellGas Visual Demo - Quick Tour', async ({ page }) => {
  console.log('🎬 Starting BellGas Laravel Visual Demo...');
  
  // 1. Home Page
  console.log('📱 1/4 Loading Home Page...');
  await page.goto('http://localhost:8000');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: 'demo-screenshots/demo-01-home.png', fullPage: true });
  console.log('📸 Home page captured');
  
  // 2. Products Page
  console.log('🛍️ 2/4 Loading Products Page...');
  await page.goto('http://localhost:8000/products');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'demo-screenshots/demo-02-products.png', fullPage: true });
  console.log('📸 Products page captured');
  
  // 3. Login Page
  console.log('🔐 3/4 Loading Login Page...');
  await page.goto('http://localhost:8000/login');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'demo-screenshots/demo-03-login.png', fullPage: true });
  console.log('📸 Login page captured');
  
  // 4. Register Page with Form Interaction
  console.log('📝 4/4 Loading Register Page and Testing Form...');
  await page.goto('http://localhost:8000/register');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'demo-screenshots/demo-04-register.png', fullPage: true });
  
  // Try to fill a form if it exists
  const nameInput = page.locator('input[name="name"], input[placeholder*="name" i]').first();
  if (await nameInput.count() > 0) {
    console.log('📝 Found form, filling demo data...');
    await nameInput.fill('Demo User');
    await page.waitForTimeout(1000);
    
    const emailInput = page.locator('input[name="email"], input[type="email"]').first();
    if (await emailInput.count() > 0) {
      await emailInput.fill('demo@example.com');
      await page.waitForTimeout(1000);
    }
    
    await page.screenshot({ path: 'demo-screenshots/demo-05-form-filled.png', fullPage: true });
    console.log('📸 Form interaction captured');
  }
  
  // 5. Test responsive design
  console.log('📱 Testing Mobile View...');
  await page.setViewportSize({ width: 375, height: 667 });
  await page.goto('http://localhost:8000');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  await page.screenshot({ path: 'demo-screenshots/demo-06-mobile.png', fullPage: true });
  console.log('📸 Mobile view captured');
  
  console.log('✅ Visual Demo Completed Successfully!');
  console.log('📁 Screenshots saved in demo-screenshots/ folder');
  console.log('🎥 Video recording saved in test-results/ folder');
});