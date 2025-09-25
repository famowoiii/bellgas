# BellGas API Endpoints Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All API endpoints require JWT authentication unless otherwise specified.

**Header Format:**
```
Authorization: Bearer {jwt_token}
```

## Authentication Endpoints

### Register User
**POST** `/auth/register`

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe", 
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone_number": "+61412345678"
}
```

**Response (Success):**
```json
{
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone_number": "+61412345678",
        "role": "CUSTOMER",
        "is_active": true
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### Login
**POST** `/auth/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (Success):**
```json
{
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "role": "CUSTOMER"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### Logout
**POST** `/auth/logout`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "message": "Successfully logged out"
}
```

### Refresh Token
**POST** `/auth/refresh`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### Get Current User
**GET** `/auth/me`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone_number": "+61412345678",
    "role": "CUSTOMER",
    "is_active": true
}
```

## Product Endpoints

### Get All Products
**GET** `/products`

**Query Parameters:**
- `category` (optional): Filter by category (REFILL, FULL_TANK)
- `active` (optional): Filter by active status (true/false)

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "LPG Full Tank",
            "slug": "lpg-full-tank",
            "description": "New 9kg LPG gas cylinder",
            "category": "FULL_TANK",
            "is_active": true,
            "variants": [
                {
                    "id": 1,
                    "name": "9kg",
                    "price_aud": "35.00",
                    "is_active": true
                }
            ],
            "photos": [
                {
                    "id": 1,
                    "image_path": "/storage/products/lpg-tank.jpg",
                    "is_primary": true
                }
            ]
        }
    ]
}
```

### Get Single Product
**GET** `/products/{slug}`

**Response:** Same as single product object from above

## Checkout Endpoints

### Create Payment Intent
**POST** `/checkout/create-payment-intent`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "fulfillment_method": "PICKUP",
    "items": [
        {
            "product_variant_id": 1,
            "quantity": 2
        },
        {
            "product_variant_id": 3,
            "quantity": 1
        }
    ],
    "address_id": 1,
    "customer_notes": "Please call when ready"
}
```

**Validation Rules:**
- `fulfillment_method`: required, must be "PICKUP" or "DELIVERY"
- `items`: required array with minimum 1 item
- `items.*.product_variant_id`: required, must exist in database
- `items.*.quantity`: required integer, min 1, max 10
- `address_id`: required for DELIVERY, optional for PICKUP
- `customer_notes`: optional string, max 1000 characters

**Response (Success):**
```json
{
    "client_secret": "pi_3OxYZ1234567890_secret_ABC123",
    "order": {
        "id": 15,
        "order_number": "BG-ABCD1234",
        "user_id": 1,
        "address_id": 1,
        "status": "PENDING",
        "fulfillment_method": "PICKUP",
        "subtotal_aud": "70.00",
        "shipping_cost_aud": "0.00",
        "total_aud": "70.00",
        "customer_notes": "Please call when ready",
        "items": [
            {
                "id": 25,
                "product_variant_id": 1,
                "quantity": 2,
                "unit_price_aud": "35.00",
                "total_price_aud": "70.00",
                "product_variant": {
                    "id": 1,
                    "name": "9kg",
                    "price_aud": "35.00",
                    "product": {
                        "id": 1,
                        "name": "LPG Full Tank",
                        "category": "FULL_TANK"
                    }
                }
            }
        ]
    }
}
```

**Response (Validation Error):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "fulfillment_method": [
            "Refill products can only be picked up at the store. Please select 'PICKUP' for: LPG Gas Refill - 9kg"
        ],
        "address_id": [
            "Selected address is not valid."
        ]
    }
}
```

### Confirm Payment
**POST** `/checkout/confirm-payment`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "payment_intent_id": "pi_3OxYZ1234567890",
    "payment_method_id": "pm_1234567890abcdef"
}
```

**Response (Success):**
```json
{
    "success": true,
    "order": {
        "id": 15,
        "order_number": "BG-ABCD1234",
        "status": "PAID",
        "paid_at": "2025-09-15T10:30:45Z",
        "stripe_payment_intent_id": "pi_3OxYZ1234567890",
        "payment_method": "card"
    }
}
```

**Response (Payment Failed):**
```json
{
    "success": false,
    "message": "Payment failed",
    "error": "Your card was declined."
}
```

## Address Endpoints

### Get User Addresses
**GET** `/addresses`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "type": "HOME",
            "first_name": "John",
            "last_name": "Doe",
            "company_name": null,
            "street_address": "123 Main Street",
            "suburb": "Melbourne",
            "state": "VIC",
            "postcode": "3000",
            "country": "Australia",
            "phone_number": "+61412345678",
            "is_default": true
        }
    ]
}
```

### Create Address
**POST** `/addresses`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "type": "HOME",
    "first_name": "John",
    "last_name": "Doe",
    "street_address": "123 Main Street",
    "suburb": "Melbourne", 
    "state": "VIC",
    "postcode": "3000",
    "country": "Australia",
    "phone_number": "+61412345678",
    "is_default": false
}
```

**Response:**
```json
{
    "data": {
        "id": 2,
        "user_id": 1,
        "type": "HOME",
        "first_name": "John",
        "last_name": "Doe",
        "street_address": "123 Main Street",
        "suburb": "Melbourne",
        "state": "VIC", 
        "postcode": "3000",
        "country": "Australia",
        "phone_number": "+61412345678",
        "is_default": false
    }
}
```

## Order Endpoints

### Get User Orders
**GET** `/orders`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by order status
- `per_page` (optional): Number of results per page (default: 15)

**Response:**
```json
{
    "data": [
        {
            "id": 15,
            "order_number": "BG-ABCD1234",
            "status": "PAID",
            "fulfillment_method": "PICKUP",
            "total_aud": "70.00",
            "paid_at": "2025-09-15T10:30:45Z",
            "customer_notes": "Please call when ready",
            "created_at": "2025-09-15T10:25:30Z",
            "items": [
                {
                    "id": 25,
                    "quantity": 2,
                    "unit_price_aud": "35.00",
                    "total_price_aud": "70.00",
                    "product_variant": {
                        "name": "9kg",
                        "product": {
                            "name": "LPG Full Tank"
                        }
                    }
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### Get Single Order
**GET** `/orders/{order_number}`

**Headers:** `Authorization: Bearer {token}`

**Response:** Same as single order object from above

## Admin Endpoints

*Note: These endpoints require admin or staff role*

### Get All Orders (Admin)
**GET** `/admin/orders`

**Headers:** `Authorization: Bearer {admin_token}`

**Query Parameters:**
- `status` (optional): Filter by status
- `user_id` (optional): Filter by user
- `fulfillment_method` (optional): Filter by fulfillment method
- `per_page` (optional): Results per page

### Update Order Status (Admin)
**PATCH** `/admin/orders/{order_number}/status`

**Headers:** `Authorization: Bearer {admin_token}`

**Request Body:**
```json
{
    "status": "CONFIRMED"
}
```

**Valid Status Transitions:**
- PENDING → PAID (via payment)
- PAID → CONFIRMED
- CONFIRMED → READY_FOR_PICKUP (pickup orders)
- CONFIRMED → OUT_FOR_DELIVERY (delivery orders)
- READY_FOR_PICKUP → COMPLETED
- OUT_FOR_DELIVERY → DELIVERED
- DELIVERED → COMPLETED
- Any status → CANCELLED

## Error Response Format

All endpoints return errors in this format:

**422 Validation Error:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Field specific error message"
        ]
    }
}
```

**401 Unauthorized:**
```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden:**
```json
{
    "message": "This action is unauthorized."
}
```

**404 Not Found:**
```json
{
    "message": "Resource not found."
}
```

**500 Server Error:**
```json
{
    "message": "Internal server error.",
    "error": "Detailed error message"
}
```

## Rate Limiting

- **General API**: 60 requests per minute per IP
- **Authentication**: 5 requests per minute per IP
- **Payment endpoints**: 10 requests per minute per user

## Webhook Endpoints

### Stripe Webhooks
**POST** `/webhooks/stripe`

**Headers:**
```
Stripe-Signature: {signature}
```

**Handled Events:**
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `payment_intent.canceled`

## Testing

### Test Data
Use the provided seeder accounts for testing:

**Admin:** admin@bellgas.com.au / password
**Customer:** john@example.com / password

### Stripe Test Cards
- Success: 4242424242424242
- Declined: 4000000000000002
- 3D Secure: 4000002500003155

### Example cURL Requests

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password"}'
```

**Create Payment Intent:**
```bash
curl -X POST http://localhost:8000/api/checkout/create-payment-intent \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "fulfillment_method": "PICKUP",
    "items": [{"product_variant_id": 1, "quantity": 1}]
  }'
```