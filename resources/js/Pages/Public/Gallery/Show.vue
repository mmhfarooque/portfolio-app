<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import LikeButton from '@/Components/Photo/LikeButton.vue';
import CommentSection from '@/Components/Photo/CommentSection.vue';

const props = defineProps({
    photo: Object,
    relatedPhotos: Array,
    previousPhoto: Object,
    nextPhoto: Object
});

const page = usePage();
const themeData = computed(() => page.props.theme || {});
const isDark = computed(() => themeData.value?.isDark ?? false);

// Sticky sidebar handling
const sidebarRef = ref(null);
const isSticky = ref(false);

const handleScroll = () => {
    if (window.innerWidth >= 1024 && sidebarRef.value) {
        const rect = sidebarRef.value.getBoundingClientRect();
        isSticky.value = rect.top <= 100;
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
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
    <Head>
        <title>{{ photo.seo_title || photo.title }}</title>
        <meta v-if="photo.meta_description" name="description" :content="photo.meta_description" />
    </Head>

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
        <div
            class="transition-colors duration-300"
            :class="isDark ? 'bg-[var(--bg-primary)]' : 'bg-[var(--bg-primary,#fafaf9)]'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm mb-8">
                    <Link
                        :href="route('photos.index')"
                        class="transition-colors"
                        :class="isDark
                            ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                            : 'text-[var(--text-muted,#78716c)] hover:text-[var(--text-primary,#1c1917)]'"
                    >
                        Gallery
                    </Link>
                    <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link
                        v-if="photo.category"
                        :href="route('photos.index', { category: photo.category.slug })"
                        class="transition-colors"
                        :class="isDark
                            ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                            : 'text-[var(--text-muted,#78716c)] hover:text-[var(--text-primary,#1c1917)]'"
                    >
                        {{ photo.category.name }}
                    </Link>
                    <svg v-if="photo.category" class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'" class="truncate max-w-[200px]">
                        {{ photo.title }}
                    </span>
                </nav>

                <!-- Main Content Grid -->
                <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                    <!-- Left Column - Title, Description, Story, Comments -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Title & Description Card -->
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight mb-4"
                                :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
                                :style="{ fontFamily: 'var(--font-heading, inherit)' }"
                            >
                                {{ photo.title }}
                            </h1>

                            <!-- Date & Location Meta -->
                            <div class="flex flex-wrap items-center gap-4 mb-6">
                                <div v-if="photo.taken_at || photo.created_at" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-sm" :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'">
                                        {{ formatDate(photo.taken_at || photo.created_at) }}
                                    </span>
                                </div>
                                <div v-if="photo.location_name" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm" :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'">
                                        {{ photo.location_name }}
                                    </span>
                                </div>
                            </div>

                            <p
                                v-if="photo.description"
                                class="text-lg leading-relaxed"
                                :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'"
                            >
                                {{ photo.description }}
                            </p>
                        </div>

                        <!-- Story Section -->
                        <div
                            v-if="photo.story"
                            class="rounded-2xl p-6 lg:p-8 transition-colors"
                            :class="isDark
                                ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                : 'bg-white border border-[var(--border,#e7e5e4)] shadow-sm'"
                        >
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="isDark ? 'bg-[var(--accent)]/20' : 'bg-amber-100'"
                                >
                                    <svg class="w-5 h-5" :class="isDark ? 'text-[var(--accent)]' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <h2
                                    class="text-lg font-semibold"
                                    :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
                                >
                                    The Story Behind
                                </h2>
                            </div>
                            <div
                                class="prose prose-lg max-w-none leading-relaxed"
                                :class="isDark ? 'prose-invert text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'"
                                v-html="photo.story"
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
                            <div
                                class="rounded-2xl p-5 transition-colors"
                                :class="isDark
                                    ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                    : 'bg-white border border-[var(--border,#e7e5e4)] shadow-sm'"
                            >
                                <div class="flex items-center justify-between">
                                    <!-- Like Button -->
                                    <LikeButton
                                        :photo-slug="photo.slug"
                                        :initial-likes-count="photo.likes_count"
                                    />

                                    <!-- Comment Link -->
                                    <a
                                        href="#comments"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200 hover:scale-105"
                                        :class="isDark
                                            ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--bg-tertiary)]'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
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
                                        class="relative flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200 hover:scale-105"
                                        :class="isDark
                                            ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--bg-tertiary)]'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
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
                                    class="flex items-center justify-center gap-2 mt-4 pt-4 border-t"
                                    :class="isDark ? 'border-[var(--border)]' : 'border-gray-100'"
                                >
                                    <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span class="text-sm" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                        {{ photo.views.toLocaleString() }} views
                                    </span>
                                </div>
                            </div>

                            <!-- Camera & Settings Card -->
                            <div
                                v-if="photo.formatted_exif"
                                class="rounded-2xl overflow-hidden transition-colors"
                                :class="isDark
                                    ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                    : 'bg-white border border-[var(--border,#e7e5e4)] shadow-sm'"
                            >
                                <!-- Camera Header -->
                                <div
                                    class="px-5 py-4 border-b"
                                    :class="isDark ? 'border-[var(--border)] bg-[var(--bg-hover)]' : 'border-gray-100 bg-gray-50'"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl flex items-center justify-center"
                                            :class="isDark ? 'bg-[var(--bg-primary)]' : 'bg-white shadow-sm'"
                                        >
                                            <svg class="w-5 h-5" :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold truncate" :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                                                {{ photo.formatted_exif.camera || 'Camera' }}
                                            </p>
                                            <p v-if="photo.formatted_exif.lens" class="text-xs truncate" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                                {{ photo.formatted_exif.lens }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Settings Grid -->
                                <div class="p-5">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Focal Length -->
                                        <div v-if="photo.formatted_exif.focal_length" class="text-center p-3 rounded-xl" :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-gray-50'">
                                            <p class="text-lg font-bold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                                                {{ photo.formatted_exif.focal_length }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                                Focal Length
                                            </p>
                                        </div>

                                        <!-- Aperture -->
                                        <div v-if="photo.formatted_exif.aperture" class="text-center p-3 rounded-xl" :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-gray-50'">
                                            <p class="text-lg font-bold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                                                {{ photo.formatted_exif.aperture }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                                Aperture
                                            </p>
                                        </div>

                                        <!-- Shutter Speed -->
                                        <div v-if="photo.formatted_exif.shutter" class="text-center p-3 rounded-xl" :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-gray-50'">
                                            <p class="text-lg font-bold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                                                {{ photo.formatted_exif.shutter }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                                Shutter
                                            </p>
                                        </div>

                                        <!-- ISO -->
                                        <div v-if="photo.formatted_exif.iso" class="text-center p-3 rounded-xl" :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-gray-50'">
                                            <p class="text-lg font-bold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                                                {{ photo.formatted_exif.iso }}
                                            </p>
                                            <p class="text-xs uppercase tracking-wide mt-0.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                                                ISO
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Card -->
                            <div
                                v-if="photo.category"
                                class="rounded-2xl p-5 transition-colors"
                                :class="isDark
                                    ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                    : 'bg-white border border-[var(--border,#e7e5e4)] shadow-sm'"
                            >
                                <h3
                                    class="text-xs font-semibold uppercase tracking-wider mb-3"
                                    :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-400'"
                                >
                                    Category
                                </h3>
                                <Link
                                    :href="route('photos.index', { category: photo.category.slug })"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 hover:scale-105"
                                    :class="isDark
                                        ? 'bg-[var(--accent)]/20 text-[var(--accent)] hover:bg-[var(--accent)]/30'
                                        : 'bg-amber-100 text-amber-700 hover:bg-amber-200'"
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
                                class="rounded-2xl p-5 transition-colors"
                                :class="isDark
                                    ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                    : 'bg-white border border-[var(--border,#e7e5e4)] shadow-sm'"
                            >
                                <h3
                                    class="text-xs font-semibold uppercase tracking-wider mb-3"
                                    :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-400'"
                                >
                                    Tags
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-for="tag in photo.tags"
                                        :key="tag.id"
                                        :href="route('photos.index', { tag: tag.slug })"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200 hover:scale-105"
                                        :class="isDark
                                            ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--accent)]/20 hover:text-[var(--accent)]'
                                            : 'bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700'"
                                    >
                                        #{{ tag.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Photos -->
                <div v-if="relatedPhotos?.length > 0" class="mt-16 pt-12 border-t" :class="isDark ? 'border-[var(--border)]' : 'border-gray-200'">
                    <div class="flex items-center justify-between mb-8">
                        <h2
                            class="text-2xl font-bold"
                            :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'"
                        >
                            You May Also Like
                        </h2>
                        <Link
                            :href="route('photos.index')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-[var(--accent)] hover:text-[var(--accent-hover)]'
                                : 'text-amber-600 hover:text-amber-700'"
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
                                class="aspect-square rounded-2xl overflow-hidden ring-1 transition-all duration-300 group-hover:ring-2"
                                :class="isDark
                                    ? 'bg-[var(--bg-secondary)] ring-[var(--border)] group-hover:ring-[var(--accent)]'
                                    : 'bg-gray-100 ring-gray-200 group-hover:ring-amber-400'"
                            >
                                <img
                                    :src="`/storage/${related.thumbnail_path}`"
                                    :alt="related.title"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                    loading="lazy"
                                />
                            </div>
                            <p
                                class="text-sm font-medium mt-3 truncate transition-colors"
                                :class="isDark
                                    ? 'text-[var(--text-secondary)] group-hover:text-[var(--text-primary)]'
                                    : 'text-gray-600 group-hover:text-gray-900'"
                            >
                                {{ related.title }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
