const { chromium } = require('playwright');
const fs = require('fs');

async function runSimpleE2ETest() {
    console.log('🚀 Starting Simple E2E Test for BellGas Application');
    console.log('=' .repeat(60));
    
    const browser = await chromium.launch({ 
        headless: true,
        timeout: 10000
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    let results = {
        total: 0,
        passed: 0,
        failed: 0,
        tests: []
    };

    try {
        const page = await context.newPage();
        page.setDefaultTimeout(10000);
        
        // Test 1: Home Page
        await runTest('Home Page Access', async () => {
            await page.goto('http://127.0.0.1:8000/', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_homepage.png' });
            console.log('  📸 Screenshot saved: test_homepage.png');
            
            return page.url() === 'http://127.0.0.1:8000/';
        }, results);
        
        // Test 2: Login Page
        await runTest('Login Page Access', async () => {
            await page.goto('http://127.0.0.1:8000/login', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_login.png' });
            console.log('  📸 Screenshot saved: test_login.png');
            
            return page.url().includes('/login');
        }, results);
        
        // Test 3: Products Page
        await runTest('Products Page Access', async () => {
            await page.goto('http://127.0.0.1:8000/products', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_products.png' });
            console.log('  📸 Screenshot saved: test_products.png');
            
            return page.url().includes('/products');
        }, results);
        
        // Test 4: Admin Login Attempt
        await runTest('Admin Login Flow', async () => {
            await page.goto('http://127.0.0.1:8000/login', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            // Fill login form
            await page.waitForSelector('input[name="email"]', { timeout: 5000 });
            await page.fill('input[name="email"]', 'admin@bellgas.com.au');
            await page.fill('input[name="password"]', 'password');
            
            await page.screenshot({ path: 'test_admin_login_filled.png' });
            console.log('  📸 Screenshot saved: test_admin_login_filled.png');
            
            // Click demo button to fill credentials
            try {
                await page.click('button:has-text("Fill Admin")', { timeout: 2000 });
                console.log('  🔄 Used Fill Admin button');
            } catch (e) {
                console.log('  ℹ️ Fill Admin button not found, using manual input');
            }
            
            await page.screenshot({ path: 'test_admin_before_submit.png' });
            
            // Submit form
            await page.click('button[type="submit"]');
            
            // Wait for response
            await page.waitForTimeout(5000);
            
            await page.screenshot({ path: 'test_admin_after_submit.png' });
            console.log('  📸 Screenshot saved: test_admin_after_submit.png');
            
            const currentUrl = page.url();
            console.log('  🔗 URL after login:', currentUrl);
            
            // Check if redirected away from login
            return !currentUrl.includes('/login') || currentUrl.includes('/admin') || currentUrl.includes('/dashboard');
        }, results);
        
        // Test 5: Direct Admin Dashboard Access
        await runTest('Admin Dashboard Direct Access', async () => {
            await page.goto('http://127.0.0.1:8000/admin/dashboard', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_admin_dashboard.png' });
            console.log('  📸 Screenshot saved: test_admin_dashboard.png');
            
            const currentUrl = page.url();
            console.log('  🔗 Admin dashboard URL:', currentUrl);
            
            // Success if we're not redirected to login
            return !currentUrl.includes('/login');
        }, results);
        
        // Test 6: Customer Login Attempt
        await runTest('Customer Login Flow', async () => {
            await page.goto('http://127.0.0.1:8000/login', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.waitForSelector('input[name="email"]', { timeout: 5000 });
            await page.fill('input[name="email"]', 'stripetester@bellgas.com');
            await page.fill('input[name="password"]', 'password123');
            
            await page.screenshot({ path: 'test_customer_login_filled.png' });
            console.log('  📸 Screenshot saved: test_customer_login_filled.png');
            
            // Try demo button
            try {
                await page.click('button:has-text("Fill Customer")', { timeout: 2000 });
                console.log('  🔄 Used Fill Customer button');
            } catch (e) {
                console.log('  ℹ️ Fill Customer button not found, using manual input');
            }
            
            await page.click('button[type="submit"]');
            await page.waitForTimeout(5000);
            
            await page.screenshot({ path: 'test_customer_after_submit.png' });
            console.log('  📸 Screenshot saved: test_customer_after_submit.png');
            
            const currentUrl = page.url();
            console.log('  🔗 URL after customer login:', currentUrl);
            
            return !currentUrl.includes('/login') || currentUrl.includes('/dashboard');
        }, results);
        
        // Test 7: Cart Access
        await runTest('Cart Page Access', async () => {
            await page.goto('http://127.0.0.1:8000/cart', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_cart.png' });
            console.log('  📸 Screenshot saved: test_cart.png');
            
            return page.url().includes('/cart');
        }, results);
        
        // Test 8: Checkout Access
        await runTest('Checkout Page Access', async () => {
            await page.goto('http://127.0.0.1:8000/checkout', { 
                waitUntil: 'domcontentloaded',
                timeout: 10000
            });
            
            await page.screenshot({ path: 'test_checkout.png' });
            console.log('  📸 Screenshot saved: test_checkout.png');
            
            return page.url().includes('/checkout');
        }, results);
        
        await page.close();
        
    } finally {
        await browser.close();
        printResults(results);
    }
}

async function runTest(testName, testFunction, results) {
    console.log(`\n🧪 Testing: ${testName}`);
    results.total++;
    
    try {
        const success = await testFunction();
        if (success) {
            console.log(`  ✅ PASSED: ${testName}`);
            results.passed++;
            results.tests.push({ name: testName, status: 'PASSED' });
        } else {
            console.log(`  ❌ FAILED: ${testName}`);
            results.failed++;
            results.tests.push({ name: testName, status: 'FAILED' });
        }
    } catch (error) {
        console.log(`  ❌ ERROR: ${testName}`);
        console.log(`    ${error.message.substring(0, 100)}...`);
        results.failed++;
        results.tests.push({ name: testName, status: 'ERROR', error: error.message });
    }
}

function printResults(results) {
    console.log('\n' + '=' .repeat(60));
    console.log('📊 E2E TEST RESULTS');
    console.log('=' .repeat(60));
    
    results.tests.forEach(test => {
        const icon = test.status === 'PASSED' ? '✅' : '❌';
        console.log(`${icon} ${test.name}`);
    });
    
    const successRate = results.total > 0 ? ((results.passed / results.total) * 100).toFixed(1) : 0;
    
    console.log(`\n📈 SUMMARY:`);
    console.log(`  🎯 Success Rate: ${successRate}%`);
    console.log(`  ✅ Passed: ${results.passed}`);
    console.log(`  ❌ Failed: ${results.failed}`);
    console.log(`  📊 Total: ${results.total}`);
    
    // Generate report
    const reportData = {
        timestamp: new Date().toISOString(),
        summary: {
            total_tests: results.total,
            passed: results.passed,
            failed: results.failed,
            success_rate: successRate + '%'
        },
        tests: results.tests
    };
    
    fs.writeFileSync('simple-e2e-report.json', JSON.stringify(reportData, null, 2));
    console.log('\n💾 Report saved: simple-e2e-report.json');
    console.log('📸 Screenshots saved in current directory');
    
    // Test API endpoints quickly
    testAPIEndpoints();
}

async function testAPIEndpoints() {
    console.log('\n🔌 API ENDPOINT TESTS');
    console.log('-' .repeat(40));
    
    try {
        const fetch = require('node-fetch');
        const baseUrl = 'http://127.0.0.1:8000';
        
        const endpoints = [
            { name: 'API Health', url: '/up' },
            { name: 'API Products', url: '/api/products' },
            { name: 'API Categories', url: '/api/products/categories' }
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`\n🔍 Testing: ${endpoint.name}`);
                const response = await fetch(`${baseUrl}${endpoint.url}`, { timeout: 5000 });
                const status = response.status;
                
                if (status >= 200 && status < 400) {
                    console.log(`  ✅ ${endpoint.name}: ${status} OK`);
                } else {
                    console.log(`  ❌ ${endpoint.name}: ${status} Error`);
                }
            } catch (error) {
                console.log(`  ❌ ${endpoint.name}: ${error.message}`);
            }
        }
    } catch (error) {
        console.log('  ❌ API testing failed:', error.message);
    }
    
    console.log('\n🎉 E2E Testing Complete!');
}

// Run the test
runSimpleE2ETest().catch(console.error);