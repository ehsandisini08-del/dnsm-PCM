#!/bin/bash
###############################################################################
# Production Backup Script for DNS Manager
# Creates backup of database and application files before deployment
###############################################################################

set -e

echo "=========================================="
echo "DNS Manager - Production Backup Script"
echo "=========================================="
echo ""

# Configuration
BACKUP_DIR="/var/backups/dnsmanager"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/dnsmanager"
DB_NAME="dnsmanager"
DB_USER="root"

# Create backup directory
mkdir -p $BACKUP_DIR

echo "[1/4] Creating database backup..."
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/database_$DATE.sql
if [ $? -eq 0 ]; then
    echo "✓ Database backup created: $BACKUP_DIR/database_$DATE.sql"
else
    echo "✗ Database backup failed!"
    exit 1
fi

echo ""
echo "[2/4] Creating application files backup..."
tar -czf $BACKUP_DIR/app_files_$DATE.tar.gz -C $APP_DIR .
if [ $? -eq 0 ]; then
    echo "✓ Application files backup created: $BACKUP_DIR/app_files_$DATE.tar.gz"
else
    echo "✗ Application files backup failed!"
    exit 1
fi

echo ""
echo "[3/4] Backing up .env file..."
cp $APP_DIR/.env $BACKUP_DIR/env_$DATE
if [ $? -eq 0 ]; then
    echo "✓ .env backup created: $BACKUP_DIR/env_$DATE"
else
    echo "✗ .env backup failed!"
    exit 1
fi

echo ""
echo "[4/4] Backup verification..."
if [ -f "$BACKUP_DIR/database_$DATE.sql" ] && [ -f "$BACKUP_DIR/app_files_$DATE.tar.gz" ]; then
    echo "✓ All backups created successfully!"
    echo ""
    echo "Backup location: $BACKUP_DIR"
    ls -lh $BACKUP_DIR/*$DATE*
    echo ""
    echo "=========================================="
    echo "✓ Backup completed successfully!"
    echo "=========================================="
else
    echo "✗ Backup verification failed!"
    exit 1
fi

echo ""
echo "To restore from backup:"
echo "  Database: mysql -u $DB_USER -p $DB_NAME < $BACKUP_DIR/database_$DATE.sql"
echo "  Files: tar -xzf $BACKUP_DIR/app_files_$DATE.tar.gz -C $APP_DIR"
echo "  .env: cp $BACKUP_DIR/env_$DATE $APP_DIR/.env"
