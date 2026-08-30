# 08 — Operations

> Part 8 of 11. Previous: [`07-frontend.md`](07-frontend.md) · Next: [`09-backup-and-automation.md`](09-backup-and-automation.md)

Everything about running the thing: deploy, access, services, debugging, recovery.

---

## 8.1 Access

| Target | How |
|--------|-----|
| **Production shell** | `ssh user@SERVER_IP` (unrestricted root key from the workstation) |
| App root | `/home/mfaruk/web/mfaruk.com/private/portfolio-app` |
| Web root | `/home/mfaruk/web/mfaruk.com/public_html` (**symlink** to `…/private/portfolio-app/public/`) |
| GitHub | SSH, as **mmhfarooque** (`farooque7@gmail.com`) |
| **Break-glass** | VPSDime out-of-band console → `terminal.vpsdime.com`, VM **55504** → `root@server` |

### ⚠️ mfaruk.com is NOT alone on this box

The same HestiaCP server (user `mfaruk`) also hosts:

| Site | Type |
|------|------|
| **dev.mfaruk.com** | dev/staging |
| mahmudfarooque.com | static HTML |
| climb4earth.org | WordPress |
| mimosaw.com | WordPress |
| mytravell.info | WordPress |
| salehinarshady.com | WordPress |
| PANEL_HOST | HestiaCP admin panel (user `admin`) |

This matters for anything server-wide: **restarting PHP-FPM, Apache or Nginx affects all of
them**, and the 30 GB disk is shared. Never treat a service restart as scoped to mfaruk.com.

### ⚠️ The `mfaruk` SSH alias is NOT a deploy key on this laptop

Verified in `~/.ssh/config`:

```
Host mfaruk
    HostName SERVER_IP
    User root
    IdentityFile ~/.ssh/mfaruk-mcp        ← the MCP log-reader key (forced command)
```

`deploy.sh` hardcodes `SERVER="mfaruk"`. On the **PC** that alias is an unrestricted deploy key
and the script works. **On this laptop it is the restricted log-reader key, so `./deploy.sh`
will not deploy from here.** Deploy from the laptop by SSHing to `user@SERVER_IP` and running
the steps directly, or fix the alias. Unifying this across machines is a known open item.

**Break-glass recovery from any machine:** generate a key → paste the public key into
`/root/.ssh/authorized_keys` through the VPSDime console → SSH works. The console sits below SSH
and the network, so lockout is not possible.

---

## 8.2 Deploy

**The rule: git only. Never rsync or scp application code to the server.**

```
local edit → sanitize → git commit → git push origin main
          → server: git reset --hard origin/main → build
```

`deploy.sh` refuses to run unless the working tree is clean **and** local `HEAD` equals
`origin/main`. That guard is deliberate.

### Full deploy — what actually runs on the server

```bash
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
git fetch origin
git reset --hard origin/main
composer install --no-dev --optimize-autoloader --quiet
php artisan migrate --force
php artisan optimize:clear
npm install --silent
npm run build                       # client + SSR
php artisan config:cache
php artisan route:cache
chown -R mfaruk:www-data storage bootstrap/cache public/build bootstrap/ssr
chmod -R 775 storage bootstrap/cache
systemctl restart mfaruk-ssr
# opcache reset via a temporary public/oc.php, curl'd then deleted
```

`./deploy.sh --quick` skips `npm install` and `npm run build` — code-only changes.

### Notes that matter

- **`route:cache` is fine and is used.** An older warning said never to route:cache this
  project. That warning is **obsolete** — `deploy.sh` runs it on every deploy and the site is
  healthy. Do not remove it based on stale documentation.
- **`git reset --hard`** means anything modified directly on the server is destroyed on the next
  deploy. This is why the old `public/backup-panel/` could not be removed server-side alone — it
  was tracked in git and kept coming back.
- **Migrations run automatically** with `--force`. Any migration you commit will execute on the
  next deploy.
- **The opcache reset is a temporary public PHP file**, curl'd and then deleted. If a deploy
  aborts midway, check that `public/oc.php` is gone.
- **There are two different deploy scripts.** The repo's `deploy.sh` and the server-side
  `/home/mfaruk/deploy.sh` are not the same file. The server copy lacked the `mfaruk-ssr`
  restart until 2026-07-26, which caused SSR to serve a two-week-stale bundle with fresh props.
  It is patched — **keep the SSR restart if that script is ever regenerated.**

---

## 8.3 Runtime services

| Service | Manager | Command / unit | Verified 2026-08-03 |
|---------|---------|----------------|---------------------|
| SSR | systemd | `mfaruk-ssr` → `php artisan inertia:start-ssr`, port 13714, user `mfaruk` | **active, enabled** |
| Queue worker | supervisor | `portfolio-worker` → `queue:work database --sleep=3 --tries=3 --max-time=3600`, 1 proc | **RUNNING** |
| Scheduler | cron (user `mfaruk`) | `* * * * * php artisan schedule:run` | present |

```bash
# SSR
systemctl status mfaruk-ssr ; systemctl restart mfaruk-ssr
journalctl -u mfaruk-ssr -n 100
php artisan inertia:check-ssr

# Queue
supervisorctl status
supervisorctl restart portfolio-worker
tail -f storage/logs/worker.log
```

`--max-time=3600` means the worker recycles hourly by design; a short uptime is normal, not a
crash. The `mfaruk` crontab sets `MAILTO=farooque7@gmail.com`, so cron errors are emailed.

---

## 8.4 Production configuration

```
APP_ENV=production          APP_DEBUG=false
DB_CONNECTION=mysql         DB_DATABASE=mfaruk_portfolio
SESSION_DRIVER=database     CACHE_STORE=database
QUEUE_CONNECTION=database   FILESYSTEM_DISK=local
MAIL_MAILER=smtp            MAIL_USERNAME=farooque7@gmail.com
```

`.env` also carries `R2_*`, `AWS_*`, `TURNSTILE_*`, `GOOGLE_GSC_*`, and
`NAS_ANALYTICS_TOKEN`.

⚠️ **Most runtime configuration is in the `settings` DB table, not `.env`.** R2 credentials,
Turnstile keys, watermark and quality settings, AI provider, theme, and SEO flags all live
there. See [`03-data-model.md`](03-data-model.md) §3.6.

### Mail

Gmail SMTP with an **app password** under `farooque7@gmail.com`.

⚠️ **This has failed silently once.** The app password was revoked and *all* site mail — contact
notifications, comment OTPs — died with no visible error for weeks. A new app password was set
2026-07-26 (`.env` backup at `.env.bak-20260726`). **If mail stops, check Google app passwords
first.** The single row in `failed_jobs` is residue from a related March mail-transport failure.

---

## 8.5 Debugging production

### Site returns 500 after a deploy

```bash
cat /home/mfaruk/web/mfaruk.com/public_html/app-path.php
# must be exactly:
# <?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";

cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 502 Bad Gateway

```bash
systemctl restart php8.4-fpm ; systemctl restart apache2 ; systemctl restart nginx
```

### Pages render but crawlers see nothing

SSR is down. `systemctl is-active mfaruk-ssr` → restart. Inertia falls back to client rendering,
so this is **invisible to a human visitor**.

### Uploaded photos stuck in processing

The queue worker. `supervisorctl status` → restart `portfolio-worker`. Then check
`storage/logs/worker.log` and the `failed_jobs` table.

### Application errors

They are in the **database**, not just the log file: `activity_logs`, surfaced at `/admin/logs`.
The global exception handler writes every unhandled exception there (except auth, CSRF, 404,
405).

```bash
tail -f storage/logs/laravel.log
```

### A third-party script or XHR is blocked in the browser

The **CSP** in `app/Http/Middleware/SecurityHeaders.php`. Any new external host must be added to
the relevant directive or it is silently blocked.

---

## 8.6 Reading and writing production data

Always from the app root, and mind the tinker quirks:

```bash
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan tinker
```

⚠️ **tinker gotchas** (from `MFARUK_WORKFLOW.md` §13, all confirmed in practice):

- Strip `<?php` and `use …;` lines, or use fully-qualified class names.
- **Pipe scripts via stdin** (heredoc). `--execute="…"` mangles backslashes and backticks.
- `scp` lands files as `root:root` → always `chown mfaruk:www-data && chmod 644` afterwards,
  especially under `storage/app/public/`.

Example that works:

```bash
ssh user@SERVER_IP 'cd /home/mfaruk/web/mfaruk.com/private/portfolio-app && cat <<EOF | php artisan tinker
echo App\Models\Photo::published()->count();
EOF'
```

---

## 8.7 Capacity

| Resource | State 2026-08-30 | Headroom |
|----------|------------------|----------|
| Server disk | 30 GB, **23 GB used, 5.4 GB free (81%)** | ⚠️ watch this |
| App directory | 1.1 GB | grew with the responsive variant store |
| Cloudflare R2 | **409 MB** of 10 GB free tier (27 masters) | ~500 masters, years |
| NAS | ~4 TB RAID, 2.1 TB free | ample |

**The disk is the constraint.** It is the reason masters live in R2 and the reason backups are
pulled to the NAS rather than written locally. Before adding anything that stores files on the
server, check `df -h /` first.

`activity_logs` at 8,875 rows is not yet a problem, but `ActivityLog::cleanup()` is **not
scheduled**. It is the most likely future source of quiet growth.

---

## 8.8 Security posture

- **Headers and CSP** on every response (§8.5, `SecurityHeaders`).
- **Turnstile** on the contact form, plus spam keyword detection, suspicious-email patterns, a
  honeypot field, rate limiting at 5 submissions per hour per IP, and UTF-8 sanitisation.
- **Comment OTP** — email verification before a comment is accepted; blocked-email list on top.
- **`trustProxies(at: '*')`** so Cloudflare's forwarded protocol is honoured.
- **Only `stripe/webhook` is CSRF-exempt.**
- Registration is technically open despite there being one intended user.

### May Day contingency — dormant, flip-a-switch

Pre-staged and **off** by design, so there is no always-on latency cost: a `maydayctl` script to
enable Cloudflare Under Attack Mode with pre-staged rate limits, WAF rules and Bot Fight
(`normal` reverts), origin cloaking behind a proxied A record, optionally firewalling origin
80/443 to Cloudflare IPs while leaving SSH:22 direct so the MCP still works, and a Cloudflare
fallback holding page. Needs a **personal, zone-scoped** Cloudflare API token for mfaruk.com
only. Cloudflare already absorbs volumetric L3/L4 DDoS on the free tier.

The old publicly-reachable `/backup-panel` — the single worst exposure this site had — was
**removed 2026-08-02**. See [`09-backup-and-automation.md`](09-backup-and-automation.md).

### Secrets

Design: encrypted `install/secrets.enc` plus a plaintext `install/SECRETS.README` carrying the
decrypt command and a hint but **never** the passphrase. Intended to hold server SSH, R2 keys,
Cloudflare token, admin, and GitHub credentials. Plaintext is gitignored. **This is designed but
not yet built.** The repo is private in the meantime.
