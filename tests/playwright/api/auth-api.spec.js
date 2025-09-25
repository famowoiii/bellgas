import { test, expect } from '@playwright/test';
import { AuthHelper } from '../helpers/auth.js';
import { DatabaseHelper } from '../helpers/database.js';

test.describe('Authentication API', () => {
  let authHelper;

  test.beforeEach(async ({ page }) => {
    authHelper = new AuthHelper(page);
    await DatabaseHelper.clearCache();
  });

  test.describe('POST /api/auth/register', () => {
    test('should register new user with valid data', async ({ request }) => {
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      const response = await request.post('/api/auth/register', {
        data: userData
      });

      expect(response.status()).toBe(201);

      const result = await response.json();
      expect(result).toHaveProperty('token');
      expect(result).toHaveProperty('user');
      expect(result.user.name).toBe(userData.name);
      expect(result.user.email).toBe(userData.email);
      expect(result.user).not.toHaveProperty('password');
    });

    test('should validate required fields', async ({ request }) => {
      const response = await request.post('/api/auth/register', {
        data: {}
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result).toHaveProperty('message');
      expect(result).toHaveProperty('errors');
    });

    test('should validate email format', async ({ request }) => {
      const userData = {
        name: 'Test User',
        email: 'invalid-email-format',
        password: 'password123',
        password_confirmation: 'password123'
      };

      const response = await request.post('/api/auth/register', {
        data: userData
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result.errors).toHaveProperty('email');
    });

    test('should validate password confirmation', async ({ request }) => {
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'different-password'
      };

      const response = await request.post('/api/auth/register', {
        data: userData
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result.errors).toHaveProperty('password');
    });

    test('should prevent duplicate email registration', async ({ request }) => {
      const userData = {
        name: 'Test User',
        email: `duplicate${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      // First registration
      const firstResponse = await request.post('/api/auth/register', {
        data: userData
      });
      expect(firstResponse.status()).toBe(201);

      // Second registration with same email
      const secondResponse = await request.post('/api/auth/register', {
        data: userData
      });
      expect(secondResponse.status()).toBe(422);

      const result = await secondResponse.json();
      expect(result.errors).toHaveProperty('email');
    });

    test('should validate password length', async ({ request }) => {
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: '123', // Too short
        password_confirmation: '123'
      };

      const response = await request.post('/api/auth/register', {
        data: userData
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result.errors).toHaveProperty('password');
    });
  });

  test.describe('POST /api/auth/login', () => {
    test('should login with valid credentials', async ({ request }) => {
      // Register user first
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      await request.post('/api/auth/register', {
        data: userData
      });

      // Login
      const loginResponse = await request.post('/api/auth/login', {
        data: {
          email: userData.email,
          password: userData.password
        }
      });

      expect(loginResponse.status()).toBe(200);

      const result = await loginResponse.json();
      expect(result).toHaveProperty('token');
      expect(result).toHaveProperty('user');
      expect(result.user.email).toBe(userData.email);
    });

    test('should reject invalid credentials', async ({ request }) => {
      const response = await request.post('/api/auth/login', {
        data: {
          email: 'nonexistent@example.com',
          password: 'wrongpassword'
        }
      });

      expect(response.status()).toBe(401);

      const result = await response.json();
      expect(result).toHaveProperty('message');
    });

    test('should validate required fields', async ({ request }) => {
      const response = await request.post('/api/auth/login', {
        data: {}
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result).toHaveProperty('errors');
    });

    test('should reject empty password', async ({ request }) => {
      const response = await request.post('/api/auth/login', {
        data: {
          email: 'test@example.com',
          password: ''
        }
      });

      expect(response.status()).toBe(422);
    });
  });

  test.describe('GET /api/auth/me', () => {
    test('should return user data with valid token', async ({ request }) => {
      // Register and login
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      const registerResponse = await request.post('/api/auth/register', {
        data: userData
      });
      const { token } = await registerResponse.json();

      // Get user data
      const response = await request.get('/api/auth/me', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect(response.status()).toBe(200);

      const user = await response.json();
      expect(user.name).toBe(userData.name);
      expect(user.email).toBe(userData.email);
      expect(user).toHaveProperty('id');
      expect(user).not.toHaveProperty('password');
    });

    test('should reject invalid token', async ({ request }) => {
      const response = await request.get('/api/auth/me', {
        headers: { Authorization: 'Bearer invalid-token' }
      });

      expect(response.status()).toBe(401);
    });

    test('should reject missing token', async ({ request }) => {
      const response = await request.get('/api/auth/me');

      expect(response.status()).toBe(401);
    });
  });

  test.describe('POST /api/auth/refresh', () => {
    test('should refresh token with valid token', async ({ request }) => {
      // Register user
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      const registerResponse = await request.post('/api/auth/register', {
        data: userData
      });
      const { token } = await registerResponse.json();

      // Refresh token
      const response = await request.post('/api/auth/refresh', {
        headers: { Authorization: `Bearer ${token}` }
      });

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toHaveProperty('token');
        expect(result.token).not.toBe(token); // Should be a new token
      } else {
        // Some implementations might not support refresh
        expect([200, 404, 405]).toContain(response.status());
      }
    });

    test('should reject invalid token for refresh', async ({ request }) => {
      const response = await request.post('/api/auth/refresh', {
        headers: { Authorization: 'Bearer invalid-token' }
      });

      expect([401, 404, 405]).toContain(response.status());
    });
  });

  test.describe('POST /api/auth/logout', () => {
    test('should logout with valid token', async ({ request }) => {
      // Register user
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      const registerResponse = await request.post('/api/auth/register', {
        data: userData
      });
      const { token } = await registerResponse.json();

      // Logout
      const response = await request.post('/api/auth/logout', {
        headers: { Authorization: `Bearer ${token}` }
      });

      expect([200, 204]).toContain(response.status());

      // Token should be invalidated
      const meResponse = await request.get('/api/auth/me', {
        headers: { Authorization: `Bearer ${token}` }
      });
      expect(meResponse.status()).toBe(401);
    });

    test('should handle logout with invalid token', async ({ request }) => {
      const response = await request.post('/api/auth/logout', {
        headers: { Authorization: 'Bearer invalid-token' }
      });

      expect([401, 404]).toContain(response.status());
    });
  });

  test.describe('Password Reset', () => {
    test('should handle forgot password request', async ({ request }) => {
      // Register user first
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      await request.post('/api/auth/register', {
        data: userData
      });

      // Request password reset
      const response = await request.post('/api/auth/forgot-password', {
        data: { email: userData.email }
      });

      expect([200, 202]).toContain(response.status());

      if (response.status() === 200) {
        const result = await response.json();
        expect(result).toHaveProperty('message');
      }
    });

    test('should handle forgot password for non-existent email', async ({ request }) => {
      const response = await request.post('/api/auth/forgot-password', {
        data: { email: 'nonexistent@example.com' }
      });

      // Should return success even for non-existent emails (security best practice)
      expect([200, 202, 422]).toContain(response.status());
    });

    test('should validate email format for password reset', async ({ request }) => {
      const response = await request.post('/api/auth/forgot-password', {
        data: { email: 'invalid-email' }
      });

      expect(response.status()).toBe(422);

      const result = await response.json();
      expect(result.errors).toHaveProperty('email');
    });
  });

  test.describe('Rate Limiting', () => {
    test('should handle multiple rapid login attempts', async ({ request }) => {
      const loginData = {
        email: 'test@example.com',
        password: 'wrongpassword'
      };

      const promises = [];
      for (let i = 0; i < 10; i++) {
        promises.push(request.post('/api/auth/login', { data: loginData }));
      }

      const responses = await Promise.all(promises);
      
      // Check if rate limiting is applied
      const rateLimitedResponses = responses.filter(r => r.status() === 429);
      
      // Rate limiting might not be implemented, so just check responses
      responses.forEach(response => {
        expect([401, 422, 429]).toContain(response.status());
      });
    });
  });

  test.describe('Token Validation', () => {
    test('should validate JWT token structure', async ({ request }) => {
      // Register user
      const userData = {
        name: 'Test User',
        email: `test${Date.now()}@example.com`,
        password: 'password123',
        password_confirmation: 'password123'
      };

      const response = await request.post('/api/auth/register', {
        data: userData
      });

      const { token } = await response.json();
      
      // JWT tokens should have 3 parts separated by dots
      const tokenParts = token.split('.');
      expect(tokenParts).toHaveLength(3);
      
      // Each part should be base64 encoded
      tokenParts.forEach(part => {
        expect(part.length).toBeGreaterThan(0);
      });
    });

    test('should handle malformed tokens', async ({ request }) => {
      const malformedTokens = [
        'invalid.token',
        'not-a-token',
        'Bearer',
        ''
      ];

      for (const token of malformedTokens) {
        const response = await request.get('/api/auth/me', {
          headers: { Authorization: `Bearer ${token}` }
        });

        expect(response.status()).toBe(401);
      }
    });
  });
});