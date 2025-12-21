<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    posts: Object,
    categories: Array
});

const showDeleteModal = ref(false);
const postToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (post) => {
    postToDelete.value = post;
    showDeleteModal.value = true;
};

const deletePost = () => {
    if (!postToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.posts.destroy', postToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            postToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Blog Posts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blog Posts</h2>
                <Link :href="route('admin.posts.create')">
                    <PrimaryButton>New Post</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="posts.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="post in posts.data" :key="post.id">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <img
                                                    v-if="post.featured_image"
                                                    :src="`/storage/${post.featured_image}`"
                                                    :alt="post.title"
                                                    class="w-12 h-12 object-cover rounded mr-4"
                                                />
                                                <div v-else class="w-12 h-12 bg-gray-200 rounded mr-4 flex items-center justify-center text-gray-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ post.title.substring(0, 40) }}{{ post.title.length > 40 ? '...' : '' }}</div>
                                                    <div class="text-sm text-gray-500">{{ post.reading_time }} min read</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="post.category" class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">{{ post.category.name }}</span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 py-1 text-xs rounded-full',
                                                    post.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                ]"
                                            >
                                                {{ post.status === 'published' ? 'Published' : 'Draft' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ post.published_at ? new Date(post.published_at).toLocaleDateString() : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a
                                                v-if="post.status === 'published'"
                                                :href="route('blog.show', post.slug)"
                                                target="_blank"
                                                class="text-gray-600 hover:text-gray-800 mr-3"
                                            >
                                                View
                                            </a>
                                            <Link :href="route('admin.posts.edit', post.id)" class="text-blue-600 hover:text-blue-800 mr-3">
                                                Edit
                                            </Link>
                                            <button @click="confirmDelete(post)" class="text-red-600 hover:text-red-800">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No blog posts</h3>
                            <p class="mt-1 text-gray-500">Get started by creating a new post.</p>
                            <div class="mt-6">
                                <Link :href="route('admin.posts.create')">
                                    <PrimaryButton>Create Post</PrimaryButton>
                                </Link>
                            </div>
                        </div>

                        <div v-if="posts.data.length > 0" class="mt-6">
                            <Pagination :links="posts.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Post"
            :message="`Are you sure you want to delete '${postToDelete?.title}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deletePost"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
