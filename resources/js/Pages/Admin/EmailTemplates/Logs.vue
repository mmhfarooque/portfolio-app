<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
let searchTimeout = null;

const viewingLog = ref(null);

const applyFilters = () => {
    router.get(route('admin.email-templates.logs'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, () => {
    applyFilters();
});

const viewLog = (log) => {
    viewingLog.value = log;
};

const getStatusClass = (logStatus) => {
    return logStatus === 'sent'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head title="Email Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.email-templates.index')"
                        class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Email Logs</h2>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                    <!-- Filters -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by email, name, or subject..."
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>
                            <select v-model="status"
                                class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">All Status</option>
                                <option value="sent">Sent</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent By</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ log.sent_at || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ log.recipient_email }}</div>
                                        <div v-if="log.recipient_name" class="text-xs text-gray-500">{{ log.recipient_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                        {{ log.subject }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span v-if="log.template_slug" class="px-2 py-0.5 text-xs font-mono bg-gray-100 rounded">{{ log.template_slug }}</span>
                                        <span v-else class="text-xs text-gray-400 italic">Custom</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusClass(log.status)]">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ log.sender_name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button @click="viewLog(log)"
                                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            View
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!logs.data?.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No email logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links?.length > 3" class="px-6 py-4 border-t border-gray-100">
                        <Pagination :links="logs.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- View Log Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="viewingLog" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="viewingLog = null"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all">
                            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Email Details</h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-gray-500">To: {{ viewingLog.recipient_email }}</span>
                                        <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', getStatusClass(viewingLog.status)]">
                                            {{ viewingLog.status }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="viewingLog = null" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Meta Info -->
                            <div class="px-4 py-3 bg-gray-50 text-xs text-gray-600 grid grid-cols-2 gap-2">
                                <div><strong>Subject:</strong> {{ viewingLog.subject }}</div>
                                <div><strong>Sent:</strong> {{ viewingLog.sent_at }}</div>
                                <div><strong>Recipient:</strong> {{ viewingLog.recipient_name || viewingLog.recipient_email }}</div>
                                <div><strong>Sent By:</strong> {{ viewingLog.sender_name || 'System' }}</div>
                                <div v-if="viewingLog.template_slug"><strong>Template:</strong> {{ viewingLog.template_slug }}</div>
                                <div v-if="viewingLog.error_message" class="col-span-2 text-red-600"><strong>Error:</strong> {{ viewingLog.error_message }}</div>
                            </div>

                            <!-- Email Content -->
                            <div class="p-4 max-h-[60vh] overflow-y-auto">
                                <iframe
                                    :srcdoc="viewingLog.body_html"
                                    class="w-full border border-gray-200 rounded-lg"
                                    style="min-height: 400px;"
                                    sandbox="allow-same-origin"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
