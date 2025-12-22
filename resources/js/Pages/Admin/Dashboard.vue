<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats: Object,
    storageUsed: Object,
    photosByCategory: Array,
    viewsByCategory: Array,
    viewsChartData: Object,
    recentPhotos: Array,
    mostViewedPhotos: Array,
    recentActivity: Array
});

const maxViews = computed(() => {
    const values = Object.values(props.viewsChartData || {});
    return Math.max(...values, 1);
});

const chartDates = computed(() => {
    const keys = Object.keys(props.viewsChartData || {});
    if (keys.length === 0) return { start: '', end: 'Today' };
    return {
        start: formatDate(keys[0]),
        end: 'Today'
    };
});

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num || 0);
};

const getActivityIcon = (type) => {
    switch (type) {
        case 'error': return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'warning': return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
        case 'activity': return 'M5 13l4 4L19 7';
        default: return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

const getActivityColor = (type) => {
    switch (type) {
        case 'error': return 'bg-red-100 text-red-600';
        case 'warning': return 'bg-amber-100 text-amber-600';
        case 'activity': return 'bg-green-100 text-green-600';
        default: return 'bg-blue-100 text-blue-600';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Quick Actions -->
                <div class="mb-6 flex flex-wrap gap-2">
                    <Link
                        :href="route('admin.photos.create')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 transition-all duration-200"
                        title="Upload Photos"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Upload</span>
                    </Link>

                    <Link
                        :href="route('admin.contacts.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Messages"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        <span v-if="stats.unreadContacts > 0" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">
                            {{ stats.unreadContacts }}
                        </span>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Messages</span>
                    </Link>

                    <a
                        :href="route('home')"
                        target="_blank"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="View Site"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">View Site</span>
                    </a>

                    <Link
                        :href="route('admin.settings.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Settings"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Settings</span>
                    </Link>

                    <div class="w-px h-11 bg-gray-200 mx-1"></div>

                    <Link
                        :href="route('admin.categories.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Categories"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Categories</span>
                    </Link>

                    <Link
                        :href="route('admin.galleries.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Galleries"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Galleries</span>
                    </Link>

                    <Link
                        :href="route('admin.tags.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Tags"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6h.008v.008H6V6z" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Tags</span>
                    </Link>

                    <Link
                        :href="route('admin.posts.index')"
                        class="group relative w-11 h-11 flex items-center justify-center bg-white hover:bg-gray-50 text-gray-600 hover:text-gray-900 rounded-xl border border-gray-200 shadow-sm hover:shadow transition-all duration-200"
                        title="Blog Posts"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />
                        </svg>
                        <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">Blog Posts</span>
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Total Photos -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Photos</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(stats.totalPhotos) }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ stats.publishedPhotos }} published, {{ stats.draftPhotos }} drafts
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- This Month -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">This Month</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.photosThisMonth }}</p>
                                <p :class="['text-xs mt-1', stats.growthPercent >= 0 ? 'text-green-600' : 'text-red-600']">
                                    <span class="inline-flex items-center">
                                        <svg v-if="stats.growthPercent >= 0" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <svg v-else class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ Math.abs(stats.growthPercent) }}%
                                    </span>
                                    vs last month
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Used -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Storage Used</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ storageUsed.formatted }}</p>
                                <p class="text-xs text-gray-400 mt-1">Photos & thumbnails</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Views -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Views</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(stats.totalViews) }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ stats.downloadsCount }} downloads (30 days)</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Views Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Views (Last 30 Days)</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-end justify-between h-32 gap-1">
                            <div
                                v-for="(views, date) in viewsChartData"
                                :key="date"
                                class="flex-1 flex flex-col items-center group relative"
                            >
                                <div
                                    class="w-full bg-blue-500 rounded-t transition-all hover:bg-blue-600"
                                    :style="{ height: `${(views / maxViews) * 100}%` }"
                                    :title="`${formatDate(date)}: ${views} views`"
                                ></div>
                                <div class="hidden group-hover:block absolute -top-8 bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                                    {{ formatDate(date) }}: {{ views }}
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-gray-400">
                            <span>{{ chartDates.start }}</span>
                            <span>{{ chartDates.end }}</span>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Photos -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Recent Photos</h3>
                                <Link :href="route('admin.photos.index')" class="text-sm text-blue-600 hover:text-blue-800">View all</Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="recentPhotos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                <Link
                                    v-for="photo in recentPhotos"
                                    :key="photo.id"
                                    :href="route('admin.photos.edit', photo.id)"
                                    class="group"
                                >
                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img
                                            :src="`/storage/${photo.thumbnail_path}`"
                                            :alt="photo.title"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2 truncate">{{ photo.title }}</p>
                                    <p class="text-xs text-gray-400">{{ photo.created_at }}</p>
                                </Link>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p>No photos uploaded yet</p>
                                <Link :href="route('admin.photos.create')" class="text-blue-600 hover:underline text-sm">Upload your first photo</Link>
                            </div>
                        </div>
                    </div>

                    <!-- Photos by Category -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">By Category</h3>
                                <Link :href="route('admin.categories.index')" class="text-sm text-blue-600 hover:text-blue-800">Manage</Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="photosByCategory.length > 0" class="space-y-4">
                                <div v-for="category in photosByCategory" :key="category.id">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">{{ category.name }}</span>
                                        <span class="text-sm text-gray-500">{{ category.photos_count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full transition-all"
                                            :style="{ width: `${stats.totalPhotos > 0 ? (category.photos_count / stats.totalPhotos * 100) : 0}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-center text-gray-500 py-4">No categories yet</p>

                            <div class="mt-6 pt-6 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Total Tags</span>
                                    <span class="font-semibold text-gray-900">{{ stats.totalTags }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Most Viewed Photos -->
                <div v-if="mostViewedPhotos.length > 0" class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Most Viewed Photos</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-4">
                            <Link
                                v-for="photo in mostViewedPhotos"
                                :key="photo.id"
                                :href="route('admin.photos.edit', photo.id)"
                                class="group"
                            >
                                <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 relative">
                                    <img
                                        :src="`/storage/${photo.thumbnail_path}`"
                                        :alt="photo.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-2">
                                        <span class="text-white text-xs font-medium flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ formatNumber(photo.views) }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 mt-1 truncate">{{ photo.title }}</p>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900">Recent Activity</h3>
                            <Link :href="route('admin.logs.index')" class="text-sm text-blue-600 hover:text-blue-800">View all logs</Link>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="log in recentActivity"
                            :key="log.id"
                            class="px-6 py-4 flex items-start gap-4"
                        >
                            <div :class="['flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center', getActivityColor(log.type)]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(log.type)" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ log.message }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ log.created_at }}</p>
                            </div>
                        </div>
                        <div v-if="recentActivity.length === 0" class="px-6 py-8 text-center text-gray-500">
                            <p>No recent activity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
