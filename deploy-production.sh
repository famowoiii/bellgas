#!/bin/bash

################################################################################
# BellGas Production Deployment Script
# Website: https://bellgas.shop
# Repository: https://github.com/famowoiii/bellgas
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR=$(pwd)
BACKUP_DIR="$APP_DIR/backups"
DATE=$(date +%Y%m%d_%H%M%S)

################################################################################
# Helper Functions
################################################################################

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

################################################################################
# Step 1: Pre-deployment Checks
################################################################################

log_info "Starting BellGas deployment to production..."
log_info "Deployment time: $(date)"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    log_error "Not in Laravel directory! Please run this script from the project root."
    exit 1
fi

# Check if git is available
if ! command -v git &> /dev/null; then
    log_error "Git is not installed!"
    exit 1
fi

################################################################################
# Step 2: Create Backup Directory
################################################################################

log_info "Creating backup directory..."
mkdir -p "$BACKUP_DIR"

################################################################################
# Step 3: Backup Database
################################################################################

log_info "Backing up database..."

# Read database credentials from .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

if [ -n "$DB_DATABASE" ]; then
    if [ -z "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -u "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_DIR/database_$DATE.sql"
    else
        mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database_$DATE.sql"
    fi
    log_info "Database backed up to: $BACKUP_DIR/database_$DATE.sql"
else
    log_warning "Could not backup database - DB_DATABASE not found in .env"
fi

################################################################################
# Step 4: Backup .env and Storage
################################################################################

log_info "Backing up .env file..."
cp .env "$BACKUP_DIR/.env_$DATE"

log_info "Backing up storage directory..."
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" storage/app/public 2>/dev/null || log_warning "Storage backup failed or empty"

################################################################################
# Step 5: Enable Maintenance Mode
################################################################################

log_info "Enabling maintenance mode..."
php artisan down --message="Deploying new version. We'll be back in a moment!" --retry=60

################################################################################
# Step 6: Pull Latest Code from GitHub
################################################################################

log_info "Stashing local changes (if any)..."
git stash

log_info "Fetching latest code from GitHub..."
git fetch origin main

log_info "Pulling latest changes..."
git pull origin main

CURRENT_COMMIT=$(git rev-parse HEAD)
log_info "Deployed commit: $CURRENT_COMMIT"

################################################################################
# Step 7: Install/Update Dependencies
################################################################################

log_info "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

log_info "Optimizing Composer autoloader..."
composer dump-autoload --optimize

################################################################################
# Step 8: Run Migrations
################################################################################

log_info "Running database migrations..."
php artisan migrate --force

################################################################################
# Step 9: Clear and Rebuild Caches
################################################################################

log_info "Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

log_info "Building production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

################################################################################
# Step 10: Set Permissions
################################################################################

log_info "Setting correct file permissions..."
chmod -R 775 storage bootstrap/cache

# Try to set owner (may require sudo)
if command -v sudo &> /dev/null; then
    sudo chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || log_warning "Could not set ownership (may need manual intervention)"
fi

################################################################################
# Step 11: Restart Services
################################################################################

log_info "Restarting PHP-FPM..."
if command -v sudo &> /dev/null; then
    sudo systemctl restart php8.2-fpm 2>/dev/null || sudo service php8.2-fpm restart 2>/dev/null || log_warning "Could not restart PHP-FPM automatically"
fi

log_info "Restarting Nginx..."
if command -v sudo &> /dev/null; then
    sudo systemctl restart nginx 2>/dev/null || sudo service nginx restart 2>/dev/null || log_warning "Could not restart Nginx automatically"
fi

# Restart queue workers if using Supervisor
if command -v supervisorctl &> /dev/null; then
    log_info "Restarting queue workers..."
    sudo supervisorctl restart all 2>/dev/null || log_warning "Could not restart queue workers"
fi

################################################################################
# Step 12: Disable Maintenance Mode
################################################################################

log_info "Disabling maintenance mode..."
php artisan up

################################################################################
# Step 13: Cleanup Old Backups (Keep last 5)
################################################################################

log_info "Cleaning up old backups (keeping last 5)..."
cd "$BACKUP_DIR"
ls -t database_*.sql 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
ls -t .env_* 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
ls -t storage_*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
cd "$APP_DIR"

################################################################################
# Step 14: Health Check
################################################################################

log_info "Running health check..."

# Test if application is responding
if command -v curl &> /dev/null; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://bellgas.shop/api/health || echo "000")

    if [ "$HTTP_CODE" = "200" ]; then
        log_info "✓ Health check passed! (HTTP $HTTP_CODE)"
    else
        log_error "✗ Health check failed! (HTTP $HTTP_CODE)"
        log_error "Please check the application manually"
    fi
else
    log_warning "curl not available, skipping health check"
fi

################################################################################
# Deployment Complete
################################################################################

echo ""
log_info "=================================="
log_info "🚀 DEPLOYMENT COMPLETED!"
log_info "=================================="
log_info "Website: https://bellgas.shop"
log_info "Commit: $CURRENT_COMMIT"
log_info "Time: $(date)"
log_info "Backup location: $BACKUP_DIR"
echo ""
log_info "Next steps:"
log_info "1. Visit https://bellgas.shop and verify everything works"
log_info "2. Test critical features (login, products, checkout)"
log_info "3. Check logs: tail -f storage/logs/laravel.log"
log_info "4. Monitor for any errors"
echo ""

################################################################################
# Rollback Instructions
################################################################################

echo ""
log_warning "=================================="
log_warning "🔄 ROLLBACK INSTRUCTIONS"
log_warning "=================================="
log_warning "If something goes wrong, rollback with:"
log_warning "  git reset --hard <previous-commit>"
log_warning "  cp $BACKUP_DIR/.env_$DATE .env"
log_warning "  mysql -u $DB_USERNAME -p $DB_DATABASE < $BACKUP_DIR/database_$DATE.sql"
log_warning "  php artisan config:clear && php artisan cache:clear"
log_warning "  sudo systemctl restart php8.2-fpm nginx"
echo ""

exit 0
