# MFARUK_WORKFLOW.md — the mfaruk.com operating brain

> **Read this FIRST.** Single source of truth for developing, deploying, and operating
> **mfaruk.com** — written for any AI / agent / LLM (or human) on any machine, fresh or old.
> Personal project of **Mahmud Farooque**. **Nothing here touches Jezweb** (see §0 boundary).
> This file lives in git, so a `git clone` delivers the whole brain to every machine.
> _Living doc — edit, update, refine freely. Last updated: 2026-07-26._

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
- **Retention (GFS):** **12 monthly** (rolling year — always exactly 12, oldest pruned on new) **+ 8 weekly** (rolling ~2 months / 8 weeks, older pruned). Max **~20 backups**, bounded forever. n8n owns the rotation.
- **⚠️ STATE 2026-07-12:** the OLD server backups have been **DISABLED since 15 Mar** (toggled off, never re-enabled); the backup password is stale (reset ~May, script still hard-codes old one); last real backup **8 Mar**. **Interim protection = manual DB dumps** (`pre-series-removal-20260712-071454.sql.gz` exists). Full fix waits on n8n-on-NAS.

## 10. Content workflows
- **`/content [photo-id | URL]`** (see `CLAUDE.md` for the full step list): reverse-geocode GPS → **confirm location with Mahmud** → generate title / slug / first-person story (no travel-brochure fluff) / `seo_title` ≤70 / `meta_description` ≤160 / assign category + gallery / 10–15 tags → publish + feature.
- **Blog articles:** working area `articles/` (on NAS/local, **not** in the app repo) — `article.md` + `meta.md` + `images/` → WebP-optimize (`cwebp -q 82 -resize 1920 0`) → `tinker` insert → the **`article-manager`** drift-detector keeps live articles accurate vs their repos.

## 11. Analytics / reach — own infra only
- **`mfaruk-mcp`** (personal MCP; "no connection to Jezweb"): SSHes to the server, parses the Apache access log — **human reads vs bot crawls**. Tools: `status`, `summary`, `read_traffic`, `crawl_activity` (windowed via `since`).
- **Personal GSC + Bing** under `farooque7@gmail.com` — submit URLs via Mahmud's own logins. **Claude's GSC/Bing/Cloudflare MCPs are the Jezweb account — do NOT use them here.**
- **Reach reality (30 d, Jul 2026):** ~4,110 unique humans. **Blog drives everything** (Linux articles: 855 / 463 / 267 / 244 views); individual **photo pages get single-digit-to-~34** views. Photos are crawlable (SSR) but not yet *found* — the fix is content + search-intent SEO + blog→photo internal links, not infra.

## 12. Security — "May Day" contingency (dormant, flip-a-switch)
Pre-staged, **OFF** until an attack (no always-on latency): a `maydayctl` script (enable Cloudflare **Under Attack Mode** + pre-staged rate-limits + WAF + Bot Fight; `normal` reverts), **origin cloaking** (proxied A record; optional firewall origin 80/443 to Cloudflare IPs — SSH:22 stays direct so mfaruk-mcp is unaffected), and a Cloudflare **fallback holding page**. Needs a **personal, scoped** Cloudflare API token (mfaruk.com zone only). Also **decommission the public `/backup-panel`** (attack surface). Cloudflare already absorbs volumetric L3/L4 DDoS by default.

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

## 16. Current state / where we left off (2026-07-26)

**Session 2026-07-26 (laptop) — everything below is deployed and in git; PC picks up with a plain `git pull`:**
- **Contact reply feature LIVE** — in-admin reply form (`POST admin/contacts/{id}/reply`): ContactReply mailable (markdown, quotes original), stores `reply_subject`/`reply_message` on contacts, sets replied status. Replaced the dead `mailto:` button. Also fixed the whole ContactController calling nonexistent `LoggingService::logActivity()` (latent 500s) → real static API `LoggingService::activity()/error()`.
- **Site email FIXED** — Gmail app password had been revoked (all site mail silently dead: contact notifications, OTPs). New app password *mfaruk.com mailer* set in server `.env` `MAIL_PASSWORD` (2026-07-26, backup at `.env.bak-20260726`), verified sending. If mail dies again: check Google app passwords under farooque7@gmail.com first.
- **Blog photo outro LIVE** — `Components/Blog/PhotoOutro.vue` on every article: 3 random featured photos + browse-all link. Copy variants: dev articles = *When I'm not coding*; photography-category articles = *More frames from my camera* (detected via category name/slug containing photo/camera). Props from `BlogController@show` (`outroPhotos`, `isPhotographyPost`).
- **SSR was STALE Jul 12–26** — server-side `/home/mfaruk/deploy.sh` lacked the `mfaruk-ssr` restart (see §13). Patched; every deploy now restarts SSR. Also fixed nonexistent `photo.show` route name (→ `photos.show`) that crashed SSR on all category/gallery/tag pages — crawlers had been getting empty fallback HTML there.
- **Photos:** #42 rusty bicycle (Swiss Sheep Farm, Pattaya) PUBLISHED — created category *Still Life & Details*; #43 fountain channel (JN Memorial Botanical Garden, Srinagar, coords set manually — no GPS in file) content-complete, **DRAFT, awaiting Mahmud's publish**.
- **Laravel 13 R&D verdict (researched 2026-07-26):** upgrade is LOW RISK, not urgent. L12 bug fixes end 2026-08-13, security until 2027-02-24. PHP 8.4 OK; Inertia/Ziggy/Vite all 13-ready; official upgrade ~10 min. Watch: `Model::automaticallyEagerLoadRelationships()` in AppServiceProvider, `cache.serializable_classes` default, retest `route:cache` + SSR after. Do as its own session with fresh backup.
- **Hard preference:** everything the **Laravel way** (Mailables, migrations, FormRequests, artisan commands over ad-hoc tinker). Pending retrofits: reply validation → FormRequest; promote photo-content flow → `php artisan photo:content`.

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
