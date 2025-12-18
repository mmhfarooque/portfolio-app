<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'tips',
        'best_times',
        'latitude',
        'longitude',
        'region',
        'country',
        'cover_image',
        'amenities',
        'difficulty',
        'status',
        'is_featured',
        'views',
        'seo_title',
        'meta_description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'amenities' => 'array',
        'is_featured' => 'boolean',
    ];

    /**
     * Get photos near this location (within ~10km radius).
     */
    public function getPhotosAttribute()
    {
        if (!$this->latitude || !$this->longitude) {
            return collect();
        }

        // Using Haversine formula for approximate distance
        $radius = 10; // km
        $lat = $this->latitude;
        $lng = $this->longitude;

        return Photo::published()
            ->withLocation()
            ->selectRaw("*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Get photo count.
     */
    public function getPhotoCountAttribute(): int
    {
        return $this->photos->count();
    }

    /**
     * Scope for published locations.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for featured locations.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for locations with coordinates.
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
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

        // Fall back to first nearby photo
        $firstPhoto = $this->photos->first();
        return $firstPhoto?->thumbnail_url;
    }

    /**
     * Get difficulty label.
     */
    public function getDifficultyLabelAttribute(): string
    {
        return match ($this->difficulty) {
            'easy' => 'Easy Access',
            'moderate' => 'Moderate',
            'challenging' => 'Challenging',
            default => 'Not specified',
        };
    }

    /**
     * Check if location has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
