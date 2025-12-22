<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photo: Object,
    relatedPhotos: Array,
    previousPhoto: Object,
    nextPhoto: Object
});

const page = usePage();
const themeData = computed(() => page.props.theme || {});
const isDark = computed(() => themeData.value?.isDark ?? false);
</script>

<template>
    <Head>
        <title>{{ photo.seo_title || photo.title }}</title>
        <meta v-if="photo.meta_description" name="description" :content="photo.meta_description" />
    </Head>

    <PublicLayout>
        <!-- Hero Image Section -->
        <div class="relative">
            <!-- Full-width image with subtle vignette -->
            <div class="relative bg-black">
                <img
                    :src="`/storage/${photo.watermarked_path || photo.display_path}`"
                    :alt="photo.title"
                    class="w-full max-h-[85vh] object-contain mx-auto"
                />
                <!-- Subtle vignette overlay -->
                <div class="absolute inset-0 pointer-events-none bg-gradient-to-t from-black/20 via-transparent to-black/10"></div>
            </div>

            <!-- Navigation Arrows - Floating on Image -->
            <div class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4 pointer-events-none">
                <Link
                    v-if="previousPhoto"
                    :href="route('photos.show', previousPhoto.slug)"
                    class="pointer-events-auto p-3 bg-black/40 backdrop-blur-sm text-white/90 rounded-full hover:bg-black/60 hover:text-white transition-all duration-200 group"
                    title="Previous photo"
                >
                    <svg class="w-6 h-6 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div v-else></div>
                <Link
                    v-if="nextPhoto"
                    :href="route('photos.show', nextPhoto.slug)"
                    class="pointer-events-auto p-3 bg-black/40 backdrop-blur-sm text-white/90 rounded-full hover:bg-black/60 hover:text-white transition-all duration-200 group"
                    title="Next photo"
                >
                    <svg class="w-6 h-6 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>

        <!-- Content Section -->
        <div
            class="transition-colors duration-300"
            :class="isDark ? 'bg-[var(--bg-primary)]' : 'bg-[var(--bg-primary,#fafaf9)]'"
        >
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <!-- Back Button -->
                <Link
                    :href="route('photos.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium mb-8 transition-colors"
                    :class="isDark
                        ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                        : 'text-[var(--text-muted,#78716c)] hover:text-[var(--text-primary,#1c1917)]'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Gallery
                </Link>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
                    <!-- Left Column - Title, Description, Story -->
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <h1
                                class="text-3xl sm:text-4xl font-bold tracking-tight mb-4"
                                :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
                                :style="{ fontFamily: 'var(--font-heading, inherit)' }"
                            >
                                {{ photo.title }}
                            </h1>

                            <p
                                v-if="photo.description"
                                class="text-lg leading-relaxed"
                                :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'"
                            >
                                {{ photo.description }}
                            </p>
                        </div>

                        <!-- Story -->
                        <div
                            v-if="photo.story"
                            class="prose prose-lg max-w-none"
                            :class="isDark ? 'prose-invert' : ''"
                        >
                            <div
                                class="leading-relaxed"
                                :class="isDark ? 'text-[var(--text-secondary)]' : 'text-[var(--text-secondary,#57534e)]'"
                                v-html="photo.story"
                            ></div>
                        </div>
                    </div>

                    <!-- Right Column - Photo Details Sidebar -->
                    <div class="space-y-6">
                        <!-- Photo Details Card -->
                        <div
                            class="rounded-2xl p-6 transition-colors"
                            :class="isDark
                                ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                : 'bg-[var(--bg-card,#ffffff)] border border-[var(--border,#e7e5e4)] shadow-sm'"
                        >
                            <h3
                                class="text-xs font-semibold uppercase tracking-wider mb-4"
                                :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'"
                            >
                                Photo Details
                            </h3>

                            <dl class="space-y-4">
                                <!-- Location -->
                                <div v-if="photo.location_name" class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                    >
                                        <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Location</dt>
                                        <dd class="text-sm font-medium" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.location_name }}</dd>
                                    </div>
                                </div>

                                <!-- Category -->
                                <div v-if="photo.category" class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                    >
                                        <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Category</dt>
                                        <dd>
                                            <Link
                                                :href="route('photos.index', { category: photo.category.slug })"
                                                class="text-sm font-medium transition-colors"
                                                :class="isDark
                                                    ? 'text-[var(--accent)] hover:text-[var(--accent-hover)]'
                                                    : 'text-[var(--accent,#d97706)] hover:text-[var(--accent-hover,#b45309)]'"
                                            >
                                                {{ photo.category.name }}
                                            </Link>
                                        </dd>
                                    </div>
                                </div>

                                <!-- Views -->
                                <div v-if="photo.views" class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                    >
                                        <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Views</dt>
                                        <dd class="text-sm font-medium" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.views.toLocaleString() }}</dd>
                                    </div>
                                </div>
                            </dl>
                        </div>

                        <!-- Camera Settings Card -->
                        <div
                            v-if="photo.formatted_exif"
                            class="rounded-2xl p-6 transition-colors"
                            :class="isDark
                                ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                : 'bg-[var(--bg-card,#ffffff)] border border-[var(--border,#e7e5e4)] shadow-sm'"
                        >
                            <h3
                                class="text-xs font-semibold uppercase tracking-wider mb-4"
                                :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'"
                            >
                                Camera Settings
                            </h3>

                            <dl class="space-y-4">
                                <!-- Camera -->
                                <div v-if="photo.formatted_exif.camera" class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                    >
                                        <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Camera</dt>
                                        <dd class="text-sm font-medium" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.camera }}</dd>
                                    </div>
                                </div>

                                <!-- Lens -->
                                <div v-if="photo.formatted_exif.lens" class="flex items-start gap-3">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                    >
                                        <!-- Lens barrel icon -->
                                        <svg class="w-4 h-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <ellipse cx="12" cy="12" rx="8" ry="8" />
                                            <ellipse cx="12" cy="12" rx="5" ry="5" />
                                            <ellipse cx="12" cy="12" rx="2" ry="2" fill="currentColor" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Lens</dt>
                                        <dd class="text-sm font-medium" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.lens }}</dd>
                                    </div>
                                </div>

                                <!-- Technical Details Grid -->
                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    <!-- Focal Length -->
                                    <div v-if="photo.formatted_exif.focal_length" class="flex items-center gap-2">
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center"
                                            :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                        >
                                            <!-- Focal length / zoom icon -->
                                            <svg class="w-3.5 h-3.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="2" y="8" width="20" height="8" rx="2" />
                                                <line x1="6" y1="8" x2="6" y2="16" />
                                                <line x1="10" y1="8" x2="10" y2="16" />
                                                <circle cx="18" cy="12" r="2" fill="currentColor" />
                                            </svg>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] uppercase tracking-wide" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Focal</dt>
                                            <dd class="text-sm font-semibold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.focal_length }}</dd>
                                        </div>
                                    </div>

                                    <!-- Aperture -->
                                    <div v-if="photo.formatted_exif.aperture" class="flex items-center gap-2">
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center"
                                            :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                        >
                                            <!-- Aperture blades icon -->
                                            <svg class="w-3.5 h-3.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="M12 2 L14 10 L12 12" />
                                                <path d="M21.8 8 L14 10 L12 12" />
                                                <path d="M21.8 16 L14 14 L12 12" />
                                                <path d="M12 22 L10 14 L12 12" />
                                                <path d="M2.2 16 L10 14 L12 12" />
                                                <path d="M2.2 8 L10 10 L12 12" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] uppercase tracking-wide" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Aperture</dt>
                                            <dd class="text-sm font-semibold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.aperture }}</dd>
                                        </div>
                                    </div>

                                    <!-- Shutter Speed -->
                                    <div v-if="photo.formatted_exif.shutter" class="flex items-center gap-2">
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center"
                                            :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                        >
                                            <svg class="w-3.5 h-3.5" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                                <path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2" />
                                            </svg>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] uppercase tracking-wide" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">Shutter</dt>
                                            <dd class="text-sm font-semibold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.shutter }}</dd>
                                        </div>
                                    </div>

                                    <!-- ISO -->
                                    <div v-if="photo.formatted_exif.iso" class="flex items-center gap-2">
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center"
                                            :class="isDark ? 'bg-[var(--bg-hover)]' : 'bg-[var(--bg-tertiary,#f5f5f4)]'"
                                        >
                                            <span class="text-[10px] font-bold" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#78716c)]'">ISO</span>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] uppercase tracking-wide" :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'">ISO</dt>
                                            <dd class="text-sm font-semibold" :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'">{{ photo.formatted_exif.iso }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </dl>
                        </div>

                        <!-- Tags Card -->
                        <div
                            v-if="photo.tags?.length > 0"
                            class="rounded-2xl p-6 transition-colors"
                            :class="isDark
                                ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
                                : 'bg-[var(--bg-card,#ffffff)] border border-[var(--border,#e7e5e4)] shadow-sm'"
                        >
                            <h3
                                class="text-xs font-semibold uppercase tracking-wider mb-4"
                                :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'"
                            >
                                Tags
                            </h3>

                            <div class="flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in photo.tags"
                                    :key="tag.id"
                                    :href="route('photos.index', { tag: tag.slug })"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200"
                                    :class="isDark
                                        ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--accent)]/20 hover:text-[var(--accent)]'
                                        : 'bg-[var(--bg-tertiary,#f5f5f4)] text-[var(--text-secondary,#57534e)] hover:bg-[var(--accent,#d97706)]/10 hover:text-[var(--accent,#d97706)]'"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Photos -->
                <div v-if="relatedPhotos?.length > 0" class="mt-16 pt-12 border-t" :class="isDark ? 'border-[var(--border)]' : 'border-[var(--border,#e7e5e4)]'">
                    <h2
                        class="text-xl font-semibold mb-6"
                        :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
                    >
                        You May Also Like
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <Link
                            v-for="related in relatedPhotos"
                            :key="related.id"
                            :href="route('photos.show', related.slug)"
                            class="group"
                        >
                            <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 ring-1 ring-black/5">
                                <img
                                    :src="`/storage/${related.thumbnail_path}`"
                                    :alt="related.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                            </div>
                            <p
                                class="text-sm mt-2 truncate transition-colors"
                                :class="isDark
                                    ? 'text-[var(--text-secondary)] group-hover:text-[var(--text-primary)]'
                                    : 'text-[var(--text-secondary,#57534e)] group-hover:text-[var(--text-primary,#1c1917)]'"
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
