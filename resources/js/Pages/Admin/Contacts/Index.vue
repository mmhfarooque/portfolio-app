<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    contacts: Object,
    unreadCount: Number,
    filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const showDeleteModal = ref(false);
const contactToDelete = ref(null);
const isDeleting = ref(false);

const applyFilters = () => {
    router.get(route('admin.contacts.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
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

watch(status, applyFilters);

const confirmDelete = (contact) => {
    contactToDelete.value = contact;
    showDeleteModal.value = true;
};

const deleteContact = () => {
    if (!contactToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.contacts.destroy', contactToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            contactToDelete.value = null;
        }
    });
};

const getStatusClass = (contactStatus) => {
    const classes = {
        new: 'bg-blue-100 text-blue-800',
        read: 'bg-gray-100 text-gray-800',
        replied: 'bg-green-100 text-green-800',
        archived: 'bg-yellow-100 text-yellow-800',
    };
    return classes[contactStatus] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Contacts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contacts</h2>
                    <span v-if="unreadCount > 0" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                        {{ unreadCount }} unread
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Filters -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search by name, email, or subject..."
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="status"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="new">New</option>
                                    <option value="read">Read</option>
                                    <option value="replied">Replied</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div v-if="contacts.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="contact in contacts.data" :key="contact.id" :class="{ 'bg-blue-50': contact.status === 'new' }">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ contact.name }}</div>
                                            <div class="text-sm text-gray-500">{{ contact.email }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate">{{ contact.subject || 'No subject' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full capitalize', getStatusClass(contact.status)]">
                                                {{ contact.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ contact.created_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('admin.contacts.show', contact.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                View
                                            </Link>
                                            <button @click="confirmDelete(contact)" class="text-red-600 hover:text-red-800">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No contacts</h3>
                            <p class="mt-1 text-gray-500">Contacts from the contact form will appear here.</p>
                        </div>

                        <div v-if="contacts.data.length > 0" class="mt-6">
                            <Pagination :links="contacts.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Contact"
            :message="`Are you sure you want to delete this message from '${contactToDelete?.name}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteContact"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
