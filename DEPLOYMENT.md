# Deployment Guide

This guide covers deploying the Portfolio App to various hosting environments.

## Table of Contents

- [Requirements](#requirements)
- [cPanel / Shared Hosting](#cpanel--shared-hosting)
- [VPS / Dedicated Server](#vps--dedicated-server)
- [Git-based Deployment](#git-based-deployment)
- [Troubleshooting](#troubleshooting)

---

## Requirements

### Server Requirements

- PHP 8.1 or higher
- MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 10+
- Required PHP Extensions:
  - BCMath
  - Ctype
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD or Imagick (for image processing)
  - **intl** (for Laravel Number helpers like `Number::fileSize()`)

### Recommended

- Composer 2.x
- Git
- SSH access (for deployment script)

### Installing PHP intl Extension

The `intl` extension is required for Laravel's `Number::fileSize()`, `Number::currency()`, and other formatting helpers.

**Ubuntu/Debian:**
```bash
sudo apt install php8.2-intl
sudo systemctl restart php8.2-fpm
# or for Apache:
sudo systemctl restart apache2
```

**CentOS/RHEL:**
```bash
sudo yum install php-intl
sudo systemctl restart php-fpm
```

**Hestia Control Panel:**
```bash
# Check which PHP version is active
php -v

# Install intl for that version
sudo apt install php8.2-intl

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

**Verify installation:**
```bash
php -m | grep intl
# Should output: intl
```

> **Note:** If `intl` cannot be installed, the app will still work but some Number helper methods won't be available. Use manual formatting instead: `number_format($bytes / 1024 / 1024, 2) . ' MB'`

---

## cPanel / Shared Hosting

cPanel hosting requires a **split installation** because Apache looks for files in `public_html/` but Laravel's entry point is in the `public/` directory.

### Directory Structure

```
/home/username/
├── portfolio-app/          ← Laravel application (private)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── storage/
│   ├── vendor/
│   └── ...
│
└── public_html/            ← Web-accessible files only
    ├── index.php           ← Modified to find app
    ├── .htaccess
    ├── css/
    ├── js/
    ├── storage → symlink   ← Points to ../portfolio-app/storage/app/public
    └── app-path.php        ← Config file (blocked from web access)
```

### Option 1: Automated Deployment (Recommended)

1. **Upload the deployment script** to your server via SSH or File Manager:
   ```bash
   # Upload deploy/cpanel-deploy.sh to your home directory
   ```

2. **Edit the script** with your Git repository URL:
   ```bash
   nano ~/cpanel-deploy.sh
   # Change GIT_REPO="https://github.com/YOUR_USERNAME/portfolio-app.git"
   ```

3. **Make it executable and run**:
   ```bash
   chmod +x ~/cpanel-deploy.sh
   ./cpanel-deploy.sh --install
   ```

4. **Configure your environment**:
   ```bash
   nano ~/portfolio-app/.env
   # Set your database credentials:
   # DB_DATABASE=your_database
   # DB_USERNAME=your_username
   # DB_PASSWORD=your_password
   ```

5. **Run migrations and seeders**:
   ```bash
   ./cpanel-deploy.sh --migrate
   ./cpanel-deploy.sh --seed
   ./cpanel-deploy.sh --optimize
   ```

6. **Visit your website!**

### Option 2: Manual Installation

1. **Upload Laravel files** to `/home/username/portfolio-app/` (NOT in public_html)

2. **Copy public files** to public_html:
   ```bash
   cp -r ~/portfolio-app/public/* ~/public_html/
   ```

3. **Create app-path.php** in public_html:
   ```php
   <?php
   return '/home/username/portfolio-app';
   ```

4. **Create storage symlink**:
   ```bash
   cd ~/public_html
   ln -s ../portfolio-app/storage/app/public storage
   ```

5. **Set permissions**:
   ```bash
   chmod -R 775 ~/portfolio-app/storage
   chmod -R 775 ~/portfolio-app/bootstrap/cache
   ```

6. **Configure .env and run migrations** as above.

### Updating (cPanel)

```bash
./cpanel-deploy.sh --update
```

This will:
- Pull latest code from Git
- Install dependencies
- Run migrations
- Clear and rebuild caches

---

## VPS / Dedicated Server

For VPS/dedicated servers, you have more control and can use the standard Laravel structure.

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/portfolio-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/portfolio-app/public

    <Directory /var/www/portfolio-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Deployment Steps

```bash
# Clone repository
cd /var/www
git clone https://github.com/YOUR_USERNAME/portfolio-app.git
cd portfolio-app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.example .env
php artisan key:generate
nano .env  # Configure database

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --force
php artisan db:seed --force

# Create storage link
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Git-based Deployment

### Using GitHub Webhooks

1. **Create a deployment endpoint** in your app or use a webhook receiver

2. **Set up webhook** in GitHub repository settings:
   - Payload URL: `https://yourdomain.com/deploy-webhook`
   - Secret: Your secret key
   - Events: Just the push event

3. **Server pulls on webhook trigger**

### Using GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd ~/portfolio-app
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
```

---

## Troubleshooting

### Common Issues

#### 500 Internal Server Error

1. Check storage permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. Check error logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Ensure .env file exists and APP_KEY is set

#### Blank Page

1. Enable error display temporarily in `.env`:
   ```
   APP_DEBUG=true
   ```

2. Check PHP error logs in cPanel

#### Storage/Images Not Loading

1. Verify symlink exists:
   ```bash
   ls -la public_html/storage
   ```

2. Recreate symlink:
   ```bash
   rm public_html/storage
   ln -s ../portfolio-app/storage/app/public public_html/storage
   ```

#### "Class not found" Errors

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Getting Help

If you encounter issues:

1. Check `storage/logs/laravel.log`
2. Check PHP error logs
3. Ensure all requirements are met
4. Verify file permissions

---

## Security Checklist

Before going live:

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Configure proper `APP_URL`
- [ ] Enable HTTPS (uncomment in .htaccess)
- [ ] Set secure session settings
- [ ] Review file permissions
- [ ] Remove any test/development data

---

## Hestia Control Panel Deployment (mfaruk.com)

This section documents the specific setup for deploying to mfaruk.com using Hestia Control Panel.

**⚠️ CRITICAL: This is a SPLIT INSTALLATION - Read carefully!**

### Server Details

- **Server IP**: 63.142.240.72
- **Control Panel**: Hestia CP
- **Web Server**: Nginx (proxy) → Apache (backend)
- **PHP Version**: 8.4 (php8.4-fpm-mfaruk.com.sock)
- **Database**: MySQL
- **GitHub Repo**: https://github.com/mmhfarooque/portfolio-app.git

### Directory Structure (SPLIT INSTALLATION)

**THIS IS CRITICAL TO UNDERSTAND:**

```
/home/mfaruk/web/mfaruk.com/
│
├── private/
│   └── portfolio-app/              ← LARAVEL APP CODE LIVES HERE!
│       ├── app/                    ← Controllers, Models, Services
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── resources/              ← Blade templates, CSS, JS source
│       ├── routes/
│       ├── storage/                ← Logs, cache, compiled views
│       ├── vendor/                 ← Composer dependencies
│       ├── node_modules/           ← NPM dependencies
│       ├── public/
│       │   └── build/              ← Vite compiled assets (SOURCE)
│       ├── .env                    ← Production environment config
│       ├── .git/                   ← Git repository
│       ├── composer.json
│       └── package.json
│
└── public_html/                    ← WEB ROOT (Apache DocumentRoot)
    ├── index.php                   ← Entry point (loads app via app-path.php)
    ├── app-path.php                ← Points to /private/portfolio-app
    ├── .htaccess                   ← WordPress-style rewrite rules
    ├── build/                      ← Vite assets COPIED here for serving
    │   ├── manifest.json
    │   └── assets/
    │       ├── app-*.css
    │       └── app-*.js
    ├── storage/                    ← Symlink to private app storage
    └── (other static files)
```

### How It Works

1. **Request comes in** → Nginx proxies to Apache
2. **Apache serves** from `public_html/`
3. **`index.php`** reads `app-path.php` to find Laravel app location
4. **Laravel loads** from `/private/portfolio-app/`
5. **Static assets** (CSS/JS) are served from `public_html/build/`

### Key Files

#### public_html/index.php
```php
<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Load app path from config file - THIS IS THE KEY!
$appPath = require __DIR__ . '/app-path.php';

if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath . '/vendor/autoload.php';

$app = require_once $appPath . '/bootstrap/app.php';
$app->handleRequest(Request::capture());
```

#### public_html/app-path.php
```php
<?php return '/home/mfaruk/web/mfaruk.com/private/portfolio-app';
```

#### public_html/.htaccess
```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

### Initial Deployment Steps

```bash
# SSH to server
ssh root@63.142.240.72

# Navigate to the PRIVATE app directory (NOT public_html!)
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app

# Clone repository (first time)
git clone https://github.com/mmhfarooque/portfolio-app.git .

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm install
npm run build

# Copy built assets to public_html for serving
cp -r public/build /home/mfaruk/web/mfaruk.com/public_html/

# Set up environment
cp .env.example .env
nano .env  # Configure database, app URL, etc.

# Set permissions
chown -R mfaruk:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --force
php artisan db:seed --force  # If needed

# Clear all caches
php artisan optimize:clear
php artisan view:clear
```

### Updating Deployment

When pushing new changes from local:

```bash
# On LOCAL machine
git add .
git commit -m "Your changes"
git push origin main

# On SERVER (SSH)
ssh root@63.142.240.72

# IMPORTANT: Go to PRIVATE directory, not public_html!
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app

# Pull latest code
git fetch origin
git reset --hard origin/main

# If composer.json changed
composer install --no-dev --optimize-autoloader

# If package.json changed or CSS/JS modified
npm install
npm run build

# CRITICAL: Copy built assets to public_html
rm -rf /home/mfaruk/web/mfaruk.com/public_html/build
cp -r public/build /home/mfaruk/web/mfaruk.com/public_html/

# Clear ALL caches
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*.php

# If migrations needed
php artisan migrate --force

# Fix permissions
chown -R mfaruk:www-data storage bootstrap/cache
chown -R mfaruk:www-data /home/mfaruk/web/mfaruk.com/public_html/build
chmod -R 775 storage bootstrap/cache

# CRITICAL: Clear OPcache via web (CLI clear doesn't work for web requests)
# Create temp file, call it, delete it:
echo '<?php opcache_reset(); echo "cleared"; ?>' > /home/mfaruk/web/mfaruk.com/public_html/oc.php
curl -s http://mfaruk.com/oc.php
rm /home/mfaruk/web/mfaruk.com/public_html/oc.php
```

### Common Issues on Hestia

#### 1. Changes Not Showing After Deployment

**Most likely cause:** You updated the WRONG directory!

- **WRONG**: `/home/mfaruk/web/mfaruk.com/public_html/` (only has entry point)
- **CORRECT**: `/home/mfaruk/web/mfaruk.com/private/portfolio-app/` (actual Laravel app)

**Verify you're in the right place:**
```bash
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
git log --oneline -1  # Should match your latest commit
```

#### 2. OPcache Serving Old PHP Files

PHP OPcache caches compiled PHP files. CLI commands don't clear the web OPcache!

**Solution - Clear via web request:**
```bash
echo '<?php opcache_reset(); echo "cleared"; ?>' > /home/mfaruk/web/mfaruk.com/public_html/oc.php
curl -s http://mfaruk.com/oc.php
rm /home/mfaruk/web/mfaruk.com/public_html/oc.php
```

**Signs this is the issue:**
- Git shows correct commit
- Blade files are correct on disk
- But browser still shows old content

#### 3. Frontend Assets (CSS/JS) Not Updating

Vite builds to `private/portfolio-app/public/build/` but assets are served from `public_html/build/`.

**Solution:**
```bash
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app

# Rebuild assets
npm install
npm run build

# Copy to web root
rm -rf /home/mfaruk/web/mfaruk.com/public_html/build
cp -r public/build /home/mfaruk/web/mfaruk.com/public_html/
chown -R mfaruk:www-data /home/mfaruk/web/mfaruk.com/public_html/build
```

**Signs this is the issue:**
- Blade HTML changes show up
- But styling/layout looks old
- Check browser dev tools Network tab for CSS filename (hash should change)

#### 4. Git "Dubious Ownership" Error

```bash
git config --global --add safe.directory /home/mfaruk/web/mfaruk.com/private/portfolio-app
```

#### 5. View Cache Not Clearing

```bash
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan view:clear
rm -rf storage/framework/views/*.php
```

### Quick Deploy Script

Create `/home/mfaruk/deploy.sh`:

```bash
#!/bin/bash
set -e

APP_DIR="/home/mfaruk/web/mfaruk.com/private/portfolio-app"
PUBLIC_DIR="/home/mfaruk/web/mfaruk.com/public_html"

echo "=== Portfolio App Deployment Script ==="
echo ""
echo "App directory: $APP_DIR"
echo "Public directory: $PUBLIC_DIR"
echo ""

cd $APP_DIR

echo "1. Pulling latest code from GitHub..."
git fetch origin
git reset --hard origin/main
echo "   Current commit: $(git log --oneline -1)"

echo ""
echo "2. Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --quiet

echo ""
echo "3. Running database migrations..."
php artisan migrate --force

echo ""
echo "4. Clearing Laravel caches..."
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*.php 2>/dev/null || true

echo ""
echo "5. Building frontend assets (Vite)..."
npm install --silent
npm run build

echo ""
echo "6. Copying assets to public_html..."
rm -rf $PUBLIC_DIR/build
cp -r public/build $PUBLIC_DIR/

echo ""
echo "7. Fixing permissions..."
chown -R mfaruk:www-data storage bootstrap/cache
chown -R mfaruk:www-data $PUBLIC_DIR/build
chmod -R 775 storage bootstrap/cache

echo ""
echo "8. Clearing OPcache (via web request)..."
echo '<?php opcache_reset(); echo "cleared"; ?>' > $PUBLIC_DIR/oc.php
curl -s http://mfaruk.com/oc.php
rm $PUBLIC_DIR/oc.php

echo ""
echo "=== Deployment Complete! ==="
echo "Commit: $(git log --oneline -1)"
```

Make it executable:
```bash
chmod +x /home/mfaruk/deploy.sh
```

**Deploy with single command:**
```bash
/home/mfaruk/deploy.sh
```

### Installing PHP Extensions on Hestia (mfaruk.com)

The server uses PHP 8.4. Some Laravel features require additional extensions:

**Required extensions for full Laravel 12 functionality:**
```bash
# SSH to server
ssh root@63.142.240.72

# Check current PHP version
php -v
# Output: PHP 8.4.x

# Check installed extensions
php -m

# Install intl extension (required for Number::fileSize(), Number::currency(), etc.)
sudo apt update
sudo apt install php8.4-intl

# Restart PHP-FPM for the site
sudo systemctl restart php8.4-fpm

# Verify intl is installed
php -m | grep intl
# Should output: intl
```

**If intl extension cannot be installed**, use manual formatting in Blade templates:
```php
// Instead of:
{{ Number::fileSize($photo->file_size) }}

// Use:
{{ number_format($photo->file_size / 1024 / 1024, 2) }} MB
```

### SSH Access via Python (when sshpass not available)

If your local machine doesn't have sshpass, use Python:

```python
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('63.142.240.72', username='root', password='YOUR_PASSWORD')

stdin, stdout, stderr = ssh.exec_command('cd /home/mfaruk/web/mfaruk.com/public_html && git pull')
print(stdout.read().decode())
print(stderr.read().decode())

ssh.close()
```
