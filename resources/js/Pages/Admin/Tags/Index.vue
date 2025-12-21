<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    tags: Array
});

const newTagForm = useForm({
    name: ''
});

const editingTag = ref(null);
const editForm = useForm({
    name: ''
});

const showDeleteModal = ref(false);
const tagToDelete = ref(null);
const isDeleting = ref(false);

const createTag = () => {
    newTagForm.post(route('admin.tags.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newTagForm.reset();
        }
    });
};

const startEditing = (tag) => {
    editingTag.value = tag.id;
    editForm.name = tag.name;
};

const cancelEditing = () => {
    editingTag.value = null;
    editForm.reset();
};

const updateTag = (tag) => {
    editForm.put(route('admin.tags.update', tag.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingTag.value = null;
        }
    });
};

const confirmDelete = (tag) => {
    tagToDelete.value = tag;
    showDeleteModal.value = true;
};

const deleteTag = () => {
    if (!tagToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.tags.destroy', tagToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            tagToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Tags" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tags</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Add New Tag -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Add New Tag</h3>
                    </div>
                    <div class="p-4">
                        <form @submit.prevent="createTag" class="flex gap-4">
                            <div class="flex-1">
                                <TextInput
                                    v-model="newTagForm.name"
                                    placeholder="Tag name"
                                    class="w-full"
                                    required
                                />
                                <InputError :message="newTagForm.errors.name" class="mt-1" />
                            </div>
                            <PrimaryButton :disabled="newTagForm.processing">
                                Add Tag
                            </PrimaryButton>
                        </form>
                    </div>
                </div>

                <!-- Existing Tags -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">Existing Tags</h3>
                    </div>
                    <div class="p-4">
                        <div v-if="tags.length > 0" class="flex flex-wrap gap-3">
                            <div
                                v-for="tag in tags"
                                :key="tag.id"
                                class="inline-flex items-center bg-gray-100 rounded-full"
                            >
                                <!-- View Mode -->
                                <template v-if="editingTag !== tag.id">
                                    <span class="px-4 py-2 text-sm text-gray-700">{{ tag.name }}</span>
                                    <span class="text-xs text-gray-500 mr-2">({{ tag.photos_count }})</span>
                                    <button
                                        @click="startEditing(tag)"
                                        class="text-gray-400 hover:text-gray-600 pr-2 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(tag)"
                                        class="text-gray-400 hover:text-red-600 pr-3 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </template>

                                <!-- Edit Mode -->
                                <template v-else>
                                    <form @submit.prevent="updateTag(tag)" class="flex items-center">
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            required
                                            class="text-sm rounded-l-full border-gray-300 py-2 px-4 focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="editForm.processing"
                                            class="bg-blue-600 text-white px-3 py-2 text-sm hover:bg-blue-700 disabled:opacity-50"
                                        >
                                            Save
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEditing"
                                            class="bg-gray-300 text-gray-700 px-3 py-2 text-sm rounded-r-full hover:bg-gray-400"
                                        >
                                            Cancel
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>
                        <p v-else class="text-gray-500">No tags yet. Create your first tag above.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Tag"
            :message="`Are you sure you want to delete '${tagToDelete?.name}'? This will remove the tag from all photos.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteTag"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
