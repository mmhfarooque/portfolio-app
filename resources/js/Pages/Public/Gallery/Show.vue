<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { sanitizeHtml } from '@/composables/useSanitize.js';
import LikeButton from '@/Components/Photo/LikeButton.vue';
import CommentSection from '@/Components/Photo/CommentSection.vue';

const props = defineProps({
    photo: Object,
    relatedPhotos: Array,
    previousPhoto: Object,
    nextPhoto: Object
});

const page = usePage();

// Sticky sidebar handling
const sidebarRef = ref(null);
const isSticky = ref(false);

const handleScroll = () => {
    if (window.innerWidth >= 1024 && sidebarRef.value) {
        const rect = sidebarRef.value.getBoundingClientRect();
        isSticky.value = rect.top <= 100;
    }
};

// Map functionality
const mapContainer = ref(null);
const mapInstance = ref(null);
const hasLocation = computed(() => props.photo.latitude && props.photo.longitude);

// Format captured date (use UTC to preserve original capture time from camera)
const formattedDate = computed(() => {
    if (!props.photo.captured_at) return null;
    const date = new Date(props.photo.captured_at);
    return {
        date: date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'UTC'
        }),
        time: date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'UTC'
        })
    };
});

const initMap = () => {
    if (!mapContainer.value || !window.L || !hasLocation.value) return;

    const lat = props.photo.latitude;
    const lng = props.photo.longitude;

    mapInstance.value = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: false
    }).setView([lat, lng], 12);

    // Always use colorful OpenStreetMap tiles regardless of theme
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(mapInstance.value);

    // Custom marker
    const markerIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div class="w-8 h-8 bg-amber-500 rounded-full border-4 border-white shadow-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            </svg>
        </div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 32]
    });

    L.marker([lat, lng], { icon: markerIcon }).addTo(mapInstance.value);

    // Add zoom control to bottom right
    L.control.zoom({ position: 'bottomright' }).addTo(mapInstance.value);
};

const loadLeaflet = () => {
    if (typeof L !== 'undefined') {
        initMap();
        return;
    }

    // Load Leaflet CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);

    // Load Leaflet JS
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = initMap;
    document.head.appendChild(script);
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    if (hasLocation.value) {
        loadLeaflet();
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (mapInstance.value) {
        mapInstance.value.remove();
    }
});

// Format date
const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Copy link functionality
const showCopied = ref(false);
const copyLink = () => {
    navigator.clipboard.writeText(window.location.href);
    showCopied.value = true;
    setTimeout(() => showCopied.value = false, 2000);
};
</script>

<template>
    <SeoHead
        :title="photo.seo_title || photo.title"
        :description="photo.meta_description || photo.description"
        :image="photo.watermarked_path || photo.display_path"
        :image-alt="photo.title"
        type="photo"
        :url="`https://mfaruk.com/photo/${photo.slug}`"
        :photo="photo"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Gallery', url: '/gallery' },
            photo.category ? { name: photo.category.name, url: `/gallery?category=${photo.category.slug}` } : null,
            { name: photo.title }
        ].filter(Boolean)"
    />

    <PublicLayout>
        <!-- Hero Image Section - Full Width Dark Background -->
        <div class="relative bg-black min-h-[50vh] lg:min-h-[70vh] flex items-center justify-center">
            <!-- Main Image -->
            <div class="relative w-full h-full flex items-center justify-center py-4 lg:py-8">
                <img
                    :src="`/storage/${photo.watermarked_path || photo.display_path}`"
                    :alt="photo.title"
                    class="max-w-full max-h-[85vh] object-contain"
                />
            </div>

            <!-- Navigation Arrows -->
            <div class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4 lg:px-8 pointer-events-none">
                <Link
                    v-if="previousPhoto"
                    :href="route('photos.show', previousPhoto.slug)"
                    class="pointer-events-auto group flex items-center gap-3 p-3 lg:pr-5 bg-white/10 backdrop-blur-md text-white rounded-full hover:bg-white/20 transition-all duration-300"
                >
                    <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="hidden lg:block text-sm font-medium max-w-[120px] truncate">{{ previousPhoto.title }}</span>
                </Link>
                <div v-else></div>
                <Link
                    v-if="nextPhoto"
                    :href="route('photos.show', nextPhoto.slug)"
                    class="pointer-events-auto group flex items-center gap-3 p-3 lg:pl-5 bg-white/10 backdrop-blur-md text-white rounded-full hover:bg-white/20 transition-all duration-300"
                >
                    <span class="hidden lg:block text-sm font-medium max-w-[120px] truncate">{{ nextPhoto.title }}</span>
                    <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>

            <!-- Bottom Gradient Fade -->
            <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-black/60 to-transparent pointer-events-none"></div>
        </div>

        <!-- Content Section -->
        <div class="transition-colors duration-300 bg-theme-bg-primary">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm mb-8">
                    <Link
                        :href="route('photos.index')"
                        class="transition-colors text-theme-text-muted hover:text-theme-text-primary"
                    >
                        Gallery
                    </Link>
                    <svg class="w-4 h-4 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link
                        v-if="photo.category"
                        :href="route('photos.index', { category: photo.category.slug })"
                        class="transition-colors text-theme-text-muted hover:text-theme-text-primary"
                    >
                        {{ photo.category.name }}
                    </Link>
                    <svg v-if="photo.category" class="w-4 h-4 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-theme-text-secondary truncate max-w-[200px]">
                        {{ photo.title }}
                    </span>
                </nav>

                <!-- Main Content Grid -->
                <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                    <!-- Left Column - Title, Description, Story, Comments -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Title & Description Card -->
                        <div>
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight mb-4 text-theme-text-primary">
                                {{ photo.title }}
                            </h1>

                            <!-- Date & Location Meta -->
                            <div class="flex flex-wrap items-center gap-4 mb-6">
                                <div v-if="photo.taken_at || photo.created_at" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm text-theme-text-secondary">
                                        {{ formatDate(photo.taken_at || photo.created_at) }}
                                    </span>
                                </div>
                                <div v-if="photo.location_name" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm text-theme-text-secondary">
                                        {{ photo.location_name }}
                                    </span>
                                </div>
                            </div>

                            <p
                                v-if="photo.description"
                                class="text-lg leading-relaxed text-theme-text-secondary"
                            >
                                {{ photo.description }}
                            </p>
                        </div>

                        <!-- Story Section -->
                        <div
                            v-if="photo.story"
                            class="rounded-2xl p-6 lg:p-8 transition-colors bg-theme-bg-secondary border border-theme-border"
                        >
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center bg-theme-accent-light">
                                    <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-theme-text-primary">
                                    The Story Behind
                                </h2>
                            </div>
                            <div
                                class="prose prose-lg max-w-none leading-relaxed [&>p]:mb-4 [&>p:last-child]:mb-0 text-theme-text-secondary"
                                v-html="sanitizeHtml(photo.story)"
                            ></div>
                        </div>

                        <!-- Comments Section -->
                        <div id="comments" class="scroll-mt-8">
                            <CommentSection
                                :photo-slug="photo.slug"
                                :initial-comments-count="photo.comments_count"
                            />
                        </div>
                    </div>

                    <!-- Right Column - Sticky Sidebar -->
                    <div class="lg:col-span-4 mt-8 lg:mt-0">
                        <div ref="sidebarRef" class="lg:sticky lg:top-24 space-y-6">
                            <!-- Engagement Card -->
                            <div class="rounded-2xl p-5 transition-colors bg-theme-bg-secondary border border-theme-border">
                                <div class="flex items-center justify-between">
                                    <!-- Like Button -->
                                    <LikeButton
                                        :photo-slug="photo.slug"
                                        :initial-likes-count="photo.likes_count"
                                    />

                                    <!-- Comment Link -->
                                    <a
                                        href="#comments"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200 hover:scale-105 bg-theme-bg-hover text-theme-text-secondary hover:bg-theme-bg-tertiary"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <span class="text-sm font-medium">{{ photo.comments_count }}</span>
                                    </a>

                                    <!-- Share Button -->
                                    <button
                                        @click="copyLink"
                                        class="relative flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200 hover:scale-105 bg-theme-bg-hover text-theme-text-secondary hover:bg-theme-bg-tertiary"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                        <!-- Copied tooltip -->
                                        <Transition
                                            enter-active-class="transition duration-200 ease-out"
                                            enter-from-class="opacity-0 scale-95"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition duration-150 ease-in"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-95"
                                        >
                                            <span
                                                v-if="showCopied"
                                                class="absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 rounded-lg whitespace-nowrap"
                                            >
                                                Link copied!
                                            </span>
                                        </Transition>
                                    </button>
                                </div>

                                <!-- Views -->
                                <div
                                    v-if="photo.views"
                                    class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-theme-border"
                                >
                                    <svg class="w-4 h-4 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span class="text-sm text-theme-text-muted">
                                        {{ photo.views.toLocaleString() }} views
                                    </span>
                                </div>
                            </div>

                            <!-- Camera & Settings Card -->
                            <div
                                v-if="photo.formatted_exif"
                                class="rounded-2xl overflow-hidden transition-colors bg-theme-bg-secondary border border-theme-border"
                            >
                                <!-- Camera Header -->
                                <div class="px-5 py-4 border-b border-theme-border bg-theme-bg-hover">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-theme-bg-primary">
                                            <svg class="w-5 h-5 text-theme-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold truncate text-theme-text-primary">
                                                {{ photo.formatted_exif.camera || 'Camera' }}
                                            </p>
                                            <p v-if="photo.formatted_exif.lens" class="text-xs truncate text-theme-text-muted">
                                                {{ photo.formatted_exif.lens }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Settings Grid -->
                                <div class="p-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Focal Length -->
                                        <div v-if="photo.formatted_exif.focal_length" class="text-center p-3 rounded-xl bg-theme-bg-hover">
                                            <p class="text-lg font-bold text-theme-text-primary">
                                                {{ photo.formatted_exif.focal_length }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5 text-theme-text-muted">
                                                Focal Length
                                            </p>
                                        </div>

                                        <!-- Aperture -->
                                        <div v-if="photo.formatted_exif.aperture" class="text-center p-3 rounded-xl bg-theme-bg-hover">
                                            <p class="text-lg font-bold text-theme-text-primary">
                                                {{ photo.formatted_exif.aperture }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5 text-theme-text-muted">
                                                Aperture
                                            </p>
                                        </div>

                                        <!-- Shutter Speed -->
                                        <div v-if="photo.formatted_exif.shutter" class="text-center p-3 rounded-xl bg-theme-bg-hover">
                                            <p class="text-lg font-bold text-theme-text-primary">
                                                {{ photo.formatted_exif.shutter }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5 text-theme-text-muted">
                                                Shutter
                                            </p>
                                        </div>

                                        <!-- ISO -->
                                        <div v-if="photo.formatted_exif.iso" class="text-center p-3 rounded-xl bg-theme-bg-hover">
                                            <p class="text-lg font-bold text-theme-text-primary">
                                                {{ photo.formatted_exif.iso }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5 text-theme-text-muted">
                                                ISO
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Shot Date & Time -->
                                    <div v-if="formattedDate" class="mt-4 pt-4 border-t border-theme-border">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-4 h-4 flex-shrink-0 text-theme-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-theme-text-primary">
                                                    {{ formattedDate.date }}
                                                </p>
                                                <p class="text-xs text-theme-text-muted">
                                                    {{ formattedDate.time }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Card -->
                            <div
                                v-if="photo.category"
                                class="rounded-2xl p-5 transition-colors bg-theme-bg-secondary border border-theme-border"
                            >
                                <h3 class="text-xs font-semibold uppercase tracking-wider mb-3 text-theme-text-muted">
                                    Category
                                </h3>
                                <Link
                                    :href="route('photos.index', { category: photo.category.slug })"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 hover:scale-105 bg-theme-accent-light text-theme-accent"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ photo.category.name }}
                                </Link>
                            </div>

                            <!-- Tags Card -->
                            <div
                                v-if="photo.tags?.length > 0"
                                class="rounded-2xl p-5 transition-colors bg-theme-bg-secondary border border-theme-border"
                            >
                                <h3 class="text-xs font-semibold uppercase tracking-wider mb-3 text-theme-text-muted">
                                    Tags
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-for="tag in photo.tags"
                                        :key="tag.id"
                                        :href="route('photos.index', { tag: tag.slug })"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200 hover:scale-105 bg-theme-bg-hover text-theme-text-secondary hover:bg-theme-accent-light hover:text-theme-accent"
                                    >
                                        #{{ tag.name }}
                                    </Link>
                                </div>
                            </div>

                            <!-- Location Map Card -->
                            <div
                                v-if="hasLocation"
                                class="rounded-2xl overflow-hidden transition-colors bg-theme-bg-secondary border border-theme-border"
                            >
                                <div class="px-5 py-4 border-b border-theme-border">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-xs font-semibold uppercase tracking-wider text-theme-text-muted">
                                            Location
                                        </h3>
                                        <a
                                            :href="`https://www.google.com/maps?q=${photo.latitude},${photo.longitude}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-xs font-medium transition-colors text-theme-accent hover:text-theme-accent-hover"
                                        >
                                            Open in Maps
                                        </a>
                                    </div>
                                    <p v-if="photo.location_name" class="text-sm mt-1 text-theme-text-secondary">
                                        {{ photo.location_name }}
                                    </p>
                                </div>
                                <div ref="mapContainer" class="h-48 w-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Photos -->
                <div v-if="relatedPhotos?.length > 0" class="mt-16 pt-12 border-t border-theme-border">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-theme-text-primary">
                            You May Also Like
                        </h2>
                        <Link
                            :href="route('photos.index')"
                            class="text-sm font-medium transition-colors text-theme-accent hover:text-theme-accent-hover"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <Link
                            v-for="related in relatedPhotos"
                            :key="related.id"
                            :href="route('photos.show', related.slug)"
                            class="group"
                        >
                            <div
                                class="aspect-square rounded-2xl overflow-hidden ring-1 transition-all duration-300 group-hover:ring-2 ring-theme-border group-hover:ring-theme-accent"
                                :style="{ backgroundColor: related.dominant_color || 'var(--bg-secondary)' }"
                            >
                                <img
                                    :src="`/storage/${related.thumbnail_path}`"
                                    :alt="related.title"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                    loading="lazy"
                                />
                            </div>
                            <p class="text-sm font-medium mt-3 truncate transition-colors text-theme-text-secondary group-hover:text-theme-text-primary">
                                {{ related.title }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Custom map marker styles */
.custom-marker {
    background: transparent !important;
    border: none !important;
}

/* Leaflet controls styling */
.leaflet-control-zoom a {
    background-color: rgba(255, 255, 255, 0.9) !important;
    color: #333 !important;
    border: none !important;
    width: 28px !important;
    height: 28px !important;
    line-height: 28px !important;
    font-size: 14px !important;
}

.leaflet-control-zoom a:hover {
    background-color: white !important;
}

/* Dark mode map controls */
:root[data-theme="dark"] .leaflet-control-zoom a,
.dark .leaflet-control-zoom a {
    background-color: rgba(40, 40, 40, 0.9) !important;
    color: #fff !important;
}

:root[data-theme="dark"] .leaflet-control-zoom a:hover,
.dark .leaflet-control-zoom a:hover {
    background-color: rgba(60, 60, 60, 0.95) !important;
}
</style>
