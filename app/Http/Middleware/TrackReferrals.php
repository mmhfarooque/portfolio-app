<?php

namespace App\Http\Middleware;

use App\Models\ReferralVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferrals
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-GET requests or AJAX
        if (!$request->isMethod('GET') || $request->ajax()) {
            return $next($request);
        }

        // Skip if already tracked this session
        if (session('referral_tracked')) {
            return $next($request);
        }

        // Skip admin routes
        if ($request->is('admin/*') || $request->is('dashboard')) {
            return $next($request);
        }

        // Check if there's any UTM params or referer to track
        $hasUtm = $request->has('utm_source') ||
                  $request->has('utm_medium') ||
                  $request->has('utm_campaign');
        $referer = $request->header('referer');
        $refererDomain = ReferralVisit::extractDomain($referer);

        // Skip if no UTM and referer is same domain
        $currentDomain = $request->getHost();
        if (!$hasUtm && ($refererDomain === $currentDomain || empty($refererDomain))) {
            session(['referral_tracked' => true]);
            return $next($request);
        }

        // Parse user agent
        $userAgent = $request->userAgent() ?? '';
        $deviceInfo = ReferralVisit::parseUserAgent($userAgent);

        // Every column is VARCHAR(255) and most of these values come from
        // outside (the Facebook iOS in-app browser UA alone is ~280 chars), so
        // cap them. Tracking must never take the page down, so a failed write
        // is reported and the visitor still gets the page.
        $cap = fn (?string $value): ?string => $value === null ? null : mb_substr($value, 0, 255);

        try {
            ReferralVisit::create([
                'session_id' => session()->getId(),
                'utm_source' => $cap($request->get('utm_source')),
                'utm_medium' => $cap($request->get('utm_medium')),
                'utm_campaign' => $cap($request->get('utm_campaign')),
                'utm_term' => $cap($request->get('utm_term')),
                'utm_content' => $cap($request->get('utm_content')),
                'referer' => $cap($referer),
                'referer_domain' => $cap($refererDomain),
                'landing_page' => $cap($request->path()),
                'ip_address' => $request->ip(),
                'user_agent' => $cap($userAgent),
                'device_type' => $deviceInfo['deviceType'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        session(['referral_tracked' => true]);

        return $next($request);
    }
}
