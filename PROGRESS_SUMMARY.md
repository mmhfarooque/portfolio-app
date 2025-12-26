# Photography Portfolio Website - Progress Summary

**Last Updated:** December 26, 2025
**Status:** 12 features active (Before/After Comparison removed)

---

## Project Overview

Laravel 12 Photography Portfolio website with:
- **Production URL:** https://mfaruk.com
- **Server:** root@63.142.240.72
- **App Path:** /home/mfaruk/web/mfaruk.com/private/portfolio-app
- **PHP-FPM User:** mfaruk:mfaruk
- **Control Panel:** Hestia
- **Git Repository:** https://github.com/mmhfarooque/portfolio-app

---

## ⚠️ IMPORTANT: Code Sync Status

**Source of Truth: Git Repository (GitHub)**

Before making any changes or deployments, ensure local, git, and server are in sync:

```bash
# Check local vs git
cd "/home/mmhf/Photography Portfolio Website/portfolio-app"
git status
git fetch origin && git log HEAD..origin/main --oneline

# Check server vs git
ssh root@63.142.240.72 "cd /home/mfaruk/web/mfaruk.com/private/portfolio-app && git status && git fetch origin && git log HEAD..origin/main --oneline"
```

### Current Design/Theme State
- **Framework:** Vue 3 + Inertia.js (migrated from Alpine.js/Blade)
- **Layout:** Dark/light mode toggle with CSS custom properties
- **Photo pages:** Feature dark mode with amber accents
- **Gallery grid:** Responsive with hover effects, uses `dominant_color` for placeholders

### Deployment Workflow
1. **Always pull latest from git first** (both local and server)
2. Make changes locally
3. Test locally if possible
4. Commit and push to git
5. Pull on server from git (NOT rsync local files directly)
6. Rebuild assets on server: `npm run build`
7. Clear caches: `php artisan config:cache && php artisan view:cache && php artisan route:cache`

---

## Completed Features (12/12)

### Phase 1: Core Enhancements
1. **Modal Image Viewer** - Click to zoom, keyboard navigation, escape to close
2. **Download Options** - WebP and JPEG formats with rate limiting
3. **Enhanced Contact Form** - Honeypot spam protection, database storage, admin panel
4. **Photo View Analytics** - Dashboard with top photos, views over time, category breakdown

### Phase 2: Gallery & Client Features
5. **Password-Protected Galleries** - Session-based access with 24-hour validity
6. **Client Proofing System** - Cookie-based photo selections, export functionality

### Phase 3: Content & SEO
7. **Blog/Stories Section** - Full CRUD with categories, tags, SEO fields, Markdown support

### Phase 4: Infrastructure
8. **Queue Processing for Uploads** - Background jobs with Supervisor, real-time status
9. **Backblaze B2 Backup System** - Incremental backups, database backups, admin UI
10. **Cloudflare CDN Integration** - CDN-aware URL accessors on Photo model

### Phase 5: Print Integration
11. **Printful Print Store** - Print inquiry form, product options, API-ready

### Phase 6: Image Optimization
12. **LQIP (Low Quality Image Placeholder)** - Dominant color placeholders while images lazy-load

---

## Configuration Required (.env)

Add these to production `.env` to enable optional features:

```env
# Queue (already configured)
QUEUE_CONNECTION=database

# Backblaze B2 Backup (optional)
B2_ACCESS_KEY_ID=your_key_id
B2_SECRET_ACCESS_KEY=your_application_key
B2_BUCKET=your_bucket_name
B2_REGION=us-west-004
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com

# CDN (optional)
CDN_URL=https://your-cdn-domain.com

# Printful API (optional)
PRINTFUL_API_KEY=your_printful_api_key
```

---

## Key Files Created/Modified

### New Files
- `app/Jobs/ProcessPhotoUpload.php` - Background photo processing (includes LQIP generation)
- `app/Console/Commands/BackupPhotos.php` - Backup artisan command
- `app/Console/Commands/GeneratePlaceholders.php` - Backfill placeholders for existing photos
- `app/Services/PrintService.php` - Printful API integration
- `app/Services/BlurHashService.php` - Extracts dominant color from images for LQIP
- `app/Http/Controllers/PrintController.php` - Print store controller
- `app/Http/Controllers/Admin/BackupController.php` - Backup management
- `resources/views/print/options.blade.php` - Print options page
- `resources/views/admin/settings/backup.blade.php` - Backup settings
- `database/migrations/2025_12_16_200000_add_processing_fields_to_photos_table.php`

### Modified Files
- `app/Models/Photo.php` - Added CDN URL accessors, processing fields
- `app/Http/Controllers/Admin/PhotoController.php` - Queue dispatch, status API
- `resources/views/admin/photos/index.blade.php` - Processing status UI
- `resources/views/gallery/show.blade.php` - Print button, CDN URLs
- `resources/views/gallery/*.blade.php` - CDN-aware thumbnail URLs
- `config/app.php` - Added cdn_url config
- `config/filesystems.php` - Added B2 disk
- `config/services.php` - Added Printful config
- `routes/web.php` - Added print, backup routes

---

## Server Configuration

### Supervisor (Queue Worker)
Config file: `/etc/supervisor/conf.d/portfolio-worker.conf`
```
[program:portfolio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/mfaruk/web/mfaruk.com/private/portfolio-app/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=mfaruk
numprocs=1
redirect_stderr=true
stdout_logfile=/home/mfaruk/web/mfaruk.com/private/portfolio-app/storage/logs/worker.log
```

Check status: `supervisorctl status`
Restart: `supervisorctl restart portfolio-worker:*`

### PHP CLI (pcntl enabled)
pcntl functions were removed from disable_functions in `/etc/php/8.4/cli/php.ini`

---

## Key URLs

### Public
- Home: https://mfaruk.com
- Photos: https://mfaruk.com/photos
- Blog: https://mfaruk.com/blog
- Photo Detail: https://mfaruk.com/photo/{slug}
- Print Options: https://mfaruk.com/photo/{slug}/print
- Selections: https://mfaruk.com/selections

### Admin
- Dashboard: https://mfaruk.com/dashboard
- Photos: https://mfaruk.com/admin/photos
- Settings: https://mfaruk.com/admin/settings
- Backup: https://mfaruk.com/admin/backup
- Blog Posts: https://mfaruk.com/admin/posts
- Contacts: https://mfaruk.com/admin/contacts

---

## Deployment Commands

**⚠️ IMPORTANT: Use git-based deployment, NOT rsync of local files**

```bash
# Step 1: On local machine - commit and push changes
cd "/home/mmhf/Photography Portfolio Website/portfolio-app"
git add .
git commit -m "Description of changes"
git push origin main

# Step 2: On server - pull from git and rebuild
ssh root@63.142.240.72
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app

# Pull latest code
git pull origin main

# Install/update dependencies if needed
composer install --no-dev --optimize-autoloader

# Rebuild frontend assets
npm run build

# Clear and rebuild caches
rm -f bootstrap/cache/*.php
sudo -u mfaruk php artisan config:cache
sudo -u mfaruk php artisan route:cache
sudo -u mfaruk php artisan view:cache

# Run migrations if needed
sudo -u mfaruk php artisan migrate --force

# Fix permissions
chown -R mfaruk:mfaruk .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache

# Restart queue worker if needed
supervisorctl restart portfolio-worker:*
```

### Generate Placeholders for Existing Photos
```bash
# Run after deploying LQIP feature to backfill existing photos
ssh root@63.142.240.72
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
sudo -u mfaruk php artisan photos:generate-placeholders --force
```

---

## Database Tables Added

- `contacts` - Contact form submissions
- `posts` - Blog posts
- `client_selections` - Photo selections for proofing
- `jobs` / `failed_jobs` / `job_batches` - Queue tables (Laravel default)

### Photos Table New Columns
- `password` (on galleries table)
- `processing_stage`, `processing_error`
- `dominant_color`, `blurhash` - LQIP placeholder data

---

## What's Next (Future Enhancements)

If you want to continue development, here are potential enhancements:
1. Email notifications for contact form and print inquiries
2. Stripe/PayPal integration for direct print purchases
3. Multi-language support
4. Advanced search with filters
5. Photo licensing options
6. Portfolio PDF export

---

## Troubleshooting

### Queue not processing
```bash
ssh root@63.142.240.72
supervisorctl status
tail -f /home/mfaruk/web/mfaruk.com/private/portfolio-app/storage/logs/worker.log
```

### 500 errors
```bash
# Check Laravel log
tail -f /home/mfaruk/web/mfaruk.com/private/portfolio-app/storage/logs/laravel.log

# Fix permissions
chown -R mfaruk:mfaruk /home/mfaruk/web/mfaruk.com/private/portfolio-app
chmod -R 775 storage bootstrap/cache
```

### Cache issues
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```
