<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    photos: Object,
    categories: Array,
    tags: Array,
    currentCategory: Object,
    currentTag: Object,
    photosWithLocation: Number,
    filters: Object
});

const search = ref(props.filters.search || '');

let searchTimeout = null;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('photos.index'), {
            search: value || undefined,
            category: props.filters.category || undefined,
            tag: props.filters.tag || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const clearFilters = () => {
    router.get(route('photos.index'));
};
</script>

<template>
    <Head title="Gallery" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    <span v-if="currentCategory">{{ currentCategory.name }}</span>
                    <span v-else-if="currentTag">Photos tagged "{{ currentTag.name }}"</span>
                    <span v-else-if="filters.search">Search: {{ filters.search }}</span>
                    <span v-else>Gallery</span>
                </h1>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-4">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search photos..."
                        class="w-full sm:w-64 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />

                    <button
                        v-if="currentCategory || currentTag || filters.search"
                        @click="clearFilters"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Clear filters
                    </button>
                </div>
            </div>

            <!-- Categories -->
            <div class="flex flex-wrap gap-2 mb-8">
                <Link
                    :href="route('photos.index')"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium transition',
                        !currentCategory && !currentTag ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    All
                </Link>
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="route('photos.index', { category: category.slug })"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium transition',
                        currentCategory?.id === category.id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ category.name }}
                    <span class="ml-1 text-xs opacity-60">({{ category.published_photos_count }})</span>
                </Link>
            </div>

            <!-- Photos Grid -->
            <div v-if="photos.data.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <Link
                    v-for="photo in photos.data"
                    :key="photo.id"
                    :href="route('photos.show', photo.slug)"
                    class="group"
                >
                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 relative">
                        <img
                            :src="`/storage/${photo.thumbnail_path}`"
                            :alt="photo.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="absolute bottom-0 left-0 right-0 p-3">
                                <p class="text-white text-sm font-medium truncate">{{ photo.title }}</p>
                                <p v-if="photo.category" class="text-white/70 text-xs">{{ photo.category.name }}</p>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 text-gray-500">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-lg font-medium">No photos found</p>
                <p class="text-sm mt-1">Try adjusting your filters or search terms.</p>
            </div>

            <!-- Pagination -->
            <div v-if="photos.data.length > 0" class="mt-8">
                <Pagination :links="photos.links" />
            </div>
        </div>
    </PublicLayout>
</template>
