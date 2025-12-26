# CLAUDE.md - Project Intelligence Document
# mfaruk.com Photography Portfolio

> **IMPORTANT**: Read this file at the start of every session. This is the single source of truth for the project.

---

## QUICK START (Resume Session)

```bash
# 1. Pull latest from git (if working from another machine)
git pull origin main

# 2. Check current photos
ssh root@63.142.240.72 "cd /home/mfaruk/web/mfaruk.com/private/portfolio-app && php artisan tinker --execute=\"App\\Models\\Photo::all(['id','title','slug','status'])->each(fn(\\\$p) => print(\\\$p->id.' | '.\\\$p->status.' | '.\\\$p->slug.PHP_EOL));\""

# 3. Deploy changes
./deploy.sh
```

### Current Photos (Dec 2025)
| ID | Slug | Category | Gallery |
|----|------|----------|---------|
| 1 | monpura-sea-beach | Seascapes & Beaches | Coastal Collection |
| 11 | kashmir-tulip-garden | Flora & Gardens | Kashmir Collection |
| 12 | swiss-sheep-farm-pattaya-thailand | Landscapes | Thailand Collection |
| 13 | nong-nooch-tropical-garden-pattaya-thailand | Flora & Gardens | Thailand Collection |
| 14 | monsoon-view-po-plar-beach-koh-chang | Seascapes & Beaches | Thailand Collection |

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

---

## TRIGGER COMMANDS

### `/photo-seo [URL]`
When user shares a photo URL and says `/photo-seo`, execute this workflow:

1. **Extract photo slug** from URL (e.g., `https://mfaruk.com/photo/my-photo` → `my-photo`)
2. **Fetch photo data** from database including GPS coordinates
3. **Research location** if GPS exists (use coordinates to identify place)
4. **Generate SEO content** following these STRICT limits:

| Field | Limit | Notes |
|-------|-------|-------|
| `title` | No limit | Descriptive, includes location |
| `slug` | No limit | lowercase-with-hyphens, SEO-friendly |
| `description` | No limit | What the photo shows, 1-3 sentences |
| `seo_title` | **70 chars MAX** | Primary Keyword \| Secondary |
| `meta_description` | **160 chars MAX** | Compelling, keyword-rich |
| `location_name` | No limit | Full location with country |
| `story` | No limit | HTML `<p>` tags, 2-4 paragraphs, PERSONAL voice |

### WRITING STYLE (Critical!)
- Write like a **photographer sharing their work**, not a travel brochure
- **AVOID**: "extraordinary", "stunning", "breathtaking", "world-renowned", "internationally acclaimed"
- **AVOID**: "captured with [camera]", "the exceptional lens", "Instagram-worthy"
- **USE**: Personal observations, what drew you to the shot, the moment, the feeling
- **TONE**: Conversational, authentic, like talking to a friend about your trip

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

### `/content [photo-id or URL]`
**Complete SEO & Content Package for a Photo**

When user says `/content` with a photo ID or URL, execute this COMPLETE workflow:

#### Step 1: Fetch Photo Data
```php
// If URL: extract slug from https://mfaruk.com/photo/slug-here or admin URL
// If ID: use directly
$photo = App\Models\Photo::where('slug', $slug)->orWhere('id', $id)->first();
```

#### Step 2: Research Location (if GPS exists)
- Use latitude/longitude to identify the exact location
- Research the place name, region, country
- Find interesting facts about the location for the story

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
- [ ] NO words: "stunning", "breathtaking", "extraordinary", "vibrant"
- [ ] NO phrases: "captured with", "Instagram-worthy", "must-see"
- [ ] Sounds like talking to a friend, not a travel brochure
- [ ] Mentions specific details (time of day, weather, what you were doing)

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

---

## PROJECT OVERVIEW

**Site**: https://mfaruk.com
**Type**: Photography Portfolio with Blog
**Stack**: Laravel 12 + Vue 3 + Inertia.js + Tailwind CSS
**Database**: MySQL (production), SQLite (local)
**Hosting**: HestiaCP on DigitalOcean (63.142.240.72)
**Git Repo**: github.com/mmhfarooque/portfolio-app (main branch)

### Current Stats (as of Dec 26, 2025)
- **Photos**: 5 published (all with SEO content, categories, galleries, and personal stories)
- **Categories**: 5 (Seascapes, Sunsets, Landscapes, Rivers, Flora)
- **Galleries**: 3 (Coastal, Kashmir, Thailand)
- **Contact Email**: farooque7@gmail.com (receives form submissions)
- **Features Active**: 12+ public features, full admin panel

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

## CLOUDFLARE

The site uses Cloudflare CDN. For cache issues:
1. Hard refresh: `Cmd+Shift+R`
2. Purge from Cloudflare dashboard
3. Wait for cache expiry

---

## RECENT CHANGES LOG

| Date | Change |
|------|--------|
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

*Last Updated: December 26, 2025*
*Update this file after every significant change*
