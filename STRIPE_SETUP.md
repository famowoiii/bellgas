# Stripe Payment Setup Guide

## Overview
Your BellGas Laravel application uses Stripe for payment processing. To enable checkout functionality, you need to configure your Stripe API keys.

## Current Status
❌ **Stripe is not configured** - Checkout will return a 503 error until configured.

## Setup Instructions

### 1. Create a Stripe Account
1. Go to [https://stripe.com](https://stripe.com)
2. Sign up for a free account
3. Complete account verification

### 2. Get Your API Keys
1. Log into your Stripe Dashboard
2. Go to [Developers > API keys](https://dashboard.stripe.com/test/apikeys)
3. Copy your **Publishable key** (starts with `pk_test_`)
4. Copy your **Secret key** (starts with `sk_test_`)

### 3. Configure Your Environment
1. Open your `.env` file in the project root
2. Replace the placeholder values:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_ACTUAL_PUBLISHABLE_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_ACTUAL_SECRET_KEY_HERE
```

### 4. Test the Configuration
After updating your keys, you can test the Stripe connection:

```bash
# Test Stripe connectivity (requires valid JWT token)
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" http://localhost:8000/api/test-stripe-connection
```

### 5. Webhook Configuration (Optional)
For production environments, you may want to set up webhooks:

1. In Stripe Dashboard, go to Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/api/webhook/stripe`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
4. Copy the webhook secret and add to `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

## Testing Payment Methods

Stripe provides test card numbers for development:

| Card Number | Description |
|-------------|-------------|
| 4242424242424242 | Visa - Successful payment |
| 4000000000000002 | Card declined |
| 4000000000009995 | Insufficient funds |

## Security Notes

⚠️ **Important**: 
- Never commit real API keys to version control
- Use test keys (starting with `pk_test_` and `sk_test_`) for development
- Use live keys (starting with `pk_live_` and `sk_live_`) only in production
- Keep your secret keys secure and never expose them in client-side code

## Troubleshooting

### Common Issues

1. **"Invalid API Key" error**: Verify your keys are correct and haven't expired
2. **"Payment system not configured" error**: Ensure keys are set in `.env` and aren't placeholder values
3. **Connection timeout**: Check your internet connection and Stripe service status

### Support
- Stripe Documentation: [https://stripe.com/docs](https://stripe.com/docs)
- Stripe Support: [https://support.stripe.com](https://support.stripe.com)
- Laravel Stripe Integration: [https://laravel.com/docs/payments](https://laravel.com/docs/payments)