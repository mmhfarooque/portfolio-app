<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'sort_order',
        'photo_count',
    ];

    /**
     * Get the photos for this category.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * Get published photos for this category.
     */
    public function publishedPhotos(): HasMany
    {
        return $this->hasMany(Photo::class)->where('status', 'published');
    }

    /**
     * Update the photo count for this category.
     */
    public function updatePhotoCount(): void
    {
        $this->update(['photo_count' => $this->publishedPhotos()->count()]);
    }
}
