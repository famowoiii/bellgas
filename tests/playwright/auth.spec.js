const { test, expect } = require('@playwright/test');

test.describe('Authentication System', () => {

  test.describe('Login Page', () => {
    
    test('should load login page correctly', async ({ page }) => {
      await page.goto('/login');
      
      await expect(page).toHaveTitle(/Login - BellGas/);
      await expect(page.locator('h2')).toContainText('Sign in to your account');
    });

    test('should have all required form elements', async ({ page }) => {
      await page.goto('/login');
      
      // Check form elements
      await expect(page.locator('input[type="email"]')).toBeVisible();
      await expect(page.locator('input[type="password"]')).toBeVisible();
      await expect(page.locator('input[type="checkbox"]')).toBeVisible(); // Remember me
      await expect(page.locator('button[type="submit"]')).toBeVisible();
      
      // Check links
      await expect(page.locator('a[href="/register"]')).toBeVisible();
      await expect(page.locator('a[href="/forgot-password"]')).toBeVisible();
    });

    test('should show demo credentials', async ({ page }) => {
      await page.goto('/login');
      
      // Check demo credentials section
      await expect(page.locator('text=Demo Credentials')).toBeVisible();
      await expect(page.locator('text=stripetester@bellgas.com')).toBeVisible();
      await expect(page.locator('text=admin@bellgas.com')).toBeVisible();
    });

    test('should fill demo credentials when clicked', async ({ page }) => {
      await page.goto('/login');
      
      // Click fill customer demo
      await page.locator('text=Fill Customer').click();
      
      // Verify fields are filled
      await expect(page.locator('input[type="email"]')).toHaveValue('stripetester@bellgas.com');
      await expect(page.locator('input[type="password"]')).toHaveValue('password123');
    });

    test('should show password toggle', async ({ page }) => {
      await page.goto('/login');
      
      const passwordInput = page.locator('input[type="password"]');
      const toggleButton = page.locator('button:has(.fa-eye)');
      
      // Initially password is hidden
      await expect(passwordInput).toHaveAttribute('type', 'password');
      
      // Click toggle to show password
      await toggleButton.click();
      await expect(page.locator('input[type="text"]')).toBeVisible();
    });

    test('should handle form validation', async ({ page }) => {
      await page.goto('/login');
      
      // Try to submit empty form
      await page.locator('button[type="submit"]').click();
      
      // Browser validation should prevent submission
      const emailInput = page.locator('input[type="email"]');
      await expect(emailInput).toHaveAttribute('required');
    });

    test('should simulate successful login', async ({ page }) => {
      // Mock API response
      await page.route('**/api/auth/login', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Login successful',
            user: {
              id: 1,
              email: 'stripetester@bellgas.com',
              first_name: 'Stripe',
              last_name: 'Tester',
              role: 'CUSTOMER'
            },
            access_token: 'mock_token_123',
            token_type: 'bearer',
            expires_in: 3600
          })
        });
      });

      await page.goto('/login');
      
      // Fill and submit form
      await page.fill('input[type="email"]', 'stripetester@bellgas.com');
      await page.fill('input[type="password"]', 'password123');
      await page.click('button[type="submit"]');
      
      // Should redirect to dashboard
      await page.waitForURL('**/dashboard');
    });

    test('should handle login error', async ({ page }) => {
      // Mock error response
      await page.route('**/api/auth/login', async route => {
        await route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Invalid email or password'
          })
        });
      });

      await page.goto('/login');
      
      // Fill and submit form with wrong credentials
      await page.fill('input[type="email"]', 'wrong@email.com');
      await page.fill('input[type="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      
      // Should show error message
      await expect(page.locator('text=Login Failed')).toBeVisible();
    });

  });

  test.describe('Register Page', () => {
    
    test('should load register page correctly', async ({ page }) => {
      await page.goto('/register');
      
      await expect(page).toHaveTitle(/Register - BellGas/);
      await expect(page.locator('h2')).toContainText('Create your account');
    });

    test('should have all required form elements', async ({ page }) => {
      await page.goto('/register');
      
      // Check form elements
      await expect(page.locator('input[name="first_name"]')).toBeVisible();
      await expect(page.locator('input[name="last_name"]')).toBeVisible();
      await expect(page.locator('input[name="email"]')).toBeVisible();
      await expect(page.locator('input[name="phone_number"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
      
      // Check role selection
      await expect(page.locator('input[value="CUSTOMER"]')).toBeVisible();
      await expect(page.locator('input[value="MERCHANT"]')).toBeVisible();
      
      // Check terms checkbox
      await expect(page.locator('input[name="agree_terms"]')).toBeVisible();
    });

    test('should select account type', async ({ page }) => {
      await page.goto('/register');
      
      const customerOption = page.locator('label:has(input[value="CUSTOMER"])');
      const merchantOption = page.locator('label:has(input[value="MERCHANT"])');
      
      // Click merchant option
      await merchantOption.click();
      await expect(page.locator('input[value="MERCHANT"]')).toBeChecked();
      
      // Click customer option
      await customerOption.click();
      await expect(page.locator('input[value="CUSTOMER"]')).toBeChecked();
    });

    test('should show password strength requirements', async ({ page }) => {
      await page.goto('/register');
      
      const passwordInput = page.locator('input[name="password"]');
      await expect(passwordInput).toHaveAttribute('placeholder', /min\. 8 characters/);
    });

    test('should simulate successful registration', async ({ page }) => {
      // Mock API response
      await page.route('**/api/auth/register', async route => {
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'User registered successfully',
            user: {
              id: 2,
              email: 'newuser@example.com',
              first_name: 'New',
              last_name: 'User',
              role: 'CUSTOMER'
            },
            access_token: 'mock_token_456',
            token_type: 'bearer',
            expires_in: 3600
          })
        });
      });

      await page.goto('/register');
      
      // Fill form
      await page.fill('input[name="first_name"]', 'New');
      await page.fill('input[name="last_name"]', 'User');
      await page.fill('input[name="email"]', 'newuser@example.com');
      await page.fill('input[name="phone_number"]', '0412345678');
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="password_confirmation"]', 'password123');
      await page.check('input[name="agree_terms"]');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Should redirect to dashboard
      await page.waitForURL('**/dashboard');
    });

    test('should show validation errors', async ({ page }) => {
      // Mock validation error response
      await page.route('**/api/auth/register', async route => {
        await route.fulfill({
          status: 422,
          contentType: 'application/json',
          body: JSON.stringify({
            message: 'Validation failed',
            errors: {
              email: ['The email field is required.'],
              password: ['The password must be at least 8 characters.']
            }
          })
        });
      });

      await page.goto('/register');
      
      // Submit incomplete form
      await page.check('input[name="agree_terms"]');
      await page.click('button[type="submit"]');
      
      // Should show validation errors
      await expect(page.locator('text=The email field is required')).toBeVisible();
      await expect(page.locator('text=The password must be at least 8 characters')).toBeVisible();
    });

    test('should show account benefits', async ({ page }) => {
      await page.goto('/register');
      
      // Check benefits section
      await expect(page.locator('text=Why Create an Account?')).toBeVisible();
      await expect(page.locator('text=Track your orders')).toBeVisible();
      await expect(page.locator('text=Save delivery addresses')).toBeVisible();
      await expect(page.locator('text=Faster checkout process')).toBeVisible();
      await expect(page.locator('text=Access to special offers')).toBeVisible();
    });

  });

  test.describe('Navigation with Auth State', () => {
    
    test('should show different navigation when logged in', async ({ page }) => {
      // Mock authenticated state
      await page.addInitScript(() => {
        localStorage.setItem('access_token', 'mock_token');
        window.mockUser = {
          id: 1,
          first_name: 'Test',
          last_name: 'User',
          email: 'test@example.com',
          role: 'CUSTOMER'
        };
      });

      // Mock the auth check API
      await page.route('**/api/auth/me', async route => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            user: {
              id: 1,
              first_name: 'Test',
              last_name: 'User',
              email: 'test@example.com',
              role: 'CUSTOMER'
            }
          })
        });
      });

      await page.goto('/');
      
      // Wait for auth state to load
      await page.waitForTimeout(1000);
      
      // Should show user menu instead of login/register
      await expect(page.locator('text=Test')).toBeVisible();
      await expect(page.locator('a[href="/login"]')).toBeHidden();
    });

  });

});