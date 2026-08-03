# 05 — Services, Jobs, Commands

> Part 5 of 11. Previous: [`04-http-surface.md`](04-http-surface.md) · Next: [`06-photo-pipeline.md`](06-photo-pipeline.md)

**Business logic lives in `app/Services/`, not in controllers.** 17 services, 1 job, 5 artisan
commands. `PhotoProcessingService` is large enough to get its own part — see
[`06-photo-pipeline.md`](06-photo-pipeline.md).

---

## 5.1 Services at a glance

| Service | Lines | Live? | One-line purpose |
|---------|------:|:-----:|------------------|
| `PhotoProcessingService` | 1980 | ✅ | The image pipeline. Upload, derivatives, watermark, R2, EXIF, geocode |
| `AIImageService` | 713 | ✅ | AI image analysis and content generation (provider: Google) |
| `SocialMediaService` | 429 | 💤 | Compose, schedule, publish social posts |
| `PaymentService` | 321 | 💤 | Stripe payment intents, webhooks, shipping, tax |
| `LoggingService` | 311 | ✅ | **Static** activity/error logging into `activity_logs` |
| `PrintService` | 268 | 💤 | Printful catalogue, variants, pricing, mockups |
| `NewsletterService` | 260 | 💤 | Double opt-in subscribe/confirm/unsubscribe |
| `ABTestService` | 248 | 💤 | Variant assignment, cookies, conversions, significance |
| `GoogleSearchConsoleService` | 246 | ⚠️ dead OAuth | GSC OAuth and dashboard data |
| `ImageHashService` | 209 | ✅ | Exact + perceptual hashing, duplicate detection |
| `LightroomSyncService` | 207 | ⚠️ | Parse XMP sidecars, apply to photos |
| `ThemeService` | 197 | ✅ | Theme registry, CSS variables, dark/light |
| `TranslationService` | 196 | 💤 | Locales, per-model translations |
| `SeoAuditService` | 192 | ✅ | Score photos, posts, and the site on SEO |
| `SearchService` | 174 | ✅ | Faceted photo search incl. EXIF filters |
| `BlurHashService` | 97 | ✅ | LQIP placeholders and dominant colour |
| `InvoiceService` | 79 | 💤 | Order invoice HTML/PDF |

---

## 5.2 The load-bearing services

### `LoggingService` — static, used everywhere

The application's own logging layer, writing rows into `activity_logs` (**8,875 rows** — the
busiest table in the database).

```php
LoggingService::activity($action, $message, $model, $context);
LoggingService::info(…);   warning(…);   debug(…);
LoggingService::error($action, $message, Throwable $e);
LoggingService::critical(…);
LoggingService::startTiming();       // then elapsed ms is attached automatically
```

Convenience wrappers: `photoUploaded`, `photoUploadFailed`, `userLogin`, `userLogout`,
`settingsUpdated`, `modelCreated`, `modelUpdated`, `modelDeleted`.

**It is wired into the global exception handler** in `bootstrap/app.php`: every unhandled
exception is reported through `LoggingService::error()`, except authentication, CSRF token
mismatch, 404, and 405 — those are skipped deliberately to avoid drowning the log. If logging
itself throws, the handler falls back to `error_log()` so the original exception still surfaces.

⚠️ **The API is static.** A previous bug had `ContactController` calling a nonexistent instance
method `LoggingService::logActivity()`, causing latent 500s. Use `activity()` and `error()`.

⚠️ `activity_logs` grows without bound. `ActivityLog::cleanup()` exists but is **not
scheduled**. At 8,875 rows it is fine; keep an eye on it.

### `SearchService`

Faceted search over **published photos only**, returning a paginator.

- Free text across `title`, `description`, `story`, `location_name`.
- Filters: `category`, `tag`, and — notably — **`camera` and `lens`, extracted live from the
  EXIF JSON** using `JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Make'))`. That is a MySQL JSON
  function, so this search is not portable to SQLite.
- `getFilterOptions()` builds the facet lists; `getSuggestions()` powers typeahead.

### `SeoAuditService`

`auditPhoto()`, `auditPost()`, `auditSite()` — scores content against the SEO rules
(`seo_title` ≤ 70 characters, `meta_description` ≤ 160, presence of story, tags, alt text and so
on). Surfaced at `/admin/seo`. See `docs/CONTENT_GUIDELINES.md` for the canonical limits.

### `ThemeService`

Registry in `config/themes.php`. `getCurrentTheme()`, `getThemes()`, `getCssVariables()`,
`isDarkTheme()`, `setTheme()`. Its output is injected into **every Inertia response** as the
shared `theme` prop (`name`, `colors`, `styles`, `isDark`). Live theme is `dark`.

### `BlurHashService` and `ImageHashService`

`BlurHashService` produces the `blurhash` and `dominant_color` values used for placeholder
rendering — `generatePlaceholder()`, `getPlaceholderDataUri()`, `getDominantColorCss()`.

`ImageHashService` does duplicate protection: `generateHashes()`, `generateFileHash()` (exact),
`generatePerceptualHash()` (near-duplicate), `hammingDistance()`, `findDuplicate()`,
`findDuplicates()`, `backfillHashes()`. The default duplicate threshold is a Hamming distance of
**5**.

### `AIImageService`

Provider-based (`ai_provider` = `google`, i.e. Gemini). `isEnabled()`, `isConfigured()`,
`getProvider()`, `validateApiKey()`, `analyzeImage()`, `generateForPhoto()`, `generateText()`.
Called from the photo pipeline via `applyAIAnalysis()` / `generateAIContent()`. The admin
Settings page can validate the API key without saving.

### `GoogleSearchConsoleService` — the caveat

Full OAuth implementation: `getClient()`, `getAuthUrl()`, `handleCallback()`, `disconnect()`,
`isConnected()`, `getSiteUrl()`, `getDashboardData()`, with routes under
`/admin/settings/google/*`.

⚠️ **The OAuth client was deleted; this path has been dead since April 2026.** The dashboard now
reads Google data from `analytics_snapshots` pushed by the NAS instead. Do not debug this
service expecting it to work — reconnecting it is a known open item, not a bug.

---

## 5.3 Dormant services — what they would do

Documented so you recognise them, not so you use them.

- **`PaymentService`** — Stripe. `createPaymentIntent()`, `retrievePaymentIntent()`,
  `handleWebhook()`, `calculateShipping()`, `calculateTax()`, `isConfigured()`, `getPublicKey()`.
- **`PrintService`** — Printful. `getProducts()`, `getProductVariants()`, `getPrice()`,
  `createMockup()`, `getStoreInfo()`.
- **`InvoiceService`** — `generateHtml()`, `generatePdf()`, `generateInvoiceNumber()`,
  `getDownloadUrl()`, `isPdfAvailable()`.
- **`NewsletterService`** — `subscribe()`, `confirm()`, `unsubscribe()`, `getStats()`.
- **`SocialMediaService`** — `createPhotoPost()`, `createBlogPost()`, `publish()`,
  `processScheduledPosts()`, `updateEngagement()`, `getAvailablePlatforms()`. Note
  `processScheduledPosts()` is **not** on the scheduler, so nothing auto-publishes.
- **`ABTestService`** — `getVisitorId()`, `setVisitorCookie()`, `getVariant()`,
  `getActiveTheme()`, `recordConversion()`, `calculateStatisticalSignificance()`,
  `getTestResults()`, `createTest()`.
- **`TranslationService`** — `getLocales()`, `setLocale()`, `getTranslation()`,
  `setTranslations()`, `detectLocale()`, `getCompletionPercentage()`.
- **`LightroomSyncService`** — `parseXmp()`, `findPhotoByFilename()`, `applyToPhoto()`,
  `processXmpFiles()`. Reads Lightroom/darktable XMP sidecars and applies metadata to matching
  photos. Built, occasionally used, no automation.

**Twitter versus X:** user-facing labels say X; the `twitter:*` meta tags, the DB platform key
`twitter`, the settings keys, and the `x-twitter` icon name are deliberately unchanged because
those are still the correct standards. Do not rename them.

---

## 5.4 The one queued job

### `ProcessPhotoUpload`

```php
public int $tries = 3;
public int $timeout = 300;   // 5 minutes
__construct(public Photo $photo, public string $tempFilePath, public string $originalFilename)
handle(PhotoProcessingService $photoService)
```

Dispatched on photo upload so the HTTP request returns immediately while derivative generation,
EXIF extraction, hashing and R2 upload happen in the background.

**Idempotency built in:** on entry it calls `$this->photo->refresh()` and, if `display_path` and
`thumbnail_path` are already set from a previous partial attempt, it simply marks the photo
`draft`, clears `processing_stage` / `processing_error`, cleans up the temp file, and returns.
That is what makes the admin **Retry** button safe.

⚠️ **This job is why the queue worker matters.** Supervisor runs exactly one
`portfolio-worker` process. If it stops, uploads silently never finish — the photo sits in
`processing` with no error. Check `supervisorctl status` first when uploads hang.

---

## 5.5 Artisan commands

Only five. Everything else is done through the admin UI or tinker.

| Command | Purpose |
|---------|---------|
| `stats:weekly {--email=}` | Weekly stats digest email to the admin. **The only scheduled task** — Mondays 09:00 via `routes/console.php` |
| `photos:convert-to-avif` | Convert existing WebP derivatives to AVIF for better compression |
| `photos:generate-placeholders` | Backfill blur placeholders (LQIP) for existing photos |
| `posts:update-avro {--dry-run}` | One-off content migration for a specific blog post (iBus Avro / KDE Plasma). Historical |
| `storage:link-cpanel {public_path?}` | Storage symlink for a cPanel split install. **Legacy** — the server is HestiaCP, not cPanel |

Two of these are effectively archaeology: `posts:update-avro` is a single-post content fix, and
`storage:link-cpanel` belongs to an older hosting arrangement (as does
`deploy/cpanel-deploy.sh`).

**Known intended addition:** `php artisan photo:content` — promoting the `/content` photo-SEO
flow from an ad-hoc tinker workflow into a proper command, per the everything-the-Laravel-way
preference. Not built yet.

---

## 5.6 Where to put new logic

Follow the existing grain:

1. **Controller** — validate (ideally a FormRequest), call a service, return
   `Inertia::render(…)` or a redirect. Keep it thin.
2. **Service** — the actual work. Inject it; do not instantiate inside controllers.
3. **Model** — relations, scopes, accessors, and small domain predicates such as
   `shouldApplyWatermark()`.
4. **Job** — anything slow enough to block a request. Remember there is exactly one worker
   process.
5. **Command** — anything a human or the scheduler should be able to invoke directly. Preferred
   over ad-hoc tinker scripts.

Known outstanding retrofits toward this grain: contact-reply validation should move to a
FormRequest, and the photo content flow should become an artisan command.
