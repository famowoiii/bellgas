# 🧪 BellGas Laravel - Comprehensive Testing Guide

## 📋 Overview

This document provides a complete guide for testing the BellGas Laravel e-commerce application using Playwright for end-to-end testing and API integration testing.

## 🚀 Quick Start

### Prerequisites

- **PHP 8.2+** with Laravel framework
- **Node.js 18+** and npm
- **Composer** for PHP dependencies
- **SQLite** database (default configuration)

### Installation

1. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Set up environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

3. **Install Playwright browsers:**
   ```bash
   npm run install:browsers
   ```

### Running Tests

#### 🎯 Complete Test Suite
```bash
npm test
```
This runs all tests and generates comprehensive reports.

#### 🔍 Specific Test Categories
```bash
# Frontend E2E Tests
npm run test:e2e

# API Integration Tests
npm run test:api

# Authentication Tests
npm run test:auth

# Product Tests
npm run test:products

# Shopping Cart Tests
npm run test:cart

# Checkout Process Tests
npm run test:checkout

# Home Page Tests
npm run test:home
```

#### 🌐 Browser-Specific Testing
```bash
# Chrome/Chromium
npm run test:chromium

# Firefox
npm run test:firefox

# Safari/WebKit
npm run test:webkit

# Mobile Testing
npm run test:mobile
```

#### 🛠️ Development & Debugging
```bash
# Debug mode (opens browser)
npm run test:debug

# Headed mode (see browser)
npm run test:headed

# Quick smoke tests
npm run test:smoke

# View reports
npm run report
```

## 📊 Test Categories

### 🏠 Frontend E2E Tests

**Location:** `tests/playwright/e2e/`

- **Home Page** (`home.spec.js`)
  - Page loading and navigation
  - Responsive design testing
  - Search functionality
  - Featured products display

- **Authentication** (`auth.spec.js`)
  - User registration flow
  - Login/logout functionality
  - Password reset process
  - Session management

- **Products** (`products.spec.js`)
  - Product listing and pagination
  - Product detail views
  - Search and filtering
  - Category navigation

- **Shopping Cart** (`cart.spec.js`)
  - Add/remove items
  - Quantity updates
  - Cart persistence
  - Clear cart functionality

- **Checkout** (`checkout.spec.js`)
  - Billing information forms
  - Payment method selection
  - Order placement
  - Stripe integration testing

### 🔌 API Integration Tests

**Location:** `tests/playwright/api/`

- **Authentication API** (`auth-api.spec.js`)
  - User registration endpoint
  - JWT authentication
  - Token validation
  - Password reset API

- **Products API** (`products-api.spec.js`)
  - Product CRUD operations
  - Category management
  - Search and filtering
  - Data validation

- **Cart API** (`cart-api.spec.js`)
  - Cart operations
  - Item management
  - User-specific carts
  - Business logic validation

- **Orders API** (`orders-api.spec.js`)
  - Order creation
  - Order management
  - Status updates
  - Order history

- **Payments API** (`payments-api.spec.js`)
  - Stripe integration
  - Payment intent creation
  - Webhook handling
  - Test card processing

## 🛠️ Test Architecture

### Helper Classes

**Location:** `tests/playwright/helpers/`

- **AuthHelper** (`auth.js`)
  - User registration/login utilities
  - Token management
  - Admin user creation

- **DatabaseHelper** (`database.js`)
  - Database reset/seeding
  - Cache clearing
  - Test data creation

### Page Object Models

**Location:** `tests/playwright/pages/`

- **HomePage** - Home page interactions
- **AuthPages** - Login/Register/ForgotPassword pages
- **ProductPages** - Product list and detail pages
- **CartPage** - Shopping cart operations
- **CheckoutPage** - Checkout process handling

## 📈 Test Reports

### Comprehensive HTML Report
After running tests, open:
```
test-reports/comprehensive-report.html
```

### Playwright Native Reports
```bash
npm run report
```

### Report Files Generated
- **HTML Report:** `playwright-report/index.html`
- **JSON Results:** `test-results.json`
- **JUnit XML:** `test-results.xml`
- **Comprehensive Report:** `test-reports/comprehensive-report.html`

## 🔧 Configuration

### Playwright Config
**File:** `playwright.config.js`

Key configurations:
- **Base URL:** `http://localhost:8000`
- **Browsers:** Chrome, Firefox, Safari, Mobile
- **Reporters:** HTML, JSON, JUnit
- **Retries:** 2 attempts for failed tests
- **Screenshots:** On failure
- **Video:** On failure

### Environment Variables
**File:** `.env`

Important settings for testing:
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## 🚨 Test Data Management

### Database Seeding
Tests use fresh database state:
```bash
php artisan migrate:fresh --seed
```

### Test User Creation
The `AuthHelper` creates unique test users:
```javascript
const { user, token } = await authHelper.registerUser({
  name: 'Test User',
  email: `test${Date.now()}@example.com`,
  password: 'password123'
});
```

## 🔐 Security Testing

### Authentication Tests
- JWT token validation
- Password strength requirements
- Session management
- Unauthorized access prevention

### API Security Tests
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

## 💳 Payment Testing

### Stripe Test Cards
The tests use Stripe test card numbers:

- **Valid Card:** `4242424242424242`
- **Declined Card:** `4000000000000002`
- **Expired Card:** `4000000000000069`

### Payment Scenarios
- Successful payments
- Failed payments
- Webhook handling
- Payment intent creation

## 📱 Cross-Platform Testing

### Desktop Browsers
- **Chrome/Chromium:** Latest stable
- **Firefox:** Latest stable  
- **Safari/WebKit:** Latest stable

### Mobile Testing
- **Mobile Chrome:** Pixel 5 simulation
- **Mobile Safari:** iPhone 12 simulation

### Responsive Testing
- Desktop: 1920x1080
- Tablet: 768x1024
- Mobile: 375x667

## 🐛 Debugging Tests

### Debug Mode
```bash
npm run test:debug
```
Opens Playwright Inspector for step-by-step debugging.

### Screenshots & Videos
Failed tests automatically capture:
- Screenshots at failure point
- Video recordings of test execution
- Network activity logs

### Console Logs
Tests check for JavaScript errors:
```javascript
page.on('console', msg => {
  if (msg.type() === 'error') {
    console.error('Browser error:', msg.text());
  }
});
```

## 🎯 Best Practices

### Test Structure
1. **Arrange:** Set up test data
2. **Act:** Perform actions
3. **Assert:** Verify results

### Data Management
- Use unique data for each test
- Clean up after tests
- Avoid test interdependencies

### Error Handling
- Graceful failure handling
- Meaningful error messages
- Proper timeout management

### Performance
- Parallel test execution
- Efficient selectors
- Minimal wait times

## 📋 Continuous Integration

### GitHub Actions
Example workflow:
```yaml
name: E2E Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm ci
      - run: npm test
      - uses: actions/upload-artifact@v3
        with:
          name: test-reports
          path: test-reports/
```

### Test Commands for CI
```bash
# Headless testing for CI
npx playwright test --reporter=github

# Generate reports
node run-tests.js

# Upload artifacts
tar -czf test-reports.tar.gz test-reports/
```

## 🔍 Troubleshooting

### Common Issues

**1. Server Not Starting**
```bash
# Check if port is available
lsof -i :8000

# Start server manually
php artisan serve --port=8000
```

**2. Database Issues**
```bash
# Reset database
php artisan migrate:fresh --seed

# Check database file
ls -la database/database.sqlite
```

**3. Browser Issues**
```bash
# Reinstall browsers
npx playwright install --force
```

**4. Permission Issues**
```bash
# Fix file permissions
chmod +x run-tests.js
chmod 755 tests/
```

### Log Files
Check these logs for debugging:
- **Laravel Logs:** `storage/logs/laravel.log`
- **Test Output:** Console output during test runs
- **Browser Console:** Captured in test reports

## 📚 Additional Resources

### Documentation
- [Playwright Documentation](https://playwright.dev)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Stripe Testing](https://stripe.com/docs/testing)

### Test Examples
All test files include comprehensive examples of:
- API testing patterns
- E2E testing workflows
- Error handling strategies
- Data validation techniques

---

## 🎉 Success Metrics

A successful test run should show:
- ✅ **95%+** test pass rate
- 🚀 **< 5 minutes** total execution time
- 📊 **Comprehensive coverage** across all features
- 🔒 **Security validations** passing
- 💳 **Payment integrations** working

Happy Testing! 🧪✨