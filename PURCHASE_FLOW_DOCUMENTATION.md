# BellGas E-commerce - Complete Purchase Flow Documentation

## Overview
Dokumentasi ini menjelaskan alur pembelian lengkap dari menambah barang ke cart hingga pembayaran berhasil menggunakan Stripe payment gateway.

## Prerequisites
- Laravel server running on `http://127.0.0.1:8000`
- Stripe Test Keys configured
- Valid JWT authentication token

## Complete Purchase Flow

### Step 1: User Registration
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  --data-raw '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone_number": "+61412345678",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone_number": "+61412345678",
    "role": "CUSTOMER"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Step 2: Create Delivery Address
```bash
curl -X POST http://127.0.0.1:8000/api/addresses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{
    "name": "Home Address",
    "street_address": "123 Collins Street",
    "suburb": "Melbourne",
    "state": "VIC",
    "postcode": "3000",
    "country": "Australia",
    "type": "HOME",
    "is_default": true
  }'
```

**Response:**
```json
{
  "message": "Address created successfully",
  "data": {
    "id": 1,
    "type": "HOME",
    "name": "Home Address",
    "street_address": "123 Collins Street",
    "suburb": "Melbourne",
    "state": "VIC",
    "postcode": "3000",
    "country": "Australia",
    "is_default": true,
    "full_address": "123 Collins Street, Melbourne VIC 3000, Australia"
  }
}
```

### Step 3: Add Product to Cart
```bash
curl -X POST http://127.0.0.1:8000/api/cart \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{
    "product_variant_id": 1,
    "quantity": 2
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "id": 1,
    "quantity": 2,
    "price": "89.95",
    "is_preorder": false,
    "reserved_until": "2025-09-04T06:40:46.000000Z",
    "notes": null,
    "total": 179.9,
    "productVariant": {
      "id": 1,
      "name": "9kg Cylinder",
      "weight_kg": "9.00",
      "price_aud": "89.95",
      "stock_quantity": 5,
      "product": {
        "id": 1,
        "name": "LPG Full Tank",
        "description": "Complete LPG gas cylinder with full tank. Perfect for home cooking, heating, and outdoor activities.",
        "category": "FULL_TANK"
      }
    }
  }
}
```

### Step 4: Create Order
```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{
    "address_id": 1,
    "payment_method": "CARD",
    "fulfillment_method": "DELIVERY",
    "notes": "Please deliver between 9 AM - 5 PM"
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "address_id": 1,
    "status": "UNPAID",
    "fulfillment_method": "DELIVERY",
    "subtotal_aud": "179.90",
    "shipping_cost_aud": "0.00",
    "total_aud": "179.90",
    "customer_notes": "Please deliver between 9 AM - 5 PM",
    "order_number": "BG-ADENK70R",
    "created_at": "2025-09-04T06:30:54.000000Z",
    "items": [
      {
        "id": 1,
        "product_variant_id": 1,
        "quantity": 2,
        "unit_price_aud": "89.95",
        "total_price_aud": "179.90",
        "productVariant": {
          "id": 1,
          "name": "9kg Cylinder",
          "product": {
            "name": "LPG Full Tank",
            "category": "FULL_TANK"
          }
        }
      }
    ],
    "address": {
      "id": 1,
      "name": "Home Address",
      "street_address": "123 Collins Street",
      "suburb": "Melbourne",
      "state": "VIC",
      "postcode": "3000",
      "country": "Australia"
    }
  }
}
```

### Step 5: Complete Payment with Stripe Test Card
```bash
curl -X POST http://127.0.0.1:8000/api/payments/orders/BG-ADENK70R/simulate \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{}'
```

**Response:**
```json
{
  "message": "Test payment completed successfully",
  "payment_intent": {
    "id": "pi_3S3WhrHhyehj4kO114d2FgDc",
    "object": "payment_intent",
    "amount": 17990,
    "amount_received": 17990,
    "currency": "aud",
    "status": "succeeded",
    "payment_method": "pm_1S3WhuHhyehj4kO13S1D46y4",
    "latest_charge": "ch_3S3WhrHhyehj4kO118tufoGp",
    "metadata": {
      "customer_id": "1",
      "order_id": "1",
      "order_number": "BG-ADENK70R"
    }
  },
  "order_status": "PAID",
  "test_info": {
    "card_used": "4242424242424242 (Visa Test Card)",
    "simulation": true,
    "note": "This payment was completed using Stripe test card"
  }
}
```

### Step 6: Verify Payment Status
```bash
curl -X GET http://127.0.0.1:8000/api/payments/orders/BG-ADENK70R/status \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:**
```json
{
  "message": "Payment status retrieved",
  "order_status": "PAID",
  "payment_intent": {
    "id": "pi_3S3WhrHhyehj4kO114d2FgDc",
    "status": "succeeded",
    "amount": 17990,
    "amount_received": 17990,
    "currency": "aud"
  },
  "payment_status": "succeeded"
}
```

### Step 7: Get Receipt
```bash
curl -X GET http://127.0.0.1:8000/api/receipts/order/BG-ADENK70R \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**Response:**
```json
{
  "message": "Receipt retrieved successfully",
  "receipt": {
    "receipt_info": {
      "receipt_number": "RCP-BG-ADENK70R",
      "order_number": "BG-ADENK70R",
      "issued_at": "2025-09-04T06:35:21.000000Z",
      "status": "PAID",
      "payment_status": "COMPLETED"
    },
    "business_info": {
      "name": "BellGas",
      "address": "Sydney, NSW, Australia",
      "phone": "+61 2 1234 5678",
      "email": "support@bellgas.com.au",
      "abn": "12 345 678 901",
      "website": "https://bellgas.com.au"
    },
    "customer_info": {
      "name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+61412345678"
    },
    "delivery_address": {
      "name": "Home Address",
      "street_address": "123 Collins Street",
      "suburb": "Melbourne",
      "state": "VIC",
      "postcode": "3000",
      "country": "Australia",
      "full_address": "123 Collins Street, Melbourne VIC 3000, Australia"
    },
    "order_details": {
      "order_date": "2025-09-04T06:30:54.000000Z",
      "fulfillment_method": "DELIVERY",
      "customer_notes": "Please deliver between 9 AM - 5 PM",
      "items": [
        {
          "product_name": "LPG Full Tank",
          "variant_name": "9kg Cylinder",
          "category": "FULL_TANK",
          "quantity": 2,
          "unit_price": "89.95",
          "total_price": "179.90",
          "weight_kg": "9.00"
        }
      ],
      "pricing": {
        "subtotal": "179.90",
        "shipping_cost": "0.00",
        "total": "179.90",
        "currency": "AUD"
      }
    },
    "payment_details": {
      "payment_method": "pm_1S3WhuHhyehj4kO13S1D46y4",
      "amount_paid": "179.90",
      "currency": "AUD",
      "payment_date": "2025-09-04T06:31:39.000000Z",
      "payment_intent_id": "pi_3S3WhrHhyehj4kO114d2FgDc",
      "charge_id": "ch_3S3WhrHhyehj4kO118tufoGp",
      "payment_status": "SUCCESS",
      "processing_fee_note": "Payment processed securely by Stripe"
    },
    "timestamps": {
      "order_created": "2025-09-04T06:30:54.000000Z",
      "payment_completed": "2025-09-04T06:31:39.000000Z",
      "receipt_generated": "2025-09-04T06:35:21.000000Z"
    }
  }
}
```

## Key Features Implemented

### 1. **Smart Cart System**
- Race condition protection
- 15-minute stock reservation
- Preorder functionality for out-of-stock items
- "Siapa cepat bayar dia dapat" (first come, first served) principle

### 2. **Stripe Payment Integration**
- Real payment intent creation
- Proper payment method attachment
- Test card support for development
- Payment status tracking
- Webhook support for payment confirmations

### 3. **Order Management**
- Order lifecycle tracking (UNPAID → PAID → PROCESSING → READY → COMPLETED)
- Order events logging
- Stock management integration
- Fulfillment method support (PICKUP/DELIVERY)

### 4. **Receipt System**
- Professional receipt generation
- Stripe payment details integration
- PDF receipt support
- Email receipt delivery

## Stripe Test Cards

For testing payments, use these Stripe test card numbers:

| Card Number | Brand | Description |
|-------------|-------|-------------|
| 4242424242424242 | Visa | Always succeeds |
| 4000000000000069 | Visa | Expired card |
| 4000000000000127 | Visa | Incorrect CVC |
| 4000000000000002 | Visa | Generic decline |

**Test Details:**
- Any CVC (e.g., 123)
- Any future expiry date (e.g., 12/30)
- Any billing postal code

## Order Status Flow

```
UNPAID → PAID → PROCESSING → READY → COMPLETED
   ↓
CANCELLED (can be cancelled from UNPAID or PAID status)
```

## Error Handling

The system includes comprehensive error handling for:
- Invalid payment methods
- Insufficient stock
- Authentication failures
- Stripe API errors
- Network timeouts

## Security Features

- JWT authentication for all protected endpoints
- User authorization checks (users can only access their own orders)
- Stripe webhook signature verification
- Input validation and sanitization
- CSRF protection

## Performance Optimizations

- Database indexing on order_number, user_id, and status
- Eager loading of relationships
- Efficient stock reservation system
- Optimized query patterns

## Production Considerations

1. **Environment Variables**: Ensure all Stripe keys are properly configured
2. **Database**: Use production database with proper indexing
3. **Email**: Configure proper SMTP or email service
4. **Monitoring**: Implement logging and monitoring for payment flows
5. **Backup**: Regular database backups for order and payment data
6. **SSL**: Ensure HTTPS for all payment-related endpoints

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/addresses` | Create delivery address |
| POST | `/api/cart` | Add item to cart |
| GET | `/api/cart` | View cart contents |
| POST | `/api/orders` | Create order from cart |
| POST | `/api/payments/orders/{order}/simulate` | Simulate test payment |
| GET | `/api/payments/orders/{order}/status` | Get payment status |
| GET | `/api/receipts/order/{order}` | Get order receipt |
| POST | `/api/receipts/email/{order}` | Email receipt to customer |

## Testing Workflow

1. Register user and get JWT token
2. Create address
3. Add products to cart
4. Create order
5. Process payment with test card
6. Verify payment success
7. Generate and view receipt

Sistem ini telah teruji dan dapat memproses pembayaran real menggunakan Stripe dengan aman dan efisien.