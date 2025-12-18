<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Photo extends Model
{
    protected $fillable = [
        'title',
        'seo_title',
        'slug',
        'description',
        'meta_description',
        'story',
        'original_path',
        'display_path',
        'thumbnail_path',
        'watermarked_path',
        'before_display_path',
        'before_thumbnail_path',
        'exif_data',
        'latitude',
        'longitude',
        'location_name',
        'width',
        'height',
        'file_size',
        'image_hash',
        'file_hash',
        'mime_type',
        'original_filename',
        'user_id',
        'category_id',
        'gallery_id',
        'status',
        'is_featured',
        'views',
        'captured_at',
        'processing_stage',
        'processing_error',
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
     * Check if photo has a before image (for before/after comparison).
     */
    public function hasBeforeImage(): bool
    {
        return !empty($this->before_display_path);
    }

    /**
     * Increment the view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get the display image URL (with CDN support).
     */
    public function getDisplayUrlAttribute(): ?string
    {
        if (!$this->display_path) {
            return null;
        }
        return $this->getCdnUrl($this->display_path);
    }

    /**
     * Get the thumbnail URL (with CDN support).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return $this->getCdnUrl($this->thumbnail_path);
    }

    /**
     * Get the watermarked image URL (with CDN support).
     */
    public function getWatermarkedUrlAttribute(): ?string
    {
        if (!$this->watermarked_path) {
            return null;
        }
        return $this->getCdnUrl($this->watermarked_path);
    }

    /**
     * Get the primary display URL (watermarked if available, otherwise display).
     */
    public function getPrimaryUrlAttribute(): ?string
    {
        $path = $this->watermarked_path ?? $this->display_path;
        if (!$path) {
            return null;
        }
        return $this->getCdnUrl($path);
    }

    /**
     * Get the before image URL (with CDN support).
     */
    public function getBeforeDisplayUrlAttribute(): ?string
    {
        if (!$this->before_display_path) {
            return null;
        }
        return $this->getCdnUrl($this->before_display_path);
    }

    /**
     * Get URL with CDN support if configured.
     */
    protected function getCdnUrl(string $path): string
    {
        $cdnUrl = config('app.cdn_url');

        if ($cdnUrl) {
            return rtrim($cdnUrl, '/') . '/storage/' . $path;
        }

        return asset('storage/' . $path);
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

    /**
     * Check if photo is currently being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if photo processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get human-readable processing stage.
     */
    public function getProcessingStageTextAttribute(): ?string
    {
        return match ($this->processing_stage) {
            'reading_image' => 'Reading image...',
            'generating_versions' => 'Generating image versions...',
            'generating_hashes' => 'Creating image hash...',
            'ai_analysis' => 'Analyzing with AI...',
            'error' => 'Processing failed',
            null => null,
            default => 'Processing...',
        };
    }

    /**
     * Scope for processing photos.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for failed photos.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
