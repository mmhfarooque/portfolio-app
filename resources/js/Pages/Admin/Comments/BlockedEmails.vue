<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    blockedEmails: Object
});

const unblockEmail = (email) => {
    if (confirm(`Are you sure you want to unblock "${email}"?`)) {
        router.post(route('admin.comments.unblock-email'), { email }, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Blocked Emails" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <Link
                            :href="route('admin.comments.index')"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Blocked Emails</h1>
                    </div>
                    <p class="text-sm text-gray-600">
                        Email addresses blocked from commenting on your photos
                    </p>
                </div>
            </div>

            <!-- Blocked Emails List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Table Header -->
                <div class="hidden sm:grid sm:grid-cols-12 gap-4 px-6 py-4 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <div class="col-span-4">Email</div>
                    <div class="col-span-3">Reason</div>
                    <div class="col-span-2">Blocked By</div>
                    <div class="col-span-2">Date</div>
                    <div class="col-span-1 text-right">Action</div>
                </div>

                <!-- Email Rows -->
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="blocked in blockedEmails.data"
                        :key="blocked.id"
                        class="p-4 sm:px-6 sm:py-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="sm:grid sm:grid-cols-12 gap-4 items-center">
                            <!-- Email -->
                            <div class="sm:col-span-4 mb-2 sm:mb-0">
                                <span class="font-medium text-gray-900">{{ blocked.email }}</span>
                            </div>

                            <!-- Reason -->
                            <div class="sm:col-span-3 mb-2 sm:mb-0">
                                <span class="text-sm text-gray-600">{{ blocked.reason || 'No reason provided' }}</span>
                            </div>

                            <!-- Blocked By -->
                            <div class="sm:col-span-2 mb-2 sm:mb-0">
                                <span class="text-sm text-gray-500">{{ blocked.blocked_by }}</span>
                            </div>

                            <!-- Date -->
                            <div class="sm:col-span-2 mb-2 sm:mb-0">
                                <span class="text-sm text-gray-500">{{ blocked.created_at }}</span>
                            </div>

                            <!-- Action -->
                            <div class="sm:col-span-1 flex justify-end">
                                <button
                                    @click="unblockEmail(blocked.email)"
                                    class="px-3 py-1.5 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
                                >
                                    Unblock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="blockedEmails.data.length === 0" class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500">No blocked emails.</p>
                    <p class="text-sm text-gray-400 mt-1">Blocked emails will appear here.</p>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination v-if="blockedEmails.data.length > 0" :links="blockedEmails.links" class="mt-6" />
        </div>
    </AuthenticatedLayout>
</template>
