# CLAUDE.md - Project Intelligence Document
# mfaruk.com Photography Portfolio

> **IMPORTANT**: Read this file at the start of every session. This is the single source of truth for the project.

---

## TRIGGER COMMANDS

### `/photo-seo [URL]`
When user shares a photo URL and says `/photo-seo`, execute this workflow:

1. **Extract photo slug** from URL (e.g., `https://mfaruk.com/photo/my-photo` → `my-photo`)
2. **Fetch photo data** from database including GPS coordinates
3. **Research location** if GPS exists (use coordinates to identify place)
4. **Generate SEO content** following these STRICT limits:

| Field | Max Length | Format |
|-------|------------|--------|
| `title` | 60 chars | Descriptive, includes location |
| `slug` | 60 chars | lowercase-with-hyphens |
| `description` | 160 chars | What the photo shows |
| `seo_title` | **70 chars** | Primary Keyword \| Secondary |
| `meta_description` | **160 chars** | Compelling, keyword-rich |
| `location_name` | 100 chars | Full location with country |
| `story` | 500-1000 chars | HTML `<p>` tags, 2-4 paragraphs |

5. **Add 10-15 tags** related to: location, subject, style, mood, season
6. **Set status** to `published` if ready
7. **Verify** all character counts before saving

**CRITICAL**: If SEO title > 70 or meta_description > 160, the photo won't display properly!

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
**Hosting**: HestiaCP on DigitalOcean (63.142.240.72)

---

## SERVER ACCESS

```bash
# SSH to production
ssh root@63.142.240.72

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
| `app/Http/Controllers/GalleryController.php` | Photo page data (must include lat/lng) |
| `app/Http/Controllers/PhotoInteractionController.php` | Like/comment API |
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
  captured_at, created_at, updated_at
```

---

## COMMON ISSUES & FIXES

### 500 Error After Deploy
```bash
# SSH to server
ssh root@63.142.240.72

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

When generating SEO content for a photo, use this template:

```
PHOTO ANALYSIS:
- Subject: [What is in the photo]
- Location: [GPS lookup or user provided]
- Mood: [Serene, dramatic, vibrant, etc.]
- Style: [Landscape, portrait, macro, etc.]
- Season/Time: [If apparent]

GENERATED CONTENT:

Title (≤60 chars):
[Descriptive title including location]

Slug (≤60 chars):
[lowercase-hyphenated-url]

Description (≤160 chars):
[Brief description of what the photo shows]

SEO Title (≤70 chars): ⚠️ COUNT CAREFULLY
[Primary Keyword | Secondary Keyword]

Meta Description (≤160 chars): ⚠️ COUNT CAREFULLY
[Compelling description with keywords, encourages clicks]

Location Name:
[Specific Place, City, Region, Country]

Story (HTML, 2-4 paragraphs):
<p>First paragraph about the location/subject...</p>
<p>Second paragraph about the experience/significance...</p>
<p>Optional third paragraph with additional context...</p>

Tags (10-15):
[tag1], [tag2], [tag3], ...

Status: published
```

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

## CLOUDFLARE

The site uses Cloudflare CDN. For cache issues:
1. Hard refresh: `Cmd+Shift+R`
2. Purge from Cloudflare dashboard
3. Wait for cache expiry

---

## RECENT CHANGES LOG

| Date | Change |
|------|--------|
| 2024-12-22 | Added photo likes & comments with OTP verification |
| 2024-12-22 | Added location map to photo pages (Leaflet) |
| 2024-12-22 | Fixed favicon (black bg, golden M) |
| 2024-12-22 | Added SEO content to all photos |
| 2024-12-22 | Fixed deploy.sh app-path.php issue |
| 2024-12-22 | Created CLAUDE.md project intelligence doc |

---

## CONTACT / OWNER

**Site Owner**: Mahmud Farooque
**Email**: (check .env for MAIL_FROM_ADDRESS)
**Domain**: mfaruk.com

---

*Last Updated: December 22, 2024*
*Update this file after every significant change*
