<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    order: Object
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const isDigitalProduct = props.order.product_type === 'license';
</script>

<template>
    <Head title="Order Confirmation" />

    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Thank You!</h1>
                <p class="text-gray-600 mt-2">Your order has been confirmed.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <!-- Order Details -->
                <div class="mb-6 pb-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h2>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Order Number</dt>
                            <dd class="font-medium text-gray-900">{{ order.order_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" class="inline-flex px-2 py-1 rounded-full text-xs font-medium">
                                    {{ order.payment_status === 'paid' ? 'Paid' : 'Pending' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Product</dt>
                            <dd class="font-medium text-gray-900">{{ order.product_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Total</dt>
                            <dd class="font-medium text-gray-900">{{ formatCurrency(order.total) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Photo -->
                <div v-if="order.photo" class="mb-6 pb-6 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                        <img :src="`/storage/${order.photo.thumbnail_path}`" :alt="order.photo.title" class="w-24 h-24 object-cover rounded-lg" />
                        <div>
                            <p class="font-medium text-gray-900">{{ order.photo.title }}</p>
                            <Link :href="route('photos.show', order.photo.slug)" class="text-sm text-indigo-600 hover:text-indigo-800">
                                View photo
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- License Key (for digital products) -->
                <div v-if="isDigitalProduct && order.license_key" class="mb-6 pb-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Your License Key</h3>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <code class="text-sm font-mono text-gray-900 break-all">{{ order.license_key }}</code>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Please save this license key. You'll need it to download your photo.
                    </p>
                    <Link :href="route('download.photo', order.id) + '?key=' + order.license_key" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Photo
                    </Link>
                </div>

                <!-- Confirmation Email -->
                <div class="text-center text-sm text-gray-600">
                    <p>A confirmation email has been sent to <strong>{{ order.customer_email }}</strong></p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex justify-center gap-4">
                <Link href="/" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Return Home
                </Link>
                <Link :href="route('photos.index')" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Continue Browsing
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
