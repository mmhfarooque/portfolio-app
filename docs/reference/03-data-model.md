# 03 — Data Model

> Part 3 of 11. Previous: [`02-architecture.md`](02-architecture.md) · Next: [`04-http-surface.md`](04-http-surface.md)

27 Eloquent models, 36 tables, 42 migrations. Schema below read from the **live production
database** on 2026-08-03, not from the migration files.

---

## 3.1 Every table, with live row counts

Row counts are the fastest way to tell live features from dormant ones.

| Table | Rows | Status |
|-------|------|--------|
| `activity_logs` | **8,875** | 🔥 very active — everything is logged |
| `referral_visits` | **855** | 🔥 active |
| `photo_tag` | 395 | active pivot |
| `tags` | 377 | active |
| `post_tag` | 128 | active pivot |
| `settings` | 74 | active — app config lives here |
| `cache` | 58 | active (DB cache store) |
| `sessions` | 54 | active (DB sessions) |
| `migrations` | 42 | — |
| `photos` | 27 | active |
| `posts` | 16 | active |
| `categories` | 12 | active |
| `galleries` | 8 | active |
| `analytics_snapshots` | 8 | active — pushed by the NAS |
| `email_templates` | 7 | seeded |
| `contacts` | 6 | active |
| `comment_otp_verifications` | 2 | active |
| `failed_jobs` | 1 | stale residue (Mar 2026 mail failure) |
| `photo_comments` | 1 | barely used |
| `users` | 1 | Mahmud only |
| `ab_test_participants`, `ab_tests` | 0 | 💤 dormant |
| `blocked_emails` | 0 | 💤 |
| `cache_locks`, `job_batches`, `jobs` | 0 | idle (healthy) |
| `client_selections` | 0 | 💤 |
| `email_logs` | 0 | 💤 |
| `equipment` | 0 | 💤 |
| `locations` | 0 | 💤 |
| `newsletter_subscribers` | 0 | 💤 |
| `orders` | 0 | 💤 commerce never used |
| `password_reset_tokens` | 0 | idle |
| `photo_likes` | 0 | 💤 feature live, nobody has liked anything |
| `social_accounts`, `social_posts` | 0 | 💤 |
| `translations` | 0 | 💤 |

---

## 3.2 The core graph

```
                 User (1 row — Mahmud)
                   │ owns everything
                   ▼
   Category ◀──┬── Photo ──┬──▶ Gallery
   (shared     │    │      │
    with       │    │      └──▶ Tag  (belongsToMany via photo_tag)
    Post)      │    ├──▶ PhotoLike        (session or user)
               │    ├──▶ PhotoComment     (threaded, self-parent)
               │    │      └──▶ CommentOtpVerification
               │    ├──▶ Order            (commerce, dormant)
               │    ├──▶ ClientSelection  (proofing, dormant)
               │    └──▶ SocialPost       (dormant)
               │
               └── Post ───▶ Tag  (belongsToMany via post_tag)
```

**Critical shared-taxonomy fact:** `Category` and `Tag` are shared between **Photos and Posts**.
A category page or tag page can therefore surface either content type. `Tag` has a `type`
column used to distinguish photo tags from post tags, and both models expose separate
`photos` / `posts` and `publishedPhotos` / `publishedPosts` relations.

**Removed 2026-07-12:** the *Series* feature — code and its (empty) DB tables were dropped
entirely. If you see references to series in old docs, they are dead.

---

## 3.3 `photos` — the central table

Live schema, 41 columns:

```
id, title, seo_title, slug, description, meta_description, story
original_path, display_path, thumbnail_path, watermarked_path
exif_data(longtext JSON), latitude(10,8), longitude(11,8), location_name
width, height, file_size, mime_type
image_hash(64), file_hash(64), blurhash(50), dominant_color(7)
custom_max_resolution, custom_quality, watermark_disabled(default 0)
original_width, original_height, original_filename
user_id, category_id, gallery_id
status enum('draft','published','processing','failed') default 'draft'
processing_stage, processing_error
is_featured(default 0), views, likes_count, comments_count
captured_at, created_at, updated_at
```

Things worth knowing:

- **`status` has four values, not two.** `processing` and `failed` are pipeline states. Public
  scopes filter on `published`; the admin surfaces `processing` and `failed` separately.
- **`original_path` carries an `r2:` prefix** when the master is in Cloudflare R2, e.g.
  `r2:originals/<uuid>.jpg`. All 27 photos are currently in R2. `isOriginalInCloud()` is just a
  prefix check.
- **Two different hashes.** `file_hash` is an exact-content hash; `image_hash` is a *perceptual*
  hash used for near-duplicate detection via Hamming distance.
- `blurhash` and `dominant_color` drive placeholder rendering before the image loads.
- `likes_count` and `comments_count` are **denormalised counters**. `Photo::syncCommentsCount()`
  exists precisely because they can drift.
- `custom_max_resolution`, `custom_quality`, `watermark_disabled` are **per-photo overrides** of
  global settings — see [`06-photo-pipeline.md`](06-photo-pipeline.md).
- `original_width` / `original_height` hold the **master's** size, `width` / `height` the
  **derivative's**. Until 2026-08-30 `reoptimizePhoto()` overwrote width/height without
  preserving the master first, so the original columns were null on all 27 photos and the app
  had no record of how large its own originals were. Both are fixed and back-filled — 27/27,
  ranging 1186px to 7825px on the long edge.

### Photo model API

**Relations:** `user`, `category`, `gallery`, `tags`, `likes`, `comments`, `approvedComments`,
`topLevelApprovedComments`.

**Scopes:** `published`, `featured`, `draft`, `withLocation`, `processing`, `failed`.

**Accessors:** `display_url`, `thumbnail_url`, `watermarked_url`, `primary_url`,
`formatted_exif`, `processing_stage_text`, `processing_elapsed`, `processing_duration`.

**Behaviour:**

| Method | Does |
|--------|------|
| `shouldApplyWatermark()` | **Per-photo override wins.** If `watermark_disabled` is true → never watermark, regardless of the global setting |
| `getEffectiveMaxResolution()` / `getEffectiveQuality()` | Per-photo value, else global setting |
| `hasCustomSettings()` | Whether either override is set |
| `hasOriginal()` / `isOriginalInCloud()` / `getOriginalPathFull()` | Master file resolution |
| `hasLocation()` | Has usable GPS |
| `incrementViews()` | View counter |
| `hasBeenLikedBy()`, `incrementLikesCount()`, `decrementLikesCount()` | Likes |
| `incrementApprovedCommentsCount()`, `decrementApprovedCommentsCount()`, `syncCommentsCount()` | Comment counters |
| `getResolutionLabel()` | Human-readable resolution |
| `isProcessing()`, `hasFailed()` | Pipeline state |

---

## 3.4 `posts` — the blog (the traffic driver)

```
id, title, slug, excerpt, content(longtext — Editor.js JSON), featured_image
status enum('draft','published') default 'draft', is_featured(default 0)
user_id, category_id, seo_title, meta_description(varchar 255)
views, published_at, created_at, updated_at
```

`content` holds **Editor.js JSON**, not HTML or Markdown. Anything rendering or writing post
content must speak that format.

**Model:** relations `user`, `category`, `tags` (with timestamps). Scopes `published`, `draft`,
`featured`. Methods `incrementViews()`, `hasFeaturedImage()`, and the `reading_time` accessor.

Note the asymmetry with photos: posts have **no** `story`, no GPS, no processing states, and
`meta_description` is a `varchar(255)` here but `text` on photos.

---

## 3.5 Model-by-model reference

### Content and taxonomy

| Model | Relations | Notable methods |
|-------|-----------|-----------------|
| **Category** | `photos`, `publishedPhotos`, `posts`, `publishedPosts` | `updatePhotoCount()` — denormalised count, call after reassigning photos |
| **Gallery** | `user`, `photos` | Password protection: `setPasswordAttribute()`, `verifyPassword()`, `isPasswordProtected()`. Session access: `getAccessSessionKey()`, `hasAccess()`, `grantAccess()`. Client sharing: `generateAccessToken()`, `getShareUrl()`, `isExpired()`, `isAccessible()`, `recordView()`, `days_until_expiration`. Scopes `published`, `featured`, `clientGalleries`, `notExpired`, `expired` |
| **Tag** | `photos`, `publishedPhotos`, `posts`, `publishedPosts` | Pure pivot model. Has a `type` column |

### Engagement

| Model | Relations | Notable methods |
|-------|-----------|-----------------|
| **PhotoLike** | `photo`, `user` | `hasLiked()`. Session-based — no login required |
| **PhotoComment** | `photo`, `parent`, `replies`, `approvedReplies`, `user`, `approvedByUser` | Threaded. `approve()`, `reject()`, `markAsSpam()`. Scopes `pending`, `approved`, `topLevel`. Accessors `author_name`, `author_email`, `is_admin`, `status_color` |
| **CommentOtpVerification** | `photo`, `parent` | Email OTP gate for commenting: `generateOtp()`, `verifyOtp()`, `isExpired()`, `maxAttemptsReached()`, `incrementAttempts()`, `markAsVerified()`, `cleanupExpired()` |
| **BlockedEmail** | `blockedByUser` | `isBlocked()`, `blockEmail()`, `unblockEmail()` — spam control for comments |
| **Contact** | — | `markAsRead()`, `markAsReplied()`, `archive()`; scopes `unread`, `archived`; `status_color`, `status_label` accessors. Reply fields added 2026-07-26 |

### Commerce and clients — all dormant

| Model | Relations | Notable methods |
|-------|-----------|-----------------|
| **Order** | `photo` | `generateOrderNumber()`, `generateLicenseKey()`, `isPaid()`, `canDownload()`, `incrementDownloads()`; scopes `paid`, `pending` |
| **ClientSelection** | `photo`, `gallery` | `scopeForSession()`, `isSelected()`, `getSelectionCount()`, `toggleSelection()` |

### Marketing and experimentation — all dormant

| Model | Notable methods |
|-------|-----------------|
| **NewsletterSubscriber** | `confirm()`, `unsubscribe()`, `isConfirmed()`, `isActive()`, `getConfirmationUrl()`, `getUnsubscribeUrl()`; scopes `confirmed`, `pending`, `active` |
| **SocialAccount** | `isTokenExpired()`, `needsRefresh()`; scopes `active`, `forPlatform` |
| **SocialPost** | `markAsPublished()`, `markAsFailed()`, `getContent()`, `getImageUrl()`; scopes `pending`, `scheduled`, `published`, `failed`, `readyToPublish`, `forPlatform` |
| **ABTest** | `start()`, `pause()`, `complete()`, `getVariantNames()`, `getVariantCounts()`, `getConversionRates()`, `getTotalParticipants()`, `hasSufficientSample()`; scopes `running`, `forType` |
| **ABTestParticipant** | `markConverted()`, `addMetadata()` |
| **Translation** | Polymorphic (`translatable`). `getFor()`, `setFor()`, `allFor()`, `deleteFor()` |
| **Equipment** | `current`, `featured`, `ofType` scopes; `photos`, `photo_count`, `image_url`, `type_label` accessors |
| **Location** | `published`, `featured`, `withCoordinates` scopes; `incrementViews()`, `hasCoordinates()`, `cover_image_url`, `difficulty_label` |

### Infrastructure

| Model | Notable methods |
|-------|-----------------|
| **Setting** | `get()`, `set()`, `getGroup()`, `clearCache()` — **static, cached.** The app's config store |
| **ActivityLog** | Polymorphic `loggable`. Scopes `ofType`, `ofLevel`, `errors`, `dateRange`; `cleanup()`; colour accessors |
| **AnalyticsSnapshot** | `scopeFromNas()` — rows pushed by the NAS n8n stack |
| **ReferralVisit** | `markConverted()`, `current()`, `parseUserAgent()`, `extractDomain()`; scopes `withUtm`, `converted`, `dateRange` |
| **EmailTemplate** | `findBySlug()` (cached), `clearSlugCache()`, `renderSubject()`, `renderBody()`; scopes `active`, `byCategory` |
| **EmailLog** | `sender` relation; scopes `sent`, `failed` |
| **User** | Standard Laravel authenticatable. Exactly one row |

---

## 3.6 `settings` — the real configuration store

74 rows. **This, not `.env`, is where most runtime configuration lives.** Access is static and
cached:

```php
Setting::get('key', $default);   Setting::set('key', $value);
Setting::getGroup('seo');        Setting::clearCache();
```

Grouped roughly into: profile and CV fields (`profile_*`), contact (`contact_*`), social links
(`social_*`), skills (`skills_*`), SEO (`seo_robots_allow`, …), image pipeline (watermark,
quality, resolution), Cloudflare (`r2_*`, `turnstile_*`), AI (`ai_enabled`, `ai_provider`), and
theme (`site_theme`).

⚠️ **A setting written into the wrong group has caused a real bug before** — a quality slider
silently reverting because it was stored under the wrong group. When adding settings, match the
group the reader expects.

---

## 3.7 Migrations

42 files, `database/migrations/`. Chronology tells the build story: core photos/categories/
galleries/tags (Nov 2025) → SEO fields, contacts, posts, orders, client galleries (Dec 2025) →
likes, comments, OTP, blocked emails (22 Dec 2025) → per-photo optimisation and Cloudflare
settings (3 Jan 2026) → watermark disable (4 Jan) → email templates and logs (Feb) → post
featured flag (May) → contact reply fields and `analytics_snapshots` (26 Jul 2026).

There is also a removal migration — `remove_before_image_columns_from_photos_table` — evidence
that reverting a feature by migration is the accepted pattern here. Do the same rather than
leaving dead columns.

Deploys run `php artisan migrate --force` automatically. Any migration you commit **will** run
on the next deploy. Make them safe.
