#!/bin/bash
###############################################################################
# Production Deployment Script for DNS Manager
# Deploys OAuth, OTP, and Approval Workflow feature
###############################################################################

set -e

echo "=========================================="
echo "DNS Manager - Production Deployment"
echo "=========================================="
echo ""

# Configuration
APP_DIR="/var/www/dnsmanager"
BRANCH="main"
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "8.4")

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Change to application directory
cd $APP_DIR

echo "[1/12] Enabling maintenance mode..."
php artisan down --retry=60
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Maintenance mode enabled${NC}"
else
    echo -e "${RED}✗ Failed to enable maintenance mode${NC}"
    exit 1
fi

echo ""
echo "[2/12] Pulling latest code from GitHub..."
git fetch origin
git pull origin $BRANCH
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Code pulled successfully${NC}"
else
    echo -e "${RED}✗ Git pull failed${NC}"
    php artisan up
    exit 1
fi

echo ""
echo "[3/12] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${RED}✗ Composer install failed${NC}"
    php artisan up
    exit 1
fi

echo ""
echo "[4/12] Installing NPM dependencies..."
npm install --production
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ NPM dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ NPM install failed (non-critical)${NC}"
fi

echo ""
echo "[5/12] Building frontend assets..."
npm run build
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Frontend assets built${NC}"
else
    echo -e "${YELLOW}⚠ Build failed (non-critical)${NC}"
fi

echo ""
echo "[6/12] Running database migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    echo -e "${YELLOW}⚠ Rolling back...${NC}"
    php artisan migrate:rollback --force
    php artisan up
    exit 1
fi

echo ""
echo "[7/12] Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo ""
echo "[8/12] Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Configuration cached${NC}"

echo ""
echo "[9/12] Optimizing autoloader..."
composer dump-autoload --optimize
echo -e "${GREEN}✓ Autoloader optimized${NC}"

echo ""
echo "[10/12] Setting permissions, sudoers & systemd sandbox overrides for SSL Certbot..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Allow www-data to execute certbot and reload nginx without password
if [ "$EUID" -eq 0 ]; then
    cat << 'EOF' > /etc/sudoers.d/dnsmanager-ssl
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot, /usr/bin/systemctl reload nginx, /usr/sbin/nginx, /usr/bin/nginx
EOF
    chmod 440 /etc/sudoers.d/dnsmanager-ssl 2>/dev/null || true

    # Configure systemd drop-in override for PHP-FPM to allow write access to /etc/letsencrypt and /etc/nginx
    for SVC in php8.4-fpm php8.3-fpm php8.2-fpm; do
        if systemctl list-unit-files 2>/dev/null | grep -q "${SVC}"; then
            mkdir -p "/etc/systemd/system/${SVC}.service.d"
            cat << 'EOF' > "/etc/systemd/system/${SVC}.service.d/override.conf"
[Service]
ReadWritePaths=/etc/letsencrypt /etc/nginx /var/log/letsencrypt /var/lib/letsencrypt /var/www/dnsmanager
EOF
        fi
    done
    systemctl daemon-reload 2>/dev/null || true
fi
echo -e "${GREEN}✓ Permissions, sudoers, and systemd overrides configured${NC}"

echo ""
echo "[11/12] Restarting services..."
systemctl restart php$PHP_VERSION-fpm
systemctl reload nginx
echo -e "${GREEN}✓ Services restarted${NC}"

echo ""
echo "[12/12] Disabling maintenance mode..."
php artisan up
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Maintenance mode disabled${NC}"
else
    echo -e "${RED}✗ Failed to disable maintenance mode${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✓ Deployment completed successfully!${NC}"
echo "=========================================="
echo ""

# Verify deployment
echo "Deployment verification:"
echo "  Git commit: $(git log -1 --pretty=format:'%h - %s')"
echo "  PHP version: $(php -v | head -n 1)"
echo "  Laravel version: $(php artisan --version)"
echo ""
echo "Next steps:"
echo "  1. Test registration flow: https://yourdomain.com/admin/register"
echo "  2. Test Google OAuth: https://yourdomain.com/admin/login"
echo "  3. Test Super Admin approval: Users menu → Approve pending users"
echo "  4. Monitor logs: tail -f storage/logs/laravel.log"
