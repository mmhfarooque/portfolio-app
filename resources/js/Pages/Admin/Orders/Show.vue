<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    order: Object
});

const showShipForm = ref(false);
const showNoteForm = ref(false);

const statusForm = useForm({
    status: props.order.status
});

const shipForm = useForm({
    tracking_number: '',
    tracking_url: ''
});

const noteForm = useForm({
    notes: ''
});

const updateStatus = () => {
    statusForm.patch(route('admin.orders.update-status', props.order.id), {
        preserveScroll: true
    });
};

const shipOrder = () => {
    shipForm.post(route('admin.orders.ship', props.order.id), {
        onSuccess: () => {
            showShipForm.value = false;
            shipForm.reset();
        }
    });
};

const addNote = () => {
    noteForm.post(route('admin.orders.add-note', props.order.id), {
        onSuccess: () => {
            showNoteForm.value = false;
            noteForm.reset();
        }
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0);
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        processing: 'bg-indigo-100 text-indigo-800',
        shipped: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getPaymentStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head :title="`Order #${order.order_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.orders.index')" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order #{{ order.order_number }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="['px-3 py-1 text-sm rounded-full capitalize', getStatusClass(order.status)]">
                        {{ order.status }}
                    </span>
                    <span :class="['px-3 py-1 text-sm rounded-full capitalize', getPaymentStatusClass(order.payment_status)]">
                        {{ order.payment_status }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Order Details -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-sm text-gray-500">Product Type</div>
                                    <div class="text-sm font-medium text-gray-900 capitalize">{{ order.product_type }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Quantity</div>
                                    <div class="text-sm font-medium text-gray-900">{{ order.quantity }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Price</div>
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(order.price) }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Total</div>
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(order.total) }}</div>
                                </div>
                            </div>

                            <div v-if="order.product_options" class="mt-4 pt-4 border-t border-gray-100">
                                <div class="text-sm text-gray-500 mb-2">Product Options</div>
                                <pre class="text-sm bg-gray-50 p-3 rounded-lg overflow-x-auto">{{ JSON.stringify(order.product_options, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Photo Preview -->
                        <div v-if="order.photo" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Photo</h3>
                            <div class="flex items-start gap-4">
                                <img
                                    :src="`/storage/${order.photo.display_path || order.photo.thumbnail_path}`"
                                    :alt="order.photo.title"
                                    class="w-48 h-48 object-cover rounded-lg"
                                />
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ order.photo.title }}</h4>
                                    <Link :href="route('photos.show', order.photo.slug)" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        View Photo
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Information -->
                        <div v-if="order.shipping_address || order.tracking_number" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping</h3>

                            <div v-if="order.shipping_address" class="mb-4">
                                <div class="text-sm text-gray-500 mb-1">Shipping Address</div>
                                <div class="text-sm text-gray-900 whitespace-pre-line">{{ order.shipping_address }}</div>
                            </div>

                            <div v-if="order.tracking_number" class="flex items-center gap-4">
                                <div>
                                    <div class="text-sm text-gray-500">Tracking Number</div>
                                    <div class="text-sm font-medium text-gray-900">{{ order.tracking_number }}</div>
                                </div>
                                <a v-if="order.tracking_url" :href="order.tracking_url" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Track Package
                                </a>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Notes</h3>
                                <button @click="showNoteForm = !showNoteForm" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Add Note
                                </button>
                            </div>

                            <div v-if="showNoteForm" class="mb-4 p-4 bg-gray-50 rounded-lg">
                                <form @submit.prevent="addNote">
                                    <textarea
                                        v-model="noteForm.notes"
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Add a note..."
                                    ></textarea>
                                    <div class="mt-2 flex justify-end gap-2">
                                        <SecondaryButton @click="showNoteForm = false">Cancel</SecondaryButton>
                                        <PrimaryButton :disabled="noteForm.processing">Save Note</PrimaryButton>
                                    </div>
                                </form>
                            </div>

                            <div v-if="order.notes" class="text-sm text-gray-700 whitespace-pre-line">{{ order.notes }}</div>
                            <div v-else class="text-sm text-gray-500">No notes yet.</div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Customer Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Name</div>
                                    <div class="text-sm font-medium text-gray-900">{{ order.customer_name }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Email</div>
                                    <a :href="`mailto:${order.customer_email}`" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        {{ order.customer_email }}
                                    </a>
                                </div>
                                <div v-if="order.customer_phone">
                                    <div class="text-sm text-gray-500">Phone</div>
                                    <a :href="`tel:${order.customer_phone}`" class="text-sm text-indigo-600 hover:text-indigo-800">
                                        {{ order.customer_phone }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>

                            <!-- Update Status -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                                <div class="flex gap-2">
                                    <select
                                        v-model="statusForm.status"
                                        class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <PrimaryButton @click="updateStatus" :disabled="statusForm.processing">
                                        Update
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Ship Order -->
                            <div v-if="order.status !== 'shipped' && order.status !== 'delivered'" class="pt-4 border-t border-gray-100">
                                <button
                                    @click="showShipForm = !showShipForm"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                                >
                                    Mark as Shipped
                                </button>

                                <div v-if="showShipForm" class="mt-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
                                        <input
                                            v-model="shipForm.tracking_number"
                                            type="text"
                                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tracking URL (optional)</label>
                                        <input
                                            v-model="shipForm.tracking_url"
                                            type="url"
                                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <PrimaryButton @click="shipOrder" :disabled="shipForm.processing" class="w-full justify-center">
                                        Confirm Shipment
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Method</div>
                                    <div class="text-sm font-medium text-gray-900 capitalize">{{ order.payment_method || 'N/A' }}</div>
                                </div>
                                <div v-if="order.stripe_payment_intent">
                                    <div class="text-sm text-gray-500">Stripe ID</div>
                                    <div class="text-xs font-mono text-gray-600 break-all">{{ order.stripe_payment_intent }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Timestamps -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                            <div class="space-y-3">
                                <div>
                                    <div class="text-sm text-gray-500">Created</div>
                                    <div class="text-sm font-medium text-gray-900">{{ order.created_at }}</div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Last Updated</div>
                                    <div class="text-sm font-medium text-gray-900">{{ order.updated_at }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
