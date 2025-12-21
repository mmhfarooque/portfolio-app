<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    locales: Object,
    photos: Array,
    posts: Array
});

const getProgressColor = (percentage) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};
</script>

<template>
    <Head title="Translations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Translations</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Locales -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Available Languages</h3>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="(name, code) in locales" :key="code" class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                            {{ name }} ({{ code }})
                        </span>
                    </div>
                </div>

                <!-- Photos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Photo Translations</h3>
                    <div v-if="photos.length > 0" class="space-y-3">
                        <div v-for="photo in photos" :key="photo.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ photo.title }}</p>
                                <p class="text-sm text-gray-500">/photo/{{ photo.slug }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-24">
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div :class="getProgressColor(photo.translation_status)" :style="{ width: `${photo.translation_status}%` }" class="h-full"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 text-center mt-1">{{ photo.translation_status }}%</p>
                                </div>
                                <Link :href="route('admin.translations.photo', photo.id)" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No published photos.</p>
                </div>

                <!-- Posts -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Post Translations</h3>
                    <div v-if="posts.length > 0" class="space-y-3">
                        <div v-for="post in posts" :key="post.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ post.title }}</p>
                                <p class="text-sm text-gray-500">/blog/{{ post.slug }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-24">
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div :class="getProgressColor(post.translation_status)" :style="{ width: `${post.translation_status}%` }" class="h-full"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 text-center mt-1">{{ post.translation_status }}%</p>
                                </div>
                                <Link :href="route('admin.translations.post', post.id)" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-center py-4">No published posts.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
