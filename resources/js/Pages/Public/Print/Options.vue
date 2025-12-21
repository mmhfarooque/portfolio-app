<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    photo: Object,
    products: Array,
    isApiConfigured: Boolean
});

const selectedProduct = ref(null);
const showInquiryForm = ref(false);

const form = useForm({
    name: '',
    email: '',
    product: '',
    message: '',
    honeypot: ''
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const selectProduct = (product) => {
    selectedProduct.value = product;
};

const proceedToCheckout = () => {
    if (!selectedProduct.value) return;
    const productId = selectedProduct.value.id || selectedProduct.value.name;
    window.location.href = route('checkout.show', props.photo.slug) + '?product=' + encodeURIComponent(productId);
};

const openInquiryForm = (product) => {
    form.product = product?.name || product?.id || '';
    showInquiryForm.value = true;
};

const submitInquiry = () => {
    form.post(route('print.inquiry', props.photo.slug), {
        onSuccess: () => {
            showInquiryForm.value = false;
            form.reset();
        }
    });
};

const productsByType = computed(() => {
    const grouped = {};
    for (const product of (props.products || [])) {
        const type = product.type || 'other';
        if (!grouped[type]) grouped[type] = [];
        grouped[type].push(product);
    }
    return grouped;
});

const typeLabels = {
    print: 'Fine Art Prints',
    canvas: 'Canvas Prints',
    metal: 'Metal Prints',
    license: 'Digital Licenses',
    other: 'Other Products'
};
</script>

<template>
    <Head :title="`Print Options - ${photo.title}`" />

    <PublicLayout>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link :href="route('photos.show', photo.slug)" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to photo
            </Link>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Photo Preview -->
                <div>
                    <div class="aspect-square bg-gray-200 rounded-xl overflow-hidden sticky top-24">
                        <img :src="`/storage/${photo.display_path || photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-contain" />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mt-4">{{ photo.title }}</h1>
                </div>

                <!-- Products -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Print & License Options</h2>

                    <div v-if="!isApiConfigured" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <p class="text-yellow-800">Online ordering is currently unavailable. Please use the inquiry form below.</p>
                    </div>

                    <div v-if="products && products.length > 0" class="space-y-8">
                        <div v-for="(typeProducts, type) in productsByType" :key="type">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ typeLabels[type] || type }}</h3>
                            <div class="space-y-3">
                                <div v-for="product in typeProducts" :key="product.id || product.name" @click="selectProduct(product)" :class="['p-4 border rounded-lg cursor-pointer transition', selectedProduct === product ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300']">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ product.name }}</h4>
                                            <p v-if="product.size" class="text-sm text-gray-500">{{ product.size }}</p>
                                            <p v-if="product.description" class="text-sm text-gray-600 mt-1">{{ product.description }}</p>
                                        </div>
                                        <span class="font-semibold text-gray-900">{{ formatCurrency(product.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Products -->
                    <div v-else class="text-center py-8">
                        <p class="text-gray-500">No products available. Please contact us for custom options.</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-3">
                        <button v-if="selectedProduct && isApiConfigured" @click="proceedToCheckout" class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Continue to Checkout
                        </button>
                        <button @click="openInquiryForm(selectedProduct)" class="w-full px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                            Send Inquiry
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inquiry Modal -->
        <div v-if="showInquiryForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Print Inquiry</h3>
                <form @submit.prevent="submitInquiry" class="space-y-4">
                    <input type="text" name="honeypot" v-model="form.honeypot" class="hidden" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" v-model="form.name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" v-model="form.email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <input type="text" v-model="form.product" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea v-model="form.message" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showInquiryForm = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                            {{ form.processing ? 'Sending...' : 'Send Inquiry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
