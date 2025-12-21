<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    post: Object,
    audit: Object
});

const getScoreClass = (score) => {
    if (score >= 80) return 'text-green-600 bg-green-100';
    if (score >= 60) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
};

const getSeverityClass = (severity) => {
    const classes = {
        critical: 'border-red-500 bg-red-50',
        warning: 'border-yellow-500 bg-yellow-50',
        info: 'border-blue-500 bg-blue-50',
        success: 'border-green-500 bg-green-50',
    };
    return classes[severity] || 'border-gray-500 bg-gray-50';
};

const getSeverityIcon = (severity) => {
    if (severity === 'success') return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
    if (severity === 'critical') return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z';
    if (severity === 'warning') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};
</script>

<template>
    <Head :title="`SEO Audit - ${post.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.seo.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Post SEO Audit</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Post Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ post.title }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ post.slug }}</p>
                            <p v-if="post.excerpt" class="text-sm text-gray-600 mt-2">{{ post.excerpt }}</p>
                        </div>
                        <div :class="['text-2xl font-bold rounded-lg px-4 py-2', getScoreClass(audit.score)]">
                            {{ audit.score }}%
                        </div>
                    </div>
                    <div class="mt-4">
                        <Link :href="route('admin.posts.edit', post.id)">
                            <PrimaryButton>Edit Post</PrimaryButton>
                        </Link>
                    </div>
                </div>

                <!-- Audit Checks -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">SEO Checks</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="(check, index) in audit.checks" :key="index" :class="['p-4 border-l-4', getSeverityClass(check.severity)]">
                            <div class="flex items-start gap-3">
                                <svg :class="[
                                    'w-5 h-5 flex-shrink-0 mt-0.5',
                                    check.severity === 'success' ? 'text-green-500' :
                                    check.severity === 'critical' ? 'text-red-500' :
                                    check.severity === 'warning' ? 'text-yellow-500' : 'text-blue-500'
                                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getSeverityIcon(check.severity)" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">{{ check.title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">{{ check.message }}</p>
                                    <p v-if="check.recommendation" class="text-sm text-gray-500 mt-2">
                                        <strong>Tip:</strong> {{ check.recommendation }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Values -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Meta Values</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">SEO Title</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ post.seo_title || '(Not set - using post title)' }}
                                <span class="text-gray-400 ml-2">({{ (post.seo_title || post.title).length }}/60 chars)</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Meta Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ post.meta_description || '(Not set - using excerpt)' }}
                                <span class="text-gray-400 ml-2">({{ (post.meta_description || post.excerpt || '').length }}/160 chars)</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
