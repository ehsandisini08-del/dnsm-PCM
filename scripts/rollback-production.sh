#!/bin/bash
###############################################################################
# Production Rollback Script for DNS Manager
# Rolls back to previous deployment state
###############################################################################

set -e

echo "=========================================="
echo "DNS Manager - Production Rollback"
echo "=========================================="
echo ""

# Configuration
APP_DIR="/var/www/dnsmanager"
BACKUP_DIR="/var/backups/dnsmanager"
PHP_VERSION="8.3"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# List available backups
echo "Available backups:"
ls -lth $BACKUP_DIR/ | head -10

echo ""
read -p "Enter backup date (format: YYYYMMDD_HHMMSS): " BACKUP_DATE

if [ -z "$BACKUP_DATE" ]; then
    echo -e "${RED}✗ No backup date provided${NC}"
    exit 1
fi

# Verify backup files exist
if [ ! -f "$BACKUP_DIR/database_$BACKUP_DATE.sql" ]; then
    echo -e "${RED}✗ Database backup not found: $BACKUP_DIR/database_$BACKUP_DATE.sql${NC}"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/app_files_$BACKUP_DATE.tar.gz" ]; then
    echo -e "${RED}✗ Application backup not found: $BACKUP_DIR/app_files_$BACKUP_DATE.tar.gz${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}⚠ WARNING: This will restore your application to the state from $BACKUP_DATE${NC}"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

echo ""
echo "[1/8] Enabling maintenance mode..."
cd $APP_DIR
php artisan down --retry=60
echo -e "${GREEN}✓ Maintenance mode enabled${NC}"

echo ""
echo "[2/8] Restoring database..."
mysql -u root -p dnsmanager < $BACKUP_DIR/database_$BACKUP_DATE.sql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database restored${NC}"
else
    echo -e "${RED}✗ Database restore failed${NC}"
    php artisan up
    exit 1
fi

echo ""
echo "[3/8] Restoring application files..."
tar -xzf $BACKUP_DIR/app_files_$BACKUP_DATE.tar.gz -C $APP_DIR
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Application files restored${NC}"
else
    echo -e "${RED}✗ File restore failed${NC}"
    php artisan up
    exit 1
fi

echo ""
echo "[4/8] Restoring .env file..."
cp $BACKUP_DIR/env_$BACKUP_DATE $APP_DIR/.env
echo -e "${GREEN}✓ .env restored${NC}"

echo ""
echo "[5/8] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Dependencies installed${NC}"

echo ""
echo "[6/8] Clearing and caching..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Caches refreshed${NC}"

echo ""
echo "[7/8] Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo "[8/8] Restarting services..."
systemctl restart php$PHP_VERSION-fpm
systemctl reload nginx
echo -e "${GREEN}✓ Services restarted${NC}"

echo ""
echo "Disabling maintenance mode..."
php artisan up
echo -e "${GREEN}✓ Maintenance mode disabled${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}✓ Rollback completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Restored from backup: $BACKUP_DATE"
