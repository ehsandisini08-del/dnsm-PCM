# Security & Hardening Guide

## 1. Security Architecture Principles
- **Least Privilege Access:** Role-based access control with granular Laravel Policies (`UserPolicy`, `PdnsDomainPolicy`, `PdnsRecordPolicy`, `DnsServerPolicy`, `CustomerPolicy`, `DnsTemplatePolicy`, `AuditLogPolicy`).
- **No Plaintext Passwords:** Passwords hashed with Bcrypt (cost factor 12).
- **Masked Credentials:** API keys, database credentials, and secrets are strictly masked in logs and views.
- **Audit Trails:** All zone, record, user, customer, and authentication events are immutably logged with client IP address and User Agent.

---

## 2. API Rate Limiting & Throttling
- `/api/v1/login`: Throttled to **6 requests per minute** per IP to prevent brute force.
- `/api/v1/*` authenticated endpoints: Throttled to **60 requests per minute** per user token.

---

## 3. Web & HTTP Security Headers
Every HTTP response includes standard hardening headers:
```http
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

---

## 4. Production Environment Checklist
- Ensure `APP_DEBUG=false` in `.env`.
- Restrict database permissions to localhost only.
- Configure UFW firewall:
  ```bash
  sudo ufw default deny incoming
  sudo ufw default allow outgoing
  sudo ufw allow 22/tcp
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw allow 53/tcp
  sudo ufw allow 53/udp
  sudo ufw enable
  ```
- Block access to `.env`, `.git`, and `storage/logs/` in Nginx configuration.
