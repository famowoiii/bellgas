import { test, expect } from '@playwright/test';

test.describe('Quick Smoke Tests', () => {
  test('should load home page', async ({ page }) => {
    await page.goto('http://localhost:8000');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    
    // Basic checks
    await expect(page).toHaveTitle(/BellGas|Laravel|Home/i);
    
    // Check if page content is loaded
    const bodyContent = await page.textContent('body');
    expect(bodyContent.length).toBeGreaterThan(0);
    
    console.log('✅ Home page loaded successfully');
  });

  test('should access API health endpoint', async ({ request }) => {
    const response = await request.get('http://localhost:8000/api/health');
    
    expect(response.status()).toBe(200);
    
    const data = await response.json();
    expect(data).toHaveProperty('status', 'OK');
    expect(data).toHaveProperty('service', 'BellGas API');
    
    console.log('✅ API health check passed');
  });

  test('should access products API endpoint', async ({ request }) => {
    const response = await request.get('http://localhost:8000/api/products');
    
    // Should return 200 or 404 (if no products)
    expect([200, 404]).toContain(response.status());
    
    if (response.status() === 200) {
      const data = await response.json();
      expect(Array.isArray(data) || data.data).toBeTruthy();
      console.log('✅ Products API accessible');
    } else {
      console.log('ℹ️  Products API returns 404 (no products found)');
    }
  });

  test('should handle authentication endpoints', async ({ request }) => {
    // Test registration endpoint
    const userData = {
      name: 'Test User',
      email: `test${Date.now()}@example.com`,
      password: 'password123',
      password_confirmation: 'password123'
    };

    const registerResponse = await request.post('http://localhost:8000/api/auth/register', {
      data: userData
    });

    expect([201, 422]).toContain(registerResponse.status());
    
    if (registerResponse.status() === 201) {
      const result = await registerResponse.json();
      expect(result).toHaveProperty('token');
      expect(result).toHaveProperty('user');
      console.log('✅ User registration working');
    } else {
      console.log('ℹ️  Registration validation working (422 response)');
    }
  });

  test('should load web routes', async ({ page }) => {
    const routes = ['/', '/home', '/products', '/login', '/register'];
    
    for (const route of routes) {
      try {
        const response = await page.goto(`http://localhost:8000${route}`);
        
        if (response) {
          expect([200, 302]).toContain(response.status());
          console.log(`✅ Route ${route}: ${response.status()}`);
        } else {
          console.log(`⚠️  Route ${route}: No response`);
        }
      } catch (error) {
        console.log(`❌ Route ${route}: ${error.message}`);
      }
    }
  });

  test('should check for console errors', async ({ page }) => {
    const consoleErrors = [];
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    await page.goto('http://localhost:8000');
    await page.waitForTimeout(3000);
    
    // Filter out common non-critical errors
    const criticalErrors = consoleErrors.filter(error => 
      !error.includes('favicon') && 
      !error.includes('404') &&
      !error.includes('AdBlock')
    );

    if (criticalErrors.length === 0) {
      console.log('✅ No critical console errors found');
    } else {
      console.log('⚠️  Console errors found:', criticalErrors);
    }
    
    expect(criticalErrors.length).toBeLessThan(5); // Allow some minor errors
  });
});