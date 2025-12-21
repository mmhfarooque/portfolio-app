<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    platforms: Object,
    recentPosts: Array,
    stats: Object
});

const showDeleteModal = ref(false);
const postToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (post) => {
    postToDelete.value = post;
    showDeleteModal.value = true;
};

const deletePost = () => {
    isDeleting.value = true;
    router.delete(route('admin.social.destroy', postToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            postToDelete.value = null;
        }
    });
};

const publishPost = (id) => {
    router.post(route('admin.social.publish', id));
};

const getStatusClass = (status) => {
    switch (status) {
        case 'published': return 'bg-green-100 text-green-800';
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'failed': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Social Media" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Social Media</h2>
                <Link :href="route('admin.social.create')">
                    <PrimaryButton>Create Post</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Stats -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <p class="text-sm text-gray-600">Total Posts</p>
                        <p class="text-2xl font-semibold">{{ stats.total_posts }}</p>
                    </div>
                    <div class="bg-green-50 rounded-xl shadow-sm border border-green-100 p-6">
                        <p class="text-sm text-green-600">Published</p>
                        <p class="text-2xl font-semibold text-green-700">{{ stats.published }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-100 p-6">
                        <p class="text-sm text-blue-600">Scheduled</p>
                        <p class="text-2xl font-semibold text-blue-700">{{ stats.scheduled }}</p>
                    </div>
                    <div class="bg-red-50 rounded-xl shadow-sm border border-red-100 p-6">
                        <p class="text-sm text-red-600">Failed</p>
                        <p class="text-2xl font-semibold text-red-700">{{ stats.failed }}</p>
                    </div>
                </div>

                <!-- Connected Platforms -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Connected Platforms</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div v-for="(platform, key) in platforms" :key="key" class="flex items-center gap-3 p-4 border rounded-lg" :class="platform.connected ? 'border-green-200 bg-green-50' : 'border-gray-200'">
                            <span class="font-medium">{{ platform.name }}</span>
                            <span v-if="platform.connected" class="ml-auto text-green-600 text-sm">Connected</span>
                            <span v-else class="ml-auto text-gray-400 text-sm">Not connected</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <Link :href="route('admin.social.accounts')" class="text-sm text-indigo-600 hover:text-indigo-800">Manage Accounts</Link>
                    </div>
                </div>

                <!-- Recent Posts -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Posts</h3>
                        <div v-if="recentPosts.length > 0" class="space-y-4">
                            <div v-for="post in recentPosts" :key="post.id" class="flex items-center gap-4 p-4 border rounded-lg">
                                <img v-if="post.photo?.thumbnail_path" :src="`/storage/${post.photo.thumbnail_path}`" class="w-16 h-16 object-cover rounded" />
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ post.photo?.title || 'Unknown Photo' }}</p>
                                    <p class="text-sm text-gray-500 capitalize">{{ post.platform }}</p>
                                </div>
                                <span :class="getStatusClass(post.status)" class="px-2 py-1 text-xs rounded-full capitalize">{{ post.status }}</span>
                                <div class="flex gap-2">
                                    <button v-if="post.status === 'pending'" @click="publishPost(post.id)" class="text-sm text-green-600 hover:text-green-800">Publish</button>
                                    <Link :href="route('admin.social.show', post.id)" class="text-sm text-blue-600 hover:text-blue-800">View</Link>
                                    <button @click="confirmDelete(post)" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 text-center py-8">No social posts yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal :show="showDeleteModal" title="Delete Social Post" message="Are you sure you want to delete this social post?" confirm-text="Delete" variant="danger" :processing="isDeleting" @confirm="deletePost" @close="showDeleteModal = false" />
    </AuthenticatedLayout>
</template>
