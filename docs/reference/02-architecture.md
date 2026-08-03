# 02 — Architecture

> Part 2 of 11. Previous: [`01-orientation.md`](01-orientation.md) · Next: [`03-data-model.md`](03-data-model.md)

---

## 2.1 The stack, with real versions

Verified from `composer.json`, `package.json`, and the production server.

**Backend**

| Component | Version | Note |
|-----------|---------|------|
| Laravel Framework | **12.54.1** (prod) | `^12.0` in composer |
| PHP | **8.4.10** (prod) | `^8.2` declared, so 8.2+ works |
| Inertia Laravel | `^2.0` (v2.0.22 installed) | SSR enabled |
| Ziggy | `^2.6` | Route names available in JS **and** SSR |
| Intervention Image (Laravel) | `^1.5` | Image manipulation |
| Flysystem AWS S3 v3 | `^3.0` | Drives the Cloudflare R2 disk |
| Google API Client | `^2.19` | Search Console OAuth |

**Frontend**

| Component | Version | Note |
|-----------|---------|------|
| Vue | `^3.5.30` | `<script setup>` throughout |
| @inertiajs/vue3 | `^2.3.18` | v2 features incl. `InfiniteScroll` |
| Vite | `^7.3.1` | Client build + separate SSR build |
| Tailwind CSS | `^3.4.19` | **v3, not v4** |
| Editor.js | `^2.31.5` + 6 plugins | Blog and About page content |
| DOMPurify | `^3.3.3` | Sanitising, with an SSR guard |
| ziggy-js | `^2.6.2` | Client-side `route()` |

⚠️ `@tailwindcss/vite ^4.2.1` is present in devDependencies but the project uses **Tailwind v3**
(`tailwindcss ^3.4.19`) with `postcss` and `autoprefixer`. Do not assume v4 semantics.

**Build commands** — only two npm scripts exist:

```
npm run dev     → vite
npm run build   → vite build && vite build --ssr      ← BOTH builds, always
```

---

## 2.2 Hosting topology

```
                         ┌────────────────────────────┐
   visitor ──HTTPS──▶    │  Cloudflare                │  registrar + DNS + CDN + R2
                         │  (free tier; only domain   │  Turnstile for the contact form
                         │   registration is paid)    │
                         └─────────────┬──────────────┘
                                       │ proxied
                         ┌─────────────▼──────────────┐
                         │  VPSDime VPS SERVER_IP │  HestiaCP, 30 GB disk (79% used)
                         │  Apache → PHP 8.4          │
                         │  MySQL  mfaruk_portfolio   │
                         │  systemd  mfaruk-ssr       │  Inertia SSR :13714
                         │  supervisor portfolio-worker│  queue:work database
                         │  cron  schedule:run (1 min)│
                         └─────────────┬──────────────┘
                          SSH (key)    │
        ┌────────────────────────────┐ │  ┌──────────────────────────────┐
        │ Cloudflare R2              │◀┘  │ Synology DS923+ NAS          │
        │ bucket: photography        │    │ n8n: backups, watchdog,      │
        │ originals/<uuid>.jpg       │    │ analytics push, digest       │
        │ 27/27 photo masters        │    │ weekly×4 + monthly×12        │
        └────────────────────────────┘    └──────────────────────────────┘
```

### The HestiaCP layout — the single most important structural fact

```
/home/mfaruk/web/mfaruk.com/
├── private/portfolio-app/           ← LARAVEL APP ROOT (the git repo)
│   ├── app/ config/ routes/ resources/ database/
│   ├── vendor/ node_modules/ storage/ bootstrap/
│   ├── .env                          ← production config
│   └── public/                       ← web-servable + Vite output
│       ├── index.php
│       ├── app-path.php              ← tells index.php where the app root is
│       └── build/                    ← Vite output, gitignored, built ON the server
│
└── public_html/  ──SYMLINK──▶  private/portfolio-app/public/
```

Consequences you must internalise:

- `public_html` **is** `public/`. They are the same directory. There is never a copy step.
- Laravel source must **never** be placed in `public_html` — it would be web-servable.
- `public/app-path.php` must contain exactly
  `<?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";`
  If the site 500s right after a deploy, check this first.
- Build output lands in `public/build/`, which is already `public_html/build/`.

---

## 2.3 Request lifecycle

```
Cloudflare
   ↓
Apache → public/index.php → app-path.php → private/portfolio-app/bootstrap
   ↓
bootstrap/app.php
   ├── trustProxies(at: '*')              ← Cloudflare, so HTTPS is detected
   ├── CSRF except: stripe/webhook
   ├── web middleware appended, in order:
   │      SecurityHeaders → TrackReferrals → HandleInertiaRequests
   ├── health endpoint: /up
   └── exception reporter → LoggingService::error() into the DB
   ↓
routes/web.php (or routes/api.php)
   ↓
Controller → Service → Model
   ↓
Inertia::render('Page/Name', props)
   ↓
SSR daemon at 127.0.0.1:13714 renders the real <head> + HTML
   ↓   (falls back to client-side render if the daemon is down)
Browser hydrates Vue
```

### Middleware, in detail

**`SecurityHeaders`** — sets on every web response:
`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection`,
`Referrer-Policy: strict-origin-when-cross-origin`,
`Permissions-Policy: camera=(), microphone=(), geolocation=(self)`,
`Strict-Transport-Security: max-age=31536000; includeSubDomains`, and a full **Content Security
Policy**.

The CSP allowlist is the thing that will bite you. It currently permits scripts from
`challenges.cloudflare.com` (Turnstile), `js.stripe.com`, `unpkg.com`, and Google Tag Manager /
Analytics; styles from Google Fonts and unpkg; images from anywhere over https/data/blob;
connections to Turnstile, Stripe, `*.r2.cloudflarestorage.com`, OpenStreetMap tiles, and Google
Analytics. **Adding any new third-party script or XHR host requires editing this CSP** or it
will be silently blocked in the browser console.

**`TrackReferrals`** — records external referrals into `referral_visits`. It deliberately skips
non-GET requests, AJAX, anything under `admin/*` or `dashboard`, and any visit already tracked
this session. A visit is only recorded when there are UTM parameters or the referer domain
differs from the current host.

**`HandleInertiaRequests`** — shares these props with **every** page:

| Prop | Contents |
|------|----------|
| `auth.user` | `id`, `name`, `email`, or `null` |
| `flash` | `success`, `error`, `warning`, `info` (lazy closures) |
| `appName` | `Setting::get('photographer_name')`, falling back to config |
| `theme` | `name`, `colors`, `styles`, `isDark` from `ThemeService` |
| `ziggy` | Full Ziggy route table plus current URL — **required so `route()` resolves during SSR** |

**`VerifyNasToken`** — API only. Compares the bearer token against
`config('services.nas.analytics_token')` using `hash_equals`, aborting 401 otherwise. Guards the
single API endpoint.

---

## 2.4 Inertia SSR

Enabled 2026-06-07 so that search engines and AI crawlers (ClaudeBot, GPTBot, Perplexity, Google
Images) receive a real per-page `<head>`. Before it, bots saw an empty SPA shell containing only
the site name.

**The pieces**

| Piece | Role |
|-------|------|
| `resources/js/ssr.js` | SSR entry — eager page glob, Ziggy from shared props, `@vue/server-renderer` |
| `config/inertia.php` | `ssr.enabled = true`, url `127.0.0.1:13714` |
| `vite.config.js` | `ssr: 'resources/js/ssr.js'` input; `manualChunks` scoped to the client build only |
| `package.json` | `build` runs client build **and** `--ssr` build → `bootstrap/ssr/ssr.js` (gitignored, built on server) |
| `deploy/mfaruk-ssr.service` | systemd unit running `php artisan inertia:start-ssr` as user `mfaruk` |
| `deploy.sh` | restarts `mfaruk-ssr` after every build |

**Ops:** `php artisan inertia:check-ssr` · `systemctl restart mfaruk-ssr` ·
`journalctl -u mfaruk-ssr`. Verified **active and enabled** on 2026-08-03. If the daemon dies,
Inertia falls back to client rendering — the site still works, it just loses the crawler-visible
head.

**Why `manualChunks` is client-only:** it conflicts with the SSR bundle's inlined dynamic
imports. The condition in `vite.config.js` is deliberate; do not simplify it away.

SSR imposes real constraints on frontend code — they are listed in
[`10-gotchas.md`](10-gotchas.md) §Frontend/SSR and in [`07-frontend.md`](07-frontend.md).

---

## 2.5 Runtime services on the box

| Service | Mechanism | What it does | Verified 2026-08-03 |
|---------|-----------|--------------|---------------------|
| Web | Apache + PHP 8.4 | Serves the app | live |
| **SSR** | systemd `mfaruk-ssr` | Inertia server render, port 13714 | **active, enabled** |
| **Queue worker** | supervisor `portfolio-worker` | `queue:work database --sleep=3 --tries=3 --max-time=3600`, 1 process, user `mfaruk` | **RUNNING** |
| **Scheduler** | user `mfaruk` crontab, every minute | `php artisan schedule:run` | present |
| Backups | **NAS n8n over SSH** — not on the server | see part 9 | live |

The scheduler currently has exactly **one** scheduled task, from `routes/console.php`:

```php
Schedule::command('stats:weekly')->weeklyOn(1, '09:00');   // Mondays 09:00 server time
```

The `mfaruk` crontab also sets `MAILTO=farooque7@gmail.com`, so cron errors are emailed.

⚠️ **The queue worker is load-bearing.** `ProcessPhotoUpload` is a queued job — if supervisor is
down, uploaded photos sit unprocessed forever with no visible error. There is currently **1
failed job**, from 2026-03-08, caused by a mail transport failure (`127.0.0.1:2525` refused)
that predates the mail fix. It is stale residue, not an active fault.

---

## 2.6 Storage and disks

`FILESYSTEM_DISK=local`. Configured disks live in `config/filesystems.php`. The important one is
the **R2 disk**, which is configured **at runtime** by `PhotoProcessingService::configureR2Disk()`
from database settings — not from `.env`. See [`06-photo-pipeline.md`](06-photo-pipeline.md).

`.env` does carry `R2_*` keys as a fallback shape, alongside `AWS_*`, `TURNSTILE_*` and
`GOOGLE_GSC_*`. But **the live values that matter are in the `settings` table.** If you change
R2 credentials in `.env` and nothing happens, that is why.

Derivatives (display, thumbnail, watermarked) live on the server under
`storage/app/public/`, exposed through the `public/storage` symlink. Masters live in R2 only.

---

## 2.7 Database, cache, queue, session

All four are MySQL. There is no Redis.

```
DB_CONNECTION=mysql        DB_DATABASE=mfaruk_portfolio
SESSION_DRIVER=database    CACHE_STORE=database
QUEUE_CONNECTION=database  MAIL_MAILER=smtp (Gmail, farooque7@gmail.com)
APP_ENV=production         APP_DEBUG=false
```

Practical consequences:

- `php artisan cache:clear` touches a **DB table**, not Redis.
- Sessions are rows. Gallery password unlocks and client-proofing selections ride on the
  session, so clearing sessions logs everyone out of protected galleries.
- The queue is a table. Everything about queue health is visible with plain SQL.
- A local `database/database.sqlite` exists but is **stale (April 2026)** and unused — there is
  no local runtime. Do not treat it as a source of truth.
