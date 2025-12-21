<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    photo: Object
});

const formatFileSize = (bytes) => {
    if (!bytes) return 'Unknown';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
};

const getStatusClass = (status) => {
    const classes = {
        published: 'bg-green-100 text-green-800',
        draft: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        failed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`Photo - ${photo.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.photos.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Photo Details</h2>
                <span :class="['px-3 py-1 text-sm rounded-full', getStatusClass(photo.status)]">
                    {{ photo.status }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Main Image -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="relative">
                        <img
                            :src="`/storage/${photo.display_path || photo.thumbnail_path}`"
                            :alt="photo.title"
                            class="w-full max-h-[600px] object-contain bg-gray-100"
                        />
                        <div v-if="photo.is_featured" class="absolute top-4 left-4">
                            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Featured
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-2xl font-bold text-gray-900">{{ photo.title }}</h3>
                                <Link :href="route('admin.photos.edit', photo.id)">
                                    <PrimaryButton>Edit Photo</PrimaryButton>
                                </Link>
                            </div>

                            <p v-if="photo.description" class="text-gray-600 mb-4">{{ photo.description }}</p>

                            <div v-if="photo.story" class="prose max-w-none text-gray-700">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Story</h4>
                                <p class="whitespace-pre-wrap">{{ photo.story }}</p>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500">
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ photo.views?.toLocaleString() || 0 }} views
                                </div>
                                <div v-if="photo.location_name" class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ photo.location_name }}
                                </div>
                            </div>
                        </div>

                        <!-- EXIF Data -->
                        <div v-if="photo.formatted_exif" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Camera Settings</h3>
                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-if="photo.formatted_exif.camera">
                                    <dt class="text-sm text-gray-500">Camera</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.camera }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.lens">
                                    <dt class="text-sm text-gray-500">Lens</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.lens }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.focal_length">
                                    <dt class="text-sm text-gray-500">Focal Length</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.focal_length }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.aperture">
                                    <dt class="text-sm text-gray-500">Aperture</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.aperture }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.shutter_speed">
                                    <dt class="text-sm text-gray-500">Shutter Speed</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.shutter_speed }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.iso">
                                    <dt class="text-sm text-gray-500">ISO</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.iso }}</dd>
                                </div>
                                <div v-if="photo.formatted_exif.date_taken">
                                    <dt class="text-sm text-gray-500">Date Taken</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.formatted_exif.date_taken }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- File Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">File Info</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500">Filename</dt>
                                    <dd class="text-sm font-medium text-gray-900 break-all">{{ photo.original_filename }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Dimensions</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.width }} x {{ photo.height }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">File Size</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatFileSize(photo.file_size) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Slug</dt>
                                    <dd class="text-sm font-mono text-gray-900">{{ photo.slug }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Organization -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Organization</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500">Category</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.category?.name || 'None' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Gallery</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.gallery?.name || 'None' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Tags</dt>
                                    <dd class="mt-1">
                                        <div v-if="photo.tags && photo.tags.length > 0" class="flex flex-wrap gap-1">
                                            <span v-for="tag in photo.tags" :key="tag.id" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs">
                                                {{ tag.name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-gray-400">No tags</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Dates -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dates</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500">Created</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.created_at }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500">Last Updated</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ photo.updated_at }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                            <div class="space-y-2">
                                <Link :href="route('admin.photos.edit', photo.id)" class="block">
                                    <SecondaryButton class="w-full justify-center">Edit Photo</SecondaryButton>
                                </Link>
                                <a :href="`/storage/${photo.display_path}`" target="_blank" class="block">
                                    <SecondaryButton class="w-full justify-center">View Full Size</SecondaryButton>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
