#!/bin/bash

# Deploy script for mfaruk.com Photography Portfolio (HestiaCP)
#
# ARCHITECTURE (HestiaCP):
#   public_html/ → SYMLINK to private/portfolio-app/public/
#   private/portfolio-app/ → Laravel app root (git repo)
#   Apache serves from public_html/ (which is public/ inside the app)
#   index.php reads app-path.php → boots Laravel from private/portfolio-app/
#
# DEPLOYMENT METHOD: Git-based (push local → pull on server)
#   - NEVER rsync local files to server
#   - Server pulls from GitHub and builds assets there
#
# Usage: ./deploy.sh [--quick]
#   --quick: Skip npm install/build (code-only changes)

set -e

SERVER="mfaruk"  # SSH alias in ~/.ssh/config — pins User=root and IdentityFile=~/.ssh/mfaruk
APP_DIR="/home/mfaruk/web/mfaruk.com/private/portfolio-app"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Parse arguments
QUICK=false
for arg in "$@"; do
    case $arg in
        --quick) QUICK=true ;;
    esac
done

# Step 1: Ensure local changes are committed and pushed
echo -e "${YELLOW}Checking local git status...${NC}"
if [[ -n $(git status --porcelain) ]]; then
    echo -e "${RED}ERROR: You have uncommitted changes. Commit and push first.${NC}"
    git status --short
    exit 1
fi

LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse origin/main 2>/dev/null || echo "unknown")

if [ "$LOCAL_COMMIT" != "$REMOTE_COMMIT" ]; then
    echo -e "${RED}ERROR: Local and remote are out of sync. Push your changes first.${NC}"
    echo "  Local:  $LOCAL_COMMIT"
    echo "  Remote: $REMOTE_COMMIT"
    exit 1
fi

echo -e "${GREEN}Local commit: $(git log --oneline -1)${NC}"

# Step 2: Deploy on server via SSH
echo ""
echo -e "${GREEN}=== Deploying to mfaruk.com ===${NC}"

if [ "$QUICK" = true ]; then
    echo -e "${YELLOW}Quick deploy (code only, no npm build)...${NC}"
    ssh $SERVER "cd $APP_DIR \
        && git fetch origin \
        && git reset --hard origin/main \
        && composer install --no-dev --optimize-autoloader --quiet \
        && php artisan migrate --force \
        && php artisan optimize:clear \
        && php artisan config:cache \
        && php artisan route:cache \
        && echo '<?php opcache_reset(); echo \"cleared\"; ?>' > public/oc.php \
        && curl -s http://mfaruk.com/oc.php \
        && rm public/oc.php \
        && echo '' \
        && echo 'Deployed: '$(git log --oneline -1)"
else
    echo -e "${YELLOW}Full deploy (with npm build)...${NC}"
    ssh $SERVER "cd $APP_DIR \
        && git fetch origin \
        && git reset --hard origin/main \
        && composer install --no-dev --optimize-autoloader --quiet \
        && php artisan migrate --force \
        && php artisan optimize:clear \
        && npm install --silent \
        && npm run build \
        && php artisan config:cache \
        && php artisan route:cache \
        && chown -R mfaruk:www-data storage bootstrap/cache public/build \
        && chmod -R 775 storage bootstrap/cache \
        && echo '<?php opcache_reset(); echo \"cleared\"; ?>' > public/oc.php \
        && curl -s http://mfaruk.com/oc.php \
        && rm public/oc.php \
        && echo '' \
        && echo 'Deployed: '$(git log --oneline -1)"
fi

echo ""
echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo "Site:  https://mfaruk.com"
echo "Admin: https://mfaruk.com/admin"
