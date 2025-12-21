<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    equipment: Object
});

const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.equipment.destroy', itemToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            itemToDelete.value = null;
        }
    });
};

const getTypeLabel = (type) => {
    const types = {
        camera: 'Camera',
        lens: 'Lens',
        accessory: 'Accessory',
        lighting: 'Lighting',
        software: 'Software',
    };
    return types[type] || type;
};

const getTypeClass = (type) => {
    const classes = {
        camera: 'bg-blue-100 text-blue-800',
        lens: 'bg-purple-100 text-purple-800',
        accessory: 'bg-green-100 text-green-800',
        lighting: 'bg-yellow-100 text-yellow-800',
        software: 'bg-gray-100 text-gray-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Equipment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Equipment</h2>
                <Link :href="route('admin.equipment.create')">
                    <PrimaryButton>Add Equipment</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="equipment.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand / Model</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in equipment.data" :key="item.id">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <img
                                                    v-if="item.image_path"
                                                    :src="`/storage/${item.image_path}`"
                                                    :alt="item.name"
                                                    class="w-12 h-12 object-cover rounded mr-4"
                                                />
                                                <div v-else class="w-12 h-12 bg-gray-200 rounded mr-4 flex items-center justify-center text-gray-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full', getTypeClass(item.type)]">
                                                {{ getTypeLabel(item.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ item.brand || '-' }}</div>
                                            <div class="text-sm text-gray-500">{{ item.model || '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 py-1 text-xs rounded-full',
                                                    item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                                ]"
                                            >
                                                {{ item.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a
                                                :href="route('gear.show', item.slug)"
                                                target="_blank"
                                                class="text-gray-600 hover:text-gray-800 mr-3"
                                            >
                                                View
                                            </a>
                                            <Link :href="route('admin.equipment.edit', item.id)" class="text-blue-600 hover:text-blue-800 mr-3">
                                                Edit
                                            </Link>
                                            <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-800">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No equipment</h3>
                            <p class="mt-1 text-gray-500">Get started by adding your first piece of equipment.</p>
                            <div class="mt-6">
                                <Link :href="route('admin.equipment.create')">
                                    <PrimaryButton>Add Equipment</PrimaryButton>
                                </Link>
                            </div>
                        </div>

                        <div v-if="equipment.data.length > 0" class="mt-6">
                            <Pagination :links="equipment.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Equipment"
            :message="`Are you sure you want to delete '${itemToDelete?.name}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteItem"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
