# CLAUDE.md - Project Intelligence Document
# mfaruk.com Photography Portfolio

> **IMPORTANT**: Read this file at the start of every session. This is the single source of truth for the project.

---

## DEPLOYMENT — READ THIS FIRST!

### HestiaCP Architecture (CRITICAL)
```
/home/mfaruk/web/mfaruk.com/
├── private/portfolio-app/          ← LARAVEL APP ROOT (git repo)
│   ├── app/, config/, routes/      ← PHP source code
│   ├── resources/                  ← Vue/JS/CSS source
│   ├── vendor/, node_modules/      ← Dependencies
│   ├── storage/                    ← Logs, cache, uploads
│   ├── .env                        ← Production config
│   └── public/                     ← Web-servable files + Vite build output
│       ├── index.php               ← Laravel entry point
│       ├── .htaccess               ← Apache rewrites
│       ├── build/                  ← Vite compiled assets (gitignored, built on server)
│       └── storage → symlink       ← Points to storage/app/public
│
└── public_html/ → SYMLINK to private/portfolio-app/public/
    (Apache serves from here — it IS the public/ folder)
```

### DEPLOYMENT RULES — NEVER BREAK THESE
1. **NEVER rsync/copy local files to server** — git-based deploy ONLY
2. **NEVER put Laravel source (app/, config/, vendor/) in public_html** — it's a symlink to public/
3. **Build assets ON THE SERVER** — `npm run build` outputs to public/build/ which IS public_html/build/
4. **No cp needed for build/** — public_html IS public/, they're the same directory via symlink

### Deploy Workflow
```bash
# LOCAL: commit and push
git add . && git commit -m "message" && git push origin main

# DEPLOY: run from local (SSHs to server, pulls from git, builds there)
./deploy.sh            # Full deploy (with npm build)
./deploy.sh --quick    # Code-only (skip npm build)

# OR manually on server:
ssh user@SERVER_IP
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache
chown -R mfaruk:www-data storage bootstrap/cache public/build
```

### Server-side deploy script (alternative):
```bash
ssh user@SERVER_IP "/home/mfaruk/deploy.sh"
```

---

## QUICK START (Resume Session)

```bash
# 1. Pull latest from git (if working from another machine)
git pull origin main

# 2. Check current photos
ssh user@SERVER_IP "cd /home/mfaruk/web/mfaruk.com/private/portfolio-app && php artisan tinker --execute=\"App\\Models\\Photo::all(['id','title','slug','status'])->each(fn(\\\$p) => print(\\\$p->id.' | '.\\\$p->status.' | '.\\\$p->slug.PHP_EOL));\""

# 3. Deploy changes
./deploy.sh
```

### Current Photos (Feb 2026)
| ID | Slug | Category | Gallery |
|----|------|----------|---------|
| 1 | monpura-sea-beach | Seascapes & Beaches | Coastal Collection |
| 11 | kashmir-tulip-garden | Flora & Gardens | Kashmir Collection |
| 12 | swiss-sheep-farm-pattaya-thailand | Landscapes | Thailand Collection |
| 13 | nong-nooch-tropical-garden-pattaya-thailand | Flora & Gardens | Thailand Collection |
| 14 | monsoon-view-po-plar-beach-koh-chang | Seascapes & Beaches | Thailand Collection |
| 19 | golden-hour-monpura-island-bangladesh | Sunsets & Golden Hour | Coastal Collection |
| 20 | into-the-mist-suspension-bridge-goechala-trek | Landscapes | Himalaya Collection |
| 21 | first-light-kanchenjunga-goechala-viewpoint-sikkim | Landscapes | Himalaya Collection |

### Available Categories
| ID | Name | Slug |
|----|------|------|
| 1 | Seascapes & Beaches | seascapes-beaches |
| 2 | Sunsets & Golden Hour | sunsets-golden-hour |
| 3 | Landscapes | landscapes |
| 4 | Rivers & Waterways | rivers-waterways |
| 5 | Flora & Gardens | flora-gardens |

### Available Galleries
| ID | Name | Slug |
|----|------|------|
| 1 | Coastal Collection | coastal-collection |
| 2 | Kashmir Collection | kashmir-collection |
| 3 | Thailand Collection | thailand-collection |
| 4 | Himalaya Collection | himalaya-collection |

---

## TRIGGER COMMANDS

### `/content [photo-id or URL]`
**Complete SEO & Content Package for a Photo**

When user shares a photo URL, ID, or says `/content`, execute this COMPLETE workflow:

#### Step 1: Fetch Photo Data
```php
// If URL: extract slug from https://mfaruk.com/photo/slug-here or admin URL
// If ID: use directly
$photo = App\Models\Photo::where('slug', $slug)->orWhere('id', $id)->first();
```

#### Step 2: Research Location (if GPS exists)
- Use latitude/longitude to identify the exact location
- **Reverse geocode** to find what's at that exact spot (restaurant, hotel, landmark, beach, etc.)
- Research the place name, region, country
- Find interesting facts about the location for the story

#### Step 2b: Location Verification (ASK USER)
**Before proceeding, ASK the user to confirm the location:**

Example prompt:
```
📍 GPS coordinates: 12.0876, 102.3654

I found this location belongs to:
- **Property**: Po-Plar on the beach (Restaurant)
- **Area**: White Sand Beach, Koh Chang
- **Region**: Trat Province, Thailand

Is this correct? Or was the photo taken at a different spot nearby?
```

**Wait for user confirmation before generating content.**

If user provides correction (e.g., "it was at the restaurant next door" or "it's actually the beach, not the restaurant"), update location accordingly.

#### Step 3: Assign Category
**Pick the BEST matching category from existing ones:**
| ID | Category | Use for |
|----|----------|---------|
| 1 | Seascapes & Beaches | Ocean, sea, beach, coastal views |
| 2 | Sunsets & Golden Hour | Sunset, sunrise, golden light shots |
| 3 | Landscapes | Mountains, fields, rural, scenic views |
| 4 | Rivers & Waterways | Rivers, lakes, waterfalls, streams |
| 5 | Flora & Gardens | Flowers, gardens, plants, botanical |

**If no category fits**, create a new one:
```php
$category = App\Models\Category::create([
    'name' => 'New Category Name',
    'slug' => Str::slug('New Category Name'),
    'sort_order' => App\Models\Category::max('sort_order') + 1,
]);
```

#### Step 4: Assign Gallery
**Pick the BEST matching gallery based on location/trip:**
| ID | Gallery | Use for |
|----|---------|---------|
| 1 | Coastal Collection | Bangladesh coastal photos |
| 2 | Kashmir Collection | Kashmir, India photos |
| 3 | Thailand Collection | Thailand photos |

**If no gallery fits**, create a new one:
```php
$gallery = App\Models\Gallery::create([
    'name' => 'New Gallery Name',
    'slug' => Str::slug('New Gallery Name'),
    'is_published' => true,
]);
```

#### Step 5: Generate ALL Content Fields

| Field | Action | Notes |
|-------|--------|-------|
| `title` | Generate | Descriptive, includes location |
| `slug` | Generate | SEO-friendly, lowercase-with-hyphens |
| `description` | Generate | 1-3 sentences about what's in the photo |
| `seo_title` | Generate | **MAX 70 chars** - Primary Keyword \| Location |
| `meta_description` | Generate | **MAX 160 chars** - Compelling, no fluff words |
| `location_name` | Generate | Full location: City, Region, Country |
| `story` | Generate | 2-4 paragraphs, HTML `<p>` tags, FIRST PERSON |
| `category_id` | Assign | From Step 3 |
| `gallery_id` | Assign | From Step 4 |
| `tags` | Generate | 10-15 tags: location, subject, mood, style, season |
| `status` | Set | `published` |
| `is_featured` | Set | `true` (show on homepage) |

#### Step 6: Writing Style Checklist
Before saving, verify the story:
- [ ] Uses "I", "me", "my" - first person perspective
- [ ] Describes personal experience and feelings
- [ ] NO words: "stunning", "breathtaking", "extraordinary", "vibrant", "world-renowned", "internationally acclaimed"
- [ ] NO phrases: "captured with", "Instagram-worthy", "must-see", "the exceptional lens"
- [ ] Sounds like talking to a friend, not a travel brochure
- [ ] Mentions specific details (time of day, weather, what you were doing)
- [ ] **TONE**: Conversational, authentic — personal observations, what drew you to the shot, the moment, the feeling

#### Step 7: SEO Validation
Before saving, verify:
- [ ] `seo_title` ≤ 70 characters (count and confirm)
- [ ] `meta_description` ≤ 160 characters (count and confirm)
- [ ] No duplicate content from other photos

#### Step 8: Save to Database
```php
$photo->update([
    'title' => $title,
    'slug' => $slug,
    'description' => $description,
    'seo_title' => $seoTitle,           // MUST be ≤ 70 chars
    'meta_description' => $metaDesc,     // MUST be ≤ 160 chars
    'location_name' => $locationName,
    'story' => $story,
    'category_id' => $categoryId,        // From step 3
    'gallery_id' => $galleryId,          // From step 4
    'status' => 'published',
    'is_featured' => true,
]);

// Add tags (use existing or create new)
$tagIds = [];
foreach($tagNames as $name) {
    $tag = App\Models\Tag::firstOrCreate(
        ['slug' => Str::slug($name)],
        ['name' => $name, 'type' => 'photo']
    );
    $tagIds[] = $tag->id;
}
$photo->tags()->sync($tagIds);

// Update category count
if ($photo->category_id) {
    App\Models\Category::find($photo->category_id)->updatePhotoCount();
}
```

#### Step 9: Confirm Completion
Report back:
- Photo ID and URL
- **Category assigned** (existing or newly created)
- **Gallery assigned** (existing or newly created)
- SEO title with character count
- Meta description with character count
- Number of tags added (list them)
- Status: published

**This command ensures the photo has:**
- **Category** for gallery filtering and organization
- **Gallery** for collection grouping
- Full SEO optimization (Open Graph, Twitter Cards, JSON-LD are automatic via SeoHead.vue)
- Personal, authentic story content
- Proper location data
- Complete tag coverage
- Featured status for homepage display
- Ready for social sharing

### `/deploy`
Quick deploy to production:
```bash
./deploy.sh
```

### `/status`
Check site status and recent errors.

---

## PROJECT OVERVIEW

**Site**: https://mfaruk.com
**Type**: Photography Portfolio with Blog
**Stack**: Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS
**Database**: MySQL (production), SQLite (local)
**Hosting**: HestiaCP on DigitalOcean (SERVER_IP)
**Git Repo**: github.com/mmhfarooque/portfolio-app (main branch)

### Current Stats (as of Feb 14, 2026)
- **Photos**: 8 published (all with SEO content, categories, galleries, and personal stories)
- **Categories**: 5 (Seascapes, Sunsets, Landscapes, Rivers, Flora)
- **Galleries**: 4 (Coastal, Kashmir, Thailand, Himalaya)
- **Contact Email**: farooque7@gmail.com (receives form submissions)
- **Features Active**: 12+ public features, full admin panel, per-photo watermark control

---

## SERVER ACCESS

```bash
# SSH to production
ssh user@SERVER_IP

# Laravel app location
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app

# Public web root
cd /home/mfaruk/web/mfaruk.com/public_html

# Run artisan commands (ALWAYS from private folder)
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan tinker
php artisan migrate
php artisan config:cache
php artisan route:cache
```

**IMPORTANT**: The `app-path.php` in public_html must point to:
```php
<?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";
```

---

## DATABASE OPERATIONS

### Get Photo by Slug
```php
$photo = App\Models\Photo::where('slug', 'photo-slug')->first();
```

### Update Photo SEO
```php
$photo = App\Models\Photo::find($id);
$photo->update([
    'title' => 'Title Here',           // max 60 chars
    'slug' => 'url-slug-here',         // max 60 chars
    'description' => 'Short desc',     // max 160 chars
    'seo_title' => 'SEO Title',        // max 70 chars ⚠️
    'meta_description' => 'Meta',      // max 160 chars ⚠️
    'location_name' => 'Place, Country',
    'story' => '<p>Story HTML</p>',
    'status' => 'published',
]);
```

### Add Tags to Photo
```php
$tagNames = ['tag1', 'tag2', 'tag3'];
$tagIds = [];
foreach($tagNames as $name) {
    $tag = App\Models\Tag::firstOrCreate(
        ['slug' => Str::slug($name)],
        ['name' => $name, 'type' => 'photo']
    );
    $tagIds[] = $tag->id;
}
$photo->tags()->sync($tagIds);
```

### Check All Photos Status
```php
$photos = App\Models\Photo::all();
foreach($photos as $p) {
    echo $p->id . ' | ' . strlen($p->seo_title) . '/70 | ' . strlen($p->meta_description) . '/160 | ' . $p->status . ' | ' . $p->slug . PHP_EOL;
}
```

---

## FEATURE LIST

### Public Features
- [x] Photo gallery with masonry layout
- [x] Individual photo pages with full metadata
- [x] Photo likes (session-based, no login required)
- [x] Photo comments with OTP email verification
- [x] Interactive map showing photo locations (Leaflet)
- [x] Category and tag filtering
- [x] Photo series/collections
- [x] Blog with Editor.js
- [x] About page with Editor.js
- [x] Contact form
- [x] Newsletter signup
- [x] Print inquiry system
- [x] Client galleries (password protected)
- [x] Photo download options
- [x] Dark/Light theme toggle
- [x] SEO optimized (meta tags, Open Graph)
- [x] RSS/Atom feeds
- [x] Sitemap generation

### Admin Features
- [x] Dashboard with stats
- [x] Photo management (upload, edit, bulk edit)
- [x] Category management
- [x] Tag management
- [x] Gallery management
- [x] Blog post management
- [x] Comment moderation (approve/reject/spam)
- [x] Email blocking for spam
- [x] Contact message management
- [x] Settings management
- [x] SEO audit tools
- [x] Analytics/referral tracking

---

## FILE STRUCTURE

```
portfolio-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── GalleryController.php      # Public photo pages
│   │   ├── BlogController.php         # Public blog
│   │   ├── PhotoInteractionController.php  # Likes, comments
│   │   └── Admin/
│   │       ├── PhotoController.php    # Photo CRUD
│   │       ├── CommentController.php  # Comment moderation
│   │       └── ...
│   ├── Models/
│   │   ├── Photo.php                  # Main photo model
│   │   ├── PhotoComment.php           # Comments
│   │   ├── PhotoLike.php              # Likes
│   │   ├── Tag.php                    # Tags
│   │   ├── Category.php               # Categories
│   │   ├── Gallery.php                # Galleries
│   │   ├── Post.php                   # Blog posts
│   │   └── ...
│   └── Services/
│       └── PhotoProcessingService.php # Image processing
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Public/
│       │   │   ├── Gallery/
│       │   │   │   ├── Index.vue      # Gallery listing
│       │   │   │   ├── Show.vue       # Single photo page ⭐
│       │   │   │   └── Map.vue        # Photo map
│       │   │   ├── Blog/
│       │   │   └── ...
│       │   └── Admin/
│       │       ├── Photos/
│       │       ├── Comments/
│       │       └── ...
│       └── Components/
│           └── Photo/
│               ├── LikeButton.vue     # Like functionality
│               ├── CommentSection.vue # Comments with OTP
│               └── CommentThread.vue  # Threaded comments
├── routes/
│   └── web.php                        # All routes
├── database/
│   └── migrations/                    # DB schema
├── public/
│   ├── favicon.ico                    # Site favicon
│   └── build/                         # Compiled assets
├── docs/
│   └── CONTENT_GUIDELINES.md          # SEO limits reference
├── deploy.sh                          # Deployment script
└── CLAUDE.md                          # THIS FILE
```

---

## KEY FILES TO KNOW

| File | Purpose |
|------|---------|
| `resources/js/Pages/Public/Gallery/Show.vue` | Single photo page (likes, comments, map) |
| `resources/js/Components/SeoHead.vue` | **SEO component** - OG, Twitter, JSON-LD, canonical |
| `app/Http/Controllers/GalleryController.php` | Photo page data (must include lat/lng) |
| `app/Http/Controllers/PhotoInteractionController.php` | Like/comment API |
| `app/Http/Controllers/SitemapController.php` | Sitemap generation (photos, blog, categories) |
| `resources/views/app.blade.php` | Main HTML template (favicon, meta) |
| `deploy.sh` | Production deployment |
| `routes/web.php` | All route definitions |

---

## PHOTOS TABLE SCHEMA

```sql
photos:
  id, title, seo_title, slug, description, meta_description, story,
  original_path, display_path, thumbnail_path, watermarked_path,
  exif_data (JSON), latitude, longitude, location_name,
  width, height, file_size, mime_type,
  blurhash, dominant_color, image_hash, file_hash,
  user_id, category_id, gallery_id,
  status (draft/published), is_featured,
  views, likes_count, comments_count,
  custom_max_resolution, custom_quality, watermark_disabled,
  original_width, original_height,
  captured_at, created_at, updated_at
```

---

## COMMON ISSUES & FIXES

### 500 Error After Deploy
```bash
# SSH to server
ssh user@SERVER_IP

# Check app-path.php is correct
cat /home/mfaruk/web/mfaruk.com/public_html/app-path.php
# Should be: <?php return "/home/mfaruk/web/mfaruk.com/private/portfolio-app";

# Clear and rebuild caches
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 502 Bad Gateway
```bash
# Restart PHP-FPM
systemctl restart php8.4-fpm
systemctl restart nginx
systemctl restart apache2
```

### Photo Not Showing on Homepage
Check these:
1. `status` must be `published` (not `draft`)
2. `is_featured` must be `true`
3. `seo_title` must be ≤ 70 characters
4. `meta_description` must be ≤ 160 characters

### Map Not Showing
Ensure `GalleryController@show` passes `latitude` and `longitude` to the view.

---

## SEO CONTENT PROMPT

When generating SEO content for a photo:

### ONLY THESE HAVE CHARACTER LIMITS:
- `seo_title` - **MAX 70 characters** (count before saving!)
- `meta_description` - **MAX 160 characters** (count before saving!)

### NO LIMITS (but keep reasonable):
- `title` - Descriptive, can be long
- `slug` - SEO-friendly URL
- `description` - 1-3 sentences about the photo
- `story` - 2-4 paragraphs, HTML with `<p>` tags
- `location_name` - Full location

### WRITING VOICE:
Write as **Mahmud the photographer**, not a travel website:
- ❌ "This stunning vista showcases the breathtaking beauty..."
- ❌ "Captured with the exceptional XF 23mm lens..."
- ❌ "Instagram-worthy destination..."
- ✅ "I came here on a whim, looking for something different..."
- ✅ "The morning light was perfect, soft and golden..."
- ✅ "What struck me first wasn't any single flower..."

### TAGS:
Add 10-15 tags covering: location, subject, mood, style, season

---

## DEPLOYMENT

### Quick Deploy
```bash
./deploy.sh
```

### Deploy Flow
1. Syncs files via rsync (excludes node_modules, vendor, .env, storage)
2. Copies public files to public_html
3. Sets correct app-path.php
4. Runs composer install
5. Runs migrations
6. Rebuilds caches

### After Deploy Checklist
- [ ] Check site loads: https://mfaruk.com
- [ ] Check admin: https://mfaruk.com/admin
- [ ] Verify new changes are visible
- [ ] Check for console errors

---

## CLOUDFLARE INTEGRATION

The site uses Cloudflare for CDN, R2 storage, and Turnstile spam protection.

### R2 Cloud Storage (for photo originals)
- **Purpose**: Store original high-res photos in cloud to save server disk space
- **How it works**: When uploading photos, originals are uploaded to R2 with `r2:` prefix in path
- **Re-optimization**: When re-optimizing, originals are downloaded from R2 temporarily
- **Settings**: Admin → Settings → Cloudflare Integration → R2 Cloud Storage

```php
// Check if photo original is in R2
$photo->isOriginalInCloud(); // returns true if original_path starts with 'r2:'

// R2 settings are stored in database (Settings model)
Setting::get('r2_enabled');        // '1' or '0'
Setting::get('r2_access_key_id');
Setting::get('r2_secret_access_key');
Setting::get('r2_bucket');         // default: 'photography'
Setting::get('r2_endpoint');       // https://account-id.r2.cloudflarestorage.com
```

### Turnstile Spam Protection (for contact form)
- **Purpose**: Block spam bots from submitting contact form
- **How it works**: Invisible CAPTCHA that validates human users
- **Settings**: Admin → Settings → Cloudflare Integration → Turnstile Spam Protection
- **Get keys**: https://dash.cloudflare.com → Turnstile → Add Widget

```php
// Turnstile settings in database
Setting::get('turnstile_enabled');     // '1' or '0'
Setting::get('turnstile_site_key');    // 0x4AAAA...
Setting::get('turnstile_secret_key');  // 0x4AAAA...
```

### Contact Form Spam Protection (Multiple Layers)
1. **Turnstile** - Cloudflare's invisible CAPTCHA
2. **Spam keyword detection** - Blocks messages with: reseller, SEO, backlinks, URLs
3. **Suspicious email detection** - Blocks `randomletters123@gmail.com` patterns
4. **Honeypot field** - Hidden field that bots fill out
5. **Rate limiting** - 5 submissions per hour per IP
6. **UTF-8 sanitization** - Strips malformed characters

### CDN Cache Issues
For cache issues after deployment:
1. Hard refresh: `Cmd+Shift+R`
2. Purge from Cloudflare dashboard → Caching → Purge Everything
3. Test in Incognito window

---

## PER-PHOTO WATERMARK CONTROL

Individual photos can override the global watermark setting.

### How It Works
- **Default**: Photos use global watermark setting (Admin → Settings → Watermark)
- **Per-photo override**: In Photo Edit page → Image Optimization → Watermark toggle
- **Super power**: Per-photo "Disabled" setting has priority over global - even if global watermark is enabled, photos marked as disabled will NEVER have watermark

### Usage
1. Go to Admin → Photos → Edit Photo
2. Scroll to "Image Optimization" section
3. Toggle "Watermark" to Disabled (turns red)
4. Click "Re-optimize Photo" to apply

### Technical Details
```php
// Database column
photos.watermark_disabled  // boolean, default false

// Check if watermark should be applied
$photo->shouldApplyWatermark();  // returns false if watermark_disabled=true

// The method has "super power" priority:
// 1. If watermark_disabled = true → NEVER apply watermark
// 2. Else use global Setting::get('watermark_enabled')
```

### Key Files
- `app/Models/Photo.php` - `shouldApplyWatermark()` method
- `app/Services/PhotoProcessingService.php` - Uses photo setting in watermark generation
- `resources/js/Pages/Admin/Photos/Edit.vue` - Watermark toggle UI
- `app/Http/Controllers/Admin/PhotoController.php` - Handles watermark_disabled in reoptimize

---

## RECENT CHANGES LOG

| Date | Change |
|------|--------|
| 2026-02-14 | Added SEO content for photo #21 (Kanchenjunga sunrise from Goechala Viewpoint 1, Sikkim) |
| 2026-02-14 | Merged `/photo-seo` and `/content` into single unified `/content` command |
| 2026-01-04 | **Added per-photo watermark disable feature** - individual photos can override global watermark |
| 2026-01-04 | Fixed Settings page bug where quality slider reverted to 82% (wrong group in database) |
| 2026-01-04 | Fixed bulk optimization to always override individual photo custom settings |
| 2026-01-04 | Added SEO content for photos #19 (Monpura golden hour) and #20 (Goechala trek bridge) |
| 2026-01-04 | Created Himalaya Collection gallery for India trek photos |
| 2026-01-03 | **Added Cloudflare R2 storage for photo originals** (saves server disk space) |
| 2026-01-03 | **Added Cloudflare Turnstile spam protection** for contact form |
| 2026-01-03 | Added Cloudflare settings UI in Admin → Settings |
| 2026-01-03 | Added contact form spam detection (keywords, email patterns, UTF-8 sanitization) |
| 2025-12-26 | **Updated `/content` command to auto-assign categories and galleries** |
| 2025-12-26 | Added available categories and galleries reference tables |
| 2025-12-26 | Fixed missing categories on photos #13 and #14 |
| 2025-12-26 | Added SeoHead.vue component (Open Graph, Twitter Cards, JSON-LD structured data) |
| 2025-12-26 | Added `/content` command for complete photo SEO workflow |
| 2025-12-26 | Updated sitemap to include blog posts |
| 2025-12-26 | Rewrote photo stories to first-person personal voice |
| 2025-12-26 | Added meta descriptions and canonical URLs to all public pages |
| 2024-12-22 | Added shooting date/time to photo page sidebar |
| 2024-12-22 | Added photo likes & comments with OTP verification |
| 2024-12-22 | Added location map to photo pages (Leaflet) |
| 2024-12-22 | Fixed favicon (black bg, golden M) |
| 2024-12-22 | Added SEO content to all photos |
| 2024-12-22 | Fixed deploy.sh app-path.php issue |
| 2024-12-22 | Created CLAUDE.md project intelligence doc |

---

## SEO IMPLEMENTATION

The site now has comprehensive SEO via `SeoHead.vue` component:

### Automatic Features (no manual work needed):
- **Open Graph tags**: og:title, og:description, og:image, og:url, og:type
- **Twitter Cards**: summary_large_image with full metadata
- **JSON-LD Structured Data**: ImageObject for photos, Article for blog posts
- **Canonical URLs**: Prevents duplicate content issues
- **Breadcrumb Schema**: For navigation trail in search results

### Usage:
```vue
<SeoHead
    :title="photo.seo_title"
    :description="photo.meta_description"
    :image="photo.display_path"
    type="photo"
    :photo="photo"
    :breadcrumbs="[...]"
/>
```

The component automatically generates all required meta tags and structured data.

---

## IMPORTANT SETTINGS

### Database Settings (via Settings model)
```php
// Get a setting
App\Models\Setting::get('contact_email');

// Set a setting
App\Models\Setting::set('contact_email', 'your@email.com');

// Key settings:
// - contact_email: Receives contact form submissions
// - photographer_name: Site title/brand name
// - about_content: About page content (JSON from Editor.js)
```

### Environment Variables (.env)
Key variables on production server:
- `DB_CONNECTION=mysql`
- `MAIL_MAILER=smtp` (for contact form emails)
- `APP_URL=https://mfaruk.com`

---

## GIT WORKFLOW

```bash
# After making changes locally:
npm run build                    # Build frontend assets
git add .                        # Stage changes
git commit -m "Description"      # Commit
git push origin main             # Push to GitHub
./deploy.sh                      # Deploy to production

# From another machine:
git pull origin main             # Get latest changes
npm install                      # Install dependencies
npm run build                    # Rebuild assets
```

---

## CONTACT / OWNER

**Site Owner**: Mahmud Farooque
**Email**: farooque7@gmail.com
**Domain**: mfaruk.com
**GitHub**: github.com/mmhfarooque/portfolio-app

---

## PENDING / KNOWN ISSUES

### Photo #20 - R2 Original Not Showing in UI
- **Issue**: Photo #20 shows "No original file" in admin UI but original IS in R2 cloud
- **Database**: `original_path` = `r2:originals/2838d092-80cf-40eb-8fda-9efb1cd6af46.jpg`
- **R2 Status**: File exists (20.85 MB)
- **Tinker test**: `$photo->hasOriginal()` returns `true`, `$photo->isOriginalInCloud()` returns `true`
- **Likely cause**: Cache issue or R2 disk not being configured before checking
- **Workaround**: Try hard refresh (Ctrl+Shift+R) after cache clear
- **To investigate**: Check why `hasOriginal()` works in tinker but not in web request

### Tomorrow's Tasks
1. Investigate Photo #20 R2 display issue
2. Test watermark disable on a photo
3. Continue with photo content for remaining photos

---

*Last Updated: February 14, 2026*
*Update this file after every significant change*
