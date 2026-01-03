<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const page = usePage();
const props = defineProps({
    turnstileSiteKey: String,
});

const turnstileToken = ref('');
const turnstileWidgetId = ref(null);

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
    website_url: '', // honeypot
    'cf-turnstile-response': '',
});

// Load Turnstile script
onMounted(() => {
    if (props.turnstileSiteKey) {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        window.onTurnstileLoad = () => {
            if (window.turnstile) {
                turnstileWidgetId.value = window.turnstile.render('#turnstile-container', {
                    sitekey: props.turnstileSiteKey,
                    callback: (token) => {
                        turnstileToken.value = token;
                        form['cf-turnstile-response'] = token;
                    },
                    'expired-callback': () => {
                        turnstileToken.value = '';
                        form['cf-turnstile-response'] = '';
                    },
                });
            }
        };
    }
});

onUnmounted(() => {
    if (turnstileWidgetId.value && window.turnstile) {
        window.turnstile.remove(turnstileWidgetId.value);
    }
});

const submit = () => {
    form.post(route('contact.send'), {
        onSuccess: () => {
            form.reset();
            // Reset Turnstile widget
            if (turnstileWidgetId.value && window.turnstile) {
                window.turnstile.reset(turnstileWidgetId.value);
            }
        }
    });
};
</script>

<template>
    <SeoHead
        title="Contact Mahmud Farooque - Photography Inquiries"
        description="Get in touch with Mahmud Farooque for photography inquiries, print purchases, or collaboration opportunities."
        type="website"
        url="https://mfaruk.com/contact"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Contact' }
        ]"
    />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold text-gray-900">Get in Touch</h1>
                <p class="mt-2 text-gray-600">Have a question or want to work together? I'd love to hear from you.</p>
            </div>

            <div class="max-w-xl mx-auto">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Honeypot -->
                    <input type="text" name="website_url" v-model="form.website_url" class="hidden" tabindex="-1" autocomplete="off" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" v-model="form.name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" v-model="form.email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                        <input type="text" v-model="form.subject" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea v-model="form.message" rows="6" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                    </div>

                    <!-- Cloudflare Turnstile -->
                    <div v-if="turnstileSiteKey" id="turnstile-container" class="flex justify-center"></div>

                    <button type="submit" :disabled="form.processing || (turnstileSiteKey && !turnstileToken)" class="w-full px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition">
                        {{ form.processing ? 'Sending...' : 'Send Message' }}
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
