<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    tests: Object,
    stats: Object
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'running': return 'bg-green-100 text-green-800';
        case 'paused': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const startTest = (id) => {
    router.post(route('admin.abtests.start', id));
};

const pauseTest = (id) => {
    router.post(route('admin.abtests.pause', id));
};
</script>

<template>
    <Head title="A/B Tests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">A/B Tests</h2>
                <Link :href="route('admin.abtests.create')">
                    <PrimaryButton>Create Test</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <p class="text-sm text-gray-600">Total Tests</p>
                        <p class="text-2xl font-semibold">{{ stats.total }}</p>
                    </div>
                    <div class="bg-green-50 rounded-xl shadow-sm border border-green-100 p-6">
                        <p class="text-sm text-green-600">Running</p>
                        <p class="text-2xl font-semibold text-green-700">{{ stats.running }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-100 p-6">
                        <p class="text-sm text-blue-600">Completed</p>
                        <p class="text-2xl font-semibold text-blue-700">{{ stats.completed }}</p>
                    </div>
                </div>

                <!-- Tests Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div v-if="tests.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="test in tests.data" :key="test.id">
                                        <td class="px-6 py-4">
                                            <Link :href="route('admin.abtests.show', test.id)" class="text-blue-600 hover:text-blue-800 font-medium">
                                                {{ test.name }}
                                            </Link>
                                            <p v-if="test.description" class="text-sm text-gray-500 truncate max-w-xs">{{ test.description }}</p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ test.type }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusBadgeClass(test.status)" class="px-2 py-1 text-xs rounded-full capitalize">
                                                {{ test.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ test.total_impressions?.toLocaleString() || 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button v-if="test.status === 'draft' || test.status === 'paused'" @click="startTest(test.id)" class="text-green-600 hover:text-green-800 mr-3">Start</button>
                                            <button v-if="test.status === 'running'" @click="pauseTest(test.id)" class="text-yellow-600 hover:text-yellow-800 mr-3">Pause</button>
                                            <Link :href="route('admin.abtests.show', test.id)" class="text-blue-600 hover:text-blue-800">View</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500">No A/B tests yet.</p>
                            <div class="mt-4">
                                <Link :href="route('admin.abtests.create')">
                                    <PrimaryButton>Create Your First Test</PrimaryButton>
                                </Link>
                            </div>
                        </div>
                        <div v-if="tests.data.length > 0" class="mt-6">
                            <Pagination :links="tests.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
