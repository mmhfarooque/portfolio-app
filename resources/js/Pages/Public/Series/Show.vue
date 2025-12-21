<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    series: Object,
    photos: Array,
    relatedSeries: Array
});
</script>

<template>
    <Head :title="series.title" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link href="/series" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to Series
            </Link>

            <!-- Header -->
            <header class="mb-12">
                <h1 class="text-4xl font-bold text-gray-900">{{ series.title }}</h1>
                <div class="flex items-center gap-2 text-sm text-gray-500 mt-2">
                    <span v-if="series.project_date">{{ series.project_date }}</span>
                    <span v-if="series.location">&bull; {{ series.location }}</span>
                    <span>&bull; {{ photos.length }} photos</span>
                    <span>&bull; {{ series.views }} views</span>
                </div>
                <p v-if="series.description" class="text-xl text-gray-600 mt-4">{{ series.description }}</p>
            </header>

            <!-- Cover Image -->
            <div v-if="series.cover_image" class="aspect-video bg-gray-200 rounded-xl overflow-hidden mb-12">
                <img :src="`/storage/${series.cover_image}`" :alt="series.title" class="w-full h-full object-cover" />
            </div>

            <!-- Story -->
            <div v-if="series.story" class="prose prose-lg max-w-none mb-12" v-html="series.story"></div>

            <!-- Photos Grid -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Photos</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link v-for="photo in photos" :key="photo.id" :href="route('photos.show', photo.slug)" class="group">
                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                            <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                    </Link>
                </div>
            </section>

            <!-- Author -->
            <div v-if="series.user" class="flex items-center gap-3 mt-12 pt-8 border-t border-gray-100">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-gray-700">By {{ series.user.name }}</span>
            </div>
        </div>

        <!-- Related Series -->
        <section v-if="relatedSeries.length > 0" class="bg-gray-50 py-12 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Series</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="related in relatedSeries" :key="related.id" :href="route('series.show', related.slug)" class="group">
                        <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden">
                            <img v-if="related.cover_image" :src="`/storage/${related.cover_image}`" :alt="related.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <div class="mt-3">
                            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ related.title }}</h3>
                            <p class="text-sm text-gray-500">{{ related.photos_count }} photos</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
