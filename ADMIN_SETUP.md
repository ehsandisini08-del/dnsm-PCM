# 🔐 Admin Setup Guide
## DNS Manager - Super Admin Creation

---

## 📋 Overview

Dokumen ini menjelaskan cara membuat Super Admin user yang dapat langsung login tanpa memerlukan approval dari admin lain.

---

## 🎯 Kapan Menggunakan Guide Ini?

Gunakan guide ini ketika:
- ✅ Fresh installation (belum ada Super Admin)
- ✅ Kehilangan akses ke semua Super Admin accounts
- ✅ Perlu membuat Super Admin tambahan
- ✅ Setup untuk testing/staging environment

---

## 🚀 Method 1: Menggunakan Artisan Command (Recommended)

### **Interactive Mode**

Cara termudah untuk membuat Super Admin adalah menggunakan Artisan command secara interactive:

```bash
cd /var/www/dnsmanager
php artisan app:create-super-admin
```

**Command akan prompt:**
1. **Full Name:** Masukkan nama lengkap (default: "Super Admin")
2. **Email Address:** Masukkan email address (harus unique)
3. **Password:** Masukkan password (minimum 8 karakter)
4. **Confirm Password:** Masukkan password lagi untuk konfirmasi
5. **Confirmation:** Review data dan konfirmasi pembuatan

**Example Output:**
```
╔═══════════════════════════════════════════════════╗
║   Create Super Admin - DNS Manager                ║
╚═══════════════════════════════════════════════════╝

Enter full name (Super Admin):
> John Doe

Enter email address:
> admin@yourdomain.com

Enter password:
> ****************

Confirm password:
> ****************

Review Super Admin Details:
+----------------+----------+
| Field          | Value    |
+----------------+----------+
| Name           | John Doe |
| Email          | admin@yourdomain.com |
| Role           | super_admin |
| Status         | approved |
| Active         | Yes      |
| Email Verified | Yes      |
+----------------+----------+

Create this Super Admin user? (yes/no) [yes]:
> yes

✓ Super Admin created successfully!

+----+----------+----------------------+-------------+
| ID | Name     | Email                | Role        |
+----+----------+----------------------+-------------+
| 3  | John Doe | admin@yourdomain.com | super_admin |
+----+----------+----------------------+-------------+

Login Credentials:
  Email:    admin@yourdomain.com
  Password: ****************

⚠ Please save these credentials securely and change the password after first login.
```

---

### **Non-Interactive Mode** (untuk automation/scripting)

Gunakan flags untuk membuat Super Admin tanpa interaksi:

```bash
php artisan app:create-super-admin \
    --email=admin@yourdomain.com \
    --password=SecurePassword123 \
    --name="System Administrator" \
    --no-interaction
```

**Example Output:**
```
✓ Super Admin created successfully!

+----+------------------------+----------------------+-------------+
| ID | Name                   | Email                | Role        |
+----+------------------------+----------------------+-------------+
| 3  | System Administrator   | admin@yourdomain.com | super_admin |
+----+------------------------+----------------------+-------------+

Login Credentials:
  Email:    admin@yourdomain.com
  Password: SecurePassword123

⚠ Please save these credentials securely and change the password after first login.
```

---

### **Command Options**

| Option | Description | Required | Example |
|--------|-------------|----------|---------|
| `--email` | Email address untuk Super Admin | Yes (non-interactive) | `--email=admin@domain.com` |
| `--password` | Password untuk Super Admin | Yes (non-interactive) | `--password=SecurePass123` |
| `--name` | Nama lengkap Super Admin | No (default: "Super Admin") | `--name="John Doe"` |
| `--no-interaction` | Run tanpa prompts | No | `--no-interaction` |

---

### **Command Help**

Untuk melihat bantuan lengkap:

```bash
php artisan app:create-super-admin --help
```

---

## 🛠️ Method 2: Menggunakan Tinker (Quick Manual)

Jika command tidak tersedia atau untuk quick fix, gunakan Laravel Tinker:

```bash
php artisan tinker --execute='User::create(["name" => "Super Admin", "email" => "admin@yourdomain.com", "password" => bcrypt("YourSecurePassword"), "role" => "super_admin", "is_active" => true, "approval_status" => "approved", "email_verified_at" => now(), "approved_at" => now()]); echo "Created!";'
```

**Ganti:**
- `admin@yourdomain.com` → email yang diinginkan
- `YourSecurePassword` → password yang aman

---

## ✅ Verifikasi Super Admin

Setelah membuat Super Admin, verify dengan:

### **1. Check User di Database**

```bash
php artisan tinker --execute='$u = User::where("email", "admin@yourdomain.com")->first(); echo "Name: " . $u->name . ", Role: " . $u->role . ", Status: " . $u->approval_status . ", Verified: " . ($u->email_verified_at ? "YES" : "NO");'
```

**Expected Output:**
```
Name: John Doe, Role: super_admin, Status: approved, Verified: YES
```

### **2. Test Login**

1. Visit: `https://yourdomain.com/admin/login`
2. Login dengan email & password yang dibuat
3. Expected flow:
   - ✅ Credentials accepted
   - ✅ Redirect ke OTP verification (2FA)
   - ✅ Masukkan OTP code dari email/log
   - ✅ Login ke dashboard

### **3. Check Panel Access**

```bash
php artisan tinker --execute='$u = User::where("email", "admin@yourdomain.com")->first(); echo "Can access panel: " . ($u->canAccessPanel(filament()->getDefaultPanel()) ? "YES" : "NO");'
```

**Expected Output:**
```
Can access panel: YES
```

---

## 🔐 Security Best Practices

### **Password Requirements**

**Minimum (enforced):**
- Panjang minimal: 8 karakter

**Recommended:**
- Panjang minimal: 12 karakter
- Kombinasi uppercase & lowercase
- Minimal 1 angka
- Minimal 1 special character
- Tidak menggunakan kata yang mudah ditebak

**Good Examples:**
- `Admin@SecureDNS2026!`
- `DNS-Mgmt-P@ssw0rd`
- `Sup3rAdm!n#2026`

**Bad Examples:**
- `password` (terlalu common)
- `admin123` (terlalu lemah)
- `12345678` (hanya angka)

---

### **Post-Creation Actions**

Setelah membuat Super Admin:

1. ✅ **Save credentials securely** - gunakan password manager
2. ✅ **Test login immediately** - pastikan credentials work
3. ✅ **Change password after first login** - jika password temporary
4. ✅ **Enable 2FA** - untuk extra security layer
5. ✅ **Document who has access** - untuk audit trail

---

### **Access Control**

**Super Admin capabilities:**
- ✅ Full access ke semua fitur sistem
- ✅ Approve/reject user registrations
- ✅ Manage all users (create, update, delete)
- ✅ Manage DNS zones & records
- ✅ Manage customers & DNS servers
- ✅ View audit logs
- ✅ System configuration

**IMPORTANT:**
- ⚠️ Batasi jumlah Super Admin (recommend: 2-3 max)
- ⚠️ Jangan share Super Admin credentials
- ⚠️ Review Super Admin access regularly
- ⚠️ Disable Super Admin accounts yang tidak digunakan

---

## 🐛 Troubleshooting

### **Issue 1: Command Not Found**

**Symptom:**
```bash
Command "app:create-super-admin" is not defined.
```

**Solution:**
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Re-discover commands
composer dump-autoload

# Try again
php artisan app:create-super-admin
```

---

### **Issue 2: Email Already Exists**

**Symptom:**
```
Validation Error: This email is already registered.
```

**Solution:**

**Option A: Use different email**
```bash
php artisan app:create-super-admin --email=admin2@yourdomain.com --password=SecurePass --no-interaction
```

**Option B: Update existing user to Super Admin**
```bash
php artisan tinker --execute='User::where("email", "existing@email.com")->update(["role" => "super_admin", "approval_status" => "approved", "is_active" => true, "email_verified_at" => now()]); echo "Updated!";'
```

**Option C: Delete existing user first** (⚠️ use with caution)
```bash
php artisan tinker --execute='User::where("email", "admin@yourdomain.com")->delete(); echo "Deleted!";'
```

---

### **Issue 3: Password Validation Failed**

**Symptom:**
```
Validation Error: Password must be at least 8 characters long.
```

**Solution:**
Gunakan password yang lebih panjang (minimum 8 karakter):
```bash
php artisan app:create-super-admin --email=admin@domain.com --password=SecurePassword123 --no-interaction
```

---

### **Issue 4: Cannot Login After Creation**

**Possible Causes & Solutions:**

**A. Email not verified (should not happen with this command, but check)**
```bash
php artisan tinker --execute='User::where("email", "admin@yourdomain.com")->update(["email_verified_at" => now()]); echo "Fixed!";'
```

**B. Not approved (should not happen)**
```bash
php artisan tinker --execute='User::where("email", "admin@yourdomain.com")->update(["approval_status" => "approved", "approved_at" => now()]); echo "Fixed!";'
```

**C. Not active**
```bash
php artisan tinker --execute='User::where("email", "admin@yourdomain.com")->update(["is_active" => true]); echo "Fixed!";'
```

**D. Password issue - reset password**
```bash
php artisan tinker --execute='User::where("email", "admin@yourdomain.com")->update(["password" => bcrypt("NewPassword123")]); echo "Password reset!";'
```

---

### **Issue 5: Permission Denied Errors**

**Symptom:**
```
Permission denied: /var/www/dnsmanager/storage/logs/laravel.log
```

**Solution:**
```bash
cd /var/www/dnsmanager
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 📊 Common Scenarios

### **Scenario 1: Fresh Installation**

```bash
# Step 1: Run migrations
php artisan migrate --force

# Step 2: Create first Super Admin
php artisan app:create-super-admin

# Step 3: Test login
curl -I https://yourdomain.com/admin/login
```

---

### **Scenario 2: Lost Access to All Admins**

```bash
# Create new Super Admin via tinker (emergency method)
php artisan tinker --execute='User::create(["name" => "Emergency Admin", "email" => "emergency@yourdomain.com", "password" => bcrypt("TempPassword123!"), "role" => "super_admin", "is_active" => true, "approval_status" => "approved", "email_verified_at" => now(), "approved_at" => now()]); echo "Created!";'

# Login with emergency@yourdomain.com / TempPassword123!
# Change password immediately after login
```

---

### **Scenario 3: Staging/Testing Environment**

```bash
# Create test Super Admin with known credentials
php artisan app:create-super-admin \
    --email=admin@test.local \
    --password=TestPassword123 \
    --name="Test Administrator" \
    --no-interaction

# Verify
php artisan tinker --execute='echo User::where("email", "admin@test.local")->exists() ? "Exists" : "Not found";'
```

---

### **Scenario 4: Production Deployment**

```bash
# Create production Super Admin with strong password
php artisan app:create-super-admin

# Recommended settings:
# Email: admin@production-domain.com
# Password: Generate strong random password (use password manager)
# Name: Production Administrator

# Document credentials securely
# Change password after first login
```

---

## 📝 Maintenance

### **Regular Tasks**

**Monthly:**
- Review list of Super Admin users
- Disable inactive Super Admin accounts
- Audit Super Admin activities via audit logs

**Quarterly:**
- Force password rotation for Super Admins
- Review and update access policies
- Test emergency access procedures

**Commands:**
```bash
# List all Super Admins
php artisan tinker --execute='User::where("role", "super_admin")->get(["id", "name", "email", "is_active"])->each(fn($u) => print_r($u->toArray()));'

# Count Super Admins
php artisan tinker --execute='echo "Total Super Admins: " . User::where("role", "super_admin")->count();'

# Find inactive Super Admins
php artisan tinker --execute='User::where("role", "super_admin")->where("is_active", false)->get(["id", "name", "email"])->each(fn($u) => print_r($u->toArray()));'
```

---

## 🔗 Related Documentation

- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Production deployment procedures
- [Testing Guide](TESTING_GUIDE.md) - Manual testing scenarios
- [Quick Deploy](QUICK_DEPLOY.md) - Quick reference for deployment

---

## 📞 Support

**Need Help?**
- Check [Troubleshooting](#troubleshooting) section above
- Review application logs: `tail -f storage/logs/laravel.log`
- Check GitHub issues: https://github.com/ehsandisini08-del/dnsm-PCM/issues

---

**Last Updated:** 2026-09-12  
**Version:** 1.0.0  
**Maintainer:** DNS Manager Team
