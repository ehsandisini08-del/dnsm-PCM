# Architecture Overview — DNS Manager + PowerDNS

## 1. System High-Level Topology

```
                  +-----------------------+
                  | Internet DNS Queries  |
                  +-----------+-----------+
                              |
                              v
                  +-----------------------+
                  |  DNSDist Load Balancer|  (Optional / High Availability)
                  |     Port 53 UDP/TCP   |
                  +-----+-----------+-----+
                        |           |
            +-----------+           +-----------+
            v                                   v
+-----------------------+           +-----------------------+
| PowerDNS NS1 (Auth)   |           | PowerDNS NS2 (Auth)   |
|   103.xxx.xxx.1:53    |           |   103.xxx.xxx.2:53    |
+-----------+-----------+           +-----------+-----------+
            |                                   |
            +-----------------+-----------------+
                              |
                              v
                  +-----------------------+
                  | MariaDB / MySQL Galera|
                  | PowerDNS Database (DB)|
                  +-----------+-----------+
                              ^
                              | (Direct DB / PowerDNS API)
                  +-----------+-----------+
                  |   PowerDNSService     |
                  +-----------+-----------+
                              |
                  +-----------+-----------+
                  |  Laravel Control Panel|
                  |     (DNS Manager)     |
                  +-----------+-----------+
                              |
                  +-----------+-----------+
                  |  Filament UI / REST   |
                  +-----------------------+
```

## 2. Core Components & Responsibilities

1. **Laravel DNS Manager (Control Panel):**
   - Serves the Filament Web UI and REST API.
   - Communicates with PowerDNS directly through MariaDB (`gmysql` backend) and PowerDNS Webserver HTTP API.
   - Manages Users, Roles, Customers, DNS Templates, Audit Logs, and System Backups.
   - Does **not** edit raw BIND zone files directly on the filesystem.

2. **PowerDNS Authoritative Server (`pdns_server`):**
   - Authoritative nameserver responding to internet queries on UDP/TCP port 53.
   - Reads zone and record definitions directly from the MySQL/MariaDB `domains` and `records` tables.
   - Automatically supports instant record updates without server restarts.

3. **PowerDNSService (Service Layer):**
   - Encapsulates all domain and record business logic.
   - Formats FQDNs, validates RFC 1035 data types, increments SOA serials (`YYYYMMDDnn`), and synchronizes notifications.

4. **DNSDist (Optional HA Layer):**
   - Distributed DNS Load Balancer and query router.
   - Performs dynamic health checking, query filtering, DDoS rate limiting, and backend failover.
