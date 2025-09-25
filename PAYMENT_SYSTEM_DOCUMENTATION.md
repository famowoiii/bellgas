# BellGas Payment System Documentation

## Overview
The BellGas Laravel application uses Stripe for payment processing with JWT authentication. The system supports both pickup and delivery orders with specific business rules for product categories.

## Payment Flow

### 1. Create Payment Intent
**Endpoint:** `POST /api/checkout/create-payment-intent`

**Headers:**
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "fulfillment_method": "PICKUP|DELIVERY",
    "items": [
        {
            "product_variant_id": 1,
            "quantity": 2
        }
    ],
    "address_id": 1,
    "customer_notes": "Optional customer notes"
}
```

**Response (Success):**
```json
{
    "client_secret": "pi_xxx_secret_xxx",
    "order": {
        "id": 1,
        "order_number": "BG-ABCD1234",
        "total_aud": "50.00",
        "status": "PENDING"
    }
}
```

### 2. Confirm Payment
**Endpoint:** `POST /api/checkout/confirm-payment`

**Request Body:**
```json
{
    "payment_intent_id": "pi_xxx",
    "payment_method_id": "pm_xxx"
}
```

**Response (Success):**
```json
{
    "success": true,
    "order": {
        "id": 1,
        "order_number": "BG-ABCD1234",
        "status": "PAID",
        "paid_at": "2025-09-15T10:30:00Z"
    }
}
```

## Database Schema

### Orders Table
```sql
orders (
    id BIGINT PRIMARY KEY,
    order_number VARCHAR(255) UNIQUE,
    user_id BIGINT,
    address_id BIGINT NULLABLE,
    status ENUM('PENDING', 'PAID', 'CONFIRMED', 'READY_FOR_PICKUP', 'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETED', 'CANCELLED'),
    fulfillment_method ENUM('PICKUP', 'DELIVERY'),
    payment_method VARCHAR(255),
    subtotal_aud DECIMAL(8,2),
    shipping_cost_aud DECIMAL(8,2),
    total_aud DECIMAL(8,2),
    stripe_payment_intent_id VARCHAR(255),
    paid_at TIMESTAMP NULLABLE,
    customer_notes TEXT NULLABLE,
    pickup_ready_at TIMESTAMP NULLABLE,
    delivered_at TIMESTAMP NULLABLE,
    completed_at TIMESTAMP NULLABLE
)
```

### Order Items Table
```sql
order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT,
    product_variant_id BIGINT,
    quantity INTEGER,
    unit_price_aud DECIMAL(8,2),
    total_price_aud DECIMAL(8,2)
)
```

## Payment Processing Logic

### Order Creation Process
1. **Validation**: Request validated via `CreatePaymentIntentRequest`
2. **Product Availability**: Check product variants exist and are active
3. **Delivery Restrictions**: Validate delivery method against product categories
4. **Price Calculation**: Calculate subtotal, shipping, and total amounts
5. **Stripe Payment Intent**: Create payment intent with calculated amount
6. **Order Record**: Save order with PENDING status
7. **Response**: Return client_secret for frontend payment confirmation

### Payment Confirmation Process
1. **Stripe Verification**: Verify payment intent status with Stripe
2. **Order Update**: Update order status to PAID and set paid_at timestamp
3. **Event Dispatch**: Fire `OrderStatusUpdated` event for notifications
4. **Response**: Return updated order details

## Error Handling

### Common Error Responses

**422 Validation Error:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "fulfillment_method": [
            "Refill products can only be picked up at the store. Please select 'PICKUP' for: LPG Gas Refill - 9kg"
        ]
    }
}
```

**500 Payment Error:**
```json
{
    "message": "Payment processing failed",
    "error": "Stripe payment intent creation failed"
}
```

## Configuration

### Environment Variables
```env
STRIPE_KEY=pk_test_YOUR_STRIPE_PUBLISHABLE_KEY_HERE
STRIPE_SECRET=sk_test_YOUR_STRIPE_SECRET_KEY_HERE
STRIPE_WEBHOOK_SECRET=whsec_YOUR_STRIPE_WEBHOOK_SECRET_HERE
```

### Stripe Test Cards
- **Success**: 4242424242424242
- **Declined**: 4000000000000002
- **3D Secure**: 4000002500003155

## Testing Accounts

### Admin Account
- **Email**: admin@bellgas.com.au
- **Password**: password
- **Role**: MERCHANT (admin permissions)

### Staff Account
- **Email**: staff@bellgas.com.au
- **Password**: password
- **Role**: MERCHANT (staff permissions)

### Customer Accounts
- **Email**: john@example.com
- **Password**: password
- **Role**: CUSTOMER

- **Email**: jane@example.com
- **Password**: password
- **Role**: CUSTOMER

## Security Features

### Authentication
- JWT tokens for API authentication
- Token expiration and refresh mechanism
- Role-based access control (admin, staff, customer)

### Payment Security
- Stripe PCI compliance
- No credit card data stored locally
- Payment intent verification before order confirmation
- Webhook signature verification for Stripe events

### Data Validation
- Comprehensive input validation
- Address ownership verification
- Product availability checks
- Quantity limits per item (max 10)

## Business Rules

### Order Limits
- Minimum 1 item per order
- Maximum 10 quantity per item
- Customer notes limited to 1000 characters

### Payment Methods
- Credit/Debit cards via Stripe
- Future: Cash on pickup, Bank transfer

### Order Status Flow
```
PENDING → PAID → CONFIRMED → READY_FOR_PICKUP/OUT_FOR_DELIVERY → DELIVERED/PICKED_UP → COMPLETED
```