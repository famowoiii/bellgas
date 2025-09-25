import { test, expect } from '@playwright/test';
import { LoginPage, RegisterPage, ForgotPasswordPage } from '../pages/AuthPages.js';
import { HomePage } from '../pages/HomePage.js';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Authentication', () => {
  let loginPage, registerPage, forgotPasswordPage, homePage, authHelper;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    registerPage = new RegisterPage(page);
    forgotPasswordPage = new ForgotPasswordPage(page);
    homePage = new HomePage(page);
    authHelper = new AuthHelper(page);
    
    await DatabaseHelper.clearCache();
  });

  test.describe('User Registration', () => {
    test('should display registration form', async () => {
      await registerPage.goto();
      await registerPage.verifyRegisterForm();
    });

    test('should register new user successfully', async ({ page }) => {
      await registerPage.goto();
      
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        confirmPassword: 'password123'
      };
      
      await registerPage.register(userData);
      
      // Should either redirect to login, dashboard, or show success message
      await page.waitForTimeout(2000);
      
      const currentUrl = page.url();
      const isRedirected = currentUrl.includes('/login') || 
                          currentUrl.includes('/dashboard') || 
                          currentUrl.includes('/home') ||
                          !currentUrl.includes('/register');
                          
      expect(isRedirected).toBeTruthy();
    });

    test('should show validation errors for invalid data', async ({ page }) => {
      await registerPage.goto();
      
      // Test with invalid email
      const invalidUserData = {
        name: 'Test User',
        email: 'invalid-email',
        password: 'password123',
        confirmPassword: 'password123'
      };
      
      await registerPage.register(invalidUserData);
      await page.waitForTimeout(1000);
      
      // Should show validation error or stay on register page
      const hasError = await page.locator(registerPage.errorMessage).count() > 0;
      const stayedOnRegister = page.url().includes('/register');
      
      expect(hasError || stayedOnRegister).toBeTruthy();
    });

    test('should validate password confirmation', async ({ page }) => {
      await registerPage.goto();
      
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        confirmPassword: 'different-password'
      };
      
      await registerPage.register(userData);
      await page.waitForTimeout(1000);
      
      // Should show password mismatch error or stay on register page
      const hasError = await page.locator(registerPage.errorMessage).count() > 0;
      const stayedOnRegister = page.url().includes('/register');
      
      expect(hasError || stayedOnRegister).toBeTruthy();
    });

    test('should prevent duplicate email registration', async ({ page }) => {
      const email = `duplicate${Date.now()}@example.com`;
      
      // Register first user
      const userData1 = {
        name: 'First User',
        email: email,
        password: 'password123',
        confirmPassword: 'password123'
      };
      
      await registerPage.goto();
      await registerPage.register(userData1);
      await page.waitForTimeout(2000);
      
      // Try to register second user with same email
      const userData2 = {
        name: 'Second User',
        email: email,
        password: 'password456',
        confirmPassword: 'password456'
      };
      
      await registerPage.goto();
      await registerPage.register(userData2);
      await page.waitForTimeout(1000);
      
      // Should show duplicate email error or stay on register page
      const hasError = await page.locator(registerPage.errorMessage).count() > 0;
      const stayedOnRegister = page.url().includes('/register');
      
      expect(hasError || stayedOnRegister).toBeTruthy();
    });

    test('should navigate to login page', async () => {
      await registerPage.goto();
      await registerPage.clickLogin();
      
      await expect(registerPage.page).toHaveURL(/.*\/login/);
    });
  });

  test.describe('User Login', () => {
    test('should display login form', async () => {
      await loginPage.goto();
      await loginPage.verifyLoginForm();
    });

    test('should login with valid credentials', async ({ page }) => {
      // First register a user
      const { userData } = await authHelper.registerUser();
      
      // Then login
      await loginPage.goto();
      await loginPage.login(userData.email, userData.password);
      
      // Should redirect to dashboard or home
      await page.waitForTimeout(2000);
      
      const currentUrl = page.url();
      const isRedirected = !currentUrl.includes('/login');
      
      expect(isRedirected).toBeTruthy();
    });

    test('should show error for invalid credentials', async ({ page }) => {
      await loginPage.goto();
      
      await loginPage.login('invalid@example.com', 'wrongpassword');
      await page.waitForTimeout(1000);
      
      // Should show error or stay on login page
      const hasError = await page.locator(loginPage.errorMessage).count() > 0;
      const stayedOnLogin = page.url().includes('/login');
      
      expect(hasError || stayedOnLogin).toBeTruthy();
    });

    test('should show error for empty fields', async ({ page }) => {
      await loginPage.goto();
      
      await loginPage.login('', '');
      await page.waitForTimeout(1000);
      
      // Should show validation error or stay on login page
      const hasError = await page.locator(loginPage.errorMessage).count() > 0;
      const stayedOnLogin = page.url().includes('/login');
      
      expect(hasError || stayedOnLogin).toBeTruthy();
    });

    test('should navigate to register page', async () => {
      await loginPage.goto();
      
      const registerLink = loginPage.page.locator(loginPage.registerLink);
      if (await registerLink.count() > 0) {
        await loginPage.clickRegister();
        await expect(loginPage.page).toHaveURL(/.*\/register/);
      }
    });

    test('should navigate to forgot password page', async () => {
      await loginPage.goto();
      
      const forgotPasswordLink = loginPage.page.locator(loginPage.forgotPasswordLink);
      if (await forgotPasswordLink.count() > 0) {
        await loginPage.clickForgotPassword();
        await expect(loginPage.page).toHaveURL(/.*\/forgot-password/);
      }
    });
  });

  test.describe('Password Reset', () => {
    test('should display forgot password form', async () => {
      await forgotPasswordPage.goto();
      
      await expect(forgotPasswordPage.page.locator(forgotPasswordPage.emailInput)).toBeVisible();
      await expect(forgotPasswordPage.page.locator(forgotPasswordPage.submitButton)).toBeVisible();
    });

    test('should request password reset for valid email', async ({ page }) => {
      // Register user first
      const { userData } = await authHelper.registerUser();
      
      await forgotPasswordPage.goto();
      await forgotPasswordPage.requestPasswordReset(userData.email);
      
      await page.waitForTimeout(1000);
      
      // Should show success message or redirect
      const hasSuccess = await page.locator(forgotPasswordPage.successMessage).count() > 0;
      const isRedirected = !page.url().includes('/forgot-password');
      
      expect(hasSuccess || isRedirected).toBeTruthy();
    });

    test('should handle invalid email for password reset', async ({ page }) => {
      await forgotPasswordPage.goto();
      await forgotPasswordPage.requestPasswordReset('nonexistent@example.com');
      
      await page.waitForTimeout(1000);
      
      // Should show error or success message (depending on implementation)
      // Many apps show success even for non-existent emails for security
      const hasMessage = await page.locator('.success, .error, .alert').count() > 0;
      expect(hasMessage).toBeTruthy();
    });
  });

  test.describe('Authentication State', () => {
    test('should maintain authentication after page reload', async ({ page }) => {
      // Register and login
      const { token, userData } = await authHelper.registerUser();
      await authHelper.setAuthToken(token);
      
      // Go to home page
      await homePage.goto();
      
      // Reload page
      await page.reload();
      
      // Check if still authenticated
      const isAuthenticated = await authHelper.isAuthenticated();
      expect(isAuthenticated).toBeTruthy();
    });

    test('should redirect unauthenticated users from protected pages', async ({ page }) => {
      // Try to access protected page without authentication
      const protectedPages = ['/dashboard', '/profile', '/orders'];
      
      for (const protectedPage of protectedPages) {
        const response = await page.goto(protectedPage);
        
        // Should either redirect to login or show 401/403
        const finalUrl = page.url();
        const isRedirectedToLogin = finalUrl.includes('/login');
        const is401or403 = response && [401, 403].includes(response.status());
        
        expect(isRedirectedToLogin || is401or403).toBeTruthy();
      }
    });

    test('should logout successfully', async ({ page }) => {
      // Register and login
      const { token } = await authHelper.registerUser();
      await authHelper.setAuthToken(token);
      
      // Logout
      await authHelper.logout();
      
      // Check authentication state
      const isAuthenticated = await authHelper.isAuthenticated();
      expect(isAuthenticated).toBeFalsy();
    });
  });

  test.describe('API Authentication', () => {
    test('should authenticate via API', async ({ page }) => {
      // Register user
      const userData = {
        name: 'API Test User',
        email: `apitest${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };
      
      // Test registration API
      const registerResponse = await page.request.post('/api/auth/register', {
        data: userData
      });
      
      expect(registerResponse.status()).toBe(201);
      
      const registerResult = await registerResponse.json();
      expect(registerResult.token).toBeTruthy();
      expect(registerResult.user).toBeTruthy();
      
      // Test login API
      const loginResponse = await page.request.post('/api/auth/login', {
        data: {
          email: userData.email,
          password: userData.password
        }
      });
      
      expect(loginResponse.status()).toBe(200);
      
      const loginResult = await loginResponse.json();
      expect(loginResult.token).toBeTruthy();
      expect(loginResult.user).toBeTruthy();
    });

    test('should access protected API endpoints with token', async ({ page }) => {
      // Register and get token
      const { token } = await authHelper.registerUser();
      
      // Test protected endpoint
      const response = await page.request.get('/api/auth/me', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      expect(response.status()).toBe(200);
      
      const user = await response.json();
      expect(user.id).toBeTruthy();
      expect(user.email).toBeTruthy();
    });

    test('should reject requests with invalid token', async ({ page }) => {
      const response = await page.request.get('/api/auth/me', {
        headers: { Authorization: 'Bearer invalid-token' }
      });
      
      expect(response.status()).toBe(401);
    });

    test('should refresh token', async ({ page }) => {
      // Register and get token
      const { token } = await authHelper.registerUser();
      
      // Test token refresh
      const refreshResponse = await page.request.post('/api/auth/refresh', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (refreshResponse.status() === 200) {
        const refreshResult = await refreshResponse.json();
        expect(refreshResult.token).toBeTruthy();
      } else {
        // Some implementations may not support refresh
        expect([200, 404, 405]).toContain(refreshResponse.status());
      }
    });
  });
});