const { chromium } = require('playwright');

async function testAdminLogin() {
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 500  // Add delay to see what's happening
    });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        console.log('1. Opening login page...');
        await page.goto('http://127.0.0.1:8001/login', { waitUntil: 'networkidle' });
        
        // Take screenshot to see what we have
        await page.screenshot({ path: 'login-page.png' });
        console.log('Login page screenshot saved');

        console.log('2. Filling login form...');
        await page.fill('input[name="email"]', 'admin@bellgas.com.au');
        await page.fill('input[name="password"]', 'password');
        
        console.log('3. Submitting login...');
        
        // Wait for navigation after clicking submit
        const [response] = await Promise.all([
            page.waitForResponse(response => response.url().includes('login') && response.request().method() === 'POST'),
            page.click('button[type="submit"]')
        ]);
        
        console.log('Login response status:', response.status());
        console.log('Current URL after login:', page.url());
        
        // Wait a bit for any redirects
        await page.waitForTimeout(3000);
        console.log('Final URL:', page.url());
        
        // Take screenshot of final page
        await page.screenshot({ path: 'after-login.png' });
        console.log('After login screenshot saved');
        
        // Check if we're on admin dashboard
        if (page.url().includes('/admin/dashboard')) {
            console.log('✅ SUCCESS: Redirected to admin dashboard');
            
            // Try clicking on a admin feature
            console.log('4. Testing admin feature access...');
            const adminLinks = await page.$$('a[href*="admin/products"], a[href*="admin/orders"], a[href*="admin/customers"]');
            if (adminLinks.length > 0) {
                console.log(`Found ${adminLinks.length} admin links`);
                const link = adminLinks[0];
                const href = await link.getAttribute('href');
                console.log('Clicking on:', href);
                
                await link.click();
                await page.waitForTimeout(3000);
                
                console.log('URL after clicking admin feature:', page.url());
                if (page.url().includes('/login')) {
                    console.log('❌ PROBLEM: Redirected back to login when accessing admin feature');
                } else {
                    console.log('✅ SUCCESS: Admin feature accessible without re-login');
                }
            } else {
                console.log('No admin links found on dashboard');
            }
        } else if (page.url().includes('/login')) {
            console.log('❌ PROBLEM: Still on login page after submission');
            
            // Check for error messages
            const errorMsg = await page.textContent('.error, .alert-danger, [class*="error"]').catch(() => 'No error message found');
            console.log('Error message:', errorMsg);
        } else {
            console.log('❌ UNEXPECTED: Redirected to:', page.url());
        }

    } catch (error) {
        console.log('❌ Error during test:', error.message);
        await page.screenshot({ path: 'error-screenshot.png' });
    } finally {
        await browser.close();
    }
}

testAdminLogin();