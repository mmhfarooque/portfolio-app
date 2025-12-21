<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photo: Object,
    selectedProduct: Object,
    productId: String,
    productName: String,
    productType: String,
    price: Number,
    shipping: Number,
    tax: Number,
    total: Number,
    isPaymentConfigured: Boolean,
    stripePublicKey: String
});

const form = useForm({
    product_id: props.productId,
    product_name: props.productName,
    product_type: props.productType,
    price: props.price,
    shipping: props.shipping,
    tax: props.tax,
    total: props.total,
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    shipping_address_line1: '',
    shipping_address_line2: '',
    shipping_city: '',
    shipping_state: '',
    shipping_postal_code: '',
    shipping_country: 'US'
});

const stripe = ref(null);
const cardElement = ref(null);
const cardError = ref('');
const processing = ref(false);

const countries = [
    { code: 'US', name: 'United States' },
    { code: 'CA', name: 'Canada' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'AU', name: 'Australia' },
    { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' },
    { code: 'IT', name: 'Italy' },
    { code: 'ES', name: 'Spain' },
    { code: 'NL', name: 'Netherlands' },
    { code: 'JP', name: 'Japan' },
];

const isDigitalProduct = props.productType === 'license';

onMounted(async () => {
    if (props.isPaymentConfigured && props.stripePublicKey) {
        // Load Stripe.js dynamically
        if (!window.Stripe) {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = initStripe;
            document.head.appendChild(script);
        } else {
            initStripe();
        }
    }
});

const initStripe = () => {
    stripe.value = Stripe(props.stripePublicKey);
    const elements = stripe.value.elements();
    cardElement.value = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#374151',
                '::placeholder': { color: '#9CA3AF' }
            }
        }
    });
    cardElement.value.mount('#card-element');
    cardElement.value.on('change', (event) => {
        cardError.value = event.error ? event.error.message : '';
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const submitOrder = async () => {
    if (!props.isPaymentConfigured) {
        alert('Payment is not configured. Please contact the site administrator.');
        return;
    }

    processing.value = true;
    cardError.value = '';

    try {
        // Submit order to get client secret
        const response = await fetch(route('checkout.process', props.photo.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(form.data())
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to create order');
        }

        // Confirm payment with Stripe
        const { error, paymentIntent } = await stripe.value.confirmCardPayment(data.client_secret, {
            payment_method: {
                card: cardElement.value,
                billing_details: {
                    name: form.customer_name,
                    email: form.customer_email
                }
            }
        });

        if (error) {
            cardError.value = error.message;
        } else if (paymentIntent.status === 'succeeded') {
            // Redirect to confirmation page
            window.location.href = route('order.confirmation', data.order_id);
        }
    } catch (err) {
        cardError.value = err.message || 'An error occurred. Please try again.';
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Head :title="`Checkout - ${photo.title}`" />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <Link :href="route('print.options', photo.slug)" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    &larr; Back to product options
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 mt-4">Checkout</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                <input type="text" v-model="form.customer_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" v-model="form.customer_email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="tel" v-model="form.customer_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address (for physical products) -->
                    <div v-if="!isDigitalProduct" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Shipping Address</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
                                <input type="text" v-model="form.shipping_address_line1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                <input type="text" v-model="form.shipping_address_line2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                    <input type="text" v-model="form.shipping_city" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State/Province *</label>
                                    <input type="text" v-model="form.shipping_state" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                    <input type="text" v-model="form.shipping_postal_code" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                    <select v-model="form.shipping_country" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                        <option v-for="country in countries" :key="country.code" :value="country.code">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment</h2>
                        <div v-if="isPaymentConfigured">
                            <div id="card-element" class="p-4 border border-gray-300 rounded-lg"></div>
                            <p v-if="cardError" class="mt-2 text-sm text-red-600">{{ cardError }}</p>
                        </div>
                        <div v-else class="p-4 bg-yellow-50 rounded-lg">
                            <p class="text-yellow-800">Payment is not configured. Please contact the site administrator.</p>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-24">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                        <!-- Photo Preview -->
                        <div class="flex items-center gap-4 mb-4 pb-4 border-b border-gray-100">
                            <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-20 h-20 object-cover rounded-lg" />
                            <div>
                                <p class="font-medium text-gray-900">{{ photo.title }}</p>
                                <p class="text-sm text-gray-500">{{ productName }}</p>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-900">{{ formatCurrency(price) }}</span>
                            </div>
                            <div v-if="!isDigitalProduct" class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="text-gray-900">{{ formatCurrency(shipping) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="text-gray-900">{{ formatCurrency(tax) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-gray-100 font-semibold text-base">
                                <span>Total</span>
                                <span>{{ formatCurrency(total) }}</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button @click="submitOrder" :disabled="processing || !isPaymentConfigured" class="w-full mt-6 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            <span v-if="processing">Processing...</span>
                            <span v-else>Pay {{ formatCurrency(total) }}</span>
                        </button>

                        <p class="mt-4 text-xs text-gray-500 text-center">
                            Your payment is secured by Stripe
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
