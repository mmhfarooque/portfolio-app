<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Photo extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'story',
        'original_path',
        'display_path',
        'thumbnail_path',
        'watermarked_path',
        'exif_data',
        'latitude',
        'longitude',
        'location_name',
        'width',
        'height',
        'file_size',
        'mime_type',
        'original_filename',
        'user_id',
        'category_id',
        'gallery_id',
        'status',
        'is_featured',
        'views',
        'captured_at',
    ];

    protected $casts = [
        'exif_data' => 'array',
        'is_featured' => 'boolean',
        'captured_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the user that uploaded the photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this photo.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the gallery for this photo.
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /**
     * Get the tags for this photo.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Scope for published photos.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured photos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for draft photos.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for photos with GPS coordinates.
     */
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    /**
     * Check if photo has location data.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Increment the view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get formatted EXIF data for display.
     */
    public function getFormattedExifAttribute(): array
    {
        $exif = $this->exif_data ?? [];

        return [
            'camera' => $exif['Make'] ?? null ? ($exif['Make'] . ' ' . ($exif['Model'] ?? '')) : null,
            'aperture' => isset($exif['FNumber']) ? 'f/' . $exif['FNumber'] : null,
            'shutter_speed' => $exif['ExposureTime'] ?? null,
            'iso' => isset($exif['ISOSpeedRatings']) ? 'ISO ' . $exif['ISOSpeedRatings'] : null,
            'focal_length' => isset($exif['FocalLength']) ? $exif['FocalLength'] . 'mm' : null,
            'date_taken' => $exif['DateTimeOriginal'] ?? null,
        ];
    }
}
