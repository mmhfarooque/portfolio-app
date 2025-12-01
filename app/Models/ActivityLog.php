<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'type',
        'level',
        'action',
        'message',
        'context',
        'user_id',
        'loggable_type',
        'loggable_id',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'duration_ms',
        'memory_usage',
        'exception_class',
        'exception_message',
        'exception_trace',
        'file',
        'line',
    ];

    protected $casts = [
        'context' => 'array',
        'duration_ms' => 'float',
        'memory_usage' => 'integer',
        'line' => 'integer',
    ];

    // Log types
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_INFO = 'info';
    const TYPE_DEBUG = 'debug';
    const TYPE_ACTIVITY = 'activity';

    // Log levels
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    /**
     * Get the user who triggered this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeOfLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter errors only.
     */
    public function scopeErrors($query)
    {
        return $query->where('type', self::TYPE_ERROR);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $from, $to = null)
    {
        $query->where('created_at', '>=', $from);
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Get a human-readable level badge color.
     */
    public function getLevelColorAttribute(): string
    {
        return match ($this->level) {
            self::LEVEL_CRITICAL => 'red',
            self::LEVEL_ERROR => 'red',
            self::LEVEL_WARNING => 'yellow',
            self::LEVEL_NOTICE => 'blue',
            self::LEVEL_INFO => 'green',
            self::LEVEL_DEBUG => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get type badge color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ERROR => 'red',
            self::TYPE_WARNING => 'yellow',
            self::TYPE_INFO => 'blue',
            self::TYPE_DEBUG => 'gray',
            self::TYPE_ACTIVITY => 'green',
            default => 'gray',
        };
    }

    /**
     * Clean old logs (keep last 30 days by default).
     */
    public static function cleanup(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
