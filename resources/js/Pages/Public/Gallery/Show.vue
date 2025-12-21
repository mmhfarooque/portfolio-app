<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photo: Object,
    relatedPhotos: Array,
    previousPhoto: Object,
    nextPhoto: Object
});
</script>

<template>
    <Head>
        <title>{{ photo.seo_title || photo.title }}</title>
        <meta v-if="photo.meta_description" name="description" :content="photo.meta_description" />
    </Head>

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Navigation -->
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('gallery.index')" class="text-gray-600 hover:text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Gallery
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="previousPhoto"
                        :href="route('photos.show', previousPhoto.slug)"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                        title="Previous photo"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <Link
                        v-if="nextPhoto"
                        :href="route('photos.show', nextPhoto.slug)"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition"
                        title="Next photo"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Main Image -->
            <div class="mb-8">
                <img
                    :src="`/storage/${photo.watermarked_path || photo.display_path}`"
                    :alt="photo.title"
                    class="w-full max-h-[80vh] object-contain mx-auto rounded-lg"
                />
            </div>

            <!-- Photo Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ photo.title }}</h1>

                    <p v-if="photo.description" class="text-gray-600 mb-4">{{ photo.description }}</p>

                    <div v-if="photo.story" class="prose prose-lg max-w-none mb-6">
                        <div v-html="photo.story"></div>
                    </div>

                    <!-- Tags -->
                    <div v-if="photo.tags?.length > 0" class="flex flex-wrap gap-2 mt-4">
                        <Link
                            v-for="tag in photo.tags"
                            :key="tag.id"
                            :href="route('gallery.index', { tag: tag.slug })"
                            class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full hover:bg-gray-200 transition"
                        >
                            #{{ tag.name }}
                        </Link>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Photo Info -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Photo Details</h3>
                        <dl class="text-sm space-y-2">
                            <div v-if="photo.location_name" class="flex justify-between">
                                <dt class="text-gray-500">Location</dt>
                                <dd class="text-gray-900">{{ photo.location_name }}</dd>
                            </div>
                            <div v-if="photo.category" class="flex justify-between">
                                <dt class="text-gray-500">Category</dt>
                                <dd>
                                    <Link :href="route('gallery.index', { category: photo.category.slug })" class="text-blue-600 hover:underline">
                                        {{ photo.category.name }}
                                    </Link>
                                </dd>
                            </div>
                            <div v-if="photo.views" class="flex justify-between">
                                <dt class="text-gray-500">Views</dt>
                                <dd class="text-gray-900">{{ photo.views.toLocaleString() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- EXIF Data -->
                    <div v-if="photo.formatted_exif" class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Camera Settings</h3>
                        <dl class="text-sm space-y-2">
                            <div v-if="photo.formatted_exif.camera" class="flex justify-between">
                                <dt class="text-gray-500">Camera</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.camera }}</dd>
                            </div>
                            <div v-if="photo.formatted_exif.lens" class="flex justify-between">
                                <dt class="text-gray-500">Lens</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.lens }}</dd>
                            </div>
                            <div v-if="photo.formatted_exif.focal_length" class="flex justify-between">
                                <dt class="text-gray-500">Focal Length</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.focal_length }}</dd>
                            </div>
                            <div v-if="photo.formatted_exif.aperture" class="flex justify-between">
                                <dt class="text-gray-500">Aperture</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.aperture }}</dd>
                            </div>
                            <div v-if="photo.formatted_exif.shutter" class="flex justify-between">
                                <dt class="text-gray-500">Shutter Speed</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.shutter }}</dd>
                            </div>
                            <div v-if="photo.formatted_exif.iso" class="flex justify-between">
                                <dt class="text-gray-500">ISO</dt>
                                <dd class="text-gray-900">{{ photo.formatted_exif.iso }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Related Photos -->
            <div v-if="relatedPhotos.length > 0" class="mt-16">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Photos</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <Link
                        v-for="related in relatedPhotos"
                        :key="related.id"
                        :href="route('photos.show', related.slug)"
                        class="group"
                    >
                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                            <img
                                :src="`/storage/${related.thumbnail_path}`"
                                :alt="related.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                        </div>
                        <p class="text-sm text-gray-600 mt-2 truncate">{{ related.title }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
