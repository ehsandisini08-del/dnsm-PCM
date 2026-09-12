# 🚀 Quick Deployment Reference Card

## DNS Manager - Production Update Guide

---

## ⚡ SUPER QUICK DEPLOYMENT (3 Commands)

```bash
# SSH to production server
ssh user@your-production-server

# Run these 3 commands:
cd /var/www/dnsmanager
sudo bash scripts/production-backup.sh
sudo bash scripts/deploy-production.sh
```

**Done! Deployment complete in ~5 minutes.**

---

## 📋 DEPLOYMENT CHECKLIST (Copy & Paste)

```bash
# ✅ Pre-flight checks
cd /var/www/dnsmanager
git remote -v  # Verify GitHub connection
php artisan --version  # Verify Laravel
mysql -V  # Verify MySQL

# ✅ Step 1: BACKUP (CRITICAL!)
sudo bash scripts/production-backup.sh

# ✅ Step 2: DEPLOY
sudo bash scripts/deploy-production.sh

# ✅ Step 3: VERIFY
curl -I https://yourdomain.com/admin/login
php artisan migrate:status
tail -50 storage/logs/laravel.log

# ✅ Step 4: TEST
# Visit: https://yourdomain.com/admin/register
# Visit: https://yourdomain.com/admin/login
```

---

## 🔄 ROLLBACK (If Something Goes Wrong)

```bash
cd /var/www/dnsmanager
sudo bash scripts/rollback-production.sh
# Enter backup date when prompted (format: YYYYMMDD_HHMMSS)
```

---

## ⚙️ REQUIRED .ENV UPDATES

**Add to `/var/www/dnsmanager/.env`:**

```env
# Google OAuth
GOOGLE_CLIENT_ID=your-production-client-id
GOOGLE_CLIENT_SECRET=your-production-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback

# Email (for OTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

**After editing .env:**
```bash
sudo php artisan config:clear
sudo php artisan config:cache
```

---

## 🐛 QUICK TROUBLESHOOTING

### Issue: 500 Error After Deployment
```bash
sudo php artisan config:clear
sudo php artisan cache:clear
sudo chown -R www-data:www-data storage bootstrap/cache
sudo systemctl restart php8.3-fpm nginx
```

### Issue: Migration Failed
```bash
sudo php artisan migrate:status
sudo php artisan migrate:rollback --step=1
sudo php artisan migrate --force
```

### Issue: Google OAuth Not Working
```bash
sudo php artisan config:show services.google
# Verify GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET
# Check Google Console: redirect URI must match exactly
```

### Issue: Email Not Sent
```bash
sudo php artisan tinker
# Run: Mail::raw('Test', fn($m) => $m->to('test@email.com')->subject('Test'));
# Check logs: tail -f storage/logs/laravel.log
```

---

## 📊 POST-DEPLOYMENT MONITORING

```bash
# Watch errors
sudo tail -f /var/www/dnsmanager/storage/logs/laravel.log | grep -i error

# Check new users
sudo php artisan tinker --execute="echo 'New users today: ' . User::whereDate('created_at', today())->count();"

# Check pending approvals
sudo php artisan tinker --execute="echo 'Pending: ' . User::where('approval_status', 'pending')->count();"
```

---

## ✅ VERIFICATION TESTS

**1. Registration Flow:**
- Visit: `https://yourdomain.com/admin/register`
- Register → Receive OTP email → Verify → Pending approval page

**2. Google OAuth:**
- Visit: `https://yourdomain.com/admin/login`
- Click "Masuk dengan Google" → OAuth → OTP email → Verify

**3. Super Admin Approval:**
- Login as admin → Users menu → Filter "Pending" → Approve user

**4. Approved User Login:**
- Login with approved user → Receive 2FA OTP → Verify → Dashboard

---

## 📞 EMERGENCY CONTACTS

**Critical Issue? Roll back immediately:**
```bash
sudo bash scripts/rollback-production.sh
```

**Check deployment logs:**
```bash
# Application
tail -100 /var/www/dnsmanager/storage/logs/laravel.log

# System
sudo journalctl -u php8.3-fpm -n 100
sudo journalctl -u nginx -n 100
```

---

## 📦 FILES IN THIS DEPLOYMENT

**New Features:**
- ✅ Google OAuth authentication
- ✅ Email OTP verification (activation & 2FA)
- ✅ User approval workflow
- ✅ Super Admin approve/reject actions
- ✅ Email notifications

**Scripts Added:**
- `scripts/production-backup.sh` - Backup automation
- `scripts/deploy-production.sh` - Deployment automation
- `scripts/rollback-production.sh` - Rollback automation

**Documentation:**
- `DEPLOYMENT_GUIDE.md` - Full deployment guide
- `TESTING_GUIDE.md` - Manual testing scenarios
- `QUICK_DEPLOY.md` - This quick reference

**Tests:**
- `tests/Feature/OAuthAndApprovalWorkflowTest.php` - 24 tests
- `tests/Feature/AuthTest.php` - Updated with 2 tests
- Total: 30 tests (100% passing)

---

## 🎯 SUCCESS CRITERIA

✅ Application accessible
✅ No errors in logs
✅ Migration completed
✅ Users can register with OTP
✅ Google OAuth works
✅ Super Admin can approve users
✅ 2FA OTP works for login
✅ Existing features work

---

## ⏱️ ESTIMATED TIMELINE

| Task | Duration |
|------|----------|
| Backup | 2 minutes |
| Deployment | 3-5 minutes |
| Verification | 2-3 minutes |
| **Total** | **7-10 minutes** |

---

## 🔐 SECURITY REMINDERS

- [ ] APP_DEBUG=false in production
- [ ] Strong database passwords
- [ ] Google OAuth production credentials only
- [ ] HTTPS enabled and forced
- [ ] File permissions: 775 for storage/
- [ ] .env file permissions: 600
- [ ] Firewall: Block MySQL port 3306 from public

---

## 📅 DEPLOYMENT INFO

**Deployed:** 2026-09-12
**Version:** OAuth & Approval Workflow v1.0
**Git Commit:** 3b2690e
**Repository:** https://github.com/ehsandisini08-del/dnsm-PCM

---

**Need detailed instructions?** → Read `DEPLOYMENT_GUIDE.md`
**Need testing scenarios?** → Read `TESTING_GUIDE.md`
