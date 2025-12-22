<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhotoComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'photo_id',
        'parent_id',
        'guest_name',
        'guest_email',
        'user_id',
        'content',
        'status',
        'approved_at',
        'approved_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SPAM = 'spam';

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PhotoComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PhotoComment::class, 'parent_id');
    }

    public function approvedReplies(): HasMany
    {
        return $this->hasMany(PhotoComment::class, 'parent_id')
            ->where('status', self::STATUS_APPROVED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors
    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Anonymous';
    }

    public function getAuthorEmailAttribute(): string
    {
        return $this->user?->email ?? $this->guest_email ?? '';
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->user_id !== null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_SPAM => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Methods
    public function approve(int $adminId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $adminId,
        ]);

        $this->photo->incrementApprovedCommentsCount();
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function markAsSpam(): void
    {
        $this->update(['status' => self::STATUS_SPAM]);
    }
}
