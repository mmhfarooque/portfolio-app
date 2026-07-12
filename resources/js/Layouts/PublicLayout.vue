<script setup>
import { ref, computed, onMounted } from 'vue';
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
const themeName = computed(() => themeData.value?.name || 'dark');

// Set data-theme on document root so CSS variables cascade everywhere
onMounted(() => {
    document.documentElement.setAttribute('data-theme', themeName.value);
});
</script>

<template>
    <div
        class="min-h-screen transition-colors duration-300 bg-theme-bg-primary"
        :data-theme="themeName"
    >
        <FlashMessages />

        <!-- Navigation -->
        <nav class="backdrop-blur-xl border-b sticky top-0 z-40 transition-colors duration-300 shadow-md bg-theme-bg-secondary/95 border-theme-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('home')" class="flex items-center">
                            <span class="text-lg font-semibold tracking-tight transition-colors text-theme-text-primary">{{ appName }}</span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-8">
                        <Link :href="route('photos.index')" class="text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary">Gallery</Link>
                        <Link :href="route('blog.index')" class="text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary">Blog</Link>
                        <Link :href="route('about')" class="text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary">About</Link>
                        <Link :href="route('contact')" class="text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary">Contact</Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-md transition-colors text-theme-text-secondary hover:text-theme-text-primary hover:bg-theme-bg-hover"
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
                class="md:hidden border-t transition-colors border-theme-border bg-theme-bg-secondary/95"
            >
                <div class="px-4 py-3 space-y-1">
                    <Link :href="route('photos.index')" class="block px-3 py-2 rounded-md text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary hover:bg-theme-bg-hover" @click="mobileMenuOpen = false">Gallery</Link>
                    <Link :href="route('blog.index')" class="block px-3 py-2 rounded-md text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary hover:bg-theme-bg-hover" @click="mobileMenuOpen = false">Blog</Link>
                    <Link :href="route('about')" class="block px-3 py-2 rounded-md text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary hover:bg-theme-bg-hover" @click="mobileMenuOpen = false">About</Link>
                    <Link :href="route('contact')" class="block px-3 py-2 rounded-md text-sm font-medium transition-colors text-theme-text-secondary hover:text-theme-text-primary hover:bg-theme-bg-hover" @click="mobileMenuOpen = false">Contact</Link>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t transition-colors duration-300 bg-theme-bg-secondary border-theme-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <Link :href="route('home')">
                            <span class="text-lg font-semibold tracking-tight text-theme-text-primary">{{ appName }}</span>
                        </Link>
                        <p class="text-sm mt-1 text-theme-text-muted">Capturing moments, one frame at a time.</p>
                    </div>

                    <nav class="flex flex-wrap items-center gap-6">
                        <Link :href="route('photos.index')" class="text-sm transition-colors text-theme-text-muted hover:text-theme-text-primary">Gallery</Link>
                        <Link :href="route('blog.index')" class="text-sm transition-colors text-theme-text-muted hover:text-theme-text-primary">Blog</Link>
                        <Link :href="route('about')" class="text-sm transition-colors text-theme-text-muted hover:text-theme-text-primary">About</Link>
                        <Link :href="route('contact')" class="text-sm transition-colors text-theme-text-muted hover:text-theme-text-primary">Contact</Link>
                    </nav>
                </div>

                <div class="mt-8 pt-6 border-t text-sm border-theme-border text-theme-text-muted">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <span>&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</span>
                        <span>All images are copyrighted.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
