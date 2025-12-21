<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    categories: Array,
    tags: Array
});

const form = useForm({
    title: '',
    excerpt: '',
    content: '',
    status: 'draft',
    published_at: new Date().toISOString().slice(0, 16),
    category_id: '',
    tags: [],
    featured_image: null,
    seo_title: '',
    meta_description: ''
});

const submit = (status) => {
    form.status = status;
    form.post(route('admin.posts.store'));
};

const handleFileChange = (e) => {
    form.featured_image = e.target.files[0];
};
</script>

<template>
    <Head title="Create Blog Post" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Blog Post</h2>
                <Link :href="route('admin.posts.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Posts
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit('draft')" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Main Content -->
                            <div class="lg:col-span-2 space-y-6">
                                <div>
                                    <InputLabel for="title" value="Title" required />
                                    <TextInput
                                        id="title"
                                        v-model="form.title"
                                        class="mt-1 block w-full"
                                        placeholder="Enter post title..."
                                        required
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <div>
                                    <InputLabel for="excerpt" value="Excerpt" />
                                    <textarea
                                        id="excerpt"
                                        v-model="form.excerpt"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="Brief summary of the post (optional)..."
                                    ></textarea>
                                    <p class="mt-1 text-sm text-gray-500">A short summary shown in blog listings.</p>
                                    <InputError :message="form.errors.excerpt" />
                                </div>

                                <div>
                                    <InputLabel for="content" value="Content" required />
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="20"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                        placeholder="Write your post content here..."
                                        required
                                    ></textarea>
                                    <p class="mt-1 text-sm text-gray-500">You can use Markdown for formatting.</p>
                                    <InputError :message="form.errors.content" />
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="space-y-6">
                                <!-- Status -->
                                <div class="bg-gray-50 p-4 rounded-lg">
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

                                <!-- Published Date -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <InputLabel for="published_at" value="Publish Date" />
                                    <TextInput
                                        id="published_at"
                                        v-model="form.published_at"
                                        type="datetime-local"
                                        class="mt-1 block w-full"
                                    />
                                </div>

                                <!-- Category -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <InputLabel for="category_id" value="Category" />
                                    <select
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="">No category</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Tags -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <InputLabel value="Tags" />
                                    <div class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                                        <label v-for="tag in tags" :key="tag.id" class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :value="tag.id"
                                                v-model="form.tags"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-600">{{ tag.name }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Featured Image -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <InputLabel for="featured_image" value="Featured Image" />
                                    <input
                                        type="file"
                                        id="featured_image"
                                        accept="image/*"
                                        @change="handleFileChange"
                                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                    <InputError :message="form.errors.featured_image" />
                                </div>

                                <!-- SEO -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">SEO Settings</h3>

                                    <div class="mb-3">
                                        <InputLabel for="seo_title" value="SEO Title" class="text-xs" />
                                        <TextInput
                                            id="seo_title"
                                            v-model="form.seo_title"
                                            class="mt-1 block w-full text-sm"
                                            placeholder="Leave empty to use post title"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="meta_description" value="Meta Description" class="text-xs" />
                                        <textarea
                                            id="meta_description"
                                            v-model="form.meta_description"
                                            rows="3"
                                            class="mt-1 block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Brief description for search engines..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <Link :href="route('admin.posts.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <div class="flex gap-3">
                            <SecondaryButton type="button" @click="submit('draft')" :disabled="form.processing">
                                Save Draft
                            </SecondaryButton>
                            <PrimaryButton type="button" @click="submit('published')" :disabled="form.processing">
                                Publish
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
