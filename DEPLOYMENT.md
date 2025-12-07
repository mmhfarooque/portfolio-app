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

### Recommended

- Composer 2.x
- Git
- SSH access (for deployment script)

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

### Server Details

- **Server IP**: 63.142.240.72
- **Control Panel**: Hestia CP
- **Web Server**: Apache (with nginx proxy)
- **PHP Version**: 8.4
- **Database**: MySQL

### Directory Structure on Hestia

Unlike standard cPanel, Hestia uses `public_html` AS the Laravel app root (not split installation):

```
/home/mfaruk/web/mfaruk.com/
├── private/
│   └── portfolio-app/          ← Backup/reference copy
│       └── .env                ← Environment backup
│
├── public_html/                ← THIS IS THE LIVE LARAVEL APP
│   ├── .git/                   ← Git repository
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env                    ← Production environment
│   ├── index.php               ← Modified entry point (in root, not public/)
│   ├── .htaccess               ← Apache rewrite rules
│   └── public/                 ← Static assets
│       ├── build/              ← Vite compiled assets
│       └── storage → symlink
```

### Key Differences from Standard Laravel

1. **index.php in root**: The `index.php` file is in `public_html/` root, not in `public/`
2. **.htaccess in root**: Rewrites are handled from root
3. **Entire app in public_html**: Unlike cPanel split, the whole Laravel app is web-accessible (protected by .htaccess)

### Modified index.php

The `index.php` must be modified to work from root instead of `public/`:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// When running from root instead of public/
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

### Modified .htaccess

Root `.htaccess` to handle Laravel routing:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Serve static files from public/
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} -f
    RewriteRule ^(.*)$ /public/$1 [L]

    # Redirect everything else to index.php
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Block access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "(artisan|composer\.(json|lock)|package.*\.json|webpack\..*|vite\..*|phpunit\.xml|\.env.*)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Initial Deployment Steps

```bash
# SSH to server
ssh root@63.142.240.72

# Navigate to public_html
cd /home/mfaruk/web/mfaruk.com/public_html

# Clone repository (first time)
git clone https://github.com/mmhfarooque/photography-portfolio.git .

# Or if already exists, pull latest
git fetch origin
git reset --hard origin/main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment (copy from backup or create new)
cp /home/mfaruk/web/mfaruk.com/private/portfolio-app/.env .env

# Set permissions
chown -R mfaruk:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage symlink (inside public/)
cd public
ln -sf ../storage/app/public storage

# Run migrations
cd ..
php artisan migrate --force

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
cd /home/mfaruk/web/mfaruk.com/public_html

# Pull latest
git fetch origin
git reset --hard origin/main

# Clear ALL caches (important!)
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*.php

# If composer.json changed
composer install --no-dev --optimize-autoloader

# If migrations needed
php artisan migrate --force

# Fix permissions after pull
chown -R mfaruk:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Common Issues on Hestia

#### Old Views Still Showing After Deployment

This is the most common issue. Laravel caches compiled Blade views.

**Solution:**
```bash
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*.php
```

If still showing old content:
1. Check you're updating the correct directory (`public_html`, NOT `private/portfolio-app`)
2. Clear Cloudflare cache if using Cloudflare
3. Hard refresh browser (Ctrl+Shift+R)

#### Git "Dubious Ownership" Error

```bash
git config --global --add safe.directory /home/mfaruk/web/mfaruk.com/public_html
```

#### Wrong Directory Updated

The site runs from `public_html`, NOT from `private/portfolio-app`. Always check:

```bash
# Verify you're in the right directory
pwd
# Should show: /home/mfaruk/web/mfaruk.com/public_html

# Verify git commit
git log --oneline -1
# Should match your latest commit
```

#### .env Gets Overwritten by Git

Keep a backup of .env:
```bash
cp .env /home/mfaruk/web/mfaruk.com/private/portfolio-app/.env
```

After git reset, restore it:
```bash
cp /home/mfaruk/web/mfaruk.com/private/portfolio-app/.env .env
```

### Quick Deploy Script

Create `/home/mfaruk/deploy.sh`:

```bash
#!/bin/bash
set -e

cd /home/mfaruk/web/mfaruk.com/public_html

echo "Backing up .env..."
cp .env /home/mfaruk/web/mfaruk.com/private/portfolio-app/.env

echo "Pulling latest code..."
git fetch origin
git reset --hard origin/main

echo "Restoring .env..."
cp /home/mfaruk/web/mfaruk.com/private/portfolio-app/.env .env

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --quiet

echo "Running migrations..."
php artisan migrate --force

echo "Clearing caches..."
php artisan optimize:clear
php artisan view:clear
rm -rf storage/framework/views/*.php

echo "Fixing permissions..."
chown -R mfaruk:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Deployment complete!"
git log --oneline -1
```

Make it executable:
```bash
chmod +x /home/mfaruk/deploy.sh
```

Then deploy with:
```bash
/home/mfaruk/deploy.sh
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
