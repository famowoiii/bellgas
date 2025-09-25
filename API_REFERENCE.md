# BellGas API Reference

## 📋 API Overview

**Base URL**: `http://localhost:8000/api` (Development)  
**Content-Type**: `application/json`  
**Authentication**: JWT Bearer Token  

### Standard Response Format
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... },
  "errors": null
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## 🔐 Authentication Endpoints

### POST /auth/register
Register a new user account.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe", 
  "email": "john@example.com",
  "phone_number": "0412345678",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "CUSTOMER"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone_number": "0412345678",
      "role": "CUSTOMER",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### POST /auth/login
Authenticate user and receive access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "role": "CUSTOMER"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### GET /auth/me
Get current authenticated user information.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone_number": "0412345678",
      "role": "CUSTOMER",
      "is_active": true,
      "email_verified_at": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

### POST /auth/refresh
Refresh JWT token.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "user": { ... },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### POST /auth/logout
Logout user and invalidate token.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 📦 Product Endpoints

### GET /products
Get list of all active products with pagination.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `category_id` (optional): Filter by category ID
- `search` (optional): Search term

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "9kg LPG Cylinder",
        "slug": "9kg-lpg-cylinder",
        "description": "Standard 9kg LPG gas cylinder for home use",
        "is_active": true,
        "category": {
          "id": 1,
          "name": "Refill",
          "slug": "refill"
        },
        "variants": [
          {
            "id": 1,
            "name": "9kg Standard",
            "weight_kg": 9.0,
            "price_aud": 45.99,
            "stock_quantity": 50,
            "is_active": true
          }
        ],
        "photos": [
          {
            "id": 1,
            "url": "/storage/products/cylinder-9kg.jpg",
            "alt_text": "9kg LPG Cylinder",
            "is_primary": true
          }
        ]
      }
    ],
    "per_page": 15,
    "total": 25
  }
}
```

### GET /products/{product}
Get detailed information about a specific product.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "9kg LPG Cylinder",
      "slug": "9kg-lpg-cylinder",
      "description": "Standard 9kg LPG gas cylinder for home use",
      "is_active": true,
      "category": { ... },
      "variants": [ ... ],
      "photos": [ ... ],
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

### GET /products/categories
Get all product categories.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Refill",
        "slug": "refill",
        "description": "Gas cylinder refills",
        "is_active": true,
        "products_count": 10
      },
      {
        "id": 2,
        "name": "Full Tank",
        "slug": "full-tank", 
        "description": "Complete gas cylinders",
        "is_active": true,
        "products_count": 8
      }
    ]
  }
}
```

### POST /products
Create a new product (Merchant/Admin only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "15kg LPG Cylinder",
  "description": "Large 15kg LPG gas cylinder for commercial use",
  "category_id": 2,
  "variants": [
    {
      "name": "15kg Commercial",
      "weight_kg": 15.0,
      "price_aud": 89.99,
      "stock_quantity": 25
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "product": { ... }
  }
}
```

---

## 🛒 Shopping Cart Endpoints

### GET /cart
Get current user's cart items.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "price": 45.99,
        "effective_price": 45.99,
        "is_preorder": false,
        "reserved_until": "2024-01-01T01:00:00.000000Z",
        "notes": null,
        "total": 91.98,
        "stock_available": true,
        "reservation_expired": false,
        "productVariant": {
          "id": 1,
          "name": "9kg Standard",
          "weight_kg": 9.0,
          "price_aud": 45.99,
          "product": {
            "id": 1,
            "name": "9kg LPG Cylinder",
            "photos": [
              {
                "url": "/storage/products/cylinder-9kg.jpg"
              }
            ]
          }
        }
      }
    ],
    "total": 91.98,
    "count": 1
  }
}
```

### POST /cart
Add item to cart.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "product_variant_id": 1,
  "quantity": 2,
  "is_preorder": false,
  "notes": "Handle with care"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Item added to cart successfully",
  "data": {
    "id": 1,
    "quantity": 2,
    "price": 45.99,
    "is_preorder": false,
    "reserved_until": "2024-01-01T01:00:00.000000Z",
    "notes": "Handle with care",
    "total": 91.98,
    "productVariant": { ... }
  }
}
```

### PUT /cart/{id}
Update cart item quantity.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "quantity": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Cart item updated",
  "data": { ... }
}
```

### DELETE /cart/{id}
Remove specific item from cart.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Item removed from cart"
}
```

### DELETE /cart
Clear entire cart.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Cart cleared"
}
```

### GET /cart/count
Get cart items count.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "count": 3
  }
}
```

---

## 📋 Order Endpoints

### GET /orders
Get user's order history with pagination.

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page  
- `status` (optional): Filter by order status

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_number": "BG-2024-001",
        "status": "COMPLETED",
        "fulfillment_method": "DELIVERY",
        "subtotal_aud": 91.98,
        "shipping_cost_aud": 9.95,
        "total_aud": 101.93,
        "customer_notes": "Leave at front door",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "items": [
          {
            "id": 1,
            "quantity": 2,
            "unit_price_aud": 45.99,
            "total_price_aud": 91.98,
            "productVariant": {
              "name": "9kg Standard",
              "product": {
                "name": "9kg LPG Cylinder"
              }
            }
          }
        ],
        "address": {
          "id": 1,
          "name": "Home Address",
          "street_address": "123 Main Street",
          "suburb": "Sydney",
          "state": "NSW",
          "postcode": "2000",
          "full_address": "123 Main Street, Sydney NSW 2000"
        }
      }
    ],
    "per_page": 10,
    "total": 5
  }
}
```

### POST /orders
Create a new order from current cart.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "address_id": 1,
  "payment_method": "CARD",
  "fulfillment_method": "DELIVERY",
  "notes": "Please call before delivery"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "BG-2024-001",
      "status": "UNPAID",
      "fulfillment_method": "DELIVERY",
      "total_aud": 101.93,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "items": [ ... ],
      "address": { ... }
    }
  }
}
```

### GET /orders/{order}
Get detailed information about a specific order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "order_number": "BG-2024-001",
      "status": "PROCESSING",
      "fulfillment_method": "DELIVERY",
      "subtotal_aud": 91.98,
      "shipping_cost_aud": 9.95,
      "total_aud": 101.93,
      "stripe_payment_intent_id": "pi_1234567890",
      "customer_notes": "Please call before delivery",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "items": [ ... ],
      "address": { ... },
      "events": [
        {
          "id": 1,
          "event_type": "ORDER_CREATED",
          "description": "Order created successfully",
          "created_at": "2024-01-01T00:00:00.000000Z"
        },
        {
          "id": 2,
          "event_type": "PAYMENT_COMPLETED",
          "description": "Payment processed successfully",
          "created_at": "2024-01-01T00:05:00.000000Z"
        }
      ]
    }
  }
}
```

### PUT /orders/{order}
Update order status (Admin only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "PROCESSING",
  "notes": "Order being prepared for delivery"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Order updated successfully",
  "data": {
    "order": { ... }
  }
}
```

### PATCH /orders/{order}/cancel
Cancel an order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Order cancelled successfully"
}
```

### POST /orders/{order}/reorder
Add all items from previous order to cart.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Items added to cart successfully",
  "data": {
    "cart_items": [ ... ],
    "cart_total": 101.93
  }
}
```

---

## 🏠 Address Endpoints

### GET /addresses
Get user's saved addresses.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Addresses retrieved successfully",
  "data": [
    {
      "id": 1,
      "type": "HOME",
      "name": "Home Address",
      "street_address": "123 Main Street",
      "suburb": "Sydney",
      "state": "NSW",
      "postcode": "2000",
      "country": "Australia",
      "delivery_instructions": "Leave at front door",
      "is_default": true,
      "full_address": "123 Main Street, Sydney NSW 2000",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### POST /addresses
Create a new address.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "type": "HOME",
  "name": "Home Address",
  "street_address": "123 Main Street",
  "suburb": "Sydney",
  "state": "NSW",
  "postcode": "2000",
  "country": "Australia",
  "delivery_instructions": "Leave at front door",
  "is_default": true
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Address created successfully",
  "data": { ... }
}
```

### PUT /addresses/{address}
Update an existing address.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Updated Home Address",
  "delivery_instructions": "Ring doorbell twice"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Address updated successfully",
  "data": { ... }
}
```

### DELETE /addresses/{address}
Delete an address.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Address deleted successfully"
}
```

---

## 💳 Payment Endpoints

### POST /checkout/create-payment-intent
Create Stripe payment intent for order.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "order_id": 1
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_1234567890_secret_abc123",
    "payment_intent_id": "pi_1234567890",
    "amount": 10193,
    "currency": "aud"
  }
}
```

### POST /payments/orders/{order}/intent
Create payment intent for specific order.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_1234567890_secret_abc123",
    "publishable_key": "pk_test_...",
    "amount": 10193,
    "currency": "aud"
  }
}
```

### POST /payments/orders/{order}/complete
Complete payment and update order status.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "payment_intent_id": "pi_1234567890",
  "payment_method_id": "pm_1234567890"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment completed successfully",
  "data": {
    "order": { ... },
    "payment_status": "succeeded"
  }
}
```

### POST /payments/orders/{order}/simulate
Simulate test payment (Development only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "success": true,
  "payment_method": "test_card_visa"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Test payment simulated successfully",
  "data": {
    "order": { ... },
    "simulation_result": "success"
  }
}
```

---

## 🎫 Pickup Endpoints

### POST /pickup/generate/{order}
Generate pickup token for order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "pickup_token": "ABC123DEF456",
    "qr_code_url": "/storage/qr-codes/ABC123DEF456.png",
    "expires_at": "2024-01-02T00:00:00.000000Z"
  }
}
```

### GET /pickup/token/{order}
Get pickup token for order.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "pickup_token": "ABC123DEF456",
    "qr_code_url": "/storage/qr-codes/ABC123DEF456.png",
    "expires_at": "2024-01-02T00:00:00.000000Z",
    "is_expired": false
  }
}
```

### POST /pickup/verify
Verify pickup token (Staff only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "pickup_token": "ABC123DEF456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Pickup token verified successfully",
  "data": {
    "order": { ... },
    "customer": { ... },
    "valid": true
  }
}
```

### GET /pickup/pending
Get pending pickup orders (Staff only).

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "pending_pickups": [
      {
        "order_id": 1,
        "order_number": "BG-2024-001",
        "customer_name": "John Doe",
        "pickup_token": "ABC123DEF456",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  }
}
```

---

## 👑 Admin Endpoints

### GET /admin/dashboard
Get admin dashboard statistics.

**Headers:**
```
Authorization: Bearer <token>
Role: ADMIN or MERCHANT
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "stats": {
      "total_users": 150,
      "active_users": 145,
      "total_products": 25,
      "active_products": 24,
      "total_categories": 5,
      "active_categories": 5,
      "total_orders": 89,
      "pending_orders": 12,
      "processing_orders": 8,
      "completed_orders": 65,
      "total_revenue": 15750.50,
      "today_revenue": 350.75,
      "this_month_revenue": 8920.25
    },
    "user": { ... },
    "timestamp": "2024-01-01T00:00:00.000000Z"
  }
}
```

### GET /admin/dashboard/recent-orders
Get recent orders for admin dashboard.

**Headers:**
```
Authorization: Bearer <token>
Role: ADMIN or MERCHANT
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "BG-2024-001",
        "status": "PROCESSING",
        "total_aud": 101.93,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "user": {
          "first_name": "John",
          "last_name": "Doe",
          "email": "john@example.com"
        },
        "items": [ ... ]
      }
    ]
  }
}
```

### GET /admin/dashboard/sales-chart
Get sales chart data.

**Headers:**
```
Authorization: Bearer <token>
Role: ADMIN or MERCHANT
```

**Query Parameters:**
- `period` (optional): Days to include (default: 30)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "period": "30 days",
    "sales": [
      {
        "date": "2024-01-01",
        "revenue": 450.75,
        "orders": 8
      },
      {
        "date": "2024-01-02", 
        "revenue": 320.50,
        "orders": 6
      }
    ]
  }
}
```

### GET /admin/dashboard/top-products
Get top selling products.

**Headers:**
```
Authorization: Bearer <token>
Role: ADMIN or MERCHANT
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "9kg LPG Cylinder",
        "total_sold": 45,
        "total_revenue": 2069.55
      }
    ]
  }
}
```

---

## ⚠️ Error Codes

### HTTP Status Codes

- **200 OK**: Request successful
- **201 Created**: Resource created successfully  
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### Custom Error Messages

#### Authentication Errors
```json
{
  "success": false,
  "message": "Token has expired",
  "errors": {
    "token": ["JWT token has expired"]
  }
}
```

#### Validation Errors
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Permission Errors
```json
{
  "success": false,
  "message": "Access denied. Insufficient permissions.",
  "errors": {
    "role": ["Admin or Merchant role required"]
  }
}
```

---

## 🔧 Development Notes

### Rate Limiting
- Authentication endpoints: 5 attempts per minute
- General API endpoints: 60 requests per minute
- Admin endpoints: 100 requests per minute

### Testing
All endpoints support test mode with special test data:

**Test Users:**
- Customer: `stripetester@bellgas.com` / `password123`
- Admin: `admin@bellgas.com` / `admin123`

**Test Cards:**
- Success: `4242 4242 4242 4242`
- Declined: `4000 0000 0000 0002`

### Webhooks
Stripe webhooks are handled at:
- Production: `POST /webhook/stripe`
- Development: `POST /webhook/stripe-test`

---

**BellGas API Reference v1.0**  
*Last Updated: December 2024*