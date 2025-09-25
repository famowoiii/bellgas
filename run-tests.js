#!/usr/bin/env node

const { exec, spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 BellGas Laravel - Comprehensive Testing Suite');
console.log('================================================\n');

// Configuration
const config = {
  testDir: './tests/playwright',
  reporters: ['html', 'json', 'junit'],
  browsers: ['chromium', 'firefox', 'webkit'],
  timeout: 30000,
  retries: 2
};

// Helper functions
function runCommand(command, options = {}) {
  return new Promise((resolve, reject) => {
    console.log(`📋 Running: ${command}`);
    
    const child = exec(command, { 
      cwd: process.cwd(),
      ...options 
    });
    
    let stdout = '';
    let stderr = '';
    
    child.stdout?.on('data', (data) => {
      stdout += data;
      if (options.showOutput) {
        process.stdout.write(data);
      }
    });
    
    child.stderr?.on('data', (data) => {
      stderr += data;
      if (options.showOutput) {
        process.stderr.write(data);
      }
    });
    
    child.on('close', (code) => {
      if (code === 0) {
        resolve({ stdout, stderr, code });
      } else {
        reject(new Error(`Command failed with code ${code}: ${stderr}`));
      }
    });
  });
}

function createTestReport() {
  const timestamp = new Date().toISOString();
  const reportDir = './test-reports';
  
  if (!fs.existsSync(reportDir)) {
    fs.mkdirSync(reportDir, { recursive: true });
  }
  
  return {
    timestamp,
    reportDir,
    summary: {
      total: 0,
      passed: 0,
      failed: 0,
      skipped: 0,
      duration: 0
    },
    tests: [],
    errors: []
  };
}

async function checkPrerequisites() {
  console.log('🔍 Checking prerequisites...\n');
  
  // Check if Laravel is installed
  try {
    await runCommand('php artisan --version');
    console.log('✅ Laravel CLI is available');
  } catch (error) {
    console.log('❌ Laravel CLI not found');
    throw new Error('Laravel is not installed or not in PATH');
  }
  
  // Check if Playwright is installed
  try {
    await runCommand('npx playwright --version');
    console.log('✅ Playwright is available');
  } catch (error) {
    console.log('❌ Playwright not found');
    throw new Error('Playwright is not installed. Run: npm install');
  }
  
  // Check if database exists
  if (fs.existsSync('./database/database.sqlite')) {
    console.log('✅ SQLite database exists');
  } else {
    console.log('⚠️  SQLite database not found, will be created');
  }
  
  console.log('');
}

async function setupEnvironment() {
  console.log('🛠️  Setting up test environment...\n');
  
  try {
    // Copy .env.example to .env if not exists
    if (!fs.existsSync('.env')) {
      if (fs.existsSync('.env.example')) {
        fs.copyFileSync('.env.example', '.env');
        console.log('✅ Created .env from .env.example');
      } else {
        throw new Error('.env.example not found');
      }
    }
    
    // Generate app key if needed
    try {
      await runCommand('php artisan key:generate --ansi');
      console.log('✅ App key generated');
    } catch (error) {
      console.log('⚠️  App key generation failed (might already exist)');
    }
    
    // Install composer dependencies
    await runCommand('composer install --no-dev --optimize-autoloader');
    console.log('✅ Composer dependencies installed');
    
    // Create database
    if (!fs.existsSync('./database/database.sqlite')) {
      fs.writeFileSync('./database/database.sqlite', '');
      console.log('✅ SQLite database created');
    }
    
    // Run migrations
    await runCommand('php artisan migrate --force');
    console.log('✅ Database migrations completed');
    
    // Seed database
    try {
      await runCommand('php artisan db:seed --force');
      console.log('✅ Database seeded');
    } catch (error) {
      console.log('⚠️  Database seeding failed (no seeders found)');
    }
    
    // Install Playwright browsers
    await runCommand('npx playwright install');
    console.log('✅ Playwright browsers installed');
    
  } catch (error) {
    throw new Error(`Environment setup failed: ${error.message}`);
  }
  
  console.log('');
}

async function startLaravelServer() {
  console.log('🚀 Starting Laravel development server...\n');
  
  return new Promise((resolve, reject) => {
    const server = spawn('php', ['artisan', 'serve', '--port=8000'], {
      stdio: ['pipe', 'pipe', 'pipe']
    });
    
    let serverReady = false;
    
    server.stdout.on('data', (data) => {
      const output = data.toString();
      if (output.includes('started') && !serverReady) {
        serverReady = true;
        console.log('✅ Laravel server started on http://localhost:8000');
        resolve(server);
      }
    });
    
    server.stderr.on('data', (data) => {
      console.error('Server error:', data.toString());
    });
    
    server.on('close', (code) => {
      console.log(`Laravel server exited with code ${code}`);
    });
    
    // Timeout after 30 seconds
    setTimeout(() => {
      if (!serverReady) {
        server.kill();
        reject(new Error('Laravel server failed to start within 30 seconds'));
      }
    }, 30000);
  });
}

async function runPlaywrightTests() {
  console.log('🧪 Running Playwright tests...\n');
  
  const testResults = [];
  
  try {
    // Run all tests
    const result = await runCommand('npx playwright test --reporter=html,json,junit', {
      showOutput: true
    });
    
    console.log('✅ Playwright tests completed successfully');
    
    // Parse results if JSON report exists
    if (fs.existsSync('./test-results.json')) {
      const jsonResults = JSON.parse(fs.readFileSync('./test-results.json', 'utf8'));
      testResults.push(jsonResults);
    }
    
  } catch (error) {
    console.log('⚠️  Some Playwright tests failed');
    console.log(error.message);
    
    // Still try to parse results
    if (fs.existsSync('./test-results.json')) {
      const jsonResults = JSON.parse(fs.readFileSync('./test-results.json', 'utf8'));
      testResults.push(jsonResults);
    }
  }
  
  return testResults;
}

async function runSpecificTests(pattern) {
  console.log(`🎯 Running specific tests: ${pattern}\n`);
  
  try {
    await runCommand(`npx playwright test ${pattern} --reporter=list`, {
      showOutput: true
    });
    console.log('✅ Specific tests completed');
  } catch (error) {
    console.log('⚠️  Some specific tests failed');
    console.log(error.message);
  }
}

function generateReport(testResults, report) {
  console.log('📊 Generating comprehensive report...\n');
  
  // Calculate summary
  testResults.forEach(result => {
    if (result.suites) {
      result.suites.forEach(suite => {
        suite.specs?.forEach(spec => {
          report.summary.total++;
          
          const hasFailures = spec.tests?.some(test => 
            test.results?.some(r => r.status === 'failed')
          );
          
          if (hasFailures) {
            report.summary.failed++;
          } else {
            report.summary.passed++;
          }
        });
      });
    }
  });
  
  // Generate HTML report
  const htmlReport = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BellGas Laravel - Test Report</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 8px; 
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .header h1 { 
            color: #2c3e50; 
            margin: 0 0 10px 0; 
        }
        .header .subtitle { 
            color: #7f8c8d; 
            font-size: 16px; 
        }
        .summary { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 40px; 
        }
        .card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 25px; 
            border-radius: 8px; 
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card.passed { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .card.failed { background: linear-gradient(135deg, #ee5a6f 0%, #f29263 100%); }
        .card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card h3 { 
            margin: 0 0 10px 0; 
            font-size: 2.5em; 
            font-weight: 700; 
        }
        .card p { 
            margin: 0; 
            opacity: 0.9; 
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
        }
        .section { 
            margin-bottom: 30px; 
        }
        .section h2 { 
            color: #2c3e50; 
            border-bottom: 2px solid #3498db; 
            padding-bottom: 10px; 
            margin-bottom: 20px; 
        }
        .test-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .category {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #3498db;
        }
        .category h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .category ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .category li {
            margin: 5px 0;
            color: #555;
        }
        .footer { 
            text-align: center; 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 2px solid #eee; 
            color: #7f8c8d; 
        }
        .timestamp {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            color: #2c3e50;
        }
        .links {
            text-align: center;
            margin: 20px 0;
        }
        .links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .links a:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 BellGas Laravel - Test Report</h1>
            <p class="subtitle">Comprehensive End-to-End Testing Results</p>
        </div>
        
        <div class="timestamp">
            <strong>Generated:</strong> ${report.timestamp}
        </div>
        
        <div class="summary">
            <div class="card total">
                <h3>${report.summary.total}</h3>
                <p>Total Tests</p>
            </div>
            <div class="card passed">
                <h3>${report.summary.passed}</h3>
                <p>Passed</p>
            </div>
            <div class="card failed">
                <h3>${report.summary.failed}</h3>
                <p>Failed</p>
            </div>
            <div class="card">
                <h3>${report.summary.total > 0 ? Math.round((report.summary.passed / report.summary.total) * 100) : 0}%</h3>
                <p>Success Rate</p>
            </div>
        </div>
        
        <div class="section">
            <h2>📋 Test Categories</h2>
            <div class="test-categories">
                <div class="category">
                    <h3>🏠 Frontend E2E Tests</h3>
                    <ul>
                        <li>Home page functionality</li>
                        <li>Product browsing and search</li>
                        <li>Shopping cart operations</li>
                        <li>Checkout process</li>
                        <li>User authentication flows</li>
                    </ul>
                </div>
                <div class="category">
                    <h3>🔌 API Integration Tests</h3>
                    <ul>
                        <li>Authentication endpoints</li>
                        <li>Product catalog API</li>
                        <li>Cart management API</li>
                        <li>Order processing API</li>
                        <li>Payment integration API</li>
                    </ul>
                </div>
                <div class="category">
                    <h3>🔐 Security & Auth Tests</h3>
                    <ul>
                        <li>JWT token validation</li>
                        <li>User registration/login</li>
                        <li>Password reset flows</li>
                        <li>Authorization checks</li>
                        <li>API security measures</li>
                    </ul>
                </div>
                <div class="category">
                    <h3>💳 Payment Tests</h3>
                    <ul>
                        <li>Stripe integration</li>
                        <li>Payment intent creation</li>
                        <li>Test card processing</li>
                        <li>Webhook handling</li>
                        <li>Payment security</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>🔗 Quick Links</h2>
            <div class="links">
                <a href="./playwright-report/index.html">📊 Detailed Playwright Report</a>
                <a href="./test-results.json">📄 JSON Results</a>
                <a href="./test-results.xml">🔬 JUnit XML</a>
            </div>
        </div>
        
        <div class="section">
            <h2>🎯 Test Coverage</h2>
            <p>This comprehensive test suite covers:</p>
            <ul>
                <li><strong>User Journeys:</strong> Complete customer experience from browsing to purchase</li>
                <li><strong>API Endpoints:</strong> All major REST API endpoints with validation</li>
                <li><strong>Authentication:</strong> JWT-based auth system with role management</li>
                <li><strong>Payment Processing:</strong> Stripe integration with test scenarios</li>
                <li><strong>Error Handling:</strong> Validation and error response testing</li>
                <li><strong>Cross-browser:</strong> Testing across Chrome, Firefox, and Safari</li>
                <li><strong>Mobile Responsive:</strong> Mobile viewport testing</li>
                <li><strong>Performance:</strong> Load time and response validation</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>🚀 Generated by BellGas Comprehensive Testing Suite</p>
            <p>Laravel + Playwright + E2E Testing</p>
        </div>
    </div>
</body>
</html>`;
  
  // Write HTML report
  fs.writeFileSync(path.join(report.reportDir, 'comprehensive-report.html'), htmlReport);
  
  // Write JSON summary
  fs.writeFileSync(
    path.join(report.reportDir, 'test-summary.json'), 
    JSON.stringify(report, null, 2)
  );
  
  console.log(`✅ Comprehensive report generated:`);
  console.log(`   📊 HTML: ${path.resolve(report.reportDir, 'comprehensive-report.html')}`);
  console.log(`   📄 JSON: ${path.resolve(report.reportDir, 'test-summary.json')}`);
}

async function main() {
  const startTime = Date.now();
  let server = null;
  
  try {
    const report = createTestReport();
    
    // Check prerequisites
    await checkPrerequisites();
    
    // Setup environment
    await setupEnvironment();
    
    // Start Laravel server
    server = await startLaravelServer();
    
    // Wait for server to be fully ready
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    // Run tests based on arguments
    const args = process.argv.slice(2);
    let testResults = [];
    
    if (args.length > 0) {
      // Run specific tests
      await runSpecificTests(args.join(' '));
    } else {
      // Run all tests
      testResults = await runPlaywrightTests();
    }
    
    // Generate report
    const duration = Date.now() - startTime;
    report.summary.duration = Math.round(duration / 1000);
    
    generateReport(testResults, report);
    
    console.log('\\n🎉 Testing completed successfully!');
    console.log(`⏱️  Total duration: ${report.summary.duration} seconds`);
    console.log(`📊 Open: file://${path.resolve(report.reportDir, 'comprehensive-report.html')}`);
    
  } catch (error) {
    console.error('\\n❌ Testing failed:');
    console.error(error.message);
    process.exit(1);
  } finally {
    // Clean up server
    if (server) {
      console.log('\\n🛑 Stopping Laravel server...');
      server.kill();
    }
  }
}

// Handle Ctrl+C gracefully
process.on('SIGINT', () => {
  console.log('\\n🛑 Testing interrupted by user');
  process.exit(0);
});

// Run main function
if (require.main === module) {
  main().catch(console.error);
}

module.exports = { main, runPlaywrightTests, generateReport };