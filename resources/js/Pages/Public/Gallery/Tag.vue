<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    tag: Object,
    photos: Object
});
</script>

<template>
    <Head :title="`${tag.name} - Gallery`" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <Link href="/gallery" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to Gallery</Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-4">#{{ tag.name }}</h1>
            </div>

            <div v-if="photos.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <Link v-for="photo in photos.data" :key="photo.id" :href="route('photo.show', photo.slug)" class="group">
                    <div class="aspect-square rounded-lg overflow-hidden" :style="{ backgroundColor: photo.dominant_color || '#e5e7eb' }">
                        <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    </div>
                    <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                </Link>
            </div>
            <div v-else class="text-center py-12">
                <p class="text-gray-500">No photos with this tag.</p>
            </div>

            <div v-if="photos.data.length > 0" class="mt-8">
                <Pagination :links="photos.links" />
            </div>
        </div>
    </PublicLayout>
</template>
