<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Series extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'story',
        'cover_image',
        'project_date',
        'location',
        'status',
        'is_featured',
        'sort_order',
        'user_id',
        'views',
        'seo_title',
        'meta_description',
    ];

    protected $casts = [
        'project_date' => 'date',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the user that created the series.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the photos in this series.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'photo_series')
            ->withPivot(['sort_order', 'caption'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Scope for published series.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured series.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for draft series.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Increment views.
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get cover image URL.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }

        // Fall back to first photo's thumbnail
        $firstPhoto = $this->photos()->first();
        return $firstPhoto?->thumbnail_url;
    }

    /**
     * Get photo count.
     */
    public function getPhotoCountAttribute(): int
    {
        return $this->photos()->count();
    }

    /**
     * Check if series has photos.
     */
    public function hasPhotos(): bool
    {
        return $this->photos()->exists();
    }
}
