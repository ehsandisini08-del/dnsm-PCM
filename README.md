# Laravel DNS Manager + PowerDNS

Enterprise-grade **DNS Manager** built specifically for **ISPs and Hosting Providers** using **Laravel 11+**, **Filament**, and **PowerDNS Authoritative Server**.

---

## Key Features

- **Authoritative DNS Zone Management:** Create, edit, delete, enable/disable, and synchronize native, master, and slave DNS zones.
- **RFC-Compliant Record Types:** Support and strict validation for `A`, `AAAA`, `CNAME`, `MX`, `TXT`, `NS`, `SRV`, `CAA`, `PTR`, and `SOA` records.
- **Automated SOA Management:** Automatic serial incrementing (`YYYYMMDDnn`) and consistency enforcement on every record modification.
- **DNS Server Management & Monitoring:** Multi-nameserver cluster monitoring, periodic health checks (TCP 53, UDP 53, API status, latency ms) via Laravel Scheduler & Queue.
- **Customer Management:** Multi-zone customer account isolation and mapping.
- **Role-Based Access Control:** Strict authorization policies for *Super Admin*, *DNS Admin*, *Operator*, and *Customer*.
- **DNS Templates:** Reusable templates (Standard Web, Google Workspace, Microsoft 365, Custom ISP Mail) with dynamic variable replacement (`{ip}`, `{domain}`).
- **Immutable Audit Logging:** Full audit trail tracking all zone, record, customer, and authentication events with before/after diffs.
- **RESTful API (v1):** Token-based API with Laravel Sanctum, rate limiting, and customer-scoped endpoints.
- **DNS Diagnostics Tools:** Live DNS query lookup and global Anycast DNS propagation checker (Google, Cloudflare, Quad9, OpenDNS, Control D).
- **Import / Export:** Import standard BIND RFC 1035 zone files & JSON, and export to BIND `.zone` files.
- **Disaster Recovery:** Built-in full snapshot creation and restore engine.

---

## Tech Stack

- **Framework:** Laravel 11.x / 13.x (PHP 8.3+)
- **Admin Panel:** Filament
- **DNS Backend:** PowerDNS Authoritative Server (`gmysql` backend)
- **Database:** MariaDB 10.11+ / MySQL 8.0+
- **Cache & Queue:** Redis / Database driver
- **Web Server:** Nginx + PHP-FPM
- **OS Target:** Ubuntu Server 24.04 LTS

---

## 🚀 1-Click Automated Production Installation (Ubuntu 22.04 / 24.04 LTS)

You can install and configure the entire stack (**Nginx, PHP 8.3-FPM, MariaDB, Redis, PowerDNS Authoritative, Supervisor, and Laravel DNS Manager**) with a single command:

```bash
curl -sSL https://raw.githubusercontent.com/ehsandisini08-del/dnsm-PCM/main/install.sh | sudo bash
```

*The automated installer handles all package dependencies, resolves port 53 conflicts with systemd-resolved, creates secure database credentials, runs migrations/seeders, configures virtual hosts, and sets up background queue workers.*

---

## Quick Start (Local Development)

### 1. Requirements
- PHP 8.3 or 8.4
- Composer
- MariaDB or MySQL

### 2. Installation
```bash
# Clone repository
git clone <repo-url> dnsmanager
cd dnsmanager

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Run Migrations & Default Seeders
php artisan migrate --seed

# Publish Filament Assets
php artisan filament:assets
```

### 3. Create Super Admin
```bash
# Interactive mode (recommended)
php artisan app:create-super-admin

# Non-interactive mode
php artisan app:create-super-admin \
    --email=admin@yourdomain.com \
    --password=SecurePassword123 \
    --name="Super Admin" \
    --no-interaction
```

**Note:** Super Admin users are created with `approved` status and can login immediately without requiring approval. See [ADMIN_SETUP.md](ADMIN_SETUP.md) for detailed guide.

### 4. Running the Application
```bash
php artisan serve
```

### 5. Login
- URL: `http://localhost:8000/admin`
- Email: (email yang dibuat di step 3)
- Password: (password yang dibuat di step 3)
- 2FA: Check OTP code di `storage/logs/laravel.log` atau email

---

## Running Automated Tests

Run the comprehensive test suite with Pest:
```bash
php artisan test --compact
```

---

## Detailed Documentation

### Production Deployment & Administration

- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Complete production deployment procedures
- [Quick Deploy Reference](QUICK_DEPLOY.md) - Fast deployment cheat sheet
- [Admin Setup Guide](ADMIN_SETUP.md) - Super Admin creation and management
- [Testing Guide](TESTING_GUIDE.md) - Manual testing scenarios for OAuth & OTP workflow

### Technical Documentation

Comprehensive guides are available in the [`docs/`](./docs) folder:

- [Architecture Overview](./docs/architecture.md)
- [Ubuntu 24.04 Installation Guide](./docs/installation.md)
- [PowerDNS Server Setup](./docs/powerdns.md)
- [DNSDist Load Balancing](./docs/dnsdist.md)
- [REST API Reference (v1)](./docs/api.md)
- [Security & Hardening](./docs/security.md)
- [Backup & Disaster Recovery](./docs/backup.md)
- [Troubleshooting & Diagnostics](./docs/troubleshooting.md)

---

## License
MIT License.
# dnsm-PCM
# dnsm-PCM
