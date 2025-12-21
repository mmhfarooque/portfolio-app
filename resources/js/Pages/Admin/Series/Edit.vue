<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    series: Object,
    availablePhotos: Array
});

const form = useForm({
    title: props.series.title,
    slug: props.series.slug,
    description: props.series.description || '',
    story: props.series.story || '',
    project_date: props.series.project_date || '',
    location: props.series.location || '',
    status: props.series.status,
    is_featured: props.series.is_featured,
    cover_image: null,
    seo_title: props.series.seo_title || '',
    meta_description: props.series.meta_description || '',
});

const selectedPhotos = ref([]);

const submit = () => {
    form.post(route('admin.series.update', props.series.id), {
        forceFormData: true,
        _method: 'PUT',
    });
};

const handleImageChange = (e) => {
    form.cover_image = e.target.files[0];
};

const addPhotos = () => {
    if (selectedPhotos.value.length === 0) return;

    router.post(route('admin.series.add-photos', props.series.id), {
        photo_ids: selectedPhotos.value,
    }, {
        onSuccess: () => {
            selectedPhotos.value = [];
        }
    });
};

const removePhoto = (photoId) => {
    router.delete(route('admin.series.remove-photo', [props.series.id, photoId]));
};
</script>

<template>
    <Head :title="`Edit Series - ${series.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.series.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Series</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Series Details Form -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Series Details</h3>

                    <!-- Current Cover Image -->
                    <div v-if="series.cover_image" class="mb-6">
                        <InputLabel value="Current Cover Image" />
                        <img
                            :src="`/storage/${series.cover_image}`"
                            :alt="series.title"
                            class="mt-2 w-48 h-32 object-cover rounded-lg"
                        />
                    </div>

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
                            <InputLabel for="slug" value="URL Slug" />
                            <TextInput
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full"
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
                            <InputLabel for="cover_image" value="New Cover Image" />
                            <input
                                type="file"
                                id="cover_image"
                                @change="handleImageChange"
                                accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <InputError :message="form.errors.cover_image" class="mt-2" />
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
                            Update Series
                        </PrimaryButton>
                    </div>
                </form>

                <!-- Current Photos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Photos in Series ({{ series.photos.length }})</h3>

                    <div v-if="series.photos.length > 0" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="photo in series.photos" :key="photo.id" class="relative group">
                            <img
                                :src="`/storage/${photo.thumbnail_path}`"
                                :alt="photo.title"
                                class="w-full aspect-square object-cover rounded-lg"
                            />
                            <button
                                @click="removePhoto(photo.id)"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <p class="mt-1 text-xs text-gray-600 truncate">{{ photo.title }}</p>
                        </div>
                    </div>
                    <p v-else class="text-gray-500">No photos in this series yet.</p>
                </div>

                <!-- Add Photos -->
                <div v-if="availablePhotos.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Photos</h3>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-4">
                        <label
                            v-for="photo in availablePhotos"
                            :key="photo.id"
                            class="relative cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                v-model="selectedPhotos"
                                :value="photo.id"
                                class="sr-only"
                            />
                            <img
                                :src="`/storage/${photo.thumbnail_path}`"
                                :alt="photo.title"
                                :class="[
                                    'w-full aspect-square object-cover rounded-lg border-2 transition',
                                    selectedPhotos.includes(photo.id) ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent'
                                ]"
                            />
                            <div
                                v-if="selectedPhotos.includes(photo.id)"
                                class="absolute top-2 right-2 bg-indigo-500 text-white rounded-full w-5 h-5 flex items-center justify-center"
                            >
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </label>
                    </div>

                    <PrimaryButton @click="addPhotos" :disabled="selectedPhotos.length === 0">
                        Add {{ selectedPhotos.length }} Photo(s)
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
