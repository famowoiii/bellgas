#!/bin/bash

# BellGas Laravel - Production Deployment Script
# This script automates the deployment process for production servers

set -e  # Exit on any error

echo "========================================="
echo "BellGas Laravel Production Deployment"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="${APP_DIR:-/var/www/bellgas-laravel}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo -e "${YELLOW}Configuration:${NC}"
echo "  App Directory: $APP_DIR"
echo "  Branch: $BRANCH"
echo "  PHP Binary: $PHP_BIN"
echo "  Composer Binary: $COMPOSER_BIN"
echo ""

# Step 1: Pull latest code
echo -e "${YELLOW}[1/10] Pulling latest code from Git...${NC}"
cd "$APP_DIR"
git fetch origin
git reset --hard "origin/$BRANCH"
git pull origin "$BRANCH"
echo -e "${GREEN}✓ Code updated${NC}"
echo ""

# Step 2: Install dependencies
echo -e "${YELLOW}[2/10] Installing Composer dependencies...${NC}"
$COMPOSER_BIN install --optimize-autoloader --no-dev --no-interaction
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# Step 3: Install NPM dependencies and build assets
echo -e "${YELLOW}[3/10] Building frontend assets...${NC}"
if command -v npm &> /dev/null; then
    npm ci --production
    npm run build
    echo -e "${GREEN}✓ Assets built${NC}"
else
    echo -e "${RED}⚠ NPM not found, skipping asset build${NC}"
fi
echo ""

# Step 4: Clear and cache config
echo -e "${YELLOW}[4/10] Clearing configuration cache...${NC}"
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

# Step 5: Run migrations
echo -e "${YELLOW}[5/10] Running database migrations...${NC}"
read -p "Do you want to run migrations? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    $PHP_BIN artisan migrate --force
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${YELLOW}⊘ Migrations skipped${NC}"
fi
echo ""

# Step 6: Cache config for production
echo -e "${YELLOW}[6/10] Caching configuration for production...${NC}"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

# Step 7: Optimize autoloader
echo -e "${YELLOW}[7/10] Optimizing Composer autoloader...${NC}"
$COMPOSER_BIN dump-autoload --optimize --no-dev
echo -e "${GREEN}✓ Autoloader optimized${NC}"
echo ""

# Step 8: Set permissions
echo -e "${YELLOW}[8/10] Setting directory permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 9: Restart services
echo -e "${YELLOW}[9/10] Restarting services...${NC}"
if command -v supervisorctl &> /dev/null; then
    supervisorctl restart bellgas-worker:*
    echo -e "${GREEN}✓ Queue workers restarted${NC}"
fi

if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm
    echo -e "${GREEN}✓ PHP-FPM reloaded${NC}"
fi
echo ""

# Step 10: Health check
echo -e "${YELLOW}[10/10] Running health check...${NC}"
$PHP_BIN artisan --version
echo -e "${GREEN}✓ Deployment completed successfully!${NC}"
echo ""

echo "========================================="
echo "Post-Deployment Checklist:"
echo "========================================="
echo "[ ] Verify application is accessible"
echo "[ ] Check logs for errors: tail -f storage/logs/laravel.log"
echo "[ ] Test critical features (login, checkout, etc.)"
echo "[ ] Monitor queue workers: supervisorctl status"
echo "[ ] Check reverb server: systemctl status laravel-reverb"
echo ""
echo -e "${GREEN}Deployment complete!${NC}"
