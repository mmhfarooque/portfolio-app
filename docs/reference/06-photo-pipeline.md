# 06 — The Photo Pipeline

> Part 6 of 11. Previous: [`05-services-jobs-commands.md`](05-services-jobs-commands.md) · Next: [`07-frontend.md`](07-frontend.md)

`app/Services/PhotoProcessingService.php` — **1,980 lines**, the single most important file in
the application. This part explains it so you never have to read it cold.

---

## 6.1 The core idea — upload once, regenerate forever

```
       ┌────────────────────────────────────────────────────────────────┐
       │  ONE processed high-res JPEG master, uploaded ONCE             │
       │  → Cloudflare R2, bucket `photography`, key originals/<uuid>.jpg│
       │  → recorded in DB as  original_path = "r2:originals/<uuid>.jpg" │
       │  → NEVER stored on the 30 GB server disk                       │
       └───────────────────────────┬────────────────────────────────────┘
                                   │  downloaded to /tmp on demand
              ┌────────────────────┼────────────────────┐
              ▼                    ▼                    ▼
        display (AVIF)      thumbnail (WebP)     watermarked (AVIF)
        max 1920px, q≈80      400px, q80          max 1920px, q≈80
              └────────── all on the server, storage/app/public/ ────────┘
```

Everything downstream of the master is **disposable and regenerable**. Change the watermark
setting, the quality, or the max resolution, hit re-optimise, and the derivatives are rebuilt
from the R2 master. **There is never a re-upload.**

Why it is built this way: the server has a 30 GB disk that is already **79% full**. Masters
range 1.2–36.2 MB and average roughly 14 MB. Keeping them off the box is what makes the setup
viable.

**Current state (2026-08-30):** 27 of 27 photos have their R2 master. Zero missing. R2 free
tier is 10 GB, usage is **409 MB**, so there is room for roughly 500 masters at current sizes —
years away.

⚠️ **The diagram above says max 1920px, and the code default is 1920, but production is set to
1280.** `image_max_resolution` was changed to `1280` on 2026-01-04 and the reason was never
recorded. That single setting — not storage, not R2, not the pipeline — is why the largest
derivative the site publishes is 1280px wide, and why portraits come out at 853px (their
*height* hits the cap). Raising it consumes no R2 and requires no re-upload; that is exactly
what upload-once-regenerate-forever is for.

⚠️ **RAW `.RAF` files (~81 MB each) are never uploaded.** They stay on the laptop and NAS. R2
holds the processed JPEG master only.

---

## 6.2 Formats and quality

| Derivative | Format | Size | Quality | Why |
|-----------|--------|------|---------|-----|
| `display_path` | **AVIF** | max 1920px | mapped, ≈80 | Smallest files for the main view |
| `thumbnail_path` | **WebP** | 400px wide | 80 | Maximum compatibility, already tiny |
| `watermarked_path` | **AVIF** | max 1920px | mapped, ≈80 | Same as display, plus the watermark |

Defaults come from the DB, not from code:

```php
'max_dimension' => (int) Setting::get('image_max_resolution', 1920),
'quality'       => (int) Setting::get('image_quality', 92),
```

**AVIF quality is not the same number as WebP quality.** The service maps between them because
AVIF needs different values to preserve photographic gradients:

```php
$avifQuality = (int) (70 + (($webpQuality - 80) / 20) * 15);   // clamped to 65…85
// WebP 85 ≈ AVIF 75 · WebP 92 ≈ AVIF 80 · WebP 100 ≈ AVIF 85
```

So the stored setting of 92 produces AVIF ≈ 80. Do not pass a WebP number straight into an AVIF
encoder.

Resizing is orientation-aware: landscape images cap **width** at the max dimension, portrait
images cap **height**.

---

## 6.3 Upload flow

```
Admin uploads
   │
   ├─ checkForDuplicate() / checkFilesForDuplicates()   ← perceptual hash, Hamming ≤ 5
   │
   ├─ quickUpload()      → create the Photo row fast, return to the browser
   │                       status = processing
   └─ dispatch ProcessPhotoUpload (queued, tries 3, timeout 300s)
            │
            ▼
      processUpload()
            ├─ convertHeicToJpeg()      if the upload is HEIC
            ├─ extractExifData()
            ├─ extractGpsCoordinates()  → gpsToDecimal() → fractionToDecimal() → evaluateFraction()
            ├─ getCaptureDate()         → captured_at
            ├─ reverseGeocode()         → location_name
            ├─ ImageHashService         → file_hash + image_hash
            ├─ BlurHashService          → blurhash + dominant_color
            ├─ generateImageVersions()  → display / thumbnail / watermarked
            ├─ uploadToR2()             → original_path = "r2:originals/<uuid>.jpg"
            ├─ applyAIAnalysis()        if ai_enabled
            └─ status = draft, processing_stage = null
```

Progress is written to `processing_stage` as it goes, and the admin UI polls
`GET admin/photos/processing-status` to show it. On failure, `status = failed` and
`processing_error` holds the message; the **Retry** button re-dispatches the job, which is safe
because the job is idempotent (see [`05-services-jobs-commands.md`](05-services-jobs-commands.md) §5.4).

---

## 6.4 R2 integration

R2 is reached through the S3 driver (`league/flysystem-aws-s3-v3`) but is **configured at
runtime, from the database**, not from `config/filesystems.php` alone:

| Method | Role |
|--------|------|
| `isR2Enabled()` | Reads `Setting::get('r2_enabled')`; result memoised per request in `$r2EnabledCache` |
| `configureR2Disk()` | Builds the disk config on the fly from the DB settings |
| `uploadToR2($localPath, $r2Key)` | Push a master |
| `downloadFromR2($r2Key)` | Pull to a temp file for regeneration |
| `deleteFromR2($r2Key)` | Remove |
| `existsInR2($r2Key)` | Check |

Settings that drive it: `r2_enabled`, `r2_access_key_id`, `r2_secret_access_key`,
`r2_bucket` (currently `photography`), `r2_endpoint`.

⚠️ **`.env` has `R2_*` keys too, but the database values are what the pipeline uses.** Changing
`.env` alone will appear to do nothing. Admin → Settings → Cloudflare has a **Test R2
connection** button (`POST admin/settings/test-r2`) — use it rather than guessing.

---

## 6.5 Source resolution — `findSourceFile()`

When regenerating derivatives, the service hunts for a usable source in a strict order. Knowing
this order explains most odd behaviour:

1. **R2**, if `original_path` starts with `r2:` — downloads to a temp file and registers it in
   `$tempFilesToCleanup`.
2. **Local private storage**, `storage/app/private/<original_path>` — and if that misses, it
   retries the same base name against `jpg, jpeg, png, webp, avif`.
3. **Public storage fallback** — `display_path`, then `watermarked_path`, again with the
   extension sweep.

Step 3 is a quality trap: if the master is gone, the service will happily regenerate from an
already-compressed **derivative**, producing generation loss with no error. If output quality
degrades unexpectedly, verify that step 1 actually succeeded.

Temp files are cleaned by `cleanupTempFiles()`.

---

## 6.6 Watermarking

Defaults live in the service:

```php
['text' => '© Photography Portfolio', 'opacity' => 40,
 'position' => 'bottom-right', 'padding' => 20, 'fontSize' => 24]
```

Overridden at runtime by settings via `setWatermarkSettings()`. Position is computed by
`getWatermarkPosition()`; text width is estimated by `estimateTextWidth()`.

### The per-photo override, and its priority

```php
// app/Models/Photo.php
$photo->shouldApplyWatermark();
// 1. watermark_disabled = true  → NEVER watermark, no matter what
// 2. otherwise                  → Setting::get('watermark_enabled')
```

The per-photo flag **wins over the global setting**. This is deliberate and described in the
older docs as the super power. Set it in Admin → Photos → Edit → Image Optimization → Watermark,
then re-optimise to apply.

There are two watermark generators — `generateWatermarkedImage()` (WebP) and
`generateWatermarkedImageAvif()` (AVIF). The AVIF one is what the live pipeline calls.

**Two kinds of watermark exist, do not confuse them:**

1. A Lightroom/darktable stamp **baked into the R2 master** — permanent, always present, cannot
   be removed by the app.
2. The **application watermark** — a DB flag, applied at regeneration time.

Both survive a restore: the baked one is in the master, the app one is in the backed-up DB and
is reapplied on regen.

---

## 6.7 Re-optimisation

| Method | Scope |
|--------|-------|
| `reoptimizePhoto($photo, $customResolution, $customQuality)` | One photo |
| `reoptimizeAllPhotos()` | Every photo |
| `regenerateWatermark($photo)` | Watermark only |
| `reprocessPhoto($photo, $file)` | Replace the underlying image |

Routes: `POST admin/photos/{photo}/reoptimize` (single),
`POST admin/photos/reoptimize` (all), `POST admin/settings/regenerate-watermarks`.

Per-photo overrides are read through `getEffectiveMaxResolution()` and `getEffectiveQuality()`,
which fall back to the global settings when the per-photo columns are null.

⚠️ A historical bug: **bulk optimisation was made to always override individual photo custom
settings.** If a bulk run appears to wipe someone's per-photo tuning, that is intended
behaviour, not a regression.

---

## 6.8 EXIF, GPS, geocoding

`extractExifData()` stores the full EXIF blob as JSON in `photos.exif_data`. The
`formatted_exif` accessor renders it for display, and `SearchService` queries **camera make and
lens directly out of that JSON** with MySQL JSON functions.

GPS extraction is a small chain because EXIF stores coordinates as rationals:

```
extractGpsCoordinates() → gpsToDecimal($coord, $hemisphere)
                        → fractionToDecimal() → evaluateFraction()
```

`getCaptureDate()` fills `captured_at`, which is the **default sort order for the public
gallery** — so a photo with no capture date sorts oddly.

Geocoding: `reverseGeocode($lat, $lng)` turns coordinates into `location_name`;
`updateLocationName($photo)` refreshes one; `geocodeAllPhotos()` backfills. Map configuration
lives in `config/maps.php`, and the public map is Leaflet with OpenStreetMap tiles (which is why
`*.tile.openstreetmap.org` is in the CSP `connect-src`).

⚠️ Not every photo has GPS. One published photo had its coordinates set by hand because the file
carried none. `hasLocation()` is the guard; the map and the location block must both respect it.

---

## 6.9 Deletion

```php
deletePhotoFiles(Photo $photo, bool $preserveOriginal = false)
```

The `$preserveOriginal` flag exists so derivatives can be cleared without destroying the R2
master. Given the golden rule that backups are sacred and nothing self-destructs, **prefer
`$preserveOriginal = true`** unless removal of the master is the explicit, confirmed intent.

---

## 6.9b Responsive delivery variants (added 2026-08-30)

Sitting on top of the three derivatives above is a **delivery layer** that turns a public image
URL into a route rather than a file path:

```
/img/{photo:slug}/{variant}-{width}.{format}
/img/begnas-lake-pokhara-nepal/watermarked-1280.avif
```

The slug is the identity; role, width and format are delivery details, so a re-encode or a new
format adds URLs instead of invalidating ones Google already holds.

| Piece | Role |
|-------|------|
| `App\Enums\ImageVariant` | thumb / display / watermarked, each with a width ladder |
| `App\Enums\ImageFormat` | avif → webp → jpg, with the AVIF quality mapping from §6.2 |
| `App\Support\Images\PhotoImage` + `VariantSet` | build **every** public image URL |
| `App\Http\Controllers\ServePhotoImage` | generates on demand behind a `Cache::lock` |
| `App\Services\ImageVariantService` | downscales from the variant's own master |
| `resources/js/Components/ResponsiveImage.vue` | one `<picture>`, avif + webp + jpg fallback |
| `php artisan photos:variants` | idempotent warmer; runs on upload and nightly at 03:30 |

Two things to know:

- **Variants downscale from the existing 1280 master, not from the R2 original.** That is
  deliberate — it preserves the baked-in watermark and avoids re-running the watermark pipeline
  — but it means variant quality is inherited from that master, and it means the §6.5 fallback
  order applies transitively if a master goes missing (see gotcha 39).
- Because `PhotoImage` and `VariantSet` are the only place URLs are built, the image sitemap,
  the JSON-LD `contentUrl` and the page markup **cannot drift apart** any more. That split was a
  real, diagnosed problem on 2026-08-16.

---

## 6.10 Related commands

- `photos:convert-to-avif` — migrate legacy WebP derivatives to AVIF.
- `photos:generate-placeholders` — backfill blurhash/LQIP for older photos.
- `photos:variants` — build the responsive delivery variants (§6.9b). **Idempotent and
  re-runnable**, unlike the two above; a new width or format is a new enum case plus one more
  run. Scheduled daily at 03:30.

The first two are one-off backfills for photos that predate a pipeline change; neither is
scheduled.
