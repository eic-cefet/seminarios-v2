<div align="center">

# Installing CEFET-RJ Seminários on Ubuntu + Apache2

**Complete setup guide for bare Ubuntu server with Apache2**

</div>

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [System Setup](#system-setup)
3. [PHP Extensions](#php-extensions)
4. [Node.js & pnpm](#nodejs--pnpm)
5. [Composer](#composer)
6. [Clone Repository](#clone-repository)
7. [Apache Configuration](#apache-configuration)
8. [Application Setup](#application-setup)
9. [Queue Worker (Supervisor)](#queue-worker-supervisor)
10. [Scheduled Tasks (Cron)](#scheduled-tasks-cron)
11. [Verification](#verification)

---

## Prerequisites

This guide assumes the server already has:
- Ubuntu 22.04+
- Apache2 installed and running
- PHP 8.2+ installed with Apache module
- MySQL 8.0+ installed

| Additional Requirements | Purpose |
|------------------------|---------|
| Node.js 22 LTS | Asset Building |
| pnpm | Package Manager |
| Composer | PHP Dependencies |
| Supervisor | Queue Worker Daemon |
| Git | Version Control |

---

## System Setup

### 1. Install Additional Packages

```bash
sudo apt update
sudo apt install -y \
    unzip \
    git \
    supervisor
```

---

## PHP Extensions

### 1. Required Extensions

The application requires the following PHP extensions:

| Extension | Purpose |
|-----------|---------|
| pdo_mysql | Database connectivity |
| mbstring | String handling |
| xml / dom | XML processing |
| curl | HTTP requests |
| gd | Image processing |
| zip | Archive handling |
| intl | Internationalization |
| bcmath | Arbitrary precision math |
| exif | Image metadata |
| opcache | Performance optimization |
| fileinfo | File type detection |

### 2. Check Installed Extensions

Run this script to check all required extensions:

```bash
#!/bin/bash
extensions=("pdo_mysql" "mbstring" "xml" "dom" "curl" "gd" "zip" "intl" "bcmath" "exif" "opcache" "fileinfo")

echo "Checking PHP extensions..."
for ext in "${extensions[@]}"; do
    if php -m | grep -qi "^${ext}$"; then
        echo "  ✓ $ext"
    else
        echo "  ✗ $ext (MISSING)"
    fi
done
```

Or check all at once:

```bash
php -m | grep -E "^(pdo_mysql|mbstring|xml|dom|curl|gd|zip|intl|bcmath|exif|opcache|fileinfo)$"
```

### 3. Install Missing Extensions

If any extensions are missing, install them (replace `8.4` with your PHP version):

```bash
# Check your PHP version first
php -v

# Install missing extensions (example for PHP 8.4)
sudo apt install -y php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-gd php8.4-zip php8.4-intl php8.4-bcmath php8.4-opcache

# For PHP 8.2
sudo apt install -y php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-gd php8.2-zip php8.2-intl php8.2-bcmath php8.2-opcache
```

### 4. Restart Apache After Installing Extensions

```bash
sudo systemctl restart apache2
```

### 5. Verify PHP Version

```bash
php -v
# Should show PHP 8.2+
```

---

## Node.js & pnpm

### 1. Install Node.js 22 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Install pnpm

```bash
sudo npm install -g pnpm
```

### 3. Verify Installation

```bash
node -v  # Should show v22.x.x
pnpm -v  # Should show latest version
```

---

## Composer

### 1. Install Composer Globally

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 2. Verify Installation

```bash
composer --version
```

---

## Clone Repository

### 1. Create Directory Structure

```bash
sudo mkdir -p /var/www/sites
```

### 2. Add Your User to www-data Group

```bash
# Add your user to www-data group
sudo usermod -aG www-data $USER

# Apply group changes (or logout/login)
newgrp www-data
```

### 3. Clone the Repository

```bash
cd /var/www/sites

# Clone as www-data
sudo -u www-data git clone https://github.com/eic-cefet/seminarios-v2.git eic-seminarios.com

# Or clone and fix ownership
sudo git clone https://github.com/eic-cefet/seminarios-v2.git eic-seminarios.com
sudo chown -R www-data:www-data eic-seminarios.com
```

### 4. Set Directory Permissions

```bash
cd /var/www/sites/eic-seminarios.com

# Set ownership
sudo chown -R www-data:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Make storage and cache writable
sudo chmod -R 775 storage bootstrap/cache

# Ensure www-data owns storage
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## Apache Configuration

### 1. Enable Required Modules

```bash
sudo a2enmod rewrite headers
```

### 2. Create Symlink for /seminarios

```bash
sudo ln -sf /var/www/sites/eic-seminarios.com/public /var/www/html/seminarios
```

### 3. Enable AllowOverride for .htaccess

Laravel includes a `.htaccess` file that handles URL rewriting. Ensure Apache allows it:

```bash
sudo nano /etc/apache2/apache2.conf
```

Find the `/var/www/` directory block and ensure `AllowOverride All`:

```apache
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### 4. Restart Apache

```bash
sudo systemctl restart apache2
```

### 5. Verify Configuration

```bash
# Check Apache config syntax
sudo apache2ctl configtest

# Verify modules are loaded
apache2ctl -M | grep -E "rewrite|headers"
```

---

## Application Setup

### 1. Install Dependencies (as www-data)

```bash
cd /var/www/sites/eic-seminarios.com

# PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader --prefer-dist

# Node.js dependencies
sudo -u www-data pnpm install --frozen-lockfile
```

### 2. Environment Configuration

```bash
# Copy environment file
sudo -u www-data cp .env.example .env

# Generate application key
sudo -u www-data php artisan key:generate

# Edit environment variables
sudo nano .env
```

Update `.env` with your settings:

```env
APP_NAME="CEFET-RJ Seminários"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server.com/seminarios

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seminarios_eic
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 3. Build Assets

```bash
sudo -u www-data pnpm run build
```

### 4. Run Migrations

```bash
sudo -u www-data php artisan migrate --force
```

### 5. Optimize Application

```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
```

### 6. Create Storage Link

```bash
sudo -u www-data php artisan storage:link
```

---

## Queue Worker (Supervisor)

### 1. Create Supervisor Configuration

```bash
sudo nano /etc/supervisor/conf.d/seminarios-worker.conf
```

Add the following content:

```ini
[program:seminarios-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sites/eic-seminarios.com/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/seminarios-worker.log
stopwaitsecs=3600
```

### 2. Apply Configuration

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start seminarios-worker:*
```

### 3. Verify Workers Are Running

```bash
sudo supervisorctl status seminarios-worker:*
```

Expected output:
```
seminarios-worker:seminarios-worker_00   RUNNING   pid 12345, uptime 0:00:10
seminarios-worker:seminarios-worker_01   RUNNING   pid 12346, uptime 0:00:10
```

### 4. Useful Supervisor Commands

```bash
# Check status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart seminarios-worker:*

# Stop workers
sudo supervisorctl stop seminarios-worker:*

# View logs
sudo tail -f /var/log/seminarios-worker.log
```

---

## Scheduled Tasks (Cron)

### 1. Edit www-data Crontab

```bash
sudo crontab -u www-data -e
```

### 2. Add Laravel Scheduler and Update Script

Add the following lines:

```cron
# Laravel scheduler - runs every minute
* * * * * cd /var/www/sites/eic-seminarios.com && php artisan schedule:run >> /dev/null 2>&1

# Check for updates daily at 3am
0 3 * * * cd /var/www/sites/eic-seminarios.com && ./update.sh >> /var/log/seminarios-update.log 2>&1
```

### 3. Verify Crontab

```bash
sudo crontab -u www-data -l
```

### 4. Ensure Update Script is Executable

```bash
sudo chmod +x /var/www/sites/eic-seminarios.com/update.sh
```

---

## Verification

### 1. Check All Services

```bash
# Apache
sudo systemctl status apache2

# Supervisor
sudo systemctl status supervisor

# MySQL
sudo systemctl status mysql
```

### 2. Test Application

```bash
# Test artisan
sudo -u www-data php artisan --version

# Test queue
sudo -u www-data php artisan queue:work --once

# Check application status
curl -I http://localhost/seminarios
```

### 3. Check Logs

```bash
# Apache error log
sudo tail -f /var/log/apache2/seminarios_error.log

# Laravel log
sudo tail -f /var/www/sites/eic-seminarios.com/storage/logs/laravel.log

# Queue worker log
sudo tail -f /var/log/seminarios-worker.log
```

---

## Quick Reference

### File Locations

| Item | Path |
|------|------|
| Application | `/var/www/sites/eic-seminarios.com` |
| Public symlink | `/var/www/html/seminarios` |
| Apache config | `/etc/apache2/apache2.conf` |
| Supervisor config | `/etc/supervisor/conf.d/seminarios-worker.conf` |
| Laravel logs | `/var/www/sites/eic-seminarios.com/storage/logs/` |
| Worker logs | `/var/log/seminarios-worker.log` |
| Update logs | `/var/log/seminarios-update.log` |

### Common Commands

```bash
# Restart Apache
sudo systemctl restart apache2

# Restart queue workers
sudo supervisorctl restart seminarios-worker:*

# Clear all caches
sudo -u www-data php artisan optimize:clear

# Rebuild caches
sudo -u www-data php artisan optimize

# Check queue status
sudo -u www-data php artisan queue:monitor

# Run manual update
sudo -u www-data ./update.sh
```

---

<div align="center">

Made with ❤️ for **CEFET-RJ EIC**

</div>
