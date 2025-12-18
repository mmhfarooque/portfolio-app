<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSelection extends Model
{
    protected $fillable = [
        'session_id',
        'photo_id',
        'gallery_id',
        'note',
    ];

    /**
     * Get the photo for this selection.
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /**
     * Get the gallery for this selection.
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /**
     * Scope for current session.
     */
    public function scopeForSession($query, ?string $sessionId = null)
    {
        return $query->where('session_id', $sessionId ?? session()->getId());
    }

    /**
     * Check if a photo is selected by the current session.
     */
    public static function isSelected(int $photoId, ?string $sessionId = null): bool
    {
        return static::where('session_id', $sessionId ?? session()->getId())
            ->where('photo_id', $photoId)
            ->exists();
    }

    /**
     * Get the count of selections for the current session.
     */
    public static function getSelectionCount(?string $sessionId = null): int
    {
        return static::where('session_id', $sessionId ?? session()->getId())->count();
    }

    /**
     * Toggle selection for a photo.
     */
    public static function toggleSelection(int $photoId, ?int $galleryId = null, ?string $sessionId = null): bool
    {
        $sessionId = $sessionId ?? session()->getId();

        $existing = static::where('session_id', $sessionId)
            ->where('photo_id', $photoId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // Unselected
        }

        static::create([
            'session_id' => $sessionId,
            'photo_id' => $photoId,
            'gallery_id' => $galleryId,
        ]);

        return true; // Selected
    }
}
