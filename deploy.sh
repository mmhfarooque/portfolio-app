#!/bin/bash

# Deploy script for mfaruk.com Photography Portfolio (HestiaCP)
# Usage: ./deploy.sh [--quick]
#
# HestiaCP Structure:
#   public_html/ = Laravel app root (contains app/, bootstrap/, vendor/, etc.)
#   public_html/index.php, .htaccess = copied from public/ folder
#   storage/ = symlinked to private/portfolio-app/storage

set -e

SERVER="root@63.142.240.72"
REMOTE_PATH="/home/mfaruk/web/mfaruk.com/public_html"
LOCAL_PATH="$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Deploying to mfaruk.com (HestiaCP) ===${NC}"

# Parse arguments
QUICK=false
for arg in "$@"; do
    case $arg in
        --quick) QUICK=true ;;
    esac
done

# Step 1: Sync Laravel app files (without --delete to preserve server-specific files)
echo -e "${YELLOW}Syncing files to server...${NC}"
rsync -avz \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='node_modules' \
    --exclude='storage' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='deploy.sh' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='public/storage' \
    "$LOCAL_PATH/" "$SERVER:$REMOTE_PATH/"

if [ "$QUICK" = true ]; then
    echo -e "${GREEN}Quick deploy completed (files only)${NC}"
    exit 0
fi

# Step 2: Run server-side setup for HestiaCP flat structure
echo -e "${YELLOW}Running server-side commands...${NC}"
ssh $SERVER << 'ENDSSH'
cd /home/mfaruk/web/mfaruk.com/public_html

# Copy public folder contents to document root (HestiaCP serves from public_html directly)
echo "Setting up HestiaCP flat structure..."
cp -f public/.htaccess ./ 2>/dev/null || true
cp -f public/index.php ./
cp -f public/favicon.ico ./ 2>/dev/null || true
cp -f public/robots.txt ./ 2>/dev/null || true
cp -f public/.user.ini ./ 2>/dev/null || true
cp -rf public/build ./ 2>/dev/null || true

# Create app-path.php to tell index.php where Laravel is (must point to private/portfolio-app)
echo '<?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";' > app-path.php

# Ensure storage symlink points to storage/app/public (for web access to uploaded files)
rm -f storage 2>/dev/null || true
ln -sf /home/mfaruk/web/mfaruk.com/private/portfolio-app/storage/app/public storage

# Install dependencies (production only)
echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || composer install --no-dev --optimize-autoloader

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set correct permissions
chown -R mfaruk:www-data .
chmod -R 775 bootstrap/cache
chmod -R 775 storage 2>/dev/null || true

echo "Server-side setup complete!"
ENDSSH

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Site: https://mfaruk.com"
echo "Admin: https://mfaruk.com/admin"
