<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'photo_id',
        'post_id',
        'platform',
        'status',
        'caption',
        'hashtags',
        'external_id',
        'external_url',
        'scheduled_at',
        'published_at',
        'error_message',
        'engagement',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'engagement' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function markAsPublished(string $externalId = null, string $externalUrl = null): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'external_id' => $externalId,
            'external_url' => $externalUrl,
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }

    public function getContent(): ?object
    {
        return $this->photo ?? $this->post;
    }

    public function getImageUrl(): ?string
    {
        if ($this->photo) {
            return $this->photo->getImageUrl('large');
        }

        if ($this->post && $this->post->hasFeaturedImage()) {
            return $this->post->featured_image_url;
        }

        return null;
    }
}
