<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    photos: Object,
    filters: Object,
    filterOptions: Object
});

const searchQuery = ref(props.filters.q || '');
const selectedCategory = ref(props.filters.category || '');
const selectedTag = ref(props.filters.tag || '');
const selectedCamera = ref(props.filters.camera || '');
const selectedYear = ref(props.filters.year || '');
const selectedOrientation = ref(props.filters.orientation || '');
const sortBy = ref(props.filters.sort || 'date');
const sortOrder = ref(props.filters.order || 'desc');
const showFilters = ref(false);

const applyFilters = () => {
    const params = {};
    if (searchQuery.value) params.q = searchQuery.value;
    if (selectedCategory.value) params.category = selectedCategory.value;
    if (selectedTag.value) params.tag = selectedTag.value;
    if (selectedCamera.value) params.camera = selectedCamera.value;
    if (selectedYear.value) params.year = selectedYear.value;
    if (selectedOrientation.value) params.orientation = selectedOrientation.value;
    if (sortBy.value !== 'date') params.sort = sortBy.value;
    if (sortOrder.value !== 'desc') params.order = sortOrder.value;

    router.get(route('search'), params, { preserveState: true });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    selectedTag.value = '';
    selectedCamera.value = '';
    selectedYear.value = '';
    selectedOrientation.value = '';
    sortBy.value = 'date';
    sortOrder.value = 'desc';
    router.get(route('search'));
};

const hasActiveFilters = () => {
    return selectedCategory.value || selectedTag.value || selectedCamera.value ||
           selectedYear.value || selectedOrientation.value;
};
</script>

<template>
    <Head title="Search Photos" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Search Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Search Photos</h1>

                <!-- Search Form -->
                <form @submit.prevent="applyFilters" class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" v-model="searchQuery" placeholder="Search photos by title, description, location..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Search
                    </button>
                    <button type="button" @click="showFilters = !showFilters" class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </button>
                </form>

                <!-- Filters Panel -->
                <div v-show="showFilters" class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select v-model="selectedCategory" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="">All Categories</option>
                                <option v-for="cat in filterOptions?.categories" :key="cat.slug" :value="cat.slug">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Tag -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tag</label>
                            <select v-model="selectedTag" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="">All Tags</option>
                                <option v-for="tag in filterOptions?.tags" :key="tag.slug" :value="tag.slug">
                                    {{ tag.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Camera -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Camera</label>
                            <select v-model="selectedCamera" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="">All Cameras</option>
                                <option v-for="camera in filterOptions?.cameras" :key="camera" :value="camera">
                                    {{ camera }}
                                </option>
                            </select>
                        </div>

                        <!-- Year -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <select v-model="selectedYear" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="">All Years</option>
                                <option v-for="year in filterOptions?.years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <!-- Orientation -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Orientation</label>
                            <select v-model="selectedOrientation" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="">Any</option>
                                <option value="landscape">Landscape</option>
                                <option value="portrait">Portrait</option>
                                <option value="square">Square</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select v-model="sortBy" @change="applyFilters" class="w-full border-gray-300 rounded-md text-sm">
                                <option value="date">Date</option>
                                <option value="title">Title</option>
                                <option value="views">Views</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="hasActiveFilters()" class="mt-4 pt-4 border-t border-gray-200">
                        <button @click="clearFilters" class="text-sm text-indigo-600 hover:text-indigo-800">
                            Clear all filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Summary -->
            <div v-if="filters.q || hasActiveFilters()" class="mb-6 text-sm text-gray-600">
                <span v-if="photos?.total">{{ photos.total }} photos found</span>
                <span v-else>No photos found</span>
                <span v-if="filters.q"> for "{{ filters.q }}"</span>
            </div>

            <!-- Photo Grid -->
            <div v-if="photos?.data?.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <Link v-for="photo in photos.data" :key="photo.id" :href="route('photos.show', photo.slug)" class="group">
                    <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                        <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    </div>
                    <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                    <p v-if="photo.category" class="text-xs text-gray-500">{{ photo.category.name }}</p>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No photos found</h3>
                <p class="mt-2 text-gray-500">Try adjusting your search or filters</p>
            </div>

            <!-- Pagination -->
            <div v-if="photos?.data?.length > 0 && photos.links" class="mt-8">
                <Pagination :links="photos.links" />
            </div>
        </div>
    </PublicLayout>
</template>
