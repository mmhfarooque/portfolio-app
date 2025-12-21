<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    galleries: Array
});

const showDeleteModal = ref(false);
const galleryToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (gallery) => {
    galleryToDelete.value = gallery;
    showDeleteModal.value = true;
};

const deleteGallery = () => {
    if (!galleryToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.galleries.destroy', galleryToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            galleryToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Galleries" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Galleries</h2>
                <Link :href="route('admin.galleries.create')">
                    <PrimaryButton>Create Gallery</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="galleries.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="gallery in galleries"
                                :key="gallery.id"
                                class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition"
                            >
                                <div class="aspect-video bg-gray-100">
                                    <img
                                        v-if="gallery.cover_image"
                                        :src="`/storage/${gallery.cover_image}`"
                                        :alt="gallery.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-medium text-gray-900">{{ gallery.name }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ gallery.photos_count }} photos</p>
                                    <div class="flex items-center gap-2 mt-2 flex-wrap">
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
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Protected
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-4">
                                        <Link
                                            :href="route('admin.galleries.show', gallery.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.galleries.edit', gallery.id)"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="confirmDelete(gallery)"
                                            class="text-red-600 hover:text-red-800 text-sm"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No galleries</h3>
                            <p class="mt-1 text-gray-500">Create galleries to showcase collections of photos.</p>
                            <div class="mt-6">
                                <Link :href="route('admin.galleries.create')">
                                    <PrimaryButton>Create Gallery</PrimaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Gallery"
            :message="`Are you sure you want to delete '${galleryToDelete?.name}'? Photos will be removed from this gallery but not deleted.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteGallery"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
