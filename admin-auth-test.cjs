const { chromium } = require('playwright');

async function testAdminAuth() {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        console.log('1. Navigating to login page...');
        await page.goto('http://127.0.0.1:8000/login');
        await page.waitForSelector('input[name="email"]', { timeout: 10000 });

        console.log('2. Logging in as admin...');
        await page.fill('input[name="email"]', 'admin@bellgas.com.au');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for redirect after login
        await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
        console.log('✓ Successfully redirected to admin dashboard');

        console.log('3. Testing admin dashboard features...');
        
        // Check if we can access admin features without being redirected back to login
        const dashboardTitle = await page.textContent('h1, h2, .page-title, [class*="title"]', { timeout: 5000 }).catch(() => null);
        console.log('Dashboard title:', dashboardTitle);

        // Test navigation to product management
        console.log('4. Testing product management access...');
        const productLink = await page.$('a[href*="product"], a:has-text("Product")');
        if (productLink) {
            await productLink.click();
            await page.waitForTimeout(2000);
            const currentUrl = page.url();
            console.log('Current URL after clicking product link:', currentUrl);
            
            if (currentUrl.includes('login')) {
                console.log('❌ FAILED: Redirected back to login when accessing product management');
            } else {
                console.log('✓ SUCCESS: Can access product management without re-login');
            }
        } else {
            console.log('No product management link found');
        }

        console.log('5. Testing API access from admin dashboard...');
        
        // Check if JWT token is available in localStorage
        const token = await page.evaluate(() => localStorage.getItem('access_token'));
        console.log('JWT token in localStorage:', token ? 'Present' : 'Not found');

        // Check if user data is available
        const userData = await page.evaluate(() => localStorage.getItem('user_data'));
        console.log('User data in localStorage:', userData ? 'Present' : 'Not found');

        console.log('\n✓ Admin authentication test completed successfully');

    } catch (error) {
        console.log('❌ Test failed:', error.message);
    } finally {
        await browser.close();
    }
}

testAdminAuth();