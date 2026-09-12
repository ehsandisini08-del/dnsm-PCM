# 🚀 Production Deployment Guide
## DNS Manager - OAuth & Approval Workflow Update

---

## 📋 Quick Start

### **Option 1: Automated Deployment (Recommended)**

```bash
# On Production Server (via SSH)
cd /var/www/dnsmanager

# 1. Backup (IMPORTANT!)
bash scripts/production-backup.sh

# 2. Deploy
bash scripts/deploy-production.sh

# 3. Verify
curl -I https://yourdomain.com/admin/login
```

### **Option 2: Manual Step-by-Step**

Follow the detailed instructions below.

---

## 🔐 PRE-DEPLOYMENT CHECKLIST

### **1. Server Requirements:**
- [ ] Ubuntu Server 20.04/22.04/24.04 LTS
- [ ] PHP 8.3 or higher
- [ ] MySQL/MariaDB 10.4 or higher
- [ ] Nginx or Apache
- [ ] Composer 2.x
- [ ] Node.js 18+ and npm
- [ ] Git
- [ ] Supervisor (for queue workers)

### **2. Access Requirements:**
- [ ] SSH access to production server
- [ ] sudo privileges
- [ ] Database credentials
- [ ] GitHub repository access

### **3. Backup Verification:**
- [ ] Database backup created
- [ ] Application files backup created
- [ ] .env file backup created
- [ ] Backups stored in safe location

---

## 📦 METHOD 1: AUTOMATED DEPLOYMENT

### **Step 1: Upload Deployment Scripts**

```bash
# On your local machine
scp scripts/production-backup.sh user@your-server:/var/www/dnsmanager/scripts/
scp scripts/deploy-production.sh user@your-server:/var/www/dnsmanager/scripts/
scp scripts/rollback-production.sh user@your-server:/var/www/dnsmanager/scripts/

# SSH to server
ssh user@your-server

# Make scripts executable
cd /var/www/dnsmanager
chmod +x scripts/*.sh
```

### **Step 2: Create Backup**

```bash
sudo bash scripts/production-backup.sh
```

**Expected Output:**
```
==========================================
DNS Manager - Production Backup Script
==========================================

[1/4] Creating database backup...
✓ Database backup created: /var/backups/dnsmanager/database_20260912_053000.sql

[2/4] Creating application files backup...
✓ Application files backup created: /var/backups/dnsmanager/app_files_20260912_053000.tar.gz

[3/4] Backing up .env file...
✓ .env backup created: /var/backups/dnsmanager/env_20260912_053000

[4/4] Backup verification...
✓ All backups created successfully!

==========================================
✓ Backup completed successfully!
==========================================
```

### **Step 3: Deploy Update**

```bash
sudo bash scripts/deploy-production.sh
```

**Expected Output:**
```
==========================================
DNS Manager - Production Deployment
==========================================

[1/12] Enabling maintenance mode...
✓ Maintenance mode enabled

[2/12] Pulling latest code from GitHub...
✓ Code pulled successfully

[3/12] Installing Composer dependencies...
✓ Composer dependencies installed

[4/12] Installing NPM dependencies...
✓ NPM dependencies installed

[5/12] Building frontend assets...
✓ Frontend assets built

[6/12] Running database migrations...
✓ Migrations completed

[7/12] Clearing caches...
✓ Caches cleared

[8/12] Caching configuration...
✓ Configuration cached

[9/12] Optimizing autoloader...
✓ Autoloader optimized

[10/12] Setting permissions...
✓ Permissions set

[11/12] Restarting services...
✓ Services restarted

[12/12] Disabling maintenance mode...
✓ Maintenance mode disabled

==========================================
✓ Deployment completed successfully!
==========================================
```

### **Step 4: Verify Deployment**

```bash
# Check application status
curl -I https://yourdomain.com/admin/login

# Check latest commit
cd /var/www/dnsmanager
git log -1 --oneline

# Check migrations
php artisan migrate:status

# Monitor logs
tail -f storage/logs/laravel.log
```

---

## 🔧 METHOD 2: MANUAL DEPLOYMENT (Step-by-Step)

### **Step 1: SSH to Production Server**

```bash
ssh user@your-production-server
```

### **Step 2: Backup Current State** ⚠️ CRITICAL

```bash
# Create backup directory
sudo mkdir -p /var/backups/dnsmanager
cd /var/backups/dnsmanager

# Backup database
sudo mysqldump -u root -p dnsmanager > database_$(date +%Y%m%d_%H%M%S).sql

# Backup application files
sudo tar -czf app_files_$(date +%Y%m%d_%H%M%S).tar.gz -C /var/www/dnsmanager .

# Backup .env
sudo cp /var/www/dnsmanager/.env env_$(date +%Y%m%d_%H%M%S)

# Verify backups
ls -lh
```

### **Step 3: Enable Maintenance Mode**

```bash
cd /var/www/dnsmanager
sudo php artisan down --retry=60
```

**Users will see:**
```
Service Unavailable
The application is currently down for maintenance. Please try again in a few moments.
```

### **Step 4: Pull Latest Code**

```bash
# Stash any local changes (if any)
sudo git stash

# Pull latest code from GitHub
sudo git fetch origin
sudo git pull origin main

# Verify latest commit
git log -1
```

**Expected Output:**
```
commit d0275bf...
Author: Your Name
Date:   Thu Sep 12 2026

    feat: implement comprehensive OAuth, OTP, and approval workflow with tests
```

### **Step 5: Update Dependencies**

```bash
# Update Composer dependencies
sudo composer install --no-dev --optimize-autoloader

# Update NPM dependencies
sudo npm install --production

# Build frontend assets
sudo npm run build
```

### **Step 6: Run Migrations**

```bash
# Check pending migrations
sudo php artisan migrate:status

# Run migrations
sudo php artisan migrate --force
```

**Expected Output:**
```
INFO Running migrations.

2026_09_11_092127_add_auth_fields_and_approval_to_users_table .. DONE
```

**Migration adds:**
- `google_id` column
- `otp_code` column
- `otp_expires_at` column
- `approval_status` column (enum: pending, approved, rejected)
- `approved_at` column
- `approved_by` column (foreign key to users)

### **Step 7: Clear & Cache**

```bash
# Clear all caches
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan view:clear
sudo php artisan route:clear

# Cache configuration
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache

# Optimize autoloader
sudo composer dump-autoload --optimize
```

### **Step 8: Set Permissions**

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/dnsmanager/storage
sudo chown -R www-data:www-data /var/www/dnsmanager/bootstrap/cache

# Set permissions
sudo chmod -R 775 /var/www/dnsmanager/storage
sudo chmod -R 775 /var/www/dnsmanager/bootstrap/cache

# Verify permissions
ls -la storage/
```

### **Step 9: Restart Services**

```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart Queue Worker (if using Supervisor)
sudo supervisorctl restart laravel-worker:*

# Verify services are running
sudo systemctl status php8.3-fpm
sudo systemctl status nginx
```

### **Step 10: Disable Maintenance Mode**

```bash
sudo php artisan up
```

### **Step 11: Verify Deployment**

```bash
# Check application is accessible
curl -I https://yourdomain.com

# Check admin panel
curl -I https://yourdomain.com/admin/login

# Check migration status
sudo php artisan migrate:status

# Monitor logs
sudo tail -f storage/logs/laravel.log
```

---

## 🔍 POST-DEPLOYMENT VERIFICATION

### **1. Check Application Health**

```bash
# Test homepage
curl -I https://yourdomain.com

# Expected: HTTP/2 200 OK
```

### **2. Test Database**

```bash
cd /var/www/dnsmanager
sudo php artisan tinker --execute="echo 'Users: ' . User::count();"

# Check new columns exist
sudo php artisan tinker --execute="echo json_encode(User::first(['id', 'google_id', 'approval_status']));"
```

### **3. Test Registration Flow**

```bash
# Visit in browser
https://yourdomain.com/admin/register
```

**Expected:**
- ✅ Registration form loads
- ✅ "Daftar dengan Google" button visible
- ✅ Can submit registration form
- ✅ Redirects to OTP verification page after registration

### **4. Test Super Admin Login**

```bash
# Visit in browser
https://yourdomain.com/admin/login

# Login with credentials:
Email: admin@yourdomain.com
Password: [your-super-admin-password]
```

**Expected:**
- ✅ Login form loads
- ✅ After login, receives 2FA OTP via email
- ✅ OTP verification page loads
- ✅ After OTP verification, dashboard accessible

### **5. Monitor Logs**

```bash
# Watch application logs
sudo tail -f /var/www/dnsmanager/storage/logs/laravel.log

# Watch Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Watch PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

---

## ⚙️ ENVIRONMENT CONFIGURATION

### **Required .env Updates for Production**

```bash
sudo nano /var/www/dnsmanager/.env
```

Add/Update these lines:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your-production-client-id
GOOGLE_CLIENT_SECRET=your-production-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback

# Email Configuration (for OTP emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-app-specific-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Production Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

**After updating .env:**
```bash
sudo php artisan config:clear
sudo php artisan config:cache
```

---

## 🔄 ROLLBACK PROCEDURE

### **If Deployment Fails:**

#### **Option 1: Use Rollback Script**

```bash
cd /var/www/dnsmanager
sudo bash scripts/rollback-production.sh

# Enter backup date when prompted
# Example: 20260912_053000
```

#### **Option 2: Manual Rollback**

```bash
# 1. Enable maintenance mode
sudo php artisan down

# 2. Restore database
sudo mysql -u root -p dnsmanager < /var/backups/dnsmanager/database_20260912_053000.sql

# 3. Restore application files
cd /var/www
sudo rm -rf dnsmanager
sudo tar -xzf /var/backups/dnsmanager/app_files_20260912_053000.tar.gz -C /var/www/dnsmanager

# 4. Restore .env
sudo cp /var/backups/dnsmanager/env_20260912_053000 /var/www/dnsmanager/.env

# 5. Install dependencies
cd /var/www/dnsmanager
sudo composer install --no-dev --optimize-autoloader

# 6. Clear caches
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan config:cache

# 7. Restart services
sudo systemctl restart php8.3-fpm nginx

# 8. Disable maintenance mode
sudo php artisan up
```

---

## 🐛 TROUBLESHOOTING

### **Issue 1: Migration Fails**

**Symptoms:**
```
SQLSTATE[42S21]: Column already exists: google_id
```

**Solution:**
```bash
# Check if migration already ran
sudo php artisan migrate:status

# If migration shows as "Ran", rollback and re-run
sudo php artisan migrate:rollback --step=1
sudo php artisan migrate --force
```

### **Issue 2: 500 Internal Server Error**

**Check:**
```bash
# View error logs
sudo tail -100 storage/logs/laravel.log

# Check PHP-FPM errors
sudo tail -50 /var/log/php8.3-fpm.log

# Check Nginx errors
sudo tail -50 /var/log/nginx/error.log
```

**Common fixes:**
```bash
# Clear all caches
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan view:clear

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Restart services
sudo systemctl restart php8.3-fpm nginx
```

### **Issue 3: Google OAuth Not Working**

**Check:**
```bash
# Verify .env configuration
sudo php artisan config:show services.google

# Test redirect
curl -I https://yourdomain.com/auth/google/redirect
```

**Fix:**
1. Verify Google Cloud Console settings:
   - Authorized redirect URI: `https://yourdomain.com/auth/google/callback`
   - OAuth consent screen configured
   - Credentials are for production (not development)

2. Clear config cache:
   ```bash
   sudo php artisan config:clear
   sudo php artisan config:cache
   ```

### **Issue 4: Email OTP Not Sent**

**Check:**
```bash
# Test email configuration
sudo php artisan tinker
```

```php
Mail::raw('Test email', function($msg) {
    $msg->to('your@email.com')->subject('Test');
});
```

**Fix:**
1. Verify SMTP credentials in `.env`
2. Check if using Gmail: Enable "App Passwords"
3. Check firewall: Port 587 (TLS) must be open
4. Check logs: `tail -f storage/logs/laravel.log`

### **Issue 5: Users Cannot Access Dashboard**

**Symptoms:**
- User can login but redirected to pending approval page
- User shows as approved in database but still can't access

**Check:**
```bash
sudo php artisan tinker --execute="echo json_encode(User::where('email', 'user@example.com')->first(['approval_status', 'is_active', 'email_verified_at']));"
```

**Expected values for approved user:**
- `approval_status`: "approved"
- `is_active`: true
- `email_verified_at`: not null

**Fix:**
```bash
# Manually approve user
sudo php artisan tinker --execute="User::where('email', 'user@example.com')->update(['approval_status' => 'approved', 'is_active' => true, 'email_verified_at' => now(), 'approved_at' => now()]);"
```

---

## 📊 MONITORING AFTER DEPLOYMENT

### **First 24 Hours Checklist:**

```bash
# Monitor errors
sudo tail -f /var/www/dnsmanager/storage/logs/laravel.log | grep -i error

# Monitor user registrations
sudo php artisan tinker --execute="echo 'New registrations today: ' . User::whereDate('created_at', today())->count();"

# Monitor pending approvals
sudo php artisan tinker --execute="echo 'Pending approvals: ' . User::where('approval_status', 'pending')->count();"

# Check disk space
df -h

# Check memory usage
free -h

# Check CPU usage
top -n 1 | head -20
```

### **Set Up Monitoring (Optional)**

```bash
# Create monitoring cron job
sudo crontab -e
```

Add:
```cron
# Check for errors every 5 minutes
*/5 * * * * grep -i "error" /var/www/dnsmanager/storage/logs/laravel.log | mail -s "Laravel Errors" admin@yourdomain.com

# Daily pending approval report
0 9 * * * php /var/www/dnsmanager/artisan tinker --execute="echo User::pendingApproval()->count() . ' users pending approval';" | mail -s "Pending Approvals" admin@yourdomain.com
```

---

## ✅ DEPLOYMENT CHECKLIST

### **Pre-Deployment:**
- [ ] Read deployment guide completely
- [ ] Backup database
- [ ] Backup application files
- [ ] Backup .env file
- [ ] Notify users of maintenance window
- [ ] Test deployment in staging environment (if available)

### **During Deployment:**
- [ ] Enable maintenance mode
- [ ] Pull latest code
- [ ] Install dependencies
- [ ] Run migrations
- [ ] Clear & cache configuration
- [ ] Set correct permissions
- [ ] Restart services
- [ ] Disable maintenance mode

### **Post-Deployment:**
- [ ] Verify application is accessible
- [ ] Test registration flow
- [ ] Test Google OAuth login
- [ ] Test Super Admin approval workflow
- [ ] Test 2FA OTP flow
- [ ] Check error logs
- [ ] Monitor for 24 hours
- [ ] Update documentation

---

## 📞 SUPPORT

### **Need Help?**

**Check Logs:**
```bash
# Application logs
sudo tail -100 /var/www/dnsmanager/storage/logs/laravel.log

# System logs
sudo journalctl -u php8.3-fpm -n 100
sudo journalctl -u nginx -n 100
```

**Test Database Connection:**
```bash
sudo php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';"
```

**Verify Services:**
```bash
sudo systemctl status php8.3-fpm
sudo systemctl status nginx
sudo systemctl status mysql
```

---

## 🎯 SUCCESS CRITERIA

Deployment is successful when:

✅ Application accessible at production URL
✅ No errors in logs
✅ Database migrations completed
✅ Users can register and receive OTP
✅ Google OAuth login works
✅ Super Admin can approve/reject users
✅ Approved users can login with 2FA
✅ All existing functionality still works

---

**Deployment prepared on:** 2026-09-12
**Version:** OAuth & Approval Workflow v1.0
**Estimated downtime:** 5-10 minutes
