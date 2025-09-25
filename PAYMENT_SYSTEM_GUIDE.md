# 📋 BellGas Payment System Guide

## How the Payment System Works

### 🔄 Complete Payment Flow

#### 1. **Customer Side:**
1. **Browse Products** → Add items to cart
2. **Go to Checkout** → Select delivery/pickup, enter address
3. **Fill Payment Form** → Enter real credit card details:
   - Card Number: `4242424242424242` (Stripe test card)
   - CVC: Any 3 digits (e.g., `123`)
   - Expiry: Any future date (e.g., `12/25`)
   - ZIP: Any valid postal code (e.g., `12345`)
4. **Click "Pay Now"** → **REAL PAYMENT IS PROCESSED BY STRIPE**
5. **Payment Success** → Order status changes from UNPAID → **PAID**
6. **Redirected to Order Detail Page** → Can view order, download receipt

#### 2. **Merchant/Admin Side:**
1. **Check Dashboard** → New orders appear with status **PAID** (not UNPAID!)
2. **Process Order** → Change status through order management:
   - PAID → PROCESSING (preparing order)
   - PROCESSING → READY_FOR_PICKUP (if pickup) / SHIPPED (if delivery)
   - READY_FOR_PICKUP/SHIPPED → DELIVERED (order completed)

### 🚨 Important Notes

#### **Payment is REAL:**
- When customer enters card details and clicks "Pay Now", Stripe charges the card
- The system uses **actual Stripe test API keys** (not placeholders)
- Order status automatically changes to PAID when payment succeeds
- If you see UNPAID status, it means payment failed or was not completed

#### **Merchant Workflow:**
- **You DON'T confirm payments** - that's automatic
- **You DO process orders** - update status as you fulfill orders
- Orders appear as PAID when customers successfully pay

### 🔧 Test Payment Cards (Stripe Test Mode)

```
✅ Success Card:
Number: 4242424242424242
CVC: Any 3 digits
Expiry: Any future date

❌ Declined Card (for testing failures):
Number: 4000000000000002
CVC: Any 3 digits
Expiry: Any future date

💳 Other Test Cards:
4000000000000044 (insufficient funds)
4000000000000069 (expired card)
```

### 📊 Order Status Workflow

```
1. UNPAID    → Customer created order but didn't pay yet
2. PAID      → Customer successfully paid (auto-updated by system)
3. PROCESSING → Merchant is preparing the order
4. READY_FOR_PICKUP → Order ready (pickup orders)
5. SHIPPED   → Order shipped (delivery orders)
6. DELIVERED → Order completed
7. CANCELLED → Order was cancelled
```

### 🛠️ Troubleshooting

#### **If orders show UNPAID after payment:**
1. Check browser console for JavaScript errors
2. Verify Stripe API keys in `.env` file
3. Check network tab - confirm payment confirmation API call succeeded
4. Look at Laravel logs: `tail -f storage/logs/laravel.log`

#### **If payment form doesn't work:**
1. Check Stripe public key in checkout page
2. Verify network connectivity
3. Use browser dev tools to check for errors
4. Try different test card numbers

### 🔍 API Endpoints for Testing

```bash
# Check order status
GET /api/orders/{order_id}
Authorization: Bearer {token}

# Confirm payment (automatically called by frontend)
POST /api/orders/{order_id}/confirm-payment
{
  "payment_intent_id": "pi_xxxxx",
  "payment_method_id": "pm_xxxxx"
}

# Update order status (merchant action)
PUT /api/orders/{order_id}
{
  "status": "PROCESSING",
  "notes": "Order is being prepared"
}
```

### 📧 Support

If you encounter any issues:
1. Check browser developer console for errors
2. Review Laravel error logs
3. Verify all environment variables are set correctly
4. Test with different browsers/devices

---

**Remember:** The payment system is designed to be automatic. Merchants focus on order fulfillment, not payment processing!