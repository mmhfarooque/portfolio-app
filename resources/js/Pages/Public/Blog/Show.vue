<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { sanitizeHtml } from '@/composables/useSanitize.js';

const props = defineProps({
    post: Object,
    relatedPosts: Array,
    previousPost: Object,
    nextPost: Object
});
</script>

<template>
    <SeoHead
        :title="post.seo_title || post.title"
        :description="post.meta_description || post.excerpt"
        :image="post.featured_image"
        :image-alt="post.title"
        type="article"
        :url="`https://mfaruk.com/blog/${post.slug}`"
        :published-time="post.published_at"
        :modified-time="post.updated_at"
        :article="post"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Blog', url: '/blog' },
            post.category ? { name: post.category.name, url: `/blog?category=${post.category.slug}` } : null,
            { name: post.title }
        ].filter(Boolean)"
    />

    <PublicLayout>
        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link href="/blog" class="text-theme-accent hover:text-theme-accent-hover text-sm mb-8 inline-block">
                &larr; Back to Blog
            </Link>

            <!-- Featured Image -->
            <div v-if="post.featured_image" class="aspect-video bg-theme-bg-tertiary rounded-xl overflow-hidden mb-8">
                <img :src="`/storage/${post.featured_image}`" :alt="post.title" class="w-full h-full object-cover" />
            </div>

            <!-- Header -->
            <header class="mb-8">
                <div class="flex items-center gap-2 text-sm text-theme-text-muted mb-4">
                    <Link v-if="post.category" :href="`/blog?category=${post.category.slug}`" class="text-theme-accent hover:text-theme-accent-hover">
                        {{ post.category.name }}
                    </Link>
                    <span v-if="post.category">&bull;</span>
                    <span>{{ post.published_at }}</span>
                    <span v-if="post.reading_time">&bull; {{ post.reading_time }} min read</span>
                </div>
                <h1 class="text-4xl font-bold text-theme-text-primary mb-4">{{ post.title }}</h1>
                <p v-if="post.excerpt" class="text-xl text-theme-text-secondary">{{ post.excerpt }}</p>
            </header>

            <!-- Content -->
            <div class="blog-content max-w-none mb-12" v-html="sanitizeHtml(post.content)"></div>

            <!-- Tags -->
            <div v-if="post.tags.length > 0" class="flex flex-wrap gap-2 mb-12">
                <Link v-for="tag in post.tags" :key="tag.slug" :href="`/blog?tag=${tag.slug}`" class="px-3 py-1 bg-theme-bg-tertiary text-theme-text-secondary text-sm rounded-full hover:bg-theme-bg-hover hover:text-theme-text-primary transition">
                    #{{ tag.name }}
                </Link>
            </div>

            <!-- Author & Views -->
            <div class="flex items-center justify-between text-sm text-theme-text-muted py-4 border-t border-theme-border">
                <span v-if="post.user">By {{ post.user.name }}</span>
                <span>{{ post.views }} views</span>
            </div>

            <!-- Navigation -->
            <nav class="flex justify-between py-6 border-t border-theme-border">
                <Link v-if="previousPost" :href="route('blog.show', previousPost.slug)" class="flex items-center text-theme-accent hover:text-theme-accent-hover">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="text-sm">{{ previousPost.title }}</span>
                </Link>
                <div v-else></div>
                <Link v-if="nextPost" :href="route('blog.show', nextPost.slug)" class="flex items-center text-theme-accent hover:text-theme-accent-hover">
                    <span class="text-sm">{{ nextPost.title }}</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </nav>
        </article>

        <!-- Related Posts -->
        <section v-if="relatedPosts.length > 0" class="bg-theme-bg-secondary py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-theme-text-primary mb-8">Related Posts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link v-for="related in relatedPosts" :key="related.id" :href="route('blog.show', related.slug)" class="bg-theme-bg-card rounded-lg shadow-sm overflow-hidden group border border-theme-border">
                        <div v-if="related.featured_image" class="aspect-video bg-theme-bg-tertiary">
                            <img :src="`/storage/${related.featured_image}`" :alt="related.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-theme-text-muted mb-1">{{ related.published_at }}</p>
                            <h3 class="font-semibold text-theme-text-primary group-hover:text-theme-accent transition">{{ related.title }}</h3>
                        </div>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.blog-content :deep(h2) {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.blog-content :deep(h3) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
}

.blog-content :deep(p) {
    margin-bottom: 1.25rem;
    line-height: 1.8;
    color: var(--text-secondary);
    font-size: 1.0625rem;
}

.blog-content :deep(a) {
    color: var(--accent);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.blog-content :deep(a:hover) {
    color: var(--accent-hover);
}

.blog-content :deep(strong) {
    color: var(--text-primary);
    font-weight: 600;
}

.blog-content :deep(code) {
    background: var(--bg-tertiary);
    color: var(--accent);
    padding: 0.15rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.blog-content :deep(pre) {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    overflow-x: auto;
    margin-bottom: 1.5rem;
}

.blog-content :deep(pre code) {
    background: none;
    padding: 0;
    color: var(--text-primary);
    font-size: 0.875rem;
    line-height: 1.7;
}

.blog-content :deep(blockquote) {
    border-left: 3px solid var(--accent);
    padding-left: 1rem;
    margin: 1.5rem 0;
    color: var(--text-secondary);
    font-style: italic;
}

.blog-content :deep(ul),
.blog-content :deep(ol) {
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
    color: var(--text-secondary);
}

.blog-content :deep(li) {
    margin-bottom: 0.5rem;
    line-height: 1.7;
}

.blog-content :deep(ul li) {
    list-style-type: disc;
}

.blog-content :deep(ol li) {
    list-style-type: decimal;
}

.blog-content :deep(hr) {
    border: none;
    border-top: 1px solid var(--border);
    margin: 2rem 0;
}

.blog-content :deep(img) {
    border-radius: 0.5rem;
    margin: 1.5rem 0;
    max-width: 100%;
}

.blog-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    border: 1px solid var(--border);
}

.blog-content :deep(th) {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-weight: 600;
    text-align: left;
    padding: 0.65rem 0.9rem;
    border-bottom: 2px solid var(--border);
}

.blog-content :deep(td) {
    color: var(--text-secondary);
    padding: 0.6rem 0.9rem;
    border-bottom: 1px solid var(--border);
    line-height: 1.6;
}

.blog-content :deep(tbody tr:last-child td) {
    border-bottom: none;
}

.blog-content :deep(tbody tr:hover) {
    background: var(--bg-tertiary);
}

.blog-content :deep(th strong),
.blog-content :deep(td strong) {
    color: var(--text-primary);
}
</style>
