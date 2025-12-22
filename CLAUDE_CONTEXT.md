# Claude Context - Photography Portfolio Project

> **This file is for Claude AI agents to understand the project context.**
> Put this file in your project root and tell Claude: "Read CLAUDE_CONTEXT.md and continue from where we left off"

---

## Quick Start for Claude

When starting a session, Claude should:
1. Read this file first
2. Check `git log --oneline -5` for recent changes
3. Run `git status` to see current state
4. Ask the user what they want to work on

---

## Project Identity

| Key | Value |
|-----|-------|
| **Project Name** | Photography Portfolio Website |
| **Owner** | M M H Farooque |
| **Production URL** | https://mfaruk.com |
| **Repository** | https://github.com/mmhfarooque/portfolio-app.git |
| **Branch** | main |

---

## Tech Stack

### Backend
- **Framework:** Laravel 12.x (latest)
- **PHP:** 8.2+
- **Database:** SQLite (local), MySQL (production optional)
- **Queue:** Database driver with Supervisor

### Frontend
- **JS Framework:** Vue 3.5+ (Composition API with `<script setup>`)
- **SPA Bridge:** Inertia.js v2
- **Routing (JS):** Ziggy (Laravel routes in JS)
- **Build Tool:** Vite 7
- **CSS:** Tailwind CSS 3.x
- **NO Alpine.js** - Use Vue 3 for all interactivity

### Key Packages
```
# PHP (composer.json)
- inertiajs/inertia-laravel: ^2.0
- intervention/image-laravel: ^1.5
- tightenco/ziggy: ^2.6

# JS (package.json)
- vue: ^3.5.26
- @inertiajs/vue3: ^2.3.4
- @vitejs/plugin-vue: ^6.0.3
- ziggy-js: ^2.6.0
- tailwindcss: ^3.1.0
```

---

## Server Access

### Production Server
```
Host: 63.142.240.72
User: root
SSH: ssh root@63.142.240.72
Control Panel: Hestia
```

### Application Paths (Production)
```
App Root: /home/mfaruk/web/mfaruk.com/private/portfolio-app
Public:   /home/mfaruk/web/mfaruk.com/public_html (symlinked)
Logs:     /home/mfaruk/web/mfaruk.com/private/portfolio-app/storage/logs/
PHP-FPM:  User mfaruk:mfaruk
```

### FTP (if needed)
```
Host: ftp.mfaruk.com
Protocol: SFTP preferred (use SSH credentials)
```

---

## Git Workflow

### Repository
```bash
# Clone (for new machine)
git clone https://github.com/mmhfarooque/portfolio-app.git

# Check status
git status

# Before starting work
git pull

# After finishing work
git add .
git commit -m "Description of changes"
git push
```

### Important
- Always pull before starting
- Always push after finishing
- Commit messages should be descriptive
- Never commit `.env` files

---

## Local Development Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm

### First Time Setup
```bash
cd portfolio-app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

### Daily Development
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (hot reload)
npm run dev
```

### URLs
- Local: http://localhost:8000
- Vite: http://localhost:5173

---

## Project Structure

```
portfolio-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/           # Admin panel controllers
│   │   │   ├── PhotoController.php
│   │   │   ├── SettingController.php
│   │   │   └── BackupController.php
│   │   ├── FrontPageController.php  # Public pages
│   │   └── PrintController.php
│   ├── Models/
│   │   ├── Photo.php        # Main photo model
│   │   ├── Category.php
│   │   ├── Setting.php      # Key-value settings
│   │   └── Post.php         # Blog posts
│   ├── Jobs/
│   │   └── ProcessPhotoUpload.php   # Background processing
│   └── Services/
│       ├── PhotoProcessingService.php
│       ├── LoggingService.php
│       └── PrintService.php
├── resources/
│   ├── js/
│   │   ├── app.js           # Vue/Inertia bootstrap
│   │   ├── Components/      # Reusable Vue components
│   │   ├── Layouts/
│   │   │   ├── AuthenticatedLayout.vue  # Admin layout
│   │   │   └── PublicLayout.vue         # Public layout
│   │   └── Pages/
│   │       ├── Admin/       # Admin Vue pages
│   │       └── Public/      # Public Vue pages
│   └── views/
│       └── app.blade.php    # Main Blade template
├── routes/
│   └── web.php              # All routes
├── config/
│   └── themes.php           # Theme configuration
├── database/
│   ├── migrations/
│   └── database.sqlite      # Local SQLite database
└── storage/
    └── app/public/photos/   # Uploaded photos
```

---

## Key Patterns & Rules

### 1. Vue 3 Composition API Only
```vue
<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const open = ref(false);
</script>

<template>
  <div>...</div>
</template>
```

### 2. No Alpine.js
All interactivity must use Vue 3. For dropdowns, modals, etc., use:
- `ref()` for reactive state
- `v-show` or `v-if` for visibility
- `@click` for event handling
- Click-outside detection with `onMounted/onUnmounted`

### 3. Inertia.js Forms
```vue
const form = useForm({
  name: '',
  email: '',
});

const submit = () => {
  form.post(route('settings.update'));
};
```

### 4. Laravel Routes in Vue (Ziggy)
```vue
import { route } from 'ziggy-js';

// In template
<Link :href="route('photos.show', photo.id)">View</Link>
```

### 5. Settings System
```php
// Get setting
$value = Setting::get('profile_name');

// Set setting
Setting::set('profile_name', 'John Doe', 'profile');
```

### 6. Image Processing
Uses Intervention Image:
```php
use Intervention\Image\Laravel\Facades\Image;

$image = Image::read($file->getRealPath());
$image->scale(width: 500);
$image->save($path);
```

---

## Completed Features (11)

1. **Modal Image Viewer** - Click to zoom, keyboard nav
2. **Download Options** - WebP/JPEG formats
3. **Enhanced Contact Form** - Honeypot spam protection
4. **Photo View Analytics** - Dashboard stats
5. **Password-Protected Galleries** - Session-based
6. **Client Proofing System** - Cookie-based selections
7. **Blog/Stories Section** - Full CRUD with Markdown
8. **Queue Processing** - Background photo processing
9. **Backblaze B2 Backup** - Incremental backups
10. **Cloudflare CDN Integration** - CDN-aware URLs
11. **Printful Print Store** - Print inquiry form

---

## Common Commands

### Development
```bash
# Serve app
php artisan serve

# Vite dev (hot reload)
npm run dev

# Build for production
npm run build

# Clear all caches
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear

# Run migrations
php artisan migrate

# Fresh database with seeds
php artisan migrate:fresh --seed
```

### Deployment
```bash
# From local - rsync to server
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' \
  --exclude='storage/logs/*' --exclude='.env' \
  ./ root@63.142.240.72:/home/mfaruk/web/mfaruk.com/private/portfolio-app/

# On server after deploy
ssh root@63.142.240.72
cd /home/mfaruk/web/mfaruk.com/private/portfolio-app
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R mfaruk:mfaruk .
chmod -R 775 storage bootstrap/cache
supervisorctl restart portfolio-worker:*
```

---

## Environment Variables

### Required (.env)
```env
APP_NAME="Photography Portfolio"
APP_ENV=local
APP_KEY=base64:xxxxx
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

QUEUE_CONNECTION=database
```

### Optional (.env)
```env
# Backblaze B2 Backup
B2_ACCESS_KEY_ID=
B2_SECRET_ACCESS_KEY=
B2_BUCKET=
B2_REGION=us-west-004
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com

# CDN
CDN_URL=https://your-cdn-domain.com

# Printful
PRINTFUL_API_KEY=
```

---

## Troubleshooting

### Build errors
```bash
rm -rf node_modules
npm install
npm run build
```

### Database issues
```bash
php artisan migrate:fresh --seed
```

### Permission issues (server)
```bash
chown -R mfaruk:mfaruk /home/mfaruk/web/mfaruk.com/private/portfolio-app
chmod -R 775 storage bootstrap/cache
```

### Queue not working (server)
```bash
supervisorctl status
supervisorctl restart portfolio-worker:*
tail -f storage/logs/worker.log
```

---

## Key URLs

### Public
- Home: /
- Gallery: /photos
- Photo Detail: /photo/{slug}
- Blog: /blog
- Print Options: /photo/{slug}/print
- Selections: /selections

### Admin (requires auth)
- Dashboard: /dashboard
- Photos: /admin/photos
- Settings: /admin/settings
- Backup: /admin/backup
- Blog Posts: /admin/posts
- Contacts: /admin/contacts

---

## Future Enhancements (Ideas)

- Email notifications for contact/print inquiries
- Stripe/PayPal for direct purchases
- Multi-language support
- Advanced search filters
- Photo licensing options
- Portfolio PDF export

---

## Last Synced

**Date:** December 22, 2025
**From:** Ubuntu Desktop (mmhf)
**Commit:** Work in progress - syncing for laptop development

---

## Notes for Claude

1. **Check Laravel docs first** - Laravel 12 has many built-in features
2. **Use Vue 3 Composition API** - No Options API, no Alpine.js
3. **Follow existing patterns** - Check similar files before creating new ones
4. **Test locally before suggesting deployment**
5. **Read DEVELOPMENT.md** for detailed coding guidelines
6. **Read PROGRESS_SUMMARY.md** for feature status (in parent directory)
