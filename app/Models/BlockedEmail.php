<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedEmail extends Model
{
    protected $fillable = [
        'email',
        'reason',
        'blocked_by',
    ];

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Check if an email is blocked.
     */
    public static function isBlocked(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }

    /**
     * Block an email address.
     */
    public static function blockEmail(string $email, ?string $reason = null, ?int $blockedBy = null): self
    {
        return static::firstOrCreate(
            ['email' => strtolower(trim($email))],
            ['reason' => $reason, 'blocked_by' => $blockedBy]
        );
    }

    /**
     * Unblock an email address.
     */
    public static function unblockEmail(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->delete() > 0;
    }
}
