# Troubleshooting & Diagnostics Guide

## 1. Common Issues & Solutions

### Issue: "DNS server tidak dapat dihubungi" / Port 53 Connection Refused
**Cause:** PowerDNS daemon (`pdns`) is not running or is blocked by `systemd-resolved`.
**Solution:**
```bash
sudo systemctl status pdns
sudo ss -tulpn | grep 53
# If port 53 is held by systemd-resolved:
sudo sed -i 's/#DNSStubListener=yes/DNSStubListener=no/' /etc/systemd/resolved.conf
sudo systemctl restart systemd-resolved
sudo systemctl restart pdns
```

---

### Issue: DNS Queries return NXDOMAIN or No Record
**Cause:** The SOA record is missing or record names are not fully qualified.
**Solution:**
1. Open the Zone in **DNS Management > Zones**.
2. Click **Sync Zone** — this automatically validates and repairs missing SOA and NS records.
3. Check using the **DNS Management > DNS Tools** page or via CLI:
   ```bash
   dig @127.0.0.1 example.com SOA
   dig @127.0.0.1 example.com A
   ```

---

### Issue: Filament UI Styling or Asset Glitch
**Solution:**
```bash
php artisan filament:assets
php artisan view:clear
php artisan config:clear
```

---

### Issue: Redis Connection Exception on Local Machine
**Cause:** Redis service is not installed/running while `.env` is set to `SESSION_DRIVER=redis`.
**Solution:**
Switch `.env` drivers to `database`:
```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```
Then run:
```bash
php artisan config:clear
```

---

## 2. Log Files Location
- Laravel Application Logs: `storage/logs/laravel.log`
- Supervisor Worker Logs: `storage/logs/worker.log`
- PowerDNS System Logs: `sudo journalctl -u pdns -f`
- Nginx Error Logs: `/var/log/nginx/error.log`
