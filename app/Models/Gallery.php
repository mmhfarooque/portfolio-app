<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Gallery extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'user_id',
        'is_published',
        'is_featured',
        'password',
        'sort_order',
        'expires_at',
        'client_name',
        'client_email',
        'access_token',
        'is_client_gallery',
        'allow_downloads',
        'allow_selections',
        'selection_limit',
        'client_notes',
        'last_accessed_at',
        'view_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_client_gallery' => 'boolean',
        'allow_downloads' => 'boolean',
        'allow_selections' => 'boolean',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the user that owns the gallery.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the photos in this gallery.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Scope for published galleries.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for featured galleries.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Check if gallery is password protected.
     */
    public function isPasswordProtected(): bool
    {
        return !empty($this->password);
    }

    /**
     * Set the gallery password (hash it).
     */
    public function setPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = null;
        }
    }

    /**
     * Verify the gallery password.
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Get the session key for this gallery's access.
     */
    public function getAccessSessionKey(): string
    {
        return "gallery_access_{$this->id}";
    }

    /**
     * Check if user has access to this gallery (via session).
     */
    public function hasAccess(): bool
    {
        if (!$this->isPasswordProtected()) {
            return true;
        }

        return session()->has($this->getAccessSessionKey());
    }

    /**
     * Grant access to this gallery (store in session).
     */
    public function grantAccess(): void
    {
        session()->put($this->getAccessSessionKey(), true);
    }

    /**
     * Generate a unique access token for client gallery.
     */
    public static function generateAccessToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }

    /**
     * Check if gallery has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if gallery is accessible (not expired, published if not client gallery).
     */
    public function isAccessible(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->is_client_gallery) {
            return true; // Client galleries are accessible via token
        }

        return $this->is_published;
    }

    /**
     * Get the share URL for client gallery.
     */
    public function getShareUrl(): ?string
    {
        if (!$this->access_token) {
            return null;
        }

        return route('client-gallery.view', ['token' => $this->access_token]);
    }

    /**
     * Record a view.
     */
    public function recordView(): void
    {
        $this->increment('view_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at);
    }

    /**
     * Scope for client galleries.
     */
    public function scopeClientGalleries($query)
    {
        return $query->where('is_client_gallery', true);
    }

    /**
     * Scope for non-expired galleries.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for expired galleries.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }
}
