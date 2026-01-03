<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    photo: Object,
    categories: Array,
    galleries: Array,
    tags: Array,
    globalSettings: {
        type: Object,
        default: () => ({ max_resolution: 1920, quality: 82 })
    }
});

const form = useForm({
    title: props.photo.title,
    slug: props.photo.slug,
    description: props.photo.description || '',
    story: props.photo.story || '',
    location_name: props.photo.location_name || '',
    seo_title: props.photo.seo_title || '',
    meta_description: props.photo.meta_description || '',
    category_id: props.photo.category_id || '',
    gallery_id: props.photo.gallery_id || '',
    status: props.photo.status,
    is_featured: props.photo.is_featured,
    tags: props.photo.tag_ids || [],
});

// Modal states
const showDeleteModal = ref(false);
const showReplaceModal = ref(false);
const isDeleting = ref(false);

// Replace image functionality
const replaceFile = ref(null);
const replaceProgress = ref(0);
const isReplacing = ref(false);

const slugFromTitle = computed(() => {
    return form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
});

const autoGenerateSlug = () => {
    form.slug = slugFromTitle.value;
};

const submit = () => {
    form.put(route('admin.photos.update', props.photo.id));
};

const deletePhoto = () => {
    isDeleting.value = true;
    router.delete(route('admin.photos.destroy', props.photo.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};

const handleReplaceFile = (e) => {
    replaceFile.value = e.target.files[0];
    if (replaceFile.value) {
        showReplaceModal.value = true;
    }
};

const replaceImage = () => {
    if (!replaceFile.value) return;

    showReplaceModal.value = false;
    isReplacing.value = true;
    replaceProgress.value = 0;

    const formData = new FormData();
    formData.append('image', replaceFile.value);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', route('admin.photos.replace-image', props.photo.id));
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            replaceProgress.value = Math.round((e.loaded / e.total) * 100);
        }
    });

    xhr.onload = () => {
        isReplacing.value = false;
        replaceFile.value = null;
        if (xhr.status >= 200 && xhr.status < 400) {
            // Start polling immediately to show processing state
            startPolling();
            router.reload();
        }
    };

    xhr.onerror = () => {
        isReplacing.value = false;
        replaceFile.value = null;
    };

    xhr.send(formData);
};

const cancelReplace = () => {
    showReplaceModal.value = false;
    replaceFile.value = null;
};

const formatBytes = (bytes) => {
    if (!bytes) return '-';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Auto-polling for processing status
let pollInterval = null;
const isPolling = ref(false);

const startPolling = () => {
    if (pollInterval) return;
    isPolling.value = true;
    pollInterval = setInterval(async () => {
        try {
            const response = await fetch(route('admin.photos.show', props.photo.id), {
                headers: { 'Accept': 'application/json' }
            });
            if (response.ok) {
                const data = await response.json();
                if (data.photo && data.photo.status !== 'processing') {
                    stopPolling();
                    router.reload();
                }
            }
        } catch (e) {
            // Ignore errors, keep polling
        }
    }, 2000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    isPolling.value = false;
};

// Start polling if photo is processing
onMounted(() => {
    if (props.photo.status === 'processing') {
        startPolling();
    }
});

// Also start polling after replace
watch(() => props.photo.status, (newStatus) => {
    if (newStatus === 'processing') {
        startPolling();
    } else {
        stopPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

// Image optimization settings
const resolutionOptions = [
    { value: null, label: 'Use Global Setting' },
    { value: 800, label: 'Small (800px)' },
    { value: 1024, label: 'XGA (1024px)' },
    { value: 1280, label: 'HD (1280px)' },
    { value: 1440, label: 'HD+ (1440px)' },
    { value: 1600, label: 'UXGA (1600px)' },
    { value: 1920, label: 'Full HD (1920px)' },
    { value: 2048, label: '2K (2048px)' },
    { value: 2560, label: 'QHD (2560px)' },
    { value: 3840, label: '4K (3840px)' },
];

const optimizationForm = reactive({
    custom_max_resolution: props.photo.custom_max_resolution,
    custom_quality: props.photo.custom_quality ?? null,
});

const isReoptimizing = ref(false);
const hasOriginal = computed(() => props.photo.has_original);

const effectiveResolution = computed(() => {
    return optimizationForm.custom_max_resolution ?? props.globalSettings?.max_resolution ?? 1920;
});

const effectiveQuality = computed(() => {
    return optimizationForm.custom_quality ?? props.globalSettings?.quality ?? 82;
});

const canUpscale = computed(() => {
    if (!hasOriginal.value) return false;
    const originalMax = Math.max(props.photo.original_width || 0, props.photo.original_height || 0);
    return effectiveResolution.value < originalMax;
});

// Toast state
const toast = reactive({
    show: false,
    type: 'success',
    title: '',
    message: ''
});

const showToast = (type, title, message) => {
    toast.show = false;
    setTimeout(() => {
        toast.type = type;
        toast.title = title;
        toast.message = message;
        toast.show = true;
    }, 100);
};

const reoptimizePhoto = async () => {
    isReoptimizing.value = true;
    try {
        const response = await fetch(route('admin.photos.reoptimize-single', props.photo.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                custom_max_resolution: optimizationForm.custom_max_resolution,
                custom_quality: optimizationForm.custom_quality
            })
        });
        const data = await response.json();
        if (data.success) {
            showToast('success', 'Optimization Complete', `Photo re-optimized to ${data.width}×${data.height} (${data.file_size})`);
            router.reload();
        } else {
            showToast('error', 'Optimization Failed', data.message || 'Failed to re-optimize photo');
        }
    } catch (e) {
        showToast('error', 'Error', 'An error occurred during optimization');
    }
    isReoptimizing.value = false;
};

const resetToGlobal = () => {
    optimizationForm.custom_max_resolution = null;
    optimizationForm.custom_quality = null;
};
</script>

<template>
    <Head :title="`Edit: ${photo.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Photo</h2>
                <Link :href="route('admin.photos.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Photos
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 space-y-6">
                                <!-- Title -->
                                <div>
                                    <InputLabel for="title" value="Title" required />
                                    <TextInput
                                        id="title"
                                        v-model="form.title"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <!-- Slug -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel for="slug" value="URL Slug" required />
                                        <button
                                            type="button"
                                            @click="autoGenerateSlug"
                                            class="text-sm text-blue-600 hover:text-blue-700"
                                        >
                                            Generate from title
                                        </button>
                                    </div>
                                    <TextInput
                                        id="slug"
                                        v-model="form.slug"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.slug" />
                                </div>

                                <!-- Description -->
                                <div>
                                    <InputLabel for="description" value="Short Description" />
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="2"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    ></textarea>
                                    <InputError :message="form.errors.description" />
                                </div>

                                <!-- Story -->
                                <div>
                                    <InputLabel for="story" value="Story" />
                                    <textarea
                                        id="story"
                                        v-model="form.story"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="Tell the story behind this photo..."
                                    ></textarea>
                                    <InputError :message="form.errors.story" />
                                </div>

                                <!-- Location -->
                                <div>
                                    <InputLabel for="location_name" value="Location" />
                                    <TextInput
                                        id="location_name"
                                        v-model="form.location_name"
                                        class="mt-1 block w-full"
                                        placeholder="e.g., Paris, France"
                                    />
                                    <InputError :message="form.errors.location_name" />
                                </div>

                                <!-- SEO Section -->
                                <div class="pt-6 border-t border-gray-200">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <InputLabel for="seo_title" value="SEO Title" />
                                            <TextInput
                                                id="seo_title"
                                                v-model="form.seo_title"
                                                class="mt-1 block w-full"
                                                maxlength="70"
                                            />
                                            <p class="text-xs text-gray-500 mt-1">{{ form.seo_title?.length || 0 }}/70 characters</p>
                                        </div>

                                        <div>
                                            <InputLabel for="meta_description" value="Meta Description" />
                                            <textarea
                                                id="meta_description"
                                                v-model="form.meta_description"
                                                rows="2"
                                                maxlength="160"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            ></textarea>
                                            <p class="text-xs text-gray-500 mt-1">{{ form.meta_description?.length || 0 }}/160 characters</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                                <DangerButton type="button" @click="showDeleteModal = true">
                                    Delete Photo
                                </DangerButton>
                                <div class="flex items-center gap-3">
                                    <Link :href="route('admin.photos.index')">
                                        <SecondaryButton type="button">Cancel</SecondaryButton>
                                    </Link>
                                    <PrimaryButton :disabled="form.processing">
                                        Save Changes
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Image Preview -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Image</h3>
                            </div>
                            <div class="p-4">
                                <!-- Show image if available -->
                                <img
                                    v-if="photo.display_path"
                                    :src="`/storage/${photo.display_path}`"
                                    :alt="photo.title"
                                    class="w-full rounded-lg"
                                />
                                <!-- Show processing spinner -->
                                <div v-else-if="photo.status === 'processing' || isPolling" class="aspect-video bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg flex flex-col items-center justify-center">
                                    <svg class="animate-spin h-10 w-10 text-blue-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-600 font-medium">Processing image...</span>
                                    <span class="text-blue-400 text-sm mt-1">This may take a moment</span>
                                </div>
                                <!-- Show no image placeholder -->
                                <div v-else class="aspect-video bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                    <span>No image</span>
                                </div>

                                <!-- Replace Image -->
                                <div class="mt-4">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/avif"
                                        @change="handleReplaceFile"
                                        class="hidden"
                                        id="replace-file"
                                        ref="fileInput"
                                    />
                                    <label
                                        for="replace-file"
                                        class="flex items-center justify-center gap-2 w-full py-2.5 px-4 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 cursor-pointer transition"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Replace Image
                                    </label>

                                    <!-- Upload Progress -->
                                    <div v-if="isReplacing" class="mt-3">
                                        <div class="flex items-center justify-between text-sm mb-2">
                                            <span class="text-gray-600 font-medium">Uploading...</span>
                                            <span class="text-blue-600 font-semibold">{{ replaceProgress }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                            <div
                                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-300"
                                                :style="{ width: `${replaceProgress}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Publish Settings -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Publish</h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <InputLabel for="status" value="Status" />
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        id="is_featured"
                                        type="checkbox"
                                        v-model="form.is_featured"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Featured photo</label>
                                </div>

                                <div class="pt-4 border-t border-gray-100 text-sm text-gray-500">
                                    <p>Views: {{ photo.views?.toLocaleString() || 0 }}</p>
                                    <p>Created: {{ photo.created_at }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Category & Gallery -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Organization</h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <InputLabel for="category_id" value="Category" />
                                    <select
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">No category</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel for="gallery_id" value="Gallery" />
                                    <select
                                        id="gallery_id"
                                        v-model="form.gallery_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">No gallery</option>
                                        <option v-for="gallery in galleries" :key="gallery.id" :value="gallery.id">{{ gallery.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Tags</h3>
                            </div>
                            <div class="p-4">
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        :class="[
                                            'inline-flex items-center px-3 py-1 rounded-full text-sm cursor-pointer transition',
                                            form.tags.includes(tag.id)
                                                ? 'bg-blue-100 text-blue-800 ring-2 ring-blue-500'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="tag.id"
                                            v-model="form.tags"
                                            class="hidden"
                                        />
                                        {{ tag.name }}
                                    </label>
                                </div>
                                <p v-if="tags.length === 0" class="text-sm text-gray-500">No tags available</p>
                            </div>
                        </div>

                        <!-- File Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">File Info</h3>
                            </div>
                            <div class="p-4 text-sm text-gray-600 space-y-2">
                                <p><span class="font-medium">Filename:</span> {{ photo.original_filename || '-' }}</p>
                                <p><span class="font-medium">Size:</span> {{ formatBytes(photo.file_size) }}</p>
                                <p><span class="font-medium">Dimensions:</span> {{ photo.width || '-' }} x {{ photo.height || '-' }}</p>
                                <div v-if="photo.formatted_exif">
                                    <p v-if="photo.formatted_exif.camera"><span class="font-medium">Camera:</span> {{ photo.formatted_exif.camera }}</p>
                                    <p v-if="photo.formatted_exif.lens"><span class="font-medium">Lens:</span> {{ photo.formatted_exif.lens }}</p>
                                    <p v-if="photo.formatted_exif.aperture"><span class="font-medium">Aperture:</span> {{ photo.formatted_exif.aperture }}</p>
                                    <p v-if="photo.formatted_exif.shutter"><span class="font-medium">Shutter:</span> {{ photo.formatted_exif.shutter }}</p>
                                    <p v-if="photo.formatted_exif.iso"><span class="font-medium">ISO:</span> {{ photo.formatted_exif.iso }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Image Optimization -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-medium text-gray-900">Image Optimization</h3>
                            </div>
                            <div class="p-4 space-y-4">
                                <!-- Original Info -->
                                <div v-if="photo.original_width" class="p-3 bg-green-50 rounded-lg text-sm">
                                    <div class="flex items-center gap-2 text-green-700">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span class="font-medium">Original preserved</span>
                                    </div>
                                    <p class="text-green-600 mt-1">{{ photo.original_width }} x {{ photo.original_height }}px</p>
                                </div>
                                <div v-else class="p-3 bg-yellow-50 rounded-lg text-sm">
                                    <div class="flex items-center gap-2 text-yellow-700">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <span class="font-medium">No original file</span>
                                    </div>
                                    <p class="text-yellow-600 mt-1">Cannot increase resolution</p>
                                </div>

                                <!-- Custom Resolution -->
                                <div>
                                    <InputLabel value="Max Resolution" />
                                    <select
                                        v-model="optimizationForm.custom_max_resolution"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    >
                                        <option v-for="opt in resolutionOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}{{ opt.value === null ? ` (${globalSettings?.max_resolution || 1920}px)` : '' }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Custom Quality -->
                                <div>
                                    <div class="flex items-center justify-between">
                                        <InputLabel value="Quality" />
                                        <span class="text-sm text-gray-500">{{ effectiveQuality }}%</span>
                                    </div>
                                    <input
                                        type="range"
                                        v-model.number="optimizationForm.custom_quality"
                                        min="60"
                                        max="95"
                                        class="mt-1 w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                    />
                                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                                        <span>Smaller</span>
                                        <span>Best</span>
                                    </div>
                                </div>

                                <!-- Current Settings Indicator -->
                                <div class="text-xs text-gray-500 pt-2 border-t border-gray-100">
                                    <span v-if="optimizationForm.custom_max_resolution || optimizationForm.custom_quality" class="text-blue-600 font-medium">
                                        Using custom settings
                                    </span>
                                    <span v-else>Using global settings</span>
                                    <button
                                        v-if="optimizationForm.custom_max_resolution || optimizationForm.custom_quality"
                                        @click="resetToGlobal"
                                        class="ml-2 text-gray-400 hover:text-gray-600 underline"
                                    >
                                        Reset
                                    </button>
                                </div>

                                <!-- Re-optimize Button -->
                                <button
                                    type="button"
                                    @click="reoptimizePhoto"
                                    :disabled="isReoptimizing"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="isReoptimizing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ isReoptimizing ? 'Optimizing...' : 'Re-optimize Photo' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Photo"
            message="Are you sure you want to delete this photo? This action cannot be undone and all associated files will be permanently removed."
            confirm-text="Delete Photo"
            variant="danger"
            :processing="isDeleting"
            @confirm="deletePhoto"
            @close="showDeleteModal = false"
        />

        <!-- Replace Image Confirmation Modal -->
        <ConfirmModal
            :show="showReplaceModal"
            title="Replace Image"
            :message="`Replace the current image with '${replaceFile?.name}'? This will regenerate all image variants and watermarks.`"
            confirm-text="Replace"
            variant="warning"
            @confirm="replaceImage"
            @close="cancelReplace"
        />

        <!-- Toast Notification -->
        <Toast
            :show="toast.show"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            @close="toast.show = false"
        />
    </AuthenticatedLayout>
</template>
