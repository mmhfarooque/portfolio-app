<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    categories: Array,
    galleries: Array,
    tags: Array
});

const files = ref([]);
const categoryId = ref('');
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadResults = ref(null);

const totalSize = computed(() => {
    const bytes = files.value.reduce((sum, f) => sum + f.size, 0);
    return formatBytes(bytes);
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const handleDrop = (e) => {
    isDragging.value = false;
    const droppedFiles = Array.from(e.dataTransfer.files).filter(f =>
        ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic', 'image/heif'].includes(f.type)
    );
    addFiles(droppedFiles);
};

const handleFileSelect = (e) => {
    const selectedFiles = Array.from(e.target.files);
    addFiles(selectedFiles);
    e.target.value = '';
};

const addFiles = (newFiles) => {
    newFiles.forEach(file => {
        file.preview = URL.createObjectURL(file);
        file.id = Math.random().toString(36).substr(2, 9);
        files.value.push(file);
    });
};

const removeFile = (id) => {
    const index = files.value.findIndex(f => f.id === id);
    if (index > -1) {
        URL.revokeObjectURL(files.value[index].preview);
        files.value.splice(index, 1);
    }
};

const clearFiles = () => {
    files.value.forEach(f => URL.revokeObjectURL(f.preview));
    files.value = [];
};

const uploadPhotos = () => {
    if (files.value.length === 0) return;

    isUploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    files.value.forEach(file => {
        formData.append('photos[]', file);
    });
    if (categoryId.value) {
        formData.append('category_id', categoryId.value);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', route('admin.photos.store'));

    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            uploadProgress.value = Math.round((e.loaded / e.total) * 100);
        }
    });

    xhr.onload = () => {
        isUploading.value = false;
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            uploadResults.value = response;
            clearFiles();

            // Redirect after a short delay
            setTimeout(() => {
                router.visit(route('admin.photos.index'));
            }, 2000);
        } else {
            alert('Upload failed. Please try again.');
        }
    };

    xhr.onerror = () => {
        isUploading.value = false;
        alert('Upload failed. Please try again.');
    };

    xhr.send(formData);
};
</script>

<template>
    <Head title="Upload Photos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Upload Photos</h2>
                <Link :href="route('admin.photos.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Photos
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="uploadResults" class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-green-800">{{ uploadResults.message }}</p>
                            <p class="text-sm text-green-600 mt-1">Redirecting to photos...</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Category Selection -->
                    <div class="p-6 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category (optional)</label>
                        <select
                            v-model="categoryId"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">No category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <!-- Upload Area -->
                    <div class="p-6">
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-xl p-8 text-center transition-colors',
                                isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                            ]"
                        >
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/gif,image/webp,image/heic,image/heif"
                                multiple
                                @change="handleFileSelect"
                                class="hidden"
                                id="file-input"
                            />

                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>

                            <p class="text-gray-600 mb-2">
                                <label for="file-input" class="text-blue-600 hover:text-blue-700 cursor-pointer font-medium">
                                    Click to upload
                                </label>
                                or drag and drop
                            </p>
                            <p class="text-sm text-gray-500">JPG, PNG, GIF, WebP, HEIC (max 50MB each)</p>
                        </div>

                        <!-- File Preview Grid -->
                        <div v-if="files.length > 0" class="mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-gray-600">
                                    {{ files.length }} file(s) selected ({{ totalSize }})
                                </span>
                                <button @click="clearFiles" class="text-sm text-red-600 hover:text-red-700">
                                    Clear all
                                </button>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                <div v-for="file in files" :key="file.id" class="relative group">
                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover" />
                                    </div>
                                    <button
                                        @click="removeFile(file.id)"
                                        class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <p class="text-xs text-gray-600 mt-1 truncate">{{ file.name }}</p>
                                    <p class="text-xs text-gray-400">{{ formatBytes(file.size) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Progress -->
                        <div v-if="isUploading" class="mt-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Uploading...</span>
                                <span class="text-sm text-gray-500">{{ uploadProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${uploadProgress}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <div v-if="files.length > 0 && !isUploading" class="mt-6">
                            <button
                                @click="uploadPhotos"
                                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Upload {{ files.length }} Photo(s)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
