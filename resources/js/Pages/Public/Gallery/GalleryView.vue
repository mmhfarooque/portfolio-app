<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    gallery: Object,
    photos: Object,
    needsPassword: Boolean
});

const form = useForm({
    password: ''
});

const submitPassword = () => {
    form.post(route('gallery.password', props.gallery.slug));
};
</script>

<template>
    <Head :title="`${gallery.name} - Gallery`" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Password Protected -->
            <div v-if="needsPassword" class="max-w-md mx-auto text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 mt-4">{{ gallery.name }}</h1>
                <p class="text-gray-600 mt-2">This gallery is password protected.</p>
                <form @submit.prevent="submitPassword" class="mt-6">
                    <input type="password" v-model="form.password" placeholder="Enter password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required />
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" :disabled="form.processing">Enter Gallery</button>
                </form>
            </div>

            <!-- Gallery Content -->
            <div v-else>
                <div class="mb-8">
                    <Link href="/gallery" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to Gallery</Link>
                    <h1 class="text-3xl font-bold text-gray-900 mt-4">{{ gallery.name }}</h1>
                    <p v-if="gallery.description" class="mt-2 text-gray-600">{{ gallery.description }}</p>
                </div>

                <div v-if="photos.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link v-for="photo in photos.data" :key="photo.id" :href="route('photo.show', photo.slug)" class="group">
                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                            <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                    </Link>
                </div>
                <div v-else class="text-center py-12">
                    <p class="text-gray-500">No photos in this gallery.</p>
                </div>

                <div v-if="photos.data.length > 0" class="mt-8">
                    <Pagination :links="photos.links" />
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
