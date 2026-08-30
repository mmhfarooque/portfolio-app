#!/bin/bash
# One-shot deploy for the Laravel 13 upgrade (branch upgrade/laravel-13).
#
# The stock deploy.sh is fine for a code change. It is NOT safe for a major
# framework bump, because it:
#   - has no `artisan down`, so between `git reset --hard` and the end of
#     `composer install` visitors get hard 500s (new code, old vendor)
#   - never stops the supervisor queue worker, so vendor/ is swapped underneath
#     a running worker and jobs fatal, burning their retries
#   - never pauses the every-minute schedule:run cron, which has
#     MAILTO=farooque7@gmail.com and will email on every failure mid-deploy
#   - runs composer without COMPOSER_ALLOW_SUPERUSER=1, so package:discover is
#     already degraded on every deploy today
#   - swallows SSR failure with `|| true`, and SSR fails SILENTLY on this site
#     (HttpGateway catches the exception and falls back to client rendering -
#     exactly how the Jul 12-26 outage went unnoticed)
#   - takes no vendor snapshot, so rollback means re-downloading ~130 packages
#
# This script fixes all of that and verifies afterwards. It is idempotent up to
# the git reset. Run it FROM THE LAPTOP: bash deploy/upgrade-l13.sh
#
# ROLLBACK (see the end of this file for the full sequence):
#   ssh mfaruk 'cd APP && mv vendor vendor.failed && mv vendor.bak-PREV vendor \
#     && git reset --hard c04f897 && php artisan optimize:clear && php artisan up'

set -euo pipefail

SERVER="mfaruk"
APP="/home/mfaruk/web/mfaruk.com/private/portfolio-app"
BRANCH="${1:-upgrade/laravel-13}"
ROLLBACK_TO="c04f897"          # the commit live before this upgrade
STAMP="$(date +%Y%m%d-%H%M%S)"

G='\033[0;32m'; Y='\033[1;33m'; R='\033[0;31m'; N='\033[0m'
say()  { echo -e "${G}==> $*${N}"; }
warn() { echo -e "${Y}--- $*${N}"; }
die()  { echo -e "${R}FAILED: $*${N}" >&2; exit 1; }

# ----------------------------------------------------------------- local gate
say "Local pre-flight"
git rev-parse --abbrev-ref HEAD | grep -qx "$BRANCH" || die "not on $BRANCH"
[[ -z "$(git status --porcelain)" ]] || die "working tree dirty - commit first"
git diff --quiet "origin/$BRANCH" 2>/dev/null || warn "branch not pushed to origin yet (server pulls from GitHub, so PUSH FIRST)"

# ---------------------------------------------------------------- server gate
say "Server pre-flight"
ssh "$SERVER" bash -s <<EOSSH || die "pre-flight failed"
set -euo pipefail
cd "$APP"

FREE_MB=\$(df -Pm / | awk 'NR==2{print \$4}')
echo "  disk free: \${FREE_MB} MB"
[ "\$FREE_MB" -gt 2500 ] || { echo "  refusing: need >2500 MB free for a vendor snapshot + install"; exit 1; }

# These two are UNTRACKED and the site does not boot without app-path.php.
# Nothing in this script may ever run git clean.
[ -f public/app-path.php ] || { echo "  refusing: public/app-path.php missing"; exit 1; }
echo "  app-path.php + robots.txt present"

PHPV=\$(php -r 'echo PHP_MAJOR_VERSION."."."".PHP_MINOR_VERSION;')
echo "  php: \$PHPV (Laravel 13 needs >= 8.3)"
php -r 'exit((PHP_VERSION_ID >= 80300) ? 0 : 1);' || { echo "  refusing: PHP too old"; exit 1; }
EOSSH

# Backup freshness: the NAS weekly is the backup that matters.
say "Confirm a recent backup exists on the NAS"
ssh Synology 'ls -t /volume1/homes/mimocloud/Backup/mfaruk.com/weekly/*.tar.gz 2>/dev/null | head -1 | xargs -r stat -c "  newest weekly: %n (%y)"' \
  || warn "could not reach the NAS to confirm - check before continuing"
read -r -p "Backup looks current and you want to proceed? [type DEPLOY] " ans
[[ "$ans" == "DEPLOY" ]] || die "aborted by operator"

# ------------------------------------------------------------------- deploy
say "Deploying $BRANCH to mfaruk.com"
ssh "$SERVER" bash -s <<EOSSH || die "deploy step failed - site may be in maintenance mode, see rollback"
set -euo pipefail
cd "$APP"
export COMPOSER_ALLOW_SUPERUSER=1

echo "==> maintenance mode ON"
php artisan down --retry=60 || true

echo "==> stop the queue worker (vendor/ is about to be swapped under it)"
supervisorctl stop portfolio-worker:* || true

echo "==> pause the schedule cron (MAILTO would email on every mid-deploy failure)"
crontab -l -u mfaruk > /tmp/crontab.mfaruk.$STAMP.bak
crontab -l -u mfaruk | sed 's|^\* \* \* \* \* cd .*schedule:run|#DEPLOY& |' | crontab -u mfaruk -
echo "    cron backed up to /tmp/crontab.mfaruk.$STAMP.bak"

echo "==> snapshot vendor for instant offline rollback"
rm -rf vendor.bak-prev
cp -a vendor vendor.bak-$STAMP
ln -sfn vendor.bak-$STAMP vendor.bak-prev
du -sh vendor.bak-$STAMP

echo "==> fetch + reset (NEVER git clean: app-path.php and robots.txt are untracked)"
git fetch origin
git reset --hard "origin/$BRANCH"
git log --oneline -1

echo "==> composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> migrate"
php artisan migrate --force

echo "==> frontend build"
npm ci --no-audit --no-fund
npm run build

echo "==> caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> permissions"
chown -R mfaruk:www-data storage bootstrap/cache public/build bootstrap/ssr
chmod -R 775 storage bootstrap/cache

echo "==> restart SSR and ASSERT it came up (no || true - silent SSR death is the known failure mode)"
systemctl restart mfaruk-ssr
sleep 4
systemctl is-active --quiet mfaruk-ssr || { echo "SSR unit not active"; exit 1; }
curl -fsS -m 10 -o /dev/null http://127.0.0.1:13714/health || { echo "SSR health endpoint not responding"; exit 1; }
echo "    SSR active and healthy"

echo "==> opcache reset"
echo '<?php opcache_reset(); echo "cleared"; ?>' > public/oc.php
curl -s -m 15 http://mfaruk.com/oc.php || true; echo
rm -f public/oc.php

echo "==> restart the queue worker"
supervisorctl start portfolio-worker:* || true

echo "==> restore the cron"
crontab -u mfaruk /tmp/crontab.mfaruk.$STAMP.bak
crontab -l -u mfaruk | grep -c schedule:run | xargs echo "    schedule:run entries restored:"

echo "==> maintenance mode OFF"
php artisan up
EOSSH

# --------------------------------------------------------------- verification
say "Post-deploy verification"
FAILED=0
for p in / /photos /blog /about /contact /login /sitemap.xml /sitemap-images.xml /feed/rss /feed/atom /blog/feed.xml; do
    code=$(curl -s -m 25 -o /dev/null -w '%{http_code}' "https://mfaruk.com$p")
    if [[ "$code" == "200" ]]; then printf "  ✅ %-22s %s\n" "$p" "$code"
    else printf "  ❌ %-22s %s\n" "$p" "$code"; FAILED=1; fi
done

say "SSR under Googlebot UA (must be exactly one title, one ld+json, zero innerHTML=)"
TMP=$(mktemp)
curl -s -m 30 -A "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" https://mfaruk.com/ -o "$TMP"
T=$(grep -c '<title[ >]' "$TMP" || true)
L=$(grep -c 'application/ld+json' "$TMP" || true)
I=$(grep -c 'innerHTML=' "$TMP" || true)
echo "  title=$T ld+json=$L innerHTML=$I"
[[ "$T" == "1" && "$I" == "0" ]] || { echo -e "  ${R}SSR output looks wrong - this is the silent-failure mode${N}"; FAILED=1; }
rm -f "$TMP"

say "Server-side checks"
ssh "$SERVER" "cd $APP && php artisan --version && systemctl is-active mfaruk-ssr && supervisorctl status portfolio-worker:* | head -2 && php artisan about --only=environment 2>/dev/null | head -8"

if [[ "$FAILED" == "0" ]]; then
    echo -e "\n${G}=== DEPLOY OK ===${N}"
    echo "Still to check BY EYE (nothing automated can judge these):"
    echo "  - a watermarked photo: font, size, opacity and position"
    echo "  - upload one new photo end to end (avif + webp + thumbnail + blurhash)"
    echo "  - admin login, Editor.js, contact form, comment OTP"
    echo
    echo "Once happy: ssh $SERVER 'rm -rf $APP/vendor.bak-*'"
else
    echo -e "\n${R}=== VERIFICATION FAILED - consider rolling back ===${N}"
    cat <<'ROLLBACK'
  ssh mfaruk '
    cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
    php artisan down
    supervisorctl stop portfolio-worker:*
    rm -rf vendor && mv "$(readlink -f vendor.bak-prev)" vendor
    git reset --hard c04f897
    composer dump-autoload --optimize
    php artisan optimize:clear && php artisan config:cache && php artisan route:cache
    npm ci && npm run build
    systemctl restart mfaruk-ssr && systemctl is-active mfaruk-ssr
    supervisorctl start portfolio-worker:*
    php artisan up'
  Note: migrations in this upgrade are additive only - none were added by the
  framework bump itself, so a code rollback does NOT need a DB restore.
ROLLBACK
    exit 1
fi
