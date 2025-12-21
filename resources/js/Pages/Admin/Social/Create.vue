<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    platforms: Object,
    photos: Array,
    selectedPhoto: Object
});

const scheduleType = ref('now');

const form = useForm({
    photo_id: props.selectedPhoto?.id || '',
    platforms: [],
    caption: '',
    hashtags: '',
    schedule_at: ''
});

const submit = () => {
    if (scheduleType.value === 'now') {
        form.schedule_at = '';
    }
    form.post(route('admin.social.store'));
};
</script>

<template>
    <Head title="Create Social Post" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.social.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Social Post</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Select Photo -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Select Photo</h3>

                        <div v-if="selectedPhoto" class="flex items-center gap-4">
                            <img :src="selectedPhoto.getImageUrl?.('medium') || `/storage/${selectedPhoto.display_path}`" class="w-32 h-32 rounded-lg object-cover">
                            <div>
                                <div class="font-medium text-gray-900">{{ selectedPhoto.title }}</div>
                                <Link :href="route('admin.social.create')" class="text-sm text-indigo-600 hover:text-indigo-800">Change photo</Link>
                            </div>
                        </div>
                        <div v-else class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                            <label v-for="photo in photos" :key="photo.id" class="relative cursor-pointer">
                                <input type="radio" v-model="form.photo_id" :value="photo.id" class="sr-only peer">
                                <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full aspect-square object-cover rounded-lg border-2 border-transparent peer-checked:border-indigo-600 hover:opacity-80 transition">
                            </label>
                        </div>
                        <InputError :message="form.errors.photo_id" />
                    </div>

                    <!-- Select Platforms -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Select Platforms</h3>
                        <div class="space-y-3">
                            <label v-for="(platform, key) in platforms" :key="key" class="flex items-center gap-3 p-3 border rounded-lg" :class="platform.connected ? 'cursor-pointer hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'">
                                <input type="checkbox" v-model="form.platforms" :value="key" :disabled="!platform.connected" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <span class="flex-1">
                                    <span class="font-medium text-gray-900">{{ platform.name }}</span>
                                    <span v-if="!platform.connected" class="text-sm text-gray-500 ml-2">(Not connected)</span>
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.platforms" />
                    </div>

                    <!-- Caption -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Caption</h3>
                        <textarea v-model="form.caption" rows="4" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Write your caption... (leave empty to auto-generate)"></textarea>
                        <p class="mt-1 text-sm text-gray-500">Max 2200 characters for Instagram compatibility</p>
                    </div>

                    <!-- Hashtags -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Hashtags</h3>
                        <TextInput v-model="form.hashtags" class="w-full" placeholder="photography, landscape, nature (comma-separated)" />
                        <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate from photo tags</p>
                    </div>

                    <!-- Schedule -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule</h3>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="radio" v-model="scheduleType" value="now" class="text-indigo-600 focus:ring-indigo-500">
                                <span>Post immediately</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" v-model="scheduleType" value="scheduled" class="text-indigo-600 focus:ring-indigo-500">
                                <span>Schedule for later</span>
                            </label>
                        </div>
                        <div v-if="scheduleType === 'scheduled'" class="mt-4">
                            <TextInput type="datetime-local" v-model="form.schedule_at" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link :href="route('admin.social.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">Create Post</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
