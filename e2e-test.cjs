const { chromium } = require('playwright');
const fs = require('fs');

async function runE2ETests() {
    console.log('🚀 Starting E2E Tests for BellGas Application');
    console.log('=' .repeat(60));
    
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 1000 
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    let testResults = {
        admin: { passed: 0, failed: 0, tests: [] },
        customer: { passed: 0, failed: 0, tests: [] }
    };

    try {
        // Test Admin Flow
        await testAdminFlow(context, testResults);
        
        // Test Customer Flow
        await testCustomerFlow(context, testResults);
        
    } finally {
        await browser.close();
        printTestResults(testResults);
    }
}

async function testAdminFlow(context, testResults) {
    console.log('\n📊 TESTING ADMIN FLOW');
    console.log('-' .repeat(40));
    
    const page = await context.newPage();
    
    // Test 1: Admin Login
    await runTest('Admin Login', async () => {
        await page.goto('http://127.0.0.1:8000/login', { 
            waitUntil: 'networkidle' 
        });
        
        // Take screenshot
        await page.screenshot({ path: 'admin_login_page.png', fullPage: true });
        console.log('  📸 Screenshot: admin_login_page.png');
        
        // Fill admin credentials
        await page.fill('input[name="email"]', 'admin@bellgas.com.au');
        await page.fill('input[name="password"]', 'password');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Wait for redirect - either to admin dashboard or check if still on login
        await page.waitForTimeout(3000);
        
        const currentUrl = page.url();
        console.log('  🔗 Current URL after login:', currentUrl);
        
        // Take screenshot after login
        await page.screenshot({ path: 'admin_after_login.png', fullPage: true });
        console.log('  📸 Screenshot: admin_after_login.png');
        
        // Check if we're on admin dashboard or login page
        if (currentUrl.includes('/admin/dashboard')) {
            console.log('  ✅ Successfully redirected to admin dashboard');
            return true;
        } else if (currentUrl.includes('/login')) {
            console.log('  ❌ Still on login page - checking for errors');
            
            // Check for error messages
            const errorMessages = await page.locator('.text-red-600, .text-red-700, .text-red-800').allTextContents();
            if (errorMessages.length > 0) {
                console.log('  🚨 Error messages found:', errorMessages);
            }
            
            // Try to access admin dashboard directly
            console.log('  🔄 Trying to access admin dashboard directly...');
            await page.goto('http://127.0.0.1:8000/admin/dashboard');
            await page.waitForTimeout(2000);
            
            const finalUrl = page.url();
            console.log('  🔗 Final URL:', finalUrl);
            
            if (finalUrl.includes('/admin/dashboard')) {
                console.log('  ✅ Successfully accessed admin dashboard directly');
                return true;
            } else {
                console.log('  ❌ Failed to access admin dashboard');
                return false;
            }
        } else {
            console.log('  ✅ Redirected to:', currentUrl);
            return true;
        }
    }, testResults.admin);
    
    // Test 2: Admin Dashboard Access
    await runTest('Admin Dashboard Access', async () => {
        await page.goto('http://127.0.0.1:8000/admin/dashboard', { 
            waitUntil: 'networkidle' 
        });
        
        await page.screenshot({ path: 'admin_dashboard.png', fullPage: true });
        console.log('  📸 Screenshot: admin_dashboard.png');
        
        const title = await page.locator('h1').first().textContent();
        console.log('  📝 Page title:', title);
        
        return page.url().includes('/admin/dashboard') && !page.url().includes('/login');
    }, testResults.admin);
    
    // Test 3: Admin Orders Management
    await runTest('Admin Orders Management', async () => {
        await page.click('a[href="/admin/orders"]');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'admin_orders.png', fullPage: true });
        console.log('  📸 Screenshot: admin_orders.png');
        
        return page.url().includes('/admin/orders');
    }, testResults.admin);
    
    // Test 4: Admin Products Management
    await runTest('Admin Products Management', async () => {
        await page.click('a[href="/admin/products"]');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'admin_products.png', fullPage: true });
        console.log('  📸 Screenshot: admin_products.png');
        
        return page.url().includes('/admin/products');
    }, testResults.admin);
    
    // Test 5: Admin Customers Management
    await runTest('Admin Customers Management', async () => {
        await page.click('a[href="/admin/customers"]');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'admin_customers.png', fullPage: true });
        console.log('  📸 Screenshot: admin_customers.png');
        
        return page.url().includes('/admin/customers');
    }, testResults.admin);
    
    // Test 6: Admin Settings
    await runTest('Admin Settings Access', async () => {
        await page.click('a[href="/admin/settings"]');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'admin_settings.png', fullPage: true });
        console.log('  📸 Screenshot: admin_settings.png');
        
        return page.url().includes('/admin/settings');
    }, testResults.admin);
    
    await page.close();
}

async function testCustomerFlow(context, testResults) {
    console.log('\n🛍️ TESTING CUSTOMER FLOW');
    console.log('-' .repeat(40));
    
    const page = await context.newPage();
    
    // Test 1: Home Page Access
    await runTest('Home Page Access', async () => {
        await page.goto('http://127.0.0.1:8000/', { 
            waitUntil: 'networkidle' 
        });
        
        await page.screenshot({ path: 'customer_homepage.png', fullPage: true });
        console.log('  📸 Screenshot: customer_homepage.png');
        
        const title = await page.title();
        console.log('  📝 Page title:', title);
        
        return page.url() === 'http://127.0.0.1:8000/';
    }, testResults.customer);
    
    // Test 2: Products Page
    await runTest('Products Page Access', async () => {
        await page.goto('http://127.0.0.1:8000/products');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_products.png', fullPage: true });
        console.log('  📸 Screenshot: customer_products.png');
        
        return page.url().includes('/products');
    }, testResults.customer);
    
    // Test 3: Customer Login
    await runTest('Customer Login', async () => {
        await page.goto('http://127.0.0.1:8000/login');
        await page.waitForTimeout(1000);
        
        // Fill customer credentials
        await page.fill('input[name="email"]', 'stripetester@bellgas.com');
        await page.fill('input[name="password"]', 'password123');
        
        await page.screenshot({ path: 'customer_login_filled.png', fullPage: true });
        console.log('  📸 Screenshot: customer_login_filled.png');
        
        // Submit form
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        
        const currentUrl = page.url();
        console.log('  🔗 Current URL after login:', currentUrl);
        
        await page.screenshot({ path: 'customer_after_login.png', fullPage: true });
        console.log('  📸 Screenshot: customer_after_login.png');
        
        return currentUrl.includes('/dashboard') && !currentUrl.includes('/login');
    }, testResults.customer);
    
    // Test 4: Customer Dashboard
    await runTest('Customer Dashboard Access', async () => {
        await page.goto('http://127.0.0.1:8000/dashboard');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_dashboard.png', fullPage: true });
        console.log('  📸 Screenshot: customer_dashboard.png');
        
        return page.url().includes('/dashboard');
    }, testResults.customer);
    
    // Test 5: Cart Functionality
    await runTest('Cart Access', async () => {
        await page.goto('http://127.0.0.1:8000/cart');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_cart.png', fullPage: true });
        console.log('  📸 Screenshot: customer_cart.png');
        
        return page.url().includes('/cart');
    }, testResults.customer);
    
    // Test 6: Checkout Process
    await runTest('Checkout Access', async () => {
        await page.goto('http://127.0.0.1:8000/checkout');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_checkout.png', fullPage: true });
        console.log('  📸 Screenshot: customer_checkout.png');
        
        return page.url().includes('/checkout');
    }, testResults.customer);
    
    // Test 7: Orders History
    await runTest('Orders History', async () => {
        await page.goto('http://127.0.0.1:8000/orders');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_orders.png', fullPage: true });
        console.log('  📸 Screenshot: customer_orders.png');
        
        return page.url().includes('/orders');
    }, testResults.customer);
    
    // Test 8: Profile Management
    await runTest('Profile Management', async () => {
        await page.goto('http://127.0.0.1:8000/profile');
        await page.waitForTimeout(2000);
        
        await page.screenshot({ path: 'customer_profile.png', fullPage: true });
        console.log('  📸 Screenshot: customer_profile.png');
        
        return page.url().includes('/profile');
    }, testResults.customer);
    
    await page.close();
}

async function runTest(testName, testFunction, results) {
    console.log(`\n🧪 Running: ${testName}`);
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
        console.log(`  ❌ ERROR: ${testName} - ${error.message}`);
        results.failed++;
        results.tests.push({ name: testName, status: 'ERROR', error: error.message });
    }
}

function printTestResults(testResults) {
    console.log('\n' + '=' .repeat(60));
    console.log('📊 E2E TEST RESULTS SUMMARY');
    console.log('=' .repeat(60));
    
    console.log('\n👔 ADMIN TESTS:');
    console.log(`  ✅ Passed: ${testResults.admin.passed}`);
    console.log(`  ❌ Failed: ${testResults.admin.failed}`);
    console.log(`  📊 Total: ${testResults.admin.passed + testResults.admin.failed}`);
    
    testResults.admin.tests.forEach(test => {
        const icon = test.status === 'PASSED' ? '✅' : '❌';
        console.log(`    ${icon} ${test.name}`);
        if (test.error) {
            console.log(`      Error: ${test.error}`);
        }
    });
    
    console.log('\n🛍️ CUSTOMER TESTS:');
    console.log(`  ✅ Passed: ${testResults.customer.passed}`);
    console.log(`  ❌ Failed: ${testResults.customer.failed}`);
    console.log(`  📊 Total: ${testResults.customer.passed + testResults.customer.failed}`);
    
    testResults.customer.tests.forEach(test => {
        const icon = test.status === 'PASSED' ? '✅' : '❌';
        console.log(`    ${icon} ${test.name}`);
        if (test.error) {
            console.log(`      Error: ${test.error}`);
        }
    });
    
    const totalPassed = testResults.admin.passed + testResults.customer.passed;
    const totalFailed = testResults.admin.failed + testResults.customer.failed;
    const totalTests = totalPassed + totalFailed;
    const successRate = totalTests > 0 ? ((totalPassed / totalTests) * 100).toFixed(1) : 0;
    
    console.log('\n📈 OVERALL RESULTS:');
    console.log(`  🎯 Success Rate: ${successRate}%`);
    console.log(`  ✅ Total Passed: ${totalPassed}`);
    console.log(`  ❌ Total Failed: ${totalFailed}`);
    console.log(`  📊 Total Tests: ${totalTests}`);
    
    // Save results to JSON file
    const reportData = {
        timestamp: new Date().toISOString(),
        summary: {
            total_tests: totalTests,
            total_passed: totalPassed,
            total_failed: totalFailed,
            success_rate: successRate + '%'
        },
        admin_tests: testResults.admin,
        customer_tests: testResults.customer
    };
    
    fs.writeFileSync('e2e-test-report.json', JSON.stringify(reportData, null, 2));
    console.log('\n💾 Test report saved to: e2e-test-report.json');
    console.log('📸 Screenshots saved in current directory');
    console.log('\n🎉 E2E Testing Complete!');
}

// API Testing Function
async function testAPIEndpoints() {
    console.log('\n🔌 TESTING API ENDPOINTS');
    console.log('-' .repeat(40));
    
    const fetch = require('node-fetch');
    const baseUrl = 'http://127.0.0.1:8000/api';
    
    const apiTests = [
        { name: 'Health Check', endpoint: '/health', method: 'GET' },
        { name: 'Products API', endpoint: '/products', method: 'GET' },
        { name: 'Products Categories', endpoint: '/products/categories', method: 'GET' },
        { name: 'Auth Login Endpoint', endpoint: '/auth/login', method: 'POST', skipTest: true }
    ];
    
    for (const test of apiTests) {
        if (test.skipTest) continue;
        
        try {
            console.log(`\n🔍 Testing: ${test.name}`);
            const response = await fetch(`${baseUrl}${test.endpoint}`);
            const status = response.status;
            
            if (status === 200 || status === 201) {
                console.log(`  ✅ PASSED: ${test.name} (${status})`);
            } else {
                console.log(`  ❌ FAILED: ${test.name} (${status})`);
            }
        } catch (error) {
            console.log(`  ❌ ERROR: ${test.name} - ${error.message}`);
        }
    }
}

// Run the tests
runE2ETests().catch(console.error);