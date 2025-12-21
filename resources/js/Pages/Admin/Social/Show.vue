<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    socialPost: Object
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const publishPost = () => {
    router.post(route('admin.social.publish', props.socialPost.id));
};

const deletePost = () => {
    isDeleting.value = true;
    router.delete(route('admin.social.destroy', props.socialPost.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
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
    <Head :title="`Social Post - ${socialPost.platform}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('admin.social.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight capitalize">{{ socialPost.platform }} Post</h2>
                    <span :class="getStatusClass(socialPost.status)" class="ml-3 px-2 py-1 text-xs rounded-full capitalize">{{ socialPost.status }}</span>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton v-if="socialPost.status === 'pending'" @click="publishPost">Publish Now</PrimaryButton>
                    <DangerButton @click="showDeleteModal = true">Delete</DangerButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Photo -->
                <div v-if="socialPost.photo" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Photo</h3>
                    <div class="flex items-center gap-4">
                        <img :src="`/storage/${socialPost.photo.display_path}`" class="w-48 h-48 object-cover rounded-lg" />
                        <div>
                            <p class="font-medium text-gray-900">{{ socialPost.photo.title }}</p>
                            <Link :href="route('admin.photos.edit', socialPost.photo.id)" class="text-sm text-indigo-600 hover:text-indigo-800">Edit Photo</Link>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Content</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500">Caption</p>
                            <p class="text-gray-900 whitespace-pre-wrap">{{ socialPost.caption || 'No caption' }}</p>
                        </div>
                        <div v-if="socialPost.hashtags?.length">
                            <p class="text-sm text-gray-500">Hashtags</p>
                            <p class="text-gray-900">{{ socialPost.hashtags.map(h => '#' + h).join(' ') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Platform</p>
                            <p class="font-medium capitalize">{{ socialPost.platform }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <p class="font-medium capitalize">{{ socialPost.status }}</p>
                        </div>
                        <div v-if="socialPost.scheduled_at">
                            <p class="text-sm text-gray-500">Scheduled For</p>
                            <p class="font-medium">{{ new Date(socialPost.scheduled_at).toLocaleString() }}</p>
                        </div>
                        <div v-if="socialPost.published_at">
                            <p class="text-sm text-gray-500">Published At</p>
                            <p class="font-medium">{{ new Date(socialPost.published_at).toLocaleString() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="socialPost.error_message" class="bg-red-50 rounded-xl border border-red-200 p-6">
                    <h3 class="text-lg font-medium text-red-900 mb-2">Error</h3>
                    <p class="text-red-700">{{ socialPost.error_message }}</p>
                </div>
            </div>
        </div>

        <ConfirmModal :show="showDeleteModal" title="Delete Social Post" message="Are you sure you want to delete this social post?" confirm-text="Delete" variant="danger" :processing="isDeleting" @confirm="deletePost" @close="showDeleteModal = false" />
    </AuthenticatedLayout>
</template>
