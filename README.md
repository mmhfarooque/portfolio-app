# Photography Portfolio Website

A professional photography portfolio and CV/Resume website built with Laravel 13.

**Live Site:** [mfaruk.com](https://mfaruk.com)

> 🧠 **AI agents & developers — read [`MFARUK_WORKFLOW.md`](MFARUK_WORKFLOW.md) first.** It is the single operating brain: architecture, access & recovery, R2 photo sync, backups, deploy chain, gotchas, and boundaries.

## Tech Stack

- **Framework:** Laravel 13.x (PHP 8.3+, production on PHP 8.4)
- **Frontend:** Vue 3, Inertia.js 2 (with SSR), Tailwind CSS
- **Routing in JS:** Ziggy
- **Database:** MySQL/MariaDB
- **Image Processing:** Intervention Image 4
- **Object Storage:** Cloudflare R2 (S3-compatible, via Flysystem)
- **Build Tool:** Vite 7

## Features

- Photo gallery with categories, tags, and galleries
- Automatic thumbnail and watermark generation
- EXIF data extraction and display
- GPS location mapping
- Professional CV/Resume front page
- Admin dashboard for content management
- Theme customization (light/dark modes)
- Media library for selecting photos in settings

## Laravel Features in Use

Upgraded from Laravel 12 to Laravel 13 in August 2026. The project leverages modern Laravel features:

- **Automatic Eager Loading** (12.8+) - Prevents N+1 queries automatically
- **Number Helper** - For human-readable file sizes (`Number::fileSize()`)
- **Fluent Helpers** - For cleaner data manipulation

See [DEVELOPMENT.md](./DEVELOPMENT.md) for full development guidelines.

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Build assets
npm run build

# Start development server
php artisan serve
```

## Directory Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # Admin panel controllers
│   └── PageController  # Public pages
├── Models/             # Eloquent models
├── Services/           # Business logic services
└── Providers/          # Service providers

resources/
├── views/
│   ├── admin/          # Admin panel views
│   ├── components/     # Blade components
│   └── pages/          # Public page views
└── js/css/             # Frontend assets

storage/app/public/
├── photos/             # Uploaded photos
├── settings/           # Setting images (profile, etc.)
└── thumbnails/         # Generated thumbnails
```

## Deployment

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed deployment instructions to Hestia/cPanel servers.

## Documentation

- [DEPLOYMENT.md](./DEPLOYMENT.md) - Server deployment guide
- [DEVELOPMENT.md](./DEVELOPMENT.md) - Development guidelines and patterns

## License

This project is proprietary software.
