<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Referral Analytics
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.analytics.referrals', ['days' => 7]) }}"
                   class="px-3 py-1 text-sm rounded {{ $days == 7 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    7 Days
                </a>
                <a href="{{ route('admin.analytics.referrals', ['days' => 30]) }}"
                   class="px-3 py-1 text-sm rounded {{ $days == 30 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    30 Days
                </a>
                <a href="{{ route('admin.analytics.referrals', ['days' => 90]) }}"
                   class="px-3 py-1 text-sm rounded {{ $days == 90 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                    90 Days
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Total Visits</div>
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($totalVisits) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">UTM Tracked</div>
                    <div class="text-3xl font-bold text-indigo-600">{{ number_format($utmVisits) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Conversions</div>
                    <div class="text-3xl font-bold text-green-600">{{ number_format($conversions) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Conversion Rate</div>
                    <div class="text-3xl font-bold text-blue-600">{{ $conversionRate }}%</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Sources -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Traffic Sources</h3>
                    </div>
                    <div class="p-6">
                        @if($topSources->count() > 0)
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500">
                                        <th class="pb-3">Source</th>
                                        <th class="pb-3 text-right">Visits</th>
                                        <th class="pb-3 text-right">Conv.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($topSources as $source)
                                        <tr>
                                            <td class="py-2 font-medium">{{ $source->utm_source ?? 'Direct' }}</td>
                                            <td class="py-2 text-right">{{ number_format($source->visits) }}</td>
                                            <td class="py-2 text-right text-green-600">{{ $source->conversions ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-center py-4">No UTM tracked visits yet</p>
                        @endif
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Referrers</h3>
                    </div>
                    <div class="p-6">
                        @if($topReferrers->count() > 0)
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500">
                                        <th class="pb-3">Domain</th>
                                        <th class="pb-3 text-right">Visits</th>
                                        <th class="pb-3 text-right">Conv.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($topReferrers as $referrer)
                                        <tr>
                                            <td class="py-2 font-medium truncate max-w-xs">{{ $referrer->referer_domain }}</td>
                                            <td class="py-2 text-right">{{ number_format($referrer->visits) }}</td>
                                            <td class="py-2 text-right text-green-600">{{ $referrer->conversions ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-center py-4">No referrer data yet</p>
                        @endif
                    </div>
                </div>

                <!-- Top Campaigns -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Campaigns</h3>
                    </div>
                    <div class="p-6">
                        @if($topCampaigns->count() > 0)
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500">
                                        <th class="pb-3">Campaign</th>
                                        <th class="pb-3">Source/Medium</th>
                                        <th class="pb-3 text-right">Visits</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($topCampaigns as $campaign)
                                        <tr>
                                            <td class="py-2 font-medium">{{ $campaign->utm_campaign }}</td>
                                            <td class="py-2 text-sm text-gray-500">{{ $campaign->utm_source }}/{{ $campaign->utm_medium }}</td>
                                            <td class="py-2 text-right">{{ number_format($campaign->visits) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-center py-4">No campaign data yet</p>
                        @endif
                    </div>
                </div>

                <!-- Device Breakdown -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Device & Browser</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-8">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Devices</h4>
                                @foreach($deviceBreakdown as $device => $count)
                                    <div class="flex justify-between py-1">
                                        <span class="capitalize">{{ $device }}</span>
                                        <span class="font-medium">{{ number_format($count) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-3">Browsers</h4>
                                @foreach($browserBreakdown as $browser => $count)
                                    <div class="flex justify-between py-1">
                                        <span>{{ $browser }}</span>
                                        <span class="font-medium">{{ number_format($count) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Landing Pages -->
                <div class="bg-white rounded-lg shadow lg:col-span-2">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Landing Pages</h3>
                    </div>
                    <div class="p-6">
                        @if(count($topLandingPages) > 0)
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500">
                                        <th class="pb-3">Page</th>
                                        <th class="pb-3 text-right">Visits</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($topLandingPages as $page => $visits)
                                        <tr>
                                            <td class="py-2 font-medium">/{{ $page }}</td>
                                            <td class="py-2 text-right">{{ number_format($visits) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-center py-4">No landing page data yet</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
