<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    log: Object
});

const getTypeClass = (type) => {
    const classes = {
        error: 'bg-red-100 text-red-800',
        warning: 'bg-yellow-100 text-yellow-800',
        info: 'bg-blue-100 text-blue-800',
        activity: 'bg-green-100 text-green-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Log Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.logs.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Log Details</h2>
                <span :class="['px-3 py-1 text-sm rounded-full', getTypeClass(log.type)]">
                    {{ log.type }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Overview -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Overview</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Action</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.action }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Level</dt>
                            <dd class="text-sm font-medium text-gray-900 capitalize">{{ log.level }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">User</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.user?.name || 'System' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Time</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.created_at }} ({{ log.created_at_human }})</dd>
                        </div>
                    </dl>
                </div>

                <!-- Message -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Message</h3>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ log.message }}</p>
                </div>

                <!-- Context -->
                <div v-if="log.context" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Context</h3>
                    <pre class="bg-gray-50 p-4 rounded-lg overflow-x-auto text-sm">{{ JSON.stringify(log.context, null, 2) }}</pre>
                </div>

                <!-- Request Info -->
                <div v-if="log.url || log.ip_address" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Info</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="log.url">
                            <dt class="text-sm text-gray-500">URL</dt>
                            <dd class="text-sm font-mono text-gray-900 break-all">{{ log.url }}</dd>
                        </div>
                        <div v-if="log.method">
                            <dt class="text-sm text-gray-500">Method</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.method }}</dd>
                        </div>
                        <div v-if="log.ip_address">
                            <dt class="text-sm text-gray-500">IP Address</dt>
                            <dd class="text-sm font-mono text-gray-900">{{ log.ip_address }}</dd>
                        </div>
                        <div v-if="log.duration_ms">
                            <dt class="text-sm text-gray-500">Duration</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.duration_ms }}ms</dd>
                        </div>
                        <div v-if="log.memory_usage">
                            <dt class="text-sm text-gray-500">Memory Usage</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ log.memory_usage }}</dd>
                        </div>
                    </dl>
                    <div v-if="log.user_agent" class="mt-4">
                        <dt class="text-sm text-gray-500">User Agent</dt>
                        <dd class="text-sm font-mono text-gray-600 break-all mt-1">{{ log.user_agent }}</dd>
                    </div>
                </div>

                <!-- Exception -->
                <div v-if="log.exception_class" class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Exception</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Class</dt>
                            <dd class="text-sm font-mono text-red-600">{{ log.exception_class }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Message</dt>
                            <dd class="text-sm text-gray-900">{{ log.exception_message }}</dd>
                        </div>
                        <div v-if="log.file">
                            <dt class="text-sm text-gray-500">Location</dt>
                            <dd class="text-sm font-mono text-gray-600">{{ log.file }}:{{ log.line }}</dd>
                        </div>
                        <div v-if="log.exception_trace">
                            <dt class="text-sm text-gray-500">Stack Trace</dt>
                            <dd class="mt-2">
                                <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-xs">{{ log.exception_trace }}</pre>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
