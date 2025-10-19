# 🚀 BellGas Production Setup Guide

This guide will help you set up BellGas Laravel application for production deployment.

## 📋 Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Nginx or Apache web server
- SSL Certificate (for HTTPS)
- Git

## 🔧 Production Configuration

### Environment Setup

1. **Copy `.env.example` to `.env`**
   ```bash
   cp .env.example .env
   ```

2. **Update Database Credentials**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bellgas_laravel
   DB_USERNAME=your_db_username
   DB_PASSWORD=your_secure_password
   ```

3. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

4. **Generate JWT Secret**
   ```bash
   php artisan jwt:secret
   ```

5. **Verify Production Settings**
   ```env
   APP_NAME="BellGas"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://bellgas.shop
   LOG_LEVEL=warning
   ```

## 💳 Stripe Configuration

### IMPORTANT: Configure Your Stripe Keys!

You need to add your Stripe API keys to `.env` file for payment processing.

### Get Your Stripe Keys:

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. For **PRODUCTION** (real payments): Copy your **Live keys**
   - Publishable key (starts with `pk_live_...`)
   - Secret key (starts with `sk_live_...`)
3. For **TESTING** (fake payments): Copy your **Test keys**
   - Publishable key (starts with `pk_test_...`)
   - Secret key (starts with `sk_test_...`)

### Update .env File:

**For Production (REAL PAYMENTS):**
```env
STRIPE_PUBLISHABLE_KEY=pk_live_YOUR_ACTUAL_LIVE_KEY
STRIPE_SECRET_KEY=sk_live_YOUR_ACTUAL_LIVE_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET
```

**For Testing/Development (NO REAL MONEY):**
```env
STRIPE_PUBLISHABLE_KEY=pk_test_YOUR_ACTUAL_TEST_KEY
STRIPE_SECRET_KEY=sk_test_YOUR_ACTUAL_TEST_SECRET
STRIPE_WEBHOOK_SECRET=whsec_YOUR_TEST_WEBHOOK_SECRET
```

⚠️ **WARNING**: Live keys will process REAL payments! Make sure you're using the correct keys for your environment.

### Setting up Stripe Webhook

1. Go to Stripe Dashboard: https://dashboard.stripe.com/webhooks
2. Click **"Add endpoint"**
3. Enter your webhook URL:
   ```
   https://bellgas.shop/api/webhook/stripe
   ```
4. Select events to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
5. Copy the **Webhook Signing Secret** and update `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
   ```

## 📦 Installation Steps

### 1. Clone Repository
```bash
cd /var/www
git clone https://github.com/famowoiii/bellgas.git bellgas-laravel
cd bellgas-laravel
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Set Permissions
```bash
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE bellgas_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bellgas_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON bellgas_laravel.* TO 'bellgas_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Run Migrations & Seeders
```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Link Storage
```bash
php artisan storage:link
```

### 7. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🌐 Web Server Configuration

### Nginx Configuration

Create file: `/etc/nginx/sites-available/bellgas.shop`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name bellgas.shop www.bellgas.shop;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name bellgas.shop www.bellgas.shop;

    root /var/www/bellgas-laravel/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/bellgas.shop/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bellgas.shop/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Upload limit
    client_max_body_size 10M;

    # Logging
    access_log /var/log/nginx/bellgas-access.log;
    error_log /var/log/nginx/bellgas-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/bellgas.shop /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🔒 SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d bellgas.shop -d www.bellgas.shop
```

## 🔄 Queue & Scheduler Setup (Optional)

### Supervisor for Queue Workers

Create file: `/etc/supervisor/conf.d/bellgas-worker.conf`

```ini
[program:bellgas-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bellgas-laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/bellgas-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bellgas-worker:*
```

### Cron Job for Laravel Scheduler

```bash
crontab -e
```

Add:
```
* * * * * cd /var/www/bellgas-laravel && php artisan schedule:run >> /dev/null 2>&1
```

## 🧪 Verification

### 1. Check Application Status
```bash
php artisan about
```

### 2. Test Health Endpoint
```bash
curl https://bellgas.shop/api/health
```

Expected response:
```json
{
  "status": "OK",
  "service": "BellGas API",
  "timestamp": "2025-10-19T..."
}
```

### 3. Test Products API
```bash
curl https://bellgas.shop/api/products
```

### 4. Check Logs
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/bellgas-error.log
```

## 🔐 Security Checklist

- [x] `APP_ENV=production` in `.env`
- [x] `APP_DEBUG=false` in `.env`
- [x] Stripe **LIVE keys** configured
- [x] SSL certificate installed and working
- [x] File permissions set correctly (775 for storage)
- [x] Database credentials secured
- [x] `.env` file NOT committed to Git
- [x] Security headers configured in Nginx
- [x] Firewall configured (UFW or similar)

## 📊 Monitoring

### Check Application Logs
```bash
tail -100 storage/logs/laravel.log
```

### Monitor Queue Jobs
```bash
php artisan queue:monitor
```

### Check Database Size
```bash
php artisan db:show
```

## 🚀 Deployment Script

For automated deployments, use:
```bash
bash deploy-production.sh
```

This script handles:
- Backup creation
- Code updates from Git
- Dependency installation
- Database migrations
- Cache rebuilding
- Service restarts

## 🆘 Troubleshooting

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log

# Check Nginx logs
sudo tail -50 /var/log/nginx/bellgas-error.log

# Fix permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan config:clear
php artisan cache:clear
```

### Database Connection Error
```bash
# Test database connection
php artisan db:show

# Check credentials in .env
grep DB_ .env
```

### Stripe Payments Not Working
```bash
# Verify Stripe keys
grep STRIPE .env

# Clear config cache
php artisan config:clear
php artisan config:cache
```

## 📞 Support

- **Repository**: https://github.com/famowoiii/bellgas
- **Website**: https://bellgas.shop
- **Documentation**: Check `DEPLOYMENT_GUIDE.md` and `QUICK_DEPLOY.md`

---

**Ready for Production!** 🎉

This configuration uses **LIVE Stripe keys** and is production-ready out of the box.
