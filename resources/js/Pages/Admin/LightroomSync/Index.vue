<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const selectedFiles = ref([]);
const overwrite = ref(false);
const syncTags = ref(true);
const processing = ref(false);
const dragOver = ref(false);

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files).filter(file =>
        file.name.endsWith('.xmp') || file.name.endsWith('.XMP')
    );
    selectedFiles.value = files;
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    const files = Array.from(e.dataTransfer.files).filter(file =>
        file.name.endsWith('.xmp') || file.name.endsWith('.XMP')
    );
    selectedFiles.value = files;
};

const handleDragOver = (e) => {
    e.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const processFiles = () => {
    if (selectedFiles.value.length === 0) return;

    processing.value = true;

    const formData = new FormData();
    selectedFiles.value.forEach((file, index) => {
        formData.append(`xmp_files[${index}]`, file);
    });
    formData.append('overwrite', overwrite.value ? '1' : '0');
    formData.append('sync_tags', syncTags.value ? '1' : '0');

    router.post(route('admin.lightroom.process'), formData, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Lightroom Sync" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lightroom Sync</h2>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-blue-800">How it works</h3>
                            <p class="mt-1 text-sm text-blue-700">
                                Export XMP sidecar files from Lightroom Classic and upload them here.
                                The system will match files by filename and update photo metadata including
                                ratings, labels, keywords, and develop settings.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload XMP Files</h3>

                        <!-- Drop Zone -->
                        <div
                            @drop="handleDrop"
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                dragOver ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'
                            ]"
                        >
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="mt-4">
                                <label class="cursor-pointer">
                                    <span class="text-indigo-600 hover:text-indigo-500 font-medium">Upload XMP files</span>
                                    <span class="text-gray-500"> or drag and drop</span>
                                    <input
                                        type="file"
                                        accept=".xmp"
                                        multiple
                                        @change="handleFileSelect"
                                        class="hidden"
                                    />
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">XMP sidecar files only</p>
                        </div>

                        <!-- Selected Files -->
                        <div v-if="selectedFiles.length > 0" class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Selected Files ({{ selectedFiles.length }})</h4>
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between px-4 py-2">
                                        <span class="text-sm text-gray-700 truncate">{{ file.name }}</span>
                                        <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="mt-6 space-y-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="overwrite"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Overwrite existing metadata</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="syncTags"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Sync keywords as tags</span>
                            </label>
                        </div>

                        <!-- Submit -->
                        <div class="mt-6">
                            <PrimaryButton
                                @click="processFiles"
                                :disabled="selectedFiles.length === 0 || processing"
                            >
                                {{ processing ? 'Processing...' : 'Process Files' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
