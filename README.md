# Photography Portfolio Website

A professional photography portfolio and CV/Resume website built with Laravel 12.

**Live Site:** [mfaruk.com](https://mfaruk.com)

## Tech Stack

- **Framework:** Laravel 12.x (PHP 8.2+)
- **Frontend:** Blade Templates, Alpine.js, Tailwind CSS
- **Database:** MySQL/MariaDB
- **Image Processing:** Intervention Image
- **Build Tool:** Vite

## Features

- Photo gallery with categories, tags, and galleries
- Automatic thumbnail and watermark generation
- EXIF data extraction and display
- GPS location mapping
- Professional CV/Resume front page
- Admin dashboard for content management
- Theme customization (light/dark modes)
- Media library for selecting photos in settings

## Laravel 12 Features in Use

This project leverages modern Laravel 12 features:

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
