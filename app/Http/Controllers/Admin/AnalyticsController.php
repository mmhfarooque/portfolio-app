<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display referral analytics dashboard.
     */
    public function referrals(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Total visits
        $totalVisits = ReferralVisit::dateRange($startDate, now())->count();

        // Visits with UTM tracking
        $utmVisits = ReferralVisit::dateRange($startDate, now())->withUtm()->count();

        // Conversions
        $conversions = ReferralVisit::dateRange($startDate, now())->converted()->count();
        $conversionRate = $totalVisits > 0 ? round(($conversions / $totalVisits) * 100, 2) : 0;

        // Top sources
        $topSources = ReferralVisit::dateRange($startDate, now())
            ->select('utm_source', DB::raw('count(*) as visits'), DB::raw('sum(converted) as conversions'))
            ->whereNotNull('utm_source')
            ->groupBy('utm_source')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Top referrers
        $topReferrers = ReferralVisit::dateRange($startDate, now())
            ->select('referer_domain', DB::raw('count(*) as visits'), DB::raw('sum(converted) as conversions'))
            ->whereNotNull('referer_domain')
            ->groupBy('referer_domain')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Top campaigns
        $topCampaigns = ReferralVisit::dateRange($startDate, now())
            ->select('utm_campaign', 'utm_source', 'utm_medium', DB::raw('count(*) as visits'), DB::raw('sum(converted) as conversions'))
            ->whereNotNull('utm_campaign')
            ->groupBy('utm_campaign', 'utm_source', 'utm_medium')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Daily visits for chart
        $dailyVisits = ReferralVisit::dateRange($startDate, now())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as visits'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('visits', 'date')
            ->toArray();

        // Device breakdown
        $deviceBreakdown = ReferralVisit::dateRange($startDate, now())
            ->select('device_type', DB::raw('count(*) as count'))
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        // Browser breakdown
        $browserBreakdown = ReferralVisit::dateRange($startDate, now())
            ->select('browser', DB::raw('count(*) as count'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'browser')
            ->toArray();

        // Top landing pages
        $topLandingPages = ReferralVisit::dateRange($startDate, now())
            ->select('landing_page', DB::raw('count(*) as visits'))
            ->groupBy('landing_page')
            ->orderByDesc('visits')
            ->limit(10)
            ->pluck('visits', 'landing_page')
            ->toArray();

        // Conversion by type
        $conversionsByType = ReferralVisit::dateRange($startDate, now())
            ->select('conversion_type', DB::raw('count(*) as count'))
            ->where('converted', true)
            ->whereNotNull('conversion_type')
            ->groupBy('conversion_type')
            ->pluck('count', 'conversion_type')
            ->toArray();

        return view('admin.analytics.referrals', compact(
            'days',
            'totalVisits',
            'utmVisits',
            'conversions',
            'conversionRate',
            'topSources',
            'topReferrers',
            'topCampaigns',
            'dailyVisits',
            'deviceBreakdown',
            'browserBreakdown',
            'topLandingPages',
            'conversionsByType'
        ));
    }
}
