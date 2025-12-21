<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const form = useForm({
    name: '',
    description: '',
    cover_image: null,
    is_published: false,
    is_featured: false,
    sort_order: 0,
    password: '',
    is_client_gallery: false,
    client_name: '',
    client_email: '',
    expires_at: '',
    allow_downloads: false,
    allow_selections: true,
    selection_limit: ''
});

const previewUrl = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.cover_image = file;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.galleries.store'));
};
</script>

<template>
    <Head title="Create Gallery" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Gallery</h2>
                <Link :href="route('admin.galleries.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Galleries
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Name" required />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image" />
                            <div class="mt-2 flex items-start gap-4">
                                <div v-if="previewUrl" class="flex-shrink-0">
                                    <img :src="previewUrl" class="w-32 h-24 object-cover rounded-lg" />
                                </div>
                                <div class="flex-1">
                                    <input
                                        type="file"
                                        id="cover_image"
                                        accept="image/*"
                                        @change="handleFileChange"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    />
                                </div>
                            </div>
                            <InputError :message="form.errors.cover_image" />
                        </div>

                        <!-- Publish Options -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <input
                                    id="is_published"
                                    type="checkbox"
                                    v-model="form.is_published"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="is_published" class="ml-2 text-sm text-gray-700">Published</label>
                            </div>
                            <div class="flex items-center">
                                <input
                                    id="is_featured"
                                    type="checkbox"
                                    v-model="form.is_featured"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="is_featured" class="ml-2 text-sm text-gray-700">Featured</label>
                            </div>
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <InputLabel for="sort_order" value="Sort Order" />
                            <TextInput
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                class="mt-1 block w-32"
                            />
                        </div>

                        <!-- Password Protection -->
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <InputLabel for="password">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Password Protection (Optional)
                                </span>
                            </InputLabel>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="text"
                                class="mt-2 block w-full"
                                placeholder="Leave empty for public gallery"
                            />
                            <p class="mt-1 text-xs text-gray-500">Set a password to restrict access to this gallery.</p>
                        </div>

                        <!-- Client Gallery Section -->
                        <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <div class="flex items-center mb-4">
                                <input
                                    id="is_client_gallery"
                                    type="checkbox"
                                    v-model="form.is_client_gallery"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <label for="is_client_gallery" class="ml-2 text-sm font-medium text-gray-700">Client Gallery</label>
                            </div>
                            <p class="text-xs text-gray-500 mb-4">Enable to create a private gallery with a shareable link for clients.</p>

                            <div v-if="form.is_client_gallery" class="space-y-4 pt-4 border-t border-blue-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="client_name" value="Client Name" />
                                        <TextInput
                                            id="client_name"
                                            v-model="form.client_name"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel for="client_email" value="Client Email" />
                                        <TextInput
                                            id="client_email"
                                            v-model="form.client_email"
                                            type="email"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="expires_at" value="Expiration Date" />
                                    <TextInput
                                        id="expires_at"
                                        v-model="form.expires_at"
                                        type="datetime-local"
                                        class="mt-1 block w-full"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Leave empty for no expiration.</p>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    <div class="flex items-center">
                                        <input
                                            id="allow_downloads"
                                            type="checkbox"
                                            v-model="form.allow_downloads"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label for="allow_downloads" class="ml-2 text-sm text-gray-600">Allow Downloads</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            id="allow_selections"
                                            type="checkbox"
                                            v-model="form.allow_selections"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label for="allow_selections" class="ml-2 text-sm text-gray-600">Allow Selections</label>
                                    </div>
                                    <div>
                                        <InputLabel for="selection_limit" value="Selection Limit" />
                                        <TextInput
                                            id="selection_limit"
                                            v-model="form.selection_limit"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full"
                                            placeholder="Unlimited"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <Link :href="route('admin.galleries.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Create Gallery
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
