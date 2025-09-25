const { exec } = require('child_process');
const fs = require('fs');
const util = require('util');

const execAsync = util.promisify(exec);
const BASE_URL = 'http://127.0.0.1:8000';

async function runSimpleFunctionalTests() {
    console.log('🚀 Simple Functional Tests for BellGas Application');
    console.log('=' .repeat(60));
    
    const results = {
        web_routes: [],
        api_routes: [],
        admin_routes: [],
        auth_tests: [],
        total_passed: 0,
        total_failed: 0
    };

    // Test Web Routes
    await testWebRoutes(results);
    
    // Test API Routes
    await testAPIRoutes(results);
    
    // Test Admin Routes
    await testAdminRoutes(results);
    
    // Test Authentication
    await testAuthentication(results);
    
    // Print Results
    printResults(results);
}

async function testWebRoutes(results) {
    console.log('\n🌐 TESTING WEB ROUTES');
    console.log('-' .repeat(40));
    
    const routes = [
        { name: 'Home Page', path: '/' },
        { name: 'Login Page', path: '/login' },
        { name: 'Register Page', path: '/register' },
        { name: 'Products Page', path: '/products' },
        { name: 'Cart Page', path: '/cart' },
        { name: 'Checkout Page', path: '/checkout' },
        { name: 'Dashboard Page', path: '/dashboard' },
        { name: 'About Page', path: '/about' },
        { name: 'Contact Page', path: '/contact' }
    ];
    
    for (const route of routes) {
        const result = await testRoute(route, 'web');
        results.web_routes.push(result);
        
        if (result.success) {
            results.total_passed++;
        } else {
            results.total_failed++;
        }
    }
}

async function testAPIRoutes(results) {
    console.log('\n🔌 TESTING API ROUTES');
    console.log('-' .repeat(40));
    
    const routes = [
        { name: 'Health Check', path: '/up' },
        { name: 'API Products', path: '/api/products' },
        { name: 'API Categories', path: '/api/products/categories' }
    ];
    
    for (const route of routes) {
        const result = await testRoute(route, 'api');
        results.api_routes.push(result);
        
        if (result.success) {
            results.total_passed++;
        } else {
            results.total_failed++;
        }
    }
}

async function testAdminRoutes(results) {
    console.log('\n👔 TESTING ADMIN ROUTES');
    console.log('-' .repeat(40));
    
    const routes = [
        { name: 'Admin Dashboard', path: '/admin/dashboard' },
        { name: 'Admin Orders', path: '/admin/orders' },
        { name: 'Admin Products', path: '/admin/products' },
        { name: 'Admin Customers', path: '/admin/customers' }
    ];
    
    for (const route of routes) {
        const result = await testRoute(route, 'admin');
        results.admin_routes.push(result);
        
        if (result.success) {
            results.total_passed++;
        } else {
            results.total_failed++;
        }
    }
}

async function testRoute(route, category) {
    console.log(`\n🧪 Testing: ${route.name}`);
    
    try {
        const command = `curl -s -w "HTTPSTATUS:%{http_code}" -L --max-time 10 "${BASE_URL}${route.path}"`;
        const { stdout } = await execAsync(command);
        
        const statusMatch = stdout.match(/HTTPSTATUS:(\d+)$/);
        const statusCode = statusMatch ? parseInt(statusMatch[1]) : 0;
        
        console.log(`  🔗 URL: ${route.path}`);
        console.log(`  📊 Status: ${statusCode}`);
        
        // Consider success if status is 200, 302 (redirect), or 401 (auth required)
        const isSuccess = [200, 302, 401].includes(statusCode);
        
        if (isSuccess) {
            console.log(`  ✅ PASSED: ${route.name}`);
        } else {
            console.log(`  ❌ FAILED: ${route.name} (Status: ${statusCode})`);
        }
        
        return {
            name: route.name,
            path: route.path,
            status: statusCode,
            success: isSuccess,
            category: category
        };
        
    } catch (error) {
        console.log(`  ❌ ERROR: ${route.name} - ${error.message}`);
        
        return {
            name: route.name,
            path: route.path,
            status: 'ERROR',
            success: false,
            error: error.message,
            category: category
        };
    }
}

async function testAuthentication(results) {
    console.log('\n🔐 TESTING AUTHENTICATION');
    console.log('-' .repeat(40));
    
    // Test Admin Login
    console.log('\n🧪 Testing Admin API Login');
    
    try {
        const loginData = JSON.stringify({
            email: 'admin@bellgas.com.au',
            password: 'password'
        });
        
        const command = `curl -s -w "HTTPSTATUS:%{http_code}" -X POST -H "Content-Type: application/json" -H "Accept: application/json" -d '${loginData}' --max-time 10 "${BASE_URL}/api/auth/login"`;
        
        const { stdout } = await execAsync(command);
        const statusMatch = stdout.match(/HTTPSTATUS:(\d+)$/);
        const statusCode = statusMatch ? parseInt(statusMatch[1]) : 0;
        
        console.log(`  📊 Admin Login Status: ${statusCode}`);
        
        const responseBody = stdout.replace(/HTTPSTATUS:\d+$/, '');
        
        if (statusCode === 200) {
            try {
                const responseData = JSON.parse(responseBody);
                if (responseData.access_token) {
                    console.log('  ✅ PASSED: Admin API Login successful');
                    console.log(`  🔑 Token received: ${responseData.access_token.substring(0, 20)}...`);
                    console.log(`  👤 User: ${responseData.user.email} (${responseData.user.role})`);
                    
                    results.auth_tests.push({
                        name: 'Admin API Login',
                        success: true,
                        status: statusCode
                    });
                    results.total_passed++;
                } else {
                    console.log('  ❌ FAILED: No access token in response');
                    results.auth_tests.push({
                        name: 'Admin API Login',
                        success: false,
                        status: statusCode,
                        error: 'No access token'
                    });
                    results.total_failed++;
                }
            } catch (parseError) {
                console.log('  ❌ FAILED: Invalid JSON response');
                results.auth_tests.push({
                    name: 'Admin API Login',
                    success: false,
                    status: statusCode,
                    error: 'Invalid JSON'
                });
                results.total_failed++;
            }
        } else {
            console.log(`  ❌ FAILED: Admin API Login failed (Status: ${statusCode})`);
            console.log(`  Response: ${responseBody.substring(0, 200)}...`);
            results.auth_tests.push({
                name: 'Admin API Login',
                success: false,
                status: statusCode,
                response: responseBody.substring(0, 100)
            });
            results.total_failed++;
        }
        
    } catch (error) {
        console.log(`  ❌ ERROR: Admin API Login - ${error.message}`);
        results.auth_tests.push({
            name: 'Admin API Login',
            success: false,
            error: error.message
        });
        results.total_failed++;
    }
    
    // Test Customer Login
    console.log('\n🧪 Testing Customer API Login');
    
    try {
        const loginData = JSON.stringify({
            email: 'stripetester@bellgas.com',
            password: 'password123'
        });
        
        const command = `curl -s -w "HTTPSTATUS:%{http_code}" -X POST -H "Content-Type: application/json" -H "Accept: application/json" -d '${loginData}' --max-time 10 "${BASE_URL}/api/auth/login"`;
        
        const { stdout } = await execAsync(command);
        const statusMatch = stdout.match(/HTTPSTATUS:(\d+)$/);
        const statusCode = statusMatch ? parseInt(statusMatch[1]) : 0;
        
        console.log(`  📊 Customer Login Status: ${statusCode}`);
        
        const responseBody = stdout.replace(/HTTPSTATUS:\d+$/, '');
        
        if (statusCode === 200) {
            try {
                const responseData = JSON.parse(responseBody);
                if (responseData.access_token) {
                    console.log('  ✅ PASSED: Customer API Login successful');
                    console.log(`  🔑 Token received: ${responseData.access_token.substring(0, 20)}...`);
                    console.log(`  👤 User: ${responseData.user.email} (${responseData.user.role})`);
                    
                    results.auth_tests.push({
                        name: 'Customer API Login',
                        success: true,
                        status: statusCode
                    });
                    results.total_passed++;
                } else {
                    console.log('  ❌ FAILED: No access token in response');
                    results.auth_tests.push({
                        name: 'Customer API Login',
                        success: false,
                        status: statusCode,
                        error: 'No access token'
                    });
                    results.total_failed++;
                }
            } catch (parseError) {
                console.log('  ❌ FAILED: Invalid JSON response');
                results.auth_tests.push({
                    name: 'Customer API Login',
                    success: false,
                    status: statusCode,
                    error: 'Invalid JSON'
                });
                results.total_failed++;
            }
        } else {
            console.log(`  ❌ FAILED: Customer API Login failed (Status: ${statusCode})`);
            results.auth_tests.push({
                name: 'Customer API Login',
                success: false,
                status: statusCode,
                response: responseBody.substring(0, 100)
            });
            results.total_failed++;
        }
        
    } catch (error) {
        console.log(`  ❌ ERROR: Customer API Login - ${error.message}`);
        results.auth_tests.push({
            name: 'Customer API Login',
            success: false,
            error: error.message
        });
        results.total_failed++;
    }
}

function printResults(results) {
    console.log('\n' + '=' .repeat(60));
    console.log('📊 SIMPLE FUNCTIONAL TEST RESULTS');
    console.log('=' .repeat(60));
    
    // Summary by category
    console.log('\n🌐 WEB ROUTES:');
    results.web_routes.forEach(test => {
        const icon = test.success ? '✅' : '❌';
        console.log(`  ${icon} ${test.name} (${test.status})`);
    });
    
    console.log('\n🔌 API ROUTES:');
    results.api_routes.forEach(test => {
        const icon = test.success ? '✅' : '❌';
        console.log(`  ${icon} ${test.name} (${test.status})`);
    });
    
    console.log('\n👔 ADMIN ROUTES:');
    results.admin_routes.forEach(test => {
        const icon = test.success ? '✅' : '❌';
        console.log(`  ${icon} ${test.name} (${test.status})`);
    });
    
    console.log('\n🔐 AUTHENTICATION:');
    results.auth_tests.forEach(test => {
        const icon = test.success ? '✅' : '❌';
        console.log(`  ${icon} ${test.name} (${test.status || test.error})`);
    });
    
    // Overall Summary
    const totalTests = results.total_passed + results.total_failed;
    const successRate = totalTests > 0 ? ((results.total_passed / totalTests) * 100).toFixed(1) : 0;
    
    console.log('\n📈 OVERALL RESULTS:');
    console.log(`  🎯 Success Rate: ${successRate}%`);
    console.log(`  ✅ Total Passed: ${results.total_passed}`);
    console.log(`  ❌ Total Failed: ${results.total_failed}`);
    console.log(`  📊 Total Tests: ${totalTests}`);
    
    // Save report
    const reportData = {
        timestamp: new Date().toISOString(),
        summary: {
            total_tests: totalTests,
            total_passed: results.total_passed,
            total_failed: results.total_failed,
            success_rate: successRate + '%'
        },
        tests: {
            web_routes: results.web_routes,
            api_routes: results.api_routes,
            admin_routes: results.admin_routes,
            auth_tests: results.auth_tests
        }
    };
    
    fs.writeFileSync('simple-functional-report.json', JSON.stringify(reportData, null, 2));
    
    console.log('\n💾 Report saved: simple-functional-report.json');
    console.log('\n🎉 Testing completed!');
    console.log('\n📋 KEY FINDINGS:');
    
    // Analysis
    const webSuccess = results.web_routes.filter(t => t.success).length;
    const apiSuccess = results.api_routes.filter(t => t.success).length;
    const adminSuccess = results.admin_routes.filter(t => t.success).length;
    const authSuccess = results.auth_tests.filter(t => t.success).length;
    
    console.log(`   • Web Routes: ${webSuccess}/${results.web_routes.length} accessible`);
    console.log(`   • API Routes: ${apiSuccess}/${results.api_routes.length} responding`);
    console.log(`   • Admin Routes: ${adminSuccess}/${results.admin_routes.length} responding`);
    console.log(`   • Authentication: ${authSuccess}/${results.auth_tests.length} working`);
    
    if (successRate >= 80) {
        console.log('\n🎊 EXCELLENT: Application is functioning well!');
    } else if (successRate >= 60) {
        console.log('\n👍 GOOD: Most features are working, some issues detected');
    } else {
        console.log('\n⚠️  ATTENTION: Multiple issues detected, requires investigation');
    }
}

// Run the tests
runSimpleFunctionalTests().catch(console.error);