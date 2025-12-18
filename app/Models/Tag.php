<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the photos with this tag.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class);
    }

    /**
     * Get published photos with this tag.
     */
    public function publishedPhotos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->where('status', 'published');
    }

    /**
     * Get the posts with this tag.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Get published posts with this tag.
     */
    public function publishedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->where('status', 'published');
    }
}
