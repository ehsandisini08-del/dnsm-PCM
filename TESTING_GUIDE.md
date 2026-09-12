# Manual Testing Guide - OAuth & Approval Workflow

## Prerequisites

### 1. Database Setup
✅ MySQL/MariaDB running (port 3306)
✅ Database `dnsmanager` created
✅ Migrations executed: `php artisan migrate`
✅ Super Admin user created

**Super Admin Credentials:**
- Email: `admin@dnsmanager.test`
- Password: `password123`

### 2. Environment Configuration

**Email Testing (OTP Codes):**
```env
MAIL_MAILER=log
```
- OTP codes akan muncul di: `storage/logs/laravel.log`
- Monitoring real-time: `tail -f storage/logs/laravel.log` (Linux/Mac) atau `Get-Content storage\logs\laravel.log -Wait` (Windows PowerShell)

**Google OAuth (Optional - untuk test OAuth):**
```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 3. Start Development Server
```bash
php artisan serve
# Access: http://localhost:8000
```

---

## Test Scenarios

### 🧪 SCENARIO 1: Manual Registration Flow

**Steps:**
1. **Navigate to Register Page**
   ```
   URL: http://localhost:8000/admin/register
   ```

2. **Fill Registration Form**
   - Name: `Test User Manual`
   - Email: `testuser@example.com`
   - Password: `password123`
   - Password Confirmation: `password123`

3. **Submit Registration**
   - Click "Daftar" button

4. **Verify Redirect**
   - ✅ Should redirect to: `/admin/verify-otp`
   - ✅ Page title: "Aktivasi Akun via OTP Email"

5. **Get OTP Code from Log**
   ```bash
   # Windows PowerShell
   Get-Content storage\logs\laravel.log -Tail 50 | Select-String "OTP"
   
   # Or open file manually
   notepad storage\logs\laravel.log
   ```
   - Look for: `AccountActivationOtpNotification`
   - Find 6-digit code (e.g., `123456`)

6. **Enter OTP Code**
   - Input the 6-digit code
   - Click "Verifikasi Kode OTP"

7. **Verify Email Activation**
   - ✅ Should redirect to: `/admin/pending-approval`
   - ✅ Message: "Email Anda Berhasil Diverifikasi!"
   - ✅ Shows: "Pendaftaran akun Anda telah berhasil..."

8. **Verify Database State**
   ```bash
   php artisan tinker --execute="echo json_encode(User::where('email', 'testuser@example.com')->first(['email', 'email_verified_at', 'approval_status', 'is_active'])->toArray());"
   ```
   - ✅ `email_verified_at`: NOT NULL
   - ✅ `approval_status`: `pending`
   - ✅ `is_active`: `false`

---

### 🧪 SCENARIO 2: Super Admin Approval Workflow

**Steps:**
1. **Login as Super Admin**
   ```
   URL: http://localhost:8000/admin/login
   Email: admin@dnsmanager.test
   Password: password123
   ```

2. **Enter Super Admin 2FA OTP**
   - Check log for Login OTP code
   - Enter OTP code
   - ✅ Should login successfully

3. **Navigate to Users Page**
   - Click "Users" menu in sidebar

4. **Filter Pending Users**
   - Click filter icon
   - Select "Approval Status" → "Pending Approval"
   - ✅ Should see `testuser@example.com` in list

5. **Approve User**
   - Click "Setujui" (Approve) button on pending user
   - Modal opens: "Setujui Pendaftaran Akun"
   - Select Role: "Operator NOC"
   - Click confirm button

6. **Verify Approval Notification**
   - ✅ Success notification: "Akun Disetujui"
   - ✅ Check log: `AccountApprovedNotification` sent

7. **Verify Database State**
   ```bash
   php artisan tinker --execute="echo json_encode(User::where('email', 'testuser@example.com')->first(['email', 'approval_status', 'is_active', 'role', 'approved_at', 'approved_by'])->toArray());"
   ```
   - ✅ `approval_status`: `approved`
   - ✅ `is_active`: `true`
   - ✅ `role`: `operator`
   - ✅ `approved_at`: NOT NULL
   - ✅ `approved_by`: Super Admin user ID

---

### 🧪 SCENARIO 3: Approved User Login with 2FA

**Steps:**
1. **Logout Super Admin**
   - Click logout button

2. **Login as Approved User**
   ```
   Email: testuser@example.com
   Password: password123
   ```

3. **Verify 2FA OTP Sent**
   - ✅ Should redirect to: `/admin/verify-otp`
   - ✅ Page shows: "Masukkan Kode Keamanan OTP"
   - ✅ Check log: `LoginOtpNotification` with 6-digit code

4. **Enter 2FA OTP**
   - Input the 6-digit code from log
   - Click "Verifikasi Kode OTP"

5. **Verify Login Success**
   - ✅ Should redirect to: `/admin` (Dashboard)
   - ✅ Success notification: "Login Berhasil"
   - ✅ Dashboard is accessible

6. **Verify Panel Access**
   - ✅ Can see sidebar menus
   - ✅ Can navigate to different pages
   - ✅ User is authenticated

---

### 🧪 SCENARIO 4: Google OAuth Registration (Optional)

**Prerequisites:**
- Google OAuth credentials configured in `.env`

**Steps:**
1. **Navigate to Register Page**
   ```
   URL: http://localhost:8000/admin/register
   ```

2. **Click "Daftar dengan Google"**
   - Button below the registration form

3. **Complete Google OAuth**
   - Redirects to Google login
   - Login with Google account
   - Grant permissions
   - Redirects back to app

4. **Verify New User Created**
   - ✅ Should redirect to: `/admin/verify-otp`
   - ✅ User created with:
     - `google_id`: Google user ID
     - `email`: From Google account
     - `approval_status`: `pending`
     - `email_verified_at`: NULL

5. **Complete Activation OTP**
   - Get OTP from log
   - Verify OTP
   - ✅ Redirect to pending approval page

6. **Approval & Login Flow**
   - (Same as Scenario 2 & 3)

---

### 🧪 SCENARIO 5: Rejected User Login Attempt

**Steps:**
1. **Super Admin Rejects a User**
   - Login as Super Admin
   - Navigate to Users
   - Filter: "Pending Approval"
   - Click "Tolak" (Reject) on a pending user
   - Confirm rejection

2. **Verify Rejection**
   - ✅ User `approval_status`: `rejected`
   - ✅ User `is_active`: `false`

3. **Rejected User Tries to Login**
   - Logout Super Admin
   - Try login with rejected user credentials
   - ✅ Error notification: "Akses Ditolak"
   - ✅ Message: "Pendaftaran akun Anda telah ditolak..."
   - ✅ User NOT logged in

---

### 🧪 SCENARIO 6: OTP Resend Functionality

**Steps:**
1. **Start Registration/Login Flow**
   - Register new user OR login approved user
   - Reach OTP verification page

2. **Click "Kirim Ulang Kode"**
   - Don't enter OTP yet
   - Click "Kirim Ulang Kode" button

3. **Verify New OTP Sent**
   - ✅ Success notification: "Kode OTP Baru Terkirim"
   - ✅ Check log: New OTP code generated
   - ✅ Old OTP code is invalidated

4. **Test Rate Limiting**
   - Click "Kirim Ulang Kode" again immediately
   - ✅ Warning notification: "Mohon Tunggu"
   - ✅ Message: "Permintaan kirim ulang OTP hanya dapat dilakukan 1 kali per menit"

5. **Wait 60 Seconds & Try Again**
   - Wait 60 seconds
   - Click "Kirim Ulang Kode" again
   - ✅ Should work and send new OTP

---

### 🧪 SCENARIO 7: Invalid OTP Handling

**Steps:**
1. **Reach OTP Verification Page**
   - Register or login to trigger OTP

2. **Enter Invalid OTP**
   - Input: `999999` (wrong code)
   - Click "Verifikasi Kode OTP"

3. **Verify Error Shown**
   - ✅ Error notification: "Kode OTP Salah" or "Kode OTP Tidak Valid"
   - ✅ User stays on OTP page
   - ✅ Can retry with correct code

4. **Test Expired OTP**
   - Wait 6+ minutes (OTP expires after 5 minutes)
   - Try to verify with the expired OTP
   - ✅ Error: "Kode OTP... telah kedaluwarsa"

---

### 🧪 SCENARIO 8: Pending User Login Attempt

**Steps:**
1. **Register New User**
   - Complete registration
   - Verify activation OTP
   - Reach pending approval page

2. **Try to Login Before Approval**
   - Logout or open incognito window
   - Try to login with pending user credentials
   - Enter correct email/password

3. **Verify Redirect**
   - ✅ Should redirect to: `/admin/pending-approval`
   - ✅ Shows: "Akun Sedang Ditinjau"
   - ✅ NOT logged in to dashboard

---

## Edge Cases Testing

### ⚠️ Rate Limiting Tests

**Login Rate Limit:**
- Try 6+ failed login attempts
- ✅ Should be rate limited
- ✅ Notification: "Terlalu Banyak Percobaan"

**OTP Verification Rate Limit:**
- Try 6+ wrong OTP codes
- ✅ Should be rate limited
- ✅ Notification: "Terlalu Banyak Percobaan"

### 🔐 Security Tests

**OTP Code Not Visible:**
- Check database:
  ```bash
  php artisan tinker --execute="echo User::whereNotNull('otp_code')->first()->otp_code ?? 'No OTP found';"
  ```
- ✅ Should be hashed (bcrypt), NOT plaintext

**Session Management:**
- After OTP verification, session keys should be cleared
- Check: `auth_pending_user_id`, `auth_otp_type` should be NULL

---

## Troubleshooting

### Issue: OTP Code Not Found in Log

**Solution:**
```bash
# Make sure log file exists
New-Item -ItemType File -Path storage\logs\laravel.log -Force

# Real-time monitoring (PowerShell)
Get-Content storage\logs\laravel.log -Wait -Tail 20

# Search for OTP in log
Get-Content storage\logs\laravel.log | Select-String "OTP" -Context 2
```

### Issue: Email Not Sent

**Check Configuration:**
```bash
php artisan config:show mail
# Verify MAIL_MAILER=log
```

### Issue: Google OAuth Error

**Common Causes:**
1. Invalid `GOOGLE_CLIENT_ID` or `GOOGLE_CLIENT_SECRET`
2. Redirect URI mismatch in Google Console
3. OAuth consent screen not configured

**Fix:**
- Verify credentials in Google Cloud Console
- Add authorized redirect URI: `http://localhost:8000/auth/google/callback`

---

## Expected Results Summary

| Scenario | Expected Outcome |
|----------|------------------|
| Manual Registration | User created → OTP sent → Email verified → Pending approval |
| Google OAuth Registration | User created with google_id → OTP sent → Pending approval |
| Super Admin Approval | Pending user → Approved → Notification sent → User active |
| Super Admin Rejection | Pending user → Rejected → User cannot login |
| Approved User Login | Credentials verified → 2FA OTP sent → OTP verified → Logged in |
| Rejected User Login | Error shown → User NOT logged in |
| Pending User Login | Redirected to pending page → NOT logged in |
| OTP Resend | New OTP generated → Rate limited to 1/min |
| Invalid OTP | Error shown → Can retry |
| Expired OTP | Error shown → Request new OTP |

---

## Database Queries for Verification

### Check User States
```bash
# All pending users
php artisan tinker --execute="echo json_encode(User::pendingApproval()->get(['name', 'email', 'approval_status'])->toArray());"

# All approved users
php artisan tinker --execute="echo json_encode(User::approved()->get(['name', 'email', 'approval_status'])->toArray());"

# User with OTP code (should be hashed)
php artisan tinker --execute="echo json_encode(User::whereNotNull('otp_code')->first(['email', 'otp_code', 'otp_expires_at']));"
```

### Clear Test Data
```bash
# Delete test users (be careful!)
php artisan tinker --execute="User::where('email', 'like', '%@example.com')->delete(); echo 'Test users deleted';"

# Or refresh entire database
php artisan migrate:fresh
# Then recreate Super Admin
php artisan tinker --execute="User::create(['name' => 'Super Admin', 'email' => 'admin@dnsmanager.test', 'password' => bcrypt('password123'), 'role' => 'super_admin', 'is_active' => true, 'approval_status' => 'approved', 'email_verified_at' => now(), 'approved_at' => now()]);"
```

---

## Success Criteria

✅ **Registration Flow:**
- User can register via form
- User can register via Google OAuth
- OTP activation email sent
- Email verified after OTP

✅ **Approval Workflow:**
- Pending users visible in Users table
- Super Admin can approve with role assignment
- Super Admin can reject users
- Approval notification sent

✅ **Login & 2FA:**
- Approved users receive 2FA OTP
- 2FA OTP required before dashboard access
- Rejected users cannot login
- Pending users cannot access dashboard

✅ **Security:**
- OTP codes are hashed in database
- Rate limiting prevents brute force
- Sessions properly managed
- Authorization checks work

✅ **Edge Cases:**
- Invalid OTP shows error
- Expired OTP shows error
- Resend OTP works with rate limit
- All user states handled correctly

---

## Next Steps After Manual Testing

1. ✅ Run automated test suite: `php artisan test`
2. ✅ Format code with Pint: `vendor/bin/pint --dirty --format agent`
3. ✅ Review and commit changes to git
4. 📝 Update documentation if needed
5. 🚀 Deploy to staging/production

---

**Testing Date:** _____________  
**Tester:** _____________  
**All Scenarios Passed:** ☐ Yes ☐ No  
**Issues Found:** _____________
