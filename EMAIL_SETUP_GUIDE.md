# Email Setup Guide for BellGas Laravel Application

## Option 1: Gmail SMTP (Recommended for Personal Use)

### Prerequisites:
1. Gmail account dengan 2FA enabled
2. App Password generated

### Steps:

#### 1. Enable 2-Factor Authentication
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable "2-Step Verification"
3. Complete verification process

#### 2. Generate App Password
1. Go to [Google Account Security](https://myaccount.google.com/security) 
2. Click "2-Step Verification"
3. Scroll down to "App passwords"
4. Click "App passwords"
5. Select "Mail" and "Other (Custom name)"
6. Enter "BellGas Laravel App"
7. Click "Generate"
8. **SAVE THE 16-CHARACTER PASSWORD** (e.g., `abcd efgh ijkl mnop`)

#### 3. Update .env File
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@bellgas.com.au"
MAIL_FROM_NAME="BellGas"
```

#### 4. Test Configuration
```bash
curl -X POST http://127.0.0.1:8000/api/receipts/email/BG-ADENK70R \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{}'
```

---

## Option 2: Mailtrap (Recommended for Development/Testing)

### Prerequisites:
1. Free Mailtrap account

### Steps:

#### 1. Create Mailtrap Account
1. Go to [https://mailtrap.io](https://mailtrap.io)
2. Sign up for free account
3. Verify email address

#### 2. Get SMTP Credentials
1. Login to Mailtrap dashboard
2. Go to "Email Testing" → "Inboxes"
3. Click on "My Inbox" (or create new)
4. Click "SMTP Settings"
5. Select "Laravel 9+" from integrations
6. Copy the credentials

#### 3. Update .env File
```bash
MAIL_MAILER=smtp
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@bellgas.com.au"
MAIL_FROM_NAME="BellGas"
```

#### 4. Test Configuration
```bash
curl -X POST http://127.0.0.1:8000/api/receipts/email/BG-ADENK70R \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{}'
```

#### 5. Check Mailtrap Inbox
1. Go to Mailtrap dashboard
2. Click on your inbox
3. You should see the email there (not in real email)

---

## Option 3: SendGrid (Production Ready)

### Prerequisites:
1. SendGrid account (free tier available)

### Steps:

#### 1. Create SendGrid Account
1. Go to [https://sendgrid.com](https://sendgrid.com)
2. Sign up for account
3. Verify email and phone

#### 2. Create API Key
1. Login to SendGrid dashboard
2. Go to "Settings" → "API Keys"
3. Click "Create API Key"
4. Name: "BellGas Laravel"
5. Select "Full Access" 
6. Click "Create & View"
7. **COPY THE API KEY** (starts with `SG.`)

#### 3. Update .env File
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@bellgas.com.au"
MAIL_FROM_NAME="BellGas"
```

#### 4. Verify Sender Identity (Optional but Recommended)
1. Go to "Settings" → "Sender Authentication"
2. Click "Verify a Single Sender"
3. Fill form with your details
4. Click "Create"
5. Check email and click verification link

---

## Option 4: Mailgun (Alternative Production Option)

### Steps:

#### 1. Create Mailgun Account
1. Go to [https://mailgun.com](https://mailgun.com)
2. Sign up (free tier: 5000 emails/month for 3 months)

#### 2. Get API Credentials
1. Login to dashboard
2. Go to "Sending" → "Domains"
3. Click on your sandbox domain
4. Copy "SMTP hostname", "Port", "Username", "Password"

#### 3. Update .env File
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-mailgun-username
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@bellgas.com.au"
MAIL_FROM_NAME="BellGas"
```

---

## Testing Email Delivery

### 1. Clear Config Cache (Important!)
```bash
cd D:/sopek/bellgas-laravel
php artisan config:cache
php artisan config:clear
```

### 2. Test with Receipt Email
```bash
curl -X POST http://127.0.0.1:8000/api/receipts/email/BG-ADENK70R \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  --data-raw '{}'
```

### 3. Expected Response (Success)
```json
{
  "message": "Receipt sent successfully to testuser@bellgas.com",
  "email": "testuser@bellgas.com",
  "order_number": "BG-ADENK70R",
  "sent_at": "2025-09-04T07:00:00.000000Z"
}
```

### 4. Check Email
- **Gmail**: Check inbox of recipient
- **Mailtrap**: Check Mailtrap dashboard inbox
- **SendGrid**: Check SendGrid activity dashboard
- **Mailgun**: Check Mailgun logs

---

## Troubleshooting Common Issues

### Issue 1: "Connection could not be established"
**Solution:**
```bash
# Check firewall/antivirus blocking port 587
# Try alternative ports:
MAIL_PORT=465  # For SSL
MAIL_ENCRYPTION=ssl
```

### Issue 2: "Authentication failed"
**Solution:**
```bash
# For Gmail: Make sure you use App Password, not regular password
# For others: Double-check username/password
```

### Issue 3: "Stream_socket_enable_crypto(): SSL operation failed"
**Solution:**
```bash
# Try different encryption:
MAIL_ENCRYPTION=tls  # or ssl
MAIL_PORT=587        # or 465
```

### Issue 4: Config not updating
**Solution:**
```bash
php artisan config:clear
php artisan config:cache
```

---

## Email Templates

The application uses `ReceiptMail` class located at:
- `app/Mail/ReceiptMail.php`
- Template: `resources/views/emails/receipt.blade.php` (create if needed)

### Sample Receipt Email Template
```blade
<!DOCTYPE html>
<html>
<head>
    <title>BellGas Receipt</title>
</head>
<body>
    <h2>Order Receipt - {{ $receipt['receipt_info']['receipt_number'] }}</h2>
    
    <h3>Order Details</h3>
    <p><strong>Order Number:</strong> {{ $receipt['receipt_info']['order_number'] }}</p>
    <p><strong>Order Date:</strong> {{ $receipt['order_details']['order_date'] }}</p>
    <p><strong>Status:</strong> {{ $receipt['receipt_info']['status'] }}</p>
    
    <h3>Items</h3>
    @foreach($receipt['order_details']['items'] as $item)
    <div>
        <p>{{ $item['product_name'] }} - {{ $item['variant_name'] }}</p>
        <p>Quantity: {{ $item['quantity'] }} × ${{ $item['unit_price'] }} = ${{ $item['total_price'] }}</p>
    </div>
    @endforeach
    
    <h3>Total: ${{ $receipt['order_details']['pricing']['total'] }} AUD</h3>
    
    <p>Thank you for your purchase!</p>
    <p>BellGas Team</p>
</body>
</html>
```

---

## Production Recommendations

### For Production Environment:
1. **Use SendGrid or Mailgun** (not Gmail)
2. **Set up domain authentication**
3. **Monitor email delivery rates**
4. **Implement email queues** for better performance
5. **Set up webhooks** for delivery status tracking

### Security Best Practices:
1. Store API keys in `.env` file (never commit to git)
2. Use different credentials for different environments
3. Enable rate limiting for email endpoints
4. Implement email verification for new users
5. Add unsubscribe functionality

---

## Quick Setup Commands

### After configuring .env:
```bash
cd D:/sopek/bellgas-laravel
php artisan config:clear
php artisan config:cache
```

### Test email immediately:
```bash
curl -X POST http://127.0.0.1:8000/api/receipts/email/BG-ADENK70R \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --data-raw '{}'
```

Choose the option that best fits your needs:
- **Development/Testing**: Mailtrap
- **Personal Projects**: Gmail SMTP  
- **Production**: SendGrid or Mailgun