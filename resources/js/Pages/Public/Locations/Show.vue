<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    location: Object,
    photos: Array,
    nearbyLocations: Array
});
</script>

<template>
    <Head :title="location.name" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link href="/locations" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to Locations
            </Link>

            <!-- Header -->
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900">{{ location.name }}</h1>
                <div class="flex items-center gap-2 text-sm text-gray-500 mt-2">
                    <span v-if="location.country">{{ location.country }}</span>
                    <span v-if="location.region">&bull; {{ location.region }}</span>
                    <span>&bull; {{ location.views }} views</span>
                </div>
                <p v-if="location.description" class="text-lg text-gray-600 mt-4">{{ location.description }}</p>
            </header>

            <!-- Cover Image -->
            <div v-if="location.cover_image" class="aspect-video bg-gray-200 rounded-xl overflow-hidden mb-12">
                <img :src="`/storage/${location.cover_image}`" :alt="location.name" class="w-full h-full object-cover" />
            </div>

            <!-- Story -->
            <div v-if="location.story" class="prose prose-lg max-w-none mb-12" v-html="location.story"></div>

            <!-- Photos -->
            <section v-if="photos.length > 0" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Photos from this Location</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link v-for="photo in photos" :key="photo.id" :href="route('photos.show', photo.slug)" class="group">
                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                            <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                    </Link>
                </div>
            </section>

            <!-- Map -->
            <section v-if="location.latitude && location.longitude" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Location</h2>
                <div class="aspect-video bg-gray-200 rounded-xl overflow-hidden">
                    <iframe :src="`https://www.openstreetmap.org/export/embed.html?bbox=${location.longitude - 0.05},${location.latitude - 0.05},${location.longitude + 0.05},${location.latitude + 0.05}&layer=mapnik&marker=${location.latitude},${location.longitude}`" class="w-full h-full border-0" loading="lazy"></iframe>
                </div>
            </section>
        </div>

        <!-- Nearby Locations -->
        <section v-if="nearbyLocations.length > 0" class="bg-gray-50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Nearby Locations</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="loc in nearbyLocations" :key="loc.id" :href="route('locations.show', loc.slug)" class="group">
                        <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden">
                            <img v-if="loc.cover_image" :src="`/storage/${loc.cover_image}`" :alt="loc.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <h3 class="mt-3 font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ loc.name }}</h3>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
