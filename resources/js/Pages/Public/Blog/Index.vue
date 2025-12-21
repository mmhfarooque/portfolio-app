<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    posts: Object,
    categories: Array,
    tags: Array,
    currentCategory: Object,
    currentTag: Object,
    filters: Object
});

const searchQuery = ref(props.filters.search || '');

const applyFilter = (type, value) => {
    const params = {};
    if (type === 'category') params.category = value;
    if (type === 'tag') params.tag = value;
    if (searchQuery.value) params.search = searchQuery.value;
    router.get(route('blog.index'), params);
};

const search = () => {
    router.get(route('blog.index'), { search: searchQuery.value });
};

const clearFilters = () => {
    searchQuery.value = '';
    router.get(route('blog.index'));
};
</script>

<template>
    <Head title="Blog" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Blog</h1>
                <p class="mt-2 text-gray-600">Stories, insights, and behind-the-scenes looks at photography</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Search -->
                    <div class="mb-6">
                        <form @submit.prevent="search">
                            <input type="text" v-model="searchQuery" placeholder="Search posts..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </form>
                    </div>

                    <!-- Categories -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Categories</h3>
                        <ul class="space-y-2">
                            <li v-for="category in categories" :key="category.id">
                                <button @click="applyFilter('category', category.slug)" :class="['text-sm hover:text-indigo-600 transition', currentCategory?.slug === category.slug ? 'text-indigo-600 font-medium' : 'text-gray-600']">
                                    {{ category.name }} ({{ category.posts_count }})
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tags -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="tag in tags" :key="tag.id" @click="applyFilter('tag', tag.slug)" :class="['text-xs px-2 py-1 rounded-full transition', currentTag?.slug === tag.slug ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                                #{{ tag.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <div v-if="currentCategory || currentTag || filters.search">
                        <button @click="clearFilters" class="text-sm text-indigo-600 hover:text-indigo-800">
                            Clear all filters
                        </button>
                    </div>
                </div>

                <!-- Posts Grid -->
                <div class="lg:col-span-3">
                    <div v-if="posts.data.length > 0" class="space-y-8">
                        <article v-for="post in posts.data" :key="post.id" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <Link :href="route('blog.show', post.slug)" class="block">
                                <div v-if="post.featured_image" class="aspect-video bg-gray-200">
                                    <img :src="`/storage/${post.featured_image}`" :alt="post.title" class="w-full h-full object-cover" loading="lazy" />
                                </div>
                                <div class="p-6">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                                        <span v-if="post.category" class="text-indigo-600">{{ post.category.name }}</span>
                                        <span v-if="post.category">&bull;</span>
                                        <span>{{ post.published_at }}</span>
                                        <span v-if="post.reading_time">&bull; {{ post.reading_time }} min read</span>
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ post.title }}</h2>
                                    <p v-if="post.excerpt" class="text-gray-600 line-clamp-2">{{ post.excerpt }}</p>
                                </div>
                            </Link>
                        </article>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No posts found</h3>
                        <p class="mt-2 text-gray-500">Check back later for new content</p>
                    </div>

                    <!-- Pagination -->
                    <div v-if="posts.data.length > 0" class="mt-8">
                        <Pagination :links="posts.links" />
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
