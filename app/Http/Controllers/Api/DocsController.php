<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    public function index()
    {
        $apiEndpoints = [
            'Authentication' => [
                'POST /api/auth/register' => 'Register a new user',
                'POST /api/auth/login' => 'Login user',
                'POST /api/auth/logout' => 'Logout user',
                'GET /api/auth/me' => 'Get current user info',
                'POST /api/auth/refresh' => 'Refresh JWT token',
                'POST /api/auth/forgot-password' => 'Send password reset link',
                'POST /api/auth/reset-password' => 'Reset password',
            ],
            'Products' => [
                'GET /api/products' => 'Get all products',
                'GET /api/products/{id}' => 'Get product by ID',
                'POST /api/products' => 'Create product (auth required)',
            ],
            'Categories' => [
                'GET /api/categories' => 'Get all categories',
                'GET /api/categories/{slug}' => 'Get category by slug',
                'GET /api/categories/{slug}/products' => 'Get products by category',
                'POST /api/admin/categories' => 'Create category (admin only)',
                'PUT /api/admin/categories/{id}' => 'Update category (admin only)',
                'DELETE /api/admin/categories/{id}' => 'Delete category (admin only)',
            ],
            'Shopping Cart' => [
                'GET /api/cart' => 'Get cart items',
                'POST /api/cart' => 'Add item to cart',
                'PUT /api/cart/{id}' => 'Update cart item quantity',
                'DELETE /api/cart/{id}' => 'Remove item from cart',
                'DELETE /api/cart' => 'Clear entire cart',
                'GET /api/cart/count' => 'Get cart items count',
            ],
            'Orders' => [
                'GET /api/orders' => 'Get user orders',
                'POST /api/orders' => 'Create new order',
                'GET /api/orders/{id}' => 'Get order details',
                'PUT /api/orders/{id}' => 'Update order status (admin only)',
                'PATCH /api/orders/{id}/cancel' => 'Cancel order',
                'POST /api/orders/{id}/reorder' => 'Reorder items',
                'GET /api/orders/admin/stats' => 'Get order statistics (admin only)',
            ],
            'Addresses' => [
                'GET /api/addresses' => 'Get user addresses',
                'POST /api/addresses' => 'Create new address',
                'GET /api/addresses/{id}' => 'Get address by ID',
                'PUT /api/addresses/{id}' => 'Update address',
                'DELETE /api/addresses/{id}' => 'Delete address',
            ],
            'Payments & Checkout' => [
                'POST /api/checkout/create-payment-intent' => 'Create Stripe payment intent',
                'POST /api/webhook/stripe' => 'Stripe webhook handler',
                'GET /api/receipts/order/{id}' => 'Get order receipt',
                'POST /api/receipts/email/{id}' => 'Email receipt to customer',
            ],
            'Testing' => [
                'GET /api/health' => 'Health check endpoint',
                'GET /api/test' => 'Test authenticated endpoint',
                'GET /api/test-stripe' => 'Test Stripe connection',
                'GET /api/stripe-test/cards' => 'Get test credit cards',
                'POST /api/stripe-test/simulate-payment' => 'Simulate payment success',
            ],
        ];

        return response()->json([
            'message' => 'BellGas E-commerce API Documentation',
            'version' => '1.0.0',
            'base_url' => url('/api'),
            'authentication' => [
                'type' => 'Bearer Token (JWT)',
                'header' => 'Authorization: Bearer {token}',
                'login_endpoint' => '/api/auth/login',
            ],
            'endpoints' => $apiEndpoints,
            'notes' => [
                'All protected endpoints require authentication via JWT token',
                'Admin-only endpoints require user to have admin role',
                'All responses are in JSON format',
                'Date formats are in ISO 8601 (Y-m-d\\TH:i:s.u\\Z)',
                'Errors return appropriate HTTP status codes with error messages',
            ],
            'example_requests' => [
                'register' => [
                    'url' => url('/api/auth/register'),
                    'method' => 'POST',
                    'body' => [
                        'email' => 'user@example.com',
                        'password' => 'password123',
                        'password_confirmation' => 'password123',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'phone_number' => '+1234567890'
                    ]
                ],
                'login' => [
                    'url' => url('/api/auth/login'),
                    'method' => 'POST',
                    'body' => [
                        'email' => 'user@example.com',
                        'password' => 'password123'
                    ]
                ],
                'add_to_cart' => [
                    'url' => url('/api/cart'),
                    'method' => 'POST',
                    'headers' => ['Authorization: Bearer {token}'],
                    'body' => [
                        'product_variant_id' => 1,
                        'quantity' => 2
                    ]
                ],
                'create_order' => [
                    'url' => url('/api/orders'),
                    'method' => 'POST',
                    'headers' => ['Authorization: Bearer {token}'],
                    'body' => [
                        'address_id' => 1,
                        'payment_method' => 'CARD',
                        'notes' => 'Please call when you arrive'
                    ]
                ]
            ]
        ]);
    }
}