<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photos: Array
});

const mapContainer = ref(null);
const map = ref(null);
const selectedPhoto = ref(null);

onMounted(() => {
    // Initialize map using Leaflet (will load from CDN)
    if (typeof L !== 'undefined' && props.photos.length > 0) {
        initMap();
    } else {
        // Load Leaflet CSS and JS dynamically
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => {
            if (props.photos.length > 0) {
                initMap();
            }
        };
        document.head.appendChild(script);
    }
});

onUnmounted(() => {
    if (map.value) {
        map.value.remove();
    }
});

const initMap = () => {
    if (!mapContainer.value || !window.L) return;

    // Calculate center from photos
    const lats = props.photos.map(p => p.latitude);
    const lngs = props.photos.map(p => p.longitude);
    const centerLat = lats.reduce((a, b) => a + b, 0) / lats.length;
    const centerLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;

    map.value = L.map(mapContainer.value).setView([centerLat, centerLng], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Add markers for each photo
    props.photos.forEach(photo => {
        const marker = L.marker([photo.latitude, photo.longitude])
            .addTo(map.value)
            .bindPopup(`
                <div class="text-center">
                    <img src="/storage/${photo.thumbnail_path}" alt="${photo.title}" class="w-32 h-32 object-cover rounded mb-2" />
                    <a href="/photo/${photo.slug}" class="font-medium text-indigo-600 hover:text-indigo-800">${photo.title}</a>
                    ${photo.location_name ? `<p class="text-sm text-gray-500">${photo.location_name}</p>` : ''}
                </div>
            `);
    });

    // Fit bounds to show all markers
    if (props.photos.length > 1) {
        const bounds = L.latLngBounds(props.photos.map(p => [p.latitude, p.longitude]));
        map.value.fitBounds(bounds, { padding: [50, 50] });
    }
};

const centerOnPhoto = (photo) => {
    if (map.value) {
        map.value.setView([photo.latitude, photo.longitude], 12);
        selectedPhoto.value = photo;
    }
};
</script>

<template>
    <Head title="Photo Map" />

    <PublicLayout>
        <div class="h-screen flex flex-col">
            <!-- Header -->
            <div class="bg-white border-b border-gray-100 px-4 py-3">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link href="/gallery" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to Gallery</Link>
                        <h1 class="text-xl font-semibold text-gray-900">Photo Locations</h1>
                    </div>
                    <span class="text-sm text-gray-500">{{ photos.length }} photos with locations</span>
                </div>
            </div>

            <!-- Map Container -->
            <div class="flex-1 relative">
                <div ref="mapContainer" class="absolute inset-0"></div>

                <!-- Photo List Sidebar -->
                <div class="absolute top-4 left-4 w-64 max-h-[calc(100%-2rem)] bg-white rounded-lg shadow-lg overflow-hidden z-[1000]">
                    <div class="p-3 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Locations</h3>
                    </div>
                    <div class="overflow-y-auto max-h-80">
                        <button v-for="photo in photos" :key="photo.id" @click="centerOnPhoto(photo)" :class="['w-full flex items-center gap-3 p-3 hover:bg-gray-50 transition text-left', selectedPhoto?.id === photo.id ? 'bg-indigo-50' : '']">
                            <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-12 h-12 object-cover rounded" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ photo.title }}</p>
                                <p v-if="photo.location_name" class="text-xs text-gray-500 truncate">{{ photo.location_name }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="photos.length === 0" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="mt-4 text-gray-500">No photos with location data</p>
                        <Link href="/gallery" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800">Browse Gallery</Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Leaflet popup styles */
.leaflet-popup-content-wrapper {
    border-radius: 0.5rem;
}
.leaflet-popup-content {
    margin: 0.75rem;
}
</style>
