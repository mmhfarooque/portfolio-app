<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';

const props = defineProps({
    theme: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const appName = page.props.appName || 'Mahmud Farooque';
const mobileMenuOpen = ref(false);

// Get theme from props or page props
const themeData = computed(() => props.theme || page.props.theme || {});
const isDark = computed(() => themeData.value?.isDark ?? false);

// Generate CSS variables from theme colors
const themeStyles = computed(() => {
    const colors = themeData.value?.colors || {};
    const styles = themeData.value?.styles || {};

    let css = '';

    // Add color variables
    Object.entries(colors).forEach(([key, value]) => {
        css += `--${key}: ${value}; `;
    });

    // Add style variables
    Object.entries(styles).forEach(([key, value]) => {
        css += `--${key}: ${value}; `;
    });

    return css;
});
</script>

<template>
    <div
        class="min-h-screen transition-colors duration-300"
        :class="isDark ? 'bg-[var(--bg-primary)]' : 'bg-[var(--bg-primary,#fafaf9)]'"
        :style="themeStyles"
    >
        <FlashMessages />

        <!-- Navigation -->
        <nav
            class="backdrop-blur-xl border-b sticky top-0 z-40 transition-colors duration-300 shadow-md"
            :class="isDark
                ? 'bg-[var(--bg-secondary)]/95 border-[var(--border)]'
                : 'bg-white/95 border-[var(--border,#e5e5e5)]'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('home')" class="flex items-center">
                            <span
                                class="text-lg font-semibold tracking-tight transition-colors"
                                :class="isDark ? 'text-white' : 'text-gray-900'"
                                :style="{ fontFamily: 'var(--font-heading, inherit)' }"
                            >{{ appName }}</span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-8">
                        <Link
                            :href="route('photos.index')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white'
                                : 'text-gray-600 hover:text-gray-900'"
                        >
                            Gallery
                        </Link>
                        <Link
                            :href="route('series.index')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white'
                                : 'text-gray-600 hover:text-gray-900'"
                        >
                            Series
                        </Link>
                        <Link
                            :href="route('blog.index')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white'
                                : 'text-gray-600 hover:text-gray-900'"
                        >
                            Blog
                        </Link>
                        <Link
                            :href="route('about')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white'
                                : 'text-gray-600 hover:text-gray-900'"
                        >
                            About
                        </Link>
                        <Link
                            :href="route('contact')"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white'
                                : 'text-gray-600 hover:text-gray-900'"
                        >
                            Contact
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-md transition-colors"
                            :class="isDark
                                ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        >
                            <svg v-if="!mobileMenuOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div
                v-show="mobileMenuOpen"
                class="md:hidden border-t transition-colors"
                :class="isDark ? 'border-gray-700 bg-gray-900/95' : 'border-gray-200 bg-white/95'"
            >
                <div class="px-4 py-3 space-y-1">
                    <Link
                        :href="route('photos.index')"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="isDark
                            ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        @click="mobileMenuOpen = false"
                    >
                        Gallery
                    </Link>
                    <Link
                        :href="route('series.index')"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="isDark
                            ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        @click="mobileMenuOpen = false"
                    >
                        Series
                    </Link>
                    <Link
                        :href="route('blog.index')"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="isDark
                            ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        @click="mobileMenuOpen = false"
                    >
                        Blog
                    </Link>
                    <Link
                        :href="route('about')"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="isDark
                            ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        @click="mobileMenuOpen = false"
                    >
                        About
                    </Link>
                    <Link
                        :href="route('contact')"
                        class="block px-3 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="isDark
                            ? 'text-gray-300 hover:text-white hover:bg-gray-700'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                        @click="mobileMenuOpen = false"
                    >
                        Contact
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer with subtle separation -->
        <footer
            class="border-t transition-colors duration-300"
            :class="isDark
                ? 'bg-[var(--bg-secondary)] border-[var(--border)]'
                : 'bg-[var(--bg-tertiary,#e7e5e4)] border-[var(--border,#d6d3d1)]'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <!-- Main footer content -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <!-- Brand & Tagline -->
                    <div>
                        <Link :href="route('home')">
                            <span
                                class="text-lg font-semibold tracking-tight"
                                :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
                            >{{ appName }}</span>
                        </Link>
                        <p
                            class="text-sm mt-1"
                            :class="isDark ? 'text-[var(--text-muted)]' : 'text-[var(--text-muted,#a8a29e)]'"
                        >Capturing moments, one frame at a time.</p>
                    </div>

                    <!-- Horizontal Navigation Links -->
                    <nav class="flex flex-wrap items-center gap-6">
                        <Link
                            :href="route('photos.index')"
                            class="text-sm transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                                : 'text-[var(--text-muted,#a8a29e)] hover:text-[var(--text-primary,#1c1917)]'"
                        >Gallery</Link>
                        <Link
                            :href="route('series.index')"
                            class="text-sm transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                                : 'text-[var(--text-muted,#a8a29e)] hover:text-[var(--text-primary,#1c1917)]'"
                        >Series</Link>
                        <Link
                            :href="route('blog.index')"
                            class="text-sm transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                                : 'text-[var(--text-muted,#a8a29e)] hover:text-[var(--text-primary,#1c1917)]'"
                        >Blog</Link>
                        <Link
                            :href="route('about')"
                            class="text-sm transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                                : 'text-[var(--text-muted,#a8a29e)] hover:text-[var(--text-primary,#1c1917)]'"
                        >About</Link>
                        <Link
                            :href="route('contact')"
                            class="text-sm transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-muted)] hover:text-[var(--text-primary)]'
                                : 'text-[var(--text-muted,#a8a29e)] hover:text-[var(--text-primary,#1c1917)]'"
                        >Contact</Link>
                    </nav>
                </div>

                <!-- Copyright -->
                <div
                    class="mt-8 pt-6 border-t text-sm"
                    :class="isDark
                        ? 'border-[var(--border)] text-[var(--text-muted)]'
                        : 'border-[var(--border,#d6d3d1)] text-[var(--text-muted,#a8a29e)]'"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <span>&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</span>
                        <span>All images are copyrighted.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
