import { expect } from '@playwright/test';

export class AuthHelper {
  constructor(page) {
    this.page = page;
    this.baseURL = process.env.APP_URL || 'http://localhost:8000';
  }

  async registerUser(userData = {}) {
    const defaultData = {
      first_name: 'Test',
      last_name: 'User',
      email: `test${Date.now()}@example.com`,
      password: 'password123',
      password_confirmation: 'password123'
    };

    const data = { ...defaultData, ...userData };

    const response = await this.page.request.post(`${this.baseURL}/api/auth/register`, {
      data: data,
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
    });

    expect(response.status()).toBe(201);
    const result = await response.json();
    return { user: result.user, token: result.access_token, userData: data };
  }

  async loginUser(email, password) {
    const response = await this.page.request.post(`${this.baseURL}/api/auth/login`, {
      data: { email, password },
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
    });

    expect(response.status()).toBe(200);
    const result = await response.json();
    return { user: result.user, token: result.access_token };
  }

  async loginAsAdmin() {
    // Create admin user first
    const adminData = {
      first_name: 'Admin',
      last_name: 'User',
      email: `admin${Date.now()}@example.com`,
      password: 'admin123456',
      password_confirmation: 'admin123456'
    };

    const { token } = await this.registerUser(adminData);

    // Assign admin role via API or database
    await this.page.request.post(`${this.baseURL}/api/test/assign-role`, {
      headers: { Authorization: `Bearer ${token}`, 'Accept': 'application/json' },
      data: { role: 'admin' }
    });

    return await this.loginUser(adminData.email, adminData.password);
  }

  async setAuthToken(token) {
    await this.page.addInitScript((token) => {
      localStorage.setItem('access_token', token);
      localStorage.setItem('auth_token', token);
      localStorage.setItem('user_authenticated', 'true');
    }, token);
  }

  async logout() {
    await this.page.goto('/logout');
    await this.page.evaluate(() => {
      localStorage.removeItem('access_token');
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_authenticated');
    });
  }

  async isAuthenticated() {
    return await this.page.evaluate(() => {
      return localStorage.getItem('access_token') !== null ||
             localStorage.getItem('auth_token') !== null;
    });
  }
}
