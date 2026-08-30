<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { sanitizeHtml } from '@/composables/useSanitize.js';
import LikeButton from '@/Components/Photo/LikeButton.vue';
import CommentSection from '@/Components/Photo/CommentSection.vue';
import ResponsiveImage from '@/Components/ResponsiveImage.vue';

const props = defineProps({
    photo: Object,
    nearbyPhotos: Array,
    relatedPhotos: Array,
    previousPhoto: Object,
    nextPhoto: Object
});

const page = usePage();

// Extract region name from location (e.g., "Koh Chang, Thailand" → "Thailand", "Monpura, Bangladesh" → "Bangladesh")
const nearbyRegion = computed(() => {
    if (!props.photo.location_name) return 'this Area';
    const parts = props.photo.location_name.split(',').map(s => s.trim());
    // Use the last part (country) or second-to-last (region) for broader area
    return parts.length >= 2 ? parts[parts.length - 1] : parts[0];
});

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

// Share functionality
const showCopied = ref(false);
const showShareMenu = ref(false);

const photoUrl = computed(() => `https://mfaruk.com/photo/${props.photo.slug}`);
const shareText = computed(() => `${props.photo.title} — Photography by Mahmud Farooque`);

const copyLink = () => {
    navigator.clipboard.writeText(photoUrl.value);
    showCopied.value = true;
    showShareMenu.value = false;
    setTimeout(() => showCopied.value = false, 2000);
};

const shareWhatsApp = () => {
    window.open(`https://wa.me/?text=${encodeURIComponent(shareText.value + '\n' + photoUrl.value)}`, '_blank');
    showShareMenu.value = false;
};

const shareX = () => {
    window.open(`https://x.com/intent/tweet?text=${encodeURIComponent(shareText.value)}&url=${encodeURIComponent(photoUrl.value)}`, '_blank');
    showShareMenu.value = false;
};

const shareFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(photoUrl.value)}`, '_blank');
    showShareMenu.value = false;
};

const closeShareMenu = () => { showShareMenu.value = false; };
</script>

<template>
    <SeoHead
        :title="photo.seo_title || photo.title"
        :description="photo.meta_description || photo.description"
        :image="photo.image?.url"
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
                <ResponsiveImage
                    :image="photo.image"
                    :alt="photo.title"
                    sizes="(max-width: 1280px) 100vw, 1280px"
                    loading="eager"
                    fetchpriority="high"
                    decoding="sync"
                    picture-class="block"
                    img-class="max-w-full max-h-[85vh] w-auto h-auto object-contain"
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

                                    <!-- Share Button with Dropdown -->
                                    <div class="relative">
                                        <button
                                            @click="showShareMenu = !showShareMenu"
                                            class="relative flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200 hover:scale-105 bg-theme-bg-hover text-theme-text-secondary hover:bg-theme-bg-tertiary"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                            </svg>
                                            <!-- Copied tooltip -->
                                            <span
                                                v-if="showCopied"
                                                class="absolute -top-10 left-1/2 -translate-x-1/2 px-3 py-1.5 text-xs font-medium text-white bg-gray-900 rounded-lg whitespace-nowrap z-50"
                                            >
                                                Link copied!
                                            </span>
                                        </button>

                                        <!-- Share Dropdown -->
                                        <Transition
                                            enter-active-class="transition duration-150 ease-out"
                                            enter-from-class="opacity-0 scale-95 translate-y-1"
                                            enter-to-class="opacity-100 scale-100 translate-y-0"
                                            leave-active-class="transition duration-100 ease-in"
                                            leave-from-class="opacity-100 scale-100 translate-y-0"
                                            leave-to-class="opacity-0 scale-95 translate-y-1"
                                        >
                                            <div
                                                v-if="showShareMenu"
                                                class="absolute right-0 bottom-full mb-2 w-48 rounded-xl shadow-lg overflow-hidden z-50 bg-theme-bg-card border border-theme-border"
                                            >
                                                <button @click="shareWhatsApp" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-theme-bg-hover text-theme-text-secondary transition-colors">
                                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                    WhatsApp
                                                </button>
                                                <button @click="shareX" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-theme-bg-hover text-theme-text-secondary transition-colors">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                                    X
                                                </button>
                                                <button @click="shareFacebook" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-theme-bg-hover text-theme-text-secondary transition-colors">
                                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                    Facebook
                                                </button>
                                                <button @click="copyLink" class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-theme-bg-hover text-theme-text-secondary transition-colors border-t border-theme-border">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                    Copy Link
                                                </button>
                                            </div>
                                        </Transition>

                                        <!-- Click-outside overlay -->
                                        <div v-if="showShareMenu" class="fixed inset-0 z-40" @click="closeShareMenu"></div>
                                    </div>
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

                <!-- Nearby Photos (by GPS location) -->
                <div v-if="nearbyPhotos?.length > 0" class="mt-16 pt-12 border-t border-theme-border">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-theme-text-primary">
                            More from {{ nearbyRegion }}
                        </h2>
                        <Link
                            :href="route('photos.map')"
                            class="text-sm font-medium transition-colors text-theme-accent hover:text-theme-accent-hover"
                        >
                            View Map
                        </Link>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <Link
                            v-for="nearby in nearbyPhotos"
                            :key="nearby.id"
                            :href="route('photos.show', nearby.slug)"
                            class="group"
                        >
                            <div
                                class="aspect-square rounded-2xl overflow-hidden ring-1 transition-all duration-300 group-hover:ring-2 ring-theme-border group-hover:ring-theme-accent"
                                :style="{ backgroundColor: nearby.dominant_color || 'var(--bg-secondary)' }"
                            >
                                <ResponsiveImage
                                    :image="nearby.image"
                                    :alt="nearby.title"
                                    sizes="(max-width: 768px) 50vw, (max-width: 1280px) 16vw, 210px"
                                    picture-class="block w-full h-full"
                                    img-class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                            </div>
                            <p class="text-sm font-medium mt-3 truncate transition-colors text-theme-text-secondary group-hover:text-theme-text-primary">
                                {{ nearby.title }}
                            </p>
                            <p v-if="nearby.distance_km > 0" class="text-xs text-theme-text-muted mt-0.5">
                                {{ nearby.distance_km < 1 ? 'Less than 1' : nearby.distance_km }} km away
                            </p>
                        </Link>
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
                                <ResponsiveImage
                                    :image="related.image"
                                    :alt="related.title"
                                    sizes="(max-width: 768px) 50vw, (max-width: 1280px) 25vw, 16vw"
                                    picture-class="block w-full h-full"
                                    img-class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
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
