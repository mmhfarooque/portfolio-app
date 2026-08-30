# 11 — State and Roadmap

> Part 11 of 11. Previous: [`10-gotchas.md`](10-gotchas.md) · Back to [`00-INDEX.md`](00-INDEX.md)

Where the project actually stands as of **2026-08-03**, and what is genuinely next.

---

## 11.1 Recent work, newest first

### 2026-08-30 (late) — layout cap, variant cleanup, dimension recovery (`c4c4bf9`)

Follow-on session the same night, after reading this reference set properly.

- **`<main>` capped at `max-w-7xl` and centred** (`395d018`). The photo hero was the one element
  escaping the wrapper, rendering ~1661px from a 1280px master. Capping it made layout and
  images agree **without touching a single image** — no re-optimise, no extra storage, no
  sitemap churn. All 11 `sizes` attributes plus the enum default were corrected to end in a
  fixed pixel width past 1280 (`3bf0512`).
- **Delivery variants joined the replace step** (`52a8e2a`). They had been leaking a whole
  generation per re-optimise — 651 files / 97 MB. `deletePhotoFiles()` now calls
  `forget()`; `--prune` cleared 29 stranded files.
- **Master dimensions recovered** (`227a4de`, `c4c4bf9`). `reoptimizePhoto()` had been erasing
  them on every run; now preserved, and `photos:backfill-dimensions` restored 27/27 from R2
  (1186px–7825px on the long edge). Also fixed `ProcessPhotoUpload::failed()` passing a model
  where `LoggingService::error()` wants a `Throwable`, which meant a failed upload logged
  nothing.
- **`.env` `R2_*` keys emptied** with an explanatory comment; they were stale and misleading.
  Backup at `.env.bak-20260831-imagework`. R2 verified working after: 27 originals, 409 MB.
- ⚠️ **Two 16 MB files were NOT deleted** — see gotcha 63b. They look like reclaimable orphans
  and are the only copy of an image that is in neither the library nor R2.

### 2026-08-30 — routed image delivery and responsive variants (`660d683`)

Public photo URLs became a **route** rather than a storage path —
`/img/{photo:slug}/{variant}-{width}.{format}`. Full detail in
[`06-photo-pipeline.md`](06-photo-pipeline.md) §6.9b.

The finding that prompted it: `/photos` was server-rendering **24 `<img>` tags carrying only
`data-src`**, so crawlers that do not run JavaScript saw an imageless gallery. That is now
native lazy loading with real `src`/`srcset`. Verified live: 234 image URLs across 10 pages all
200, 26/26 sitemap entries 200, Cloudflare `HIT` on jpg and avif.

Two defects shipped and were fixed the same session — the per-photo width ladder (gotcha 44b)
and middleware name-matching (gotcha 48c). **Neither was caught by a test, because there is no
runnable test path**: phpunit is absent on the server under `composer install --no-dev`, and
there is no local runtime. Verification was HTTP-level.

⚠️ This is **infrastructure**, and §11.6 says plainly that the photo-reach fix is content, not
infrastructure. That judgement still stands. What was fixed here was a genuine defect blocking
discovery — no amount of content would have helped a listing page that served no image sources —
but it buys eligibility, not traffic. Items 2 and 4 below remain the actual levers.

### 2026-08-30 — Laravel 12 → 13 upgrade (done)

Completed. Production is **13.29.0** on PHP 8.4.10. See `docs/UPGRADE-2026-08-30.md`.

### 2026-08-16 — Google Images investigation and sitemap fixes (`fe77cb9`)

Diagnosed why photos were not reaching Google Images and corrected the split signal where the
sitemap advertised `display/*.avif` while pages exposed `watermarked/*.avif`. Also stopped
`incrementViews()` churning `lastmod`. Full detail in `MFARUK_WORKFLOW.md` §16a. **AVIF was
ruled out as a cause here and again on 2026-08-30** — Google has supported it since 2024-08-30.

### 2026-08-02 — gallery load-more (commits `c5923af`, `87d6ccf`)

Gallery index moved from conventional pagination to an Inertia v2 AJAX load-more:
`Inertia::scroll()` server-side, `<InfiniteScroll … manual>` client-side. A follow-up commit
gated the end-of-list message behind a `mounted` flag to kill an SSR flash. Both deployed and
live. Full detail in [`07-frontend.md`](07-frontend.md) §7.4.

### 2026-08-02 — backup system rebuilt on the NAS

Old system retired (commit `24aa81c`, −3,997 lines), replacement built the same evening. Five
n8n workflows, weekly ×4 plus monthly ×12, checksum-verified, with a staleness watchdog. Verified
against real files on 2026-08-03. Full detail in
[`09-backup-and-automation.md`](09-backup-and-automation.md).

### 2026-07-26 — contact reply, mail fix, blog outro, SSR fix

- **In-admin contact reply** — `POST admin/contacts/{id}/reply`, a `ContactReply` mailable that
  quotes the original, stores `reply_subject` / `reply_message`, sets replied status. Replaced a
  dead `mailto:` button. Also fixed the whole of `ContactController` calling a nonexistent
  `LoggingService::logActivity()`.
- **Site email fixed** — the Gmail app password had been revoked and *all* site mail was
  silently dead. New password set; `.env` backup at `.env.bak-20260726`.
- **Blog photo outro** — `PhotoOutro.vue` on every article.
- **SSR unstuck** — the server-side deploy script had no `mfaruk-ssr` restart, so the daemon
  served a 12 July bundle until 26 July. Also fixed the `photo.show` → `photos.show` route name
  that crashed SSR on category, gallery, and tag pages.
- **NAS-pushed analytics live** — dashboard now reads `analytics_snapshots`.

### 2026-07-12 — Series removed

The Series feature was deleted entirely, code and empty DB tables. References in old docs are
dead.

### 2026-06-07 — Inertia SSR enabled

The change that made photo and blog pages visible to crawlers at all.

---

## 11.2 What is live and load-bearing

Photos · blog · categories · galleries · tags · comments and likes · contact form with reply ·
settings · activity logging · SEO, sitemap, feeds, robots · search · the photo pipeline with R2
· analytics snapshots from the NAS · the admin panel around all of it · SSR · the queue worker ·
the NAS backup pipeline.

## 11.3 What is built but dormant

Full table in [`01-orientation.md`](01-orientation.md) §1.6. Summary: commerce and checkout,
print store, newsletter, social auto-posting, A/B testing, translations, gear pages, location
pages, client galleries and proofing.

**None of this is broken.** It is unconfigured. Do not debug it as though it were failing, and
do not remove it without asking — it is deliberate optionality.

## 11.4 What is dead and should be treated as such

| Thing | Status |
|-------|--------|
| Series feature | Removed 2026-07-12 |
| `/backup-panel`, `BackupController`, `backup:photos`, B2 integration | Removed 2026-08-02 |
| Site's own Google Search Console OAuth | **Dead since April 2026** — client deleted. Superseded by the NAS analytics push |
| `storage:link-cpanel`, `deploy/cpanel-deploy.sh` | Legacy from an older cPanel host. Server is HestiaCP |
| `posts:update-avro` | One-off content migration, already applied |

---

## 11.5 Open risks, ranked

1. **No offsite copy of the database.** RAID is not backup. The DB→R2 leg is designed and not
   built. Photo masters are already independently safe in R2, so the DB is the exposure. With
   restore now proven, this is the largest remaining gap.
2. **Server disk at 81%** — 5.4 GB free of 30 GB (2026-08-30). Includes ~94 MB of responsive variants (self-pruning since `52a8e2a`) and 31 MB in `storage/app/private/photos/originals` which is **not** reclaimable — see gotcha 63b.
3. **n8n image unpinned (`:latest`)** — a restart can jump versions underneath the backup
   workflows.
4. **NAS powered off overnight**, and n8n never back-fills a missed schedule. A DSM power
   schedule would fix it.
5. **`activity_logs` grows unbounded** — 8,875 rows, `ActivityLog::cleanup()` exists but is not
   scheduled.
6. **The `mfaruk` SSH alias is inconsistent across machines**, so `deploy.sh` works from the PC
   and not the laptop.
7. **Secrets management is designed but not built** (`install/secrets.enc`). The private repo is
   the only protection today.
8. **Registration is open** despite there being exactly one intended user.

---

## 11.6 Agreed roadmap

### Photo reach — the central strategic problem

The blog earns roughly 6,600 unique humans per 30 days; the best-performing **photo** page gets
about 38 in the same window. Photos are crawlable thanks to SSR but are not being *found*. The
fix is content and search-intent SEO plus internal linking, **not** infrastructure.

1. ~~Blog → photo outro section~~ ✅ done 2026-07-26
2. **Photo SEO backfill** on all older photos, bringing them to the standard of the two most
   recent
3. **`php artisan photo:content`** — promote the `/content` flow from ad-hoc tinker to a proper
   artisan command
4. **Reconnect Search Console** for the site and establish a Google Images baseline —
   ⚠️ *partially actioned manually.* On 2026-08-16 both sitemaps were submitted by hand under
   `farooque7@gmail.com`, and on 2026-08-30 `sitemap-images.xml` was resubmitted after every
   image URL changed (read by Google the same day, Success, 26 pages). The site's **own** GSC
   OAuth is still dead (§11.4), so this remains a manual step. A baseline has not been recorded.
   Open sub-item: the image sitemap carries **no `<lastmod>`** while `sitemap.xml` carries 437,
   so future photo edits signal no change
5. Blog: Kashmir in April — Srinagar gardens photo guide, bundling several existing photos
6. Blog: XF23mm f/1.4 field review with samples
7. Blog: choosing your first real camera beyond a phone (photography category → gets the camera
   outro variant)
8. `SocialMediaService` → X auto-share with a confirm-before-post queue. The engine exists. Also
   fix PHP 8.4 implicit-nullable deprecations in `SocialPost` and `ABTest`
9. Evaluate Pinterest (better than Instagram for referral traffic); Instagram optional,
   brand-only

### ~~Laravel 12 → 13 upgrade~~ ✅ DONE 2026-08-30 (production is 13.29.0)

Researched 2026-07-26. **Low risk, not urgent.** Laravel 12 bug fixes ended 2026-08-13; security
support runs to 2027-02-24. PHP 8.4 is fine; Inertia, Ziggy and Vite are all 13-ready; the
official upgrade is roughly 10 minutes.

Watch: `Model::automaticallyEagerLoadRelationships()` in `AppServiceProvider`, the
`cache.serializable_classes` default, and re-test `route:cache` plus SSR afterwards.

**Do it as its own session, on top of a fresh backup.** Restore is proven, so the safety net is
real — but take a fresh archive first regardless.

### Laravel-way retrofits

Standing preference: everything the idiomatic Laravel way. Outstanding:

- Contact reply validation → a **FormRequest**
- Photo content flow → an **artisan command**
- More broadly: Mailables, migrations, FormRequests and commands over ad-hoc tinker

### Infrastructure

- ~~Restore test into a scratch directory~~ ✅ **done 2026-08-02, passed** (see reference part 9 §9.3b)
- **DB → R2 offsite leg** (top priority)
- **Pin the n8n image**
- **NAS power schedule** so the Sunday backup window is never missed
- Unify the deploy SSH alias across machines
- Build `install/secrets.enc`
- Schedule `ActivityLog::cleanup()`

### Dormant but documented

**Storage overflow tier** — if R2's ~500-master free ceiling is ever approached: NAS plus MinIO
(S3-compatible, free OSS) over Tailscale; the server pulls originals to regenerate and
Cloudflare serves derivatives. Same S3 API as R2, so it is an endpoint and key swap, not an
application rewrite. Not needed for years.

**May Day security contingency** — pre-staged and off. See
[`08-operations.md`](08-operations.md) §8.8.

---

## 11.7 Content workflows

### `/content [photo-id | URL]`

The photo SEO and story workflow. **The full step list lives in `CLAUDE.md`** — that is the one
section of that file which is still authoritative. Shape:

reverse-geocode the GPS → **confirm the location with Mahmud** → generate title, slug,
first-person story, `seo_title` ≤ 70, `meta_description` ≤ 160 → assign category and gallery →
10–15 tags → publish and feature.

Voice rules, because they are violated constantly: write as **Mahmud the photographer**, first
person, conversational, specific about time of day, weather and what he was doing. Banned words
include stunning, breathtaking, extraordinary, vibrant, world-renowned, internationally
acclaimed. Banned phrases include captured with, Instagram-worthy, must-see.

⚠️ **An agent prepares; Mahmud publishes.** Never auto-publish.

### Content-workflow gotchas learned on photo #45

Recorded because each one nearly caused a silent mistake.

- **EXIF can be entirely absent from the DB record.** Photo #45 had no camera, date or GPS. It
  was recovered from the RAW on the NAS (`.RAF` plus its `.xmp` sidecar) and written back so the
  map pin and sidebar date work.
- ⚠️ **RAF filename collisions are real.** `_DSF3705.RAF` exists in **two** different shoot
  folders. **Always confirm the folder, not just the filename.**
- **The `.xmp` sidecar does not carry lens, aperture or ISO** — those live inside the RAF binary.
  `exiftool` is **not installed** on the laptop; `sips` gives only date and model.
- ⚠️ **Reverse-geocoding can be wrong, and Mahmud's correction wins.** Nominatim placed photo #45
  in Purbadhala, Netrokona; Mahmud corrected it to Gouripur, Mymensingh (Shyamganj Junction is in
  Gouripur upazila — Nominatim appears to have snapped to the nearest labelled road across a
  district boundary). **Known inconsistency:** photo #32 was shot the same day and is still
  tagged Purbadhala/Netrokona, so two neighbouring frames carry different district names. Worth
  reconciling one day.
- ⚠️ **Tag casing is inconsistent site-wide** — roughly 288 lowercase versus 80 with uppercase.
  Photo tags are mostly lowercase; Title Case ones are mostly blog tech tags. `Mymensingh` was
  deliberately **left capitalised** because a published photo already used it, and renaming would
  silently change live content. **This needs a decision, not a unilateral fix.**
- Photo #45 (`evening-walk-shyamganj-rail-line-mymensingh`) is content-complete and **still in
  DRAFT awaiting Mahmud's publish** — it is the 1 draft in the current counts. Creating it also
  added category 12, People & Everyday Life, because the site had no people or documentary
  category at all.

### Blog articles

Working area is `articles/` — on the NAS and laptop, **not in the app repo**. Flow:
`article.md` + `meta.md` + `images/` → WebP optimise (`cwebp -q 82 -resize 1920 0`) → insert via
tinker → the `article-manager` drift detector keeps live articles accurate against their source
repos.

---

## 11.8 Traffic reality

| Date | 30-day unique humans | Note |
|------|---------------------|------|
| mid-Jul 2026 | ~4,110 | Linux articles at 855 / 463 / 267 / 244 views |
| 2026-07-26 | **6,593** | +60% in two weeks. Top article 1,684 in 30 days |

Photo pages: single digits to ~38 in 30 days.

The first real reader contact arrived through a blog article and was answered using the new
admin reply feature. That is the model the roadmap is built around — the blog brings people in,
and the job is to walk them to the photographs.
