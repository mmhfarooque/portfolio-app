<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    logs: Object,
    stats: Object,
    types: Array,
    levels: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const level = ref(props.filters.level || '');

const applyFilters = () => {
    router.get(route('admin.logs.index'), {
        search: search.value || undefined,
        type: type.value || undefined,
        level: level.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([type, level], applyFilters);

const getTypeClass = (logType) => {
    const classes = {
        error: 'bg-red-100 text-red-800',
        warning: 'bg-yellow-100 text-yellow-800',
        info: 'bg-blue-100 text-blue-800',
        activity: 'bg-green-100 text-green-800',
    };
    return classes[logType] || 'bg-gray-100 text-gray-800';
};

const getLevelClass = (logLevel) => {
    const classes = {
        critical: 'bg-red-100 text-red-800',
        error: 'bg-red-100 text-red-800',
        warning: 'bg-yellow-100 text-yellow-800',
        info: 'bg-blue-100 text-blue-800',
        debug: 'bg-gray-100 text-gray-800',
    };
    return classes[logLevel] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Activity Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Activity Logs</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Total Logs</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Errors</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.errors }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Warnings</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.warnings }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Today</div>
                        <div class="text-2xl font-bold text-indigo-600">{{ stats.today }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Filters -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search logs..."
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="flex gap-4">
                                <select
                                    v-model="type"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Types</option>
                                    <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                                </select>
                                <select
                                    v-model="level"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Levels</option>
                                    <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div v-if="logs.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in logs.data" :key="log.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full', getTypeClass(log.type)]">
                                                {{ log.type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ log.action }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-md truncate">{{ log.message }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ log.user?.name || 'System' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>{{ log.created_at_human }}</div>
                                            <div class="text-xs text-gray-400">{{ log.created_at }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('admin.logs.show', log.id)" class="text-indigo-600 hover:text-indigo-900">
                                                Details
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No logs found</h3>
                            <p class="mt-1 text-gray-500">Activity logs will appear here.</p>
                        </div>

                        <div v-if="logs.data.length > 0" class="mt-6">
                            <Pagination :links="logs.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
