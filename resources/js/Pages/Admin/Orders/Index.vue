<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    orders: Object,
    stats: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const paymentStatus = ref(props.filters.payment_status || '');
const productType = ref(props.filters.product_type || '');

const applyFilters = () => {
    router.get(route('admin.orders.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        payment_status: paymentStatus.value || undefined,
        product_type: productType.value || undefined,
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

watch([status, paymentStatus, productType], applyFilters);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0);
};

const getStatusClass = (orderStatus) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        processing: 'bg-indigo-100 text-indigo-800',
        shipped: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[orderStatus] || 'bg-gray-100 text-gray-800';
};

const getPaymentStatusClass = (paymentStat) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-gray-100 text-gray-800',
    };
    return classes[paymentStat] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Orders" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Orders</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Total Orders</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Pending</div>
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Paid</div>
                        <div class="text-2xl font-bold text-green-600">{{ stats.paid }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Total Revenue</div>
                        <div class="text-2xl font-bold text-indigo-600">{{ formatCurrency(stats.revenue) }}</div>
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
                                    placeholder="Search orders..."
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="flex gap-4">
                                <select
                                    v-model="status"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <select
                                    v-model="paymentStatus"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Payments</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="failed">Failed</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                                <select
                                    v-model="productType"
                                    class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Products</option>
                                    <option value="print">Print</option>
                                    <option value="digital">Digital</option>
                                    <option value="canvas">Canvas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="p-6">
                        <div v-if="orders.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="order in orders.data" :key="order.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img
                                                    v-if="order.photo?.thumbnail_path"
                                                    :src="`/storage/${order.photo.thumbnail_path}`"
                                                    :alt="order.photo.title"
                                                    class="w-10 h-10 object-cover rounded mr-3"
                                                />
                                                <div v-else class="w-10 h-10 bg-gray-200 rounded mr-3 flex items-center justify-center text-gray-400">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">#{{ order.order_number }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ order.customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ order.customer_email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="capitalize text-sm text-gray-900">{{ order.product_type }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full capitalize', getStatusClass(order.status)]">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-xs rounded-full capitalize', getPaymentStatusClass(order.payment_status)]">
                                                {{ order.payment_status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ formatCurrency(order.total) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ order.created_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('admin.orders.show', order.id)" class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No orders found</h3>
                            <p class="mt-1 text-gray-500">Orders will appear here when customers make purchases.</p>
                        </div>

                        <div v-if="orders.data.length > 0" class="mt-6">
                            <Pagination :links="orders.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
