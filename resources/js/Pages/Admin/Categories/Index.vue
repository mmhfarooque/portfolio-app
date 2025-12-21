<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    categories: Array
});

const showDeleteModal = ref(false);
const categoryToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (category) => {
    categoryToDelete.value = category;
    showDeleteModal.value = true;
};

const deleteCategory = () => {
    if (!categoryToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.categories.destroy', categoryToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            categoryToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Categories" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Categories</h2>
                <Link :href="route('admin.categories.create')">
                    <PrimaryButton>Add Category</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="categories.length > 0" class="space-y-4">
                            <div
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition"
                            >
                                <div class="flex items-center gap-4">
                                    <img
                                        v-if="category.cover_image"
                                        :src="`/storage/${category.cover_image}`"
                                        :alt="category.name"
                                        class="w-16 h-16 object-cover rounded-lg"
                                    />
                                    <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ category.name }}</h3>
                                        <p class="text-sm text-gray-500">{{ category.photos_count }} photos</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('admin.categories.edit', category.id)"
                                        class="px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="confirmDelete(category)"
                                        class="px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No categories</h3>
                            <p class="mt-1 text-gray-500">Create categories to organize your photos.</p>
                            <div class="mt-6">
                                <Link :href="route('admin.categories.create')">
                                    <PrimaryButton>Add Category</PrimaryButton>
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
            title="Delete Category"
            :message="`Are you sure you want to delete '${categoryToDelete?.name}'? Photos in this category will become uncategorized.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteCategory"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
