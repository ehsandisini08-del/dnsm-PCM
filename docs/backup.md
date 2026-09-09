# Backup & Disaster Recovery Guide

## 1. Automated Snapshot Mechanism

The DNS Manager features an integrated `BackupService` accessible through the Filament UI at **Settings > Backups & Snapshots** or via CLI.

Each snapshot captures:
1. Complete PowerDNS Database (`domains`, `records`, `comments`, `domainmetadata`, `cryptokeys`, `tsigkeys`, `supermasters`)
2. Application Customers, Nameservers, DNS Templates, and Users
3. Metadata (total zones, total records, timestamp, application version)

---

## 2. Backup Storage & Formats

- Directory: `storage/app/backups/`
- Format: JSON formatted database snapshot with SHA metadata
- File Naming: `<label>_YYYY-MM-DD_HHMMSS.json`

---

## 3. Creating & Restoring Backups

### Via Web UI
1. Navigate to **Settings > Backups & Snapshots**.
2. Enter an optional backup label and click **Create Backup Now**.
3. To restore: Click **Restore** next to any backup snapshot. Confirm the prompt to synchronize database tables.

### Manual Database Dump (CLI)
```bash
# Export full MariaDB database
mysqldump -u root -p dnsmanager > /backup/dnsmanager_$(date +%F).sql

# Restore MariaDB database
mysql -u root -p dnsmanager < /backup/dnsmanager_2026-09-08.sql
```
