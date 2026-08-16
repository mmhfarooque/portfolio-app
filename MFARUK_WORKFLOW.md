# MFARUK_WORKFLOW.md — the mfaruk.com operating brain

> **Read this FIRST for POLICY** — golden rules, boundaries, access, secrets, contingency.
> Personal project of **Mahmud Farooque**. **Nothing here touches Jezweb** (see §0 boundary).
> This file lives in git, so a `git clone` delivers the whole brain to every machine.
>
> 📚 **For TECHNICAL depth, read [`docs/reference/`](docs/reference/00-INDEX.md)** — a sequential
> 12-part set (architecture, data model, HTTP surface, services, photo pipeline, frontend,
> operations, backups, gotchas, roadmap) verified against the live server and NAS on
> **2026-08-03**. Start at `docs/reference/00-INDEX.md`. Where that set and any other doc in this
> repo disagree, **the reference set wins** — it is the only one checked against live systems.
>
> _Living doc — edit, update, refine freely. Last updated: 2026-08-03._

---

## 0. Golden rules (read, then act)

1. **Git is authoritative.** Every machine and the server sync *from* GitHub. **Pull before you work**; reconcile divergence with "git wins."
2. **No local runtime.** We do **not** run the app locally — no local server, DB, `.env`, or build. Develop = edit code → commit → push → the **server** builds. Verify on prod or `dev.mfaruk.com`.
3. **Deploy chain (each link a checkpoint):** local edit → sanitize → `git commit` → `git push` (GitHub) → server `git reset --hard origin/main` + build. **Never** rsync/scp app code to the server.
4. **Personal only — Jezweb boundary is absolute.** mfaruk is Mahmud's *personal* work. **Never** use the Jezweb MCPs (Google Analytics, Search Console, Cloudflare, etc. under `mahmud@jezweb.net`). Use **own infra only**: `mfaruk-mcp`, personal Cloudflare/GSC/Bing under `farooque7@gmail.com`, the NAS.
5. **Backups sacred; no self-destruct.** Never delete data/DB/backups or run a destructive op without a **verified backup + explicit go**.
6. **New feature / "is X possible?" → RESEARCH it.** Never answer capability questions from a training cutoff. Check current docs/web first.

---

## 1. What it is
A **photography portfolio + technical blog**. Stack: **Laravel 12 + Vue 3 + Inertia.js (SSR enabled) + Tailwind CSS + Vite 7**. Live at **https://mfaruk.com**.

## 2. Hosting & topology
- **Origin server:** VPSDime VPS — `user@SERVER_IP`, **HestiaCP**, PHP 8.4, 30 GB disk.
  - App root: `/home/mfaruk/web/mfaruk.com/private/portfolio-app`
  - `public_html` → **symlink** to `public/`; `app-path.php` boots Laravel.
  - **SSR daemon:** systemd `mfaruk-ssr` (Inertia SSR for crawler-visible SEO).
- **Cloudflare:** domain **registrar + DNS + CDN + R2** (Mahmud pays only for domain registration; everything else is free tier).
- **Repo:** `git@github.com:mmhfarooque/portfolio-app` — **PRIVATE**, branch `main`. Personal GitHub = **mmhfarooque** (`farooque7@gmail.com`). *(Work account `mahmudfarooque` cannot access it — use SSH + the mmhfarooque account for `gh`.)*
- **NAS:** Synology **DS923+**, RAID (redundant), ~**4 TB**, on Tailscale `100.82.168.76`. Target host for n8n + mfaruk-mcp + MinIO.

## 3. Repository & canonical layout
- **Canonical local path on EVERY machine: `~/portfolio-app`** (matches repo name; no "dev" wrapper — it's a running product). This laptop already matches.
- **Fresh machine = self-onboard, not install.** Point an AI at the repo; it reads this file and pulls what it needs:
  1. install `git` → `git clone git@github.com:mmhfarooque/portfolio-app.git ~/portfolio-app`
  2. ensure `origin` uses **SSH** (HTTPS is broken — wrong active `gh` account)
  3. decrypt secrets on demand (§14) when a task needs credentials
  4. start editing. **No PHP/Composer/Node/DB install needed** (server builds; nothing runs locally).

## 4. Dev & deploy
- **`deploy.sh`** (SSR-aware): `git reset --hard origin/main` → composer → migrate → `npm run build` (client + SSR) → `config:cache` + `route:cache` → chown → restart `mfaruk-ssr` → opcache reset.
- **Deploy from THIS laptop:** use **`user@SERVER_IP`**, *not* the `mfaruk` alias. ⚠️ On this laptop the `mfaruk` SSH alias is a **log-reader-only** key (forced command); `deploy.sh`'s `ssh mfaruk` works on the **PC**, not here. _(Target: one consistent deploy alias across machines.)_
- `route:cache` is **fine now** — `deploy.sh` uses it; the old "never route:cache" warning is obsolete.

## 5. Access & recovery
- **Daily:** SSH key (unrestricted root) from the workstation; GitHub over **SSH**.
- **Break-glass (never locked out):** VPSDime **out-of-band console** → `terminal.vpsdime.com` (VM **55504**) → `root@server`. Sits below SSH/network. Recovery from any machine: generate a key → paste the pubkey into `/root/.ssh/authorized_keys` via the console → SSH works.
- Optional hardening: firewall SSH to Mahmud's dedicated IP + the NAS IP (key stays primary auth, IP is just an extra lock).

## 6. Data model
**Photos** → belong to a **Category** + a **Gallery**, tagged with **Tags** (shared table with Posts). **Blog Posts** (Editor.js) — the current traffic driver. Likes + threaded comments (OTP-verified). **Series: REMOVED 2026-07-12** (code + empty DB tables dropped). ~10 categories, ~8 galleries.

## 7. Photos — R2 storage & on-demand regeneration ✅ (verified 2026-07-12)
- Upload a **processed high-res JPEG master ONCE** → stored in **Cloudflare R2** (bucket `photography`, key `originals/<uuid>.jpg`, referenced as `r2:originals/…` in `original_path`). **Never on the 30 GB server.**
- The site **regenerates** display / thumbnail / watermarked derivatives **on demand** from the R2 master: **watermark on/off per photo** (`watermark_disabled` flag) and **quality/resolution** (`custom_quality`, `custom_max_resolution`). **No re-upload, ever.**
- **Verified:** 21/21 photos have their R2 original, **0 missing**. Sizes **1.2–36.2 MB** (avg ~14). RAW **`.RAF` (~81 MB) stay local/NAS — never uploaded.**
- **Watermark preservation on restore:** (a) Lightroom/darktable stamp = baked into the R2 master (always kept); (b) app watermark = DB flag, reapplied on regen. Both survive because the DB is backed up and regen respects the flag.
- **Free-tier math:** R2 = 10 GB free ≈ **~500 masters** (at ~20 MB). Currently 313 MB / 10 GB. **$0** for years.

## 8. Storage scaling — FALLBACK (dormant, stays in the system)
When R2's ~500-master free ceiling is ever approached: **NAS + MinIO** (S3-compatible, free OSS) over **Tailscale**; server pulls originals to regenerate; Cloudflare serves derivatives. **$0.** Same S3 API as R2 (swap endpoint+keys, no app rewrite). Not needed for years — a documented overflow tier, not an active build.

## 9. Backups — NAS-orchestrated (target design)
- **Model:** n8n (on the NAS) runs a scheduled workflow → **SSH into the server** (via mfaruk-mcp / SSH node) → `mysqldump` + `tar` files → **store on the NAS** (Synology RAID mirrors to the 2nd drive). The **server hosts no backup system** — retire the old `backup.php` + cron + public `/backup-panel` + server-disk backups. Server load = a brief dump/tar only.
- **Scope:** 4 TB NAS ⇒ **full backups incl. images** (drop the old no-images/restore-from-R2 space trick — that only existed for the tiny server disk).
- **Offsite leg (true 3-2-1):** push the small **DB dump → R2** periodically (free). ⚠️ **RAID ≠ backup** — it guards against a dead drive, not deletion/corruption/ransomware/NAS loss. Images are already independently safe in R2.
- **Retention (GFS) — AS BUILT:** **12 monthly + 4 weekly** (the original design said 8 weekly; the shipped workflow keeps 4). Oldest pruned on each new archive, and **prune only runs after the new file has landed and its sha256 matches**, so a failed run can never shrink the pool. Pruning sorts by **filename** (timestamps are embedded) not mtime, because a copy or restore rewrites mtime. n8n owns the rotation.
- **✅ OLD SYSTEM REMOVED 2026-08-02.** The server now hosts **no backup system at all**. What was actually found (the earlier note that it was simply *disabled* was wrong):
  - `public/backup-panel/` was **live and publicly reachable** — HTTP 200, a 60 KB PHP file executing outside Laravel via its own `.htaccess`. It was **tracked in git**, so a server-side delete alone would have been undone by the next `deploy.sh`.
  - The `mfaruk` crontab **still fired** `scheduled-backup.sh` weekly + monthly. It exited at line 1 every time because `.schedule-enabled` / `.schedule-config` were deleted from the panel on **15 Mar 2026 11:46:17**. Five silent no-ops a month for ~5 months — no error, no alert. That is why the gap went unnoticed.
  - `scheduled-backup.sh` **hardcoded the pre-May password in plaintext**; `ACCESS.md` carried the panel password in plaintext too.
  - A **second** system existed: a Backblaze **B2** integration in the Laravel admin (`/admin/backup`, `BackupController`, `backup:photos`, Vue page). `B2_*` was never set, `last_backup_at` was never — it **never ran once**. Removed as dead code.
  - Backups were written to the **same 30 GB disk they protected**. Never offsite.
- **Removal was non-destructive.** Server files moved to `/home/mfaruk/_retired/backup-system-20260802/` (incl. the pre-change crontab). Repo files removed with `git rm` — full contents stay in history. All archives copied to **laptop** (`~/mfaruk-backups/legacy-server-backups-20260802/`) **and NAS** (`Backup/mfaruk.com/legacy-server-backups-20260802/` on the `home` share), MD5-verified identical, with a `README.md` documenting inventory and rollback.
- **✅ STATE 2026-08-03: the pipeline is BUILT and LIVE.** Six n8n workflows on the NAS —
  backup (`IpJIdLb6fzqtouDl`, weekly Sun 10:00 keep 4 / monthly 1st 10:30 keep 12), backup
  staleness watchdog (`7ebLQGWs2DJjZsHz`, daily 11:00), failure alert, update notifier, digest,
  and (added 2026-08-16) sitemap staleness watchdog (`OnQPFp72l32FcGx1`, daily 09:20 Dhaka —
  GSC Sitemaps API `lastDownloaded` > 30 days, missing submission, or Google-reported errors
  → Telegram; silent when healthy). The
  archive never passes through n8n: SSH `create <tier>` on the server → JSON receipt with a hard
  throw → NAS collector pulls, verifies sha256, prunes → `cleanup` → Telegram. Verified against
  real files: 4 weekly + 1 monthly, each with a `.sha256` sidecar matching the execution
  receipt, `gzip -t` clean, ~194 entries incl. a gzipped DB dump. The old 12 Jul dump is
  superseded. **Full detail: [`docs/reference/09-backup-and-automation.md`](docs/reference/09-backup-and-automation.md).**
- **⚠️ STILL OPEN:** (a) **no restore test has ever been run** — integrity is verified, restore
  is not; (b) the **offsite DB→R2 leg is designed but not built** (RAID ≠ backup); (c) the NAS is
  off overnight and n8n never back-fills; (d) the n8n image is unpinned `:latest`.
- Historical note: `pre-phase2-20260308.sql.gz` was **20 bytes — a truncated, unusable gzip**.

## 10. Content workflows
- **`/content [photo-id | URL]`** (see `CLAUDE.md` for the full step list): reverse-geocode GPS → **confirm location with Mahmud** → generate title / slug / first-person story (no travel-brochure fluff) / `seo_title` ≤70 / `meta_description` ≤160 / assign category + gallery / 10–15 tags → publish + feature.
- **Blog articles:** working area `articles/` (on NAS/local, **not** in the app repo) — `article.md` + `meta.md` + `images/` → WebP-optimize (`cwebp -q 82 -resize 1920 0`) → `tinker` insert → the **`article-manager`** drift-detector keeps live articles accurate vs their repos.

## 11. Analytics / reach — own infra only
- **`mfaruk-mcp`** (personal MCP; "no connection to Jezweb"): SSHes to the server, parses the Apache access log — **human reads vs bot crawls**. Tools: `status`, `summary`, `read_traffic`, `crawl_activity` (windowed via `since`).
- **Personal GSC + Bing** under `farooque7@gmail.com` — submit URLs via Mahmud's own logins. **Claude's GSC/Bing/Cloudflare MCPs are the Jezweb account — do NOT use them here.**
- **Reach reality (30 d, Jul 2026):** ~4,110 unique humans. **Blog drives everything** (Linux articles: 855 / 463 / 267 / 244 views); individual **photo pages get single-digit-to-~34** views. Photos are crawlable (SSR) but not yet *found* — the fix is content + search-intent SEO + blog→photo internal links, not infra.

## 12. Security — "May Day" contingency (dormant, flip-a-switch)
Pre-staged, **OFF** until an attack (no always-on latency): a `maydayctl` script (enable Cloudflare **Under Attack Mode** + pre-staged rate-limits + WAF + Bot Fight; `normal` reverts), **origin cloaking** (proxied A record; optional firewall origin 80/443 to Cloudflare IPs — SSH:22 stays direct so mfaruk-mcp is unaffected), and a Cloudflare **fallback holding page**. Needs a **personal, scoped** Cloudflare API token (mfaruk.com zone only). ~~Decommission the public `/backup-panel`~~ **DONE 2026-08-02** (§9). Cloudflare already absorbs volumetric L3/L4 DDoS by default.

## 13. Gotchas (don't relearn these)
- **tinker:** strip `<?php` and `use …;` lines (or use fully-qualified names); pipe scripts via **stdin**; `--execute="…"` mangles backslashes/backticks.
- **scp lands as `root:root`** → always `chown mfaruk:www-data && chmod 644` after (especially `storage/app/public/`).
- **Injecting into `.vue`:** use **Python** (`read_text`/`replace`/`write_text`), never `sed` (CSS braces/percent break it).
- **Deploy from laptop:** `root@IP`, not the `mfaruk` alias (see §4).
- **Server-side `/home/mfaruk/deploy.sh` vs repo `deploy.sh`:** they are different scripts. The server copy had NO `mfaruk-ssr` restart until 2026-07-26 (daemon ran stale from Jul 12–26; deploys served old SSR HTML with fresh props). Patched on the server — keep the SSR-restart step if that script is ever regenerated.

## 14. Secrets (encrypted, in-repo)
Encrypted **`install/secrets.enc`** (portable cipher, e.g. `openssl aes-256`) + plaintext **`install/SECRETS.README`** (decrypt command + hint, **never** the passphrase). Holds: server SSH, backup password, R2 keys, Cloudflare token, admin, GitHub. **Plaintext is `.gitignore`d and never committed.** Repo is private. A master passphrase unlocks it; any AI given the passphrase can decrypt on demand. _(Build pending.)_

## 15. Hard boundaries
- **Jezweb separation is absolute** — own infra only for anything mfaruk.
- **Never post / reply / send as Mahmud** on any channel without an explicit ask + double-confirm.
- **No auto-delete** of data/files/DB/backups; verified backup + explicit go before any destructive op; **backups are sacred**.

## 16. Current state / where we left off (2026-08-16)

**Session 2026-08-16 (PC) — Google Images investigation + sitemap fixes (deployed, `fe77cb9`):**
- **Why photos never reached Google Images (measured from origin logs, 6 weeks retained):**
  (1) **Googlebot never fetched either sitemap — zero times in all retained logs.** ClaudeBot
  read them 800×, bingbot 189×, GPTBot 70×; Google 0×. CORRECTED 2026-08-16 against Mahmud's GSC
  screenshot: `/sitemap.xml` WAS submitted 8 Mar 2026 (Success, 135 pages discovered) but Google
  **last read it 22 Mar 2026 and never returned** — it went stale, not unsubmitted.
  `/sitemap-images.xml` was genuinely never submitted. (2) Images were invisible
  pre-SSR (before 2026-06-07 crawlers got an empty shell) + the Jul 12–26 SSR outage — Google's
  first real look at photo pages was late July. (3) Tiny crawl budget: robots.txt fetched 3× in
  6 weeks, photo pages single-digit crawls/day. (4) Split signal: sitemap advertised
  `display/*.avif` while pages (og:image, JSON-LD, img) exposed `watermarked/*.avif`.
  **NOT the problem:** robots.txt, AVIF, file serving (all 200 via CF), SEO markup.
- **Turnaround already visible:** Googlebot-Image arrived in force **15 Aug** — 74 image fetches
  (28 display / 25 watermarked / 17 thumbnails, all 200) + 29 photo-page crawls that day, vs
  ~0/week prior. Google scraped `display/` URLs from the Inertia props JSON embedded in page HTML.
- **Shipped `fe77cb9`:** both sitemap blades now emit `watermarked_path ?? display_path`
  (exactly mirrors `Show.vue`); `Photo::incrementViews()` + `Post::incrementViews()` wrap the
  increment in `timestamps = false` so views no longer churn `lastmod`. **Verified live:** all 26
  image-sitemap entries watermarked, 0 display; tinker test — views +1, `updated_at` unchanged
  on both models. Likes/comments counters still bump `updated_at` (low-frequency, left as-is).
- **✅ GSC SUBMISSION DONE 2026-08-16 (same session):** property was already verified (URL prefix,
  farooque7). `sitemap.xml` resubmitted → Googlebot fetched it ~1 min later (200), GSC Success,
  **442 pages discovered** (was 135 in Mar). `sitemap-images.xml` submitted fresh → after one
  trailing-dot typo (404, removed) the correct URL fetched 200, GSC Success, **26 images
  discovered**. All fetches log-verified from origin (66.249.64.x). The five-month sitemap
  staleness is fully broken. Now: give Google Images 2–6 weeks; baseline lives in GSC
  Performance → Search results → Search appearance / Image filter.
- Housekeeping: repo-local git identity set on the PC (`farooque7@gmail.com`) — the global
  `includeIf` only covers `~/Jezweb/`; fresh machines will hit the same author-unknown error.
- **✅ Photo SEO backfill (plan item 2) RESOLVED same day:** full audit of all 27 photos found
  the fleet already at the #42/#43 standard — only 4 targeted fixes needed, applied via tinker
  and live-verified: #41 location_name added (geo now in image sitemap) + Thailand in seo_title
  + tags 24→15; #26 assigned to Dhaka gallery; #18 seo_title tail → Sleeping Buddha View (+tag);
  #24 seo_title tail → Annapurna & Manaslu Views. Everything else deliberately left unchanged.
  **Still open: draft #31** (`dsf2503-enhanced-nr`, X-T5 + XC50-230 @230mm, 2024-11-10 16:55,
  no GPS) — FLOW-1 content package awaits Mahmud's what/where.
- **✅ Social cards + X queue SHIPPED (`f138d0c`), live-verified:** (a) new
  `/photo/{slug}/card.jpg` — lazily GD-transcoded JPEG (max 1200px, q85, cached in
  `storage/photos/social/`) because social scrapers are unreliable with AVIF (researched);
  `og:image`/`twitter:image` now advertise the card, JSON-LD keeps the AVIF. (b) X
  confirm-before-post queue: PhotoObserver/PostObserver draft a **pending** SocialPost on
  publish (dedup-guarded, nothing auto-sends); photo captions end with the page URL;
  `publishToTwitter` trims without ever cutting the trailing URL. Fixed latent fatal
  (`SocialPost::getImageUrl` called nonexistent methods) + PHP 8.4 nullable deprecations.
  Verified: card 1200×682 JPEG live; observer test drafted queue item #1 (photo 47).
  **Cross-post extension also SHIPPED (`ff5ffb6`):** one publish click fans out to every
  connected platform. Pinterest publishing added (API v5; **board id lives in
  `platform_user_id`**); Instagram sends landscape photos as a new **4:5 vertical card**
  (`/photo/{slug}/card-vertical.jpg`, 1080×1350, photo on a blurred darkened fill —
  live-verified visually) because **the IG feed API hard-rejects anything taller than 4:5;
  true 9:16 is Stories-only** (researched 2026-08-16). Observers draft one pending post per
  connected platform, twitter fallback when none.
  **✅ Pinterest DONE 2026-08-16: mfaruk.com CLAIMED** (Business account, Content
  creator/Travel; `p:domain_verify` tag hardcoded in `app.blade.php`, `96ebed0`). Boards
  *Nature* + *Social* exist; IG @mahmudfarooque claimed there with auto-publish→Social ON
  (those pins link to IG, not the site — ambient only). Stale `mahmudfarooque.com` (dead DNS)
  worth unclaiming.
  **✅ X leg WIRED AND VERIFIED 2026-08-16:** app `2088961215854698496MMHFarooque` on
  console.x.com (new pay-per-use console; the old JEZWEB TWEETER FEED app on the same login is
  OFF-LIMITS). OAuth 1.0a portal tokens (permanent) stored in SocialAccount id 1
  (`consumer_key`/`consumer_secret`/`token_secret` columns added in `6e10cb7`, signing
  implemented in SocialMediaService). Signed `GET /2/users/me` returned 200 = @MMHFarooque.
  First real publish MEASURED 2026-08-16: **failed cleanly with credits depleted — the
  pay-per-use tier has NO free posting allowance** (auth passed; billing blocked). Photo 47
  draft sits as Failed-retryable. To activate the X leg: buy a minimal credit pack in
  console.x.com → Billing, then re-publish. Note: the queue Index shows no Publish action on
  Failed rows — retry via the post's View page or flip status back to pending.
  **Mahmud's account legs still open:** (1) Pinterest developer app → token with
  `pins:write`+`boards:read` + the target board id (Nature) into Admin → Social → Accounts
  (board id goes in `platform_user_id`);
  (3) Instagram — CORRECTED 2026-08-16: Mahmud has @mahmudfarooque and **no Facebook is
  needed**: convert to a Creator account, create a Meta developer account with EMAIL,
  app with the Instagram-Login use case, own account as Instagram Tester (dev mode, no app
  review for own-account posting), long-lived token. When connected, switch
  `publishToInstagram` from `graph.facebook.com` to `graph.instagram.com` endpoints.
  **NEXT after accounts: Laravel 12→13 upgrade** (§16 R&D verdict, ride a fresh weekly
  backup, own session).
- **✅ Licensable-badge schema SHIPPED (`263ec51`), live-verified:** ImageObject on every photo
  page now carries `license` (→ new `/image-license` static page, footer-linked),
  `acquireLicensePage` (→ the photo's own `/photo/{slug}/print` page), `creditText`, and
  `copyrightNotice` (year from `captured_at`). Qualifies photos for the Google Images
  Licensable badge. Optional follow-up NOT built: embedding IPTC creator/copyright into the
  derivatives so GSC's Image metadata report lights up (schema alone already qualifies).

## 16a. Previous state (2026-07-26)

**Session 2026-07-26 (laptop) — everything below is deployed and in git; PC picks up with a plain `git pull`:**
- **Contact reply feature LIVE** — in-admin reply form (`POST admin/contacts/{id}/reply`): ContactReply mailable (markdown, quotes original), stores `reply_subject`/`reply_message` on contacts, sets replied status. Replaced the dead `mailto:` button. Also fixed the whole ContactController calling nonexistent `LoggingService::logActivity()` (latent 500s) → real static API `LoggingService::activity()/error()`.
- **Site email FIXED** — Gmail app password had been revoked (all site mail silently dead: contact notifications, OTPs). New app password *mfaruk.com mailer* set in server `.env` `MAIL_PASSWORD` (2026-07-26, backup at `.env.bak-20260726`), verified sending. If mail dies again: check Google app passwords under farooque7@gmail.com first.
- **Blog photo outro LIVE** — `Components/Blog/PhotoOutro.vue` on every article: 3 random featured photos + browse-all link. Copy variants: dev articles = *When I'm not coding*; photography-category articles = *More frames from my camera* (detected via category name/slug containing photo/camera). Props from `BlogController@show` (`outroPhotos`, `isPhotographyPost`).
- **SSR was STALE Jul 12–26** — server-side `/home/mfaruk/deploy.sh` lacked the `mfaruk-ssr` restart (see §13). Patched; every deploy now restarts SSR. Also fixed nonexistent `photo.show` route name (→ `photos.show`) that crashed SSR on all category/gallery/tag pages — crawlers had been getting empty fallback HTML there.
- **Photos:** #42 rusty bicycle (Swiss Sheep Farm, Pattaya) PUBLISHED — created category *Still Life & Details*; #43 fountain channel (JN Memorial Botanical Garden, Srinagar, coords set manually — no GPS in file) content-complete, **DRAFT, awaiting Mahmud's publish**.
- **Laravel 13 R&D verdict (researched 2026-07-26):** upgrade is LOW RISK, not urgent. L12 bug fixes end 2026-08-13, security until 2027-02-24. PHP 8.4 OK; Inertia/Ziggy/Vite all 13-ready; official upgrade ~10 min. Watch: `Model::automaticallyEagerLoadRelationships()` in AppServiceProvider, `cache.serializable_classes` default, retest `route:cache` + SSR after. Do as its own session with fresh backup.
- **Hard preference:** everything the **Laravel way** (Mailables, migrations, FormRequests, artisan commands over ad-hoc tinker). Pending retrofits: reply validation → FormRequest; promote photo-content flow → `php artisan photo:content`.

- **NAS-pushed analytics LIVE (2026-07-26)** — dashboard GA/GSC widget now reads snapshots pushed by the NAS n8n stack (single Google OAuth lives on the NAS; the site's own GSC OAuth stays dead/retired from the dashboard path). Contract: `POST https://mfaruk.com/api/nas/analytics`, header `Authorization: Bearer <NAS_ANALYTICS_TOKEN from server .env>`, JSON body `{captured_at, gsc:{clicks,impressions,ctr,position,topQueries[],topPages[],clicksOverTime{}}, ga:{activeUsers,sessions,pageViews}}` — all nested keys optional. Stored in `analytics_snapshots` (90-day retention), latest row rendered. ⚠️ First row (id 1) is TEST data pushed during build — real numbers arrive with the first n8n push. n8n side still TODO: add HTTP Request node to the 9am digest workflow.

**NEXT WEEKEND PLAN (agreed 2026-07-26, target Aug 1–2):**
1. SAT: NAS backup pipeline (n8n SSH→mysqldump+tar→NAS, GFS 12m/8w, DB→R2 offsite) + first full backup + RESTORE TEST to scratch dir
2. ~~SAT: decommission /backup-panel + backup cron + stale scripts~~ **DONE 2026-08-02** — removed ahead of the restore test rather than after it, because the panel was found publicly reachable and the archives were already MD5-verified onto laptop + NAS first. Also caught and removed a second, never-configured B2 backup system. Full story in §9.
3. SUN: Laravel 12→13 upgrade (plan in §16 R&D verdict) — on top of fresh backups
4. SUN: Laravel-way retrofits — reply validation → FormRequest; photo flow → `php artisan photo:content`
5. Optional: first-camera-beyond-phone blog post / Kashmir April guide / photo SEO backfill
Weekday check: 9am digest now auto-pushes full analytics (sessions+pageViews added 07-26); views chart builds history from 07-26.

**Photo-reach plan (agreed, plain list — no checkbox tools, Mahmud hates them):**
1. ~~Blog→photo outro section~~ DONE 07-26
2. Photo SEO backfill on all older photos (bring to #42/#43 standard)
3. `php artisan photo:content` command
4. Reconnect site GSC integration (OAuth client deleted — dead since April) + Google Images baseline
5. Blog: Kashmir in April — Srinagar gardens photo guide (bundles photos 11, 43, Pahalgam bridge)
6. Blog: XF23mm f/1.4 field review with samples
7. Blog (Mahmud's idea): choosing your first real camera beyond a phone — photography category → gets the camera outro variant
8. SocialMediaService → X auto-share with confirm-before-post queue (engine exists; also fix PHP 8.4 implicit-nullable deprecations in SocialPost/ABTest)
9. Pinterest evaluation (better than Instagram for referral traffic); Instagram optional brand-only

**Traffic reality check 07-26:** 30d = 6,593 unique humans (+60% vs ~4,110 two weeks prior). Blog drives everything (CS9711 article 1,684/30d). Photos still weak (best photo 38/30d) — hence the plan above. First real reader contact arrived via CS9711 article (contact #9, Jason — replied via new admin reply feature).

---

## 16b. Previous state (2026-07-12)
- **Series** fully removed (code + DB) and deployed. **git = laptop = prod** in sync.
- **Open tracks** (see task list): NAS n8n/mcp migration → NAS-orchestrated backups (GFS retention) + NAS config audit + storage fallback; May Day contingency; backlog (photo reach/SEO, blog articles, indexing health, doc hygiene, analytics/growth, infra/commerce); HestiaCP work TBD.
- **Next dev intent** (from PROGRESS_SUMMARY): photo-upload batch + more articles.
- **Reference docs on NAS:** `AI-Dev-handoff/PHOTO_SKILL.md` (local photo-enhance pipeline — PC), `memory-snapshot/*` (blog conventions, ssh, tinker/scp), `photography-portfolio-website/{PROGRESS_SUMMARY,project-plan,articles/ARTICLE_LOG,articles/_manager}`, `Documents/mfaruk-photography-portfolio-documentation.md` (master credentials).
