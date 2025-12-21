<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    results: Object
});

const getStatusClass = (status) => {
    const classes = {
        success: 'bg-green-100 text-green-800',
        skipped: 'bg-yellow-100 text-yellow-800',
        error: 'bg-red-100 text-red-800',
        not_found: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Sync Results" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.lightroom.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sync Results</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Total Files</div>
                        <div class="text-2xl font-bold text-gray-900">{{ results.total || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Successful</div>
                        <div class="text-2xl font-bold text-green-600">{{ results.success || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Skipped</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ results.skipped || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Errors</div>
                        <div class="text-2xl font-bold text-red-600">{{ results.errors || 0 }}</div>
                    </div>
                </div>

                <!-- Detailed Results -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">File Details</h3>
                    </div>
                    <div class="p-6">
                        <div v-if="results.files && results.files.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(file, index) in results.files" :key="index">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ file.filename }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(file.status)]">
                                                {{ file.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <Link
                                                v-if="file.photo_id"
                                                :href="route('admin.photos.edit', file.photo_id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                {{ file.photo_title || 'View Photo' }}
                                            </Link>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ file.message || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No files were processed.
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center">
                    <Link :href="route('admin.lightroom.index')">
                        <PrimaryButton>Upload More Files</PrimaryButton>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
