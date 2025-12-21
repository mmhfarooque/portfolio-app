<script setup>
import { usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';

const page = usePage();
const appName = page.props.appName || 'Photography Portfolio';
</script>

<template>
    <div class="min-h-screen bg-white">
        <FlashMessages />

        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('home')" class="flex items-center gap-2">
                            <svg class="h-8 w-8 text-gray-800" viewBox="0 0 50 50" fill="currentColor">
                                <path d="M25 5C13.97 5 5 13.97 5 25s8.97 20 20 20 20-8.97 20-20S36.03 5 25 5zm0 36c-8.82 0-16-7.18-16-16S16.18 9 25 9s16 7.18 16 16-7.18 16-16 16z"/>
                                <circle cx="25" cy="25" r="8"/>
                            </svg>
                            <span class="font-semibold text-gray-900">{{ appName }}</span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-6">
                        <Link :href="route('gallery.index')" class="text-gray-600 hover:text-gray-900 transition">
                            Gallery
                        </Link>
                        <Link :href="route('blog.index')" class="text-gray-600 hover:text-gray-900 transition">
                            Blog
                        </Link>
                        <Link :href="route('contact.show')" class="text-gray-600 hover:text-gray-900 transition">
                            Contact
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 border-t border-gray-100 py-8 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
                <p>&copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
