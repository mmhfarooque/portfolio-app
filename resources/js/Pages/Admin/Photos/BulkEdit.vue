<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    photos: Object,
    categories: Array,
    galleries: Array,
    tags: Array,
    filters: Object
});

const selectedPhotos = ref([]);
const bulkAction = ref('');
const bulkCategory = ref('');
const bulkGallery = ref('');
const bulkTags = ref([]);
const replaceTags = ref(false);

const statusFilter = ref(props.filters.status || '');
const categoryFilter = ref(props.filters.category || '');

const allSelected = computed(() => {
    return props.photos.data.length > 0 &&
           selectedPhotos.value.length === props.photos.data.length;
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedPhotos.value = [];
    } else {
        selectedPhotos.value = props.photos.data.map(p => p.id);
    }
};

const applyFilters = () => {
    router.get(route('admin.photos.bulk-edit'), {
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const executeBulkAction = () => {
    if (selectedPhotos.value.length === 0 || !bulkAction.value) return;

    if (bulkAction.value === 'publish' || bulkAction.value === 'unpublish' || bulkAction.value === 'delete') {
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
    }
};

const updateBulkField = (field) => {
    if (selectedPhotos.value.length === 0) return;

    const value = field === 'category_id' ? bulkCategory.value :
                  field === 'gallery_id' ? bulkGallery.value : '';

    router.post(route('admin.photos.bulk-update'), {
        field: field,
        photo_ids: selectedPhotos.value,
        value: value,
    }, {
        onSuccess: () => {
            bulkCategory.value = '';
            bulkGallery.value = '';
        },
    });
};

const updateBulkTags = () => {
    if (selectedPhotos.value.length === 0 || bulkTags.value.length === 0) return;

    router.post(route('admin.photos.bulk-tags'), {
        photo_ids: selectedPhotos.value,
        tag_ids: bulkTags.value,
        replace: replaceTags.value,
    }, {
        onSuccess: () => {
            bulkTags.value = [];
            replaceTags.value = false;
        },
    });
};

const quickSave = async (photo, field, value) => {
    try {
        await fetch(route('admin.photos.quick-update', photo.id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ [field]: value }),
        });
    } catch (error) {
        console.error('Failed to save:', error);
    }
};
</script>

<template>
    <Head title="Bulk Edit Photos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.photos.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Edit Photos</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Filters & Bulk Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Filters -->
                        <div class="flex gap-4 flex-1">
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                            <select
                                v-model="categoryFilter"
                                @change="applyFilters"
                                class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>

                        <!-- Bulk Actions -->
                        <div v-if="selectedPhotos.length > 0" class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">{{ selectedPhotos.length }} selected</span>
                            <select v-model="bulkAction" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Bulk Action</option>
                                <option value="publish">Publish</option>
                                <option value="unpublish">Unpublish</option>
                                <option value="delete">Delete</option>
                            </select>
                            <PrimaryButton @click="executeBulkAction" :disabled="!bulkAction">Apply</PrimaryButton>
                        </div>
                    </div>

                    <!-- Bulk Update Fields -->
                    <div v-if="selectedPhotos.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Bulk Category -->
                            <div class="flex items-center gap-2">
                                <select v-model="bulkCategory" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    <option value="">Set Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <SecondaryButton size="sm" @click="updateBulkField('category_id')" :disabled="!bulkCategory">
                                    Apply
                                </SecondaryButton>
                            </div>

                            <!-- Bulk Gallery -->
                            <div class="flex items-center gap-2">
                                <select v-model="bulkGallery" class="flex-1 rounded-lg border-gray-300 text-sm">
                                    <option value="">Set Gallery</option>
                                    <option v-for="gal in galleries" :key="gal.id" :value="gal.id">{{ gal.name }}</option>
                                </select>
                                <SecondaryButton size="sm" @click="updateBulkField('gallery_id')" :disabled="!bulkGallery">
                                    Apply
                                </SecondaryButton>
                            </div>

                            <!-- Bulk Tags -->
                            <div class="flex items-center gap-2">
                                <select v-model="bulkTags" multiple class="flex-1 rounded-lg border-gray-300 text-sm h-20">
                                    <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
                                </select>
                                <div class="flex flex-col gap-1">
                                    <label class="flex items-center text-xs">
                                        <input type="checkbox" v-model="replaceTags" class="rounded border-gray-300 text-indigo-600 mr-1" />
                                        Replace
                                    </label>
                                    <SecondaryButton size="sm" @click="updateBulkTags" :disabled="bulkTags.length === 0">
                                        Apply
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photos Grid -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">Select All</span>
                        </label>
                    </div>

                    <div v-if="photos.data.length > 0" class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                            <div v-for="photo in photos.data" :key="photo.id" class="relative group">
                                <!-- Selection Checkbox -->
                                <div class="absolute top-2 left-2 z-10">
                                    <input
                                        type="checkbox"
                                        v-model="selectedPhotos"
                                        :value="photo.id"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- Image -->
                                <img
                                    :src="`/storage/${photo.thumbnail_path}`"
                                    :alt="photo.title"
                                    class="w-full aspect-square object-cover rounded-lg"
                                />

                                <!-- Status Badge -->
                                <div class="absolute top-2 right-2">
                                    <span :class="[
                                        'px-2 py-0.5 text-xs rounded-full',
                                        photo.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    ]">
                                        {{ photo.status }}
                                    </span>
                                </div>

                                <!-- Quick Info -->
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        :value="photo.title"
                                        @blur="(e) => quickSave(photo, 'title', e.target.value)"
                                        class="w-full text-sm border-gray-200 rounded focus:border-indigo-500 focus:ring-indigo-500 truncate"
                                    />
                                    <div class="flex items-center gap-2 mt-1">
                                        <select
                                            :value="photo.category_id || ''"
                                            @change="(e) => quickSave(photo, 'category_id', e.target.value || null)"
                                            class="flex-1 text-xs border-gray-200 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">No Category</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <Pagination :links="photos.links" />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No photos found</h3>
                        <p class="mt-1 text-gray-500">Try adjusting your filters.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
