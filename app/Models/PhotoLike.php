<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoLike extends Model
{
    protected $fillable = [
        'photo_id',
        'session_id',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a like exists for this session/IP on a photo.
     */
    public static function hasLiked(int $photoId, string $sessionId, string $ipAddress): bool
    {
        return static::where('photo_id', $photoId)
            ->where(function ($query) use ($sessionId, $ipAddress) {
                $query->where('session_id', $sessionId)
                    ->orWhere('ip_address', $ipAddress);
            })
            ->exists();
    }
}
