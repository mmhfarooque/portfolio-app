#!/bin/bash

# Deploy script for mfaruk.com Photography Portfolio
# Usage: ./deploy.sh [--no-cache] [--quick]

set -e

SERVER="root@63.142.240.72"
REMOTE_PATH="/home/mfaruk/web/mfaruk.com/public_html"
LOCAL_PATH="$(dirname "$0")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Deploying to mfaruk.com ===${NC}"

# Parse arguments
NO_CACHE=false
QUICK=false
for arg in "$@"; do
    case $arg in
        --no-cache) NO_CACHE=true ;;
        --quick) QUICK=true ;;
    esac
done

# Step 1: Sync files
echo -e "${YELLOW}Syncing files to server...${NC}"
rsync -avz --delete \
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

# Step 2: Run composer and artisan commands on server
echo -e "${YELLOW}Running server-side commands...${NC}"
ssh $SERVER << 'ENDSSH'
cd /home/mfaruk/web/mfaruk.com/public_html

# Install dependencies (production only)
echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || composer install --no-dev --optimize-autoloader

# Run migrations if any
echo "Running migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set correct permissions
chown -R mfaruk:www-data .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 bootstrap/cache

echo "Server-side setup complete!"
ENDSSH

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo ""
echo "Site: https://mfaruk.com"
echo "Admin: https://mfaruk.com/admin"
