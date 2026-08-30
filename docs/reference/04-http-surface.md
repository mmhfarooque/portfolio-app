# 04 — HTTP Surface

> Part 4 of 11. Previous: [`03-data-model.md`](03-data-model.md) · Next: [`05-services-jobs-commands.md`](05-services-jobs-commands.md)

52 controllers across **five** route files. Every route below is transcribed from source.

| File | Contents | Middleware |
|------|----------|------------|
| `routes/web.php` | Everything public + the whole admin panel | full `web` group |
| `routes/auth.php` | Laravel Breeze scaffolding | full `web` group |
| `routes/api.php` | **One** endpoint — the NAS analytics push | `api` |
| `routes/images.php` | **One** endpoint — responsive image delivery | ⚠️ `SubstituteBindings` **only** |
| `routes/console.php` | `inspire`, the weekly stats schedule, nightly `photos:variants` | — |

Health endpoint: **`/up`** (registered in `bootstrap/app.php`).

⚠️ **`routes/images.php` is registered from `bootstrap/app.php` via `withRouting(then: …)`, not
through the `web` group, and that is load-bearing.** See §4.1b and gotchas 48b / 48c.

---

## 4.1 Public routes

### Home and gallery

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/` | `FrontPageController@index` | `home` |
| GET | `/photos` | `GalleryController@index` | `photos.index` |
| GET | `/photos/map` | `GalleryController@map` | `photos.map` |
| GET | `/photo/{photo:slug}` | `GalleryController@show` | `photos.show` |
| GET | `/category/{category:slug}` | `GalleryController@category` | `category.show` |
| GET | `/gallery/{gallery:slug}` | `GalleryController@gallery` | `gallery.show` |
| POST | `/gallery/{gallery:slug}/unlock` | `GalleryController@verifyGalleryPassword` | `gallery.unlock` |
| GET | `/tag/{tag:slug}` | `GalleryController@tag` | `tag.show` |

All route-model binding is **by slug**, not id.

### Image delivery (§4.1b)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/img/{photo:slug}/{variant}-{width}.{format}` | `ServePhotoImage` (invokable) | `photo.image` |

Constraints come from the enums: `variant` ∈ `thumb|display|watermarked`, `width` numeric,
`format` ∈ `avif|webp|jpg`. Published photos only — there is no session on this route, so there
is no user to authorise against; admin previews read from `/storage` instead.

Width is allowlisted against the **per-photo** ladder, never the raw enum (gotcha 44b). Old
`/storage/photos/...` URLs still resolve and were deliberately not redirected, so anything
Google already indexed keeps working.

⚠️ The route name is **`photos.show`** (plural). A nonexistent `photo.show` was once used and
**crashed SSR** on every category, gallery, and tag page — crawlers got empty fallback HTML for
two weeks. Never guess route names; check this table.

`GalleryController@index` paginates 24 per page ordered by `captured_at` descending, and wraps
the paginator in **`Inertia::scroll()`** for the AJAX load-more. Category, gallery, and tag
pages also paginate 24 but use conventional pagination. `GalleryController` also has a `home()`
method rendering `Public/Home`, which is redundant with `FrontPageController@index` — the live
`/` route uses **`FrontPageController`**.

### Photo interaction — prefix `photo/{photo:slug}`

| Method | URI | Action | Name |
|--------|-----|--------|------|
| POST | `…/like` | `toggleLike` | `photos.like` |
| GET | `…/like/check` | `checkLike` | `photos.like.check` |
| POST | `…/comment/request-otp` | `requestOtp` | `photos.comment.request-otp` |
| POST | `…/comment/verify-otp` | `verifyOtp` | `photos.comment.verify-otp` |
| POST | `…/comment/resend-otp` | `resendOtp` | `photos.comment.resend-otp` |
| GET | `…/comments` | `getComments` | `photos.comments` |

Commenting is gated behind an emailed OTP. Likes are session-based and need no login.

### Downloads, prints, checkout

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/photo/{photo:slug}/download/{format?}` | `DownloadController@download` | `photos.download` |
| GET | `/photo/{photo:slug}/print` | `PrintController@show` | `print.options` |
| POST | `/photo/{photo:slug}/print/inquiry` | `PrintController@inquiry` | `print.inquiry` |
| GET | `/api/print/products` | `PrintController@products` | `print.products` |
| GET | `/photo/{photo:slug}/checkout` | `CheckoutController@show` | `checkout.show` |
| POST | `/photo/{photo:slug}/checkout` | `CheckoutController@process` | `checkout.process` |
| GET | `/order/{order}/confirmation` | `CheckoutController@confirm` | `order.confirmation` |
| POST | `/stripe/webhook` | `CheckoutController@webhook` | `stripe.webhook` |
| GET | `/download/{order}` | `CheckoutController@download` | `download.photo` |

`{format?}` is constrained to `webp|jpeg|jpg`. **`stripe/webhook` is the only CSRF-exempt
route** (`bootstrap/app.php`). All of checkout is dormant — `stripe_enabled` is unset and there
are zero orders.

### Client proofing — prefix `selections`, name prefix `client.`

`GET /` `selections` · `GET /count` · `POST /photo/{photo}/toggle` · `GET /photo/{photo}/check`
· `POST /clear` · `GET /export` · `POST /send` (`sendToPhotographer`).

Session-scoped selection basket. Dormant.

### Client galleries — prefix `client-gallery`, token access

`GET /{token}` `view` · `POST /{token}/password` · `GET /{token}/download/{photo}` ·
`POST /{token}/toggle/{photo}` · `GET /{token}/selections` · `POST /{token}/submit`.

Token in the URL, optional password on top, optional expiry. Dormant.

### Content pages

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/about` | `PageController@about` | `about` |
| GET | `/contact` | `PageController@contact` | `contact` |
| POST | `/contact` | `PageController@sendContact` | `contact.send` |
| GET | `/blog` | `BlogController@index` | `blog.index` |
| GET | `/blog/{post:slug}` | `BlogController@show` | `blog.show` |
| GET | `/gear` | `EquipmentController@index` | `gear.index` |
| GET | `/gear/{equipment:slug}` | `EquipmentController@show` | `gear.show` |
| GET | `/locations` | `LocationController@index` | `locations.index` |
| GET | `/locations/{location:slug}` | `LocationController@show` | `locations.show` |
| GET | `/search` | `SearchController@index` | `search` |
| GET | `/search/suggestions` | `SearchController@suggestions` | `search.suggestions` |

`/gear` and `/locations` resolve but have **zero records** behind them.

`BlogController@show` also supplies `outroPhotos` and `isPhotographyPost` for the photo outro
block — see [`07-frontend.md`](07-frontend.md).

### Feeds, newsletter, SEO

| Method | URI | Name |
|--------|-----|------|
| GET | `/feed/rss` | `feed.rss` |
| GET | `/feed/atom` | `feed.atom` |
| GET | `/blog/feed.xml` | `feed.blog` (same handler as RSS) |
| POST | `/newsletter/subscribe` | `newsletter.subscribe` |
| GET | `/newsletter/confirm/{token}` | `newsletter.confirm` |
| GET | `/newsletter/unsubscribe/{token}` | `newsletter.unsubscribe` |
| GET | `/sitemap.xml` | `sitemap` |
| GET | `/sitemap-images.xml` | `sitemap.images` |
| GET | `/robots.txt` | `robots` |

**`robots.txt` is a closure in `routes/web.php`, not a controller.** It branches on
`Setting::get('seo_robots_allow')`:

- `'1'` → `Allow: /`, both sitemaps advertised, `Disallow` on `/admin/`, `/login`, `/register`,
  `/password/`, `/dashboard`, `/profile`, `/api/`; explicit `Allow: /storage/photos/`;
  `Crawl-delay: 1`.
- anything else → **`Disallow: /`** — the entire site delisted.

That single DB row can hide the whole site from search. It is currently `1`.

---

## 4.2 Authenticated, non-admin

| Method | URI | Controller | Name | Middleware |
|--------|-----|------------|------|------------|
| GET | `/dashboard` | `Admin\DashboardController@index` | `dashboard` | `auth`, `verified` |
| GET | `/admin` | closure → redirect to `dashboard` | — | `auth`, `verified` |
| GET/PATCH/DELETE | `/profile` | `ProfileController` | `profile.*` | `auth` |

`routes/auth.php` is stock Breeze: register, login, forgot-password, reset-password (guest);
verify-email, confirm-password, password update, logout (auth). Registration is technically open
— worth knowing, given there is exactly one intended user.

---

## 4.3 Admin panel

All under `middleware(['auth','verified'])->prefix('admin')->name('admin.')`.

### Photos — the largest surface

```
GET    photos                        index
GET    photos/create                 create
GET    photos/bulk-edit              bulkEdit
POST   photos                        store
GET    photos/{photo}                show
GET    photos/{photo}/edit           edit
PUT    photos/{photo}                update
PATCH  photos/{photo}/quick          quickUpdate
POST   photos/{photo}/toggle-featured
DELETE photos/{photo}                destroy
POST   photos/bulk-action            bulkAction
POST   photos/bulk-update            bulkUpdate
POST   photos/bulk-tags              bulkTags
POST   photos/reoptimize             reoptimize          ← all photos
POST   photos/{photo}/reoptimize     reoptimizeSingle    ← one photo
GET    photos/{photo}/suggest-slug   suggestSlug
POST   photos/validate-slug          validateSlug
POST   photos/validate-title         validateTitle
GET    photos/processing-status      processingStatus    ← polled by the UI
POST   photos/{photo}/retry          retryProcessing
POST   photos/{photo}/replace-image  replaceImage
```

⚠️ **Route-ordering hazard:** `photos/create`, `photos/bulk-edit`, and `photos/processing-status`
are declared **before** `photos/{photo}`. That ordering is what stops Laravel matching
`create` as a photo id. Do not reorder these.

### Everything else

| Area | Routes |
|------|--------|
| **Categories** | index, create, store, edit, update, destroy, `update-order` |
| **Galleries** | index, create, store, show, edit, update, destroy, `add-photos`, `photos/{photo}` (remove) |
| **Tags** | index, store, update, destroy |
| **Posts** | index, create, store, edit, update, `toggle-featured`, destroy |
| **Frontpage** | index, update — the CV/profile home page fields |
| **Settings** | index, update, `theme`, `validate-ai-api`, `regenerate-watermarks`, `test-r2`, `test-turnstile`, and Google OAuth: `google/connect`, `google/callback`, `google/disconnect`, `google/refresh-gsc` |
| **About** | `about/editor`, `about/editorjs`, `about/save`, `about/save-editorjs` — two editors |
| **Logs** | index, `{log}/details`, `clear`, destroy |
| **Contacts** | index, show, `status`, **`reply`**, destroy, `bulk-delete`, `archive-old` |
| **Comments** | index, approve, reject, spam, `bulk-approve`, destroy, reply, `block-email`, `blocked-emails`, `unblock-email` |
| **Orders** | index, show, `status`, `ship`, `note` |
| **Media** | `media/photos` — picker API for editors |
| **Analytics** | `analytics/referrals` |
| **Equipment** | index, create, store, edit, update, destroy |
| **Locations** | index, create, store, edit, update, destroy |
| **Lightroom** | index, `process`, `preview` |
| **Social** | index, create, store, show, publish, destroy, `accounts` |
| **A/B tests** | index, create, store, show, edit, update, start, pause, complete, destroy |
| **SEO** | `seo`, `seo/photo/{photo}`, `seo/post/{post}`, `seo/data` |
| **Translations** | index, `photo/{photo}` (+PUT), `post/{post}` (+PUT) |
| **Email templates** | index, `compose`, `send`, `logs`, edit, update, `preview`, `send-test`, `reset`, `toggle-active` |

⚠️ **Ordering hazard again** in social (`social/accounts` after `social/{socialPost}`) and email
templates (`email-templates/compose` and `/logs` are declared **before**
`email-templates/{emailTemplate}/edit`, which is what makes them work).

The admin **Backup** page and its routes were **removed 2026-08-02**. If you find references,
they are stale — backups now live entirely on the NAS.

---

## 4.4 The API — exactly one endpoint

```php
// routes/api.php
Route::post('/nas/analytics', [NasAnalyticsController::class, 'store'])
    ->middleware(VerifyNasToken::class)
    ->name('api.nas.analytics');
```

**Contract**

```
POST https://mfaruk.com/api/nas/analytics
Authorization: Bearer <NAS_ANALYTICS_TOKEN from server .env>
Content-Type: application/json

{
  "captured_at": "…",
  "gsc": { "clicks":…, "impressions":…, "ctr":…, "position":…,
           "topQueries":[…], "topPages":[…], "clicksOverTime":{…} },
  "ga":  { "activeUsers":…, "sessions":…, "pageViews":… }
}
```

All nested keys optional. Stored in `analytics_snapshots` with 90-day retention; the admin
dashboard renders the latest row. `VerifyNasToken` compares the bearer against
`config('services.nas.analytics_token')` with `hash_equals` and aborts 401 otherwise.

Pushed by the **NAS n8n digest workflow** — see [`09-backup-and-automation.md`](09-backup-and-automation.md).
The site's own Google Search Console OAuth is dead and retired from the dashboard path; this
endpoint replaced it.

⚠️ Row id 1 is **test data** pushed during the build.

---

## 4.5 Console

```php
Artisan::command('inspire', …);
Schedule::command('stats:weekly')->weeklyOn(1, '09:00');   // Mondays 09:00 server time
```

That is the entire schedule. Driven by the `mfaruk` crontab running `schedule:run` every minute.
