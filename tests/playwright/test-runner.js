#!/usr/bin/env node

/**
 * Advanced Test Runner for BellGas Laravel
 * Provides flexible test execution with detailed reporting
 */

import { test as base, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.js';
import { DatabaseHelper } from './helpers/database.js';

// Extend base test with custom fixtures
export const test = base.extend({
  // Auto-setup database for each test
  database: async ({}, use) => {
    await DatabaseHelper.clearCache();
    await use(DatabaseHelper);
  },

  // Auto-setup authentication helper
  authHelper: async ({ page }, use) => {
    const authHelper = new AuthHelper(page);
    await use(authHelper);
  },

  // Auto-setup authenticated user
  authenticatedUser: async ({ page }, use) => {
    const authHelper = new AuthHelper(page);
    const { user, token } = await authHelper.registerUser();
    await authHelper.setAuthToken(token);
    await use({ user, token, authHelper });
  },

  // Auto-setup admin user
  adminUser: async ({ page }, use) => {
    const authHelper = new AuthHelper(page);
    try {
      const { user, token } = await authHelper.loginAsAdmin();
      await authHelper.setAuthToken(token);
      await use({ user, token, authHelper });
    } catch (error) {
      console.log('Admin setup failed, using regular user:', error.message);
      const { user, token } = await authHelper.registerUser();
      await authHelper.setAuthToken(token);
      await use({ user, token, authHelper });
    }
  }
});

export { expect };

// Custom test categories
export const apiTest = test.extend({
  // API tests don't need page interactions
  page: async ({ browser }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // Set up API testing specific configurations
    page.setDefaultTimeout(10000);
    
    await use(page);
    await context.close();
  }
});

export const e2eTest = test.extend({
  // E2E tests with enhanced setup
  page: async ({ browser }, use) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // Enhanced logging for E2E tests
    page.on('console', msg => {
      if (msg.type() === 'error') {
        console.error('Browser Console Error:', msg.text());
      }
    });
    
    // Network logging
    page.on('response', response => {
      if (response.status() >= 400) {
        console.log(`❌ HTTP ${response.status()}: ${response.url()}`);
      }
    });
    
    await use(page);
    await context.close();
  }
});

// Test utilities
export class TestUtils {
  static async waitForLaravel(page) {
    // Wait for Laravel to be ready
    let retries = 10;
    while (retries > 0) {
      try {
        const response = await page.request.get('/api/health');
        if (response.status() === 200) {
          return true;
        }
      } catch (error) {
        console.log(`Waiting for Laravel... (${retries} retries left)`);
      }
      
      await page.waitForTimeout(2000);
      retries--;
    }
    
    throw new Error('Laravel server is not responding');
  }

  static async createTestProduct(request, token) {
    const productData = {
      name: `Test Product ${Date.now()}`,
      description: 'A test product for automated testing',
      price: Math.floor(Math.random() * 1000) / 10, // Random price
      category_id: 1,
      stock_quantity: 100,
      sku: `TEST-${Date.now()}`
    };

    const response = await request.post('/api/products', {
      data: productData,
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.status() === 201) {
      return await response.json();
    }

    return null;
  }

  static async addToCart(request, token, productId, quantity = 1) {
    const response = await request.post('/api/cart', {
      data: { product_id: productId, quantity },
      headers: { Authorization: `Bearer ${token}` }
    });

    return response.status() === 200 || response.status() === 201;
  }

  static async createTestOrder(request, token, items = null) {
    const orderData = {
      items: items || [
        { product_id: 1, quantity: 1, price: 50.00 }
      ],
      shipping_address: {
        street: '123 Test Street',
        city: 'Melbourne',
        state: 'VIC',
        postcode: '3000',
        country: 'Australia'
      },
      billing_address: {
        street: '123 Test Street',
        city: 'Melbourne',
        state: 'VIC',
        postcode: '3000',
        country: 'Australia'
      }
    };

    const response = await request.post('/api/orders', {
      data: orderData,
      headers: { Authorization: `Bearer ${token}` }
    });

    if ([200, 201].includes(response.status())) {
      return await response.json();
    }

    return null;
  }

  static generateTestUserData(suffix = '') {
    const timestamp = Date.now();
    return {
      name: `Test User ${suffix}${timestamp}`,
      email: `test${suffix}${timestamp}@example.com`,
      password: 'password123',
      password_confirmation: 'password123'
    };
  }

  static generateTestAddress() {
    return {
      street: '123 Test Street',
      city: 'Melbourne',
      state: 'VIC',
      postcode: '3000',
      country: 'Australia'
    };
  }

  static async takeScreenshotOnFailure(page, testInfo) {
    if (testInfo.status !== testInfo.expectedStatus) {
      const screenshot = await page.screenshot();
      await testInfo.attach('screenshot', { body: screenshot, contentType: 'image/png' });
    }
  }

  static async logNetworkActivity(page) {
    const networkLogs = [];
    
    page.on('request', request => {
      networkLogs.push({
        type: 'request',
        url: request.url(),
        method: request.method(),
        timestamp: new Date().toISOString()
      });
    });

    page.on('response', response => {
      networkLogs.push({
        type: 'response',
        url: response.url(),
        status: response.status(),
        timestamp: new Date().toISOString()
      });
    });

    return networkLogs;
  }

  static async checkAccessibility(page) {
    // Basic accessibility checks
    const issues = [];

    // Check for alt text on images
    const imagesWithoutAlt = await page.locator('img:not([alt])').count();
    if (imagesWithoutAlt > 0) {
      issues.push(`${imagesWithoutAlt} images without alt text`);
    }

    // Check for form labels
    const inputsWithoutLabels = await page.locator('input:not([aria-label]):not([aria-labelledby])').count();
    if (inputsWithoutLabels > 0) {
      issues.push(`${inputsWithoutLabels} inputs without proper labels`);
    }

    // Check for heading structure
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').count();
    if (headings === 0) {
      issues.push('No heading elements found');
    }

    return issues;
  }

  static async validatePagePerformance(page) {
    // Basic performance checks
    const performanceMetrics = await page.evaluate(() => {
      const navigation = performance.getEntriesByType('navigation')[0];
      return {
        loadTime: navigation.loadEventEnd - navigation.loadEventStart,
        domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
        firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || null
      };
    });

    return performanceMetrics;
  }
}

// Custom matchers
export const customMatchers = {
  async toBeValidEmail(received) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const pass = emailRegex.test(received);
    
    return {
      message: () => `Expected ${received} to be a valid email address`,
      pass
    };
  },

  async toBeValidPrice(received) {
    const pass = typeof received === 'number' && received >= 0;
    
    return {
      message: () => `Expected ${received} to be a valid price (non-negative number)`,
      pass
    };
  },

  async toHaveValidJWTStructure(received) {
    const parts = received.split('.');
    const pass = parts.length === 3 && parts.every(part => part.length > 0);
    
    return {
      message: () => `Expected ${received} to be a valid JWT token`,
      pass
    };
  }
};

// Global test configuration
export const testConfig = {
  // Test data
  testUsers: {
    admin: {
      email: 'admin@bellgas.test',
      password: 'admin123'
    },
    customer: {
      email: 'customer@bellgas.test', 
      password: 'customer123'
    }
  },

  // API endpoints
  endpoints: {
    auth: {
      register: '/api/auth/register',
      login: '/api/auth/login',
      me: '/api/auth/me',
      logout: '/api/auth/logout'
    },
    products: {
      list: '/api/products',
      show: '/api/products/{id}',
      create: '/api/products'
    },
    cart: {
      get: '/api/cart',
      add: '/api/cart',
      update: '/api/cart/{id}',
      remove: '/api/cart/{id}',
      clear: '/api/cart'
    }
  },

  // Test timeouts
  timeouts: {
    short: 5000,
    medium: 10000,
    long: 30000,
    veryLong: 60000
  },

  // Expected response times (ms)
  performance: {
    apiResponse: 2000,
    pageLoad: 5000,
    imageLoad: 3000
  }
};

// Export all utilities
export default {
  test,
  expect,
  apiTest,
  e2eTest,
  TestUtils,
  customMatchers,
  testConfig
};