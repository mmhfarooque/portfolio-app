<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    success: Boolean,
    message: String
});
</script>

<template>
    <Head title="Newsletter Subscription" />

    <PublicLayout>
        <div class="min-h-[60vh] flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center">
                <div v-if="success" class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div v-else class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ success ? 'Subscription Confirmed!' : 'Error' }}</h1>
                <p class="text-gray-600 mt-2">{{ message }}</p>
                <Link href="/" class="mt-6 inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Return Home
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
