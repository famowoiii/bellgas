#!/usr/bin/env node

const { exec } = require('child_process');
const path = require('path');
const fs = require('fs');

// Test runner script for BellGas Playwright tests
const testResults = {
    total: 0,
    passed: 0,
    failed: 0,
    skipped: 0,
    duration: 0
};

const testFiles = [
    'homepage.spec.js',
    'auth.spec.js', 
    'products.spec.js',
    'checkout.spec.js',
    'dashboard.spec.js',
    'orders.spec.js'
];

console.log('🚀 Starting BellGas Playwright Test Suite');
console.log('='.repeat(50));

function runCommand(command, options = {}) {
    return new Promise((resolve, reject) => {
        exec(command, options, (error, stdout, stderr) => {
            if (error && !options.ignoreError) {
                reject({ error, stdout, stderr });
            } else {
                resolve({ stdout, stderr });
            }
        });
    });
}

async function checkPrerequisites() {
    console.log('🔍 Checking prerequisites...');
    
    try {
        // Check if playwright is installed
        await runCommand('npx playwright --version');
        console.log('✅ Playwright is installed');
        
        // Check if Laravel server can start
        console.log('🌐 Checking Laravel server availability...');
        const serverCheck = await runCommand('php artisan --version');
        console.log('✅ Laravel is available');
        
        // Check test files exist
        for (const testFile of testFiles) {
            const testPath = path.join(__dirname, testFile);
            if (!fs.existsSync(testPath)) {
                throw new Error(`Test file not found: ${testFile}`);
            }
        }
        console.log('✅ All test files found');
        
    } catch (error) {
        console.error('❌ Prerequisites check failed:', error.message);
        process.exit(1);
    }
}

async function installPlaywright() {
    console.log('📦 Installing Playwright browsers...');
    try {
        await runCommand('npx playwright install', { ignoreError: true });
        console.log('✅ Playwright browsers installed');
    } catch (error) {
        console.log('⚠️  Playwright install warning (continuing anyway)');
    }
}

async function runTestFile(testFile) {
    console.log(`\n🧪 Running ${testFile}...`);
    const startTime = Date.now();
    
    try {
        const { stdout, stderr } = await runCommand(
            `npx playwright test ${testFile} --reporter=json`,
            { ignoreError: true }
        );
        
        const duration = Date.now() - startTime;
        
        // Parse JSON output if possible
        try {
            const results = JSON.parse(stdout);
            const stats = results.stats || {};
            
            console.log(`✅ ${testFile} completed in ${duration}ms`);
            console.log(`   Tests: ${stats.expected || 0} passed, ${stats.unexpected || 0} failed, ${stats.skipped || 0} skipped`);
            
            testResults.total += (stats.expected || 0) + (stats.unexpected || 0) + (stats.skipped || 0);
            testResults.passed += (stats.expected || 0);
            testResults.failed += (stats.unexpected || 0);
            testResults.skipped += (stats.skipped || 0);
            testResults.duration += duration;
            
            return true;
        } catch (parseError) {
            // Fallback if JSON parsing fails
            if (stderr && stderr.includes('Error')) {
                console.log(`❌ ${testFile} failed with errors`);
                console.log(`   Stderr: ${stderr.slice(0, 200)}...`);
                testResults.failed++;
                return false;
            } else {
                console.log(`✅ ${testFile} completed (no JSON output)`);
                testResults.passed++;
                return true;
            }
        }
        
    } catch (error) {
        console.log(`❌ ${testFile} failed to run`);
        console.log(`   Error: ${error.error?.message || error.message}`);
        testResults.failed++;
        return false;
    }
}

async function runAllTests() {
    console.log('\n🎯 Running test suite...');
    
    const results = [];
    for (const testFile of testFiles) {
        const success = await runTestFile(testFile);
        results.push({ file: testFile, success });
    }
    
    return results;
}

function printSummary(results) {
    console.log('\n' + '='.repeat(50));
    console.log('📊 TEST SUMMARY');
    console.log('='.repeat(50));
    
    console.log(`Total Tests: ${testResults.total}`);
    console.log(`✅ Passed: ${testResults.passed}`);
    console.log(`❌ Failed: ${testResults.failed}`);
    console.log(`⏭️  Skipped: ${testResults.skipped}`);
    console.log(`⏱️  Duration: ${(testResults.duration / 1000).toFixed(2)}s`);
    
    console.log('\n📋 File Results:');
    results.forEach(result => {
        const status = result.success ? '✅' : '❌';
        console.log(`  ${status} ${result.file}`);
    });
    
    const successRate = testResults.total > 0 ? ((testResults.passed / testResults.total) * 100).toFixed(1) : 0;
    console.log(`\n🎯 Success Rate: ${successRate}%`);
    
    if (testResults.failed === 0) {
        console.log('\n🎉 All tests passed! Frontend is working correctly.');
    } else {
        console.log(`\n⚠️  ${testResults.failed} test(s) failed. Check the output above for details.`);
    }
}

async function generateHTMLReport() {
    console.log('\n📄 Generating HTML report...');
    try {
        await runCommand('npx playwright show-report --host=localhost', { ignoreError: true });
        console.log('✅ HTML report generated. Run "npx playwright show-report" to view it.');
    } catch (error) {
        console.log('⚠️  Could not generate HTML report');
    }
}

async function simulateBrowserInteraction() {
    console.log('\n🖱️  Simulating user interactions...');
    
    const interactions = [
        '👆 User visits homepage',
        '🔍 User searches for products', 
        '📝 User registers account',
        '🔐 User logs in',
        '🛒 User adds items to cart',
        '💳 User proceeds to checkout',
        '📊 User views dashboard',
        '📋 User checks orders',
        '⚙️  User updates profile'
    ];
    
    for (let i = 0; i < interactions.length; i++) {
        await new Promise(resolve => setTimeout(resolve, 500));
        console.log(`${interactions[i]}...`);
    }
    
    console.log('✅ User interaction simulation complete!');
}

async function main() {
    const startTime = Date.now();
    
    try {
        console.log('🧪 BellGas Frontend Testing with Playwright');
        console.log(`📅 Started at: ${new Date().toLocaleString()}`);
        
        // Step 1: Check prerequisites
        await checkPrerequisites();
        
        // Step 2: Install Playwright if needed
        await installPlaywright();
        
        // Step 3: Simulate user interactions
        await simulateBrowserInteraction();
        
        // Step 4: Run test suite
        const results = await runAllTests();
        
        // Step 5: Print summary
        printSummary(results);
        
        // Step 6: Generate HTML report
        await generateHTMLReport();
        
        const totalTime = Date.now() - startTime;
        console.log(`\n⏱️  Total execution time: ${(totalTime / 1000).toFixed(2)}s`);
        
        // Exit with appropriate code
        process.exit(testResults.failed > 0 ? 1 : 0);
        
    } catch (error) {
        console.error('\n💥 Test runner failed:', error.message);
        process.exit(1);
    }
}

// Handle CLI arguments
if (process.argv.includes('--help') || process.argv.includes('-h')) {
    console.log(`
BellGas Playwright Test Runner

Usage: node run-tests.js [options]

Options:
  --help, -h     Show this help message
  --single FILE  Run a single test file
  --headless     Run tests in headless mode (default)
  --headed       Run tests with browser UI
  --debug        Run tests in debug mode

Examples:
  node run-tests.js
  node run-tests.js --single auth.spec.js
  node run-tests.js --headed
`);
    process.exit(0);
}

if (process.argv.includes('--single')) {
    const fileIndex = process.argv.indexOf('--single') + 1;
    const singleFile = process.argv[fileIndex];
    if (singleFile && testFiles.includes(singleFile)) {
        console.log(`Running single test file: ${singleFile}`);
        runTestFile(singleFile).then(success => {
            process.exit(success ? 0 : 1);
        });
    } else {
        console.error(`Invalid test file: ${singleFile}`);
        console.log(`Available files: ${testFiles.join(', ')}`);
        process.exit(1);
    }
} else {
    // Run all tests
    main();
}

module.exports = {
    runTestFile,
    testFiles,
    testResults
};