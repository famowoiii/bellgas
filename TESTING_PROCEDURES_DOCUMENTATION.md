# BellGas Testing Procedures Documentation

## Overview
This document outlines comprehensive testing procedures for the BellGas Laravel gas delivery application, covering payment processing, delivery restrictions, and core functionality.

## Prerequisites

### Environment Setup
```bash
# Start the Laravel development server
cd /d/sopek/bellgas-laravel
php artisan serve --host=0.0.0.0 --port=8000
```

### Database Setup
```bash
# Fresh migration and seeding
php artisan migrate:fresh --seed
```

### Test Accounts

#### Admin Account
- **Email**: admin@bellgas.com.au
- **Password**: password
- **Role**: MERCHANT (admin permissions)

#### Staff Account
- **Email**: staff@bellgas.com.au
- **Password**: password
- **Role**: MERCHANT (staff permissions)

#### Customer Accounts
- **Email**: john@example.com / **Password**: password
- **Email**: jane@example.com / **Password**: password
- **Email**: michael@example.com / **Password**: password

### Stripe Test Data
- **Publishable Key**: pk_test_xxx (from .env)
- **Success Card**: 4242424242424242
- **Declined Card**: 4000000000000002
- **3D Secure Card**: 4000002500003155
- **CVV**: Any 3 digits
- **Expiry**: Any future date

## Manual Testing Procedures

### 1. Authentication Testing

#### 1.1 User Registration
**Steps:**
1. Navigate to `/register`
2. Fill form with valid data:
   - First Name: "Test"
   - Last Name: "User"
   - Email: "test@example.com"
   - Phone: "+61412345678"
   - Password: "password123"
   - Confirm Password: "password123"
3. Submit form

**Expected Result:**
- User redirected to dashboard
- Success message displayed
- User automatically logged in

#### 1.2 User Login
**Steps:**
1. Navigate to `/login`
2. Enter credentials:
   - Email: "john@example.com"
   - Password: "password"
3. Click "Login"

**Expected Result:**
- User redirected to dashboard
- Welcome message displayed
- Navigation shows user name

#### 1.3 Invalid Login
**Steps:**
1. Navigate to `/login`
2. Enter invalid credentials
3. Submit form

**Expected Result:**
- Error message displayed
- User remains on login page
- Form data cleared

### 2. Product Browsing Testing

#### 2.1 Product Listing
**Steps:**
1. Navigate to `/products`
2. Verify all products display
3. Check product categories
4. Verify product images load

**Expected Results:**
- All active products visible
- Categories: FULL_TANK and REFILL only
- Product images display correctly
- Product prices shown in AUD

#### 2.2 Product Details
**Steps:**
1. Click on any product
2. Verify product details page
3. Check variant options
4. Test "Add to Cart" functionality

**Expected Results:**
- Product description displays
- Variant selector works
- Quantity selector works (1-10)
- Add to cart updates cart counter

### 3. Delivery Restrictions Testing

#### 3.1 Full Tank Products Only (Allow Delivery)
**Test Case:** Cart contains only FULL_TANK products

**Steps:**
1. Add "LPG Full Tank - 9kg" to cart
2. Navigate to checkout
3. Select fulfillment method

**Expected Results:**
- Both "Pickup" and "Delivery" options available
- No warning messages displayed
- Can proceed with either option

#### 3.2 Refill Products Only (Force Pickup)
**Test Case:** Cart contains only REFILL products

**Steps:**
1. Clear cart
2. Add "LPG Gas Refill - 9kg" to cart
3. Navigate to checkout
4. Observe fulfillment options

**Expected Results:**
- "Delivery" option disabled
- Warning message: "These items can only be picked up: LPG Gas Refill - 9kg"
- Only "Pickup" option available
- Auto-selected to "Pickup"

#### 3.3 Mixed Cart (Force Pickup)
**Test Case:** Cart contains both FULL_TANK and REFILL products

**Steps:**
1. Add "LPG Full Tank - 9kg" to cart
2. Add "LPG Gas Refill - 9kg" to cart
3. Navigate to checkout
4. Observe fulfillment options

**Expected Results:**
- "Delivery" option disabled
- Warning message lists refill items
- Auto-switched to "Pickup"
- Cannot select "Delivery"

#### 3.4 Auto-Switch Behavior
**Test Case:** Adding refill item when delivery is selected

**Steps:**
1. Add "LPG Full Tank - 9kg" to cart
2. Go to checkout
3. Select "Delivery"
4. Go back and add "LPG Gas Refill - 9kg"
5. Return to checkout

**Expected Results:**
- Automatically switches from "Delivery" to "Pickup"
- Notification message appears briefly
- Warning message explains the change

### 4. Payment Processing Testing

#### 4.1 Successful Payment (Pickup Order)
**Steps:**
1. Add FULL_TANK product to cart
2. Go to checkout
3. Select "Pickup"
4. Fill customer notes (optional)
5. Click "Place Order"
6. Enter Stripe test card: 4242424242424242
7. Enter CVV: 123, Expiry: 12/26
8. Submit payment

**Expected Results:**
- Payment intent created successfully
- Stripe payment form loads
- Payment processes successfully
- Order status changes to "PAID"
- Confirmation page displays
- Order number generated (BG-XXXXXXXX)

#### 4.2 Successful Payment (Delivery Order)
**Steps:**
1. Add FULL_TANK product to cart
2. Go to checkout
3. Select "Delivery"
4. Select delivery address
5. Fill customer notes
6. Complete payment with test card

**Expected Results:**
- Address validation passes
- Payment processes successfully
- Order includes shipping cost
- Delivery address saved to order

#### 4.3 Failed Payment
**Steps:**
1. Add product to cart
2. Go to checkout
3. Complete order form
4. Use declined card: 4000000000000002
5. Submit payment

**Expected Results:**
- Payment fails gracefully
- Error message displayed
- Order remains in "PENDING" status
- User can retry payment

#### 4.4 Validation Errors
**Steps:**
1. Add REFILL product to cart
2. Go to checkout
3. Select "Delivery" (should be disabled)
4. Try to submit via API

**Expected Results:**
- 422 validation error
- Clear error message about refill restrictions
- Frontend prevents submission

### 5. Address Management Testing

#### 5.1 Add Address
**Steps:**
1. Login as customer
2. Navigate to profile/addresses
3. Click "Add Address"
4. Fill address form:
   - Type: "HOME"
   - Name: "John Doe"
   - Street: "123 Test Street"
   - Suburb: "Melbourne"
   - State: "VIC"
   - Postcode: "3000"
   - Phone: "+61412345678"
5. Submit form

**Expected Results:**
- Address saved successfully
- Appears in address list
- Can be selected during checkout

#### 5.2 Address Validation (Delivery Orders)
**Steps:**
1. Create order with delivery
2. Select address not owned by user (via API)

**Expected Results:**
- Validation error: "You can only use your own addresses"
- Order creation fails

### 6. Order Management Testing

#### 6.1 Order History
**Steps:**
1. Login as customer with existing orders
2. Navigate to order history
3. Click on specific order

**Expected Results:**
- All user orders listed
- Order details display correctly
- Order status visible
- Order items show product names and quantities

#### 6.2 Admin Order Management
**Steps:**
1. Login as admin (admin@bellgas.com.au)
2. Navigate to admin orders page
3. Update order status
4. View order details

**Expected Results:**
- All orders visible to admin
- Can filter by status, user, method
- Status updates work
- Order events tracked

### 7. API Testing Procedures

#### 7.1 Authentication API
**cURL Example:**
```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password"}'

# Expected: Returns user object and JWT token
```

#### 7.2 Payment Intent API
**cURL Example:**
```bash
# Create payment intent (replace {token})
curl -X POST http://localhost:8000/api/checkout/create-payment-intent \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "fulfillment_method": "PICKUP",
    "items": [{"product_variant_id": 1, "quantity": 1}]
  }'

# Expected: Returns client_secret and order object
```

#### 7.3 Delivery Restriction API
**cURL Example:**
```bash
# Try delivery with refill product (should fail)
curl -X POST http://localhost:8000/api/checkout/create-payment-intent \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "fulfillment_method": "DELIVERY",
    "items": [{"product_variant_id": 2, "quantity": 1}]
  }'

# Expected: 422 error with delivery restriction message
```

## Automated Testing

### Unit Tests
```bash
# Run all tests
php artisan test

# Run specific test classes
php artisan test --filter=CheckoutTest
php artisan test --filter=DeliveryRestrictionTest
```

### Feature Tests

#### Payment Processing Test
```php
// Test successful payment intent creation
public function test_create_payment_intent_success()
{
    $user = User::factory()->create();
    $productVariant = ProductVariant::factory()->create([
        'product_id' => Product::factory()->create(['category' => 'FULL_TANK'])
    ]);

    $response = $this->actingAs($user)->postJson('/api/checkout/create-payment-intent', [
        'fulfillment_method' => 'PICKUP',
        'items' => [
            ['product_variant_id' => $productVariant->id, 'quantity' => 1]
        ]
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['client_secret', 'order']);
}
```

#### Delivery Restriction Test
```php
// Test refill product delivery restriction
public function test_refill_product_delivery_restriction()
{
    $user = User::factory()->create();
    $refillProduct = Product::factory()->create(['category' => 'REFILL']);
    $productVariant = ProductVariant::factory()->create(['product_id' => $refillProduct]);

    $response = $this->actingAs($user)->postJson('/api/checkout/create-payment-intent', [
        'fulfillment_method' => 'DELIVERY',
        'items' => [
            ['product_variant_id' => $productVariant->id, 'quantity' => 1]
        ]
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['fulfillment_method']);
}
```

## Performance Testing

### Load Testing
```bash
# Install Apache Bench (if not available)
# Test checkout endpoint
ab -n 100 -c 10 -T 'application/json' -H 'Authorization: Bearer {token}' \
   -p post_data.json http://localhost:8000/api/checkout/create-payment-intent
```

### Database Performance
```bash
# Check query performance
php artisan telescope:install  # Optional - for query monitoring
```

## Security Testing

### Authentication Security
1. **JWT Token Validation**
   - Test expired tokens
   - Test malformed tokens
   - Test token refresh

2. **Role-Based Access**
   - Customer accessing admin routes
   - Unauthenticated access to protected routes

3. **Data Validation**
   - SQL injection attempts
   - XSS payload testing
   - CSRF protection

### Payment Security
1. **Stripe Integration**
   - Test webhook signature validation
   - Test payment intent verification
   - Test amount manipulation attempts

2. **Address Security**
   - Test address ownership validation
   - Test cross-user address access

## Error Scenarios Testing

### Network Failures
1. **Stripe API Down**
   - Disconnect internet during payment
   - Test graceful error handling

2. **Database Connection Lost**
   - Test order creation with DB issues

### Data Corruption
1. **Invalid Product Data**
   - Missing product variants
   - Inactive products in cart

2. **Pricing Inconsistencies**
   - Price changes during checkout

## Monitoring and Logging

### Log Monitoring
```bash
# Monitor Laravel logs during testing
tail -f storage/logs/laravel.log
```

### Key Log Events to Monitor
- Failed payment attempts
- Delivery restriction violations
- Authentication failures
- Order creation errors
- Stripe webhook processing

## Test Reporting

### Test Checklist
- [ ] User registration and authentication
- [ ] Product browsing and cart management
- [ ] Delivery restrictions (all scenarios)
- [ ] Payment processing (success and failure)
- [ ] Address management
- [ ] Order management
- [ ] Admin functionality
- [ ] API endpoints
- [ ] Error handling
- [ ] Security measures

### Bug Reporting Template
```
**Bug Title**: Brief description
**Environment**: Local/Staging/Production
**Steps to Reproduce**:
1. Step one
2. Step two
3. Step three

**Expected Result**: What should happen
**Actual Result**: What actually happened
**Browser/Device**: Chrome/Firefox/Mobile
**Severity**: Critical/High/Medium/Low
**Screenshots**: [Attach if applicable]
```

## Regression Testing

### Before Deployment
1. Run full test suite
2. Test payment processing with real Stripe test mode
3. Verify delivery restrictions across all product combinations
4. Test order flow end-to-end
5. Verify admin functionality
6. Check error handling and user feedback

### Post-Deployment Verification
1. Smoke test core functionality
2. Verify Stripe webhook endpoints
3. Test user registration
4. Place test orders
5. Monitor error logs