<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    contact: Object
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const statusForm = useForm({
    status: props.contact.status
});

const updateStatus = () => {
    statusForm.patch(route('admin.contacts.update-status', props.contact.id), {
        preserveScroll: true
    });
};

const deleteContact = () => {
    isDeleting.value = true;
    router.delete(route('admin.contacts.destroy', props.contact.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const getStatusClass = (status) => {
    const classes = {
        new: 'bg-blue-100 text-blue-800',
        read: 'bg-gray-100 text-gray-800',
        replied: 'bg-green-100 text-green-800',
        archived: 'bg-yellow-100 text-yellow-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`Contact from ${contact.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.contacts.index')" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contact Message</h2>
                </div>
                <span :class="['px-3 py-1 text-sm rounded-full capitalize', getStatusClass(contact.status)]">
                    {{ contact.status }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Message -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ contact.subject || 'No Subject' }}
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">{{ contact.created_at }}</p>
                            <div class="prose max-w-none">
                                <p class="whitespace-pre-wrap text-gray-700">{{ contact.message }}</p>
                            </div>
                        </div>

                        <!-- Reply Button -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reply</h3>
                            <a
                                :href="`mailto:${contact.email}?subject=Re: ${contact.subject || 'Your message'}`"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Reply via Email
                            </a>
                            <p class="mt-2 text-sm text-gray-500">
                                After replying, remember to update the status to "Replied"
                            </p>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Contact Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Info</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Name</div>
                                    <div class="text-sm font-medium text-gray-900">{{ contact.name }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Email</div>
                                    <a :href="`mailto:${contact.email}`" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        {{ contact.email }}
                                    </a>
                                </div>
                                <div v-if="contact.phone">
                                    <div class="text-sm text-gray-500">Phone</div>
                                    <a :href="`tel:${contact.phone}`" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        {{ contact.phone }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
                            <div class="flex gap-2">
                                <select
                                    v-model="statusForm.status"
                                    class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="new">New</option>
                                    <option value="read">Read</option>
                                    <option value="replied">Replied</option>
                                    <option value="archived">Archived</option>
                                </select>
                                <PrimaryButton @click="updateStatus" :disabled="statusForm.processing">
                                    Update
                                </PrimaryButton>
                            </div>
                            <p v-if="contact.replied_at" class="mt-2 text-sm text-gray-500">
                                Replied: {{ contact.replied_at }}
                            </p>
                        </div>

                        <!-- Technical Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Technical Info</h3>
                            <div class="space-y-3">
                                <div v-if="contact.ip_address">
                                    <div class="text-sm text-gray-500">IP Address</div>
                                    <div class="text-xs font-mono text-gray-600">{{ contact.ip_address }}</div>
                                </div>
                                <div v-if="contact.user_agent">
                                    <div class="text-sm text-gray-500">User Agent</div>
                                    <div class="text-xs font-mono text-gray-600 break-all">{{ contact.user_agent }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
                            <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>
                            <DangerButton @click="showDeleteModal = true" class="w-full justify-center">
                                Delete Message
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Contact"
            :message="`Are you sure you want to delete this message from '${contact.name}'? This action cannot be undone.`"
            confirm-text="Delete"
            variant="danger"
            :processing="isDeleting"
            @confirm="deleteContact"
            @close="showDeleteModal = false"
        />
    </AuthenticatedLayout>
</template>
