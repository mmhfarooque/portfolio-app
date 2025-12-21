<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    audit: Object
});

const getScoreClass = (score) => {
    if (score >= 80) return 'text-green-600 bg-green-100';
    if (score >= 60) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
};

const getSeverityClass = (severity) => {
    const classes = {
        critical: 'bg-red-100 text-red-800 border-red-200',
        warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        info: 'bg-blue-100 text-blue-800 border-blue-200',
    };
    return classes[severity] || 'bg-gray-100 text-gray-800 border-gray-200';
};
</script>

<template>
    <Head title="SEO Audit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">SEO Audit</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Overall Score -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Overall SEO Score</h3>
                            <p class="text-sm text-gray-500 mt-1">Based on analysis of your content</p>
                        </div>
                        <div :class="['text-4xl font-bold rounded-lg px-6 py-3', getScoreClass(audit.score)]">
                            {{ audit.score || 0 }}%
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Photos Audited</div>
                        <div class="text-2xl font-bold text-gray-900">{{ audit.photos_count || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Posts Audited</div>
                        <div class="text-2xl font-bold text-gray-900">{{ audit.posts_count || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Issues Found</div>
                        <div class="text-2xl font-bold text-red-600">{{ audit.issues_count || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Warnings</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ audit.warnings_count || 0 }}</div>
                    </div>
                </div>

                <!-- Issues List -->
                <div v-if="audit.issues && audit.issues.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Issues to Address</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="(issue, index) in audit.issues" :key="index" :class="['p-4 border-l-4', getSeverityClass(issue.severity)]">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ issue.title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ issue.description }}</p>
                                    <p v-if="issue.recommendation" class="text-sm text-gray-500 mt-2">
                                        <strong>Recommendation:</strong> {{ issue.recommendation }}
                                    </p>
                                </div>
                                <Link v-if="issue.link" :href="issue.link" class="text-indigo-600 hover:text-indigo-800 text-sm whitespace-nowrap ml-4">
                                    Fix it
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photos with Issues -->
                <div v-if="audit.photos_with_issues && audit.photos_with_issues.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Photos Needing Attention</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <Link
                                v-for="photo in audit.photos_with_issues"
                                :key="photo.id"
                                :href="route('admin.seo.photo', photo.id)"
                                class="group relative"
                            >
                                <img
                                    :src="`/storage/${photo.thumbnail_path}`"
                                    :alt="photo.title"
                                    class="w-full aspect-square object-cover rounded-lg"
                                />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-lg flex items-center justify-center">
                                    <span class="text-white text-sm font-medium">Audit</span>
                                </div>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                                        {{ photo.issues_count }} issues
                                    </span>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Posts with Issues -->
                <div v-if="audit.posts_with_issues && audit.posts_with_issues.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Posts Needing Attention</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <Link
                                v-for="post in audit.posts_with_issues"
                                :key="post.id"
                                :href="route('admin.seo.post', post.id)"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
                            >
                                <span class="text-gray-900">{{ post.title }}</span>
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                    {{ post.issues_count }} issues
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- All Good -->
                <div v-if="(!audit.issues || audit.issues.length === 0) && (!audit.photos_with_issues || audit.photos_with_issues.length === 0)" class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-green-900">Great job!</h3>
                    <p class="mt-1 text-green-700">No SEO issues were found on your site.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
