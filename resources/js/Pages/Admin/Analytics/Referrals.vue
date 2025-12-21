<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    days: Number,
    totalVisits: Number,
    utmVisits: Number,
    conversions: Number,
    conversionRate: Number,
    topSources: Array,
    topReferrers: Array,
    topCampaigns: Array,
    dailyVisits: Object,
    deviceBreakdown: Object,
    browserBreakdown: Object,
    topLandingPages: Object,
    conversionsByType: Object
});

const selectedDays = ref(props.days);

const changeDateRange = () => {
    router.get(route('admin.analytics.referrals'), {
        days: selectedDays.value,
    }, {
        preserveState: true,
    });
};

const formatPercent = (value, total) => {
    if (!total) return '0%';
    return ((value / total) * 100).toFixed(1) + '%';
};
</script>

<template>
    <Head title="Referral Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Referral Analytics</h2>
                <select
                    v-model="selectedDays"
                    @change="changeDateRange"
                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option :value="7">Last 7 days</option>
                    <option :value="30">Last 30 days</option>
                    <option :value="90">Last 90 days</option>
                </select>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Total Visits</div>
                        <div class="text-2xl font-bold text-gray-900">{{ totalVisits.toLocaleString() }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">UTM Tracked</div>
                        <div class="text-2xl font-bold text-indigo-600">{{ utmVisits.toLocaleString() }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Conversions</div>
                        <div class="text-2xl font-bold text-green-600">{{ conversions.toLocaleString() }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Conversion Rate</div>
                        <div class="text-2xl font-bold text-purple-600">{{ conversionRate }}%</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Sources -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Sources</h3>
                        <div v-if="topSources.length > 0" class="space-y-3">
                            <div v-for="source in topSources" :key="source.utm_source" class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ source.utm_source }}</div>
                                    <div class="text-xs text-gray-500">{{ source.conversions || 0 }} conversions</div>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">{{ source.visits }}</div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-sm">No UTM source data available.</p>
                    </div>

                    <!-- Top Referrers -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Referrers</h3>
                        <div v-if="topReferrers.length > 0" class="space-y-3">
                            <div v-for="referrer in topReferrers" :key="referrer.referer_domain" class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ referrer.referer_domain }}</div>
                                    <div class="text-xs text-gray-500">{{ referrer.conversions || 0 }} conversions</div>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">{{ referrer.visits }}</div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-sm">No referrer data available.</p>
                    </div>

                    <!-- Device Breakdown -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Devices</h3>
                        <div v-if="Object.keys(deviceBreakdown).length > 0" class="space-y-3">
                            <div v-for="(count, device) in deviceBreakdown" :key="device" class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-900 capitalize">{{ device }}</div>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-indigo-600 h-2 rounded-full"
                                            :style="{ width: formatPercent(count, totalVisits) }"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-sm">No device data available.</p>
                    </div>

                    <!-- Browser Breakdown -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Browsers</h3>
                        <div v-if="Object.keys(browserBreakdown).length > 0" class="space-y-3">
                            <div v-for="(count, browser) in browserBreakdown" :key="browser" class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-900">{{ browser }}</div>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-green-600 h-2 rounded-full"
                                            :style="{ width: formatPercent(count, totalVisits) }"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-sm">No browser data available.</p>
                    </div>
                </div>

                <!-- Top Campaigns -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Campaigns</h3>
                    <div v-if="topCampaigns.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medium</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visits</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="campaign in topCampaigns" :key="campaign.utm_campaign">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ campaign.utm_campaign }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.utm_source }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.utm_medium }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ campaign.visits }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ campaign.conversions || 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-gray-500 text-sm">No campaign data available.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
