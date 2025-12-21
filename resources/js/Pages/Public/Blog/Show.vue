<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    post: Object,
    relatedPosts: Array,
    previousPost: Object,
    nextPost: Object
});
</script>

<template>
    <Head :title="post.seo_title || post.title" />

    <PublicLayout>
        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link href="/blog" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to Blog
            </Link>

            <!-- Featured Image -->
            <div v-if="post.featured_image" class="aspect-video bg-gray-200 rounded-xl overflow-hidden mb-8">
                <img :src="`/storage/${post.featured_image}`" :alt="post.title" class="w-full h-full object-cover" />
            </div>

            <!-- Header -->
            <header class="mb-8">
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <Link v-if="post.category" :href="`/blog?category=${post.category.slug}`" class="text-indigo-600 hover:text-indigo-800">
                        {{ post.category.name }}
                    </Link>
                    <span v-if="post.category">&bull;</span>
                    <span>{{ post.published_at }}</span>
                    <span v-if="post.reading_time">&bull; {{ post.reading_time }} min read</span>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ post.title }}</h1>
                <p v-if="post.excerpt" class="text-xl text-gray-600">{{ post.excerpt }}</p>
            </header>

            <!-- Content -->
            <div class="prose prose-lg max-w-none mb-12" v-html="post.content"></div>

            <!-- Tags -->
            <div v-if="post.tags.length > 0" class="flex flex-wrap gap-2 mb-12">
                <Link v-for="tag in post.tags" :key="tag.slug" :href="`/blog?tag=${tag.slug}`" class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full hover:bg-gray-200 transition">
                    #{{ tag.name }}
                </Link>
            </div>

            <!-- Author & Views -->
            <div class="flex items-center justify-between text-sm text-gray-500 py-4 border-t border-gray-100">
                <span v-if="post.user">By {{ post.user.name }}</span>
                <span>{{ post.views }} views</span>
            </div>

            <!-- Navigation -->
            <nav class="flex justify-between py-6 border-t border-gray-100">
                <Link v-if="previousPost" :href="route('blog.show', previousPost.slug)" class="flex items-center text-indigo-600 hover:text-indigo-800">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="text-sm">{{ previousPost.title }}</span>
                </Link>
                <div v-else></div>
                <Link v-if="nextPost" :href="route('blog.show', nextPost.slug)" class="flex items-center text-indigo-600 hover:text-indigo-800">
                    <span class="text-sm">{{ nextPost.title }}</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </nav>
        </article>

        <!-- Related Posts -->
        <section v-if="relatedPosts.length > 0" class="bg-gray-50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Posts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="related in relatedPosts" :key="related.id" :href="route('blog.show', related.slug)" class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <div v-if="related.featured_image" class="aspect-video bg-gray-200">
                            <img :src="`/storage/${related.featured_image}`" :alt="related.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-500 mb-1">{{ related.published_at }}</p>
                            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ related.title }}</h3>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
