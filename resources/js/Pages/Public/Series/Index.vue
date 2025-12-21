<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    series: Object
});
</script>

<template>
    <Head title="Photo Series" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Photo Series</h1>
                <p class="mt-2 text-gray-600">Curated collections of related photographs</p>
            </div>

            <div v-if="series.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="item in series.data" :key="item.id" :href="route('series.show', item.slug)" class="group">
                    <div class="aspect-[4/3] bg-gray-200 rounded-xl overflow-hidden">
                        <img v-if="item.cover_image" :src="`/storage/${item.cover_image}`" :alt="item.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h2 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ item.title }}</h2>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                            <span v-if="item.project_date">{{ item.project_date }}</span>
                            <span v-if="item.project_date && item.photos_count">&bull;</span>
                            <span>{{ item.photos_count }} photos</span>
                        </div>
                        <p v-if="item.description" class="text-gray-600 mt-2 line-clamp-2">{{ item.description }}</p>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No series yet</h3>
                <p class="mt-2 text-gray-500">Check back later for curated photo collections</p>
            </div>

            <!-- Pagination -->
            <div v-if="series.data.length > 0" class="mt-8">
                <Pagination :links="series.links" />
            </div>
        </div>
    </PublicLayout>
</template>
