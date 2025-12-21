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
    category: Object
});

const form = useForm({
    name: props.category.name,
    description: props.category.description || '',
    cover_image: null,
    sort_order: props.category.sort_order || 0
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const previewUrl = ref(props.category.cover_image ? `/storage/${props.category.cover_image}` : null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.cover_image = file;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.categories.update', props.category.id), {
        _method: 'PUT',
        forceFormData: true
    });
};

const deleteCategory = () => {
    isDeleting.value = true;
    router.delete(route('admin.categories.destroy', props.category.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`Edit: ${category.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Category</h2>
                <Link :href="route('admin.categories.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Categories
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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
                                    <img :src="previewUrl" class="w-32 h-32 object-cover rounded-lg" />
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

                        <!-- Sort Order -->
                        <div>
                            <InputLabel for="sort_order" value="Sort Order" />
                            <TextInput
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                class="mt-1 block w-32"
                            />
                            <InputError :message="form.errors.sort_order" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <DangerButton type="button" @click="showDeleteModal = true">
                            Delete Category
                        </DangerButton>
                        <div class="flex items-center gap-3">
                            <Link :href="route('admin.categories.index')">
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
            title="Delete Category"
            :message="`Are you sure you want to delete '${category.name}'? Photos in this category will become uncategorized.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteCategory"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
