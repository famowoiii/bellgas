import { expect } from '@playwright/test';

export class LoginPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.emailInput = 'input[name="email"], input[type="email"]';
    this.passwordInput = 'input[name="password"], input[type="password"]';
    this.loginButton = 'button[type="submit"], button:has-text("Login"), button:has-text("Sign In")';
    this.forgotPasswordLink = 'a:has-text("Forgot Password"), a:has-text("Forgot your password")';
    this.registerLink = 'a:has-text("Register"), a:has-text("Sign Up"), a[href*="register"]';
    this.errorMessage = '.error, .alert-danger, [data-testid="error"]';
    this.successMessage = '.success, .alert-success, [data-testid="success"]';
  }

  async goto() {
    await this.page.goto('/login');
    await this.page.waitForLoadState('networkidle');
  }

  async login(email, password) {
    await this.page.fill(this.emailInput, email);
    await this.page.fill(this.passwordInput, password);
    await this.page.click(this.loginButton);
    await this.page.waitForLoadState('networkidle');
  }

  async clickForgotPassword() {
    await this.page.click(this.forgotPasswordLink);
    await this.page.waitForURL('**/forgot-password**');
  }

  async clickRegister() {
    await this.page.click(this.registerLink);
    await this.page.waitForURL('**/register**');
  }

  async verifyLoginForm() {
    await expect(this.page.locator(this.emailInput)).toBeVisible();
    await expect(this.page.locator(this.passwordInput)).toBeVisible();
    await expect(this.page.locator(this.loginButton)).toBeVisible();
  }

  async verifyErrorMessage(message) {
    await expect(this.page.locator(this.errorMessage)).toContainText(message);
  }

  async verifyLoginSuccess() {
    // Should redirect to dashboard or home
    await this.page.waitForURL(url => !url.includes('/login'));
  }
}

export class RegisterPage {
  constructor(page) {
    this.page = page;

    // Selectors
    this.firstNameInput = 'input[name="first_name"]';
    this.lastNameInput = 'input[name="last_name"]';
    this.phoneInput = 'input[name="phone_number"]';
    this.emailInput = 'input[name="email"], input[type="email"]';
    this.passwordInput = 'input[name="password"]';
    this.confirmPasswordInput = 'input[name="password_confirmation"]';
    this.registerButton = 'button[type="submit"]';
    this.loginLink = 'a:has-text("Login"), a:has-text("Sign In"), a[href*="login"]';
    this.termsCheckbox = 'input[name="agree_terms"]';
    this.errorMessage = '[x-show="generalError"], .error, .alert-danger, [data-testid="error"]';
  }

  async goto() {
    await this.page.goto('/register');
    await this.page.waitForLoadState('domcontentloaded');
  }

  async register(userData) {
    const { name, first_name, last_name, email, password, confirmPassword = password, phone = '+61400000000', acceptTerms = true } = userData;

    // Support both name (split) and first_name/last_name
    const firstName = first_name || (name ? name.split(' ')[0] : 'Test');
    const lastName = last_name || (name ? name.split(' ').slice(1).join(' ') || 'User' : 'User');

    await this.page.fill(this.firstNameInput, firstName);
    await this.page.fill(this.lastNameInput, lastName);
    await this.page.fill(this.emailInput, email);

    if (await this.page.locator(this.phoneInput).count() > 0) {
      await this.page.fill(this.phoneInput, phone);
    }

    await this.page.fill(this.passwordInput, password);
    if (await this.page.locator(this.confirmPasswordInput).count() > 0) {
      await this.page.fill(this.confirmPasswordInput, confirmPassword);
    }

    if (acceptTerms) {
      const termsBox = this.page.locator(this.termsCheckbox);
      if (await termsBox.count() > 0 && !(await termsBox.isChecked())) {
        await termsBox.check();
      }
    }

    await this.page.click(this.registerButton);
    await this.page.waitForTimeout(2000);
  }

  async clickLogin() {
    await this.page.click(this.loginLink);
    await this.page.waitForURL('**/login**');
  }

  async verifyRegisterForm() {
    await expect(this.page.locator(this.firstNameInput)).toBeVisible();
    await expect(this.page.locator(this.emailInput)).toBeVisible();
    await expect(this.page.locator(this.passwordInput)).toBeVisible();
    await expect(this.page.locator(this.registerButton)).toBeVisible();
  }

  async verifyErrorMessage(message) {
    await expect(this.page.locator(this.errorMessage)).toContainText(message);
  }

  async verifyRegistrationSuccess() {
    // Should redirect to login or dashboard
    await this.page.waitForURL(url => !url.includes('/register'));
  }
}

export class ForgotPasswordPage {
  constructor(page) {
    this.page = page;
    
    // Selectors
    this.emailInput = 'input[name="email"], input[type="email"]';
    this.submitButton = 'button[type="submit"], button:has-text("Send"), button:has-text("Reset")';
    this.backToLoginLink = 'a:has-text("Login"), a:has-text("Back"), a[href*="login"]';
    this.successMessage = '.success, .alert-success, [data-testid="success"]';
    this.errorMessage = '.error, .alert-danger, [data-testid="error"]';
  }

  async goto() {
    await this.page.goto('/forgot-password');
    await this.page.waitForLoadState('domcontentloaded');
    await this.page.waitForTimeout(500); // Wait for Alpine.js to initialize
  }

  async requestPasswordReset(email) {
    await this.page.fill(this.emailInput, email);
    await this.page.click(this.submitButton);
    await this.page.waitForTimeout(3000); // Wait for Axios response
  }

  async verifySuccessMessage() {
    await expect(this.page.locator(this.successMessage)).toBeVisible();
  }
}