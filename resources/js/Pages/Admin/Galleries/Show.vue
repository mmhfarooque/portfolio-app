<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    gallery: Object,
    photos: Object
});

const showRemoveModal = ref(false);
const photoToRemove = ref(null);
const isRemoving = ref(false);

const confirmRemove = (photo) => {
    photoToRemove.value = photo;
    showRemoveModal.value = true;
};

const removePhoto = () => {
    if (!photoToRemove.value) return;

    isRemoving.value = true;
    router.delete(route('admin.galleries.remove-photo', [props.gallery.id, photoToRemove.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            isRemoving.value = false;
            showRemoveModal.value = false;
            photoToRemove.value = null;
        }
    });
};
</script>

<template>
    <Head :title="gallery.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.galleries.index')" class="text-gray-600 hover:text-gray-900">
                        &larr;
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ gallery.name }}</h2>
                </div>
                <Link :href="route('admin.galleries.edit', gallery.id)">
                    <PrimaryButton>Edit Gallery</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Gallery Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                    gallery.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                ]"
                            >
                                {{ gallery.is_published ? 'Published' : 'Draft' }}
                            </span>
                            <span
                                v-if="gallery.is_featured"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                            >
                                Featured
                            </span>
                            <span
                                v-if="gallery.password"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"
                            >
                                Password Protected
                            </span>
                        </div>
                        <p v-if="gallery.description" class="mt-2 text-gray-600">{{ gallery.description }}</p>
                    </div>
                </div>

                <!-- Photos Grid -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Photos in this Gallery</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="photos.data.length > 0">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <div
                                    v-for="photo in photos.data"
                                    :key="photo.id"
                                    class="relative group"
                                >
                                    <Link :href="route('admin.photos.edit', photo.id)" class="block">
                                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                            <img
                                                v-if="photo.thumbnail_path"
                                                :src="`/storage/${photo.thumbnail_path}`"
                                                :alt="photo.title"
                                                class="w-full h-full object-cover group-hover:opacity-75 transition"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-sm">
                                                No Image
                                            </div>
                                        </div>
                                    </Link>
                                    <button
                                        @click="confirmRemove(photo)"
                                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition bg-red-600 hover:bg-red-700 text-white rounded-full p-1"
                                        title="Remove from gallery"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <p class="text-sm text-gray-900 truncate mt-2">{{ photo.title }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <Pagination :links="photos.links" />
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No photos in this gallery</h3>
                            <p class="mt-1 text-gray-500">Add photos to this gallery from the photo edit page.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Photo Confirmation Modal -->
        <ConfirmModal
            :show="showRemoveModal"
            title="Remove Photo"
            :message="`Remove '${photoToRemove?.title}' from this gallery? The photo will not be deleted.`"
            confirm-text="Remove"
            variant="warning"
            :processing="isRemoving"
            @confirm="removePhoto"
            @close="showRemoveModal = false"
        />
    </AuthenticatedLayout>
</template>
