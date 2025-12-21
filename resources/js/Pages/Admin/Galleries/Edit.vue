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
    gallery: Object
});

const form = useForm({
    name: props.gallery.name,
    description: props.gallery.description || '',
    cover_image: null,
    is_published: props.gallery.is_published,
    is_featured: props.gallery.is_featured,
    sort_order: props.gallery.sort_order || 0,
    password: '',
    remove_password: false,
    is_client_gallery: props.gallery.is_client_gallery,
    client_name: props.gallery.client_name || '',
    client_email: props.gallery.client_email || '',
    expires_at: props.gallery.expires_at || '',
    allow_downloads: props.gallery.allow_downloads,
    allow_selections: props.gallery.allow_selections,
    selection_limit: props.gallery.selection_limit || '',
    regenerate_token: false
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const previewUrl = ref(props.gallery.cover_image ? `/storage/${props.gallery.cover_image}` : null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.cover_image = file;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.galleries.update', props.gallery.id), {
        _method: 'PUT',
        forceFormData: true
    });
};

const deleteGallery = () => {
    isDeleting.value = true;
    router.delete(route('admin.galleries.destroy', props.gallery.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Edit: ${gallery.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Gallery</h2>
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
                                    <p class="mt-1 text-xs text-gray-500">Leave empty to keep current image</p>
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
                                    Password Protection
                                </span>
                            </InputLabel>
                            <div v-if="gallery.password" class="flex items-center gap-2 mt-2 mb-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Password Protected
                                </span>
                            </div>
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="text"
                                class="mt-2 block w-full"
                                :placeholder="gallery.password ? 'Enter new password to change, leave empty to keep current' : 'Leave empty for public gallery'"
                            />
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-xs text-gray-500">
                                    {{ gallery.password ? 'Leave empty to keep the current password.' : 'Set a password to restrict access.' }}
                                </p>
                                <label v-if="gallery.password" class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.remove_password"
                                        class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                    />
                                    <span class="ml-1 text-xs text-red-600">Remove password</span>
                                </label>
                            </div>
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

                            <div v-if="form.is_client_gallery" class="space-y-4 pt-4 border-t border-blue-200">
                                <!-- Share URL -->
                                <div v-if="gallery.access_token" class="p-3 bg-white rounded-lg">
                                    <InputLabel value="Share URL" />
                                    <div class="flex items-center gap-2 mt-1">
                                        <code class="flex-1 text-xs bg-gray-100 p-2 rounded overflow-x-auto">
                                            {{ `${window.location.origin}/client/${gallery.access_token}` }}
                                        </code>
                                        <button
                                            type="button"
                                            @click="navigator.clipboard.writeText(`${window.location.origin}/client/${gallery.access_token}`)"
                                            class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                    <div class="mt-2 flex items-center">
                                        <input
                                            id="regenerate_token"
                                            type="checkbox"
                                            v-model="form.regenerate_token"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label for="regenerate_token" class="ml-2 text-xs text-gray-600">Regenerate access token</label>
                                    </div>
                                </div>

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
                        <DangerButton type="button" @click="showDeleteModal = true">
                            Delete Gallery
                        </DangerButton>
                        <div class="flex items-center gap-3">
                            <Link :href="route('admin.galleries.index')">
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
            title="Delete Gallery"
            :message="`Are you sure you want to delete '${gallery.name}'? Photos will be removed from this gallery but not deleted.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteGallery"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
