<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ResponsiveImage from '@/Components/ResponsiveImage.vue';

const props = defineProps({
    gallery: Object,
    photos: Object
});
</script>

<template>
    <Head :title="`${gallery.name} - Gallery`" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <Link :href="route('photos.index')" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to Gallery</Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ gallery.name }}</h1>
                <p v-if="gallery.description" class="mt-2 text-gray-600">{{ gallery.description }}</p>
            </div>

            <div v-if="photos.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <Link v-for="photo in photos.data" :key="photo.id" :href="route('photos.show', photo.slug)" class="group">
                    <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                        <ResponsiveImage :image="photo.image" :alt="photo.title" sizes="(max-width: 768px) 50vw, 25vw" picture-class="block w-full h-full" img-class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                </Link>
            </div>
            <div v-else class="text-center py-12">
                <p class="text-gray-500">No photos in this gallery.</p>
            </div>

            <div v-if="photos.data.length > 0" class="mt-8">
                <Pagination :links="photos.links" />
            </div>
        </div>
    </PublicLayout>
</template>
