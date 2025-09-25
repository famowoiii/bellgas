const fetch = require('node-fetch');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8000';

async function runAPIFunctionalTests() {
    console.log('🚀 Starting API & Functional Tests for BellGas Application');
    console.log('=' .repeat(60));
    
    const results = {
        web_routes: { passed: 0, failed: 0, tests: [] },
        api_routes: { passed: 0, failed: 0, tests: [] },
        admin_routes: { passed: 0, failed: 0, tests: [] }
    };

    // Test Web Routes
    await testWebRoutes(results.web_routes);
    
    // Test API Routes  
    await testAPIRoutes(results.api_routes);
    
    // Test Admin Routes (will likely redirect to login)
    await testAdminRoutes(results.admin_routes);
    
    // Print Results
    printResults(results);
}

async function testWebRoutes(results) {
    console.log('\n🌐 TESTING WEB ROUTES');
    console.log('-' .repeat(40));
    
    const webRoutes = [
        { name: 'Home Page', url: '/', expectedStatus: [200] },
        { name: 'Login Page', url: '/login', expectedStatus: [200] },
        { name: 'Register Page', url: '/register', expectedStatus: [200] },
        { name: 'Products Page', url: '/products', expectedStatus: [200] },
        { name: 'Cart Page', url: '/cart', expectedStatus: [200] },
        { name: 'Checkout Page', url: '/checkout', expectedStatus: [200] },
        { name: 'Dashboard Page', url: '/dashboard', expectedStatus: [200, 302] },
        { name: 'Orders Page', url: '/orders', expectedStatus: [200, 302] },
        { name: 'Profile Page', url: '/profile', expectedStatus: [200, 302] },
        { name: 'About Page', url: '/about', expectedStatus: [200] },
        { name: 'Contact Page', url: '/contact', expectedStatus: [200] }
    ];
    
    for (const route of webRoutes) {
        await testRoute(route, results, 'web');
    }
}

async function testAPIRoutes(results) {
    console.log('\n🔌 TESTING API ROUTES');
    console.log('-' .repeat(40));
    
    const apiRoutes = [
        { name: 'Health Check', url: '/up', expectedStatus: [200] },
        { name: 'API Products', url: '/api/products', expectedStatus: [200, 401] },
        { name: 'API Product Categories', url: '/api/products/categories', expectedStatus: [200, 401] },
        { name: 'API Cart', url: '/api/cart', expectedStatus: [200, 401] },
        { name: 'API Auth Login (GET)', url: '/api/auth/login', expectedStatus: [405] }, // Should be POST only
        { name: 'API Auth Me', url: '/api/auth/me', expectedStatus: [401] } // Should require auth
    ];
    
    for (const route of apiRoutes) {
        await testRoute(route, results, 'api');
    }
}

async function testAdminRoutes(results) {
    console.log('\n👔 TESTING ADMIN ROUTES');
    console.log('-' .repeat(40));
    
    const adminRoutes = [
        { name: 'Admin Dashboard', url: '/admin/dashboard', expectedStatus: [200, 302] },
        { name: 'Admin Orders', url: '/admin/orders', expectedStatus: [200, 302] },
        { name: 'Admin Products', url: '/admin/products', expectedStatus: [200, 302] },
        { name: 'Admin Customers', url: '/admin/customers', expectedStatus: [200, 302] },
        { name: 'Admin Settings', url: '/admin/settings', expectedStatus: [200, 302] }
    ];
    
    for (const route of adminRoutes) {
        await testRoute(route, results, 'admin');
    }
}

async function testRoute(route, results, category) {
    console.log(`\n🧪 Testing: ${route.name}`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(`${BASE_URL}${route.url}`, {
            method: 'GET',
            headers: {
                'User-Agent': 'BellGas-E2E-Test/1.0',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            },
            timeout: 10000,
            redirect: 'manual'
        });
        
        const endTime = Date.now();
        const responseTime = endTime - startTime;
        const status = response.status;
        const location = response.headers.get('location');
        
        console.log(`  🔗 URL: ${route.url}`);
        console.log(`  📊 Status: ${status}`);
        console.log(`  ⏱️ Response Time: ${responseTime}ms`);
        
        if (location) {
            console.log(`  📍 Redirects to: ${location}`);
        }
        
        const isSuccess = route.expectedStatus.includes(status);
        
        if (isSuccess) {
            console.log(`  ✅ PASSED: ${route.name}`);
            results.passed++;
            results.tests.push({ 
                name: route.name, 
                status: 'PASSED', 
                httpStatus: status,
                responseTime: responseTime,
                url: route.url,
                location: location
            });
        } else {
            console.log(`  ❌ FAILED: ${route.name} (Expected: ${route.expectedStatus.join(' or ')}, Got: ${status})`);
            results.failed++;
            results.tests.push({ 
                name: route.name, 
                status: 'FAILED', 
                httpStatus: status,
                expectedStatus: route.expectedStatus,
                responseTime: responseTime,
                url: route.url,
                location: location
            });
        }
        
    } catch (error) {
        console.log(`  ❌ ERROR: ${route.name}`);
        console.log(`    Error: ${error.message}`);
        
        results.failed++;
        results.tests.push({ 
            name: route.name, 
            status: 'ERROR', 
            error: error.message,
            url: route.url
        });
    }
}

async function testLoginFlow() {
    console.log('\n🔐 TESTING LOGIN FLOW');
    console.log('-' .repeat(40));
    
    try {
        // Test Admin Login via API
        console.log('\n🧪 Testing Admin API Login');
        
        const adminLoginResponse = await fetch(`${BASE_URL}/api/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'admin@bellgas.com.au',
                password: 'password'
            }),
            timeout: 10000
        });
        
        const adminLoginData = await adminLoginResponse.json();
        console.log(`  📊 Admin API Login Status: ${adminLoginResponse.status}`);
        
        if (adminLoginResponse.status === 200 && adminLoginData.access_token) {
            console.log('  ✅ PASSED: Admin API Login successful');
            console.log(`  🔑 Token received: ${adminLoginData.access_token.substring(0, 20)}...`);
            
            // Test authenticated API call
            console.log('\n🧪 Testing Authenticated API Call');
            
            const authResponse = await fetch(`${BASE_URL}/api/auth/me`, {
                headers: {
                    'Authorization': `Bearer ${adminLoginData.access_token}`,
                    'Accept': 'application/json'
                },
                timeout: 10000
            });
            
            console.log(`  📊 Auth Me Status: ${authResponse.status}`);
            
            if (authResponse.status === 200) {
                const userData = await authResponse.json();
                console.log('  ✅ PASSED: Authenticated API call successful');
                console.log(`  👤 User: ${userData.email} (${userData.role})`);
            } else {
                console.log('  ❌ FAILED: Authenticated API call failed');
            }
            
        } else {
            console.log('  ❌ FAILED: Admin API Login failed');
            console.log(`    Response: ${JSON.stringify(adminLoginData)}`);
        }
        
        // Test Customer Login via API
        console.log('\n🧪 Testing Customer API Login');
        
        const customerLoginResponse = await fetch(`${BASE_URL}/api/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'stripetester@bellgas.com',
                password: 'password123'
            }),
            timeout: 10000
        });
        
        const customerLoginData = await customerLoginResponse.json();
        console.log(`  📊 Customer API Login Status: ${customerLoginResponse.status}`);
        
        if (customerLoginResponse.status === 200 && customerLoginData.access_token) {
            console.log('  ✅ PASSED: Customer API Login successful');
            console.log(`  🔑 Token received: ${customerLoginData.access_token.substring(0, 20)}...`);
        } else {
            console.log('  ❌ FAILED: Customer API Login failed');
            console.log(`    Response: ${JSON.stringify(customerLoginData)}`);
        }
        
    } catch (error) {
        console.log(`  ❌ ERROR: Login Flow Test - ${error.message}`);
    }
}

function printResults(results) {
    console.log('\n' + '=' .repeat(60));
    console.log('📊 API & FUNCTIONAL TEST RESULTS');
    console.log('=' .repeat(60));
    
    // Web Routes Results
    console.log('\n🌐 WEB ROUTES:');
    console.log(`  ✅ Passed: ${results.web_routes.passed}`);
    console.log(`  ❌ Failed: ${results.web_routes.failed}`);
    console.log(`  📊 Total: ${results.web_routes.passed + results.web_routes.failed}`);
    
    // API Routes Results
    console.log('\n🔌 API ROUTES:');
    console.log(`  ✅ Passed: ${results.api_routes.passed}`);
    console.log(`  ❌ Failed: ${results.api_routes.failed}`);
    console.log(`  📊 Total: ${results.api_routes.passed + results.api_routes.failed}`);
    
    // Admin Routes Results
    console.log('\n👔 ADMIN ROUTES:');
    console.log(`  ✅ Passed: ${results.admin_routes.passed}`);
    console.log(`  ❌ Failed: ${results.admin_routes.failed}`);
    console.log(`  📊 Total: ${results.admin_routes.passed + results.admin_routes.failed}`);
    
    // Overall Results
    const totalPassed = results.web_routes.passed + results.api_routes.passed + results.admin_routes.passed;
    const totalFailed = results.web_routes.failed + results.api_routes.failed + results.admin_routes.failed;
    const totalTests = totalPassed + totalFailed;
    const successRate = totalTests > 0 ? ((totalPassed / totalTests) * 100).toFixed(1) : 0;
    
    console.log('\n📈 OVERALL RESULTS:');
    console.log(`  🎯 Success Rate: ${successRate}%`);
    console.log(`  ✅ Total Passed: ${totalPassed}`);
    console.log(`  ❌ Total Failed: ${totalFailed}`);
    console.log(`  📊 Total Tests: ${totalTests}`);
    
    // Save detailed report
    const reportData = {
        timestamp: new Date().toISOString(),
        summary: {
            total_tests: totalTests,
            total_passed: totalPassed,
            total_failed: totalFailed,
            success_rate: successRate + '%'
        },
        web_routes: results.web_routes,
        api_routes: results.api_routes,
        admin_routes: results.admin_routes
    };
    
    fs.writeFileSync('functional-test-report.json', JSON.stringify(reportData, null, 2));
    console.log('\n💾 Detailed report saved: functional-test-report.json');
    
    // Test Login Flow
    testLoginFlow().then(() => {
        console.log('\n🎉 All tests completed!');
        console.log('\n📋 SUMMARY:');
        console.log('   • Web routes tested for accessibility');
        console.log('   • API routes tested for proper responses');
        console.log('   • Admin routes tested (redirect behavior)');
        console.log('   • Authentication flow tested');
        console.log('   • Report generated with detailed results');
    });
}

// Run the tests
runAPIFunctionalTests().catch(console.error);