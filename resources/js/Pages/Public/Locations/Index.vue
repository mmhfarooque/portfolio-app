<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    locations: Array,
    featuredLocations: Array
});
</script>

<template>
    <Head title="Locations" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Locations</h1>
                <p class="mt-2 text-gray-600">Discover photos from around the world</p>
            </div>

            <!-- Featured Locations -->
            <section v-if="featuredLocations.length > 0" class="mb-12">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Featured Locations</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="loc in featuredLocations" :key="loc.id" :href="route('locations.show', loc.slug)" class="group relative aspect-video bg-gray-200 rounded-xl overflow-hidden">
                        <img v-if="loc.cover_image" :src="`/storage/${loc.cover_image}`" :alt="loc.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-4 left-4 text-white">
                            <h3 class="text-lg font-semibold">{{ loc.name }}</h3>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- All Locations -->
            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">All Locations</h2>
                <div v-if="locations.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link v-for="loc in locations" :key="loc.id" :href="route('locations.show', loc.slug)" class="group">
                        <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden">
                            <img v-if="loc.cover_image" :src="`/storage/${loc.cover_image}`" :alt="loc.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <h3 class="font-medium text-gray-900 group-hover:text-indigo-600 transition">{{ loc.name }}</h3>
                            <p class="text-sm text-gray-500">{{ loc.photos_count }} photos</p>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No locations yet</h3>
                    <p class="mt-2 text-gray-500">Check back later for photo locations</p>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
