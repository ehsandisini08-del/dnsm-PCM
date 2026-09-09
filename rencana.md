# MASTER PROMPT — LARAVEL DNS MANAGER + POWERDNS

Saya ingin membangun aplikasi **DNS Manager profesional untuk ISP** menggunakan Laravel sebagai control panel dan PowerDNS sebagai DNS backend.

Project ini harus dibuat dengan pendekatan production-ready, modular, aman, ringan, mudah dikembangkan, dan cocok digunakan oleh ISP.

Jangan hanya membuat mockup atau tampilan frontend. Buat aplikasi yang benar-benar berfungsi, termasuk database, authentication, CRUD, PowerDNS integration, API, validation, authorization, logging, monitoring, dan deployment.

---

# 1. TUJUAN PROJECT

Buat aplikasi bernama:

**DNS Manager**

Fungsi utama:

* Mengelola DNS Zone
* Mengelola DNS Record
* Mengelola beberapa DNS Server
* Integrasi Laravel dengan PowerDNS
* User management
* Role & permission
* Customer management
* DNS templates
* Audit log
* DNS health monitoring
* REST API
* Dashboard statistik
* Backup/restore konfigurasi
* Sistem keamanan
* Multi DNS server
* Dukungan DNSDist di tahap berikutnya

Aplikasi ditujukan untuk penggunaan ISP.

---

# 2. TEKNOLOGI

Gunakan stack berikut:

Backend:

* PHP 8.3+
* Laravel versi stabil terbaru yang kompatibel
* Filament versi stabil yang kompatibel dengan Laravel
* MySQL atau MariaDB
* Redis
* Laravel Queue
* Laravel Scheduler

DNS:

* PowerDNS Authoritative Server
* PowerDNS menggunakan database MySQL/MariaDB
* DNSDist sebagai optional layer / tahap lanjutan

Web server:

* Nginx

OS target:

* Ubuntu Server 24.04 LTS

Jangan menggunakan teknologi tambahan yang tidak diperlukan.

Prioritaskan kesederhanaan dan performa.

---

# 3. ARSITEKTUR

Gunakan arsitektur:

Internet
↓
DNSDist (optional)
↓
PowerDNS Authoritative
↓
MariaDB

Laravel DNS Manager:

Laravel
↓
PowerDNS Database / PowerDNS API
↓
PowerDNS

Laravel berfungsi sebagai control panel.

Jangan membuat Laravel mengedit file zone BIND9 secara langsung.

PowerDNS harus menjadi authoritative DNS backend.

---

# 4. PRINSIP PENTING

Aplikasi harus:

* Secure by default
* Tidak menyimpan password dalam plaintext
* Menggunakan Laravel authentication
* Menggunakan authorization/policy
* Validasi semua input
* Mencegah SQL Injection
* Mencegah XSS
* Mencegah CSRF
* Rate limiting untuk API
* Audit semua perubahan DNS
* Tidak menampilkan credential sensitif
* Menggunakan environment variable untuk credential
* Memiliki error handling
* Memiliki logging
* Memiliki database transaction ketika diperlukan

Jangan pernah hardcode:

* password database
* API key
* secret
* password DNS
* credential server

Gunakan `.env`.

---

# 5. STRUKTUR MODUL

Buat modul berikut:

## Dashboard

Tampilkan:

* Total Zones
* Total DNS Records
* Total Customers
* Total Users
* Total DNS Servers
* DNS Server Online
* DNS Server Offline
* Recent DNS Activity
* Recent Login
* DNS Query statistics jika tersedia

Dashboard harus responsive.

---

# 6. DNS ZONES

Buat module:

**Zones**

Fitur:

* List zone
* Search zone
* Filter zone
* Create zone
* Edit zone
* Delete zone
* View zone
* Import zone
* Export zone
* Enable/disable zone
* Assign zone ke customer
* Assign zone ke DNS server

Contoh:

pelangicomm.net

Saat membuka zone tampil:

Zone:

pelangicomm.net

Records:

A
AAAA
CNAME
MX
TXT
NS
SRV
CAA
PTR
SOA

---

# 7. DNS RECORDS

Support minimal:

* A
* AAAA
* CNAME
* MX
* TXT
* NS
* SRV
* CAA
* PTR
* SOA

CRUD:

* Create
* Read
* Update
* Delete

Form harus menyesuaikan record type.

Contoh A:

Name:
www

Value:
103.xxx.xxx.xxx

TTL:
3600

Contoh MX:

Name:
@

Priority:
10

Value:
mail.domain.com

Contoh TXT:

Name:
@

Value:
v=spf1 ...

TTL:
3600

Validasi harus berbeda berdasarkan type record.

---

# 8. DNS RECORD VALIDATION

Implement validation yang benar.

A:

* IPv4 valid

AAAA:

* IPv6 valid

CNAME:

* hostname valid

MX:

* priority wajib
* hostname valid

TXT:

* support SPF
* DKIM
* DMARC
* TXT panjang

SRV:

* priority
* weight
* port
* target

CAA:

* flags
* tag
* value

Jangan menerima data DNS yang invalid.

---

# 9. DNS SERVER MANAGEMENT

Buat:

**DNS Servers**

Contoh:

NS1
IP:
103.xxx.xxx.xxx

NS2
IP:
103.xxx.xxx.xxx

Field:

* Name
* Hostname
* IP Address
* Type
* Status
* Port
* API URL
* API credential
* Description

Jangan tampilkan credential pada UI.

Buat health check:

* Ping
* TCP 53
* UDP 53
* DNS query
* API connectivity

Status:

ONLINE
OFFLINE
WARNING

Gunakan Laravel Scheduler/Queue untuk health check.

---

# 10. POWERDNS INTEGRATION

Buat service class:

PowerDNSService.php

Jangan mencampurkan logic PowerDNS ke controller.

Gunakan service layer.

Contoh:

createZone()
deleteZone()
updateZone()
createRecord()
updateRecord()
deleteRecord()
getZone()
getRecords()
syncZone()

Semua operasi harus memiliki error handling.

Jika PowerDNS menggunakan database backend, gunakan struktur database PowerDNS yang sesuai.

Jangan merusak tabel internal PowerDNS.

Jika memungkinkan gunakan PowerDNS API untuk operasi yang memang lebih tepat menggunakan API.

---

# 11. ZONE SYNC

Buat sistem:

Laravel
↕
PowerDNS

Tampilkan status:

SYNCED
PENDING
ERROR

Jika perubahan gagal, jangan menganggap data berhasil.

Simpan error log.

---

# 12. CUSTOMER MANAGEMENT

Buat:

Customers

Field:

* Name
* Email
* Phone
* Address
* Company
* Status
* Notes

Customer dapat memiliki banyak domain/zone.

Relasi:

Customer
hasMany Zones

Zone
belongsTo Customer

---

# 13. USER MANAGEMENT

Role minimal:

Super Admin
DNS Admin
Operator
Customer

Permission:

Dashboard
Zones
Records
Customers
DNS Servers
Templates
Users
Audit Logs
Settings
API

Customer hanya boleh mengakses zone miliknya.

Operator tidak boleh menghapus user.

Super Admin memiliki seluruh permission.

Gunakan Laravel Policies / Filament authorization.

---

# 14. DNS TEMPLATE

Buat fitur:

DNS Templates

Contoh:

Website Template

@
A
103.xxx.xxx.xxx

www
A
103.xxx.xxx.xxx

Mail Template

@
MX
10 mail.domain.com

mail
A
103.xxx.xxx.xxx

User dapat membuat template sendiri.

Saat membuat zone baru:

[ Apply Template ]

Template otomatis membuat record.

---

# 15. AUDIT LOG

Semua perubahan DNS harus dicatat.

Contoh:

User:
admin

Action:
CREATE_RECORD

Zone:
pelangicomm.net

Record:
vpn.pelangicomm.net

Type:
A

## Old Value:

New Value:
103.xxx.xxx.xxx

IP Address:
xxx.xxx.xxx.xxx

Timestamp:
datetime

Action minimal:

LOGIN
LOGOUT
CREATE_ZONE
UPDATE_ZONE
DELETE_ZONE
CREATE_RECORD
UPDATE_RECORD
DELETE_RECORD
CREATE_USER
UPDATE_USER
DELETE_USER
CREATE_SERVER
UPDATE_SERVER
DELETE_SERVER

---

# 16. API

Buat REST API.

Endpoint:

GET /api/v1/zones

POST /api/v1/zones

GET /api/v1/zones/{id}

PUT /api/v1/zones/{id}

DELETE /api/v1/zones/{id}

GET /api/v1/zones/{id}/records

POST /api/v1/zones/{id}/records

PUT /api/v1/records/{id}

DELETE /api/v1/records/{id}

GET /api/v1/dns-servers

GET /api/v1/customers

Gunakan:

Laravel Sanctum

Tambahkan:

* API token
* Token permission
* Rate limiting
* Request validation
* API error response standar

Format JSON:

{
"success": true,
"message": "DNS record created successfully",
"data": {}
}

Error:

{
"success": false,
"message": "Validation failed",
"errors": {}
}

---

# 17. DNS LOOKUP / TEST

Buat halaman:

DNS Tools

Fitur:

DNS Lookup

Input:

domain.com

Record:

A
AAAA
MX
TXT
NS
CNAME

Tampilkan:

* Result
* TTL
* Server
* Response time
* Status

Tambahkan juga:

DNS propagation check jika memungkinkan.

---

# 18. DNS SERVER MONITORING

Dashboard:

DNS Server

NS1
● ONLINE

NS2
● ONLINE

Monitoring:

* DNS response time
* Port 53
* Query test
* Server availability

Gunakan Scheduler.

Contoh:

setiap 1 menit.

Jangan menjalankan proses berat setiap request HTTP.

Gunakan:

Laravel Queue
Laravel Scheduler
Redis

---

# 19. SEARCH & FILTER

Semua halaman list harus mempunyai:

Search
Filter
Sorting
Pagination

Untuk Zone:

* Name
* Customer
* Status
* DNS Server

Untuk Record:

* Type
* Name
* Zone
* Value

Untuk Customer:

* Status
* Name

---

# 20. UI / UX

Gunakan Filament.

UI harus:

* Modern
* Clean
* Responsive
* Mobile friendly
* Desktop friendly
* Tidak terlalu banyak animasi
* Cepat
* Mudah dipahami

Sidebar:

Dashboard

DNS Management
├── Zones
├── Records
├── DNS Servers
├── Templates
└── DNS Tools

Customers

Users & Access
├── Users
├── Roles
└── API Tokens

Monitoring

Audit Logs

Settings

---

# 21. DATABASE

Buat migration yang diperlukan.

Pisahkan tabel aplikasi dengan tabel PowerDNS jika diperlukan.

Minimal tabel aplikasi:

users
customers
dns_servers
dns_templates
dns_template_records
audit_logs
api_tokens
server_health_checks

Jika menggunakan tabel PowerDNS:

domains
records
domainmetadata
cryptokeys
comments
supermasters
tsigkeys

Jangan membuat struktur database PowerDNS secara sembarangan.

Ikuti schema PowerDNS yang sesuai versi yang digunakan.

---

# 22. SECURITY

Implementasikan:

* Authentication
* Authorization
* Policies
* CSRF
* XSS protection
* SQL injection protection
* Rate limiting
* Secure headers
* Password hashing
* Session security
* API token security
* Audit log
* Login throttling

Jangan expose:

.env
storage/logs
database credentials
PowerDNS API key

Nginx harus dikonfigurasi dengan benar.

---

# 23. BACKUP

Buat sistem backup:

Database Laravel
Database PowerDNS
Application configuration

Buat halaman:

Backups

Actions:

Create Backup
Download Backup
Delete Backup
Restore Backup

Restore harus memiliki confirmation.

Jangan menghapus backup lama secara otomatis tanpa konfigurasi retention.

---

# 24. IMPORT / EXPORT

Zone dapat:

Import BIND zone file

Contoh:

$ORIGIN example.com.

@ IN SOA ...
@ IN NS ns1.example.com.
@ IN A 103.xxx.xxx.xxx
www IN A 103.xxx.xxx.xxx

Parser harus membaca record dengan aman.

Export:

BIND Zone File

JSON
CSV jika diperlukan.

---

# 25. DNSDIST

Jangan jadikan DNSDist sebagai dependency wajib untuk versi pertama.

Buat arsitektur agar nanti bisa ditambahkan.

Target:

Internet
↓
DNSDist
↓
PowerDNS NS1
PowerDNS NS2

DNSDist dapat digunakan untuk:

* Load balancing
* Health checking
* Rate limiting
* DNS traffic distribution
* Failover

Buat dokumentasi konfigurasi DNSDist sebagai tahap deployment lanjutan.

---

# 26. DEPLOYMENT

Buat dokumentasi lengkap untuk Ubuntu 24.04.

Instal:

Nginx
PHP
MariaDB
Redis
PowerDNS
Laravel
Composer
Supervisor

Buat:

systemd service jika diperlukan.

Buat konfigurasi:

Nginx
PHP-FPM
PowerDNS
Redis
Queue worker
Scheduler

---

# 27. DNS SERVER SETUP

Dokumentasikan:

Install PowerDNS.

Configure:

authoritative=yes

Database backend:

gmysql

PowerDNS harus listen:

UDP 53
TCP 53

Test:

dig example.com
dig @server-ip example.com

Pastikan:

DNSSEC dapat dikembangkan nantinya.

---

# 28. ENVIRONMENT

Gunakan:

.env

Contoh:

APP_NAME="DNS Manager"
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=dnsmanager
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=127.0.0.1

PDNS_API_URL=
PDNS_API_KEY=

Jangan commit .env.

Buat:

.env.example

---

# 29. LOGGING

Gunakan Laravel logging.

Pisahkan jika diperlukan:

application.log
dns.log
powerdns.log
health.log

Jangan memasukkan password/API key ke log.

---

# 30. ERROR HANDLING

Jika PowerDNS down:

Laravel harus menampilkan:

"DNS server tidak dapat dihubungi."

Bukan error stack trace.

Untuk production:

APP_DEBUG=false

Gunakan custom error handling.

---

# 31. TESTING

Buat automated tests.

Minimal:

Authentication test
Zone CRUD test
Record CRUD test
Record validation test
Customer test
Authorization test
API test
PowerDNS service test

Pastikan:

php artisan test

berhasil.

---

# 32. CODE QUALITY

Gunakan:

* Service classes
* Form Requests
* Policies
* Resources
* Actions jika diperlukan
* Repositories hanya jika memang diperlukan

Jangan membuat controller terlalu besar.

Jangan memasukkan semua logic ke Filament Resource.

Gunakan struktur code yang mudah dipelihara.

---

# 33. DOCUMENTATION

Buat:

README.md

Isi:

* Project overview
* Requirements
* Installation
* Environment
* Database setup
* PowerDNS setup
* Laravel setup
* Nginx
* Queue
* Scheduler
* DNS configuration
* API
* Backup
* Troubleshooting

Buat juga:

docs/

architecture.md
installation.md
powerdns.md
dnsdist.md
api.md
security.md
backup.md
troubleshooting.md

---

# 34. DEVELOPMENT METHOD

Jangan mencoba membuat seluruh project dalam satu langkah besar.

Kerjakan secara bertahap.

PHASE 1

Setup:

Laravel
Filament
Database
Authentication

PHASE 2

PowerDNS

Install/configuration
Database
Connection
DNS test

PHASE 3

Zone management

PHASE 4

Record management

PHASE 5

Customer management

PHASE 6

User/Role/Permission

PHASE 7

DNS server management

PHASE 8

Audit log

PHASE 9

API

PHASE 10

Monitoring

PHASE 11

Backup/restore

PHASE 12

Import/export

PHASE 13

DNS tools

PHASE 14

DNSDist integration

PHASE 15

Security hardening

PHASE 16

Testing

PHASE 17

Production deployment

---

# 35. ATURAN PENTING UNTUK AI CODING AGENT

Sebelum menulis kode:

1. Analisa project terlebih dahulu.
2. Periksa versi Laravel/Filament yang digunakan.
3. Periksa kompatibilitas PHP.
4. Periksa schema PowerDNS.
5. Tentukan struktur database.
6. Tentukan architecture.
7. Buat implementation plan.

Jangan menghapus kode existing tanpa alasan.

Jika project masih kosong, buat struktur project dari awal.

Setiap selesai satu phase:

* Jalankan test
* Periksa error
* Perbaiki error
* Pastikan migration berhasil
* Pastikan aplikasi dapat dijalankan

Jangan melanjutkan phase berikutnya jika phase sebelumnya rusak.

---

# 36. OUTPUT SETIAP PHASE

Setelah setiap phase, berikan:

### Changed Files

Daftar file yang dibuat/diubah.

### Database Changes

Migration/model yang dibuat.

### Features

Fitur yang berhasil dibuat.

### Commands

Command yang harus dijalankan.

### Testing

Test yang dijalankan.

### Known Issues

Jika ada masalah, jelaskan.

---

# 37. JANGAN MELAKUKAN INI

Jangan:

* Membuat mock data sebagai pengganti database
* Membuat DNS palsu
* Membuat PowerDNS palsu
* Hardcode credential
* Menaruh password di source code
* Menggunakan BIND9 zone file jika PowerDNS sudah digunakan
* Membuat controller raksasa
* Mengabaikan authorization
* Mengabaikan validation
* Mengabaikan error handling
* Menggunakan package yang tidak diperlukan
* Membuat UI yang hanya bagus tetapi backend tidak berfungsi

Semua fitur harus benar-benar terhubung dengan backend.

---

# 38. HASIL AKHIR YANG DIHARAPKAN

Saya ingin mendapatkan aplikasi:

**DNS Manager**

dengan:

Laravel
+
Filament
+
MariaDB
+
PowerDNS
+
Redis

yang dapat:

* Membuat zone
* Menghapus zone
* Mengubah zone
* Membuat DNS record
* Mengubah DNS record
* Menghapus DNS record
* Mengelola customer
* Mengelola user
* Mengelola role
* Mengelola DNS server
* Monitoring DNS
* Audit log
* API
* Backup
* Restore
* Import zone
* Export zone
* DNS lookup
* Template DNS

dan nantinya:

Laravel
↓
DNSDist
↓
PowerDNS Cluster

---

# 39. MULAI SEKARANG

Mulai dari PHASE 1.

Jangan langsung mengerjakan seluruh phase.

Pertama:

1. Analisa requirement.
2. Tentukan versi Laravel dan Filament yang kompatibel.
3. Tentukan struktur project.
4. Buat Laravel project.
5. Install Filament.
6. Setup authentication.
7. Setup database.
8. Buat layout dashboard dasar.
9. Buat migration dasar untuk aplikasi.
10. Jalankan test.
11. Pastikan project dapat dijalankan.

Setelah PHASE 1 selesai, tampilkan:

* Struktur folder
* File yang dibuat
* Migration
* Command instalasi
* Cara menjalankan project
* Test result
* Masalah yang ditemukan

**Jangan lanjut ke PHASE 2 sebelum saya memberikan instruksi lanjut.**
