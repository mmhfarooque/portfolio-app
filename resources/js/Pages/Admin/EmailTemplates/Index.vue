<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    templates: Object,
});

const categoryLabels = {
    order: 'Order',
    delivery: 'Delivery',
    subscription: 'Subscription',
    system: 'System',
    general: 'General',
};

const categoryColors = {
    order: 'bg-blue-100 text-blue-800',
    delivery: 'bg-green-100 text-green-800',
    subscription: 'bg-purple-100 text-purple-800',
    system: 'bg-gray-100 text-gray-800',
    general: 'bg-amber-100 text-amber-800',
};

const toggling = ref(null);

const toggleActive = (template) => {
    toggling.value = template.id;
    router.post(route('admin.email-templates.toggle-active', template.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            toggling.value = null;
        },
    });
};

const sendTest = (template) => {
    router.post(route('admin.email-templates.send-test', template.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Email Templates" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Email Templates</h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.email-templates.compose')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Compose Email
                    </Link>
                    <Link :href="route('admin.email-templates.logs')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        View Logs
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <template v-for="(group, category) in templates" :key="category">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex items-center gap-2">
                                <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', categoryColors[category] || categoryColors.general]">
                                    {{ categoryLabels[category] || category }}
                                </span>
                                <span class="text-sm text-gray-500">{{ group.length }} template{{ group.length !== 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-for="template in group" :key="template.id"
                                class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ template.name }}</h3>
                                        <span v-if="!template.is_active"
                                            class="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">
                                            Inactive
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-0.5 truncate">{{ template.subject }}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ template.description }}
                                        <span v-if="template.editor_name"> &middot; Last edited by {{ template.editor_name }}</span>
                                        &middot; {{ template.updated_at }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <button
                                        @click="toggleActive(template)"
                                        :disabled="toggling === template.id"
                                        :class="[
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                            template.is_active ? 'bg-indigo-600' : 'bg-gray-200'
                                        ]"
                                        :title="template.is_active ? 'Deactivate' : 'Activate'"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                template.is_active ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        />
                                    </button>
                                    <button @click="sendTest(template)"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                                        title="Send Test Email">
                                        Test
                                    </button>
                                    <Link :href="route('admin.email-templates.edit', template.id)"
                                        class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        Edit
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="Object.keys(templates).length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-medium text-gray-900">No email templates</h3>
                    <p class="mt-1 text-sm text-gray-500">Run the email template seeder to create default templates.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
