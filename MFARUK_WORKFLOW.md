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
  pay-per-use tier has NO free posting allowance** (auth passed; billing blocked).
  **X leg RETIRED same day by Mahmud's decision — he will never pay X to post.** Account row
  set `is_active=false` (credentials kept, harmless), failed draft deleted, and the observers'
  twitter fallback removed (`948eb8f`): drafts are now created ONLY for active platforms.
  Re-enabling X someday = buy credits + flip `is_active` — everything else still works.
  **Pinterest app SUBMITTED 2026-08-16: mfaruk social queue, App id 1601547 — TRIAL ACCESS
  PENDING** (Pinterest reviews the connect request; hours-to-days). When approved: Manage →
  grab App secret, add redirect URI `https://mfaruk.com/`, then OAuth code flow
  (`boards:read`,`pins:read`,`pins:write`) → tokens + Nature board id into SocialAccount
  (board id in `platform_user_id`). Trial pins are INVISIBLE to the public — use for the
  end-to-end proof, then apply for Standard (free, needs a short demo video of the queue
  publishing a pin). A `/privacy` page was built for the form (`8765951`, footer-linked).
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
- **✅ LARAVEL 13 — DONE 2026-08-30. LIVE IS 13.29.0.** Everything from here to the end of the
  Laravel 13 material is KEPT AS HISTORY, not as pending work. Do not re-plan it.
  Shipped in five phases (`7ab8c67` `84ca229` `94a160e` `ad558e4` `59f6a1f`), merged and deployed;
  working record in `docs/UPGRADE-2026-08-30.md`, deploy script `deploy/upgrade-l13.sh`.
  Outcome: **composer advisories 38 to 0, npm vulnerabilities 12 to 0**, Intervention Image on v4,
  53/53 live URLs 200, SSR verified, watermark output proven pixel-equivalent to v3.
  **Where the audit below was wrong:** it counted 2 advisories when there were 38 (it missed a
  CRITICAL code injection in `mtdowling/jmespath.php`); it built a fragile lock round-trip around
  *there is no local composer* — there is one now at `~/.local/bin/composer` with
  `config.platform.php` pinned to 8.4.10, so locks are built locally and the server only pulls; and
  it called `validateCsrfTokens()` a HIGH break when L13 still has it delegating to
  `preventRequestForgery()`. It also under-scoped Intervention v4 to `read()` plus encoders — the
  renames that actually bit were `pickColor()`, `ColorChannel::toInt()` and `FontFactory::valign()`,
  none findable by static grep.
  **Two pre-existing bugs the upgrade exposed and that are now fixed:** `/locations` had never
  worked (`withCount('photos')` against an accessor, not a relationship — `116fd17`), and
  `admin.media.upload` had never existed so Editor.js image upload on About was dead (`059fd93`).
  Both were invisible until Phase 1 restored the file logging that died 2026-08-02.

- **Original plan, 2026-08-16, kept for the reasoning only — the work is DONE:**
  1. **SECURITY: 12.54.1 carries an unpatched HIGH advisory.** PKSA-3r5d-mb8f-1qw9, CRLF
     injection in the default **email validation rule** (fixed 12.60.0) — hits the contact form
     and comment-OTP paths. Plus PKSA-m5cs-t1y6-qpcs, signed-URL path confusion (fixed 12.61.1).
     Latest 12.x is **12.66.0**. **Doing nothing is NOT the safe option; patching 12.x is the
     urgent act, and it is much smaller than the major upgrade.**
  2. **BLOCKER for current L13: Intervention Image collision.** Verified from source —
     **L13.25.0 registers a first-party `image` container alias** (`Application.php:1668` →
     `Illuminate\Image\ImageManager`), and installed `intervention/image-laravel` **1.5.7 binds
     the same `image` key**. Intervention's own 4.1.0 notes call it a Laravel-13.20 naming
     conflict. The fix exists ONLY in image-laravel **4.x**, which requires
     **intervention/image ^4** (we run **3.11.7**) — i.e. a major image-library migration
     through `PhotoProcessingService`, `BlurHashService` + 4 controllers. **That is the photo
     pipeline — the product core.** L13 ≤ 13.19 predates the collision.
  3. **PHP is NOT a blocker (measured):** vhost uses `php8.4-fpm-mfaruk.com.sock`; CLI 8.4.10.
  4. **The DB backup is the WRONG rollback tool here.** composer never touches the DB, and
     restoring the 04:00 dump would DELETE today's X OAuth row + the oauth1 migration. Real
     rollback = git + a **`vendor/` snapshot** (the composer cache does NOT hold 12.54.1, so a
     cold rollback means re-downloading ~130 packages, 3–8 min).
  **PRE-FLIGHT, must happen before any framework work:**
  `cp -a vendor vendor.bak-12.54.1` (302 MB, 6.0 GB free — makes rollback an instant offline
  `mv`) · **stop the queue worker** (live: `queue:work database`, PID seen 3961264) and **pause
  the every-minute `schedule:run` cron** (else a half-installed app emails farooque7 every 60s)
  · `optimize:clear` **as root** (`bootstrap/cache/routes-v7.php` is the L12 format and is
  root-owned inside a mfaruk:www-data dir) · note prod runs `composer` itself — **there is no
  local composer**, and `composer.lock` is git-tracked, so a bumped `composer.json` + stale lock
  = `composer install` refuses. Decide where the lock is regenerated BEFORE starting.
  **PRE-EXISTING BUGS found by the audit (fix these regardless of any upgrade):**
  - ~~`gallery.password` broken route~~ **RESOLVED 2026-08-16 by REMOVING the feature** (`c04f897`).
    Mahmud never asked for password-protected galleries; measured on prod first — 0 galleries had
    a password, 0 were client galleries, 0 access tokens, 0 client_selections rows. Deleted
    `ClientGalleryController` + 4 `Public/ClientGallery/` Vue pages, the `gallery.unlock` route +
    6 `client-gallery.*` routes, the password gate/`verifyGalleryPassword`, and all password +
    client fields from the Gallery model, admin controller and admin Create/Edit/Index/Show.
    **Deliberately NOT touched: `ClientProofingController` + the `client_selections` table**
    (separate visitor photo-selection feature — do NOT drop that table), and the
    SocialAccount/GSC `access_token` columns (unrelated). **DB columns left in place** (11 on
    `galleries`) — dropping them is an unapproved Stage B. Verified live: SSR active, gallery
    page renders under Googlebot UA, `/client-gallery/*` 404s, 8 galleries + 27 photos intact,
    221 routes, `client.selections` still present.
  - `Logs/Index.vue:155` links `admin.logs.show` (actual:
    `admin.logs.details`) **inside a `<Link>` → crashes SSR on /admin/logs**.
    `About/EditorJs.vue:51` uses `admin.media.upload` (never defined) → Editor.js image upload
    broken. Also `admin.orders.add-note`→`admin.orders.note`, `admin.orders.update-status`→
    `admin.orders.status`, `admin.social.disconnect` (undefined).
  - **`bootstrap/app.php:61` returns false from the exception reporter, which STOPS Laravel's
    default log stack** — `storage/logs/laravel.log` has not been written since 2026-08-02.
    DB ActivityLog still works (36 entries today). Change to `return true` so we have file logs
    during any upgrade. (Line 45's `return false` for the skip-list is correct.)
  - `app/Services/PaymentService.php:302` — last implicit-nullable (`string $state = null`).
  - `deploy/cpanel-deploy.sh:69-73` — PHP gate still `>= 8.1`.
  - `@tailwindcss/vite` 4.2.1 is installed but **UNUSED** (7.8 MB); build is Tailwind v3 via
    postcss. Dropping it is a zero-risk win. A real v4 migration is ~700 class edits
    (237 `shadow-sm`, 288 bare `border`, 144 `space-x/y`) — **NO-GO as a ride-along.**
  **DEPLOY-CHAIN GAPS (measured; these are why this is not a 25-minute job):**
  - **The server's GitHub deploy key is READ-ONLY** (`git push --dry-run` → marked as read only).
    Combined with no local composer, the lock round-trip is: edit `composer.json` + run
    `COMPOSER_ALLOW_SUPERUSER=1 composer update --no-scripts` **on the server** → copy
    `composer.json`+`composer.lock` **down** to the laptop → commit/push from laptop → deploy.
    ⚠️ The regenerated lock sits UNCOMMITTED in the server tree and `git reset --hard origin/main`
    (deploy step 1) **destroys it** — pull it off the server first.
  - **Supervisor queue worker `portfolio-worker`** runs `queue:work database --tries=3
    --max-time=3600`; **neither deploy script runs `queue:restart`**, and `stopwaitsecs=3600`
    means a naive stop can hang for an hour. Swapping `vendor/` under it = fatals burning tries.
  - **Cron `* * * * * schedule:run` with `MAILTO=farooque7@gmail.com`** — fires mid-deploy and
    emails on every failure.
  - **No `artisan down` anywhere** → between `git reset --hard` and the end of `composer install`
    visitors get hard 500s (new code, old vendor). Minutes on a major bump.
  - `systemctl restart mfaruk-ssr 2>/dev/null || true` **swallows SSR failure and the deploy
    reports success**; unit is `Restart=always/RestartSec=2`, so a fatal SSR restart-loops
    silently. Drop the `|| true` and assert `systemctl is-active`.
  - `composer install` runs **without `COMPOSER_ALLOW_SUPERUSER=1`**, so `package:discover` is
    already degraded on every deploy today.
  - **Disk 79% (6.0 GB free), ZERO swap.** `/backup` 11 GB, `/var/log/journal` 3.0 GB unbounded.
  - **HestiaCP's OWN backup has failed for 28 days** (Error 8 = needs 2× user disk: 6688 MB
    required vs 6144 MB free; last good archive 2026-07-19). ⚠️ Do NOT confuse this with the
    NAS n8n pipeline, which is **healthy and verified** (16 Aug 04:00 archive, sha256 matches
    sidecar, gzip clean, complete 41.8 MB dump with completion marker, real row counts match).
    The NAS one is the backup that matters; Hestia's is redundant but its failure is a symptom
    of the disk pressure.
  - ⚠️ **NEVER run `git clean` on the server** — `public/robots.txt` (live, 475 b) and
    `public/app-path.php` (required to boot) are untracked.
  - `/usr/bin/php` in the SSR unit is unversioned on a box with 13 PHP versions installed.
  - ⚠️ MANIFEST row counts inside every backup are `information_schema` ESTIMATES and read
    `users: 0` — a false catastrophe signal. Verify with real `COUNT(*)`, never the manifest.
  **RECOMMENDED PHASING (not one job):** Phase 1 = pre-flight + pre-existing bug fixes.
  Phase 2 = security patch **within 12.x** (12.54.1 → 12.66.0) — clears the HIGH advisory, no
  collision, same major. Phase 3 = Intervention Image v3→v4 as its own project. Phase 4 = then
  Laravel 13 latest. L12 has security support to **2027-02-24**, so there is runway.
  **Post-upgrade verification that actually matters** (SSR fails SILENTLY — `HttpGateway`
  swallows every exception and falls back to client render, exactly the Jul 12–26 outage):
  `systemctl is-active mfaruk-ssr` + `curl 127.0.0.1:13714/health`; Googlebot-UA fetch of a
  photo page asserting exactly ONE `<title>`, ONE ld+json, ZERO `innerHTML=`; Ziggy route count
  via tinker (~220, non-empty `uri`); category/gallery/tag pages under Googlebot UA; admin login
  (proves session+CSRF); Editor.js; contact form + comment OTP; RSS/Atom + an email template.
- **Laravel 13 UPGRADE PLAN — first pass 2026-08-16 (kept for the package-level detail; see the
  revision above for what changed):**
  Live: **L12.54.1 / PHP 8.4.10 / Node 22.21.0**, database cache+session+queue drivers.
  Target: **L13.25.0** (13.0 shipped 2026-03-17; min PHP ^8.3 — 8.4.10 qualifies).
  **`composer why-not laravel/framework 13.25.0` on prod says the ONLY blocker is
  `laravel/tinker` 2.11.1 → needs `^3.0`.** Inertia 2.0.22, Ziggy 2.6.2, Intervention 1.5.7,
  Breeze 2.3, google/apiclient, flysystem-s3 are all clear; the symfony polyfills auto-bump.
  Breaking changes that ACTUALLY touch this codebase (audited, not assumed):
  1. **CSRF (HIGH)** — `bootstrap/app.php:21` calls `validateCsrfTokens(except:
     ['stripe/webhook'])`; rename to `preventRequestForgery(...)`. New `Sec-Fetch-Site` origin
     check ships with it → retest contact form, comment OTP, admin login. `/api/nas/analytics`
     is an **api** route, so the web-group middleware does not apply — NAS push is unaffected.
  2. **Cache `serializable_classes` (MEDIUM)** — `FeedController` caches an Eloquent
     **Collection of Post (with user+category eager-loaded)** and `EmailTemplate::findBySlug`
     caches a **model**. The L13 skeleton sets `serializable_classes => false`; syncing that
     blindly BREAKS RSS/Atom and email templates. Either leave the key out, or allow-list
     `Post`, `User`, `Category`, `EmailTemplate`.
  3. **Session `serialization` php→json (LOW)** — invalidates active sessions (one user).
     Recommended: adopt json, accept one re-login.
  4. **Cache prefix rename — DOES NOT APPLY**: `config/cache.php:115` already uses the
     hyphenated L13-style default.
  5. Not applicable (verified absent): `upsert`, JobAttempted/QueueBusy listeners, custom cache
     stores/contracts, Bootstrap pagination, global `array_first`/`array_last`. Model `boot`
     methods are event hooks only — no nested instantiation.
  Also: phpunit `^11.5.3` → `^12`. Separate (non-Laravel) finding: **`@tailwindcss/vite` 4.2.1
  is installed but UNUSED** — the build is Tailwind **v3** (`@tailwind` directives +
  postcss.config.js); either drop the dead dep or do a deliberate v4 migration.
- **Laravel 13 R&D verdict (researched 2026-07-26, now superseded):** upgrade is LOW RISK, not urgent. L12 bug fixes end 2026-08-13, security until 2027-02-24. PHP 8.4 OK; Inertia/Ziggy/Vite all 13-ready; official upgrade ~10 min. Watch: `Model::automaticallyEagerLoadRelationships()` in AppServiceProvider, `cache.serializable_classes` default, retest `route:cache` + SSR after. Do as its own session with fresh backup.
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
