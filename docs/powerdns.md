# PowerDNS Authoritative Server Setup Guide

## 1. PowerDNS Installation (Ubuntu 24.04 LTS)

```bash
# Install PowerDNS Authoritative Server with Generic MySQL Backend
sudo apt update
sudo apt install -y pdns-server pdns-backend-mysql
```

---

## 2. PowerDNS Configuration

Edit `/etc/powerdns/pdns.conf`:

```ini
# Main Settings
launch=gmysql
authoritative=yes
setuid=pdns
setgid=pdns

# Network Binding
local-address=0.0.0.0, ::
local-port=53

# Built-in Webserver & HTTP API Configuration
webserver=yes
webserver-address=127.0.0.1
webserver-port=8081
webserver-allow-from=127.0.0.1,::1
api=yes
api-key=YourSecurePdnsApiKeyHere

# Generic MySQL Backend Credentials
gmysql-host=127.0.0.1
gmysql-port=3306
gmysql-dbname=dnsmanager
gmysql-user=dnsuser
gmysql-password=YourSecurePasswordHere
gmysql-dnssec=yes

# Query Cache (Fast response)
query-cache-ttl=20
cache-ttl=20
negquery-cache-ttl=60
```

---

## 3. Disabling systemd-resolved Conflict

Ubuntu 24.04 binds `systemd-resolved` to port 53 by default. To allow PowerDNS to bind port 53:

```bash
# Disable stub listener in resolved.conf
sudo sed -i 's/#DNSStubListener=yes/DNSStubListener=no/' /etc/systemd/resolved.conf
sudo systemctl restart systemd-resolved

# Restart and enable PowerDNS
sudo systemctl restart pdns
sudo systemctl enable pdns
```

---

## 4. Testing PowerDNS Operational Status

Verify PowerDNS is listening on UDP/TCP port 53:
```bash
sudo ss -tulpn | grep 53
```

Test a DNS query directly against the server:
```bash
dig @127.0.0.1 example.com A +short
dig @127.0.0.1 example.com SOA
```

Test PowerDNS HTTP API connectivity:
```bash
curl -H 'X-API-Key: YourSecurePdnsApiKeyHere' http://127.0.0.1:8081/api/v1/servers/localhost
```
