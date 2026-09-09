# Installation Guide — Ubuntu Server 24.04 LTS

## 1. System Requirements
- OS: Ubuntu Server 24.04 LTS
- PHP: 8.3 or 8.4 (with extensions: `php-cli`, `php-fpm`, `php-mysql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`, `php-bcmath`, `php-intl`)
- Database: MariaDB 10.11+ or MySQL 8.0+
- Web Server: Nginx
- Memory / Caching: Redis 7.x
- Process Manager: Supervisor

---

## 2. Server Package Installation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Core packages
sudo apt install -y software-properties-common curl git unzip nginx mariadb-server redis-server supervisor

# Install PHP 8.3 & Extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
                    php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

---

## 3. Database Setup (MariaDB)

```sql
sudo mysql -u root -p
```
```sql
CREATE DATABASE dnsmanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dnsuser'@'localhost' IDENTIFIED BY 'YourSecurePasswordHere';
GRANT ALL PRIVILEGES ON dnsmanager.* TO 'dnsuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Application Deployment

```bash
# Clone or copy application into /var/www/dnsmanager
cd /var/www
sudo git clone <repository-url> dnsmanager
cd /var/www/dnsmanager

# Permissions
sudo chown -R www-data:www-data /var/www/dnsmanager
sudo chmod -R 775 /var/www/dnsmanager/storage /var/www/dnsmanager/bootstrap/cache

# Install PHP Dependencies
composer install --no-dev --optimize-autoloader

# Setup Environment
cp .env.example .env
nano .env
```

Set the database and app credentials in `.env`:
```env
APP_NAME="DNS Manager Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dns.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dnsmanager
DB_USERNAME=dnsuser
DB_PASSWORD=YourSecurePasswordHere

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Run application setup:
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan filament:assets
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Nginx Web Server Configuration

Create `/etc/nginx/sites-available/dnsmanager`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name dns.yourdomain.com;
    root /var/www/dnsmanager/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/dnsmanager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. Supervisor Queue Worker & Scheduler

Create `/etc/supervisor/conf.d/dnsmanager-worker.conf`:
```ini
[program:dnsmanager-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dnsmanager/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dnsmanager/storage/logs/worker.log
stopwaitsecs=3600
```

Add Laravel Scheduler to crontab:
```bash
sudo crontab -u www-data -e
```
Add line:
```cron
* * * * * cd /var/www/dnsmanager && php artisan schedule:run >> /dev/null 2>&1
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start dnsmanager-worker:*
```
