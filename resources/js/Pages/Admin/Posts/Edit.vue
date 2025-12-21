<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    post: Object,
    categories: Array,
    tags: Array
});

const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content || '',
    status: props.post.status,
    published_at: props.post.published_at ? props.post.published_at.slice(0, 16) : '',
    category_id: props.post.category_id || '',
    tags: props.post.tags?.map(t => t.id) || [],
    featured_image: null,
    seo_title: props.post.seo_title || '',
    meta_description: props.post.meta_description || '',
    remove_image: false
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const previewUrl = ref(props.post.featured_image ? `/storage/${props.post.featured_image}` : null);

const submit = () => {
    form.post(route('admin.posts.update', props.post.id), {
        _method: 'PUT',
        forceFormData: true
    });
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.featured_image = file;
    form.remove_image = false;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.remove_image = true;
    form.featured_image = null;
    previewUrl.value = null;
};

const deletePost = () => {
    isDeleting.value = true;
    router.delete(route('admin.posts.destroy', props.post.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Edit: ${post.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Blog Post</h2>
                <Link :href="route('admin.posts.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Posts
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                                        required
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <div>
                                    <InputLabel for="slug" value="URL Slug" />
                                    <TextInput
                                        id="slug"
                                        v-model="form.slug"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.slug" />
                                </div>

                                <div>
                                    <InputLabel for="excerpt" value="Excerpt" />
                                    <textarea
                                        id="excerpt"
                                        v-model="form.excerpt"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    ></textarea>
                                    <InputError :message="form.errors.excerpt" />
                                </div>

                                <div>
                                    <InputLabel for="content" value="Content" required />
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="20"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                        required
                                    ></textarea>
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
                                    <InputLabel value="Featured Image" />
                                    <div v-if="previewUrl" class="mt-2 mb-3">
                                        <img :src="previewUrl" class="w-full h-32 object-cover rounded-lg" />
                                        <button type="button" @click="removeImage" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                            Remove image
                                        </button>
                                    </div>
                                    <input
                                        type="file"
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
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="meta_description" value="Meta Description" class="text-xs" />
                                        <textarea
                                            id="meta_description"
                                            v-model="form.meta_description"
                                            rows="3"
                                            class="mt-1 block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <DangerButton type="button" @click="showDeleteModal = true">
                            Delete Post
                        </DangerButton>
                        <div class="flex gap-3">
                            <Link :href="route('admin.posts.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Save Changes
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Post"
            :message="`Are you sure you want to delete '${post.title}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deletePost"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
