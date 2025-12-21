<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link :href="route('home')">
                <svg class="w-20 h-20 text-gray-800" viewBox="0 0 50 50" fill="currentColor">
                    <path d="M25 5C13.97 5 5 13.97 5 25s8.97 20 20 20 20-8.97 20-20S36.03 5 25 5zm0 36c-8.82 0-16-7.18-16-16S16.18 9 25 9s16 7.18 16 16-7.18 16-16 16z"/>
                    <circle cx="25" cy="25" r="8"/>
                </svg>
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
