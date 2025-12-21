<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    title: '',
    slug: '',
    description: '',
    story: '',
    project_date: '',
    location: '',
    status: 'draft',
    is_featured: false,
    cover_image: null,
    seo_title: '',
    meta_description: '',
});

const submit = () => {
    form.post(route('admin.series.store'));
};

const handleImageChange = (e) => {
    form.cover_image = e.target.files[0];
};
</script>

<template>
    <Head title="Create Series" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.series.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Series</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Title -->
                        <div class="md:col-span-2">
                            <InputLabel for="title" value="Title *" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>

                        <!-- Slug -->
                        <div class="md:col-span-2">
                            <InputLabel for="slug" value="URL Slug (optional)" />
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Leave blank to auto-generate"
                            />
                            <InputError :message="form.errors.slug" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Story -->
                        <div class="md:col-span-2">
                            <InputLabel for="story" value="Story" />
                            <textarea
                                id="story"
                                v-model="form.story"
                                rows="5"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Tell the story behind this series..."
                            ></textarea>
                            <InputError :message="form.errors.story" class="mt-2" />
                        </div>

                        <!-- Project Date -->
                        <div>
                            <InputLabel for="project_date" value="Project Date" />
                            <TextInput
                                id="project_date"
                                v-model="form.project_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.project_date" class="mt-2" />
                        </div>

                        <!-- Location -->
                        <div>
                            <InputLabel for="location" value="Location" />
                            <TextInput
                                id="location"
                                v-model="form.location"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.location" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image" />
                            <input
                                type="file"
                                id="cover_image"
                                @change="handleImageChange"
                                accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <InputError :message="form.errors.cover_image" class="mt-2" />
                        </div>

                        <!-- SEO Title -->
                        <div>
                            <InputLabel for="seo_title" value="SEO Title" />
                            <TextInput
                                id="seo_title"
                                v-model="form.seo_title"
                                type="text"
                                class="mt-1 block w-full"
                                maxlength="60"
                            />
                            <p class="mt-1 text-xs text-gray-500">{{ form.seo_title?.length || 0 }}/60 characters</p>
                            <InputError :message="form.errors.seo_title" class="mt-2" />
                        </div>

                        <!-- Meta Description -->
                        <div>
                            <InputLabel for="meta_description" value="Meta Description" />
                            <textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                maxlength="160"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">{{ form.meta_description?.length || 0 }}/160 characters</p>
                            <InputError :message="form.errors.meta_description" class="mt-2" />
                        </div>

                        <!-- Featured -->
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.is_featured"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Featured series</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <Link :href="route('admin.series.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Create Series
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
