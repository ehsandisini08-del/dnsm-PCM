#!/usr/bin/env bash

# ==============================================================================
#  Laravel DNS Manager + PowerDNS — Automated 1-Click Installer
#  Target OS: Ubuntu Server 22.04 / 24.04 LTS & Debian 12
# ==============================================================================

set -e

# --- Color Definitions ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# --- Helper Functions ---
info() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

header() {
    echo -e "\n${BLUE}${BOLD}================================================================${NC}"
    echo -e "${BLUE}${BOLD} $1 ${NC}"
    echo -e "${BLUE}${BOLD}================================================================${NC}\n"
}

# --- 1. Root Check ---
if [[ $EUID -ne 0 ]]; then
   error "Script ini harus dijalankan sebagai root (gunakan sudo bash install.sh)."
   exit 1
fi

clear
echo -e "${PURPLE}${BOLD}"
echo "  ____  _   _ ____    __  __                                   "
echo " |  _ \| \ | / ___|  |  \/  | __ _ _ __   __ _  __ _  ___ _ __ "
echo " | | | |  \| \___ \  | |\/| |/ _\` | '_ \ / _\` |/ _\` |/ _ \ '__|"
echo " | |_| | |\  |___) | | |  | | (_| | | | | (_| | (_| |  __/ |   "
echo " |____/|_| \_|____/  |_|  |_|\__,_|_| |_|\__,_|\__, |\___|_|   "
echo "                                               |___/           "
echo -e "${NC}"
echo -e "${CYAN}${BOLD} Laravel DNS Manager + PowerDNS Authoritative Server — 1-Click Auto Installer${NC}"
echo -e "${CYAN} Production-Ready DNS Control Panel for ISPs & Hosting Providers${NC}\n"

# --- 2. Detect OS ---
header "STEP 1: Memeriksa Kompatibilitas Sistem Operasi"

if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    VER=$VERSION_ID
    info "Sistem Operasi Terdeteksi: $NAME $VERSION"
else
    error "Gagal mendeteksi sistem operasi. File /etc/os-release tidak ditemukan."
    exit 1
fi

if [[ "$OS" != "ubuntu" && "$OS" != "debian" ]]; then
    warn "Peringatan: Script ini dioptimalkan untuk Ubuntu 22.04/24.04 LTS atau Debian 12."
    read -p "Lanjutkan instalasi? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# --- 3. Interactive Configuration Prompts ---
header "STEP 2: Konfigurasi Parameter Instalasi"

# Auto-detect Public IP
PUBLIC_IP=$(curl -s -4 https://ifconfig.me/ip || curl -s -4 https://api.ipify.org || echo "127.0.0.1")

# Domain or IP for Web Panel
read -p "Masukkan Domain / Hostname Web Panel (tekan Enter untuk menggunakan IP [$PUBLIC_IP]): " INPUT_DOMAIN
DOMAIN=${INPUT_DOMAIN:-$PUBLIC_IP}

# Admin Email
read -p "Masukkan Email Administrator [admin@example.com]: " INPUT_EMAIL
ADMIN_EMAIL=${INPUT_EMAIL:-admin@example.com}

# Admin Password
RANDOM_PASS=$(openssl rand -base64 9 | tr -dc 'a-zA-Z0-9' | head -c 12)
read -p "Masukkan Password Administrator [tekan Enter untuk generate acak: $RANDOM_PASS]: " INPUT_PASS
ADMIN_PASSWORD=${INPUT_PASS:-$RANDOM_PASS}

# Primary & Secondary Nameservers
DEFAULT_NS1="ns1.${DOMAIN}"
DEFAULT_NS2="ns2.${DOMAIN}"
read -p "Masukkan Primary Nameserver [$DEFAULT_NS1]: " INPUT_NS1
NS1=${INPUT_NS1:-$DEFAULT_NS1}

read -p "Masukkan Secondary Nameserver [$DEFAULT_NS2]: " INPUT_NS2
NS2=${INPUT_NS2:-$DEFAULT_NS2}

# Install Directory
INSTALL_DIR="/var/www/dnsmanager"

# SSL Setup Prompt (If Domain is not an IP)
ENABLE_SSL=false
if [[ ! "$DOMAIN" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ && "$DOMAIN" != "localhost" ]]; then
    read -p "Apakah Anda ingin memasang SSL gratis Let's Encrypt (Certbot)? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ENABLE_SSL=true
    fi
fi

echo ""
info "Konfigurasi yang akan dipasang:"
echo " - Web Panel URL  : http://$DOMAIN"
echo " - Admin Email    : $ADMIN_EMAIL"
echo " - Admin Password : $ADMIN_PASSWORD"
echo " - Primary NS     : $NS1"
echo " - Secondary NS   : $NS2"
echo " - Directory      : $INSTALL_DIR"
echo " - Let's Encrypt  : $ENABLE_SSL"
echo ""

read -p "Lanjutkan proses instalasi otomatis sekarang? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    echo "Instalasi dibatalkan."
    exit 0
fi

# --- 4. Resolve Port 53 Conflict with systemd-resolved First ---
header "STEP 3: Membebaskan Port 53 & Konfigurasi DNS Resolver"

if systemctl is-active --quiet systemd-resolved 2>/dev/null || [ -d /etc/systemd ]; then
    info "Mengonfigurasi systemd-resolved agar tidak memblokir Port 53..."
    mkdir -p /etc/systemd/resolved.conf.d
    cat << 'EOF' > /etc/systemd/resolved.conf.d/dnsmanager.conf
[Resolve]
DNS=8.8.8.8 1.1.1.1
DNSStubListener=no
EOF
    systemctl restart systemd-resolved 2>/dev/null || true
    
    # Ensure local nameserver resolves properly during installation
    if [ -L /etc/resolv.conf ] || [ -f /etc/resolv.conf ]; then
        rm -f /etc/resolv.conf
        echo "nameserver 8.8.8.8" > /etc/resolv.conf
        echo "nameserver 1.1.1.1" >> /etc/resolv.conf
    fi
fi

# --- 5. Install Dependencies & Repositories ---
header "STEP 4: Mengunduh dan Memasang Dependensi Sistem"

export DEBIAN_FRONTEND=noninteractive

info "Mengupdate paket repositori APT..."
apt-get update -y

info "Memasang paket utilitas dasar..."
apt-get install -y software-properties-common curl git unzip jq ufw dnsutils \
                   redis-server mariadb-server supervisor nginx openssl tar

# Install PHP Repository (Ubuntu PPA / Debian Ondrej)
if [[ "$OS" == "ubuntu" ]]; then
    info "Menambahkan repositori PHP PPA (ondrej/php)..."
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
elif [[ "$OS" == "debian" ]]; then
    info "Menambahkan repositori PHP untuk Debian..."
    apt-get install -y apt-transport-https lsb-release ca-certificates
    curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
    echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt-get update -y
fi

info "Memasang PHP 8.4 & modul lengkap..."
apt-get install -y php8.4-cli php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml \
                   php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-redis \
                   php8.4-sqlite3 php8.4-gd

# Ensure PHP 8.4 is the default CLI and FPM
update-alternatives --set php /usr/bin/php8.4 2>/dev/null || true

# Detect active PHP version & FPM socket
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "8.4")
PHP_FPM_SOCK="/var/run/php/php${PHP_VER}-fpm.sock"
info "PHP Version Terpasang: PHP $PHP_VER (Socket: $PHP_FPM_SOCK)"

# Install Composer if not exists
if ! command -v composer &> /dev/null; then
    info "Memasang Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install PowerDNS Authoritative Server
info "Memasang PowerDNS Authoritative Server & MySQL Backend..."
apt-get install -y pdns-server pdns-backend-mysql || true

# --- 6. Setup MariaDB Database & Credentials ---
header "STEP 5: Mengonfigurasi Database MariaDB"

systemctl enable mariadb
systemctl start mariadb

DB_NAME="dnsmanager"
DB_USER="dnsuser"
DB_PASSWORD=$(openssl rand -hex 16)
PDNS_API_KEY=$(openssl rand -hex 24)

info "Membuat Database [$DB_NAME] dan User [$DB_USER]..."
mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

success "Database dan hak akses pengguna berhasil dibuat."

# --- 7. Configure PowerDNS Authoritative Server ---
header "STEP 6: Mengonfigurasi PowerDNS (gmysql Backend & Webserver API)"

# Backup and clean existing config/includes to prevent duplicate backend conflicts
mkdir -p /etc/powerdns/backup
cp -r /etc/powerdns/pdns.* /etc/powerdns/backup/ 2>/dev/null || true
rm -rf /etc/powerdns/pdns.d/* 2>/dev/null || true

cat << EOF > /etc/powerdns/pdns.conf
# PowerDNS Main Configuration - Generated by DNS Manager Installer
launch=gmysql
authoritative=yes
setuid=pdns
setgid=pdns

# Network Interfaces
local-address=0.0.0.0
local-port=53

# Built-in Webserver & HTTP API for Laravel DNS Manager
webserver=yes
webserver-address=127.0.0.1
webserver-port=8081
webserver-allow-from=127.0.0.1,::1
api=yes
api-key=${PDNS_API_KEY}

# Generic MySQL Backend Credentials
gmysql-host=127.0.0.1
gmysql-port=3306
gmysql-dbname=${DB_NAME}
gmysql-user=${DB_USER}
gmysql-password=${DB_PASSWORD}
gmysql-dnssec=yes

# Query Cache
query-cache-ttl=20
cache-ttl=20
negquery-cache-ttl=60
EOF

# Set proper permissions for pdns service user
mkdir -p /var/run/pdns
chown -R pdns:pdns /etc/powerdns /var/run/pdns
chmod 640 /etc/powerdns/pdns.conf

systemctl enable pdns 2>/dev/null || true

# --- 8. Deploy Laravel DNS Manager ---
header "STEP 7: Menginstal Aplikasi Laravel DNS Manager"

mkdir -p "$INSTALL_DIR"

# Check if running inside git repository
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

if [ -f "$SCRIPT_DIR/artisan" ]; then
    info "Menyalin source code dari direktori lokal ($SCRIPT_DIR)..."
    cp -r "$SCRIPT_DIR"/. "$INSTALL_DIR"/
else
    info "Meng-clone source code dari GitHub repository..."
    rm -rf "$INSTALL_DIR"
    git clone -b main https://github.com/ehsandisini08-del/dnsm-PCM.git "$INSTALL_DIR" || git clone https://github.com/ehsandisini08-del/dnsm-PCM.git "$INSTALL_DIR"
    cd "$INSTALL_DIR"
fi

cd "$INSTALL_DIR"

# Create .env file
info "Membuat konfigurasi .env production..."
cat << EOF > .env
APP_NAME="DNS Manager Pro"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log

# PowerDNS Integration
PDNS_MODE=database
PDNS_API_URL=http://127.0.0.1:8081
PDNS_API_KEY=${PDNS_API_KEY}
PDNS_SERVER_ID=localhost
PDNS_API_TIMEOUT=5
PDNS_DEFAULT_NAMESERVERS=${NS1},${NS2}
PDNS_DEFAULT_TTL=3600
PDNS_SOA_PRIMARY_NS=${NS1}
PDNS_SOA_HOSTMASTER=hostmaster.${DOMAIN}
PDNS_SOA_REFRESH=10800
PDNS_SOA_RETRY=3600
PDNS_SOA_EXPIRE=604800
PDNS_SOA_MINIMUM=3600
EOF

export COMPOSER_ALLOW_SUPERUSER=1
info "Menjalankan Composer Install..."
composer install --no-dev --optimize-autoloader --no-interaction || \
composer update --no-dev --optimize-autoloader --no-interaction

info "Generate Application Encryption Key..."
php artisan key:generate --force

info "Menjalankan Migrasi Database PowerDNS & Aplikasi..."
php artisan migrate --force

info "Menjalankan Database Seeder Awal..."
php artisan db:seed --force

# Create Custom Admin User
info "Membuat Akun Administrator [$ADMIN_EMAIL]..."
php artisan tinker --execute "
\$user = \App\Models\User::updateOrCreate(
    ['email' => '${ADMIN_EMAIL}'],
    [
        'name' => 'Super Admin',
        'password' => \Illuminate\Support\Facades\Hash::make('${ADMIN_PASSWORD}'),
        'role' => 'super_admin',
        'is_active' => true,
    ]
);
echo 'Admin configured: ' . \$user->email;
"

# Publish Filament Assets & Cache
info "Mengompilasi asset dan cache produksi..."
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set directory permissions
info "Mengatur perizinan folder untuk web server..."
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/storage" "$INSTALL_DIR/bootstrap/cache"

# Restart PowerDNS now that DB tables exist
systemctl restart pdns 2>/dev/null || true

# --- 9. Configure Nginx Web Server ---
header "STEP 8: Mengonfigurasi Nginx Virtual Host"

cat << EOF > /etc/nginx/sites-available/dnsmanager
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/dnsmanager /etc/nginx/sites-enabled/

nginx -t
systemctl reload nginx

# Optional SSL with Certbot
if [ "$ENABLE_SSL" = true ]; then
    info "Memasang sertifikat SSL Let's Encrypt untuk [$DOMAIN]..."
    apt-get install -y certbot python3-certbot-nginx
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$ADMIN_EMAIL" --redirect || warn "Let's Encrypt gagal, web server tetap berjalan di HTTP port 80."
fi

# --- 10. Configure Supervisor Queue Worker ---
header "STEP 9: Mengonfigurasi Supervisor Background Worker"

cat << EOF > /etc/supervisor/conf.d/dnsmanager-worker.conf
[program:dnsmanager-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread 2>/dev/null || true
supervisorctl update 2>/dev/null || true
supervisorctl restart dnsmanager-worker:* 2>/dev/null || true

# --- 11. Configure Crontab for Scheduler ---
header "STEP 10: Mengonfigurasi Crontab Scheduler (Health Monitoring)"

CRON_JOB="* * * * * cd ${INSTALL_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -u www-data -l 2>/dev/null | grep -v "schedule:run"; echo "$CRON_JOB") | crontab -u www-data -

# --- 12. Configure UFW Firewall ---
header "STEP 11: Mengonfigurasi UFW Firewall"

ufw allow 22/tcp comment 'SSH' 2>/dev/null || true
ufw allow 80/tcp comment 'HTTP Web' 2>/dev/null || true
ufw allow 443/tcp comment 'HTTPS Web' 2>/dev/null || true
ufw allow 53/tcp comment 'DNS TCP' 2>/dev/null || true
ufw allow 53/udp comment 'DNS UDP' 2>/dev/null || true
ufw --force enable 2>/dev/null || true

# --- 13. Save Credentials & Summary ---
CREDENTIALS_FILE="/root/dnsmanager-credentials.txt"

cat << EOF > "$CREDENTIALS_FILE"
================================================================
  LARAVEL DNS MANAGER + POWERDNS — KREDENSIAL INSTALASI
  Generated on: $(date)
================================================================

[ Web Control Panel ]
URL              : http://${DOMAIN}/admin (atau https://${DOMAIN}/admin jika SSL aktif)
Email Admin      : ${ADMIN_EMAIL}
Password Admin   : ${ADMIN_PASSWORD}

[ PowerDNS Settings ]
Status           : Running (Port 53 UDP/TCP)
API URL          : http://127.0.0.1:8081
API Key          : ${PDNS_API_KEY}
Primary NS       : ${NS1}
Secondary NS     : ${NS2}

[ Database MariaDB ]
Database Name    : ${DB_NAME}
Database User    : ${DB_USER}
Database Password: ${DB_PASSWORD}

[ Path Direktori ]
Application Path : ${INSTALL_DIR}
Credentials File : ${CREDENTIALS_FILE}
================================================================
EOF

chmod 600 "$CREDENTIALS_FILE" 2>/dev/null || true

# --- 14. Verification Tests ---
header "STEP 12: Pengujian Layanan DNS & Web Server"

# Ensure services are up
systemctl restart pdns 2>/dev/null || true
sleep 1

echo -n "Memeriksa Status PowerDNS (Port 53)... "
if ss -tulpn | grep -E -q ':(53|pdns) ' || systemctl is-active --quiet pdns; then
    echo -e "${GREEN}[OK - ONLINE]${NC}"
else
    echo -e "${RED}[ERROR - OFFLINE]${NC}"
fi

echo -n "Memeriksa Status Nginx Web Server... "
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}[OK - ONLINE]${NC}"
else
    echo -e "${RED}[ERROR - OFFLINE]${NC}"
fi

echo -n "Memeriksa Status MariaDB Database... "
if systemctl is-active --quiet mariadb; then
    echo -e "${GREEN}[OK - ONLINE]${NC}"
else
    echo -e "${RED}[ERROR - OFFLINE]${NC}"
fi

# --- 15. Completion Banner ---
header "INSTALASI BERHASIL SELESAI!"

echo -e "${GREEN}${BOLD}DNS Manager & PowerDNS telah aktif dan siap digunakan!${NC}\n"
echo -e "Silakan akses dashboard control panel Anda:"
echo -e " ${BOLD}URL Panel       :${NC} ${CYAN}http://${DOMAIN}/admin${NC}"
echo -e " ${BOLD}Email Login     :${NC} ${YELLOW}${ADMIN_EMAIL}${NC}"
echo -e " ${BOLD}Password        :${NC} ${YELLOW}${ADMIN_PASSWORD}${NC}"
echo -e " ${BOLD}PowerDNS API Key:${NC} ${YELLOW}${PDNS_API_KEY}${NC}"
echo ""
echo -e "File kredensial tersimpan dengan aman di: ${BOLD}${CREDENTIALS_FILE}${NC}"
echo -e "Dokumentasi lengkap tersedia di folder: ${BOLD}${INSTALL_DIR}/docs/${NC}\n"
