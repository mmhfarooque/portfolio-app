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

        // Create referral visit record
        ReferralVisit::create([
            'session_id' => session()->getId(),
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
            'referer' => $referer,
            'referer_domain' => $refererDomain,
            'landing_page' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => substr($userAgent, 0, 500),
            'device_type' => $deviceInfo['deviceType'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'user_id' => auth()->id(),
        ]);

        session(['referral_tracked' => true]);

        return $next($request);
    }
}
