#!/usr/bin/env node

/**
 * Frontend Validation Script for BellGas Laravel Application
 * This script validates the frontend Blade templates and JavaScript functionality
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

console.log('🔍 BellGas Frontend Validation Script');
console.log('='.repeat(50));

// Server configuration
const SERVER_HOST = '127.0.0.1';
const SERVER_PORT = 8000;
const BASE_URL = `http://${SERVER_HOST}:${SERVER_PORT}`;

// Test routes to validate
const testRoutes = [
    '/',
    '/home',
    '/login',
    '/register', 
    '/products',
    '/checkout',
    '/dashboard',
    '/about',
    '/contact'
];

// Validation results
const results = {
    total: 0,
    passed: 0,
    failed: 0,
    routes: []
};

function makeRequest(url) {
    return new Promise((resolve, reject) => {
        const request = http.get(url, (response) => {
            let data = '';
            
            response.on('data', chunk => {
                data += chunk;
            });
            
            response.on('end', () => {
                resolve({
                    statusCode: response.statusCode,
                    headers: response.headers,
                    body: data
                });
            });
        });
        
        request.on('error', (error) => {
            reject(error);
        });
        
        request.setTimeout(5000, () => {
            request.destroy();
            reject(new Error('Request timeout'));
        });
    });
}

function validateResponse(route, response) {
    const validation = {
        route: route,
        status: 'PASS',
        statusCode: response.statusCode,
        issues: []
    };
    
    // Check status code
    if (response.statusCode !== 200) {
        validation.issues.push(`HTTP ${response.statusCode}`);
    }
    
    // Check if it's HTML content
    const contentType = response.headers['content-type'] || '';
    if (!contentType.includes('text/html')) {
        validation.issues.push('Not HTML content');
    }
    
    // Check for essential HTML structure
    const body = response.body;
    if (!body.includes('<html') && !body.includes('<HTML')) {
        validation.issues.push('Missing HTML tag');
    }
    
    if (!body.includes('<head') && !body.includes('<HEAD')) {
        validation.issues.push('Missing HEAD tag');
    }
    
    if (!body.includes('<body') && !body.includes('<BODY')) {
        validation.issues.push('Missing BODY tag');
    }
    
    // Check for title tag
    if (!body.includes('<title') && !body.includes('<TITLE')) {
        validation.issues.push('Missing TITLE tag');
    }
    
    // Check for specific BellGas content
    if (route === '/' || route === '/home') {
        if (!body.includes('BellGas')) {
            validation.issues.push('Missing BellGas branding');
        }
        if (!body.includes('LPG')) {
            validation.issues.push('Missing LPG content');
        }
    }
    
    // Check for authentication pages
    if (route === '/login') {
        if (!body.includes('email') && !body.includes('Email')) {
            validation.issues.push('Missing email field');
        }
        if (!body.includes('password') && !body.includes('Password')) {
            validation.issues.push('Missing password field');
        }
    }
    
    if (route === '/register') {
        if (!body.includes('first_name') || !body.includes('last_name')) {
            validation.issues.push('Missing name fields');
        }
    }
    
    // Check for essential CSS/JS
    if (!body.includes('tailwind') && !body.includes('css')) {
        validation.issues.push('Missing CSS framework');
    }
    
    if (!body.includes('alpine') && !body.includes('script')) {
        validation.issues.push('Missing JavaScript framework');
    }
    
    // Set final status
    if (validation.issues.length > 0) {
        validation.status = 'FAIL';
    }
    
    return validation;
}

async function validateRoute(route) {
    const url = `${BASE_URL}${route}`;
    console.log(`\n🧪 Testing: ${route}`);
    
    try {
        const response = await makeRequest(url);
        const validation = validateResponse(route, response);
        
        console.log(`   Status: ${validation.status === 'PASS' ? '✅' : '❌'} ${validation.status}`);
        console.log(`   HTTP Code: ${validation.statusCode}`);
        
        if (validation.issues.length > 0) {
            console.log(`   Issues: ${validation.issues.join(', ')}`);
            results.failed++;
        } else {
            results.passed++;
        }
        
        results.routes.push(validation);
        
    } catch (error) {
        console.log(`   Status: ❌ ERROR`);
        console.log(`   Error: ${error.message}`);
        
        results.routes.push({
            route: route,
            status: 'ERROR',
            error: error.message
        });
        results.failed++;
    }
    
    results.total++;
}

async function checkServerStatus() {
    console.log('\n🌐 Checking Laravel server...');
    
    try {
        const response = await makeRequest(BASE_URL);
        console.log(`✅ Server is running (HTTP ${response.statusCode})`);
        return true;
    } catch (error) {
        console.log(`❌ Server is not accessible: ${error.message}`);
        console.log('\n💡 Please ensure Laravel server is running:');
        console.log('   php artisan serve --host=127.0.0.1 --port=8000');
        return false;
    }
}

function printSummary() {
    console.log('\n' + '='.repeat(50));
    console.log('📊 FRONTEND VALIDATION SUMMARY');
    console.log('='.repeat(50));
    
    console.log(`Total Routes Tested: ${results.total}`);
    console.log(`✅ Passed: ${results.passed}`);
    console.log(`❌ Failed: ${results.failed}`);
    
    const successRate = results.total > 0 ? ((results.passed / results.total) * 100).toFixed(1) : 0;
    console.log(`🎯 Success Rate: ${successRate}%`);
    
    console.log('\n📋 Detailed Results:');
    results.routes.forEach(result => {
        const status = result.status === 'PASS' ? '✅' : (result.status === 'ERROR' ? '💥' : '❌');
        console.log(`  ${status} ${result.route} (${result.status})`);
        
        if (result.issues && result.issues.length > 0) {
            console.log(`      Issues: ${result.issues.join(', ')}`);
        }
        if (result.error) {
            console.log(`      Error: ${result.error}`);
        }
    });
    
    console.log('\n' + '='.repeat(50));
    
    if (results.failed === 0) {
        console.log('🎉 All frontend routes are working correctly!');
        console.log('✨ The BellGas application frontend is ready for use.');
    } else {
        console.log(`⚠️  ${results.failed} route(s) have issues that need attention.`);
    }
    
    console.log(`\n⏱️  Validation completed at: ${new Date().toLocaleString()}`);
}

async function main() {
    const startTime = Date.now();
    
    // Check if server is running
    const serverReady = await checkServerStatus();
    if (!serverReady) {
        process.exit(1);
    }
    
    // Test all routes
    console.log('\n🚀 Starting route validation...');
    
    for (const route of testRoutes) {
        await validateRoute(route);
    }
    
    // Print summary
    printSummary();
    
    const totalTime = Date.now() - startTime;
    console.log(`\n⏱️  Total execution time: ${(totalTime / 1000).toFixed(2)}s`);
    
    // Exit with appropriate code
    process.exit(results.failed > 0 ? 1 : 0);
}

// Handle CLI arguments
if (process.argv.includes('--help') || process.argv.includes('-h')) {
    console.log(`
BellGas Frontend Validation Script

Usage: node frontend-validation.js [options]

Options:
  --help, -h     Show this help message

Examples:
  node frontend-validation.js
`);
    process.exit(0);
}

// Run validation
main().catch(error => {
    console.error('\n💥 Validation script failed:', error.message);
    process.exit(1);
});

module.exports = {
    validateRoute,
    testRoutes,
    results
};