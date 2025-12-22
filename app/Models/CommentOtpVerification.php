<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentOtpVerification extends Model
{
    protected $fillable = [
        'photo_id',
        'parent_id',
        'guest_name',
        'guest_email',
        'content',
        'otp_code',
        'ip_address',
        'user_agent',
        'expires_at',
        'attempts',
        'verified',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    const MAX_ATTEMPTS = 5;
    const OTP_EXPIRY_MINUTES = 10;

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PhotoComment::class, 'parent_id');
    }

    /**
     * Generate a new OTP code.
     */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if max attempts reached.
     */
    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Increment attempts counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['verified' => true]);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(string $code): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->maxAttemptsReached()) {
            return false;
        }

        if ($this->otp_code !== $code) {
            $this->incrementAttempts();
            return false;
        }

        $this->markAsVerified();
        return true;
    }

    /**
     * Clean up expired verifications.
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())
            ->where('verified', false)
            ->delete();
    }
}
