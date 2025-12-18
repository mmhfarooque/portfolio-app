<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralVisit extends Model
{
    protected $fillable = [
        'session_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'referer',
        'referer_domain',
        'landing_page',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'user_id',
        'converted',
        'conversion_type',
        'converted_at',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the user associated with this visit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for visits with UTM tracking.
     */
    public function scopeWithUtm($query)
    {
        return $query->whereNotNull('utm_source');
    }

    /**
     * Scope for converted visits.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    /**
     * Scope for visits within date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Mark as converted.
     */
    public function markConverted(string $type): void
    {
        $this->update([
            'converted' => true,
            'conversion_type' => $type,
            'converted_at' => now(),
        ]);
    }

    /**
     * Get the referral visit for the current session.
     */
    public static function current(): ?self
    {
        return static::where('session_id', session()->getId())->first();
    }

    /**
     * Parse user agent to extract device info.
     */
    public static function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';

        // Device detection
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop)/i', $userAgent)) {
            $deviceType = 'mobile';
        }

        // Browser detection
        if (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        // OS detection
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        }

        return compact('deviceType', 'browser', 'os');
    }

    /**
     * Extract domain from referer URL.
     */
    public static function extractDomain(?string $referer): ?string
    {
        if (empty($referer)) {
            return null;
        }

        $parsed = parse_url($referer);
        return $parsed['host'] ?? null;
    }
}
