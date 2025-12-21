<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    gallery: Object
});
</script>

<template>
    <Head :title="`${gallery.name} - Expired`" />

    <PublicLayout>
        <div class="min-h-[60vh] flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 mt-4">Gallery Expired</h1>
                <p class="text-gray-600 mt-2">This gallery is no longer available.</p>
                <p class="text-sm text-gray-500 mt-4">
                    Please contact the photographer if you need access to these photos.
                </p>
                <Link href="/" class="mt-6 inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Return Home
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
