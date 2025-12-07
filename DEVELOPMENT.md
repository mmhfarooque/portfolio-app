# Development Guidelines

This document outlines development patterns, guidelines, and best practices for the Photography Portfolio project.

## IMPORTANT: Laravel-First Development

**Before implementing ANY new functionality, ALWAYS check Laravel's official documentation first.**

Laravel frequently adds new features that may already solve your problem. Check:
- https://laravel.com/docs/12.x/releases (What's new)
- https://laravel.com/docs/12.x (Full documentation)
- https://laravel-news.com (Latest updates)

### Why This Matters

1. **Built-in features are tested and optimized** - Laravel's core team maintains them
2. **Reduces dependencies** - Less third-party packages to maintain
3. **Better performance** - Native features are optimized for the framework
4. **Easier upgrades** - Native features are maintained across versions
5. **Consistent patterns** - Follows Laravel conventions other developers know

### Checklist Before Adding New Code

- [ ] Is this functionality already in Laravel? (Check docs)
- [ ] Is there a Laravel helper for this? (Check `Illuminate\Support` helpers)
- [ ] Can Eloquent handle this? (Check relationships, scopes, accessors)
- [ ] Does Laravel have a facade for this? (Check available facades)
- [ ] Is there a recent Laravel release that added this? (Check changelog)

---

## Laravel 12 Features We Use

### 1. Automatic Eager Loading (12.8+)

Enabled globally in `AppServiceProvider`:

```php
Model::automaticallyEagerLoadRelationships();
```

This prevents N+1 query problems without needing `with()` everywhere.

**Before (manual):**
```php
$photos = Photo::with(['category', 'tags', 'user'])->get();
```

**After (automatic):**
```php
$photos = Photo::all(); // Relationships loaded automatically when accessed
```

### 2. Number Helper

Use Laravel's Number facade for formatting:

```php
use Illuminate\Support\Number;

Number::fileSize(1024);           // "1 KB"
Number::fileSize(1048576);        // "1 MB"
Number::abbreviate(1000);         // "1K"
Number::forHumans(1000);          // "1 thousand"
Number::percentage(0.75);         // "75%"
Number::currency(100);            // "$100.00"
```

> **Note:** `Number::fileSize()` and some other Number methods require the PHP `intl` extension.
> If not available, use manual formatting: `number_format($bytes / 1024 / 1024, 2) . ' MB'`

### 3. String Helpers

```php
use Illuminate\Support\Str;

Str::slug('Hello World');         // "hello-world"
Str::uuid();                      // UUID string
Str::headline('hello_world');     // "Hello World"
Str::mask('secret', '*', 2);      // "se****"
```

### 4. URI Helper (12.x)

```php
$uri = uri('https://example.com')
    ->withPath('/photos')
    ->withQuery(['page' => 1]);
```

### 5. Concurrency & Defer

For background tasks after response:

```php
use Illuminate\Support\Facades\Concurrency;

// Execute after HTTP response sent
Concurrency::defer([
    fn() => $this->generateThumbnail($photo),
    fn() => $this->sendNotification($user),
]);
```

---

## Code Patterns

### Controller Responses

Always return JSON for AJAX requests:

```php
public function update(Request $request)
{
    // ... update logic ...

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Updated successfully.',
            'data' => $result,
        ]);
    }

    return redirect()->route('...')
        ->with('success', 'Updated successfully.');
}
```

### Settings Management

Settings are stored in the `settings` table with key-value pairs grouped by category:

```php
// Get a setting
$value = Setting::get('profile_name');

// Set a setting
Setting::set('profile_name', 'John Doe', 'profile');

// Clear cache after updates
Setting::clearCache();
```

### File Uploads

Store files in categorized directories:

```php
// Photos go to: storage/app/public/photos/
// Settings images: storage/app/public/settings/
// Thumbnails: storage/app/public/photos/thumbnails/
```

### Image Processing

Use Intervention Image for processing:

```php
use Intervention\Image\Laravel\Facades\Image;

$image = Image::read($file->getRealPath());
$image->scale(width: 500);
$image->toPng()->save($path);
```

---

## Frontend Patterns

### Alpine.js Components

Use `x-data` with methods for interactive components:

```html
<div x-data="{
    open: false,
    toggle() { this.open = !this.open },
    async save() {
        const response = await fetch(...);
        // handle response
    }
}">
```

### Section Save Buttons

Use the `<x-section-save-button />` component for AJAX saves within forms:

```html
<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    <div class="bg-white p-6">
        <div class="flex justify-between">
            <h3>Section Title</h3>
            <x-section-save-button />
        </div>
        <!-- form fields -->
    </div>
</form>
```

### Media Picker

Use the `<x-media-picker />` component for image selection:

```html
<x-media-picker
    name="profile_image"
    label="Profile Photo"
    :current-image="$currentImage"
    :value="$imagePath"
    preview-class="w-32 h-32 rounded-full"
/>
```

---

## Database Patterns

### Migrations

Use Laravel's migration features:

```php
$table->id();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('status')->default('draft');
$table->timestamps();
$table->softDeletes(); // if needed
```

### Model Scopes

Define reusable query scopes:

```php
// In Model
public function scopePublished($query)
{
    return $query->where('status', 'published');
}

public function scopeFeatured($query)
{
    return $query->where('is_featured', true);
}

// Usage
Photo::published()->featured()->get();
```

### Accessors (Laravel 12 style)

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function formattedSize(): Attribute
{
    return Attribute::get(
        fn () => Number::fileSize($this->file_size)
    );
}
```

---

## Testing

Run tests before deploying:

```bash
php artisan test
```

---

## Common Tasks Reference

### Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Assets

```bash
npm run build
```

### Database Reset (Development Only)

```bash
php artisan migrate:fresh --seed
```

---

## AI Agent Instructions

When working on this project, AI agents should:

1. **Check Laravel docs first** before implementing new features
2. **Use native Laravel features** over custom implementations
3. **Follow existing patterns** in the codebase
4. **Update this documentation** when adding new patterns
5. **Test changes locally** before suggesting deployment

### Version Awareness

- Current Laravel version: **12.x**
- Always check for features added in recent minor versions
- Refer to https://laravel.com/docs/12.x/releases for new features

### Key Files to Know

- `app/Providers/AppServiceProvider.php` - Global configurations
- `app/Models/Setting.php` - Settings management
- `resources/views/components/` - Reusable Blade components
- `DEPLOYMENT.md` - Server deployment guide

---

## Changelog

### 2025-12-07
- Enabled automatic eager loading (Laravel 12.8+)
- Added Number helper usage for file sizes
- Created development guidelines documentation
