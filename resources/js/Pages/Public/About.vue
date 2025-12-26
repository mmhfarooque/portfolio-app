<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    content: String
});
</script>

<template>
    <SeoHead
        title="About Mahmud Farooque - Photographer"
        description="Learn about Mahmud Farooque, a photographer based in Bangladesh capturing landscapes, travel, and street photography from around the world."
        type="website"
        url="https://mfaruk.com/about"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'About' }
        ]"
    />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">About</h1>

            <div v-if="content" class="prose prose-lg max-w-none" v-html="content"></div>

            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <p class="mt-4 text-gray-500">About content coming soon.</p>
            </div>
        </div>
    </PublicLayout>
</template>
