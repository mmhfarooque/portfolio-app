<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photo: Object
});

const downloadFormats = [
    { id: 'webp', name: 'WebP', description: 'Modern format, best quality and compression' },
    { id: 'jpeg', name: 'JPEG', description: 'Universal compatibility, slightly larger file' },
];

const download = (format) => {
    window.location.href = route('photos.download', { photo: props.photo.slug, format });
};
</script>

<template>
    <Head :title="`Download - ${photo.title}`" />

    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link :href="route('photos.show', photo.slug)" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to photo
            </Link>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Photo Preview -->
                <div class="aspect-video bg-gray-200">
                    <img :src="`/storage/${photo.display_path || photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-contain" />
                </div>

                <div class="p-6">
                    <h1 class="text-2xl font-bold text-gray-900">Download: {{ photo.title }}</h1>
                    <p class="text-gray-600 mt-2">Select a format to download this photo.</p>

                    <div class="mt-6 space-y-3">
                        <button v-for="format in downloadFormats" :key="format.id" @click="download(format.id)" class="w-full flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition group">
                            <div class="text-left">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600">{{ format.name }}</h3>
                                <p class="text-sm text-gray-500">{{ format.description }}</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>

                    <p class="mt-6 text-xs text-gray-500 text-center">
                        By downloading, you agree to use this image only for personal, non-commercial purposes.
                        The image may contain a watermark.
                    </p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
