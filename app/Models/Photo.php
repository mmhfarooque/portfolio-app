<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'likes_count',
        'comments_count',
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
     * Get the series this photo belongs to.
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class, 'photo_series')
            ->withPivot(['sort_order', 'caption'])
            ->withTimestamps();
    }

    /**
     * Get the likes for this photo.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PhotoLike::class);
    }

    /**
     * Get the comments for this photo.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PhotoComment::class);
    }

    /**
     * Get approved comments for this photo.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(PhotoComment::class)
            ->where('status', PhotoComment::STATUS_APPROVED);
    }

    /**
     * Get top-level approved comments for this photo.
     */
    public function topLevelApprovedComments(): HasMany
    {
        return $this->hasMany(PhotoComment::class)
            ->where('status', PhotoComment::STATUS_APPROVED)
            ->whereNull('parent_id');
    }

    /**
     * Check if photo has been liked by session/IP.
     */
    public function hasBeenLikedBy(string $sessionId, string $ipAddress): bool
    {
        return PhotoLike::hasLiked($this->id, $sessionId, $ipAddress);
    }

    /**
     * Increment the likes count.
     */
    public function incrementLikesCount(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Decrement the likes count.
     */
    public function decrementLikesCount(): void
    {
        if ($this->likes_count > 0) {
            $this->decrement('likes_count');
        }
    }

    /**
     * Increment the approved comments count.
     */
    public function incrementApprovedCommentsCount(): void
    {
        $this->increment('comments_count');
    }

    /**
     * Decrement the approved comments count.
     */
    public function decrementApprovedCommentsCount(): void
    {
        if ($this->comments_count > 0) {
            $this->decrement('comments_count');
        }
    }

    /**
     * Sync the comments count with actual approved comments.
     */
    public function syncCommentsCount(): void
    {
        $this->update([
            'comments_count' => $this->approvedComments()->count(),
        ]);
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
            'lens' => $exif['LensModel'] ?? $exif['Lens'] ?? $exif['LensInfo'] ?? null,
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
            'queued' => 'Queued',
            'converting' => 'Converting format...',
            'extracting_metadata' => 'Reading EXIF data...',
            'reading_image' => 'Reading image...',
            'generating_versions' => 'Creating thumbnails...',
            'generating_hashes' => 'Creating hash...',
            'ai_analysis' => 'AI analysis...',
            'error' => 'Failed',
            null => null,
            default => 'Processing...',
        };
    }

    /**
     * Get elapsed processing time in human-readable format.
     */
    public function getProcessingElapsedAttribute(): ?string
    {
        if ($this->status !== 'processing') {
            return null;
        }

        $seconds = now()->diffInSeconds($this->updated_at);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes . 'm ' . $remainingSeconds . 's';
    }

    /**
     * Get total processing time (from creation to now or completion).
     */
    public function getProcessingDurationAttribute(): ?string
    {
        if ($this->status !== 'processing') {
            return null;
        }

        $seconds = now()->diffInSeconds($this->created_at);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes . 'm ' . $remainingSeconds . 's';
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
