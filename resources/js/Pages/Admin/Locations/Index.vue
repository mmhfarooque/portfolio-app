<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    locations: Object
});

const showDeleteModal = ref(false);
const locationToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (location) => {
    locationToDelete.value = location;
    showDeleteModal.value = true;
};

const deleteLocation = () => {
    if (!locationToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.locations.destroy', locationToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            locationToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Locations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Locations</h2>
                <Link :href="route('admin.locations.create')">
                    <PrimaryButton>Add Location</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="locations.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City/Country</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photos</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="location in locations.data" :key="location.id">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <img
                                                    v-if="location.cover_image"
                                                    :src="`/storage/${location.cover_image}`"
                                                    :alt="location.name"
                                                    class="w-12 h-12 object-cover rounded mr-4"
                                                />
                                                <div v-else class="w-12 h-12 bg-gray-200 rounded mr-4 flex items-center justify-center text-gray-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">{{ location.name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ location.city || '-' }}</div>
                                            <div class="text-sm text-gray-500">{{ location.country || '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ location.latitude?.toFixed(4) }}, {{ location.longitude?.toFixed(4) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ location.nearby_photos_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 py-1 text-xs rounded-full',
                                                    location.is_featured ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                                ]"
                                            >
                                                {{ location.is_featured ? 'Featured' : 'Normal' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a
                                                :href="route('locations.show', location.slug)"
                                                target="_blank"
                                                class="text-gray-600 hover:text-gray-800 mr-3"
                                            >
                                                View
                                            </a>
                                            <Link :href="route('admin.locations.edit', location.id)" class="text-blue-600 hover:text-blue-800 mr-3">
                                                Edit
                                            </Link>
                                            <button @click="confirmDelete(location)" class="text-red-600 hover:text-red-800">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No locations</h3>
                            <p class="mt-1 text-gray-500">Get started by adding a new location.</p>
                            <div class="mt-6">
                                <Link :href="route('admin.locations.create')">
                                    <PrimaryButton>Add Location</PrimaryButton>
                                </Link>
                            </div>
                        </div>

                        <div v-if="locations.data.length > 0" class="mt-6">
                            <Pagination :links="locations.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Location"
            :message="`Are you sure you want to delete '${locationToDelete?.name}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteLocation"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
