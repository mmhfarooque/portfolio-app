<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    photos: Object,
    categories: Array,
    galleries: Array,
    tags: Array,
    filters: Object
});

// Check if any photos are processing
const hasProcessingPhotos = computed(() => {
    return props.photos.data.some(p => p.status === 'processing');
});

// Auto-polling for processing photos
let pollInterval = null;
const isPolling = ref(false);

const startPolling = () => {
    if (pollInterval) return;
    isPolling.value = true;
    console.log('[Photos] Starting auto-refresh polling...');
    pollInterval = setInterval(() => {
        console.log('[Photos] Polling for updates...');
        router.reload({ preserveScroll: true });
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    isPolling.value = false;
    console.log('[Photos] Stopped polling');
};

// Watch for processing photos and start/stop polling
watch(hasProcessingPhotos, (hasProcessing) => {
    if (hasProcessing) {
        startPolling();
    } else {
        stopPolling();
    }
}, { immediate: true });

onMounted(() => {
    if (hasProcessingPhotos.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

const selectedPhotos = ref([]);
const bulkAction = ref('');
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const category = ref(props.filters.category || '');

// Debounced search
let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([status, category], () => {
    applyFilters();
});

const applyFilters = () => {
    router.get(route('admin.photos.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        category: category.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedPhotos.value = props.photos.data.map(p => p.id);
    } else {
        selectedPhotos.value = [];
    }
};

const toggleSelectPhoto = (id) => {
    const index = selectedPhotos.value.indexOf(id);
    if (index > -1) {
        selectedPhotos.value.splice(index, 1);
    } else {
        selectedPhotos.value.push(id);
    }
};

const performBulkAction = () => {
    if (!bulkAction.value || selectedPhotos.value.length === 0) return;

    if (bulkAction.value === 'delete' && !confirm(`Are you sure you want to delete ${selectedPhotos.value.length} photo(s)?`)) {
        return;
    }

    router.post(route('admin.photos.bulk-action'), {
        action: bulkAction.value,
        photo_ids: selectedPhotos.value,
    }, {
        onSuccess: () => {
            selectedPhotos.value = [];
            bulkAction.value = '';
        },
    });
};

const deletePhoto = (id) => {
    if (confirm('Are you sure you want to delete this photo?')) {
        router.delete(route('admin.photos.destroy', id));
    }
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'published': return 'bg-green-100 text-green-800';
        case 'draft': return 'bg-gray-100 text-gray-800';
        case 'processing': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Photos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Photos</h2>
                <Link :href="route('admin.photos.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Photos
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search photos..."
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Status Filter -->
                        <select
                            v-model="status"
                            class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Status</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="processing">Processing</option>
                            <option value="failed">Failed</option>
                        </select>

                        <!-- Category Filter -->
                        <select
                            v-model="category"
                            class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div v-if="selectedPhotos.length > 0" class="bg-blue-50 rounded-xl p-4 mb-6 flex items-center justify-between">
                    <span class="text-blue-800 font-medium">{{ selectedPhotos.length }} photo(s) selected</span>
                    <div class="flex items-center gap-2">
                        <select v-model="bulkAction" class="border-gray-300 rounded-lg shadow-sm">
                            <option value="">Select Action</option>
                            <option value="publish">Publish</option>
                            <option value="unpublish">Unpublish</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button
                            @click="performBulkAction"
                            :disabled="!bulkAction"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition disabled:opacity-50"
                        >
                            Apply
                        </button>
                    </div>
                </div>

                <!-- Photos Grid -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="selectedPhotos.length === photos.data.length && photos.data.length > 0"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="ml-2 text-sm text-gray-600">Select All</span>
                        </div>
                    </div>

                    <div v-if="photos.data.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 p-4">
                        <div
                            v-for="photo in photos.data"
                            :key="photo.id"
                            class="group relative"
                        >
                            <!-- Selection Checkbox -->
                            <div class="absolute top-2 left-2 z-10">
                                <input
                                    type="checkbox"
                                    :checked="selectedPhotos.includes(photo.id)"
                                    @change="toggleSelectPhoto(photo.id)"
                                    class="rounded border-white/50 bg-white/80 text-blue-600 focus:ring-blue-500"
                                />
                            </div>

                            <!-- Featured Badge -->
                            <div v-if="photo.is_featured" class="absolute top-2 right-2 z-10">
                                <span class="bg-yellow-400 text-yellow-900 text-xs font-medium px-2 py-0.5 rounded">Featured</span>
                            </div>

                            <!-- Photo -->
                            <Link :href="route('admin.photos.edit', photo.id)" class="block">
                                <div class="aspect-square rounded-lg overflow-hidden relative" :style="{ backgroundColor: photo.dominant_color || '#f3f4f6' }">
                                    <!-- Show thumbnail if available -->
                                    <img
                                        v-if="photo.thumbnail_path"
                                        :src="`/storage/${photo.thumbnail_path}`"
                                        :alt="photo.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <!-- Show processing spinner -->
                                    <div v-else-if="photo.status === 'processing'" class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
                                        <svg class="animate-spin h-8 w-8 text-blue-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-xs text-blue-600 font-medium">Processing...</span>
                                    </div>
                                    <!-- Show placeholder for other cases -->
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>

                                    <!-- Overlay with actions (only when not processing) -->
                                    <div v-if="photo.status !== 'processing'" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <Link
                                            :href="route('admin.photos.edit', photo.id)"
                                            class="p-2 bg-white rounded-full hover:bg-gray-100"
                                        >
                                            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            @click.prevent="deletePhoto(photo.id)"
                                            class="p-2 bg-white rounded-full hover:bg-red-50"
                                        >
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </Link>

                            <!-- Photo Info -->
                            <div class="mt-2">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ photo.title }}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <span :class="['text-xs px-2 py-0.5 rounded-full', getStatusBadgeClass(photo.status)]">
                                        {{ photo.status }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ photo.views }} views</span>
                                </div>
                                <p v-if="photo.category" class="text-xs text-gray-500 mt-1">{{ photo.category.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-12 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-lg font-medium">No photos found</p>
                        <p class="text-sm mt-1">Upload your first photo to get started.</p>
                        <Link :href="route('admin.photos.create')" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Upload Photos
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="photos.data.length > 0" class="p-4 border-t border-gray-100">
                        <Pagination :links="photos.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
