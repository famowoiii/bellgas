# BellGas Laravel - Deployment Guide

## 📋 Prerequisites

### Server Requirements
- Ubuntu 20.04 LTS or newer
- PHP 8.2 or higher
- MySQL 8.0 or PostgreSQL 13+
- Nginx or Apache
- Composer 2.x
- Node.js 18+ and npm
- Supervisor (for queue workers)
- Redis (optional, for caching and queues)

### Required PHP Extensions
```bash
php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml
php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath
php8.2-soap php8.2-redis
```

## 🚀 Quick Deployment

### Option 1: Using Deployment Script (Recommended)

1. **Clone repository on server:**
```bash
cd /var/www
sudo git clone https://github.com/famowoiii/bellgas.git bellgas-laravel
cd bellgas-laravel
```

2. **Create and configure .env file:**
```bash
cp .env.example .env
nano .env
```

Update these critical values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bellgas_production
DB_USERNAME=bellgas_user
DB_PASSWORD=your_secure_password

# Stripe PRODUCTION keys (NOT test keys!)
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxx
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# JWT Secret
JWT_SECRET=your_secure_jwt_secret_here
JWT_TTL=60

# Reverb/WebSocket
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=yourdomain.com
REVERB_PORT=6001
REVERB_SCHEME=https
```

3. **Run deployment script:**
```bash
chmod +x deploy.sh
sudo bash deploy.sh
```

4. **Generate application key and JWT secret:**
```bash
php artisan key:generate
php artisan jwt:secret
```

### Option 2: Manual Deployment

```bash
# 1. Install dependencies
composer install --optimize-autoloader --no-dev
npm ci --production
npm run build

# 2. Setup database
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# 3. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize

# 4. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Start services
php artisan reverb:start --host=0.0.0.0 --port=6001
```

## 🔧 Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/bellgas`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    root /var/www/bellgas-laravel/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# WebSocket Server (Reverb)
server {
    listen 6001 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/bellgas /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🔐 SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

## 👷 Background Services

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/bellgas-worker.conf`:

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

### Reverb WebSocket Server (Systemd)

Create `/etc/systemd/system/laravel-reverb.service`:

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/bellgas-laravel
ExecStart=/usr/bin/php /var/www/bellgas-laravel/artisan reverb:start --host=0.0.0.0 --port=6001
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Enable and start services:
```bash
# Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bellgas-worker:*

# Reverb
sudo systemctl daemon-reload
sudo systemctl enable laravel-reverb
sudo systemctl start laravel-reverb
```

## 📊 Monitoring

### Check Service Status
```bash
# Application
sudo systemctl status php8.2-fpm
sudo systemctl status nginx

# Queue workers
sudo supervisorctl status

# WebSocket server
sudo systemctl status laravel-reverb

# Application logs
tail -f /var/www/bellgas-laravel/storage/logs/laravel.log
```

### Health Check Endpoints
- Application: `https://yourdomain.com/`
- API: `https://yourdomain.com/api/health`
- WebSocket: `wss://yourdomain.com:6001`

## 🔄 Updating the Application

### Using Deployment Script
```bash
cd /var/www/bellgas-laravel
sudo bash deploy.sh
```

### Manual Update
```bash
cd /var/www/bellgas-laravel

# 1. Enable maintenance mode
php artisan down

# 2. Pull latest changes
git pull origin main

# 3. Update dependencies
composer install --optimize-autoloader --no-dev
npm ci --production
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo supervisorctl restart bellgas-worker:*
sudo systemctl restart laravel-reverb
sudo systemctl reload php8.2-fpm

# 7. Disable maintenance mode
php artisan up
```

## 🛡️ Security Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Use HTTPS (SSL certificate)
- [ ] Set strong `APP_KEY` and `JWT_SECRET`
- [ ] Use production Stripe keys (not test)
- [ ] Configure firewall (UFW):
  ```bash
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw allow 6001/tcp
  sudo ufw enable
  ```
- [ ] Restrict database access to localhost
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Configure rate limiting in `.env`
- [ ] Enable CSRF protection
- [ ] Set up regular backups (database + storage)

## 💾 Backup Strategy

### Database Backup (Daily)
```bash
#!/bin/bash
# /usr/local/bin/backup-bellgas-db.sh

BACKUP_DIR="/var/backups/bellgas"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="bellgas_production"
DB_USER="bellgas_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup-bellgas-db.sh
```

## 🐛 Troubleshooting

### Application Not Loading
```bash
# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# Clear cache
php artisan config:clear
php artisan cache:clear
```

### WebSocket Connection Failing
```bash
# Check if Reverb is running
sudo systemctl status laravel-reverb
netstat -tulpn | grep 6001

# Restart Reverb
sudo systemctl restart laravel-reverb

# Check logs
tail -f storage/logs/laravel.log
```

### Queue Jobs Not Processing
```bash
# Check supervisor
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart bellgas-worker:*

# Check worker logs
tail -f storage/logs/worker.log
```

### 500 Internal Server Error
```bash
# Enable debug temporarily
php artisan config:clear
# Set APP_DEBUG=true in .env (ONLY FOR DEBUGGING)

# Check logs
tail -f storage/logs/laravel.log

# Common fixes
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

## 📞 Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review documentation: `DOCUMENTATION.md`
- API reference: `API_REFERENCE.md`
- GitHub issues: https://github.com/famowoiii/bellgas/issues

---

**Important:** Always test deployment process in staging environment before production deployment!
