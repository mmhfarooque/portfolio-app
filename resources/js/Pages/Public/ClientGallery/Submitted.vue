<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    gallery: Object,
    selectedCount: Number
});
</script>

<template>
    <Head :title="`${gallery.name} - Selection Submitted`" />

    <PublicLayout>
        <div class="min-h-[60vh] flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center">
                <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mt-4">Selection Submitted!</h1>
                <p class="text-gray-600 mt-2">
                    You have selected <strong>{{ selectedCount }}</strong> photos from <strong>{{ gallery.name }}</strong>.
                </p>
                <p class="text-sm text-gray-500 mt-4">
                    The photographer has been notified and will be in touch soon.
                </p>
                <Link href="/" class="mt-6 inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Return Home
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
