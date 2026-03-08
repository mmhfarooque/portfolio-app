<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const page = usePage();
const props = defineProps({
    turnstileSiteKey: String,
});

const turnstileToken = ref('');
const turnstileWidgetId = ref(null);
const messageSent = ref(false);

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
    website_url: '', // honeypot
    'cf-turnstile-response': '',
});

// Check if there's a flash success message (page reload after submit)
const flashSuccess = computed(() => page.props.flash?.success);

// Load Turnstile script
onMounted(() => {
    if (flashSuccess.value) {
        messageSent.value = true;
    }

    if (props.turnstileSiteKey) {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        window.onTurnstileLoad = () => {
            if (window.turnstile && document.getElementById('turnstile-container')) {
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
            messageSent.value = true;
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
                <h1 class="text-3xl font-bold text-[var(--text-primary)]">Get in Touch</h1>
                <p class="mt-2 text-[var(--text-secondary)]">Have a question or want to work together? I'd love to hear from you.</p>
            </div>

            <div class="max-w-xl mx-auto">
                <!-- Thank you message (replaces form after submit) -->
                <div v-if="messageSent" class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-[var(--text-primary)] mb-2">Thank you for your message!</h2>
                    <p class="text-[var(--text-secondary)]">I will get back to you soon.</p>
                </div>

                <!-- Contact form -->
                <form v-else @submit.prevent="submit" class="space-y-6">
                    <!-- Honeypot -->
                    <input type="text" name="website_url" v-model="form.website_url" class="hidden" tabindex="-1" autocomplete="off" />

                    <div>
                        <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Name *</label>
                        <input type="text" v-model="form.name" required class="w-full px-4 py-3 border border-[var(--border)] rounded-lg bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)]" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Email *</label>
                        <input type="email" v-model="form.email" required class="w-full px-4 py-3 border border-[var(--border)] rounded-lg bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)]" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Subject *</label>
                        <input type="text" v-model="form.subject" required class="w-full px-4 py-3 border border-[var(--border)] rounded-lg bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)]" />
                        <p v-if="form.errors.subject" class="mt-1 text-sm text-red-500">{{ form.errors.subject }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Message *</label>
                        <textarea v-model="form.message" rows="6" required class="w-full px-4 py-3 border border-[var(--border)] rounded-lg bg-[var(--bg-secondary)] text-[var(--text-primary)] focus:ring-[var(--accent)] focus:border-[var(--accent)]"></textarea>
                        <p v-if="form.errors.message" class="mt-1 text-sm text-red-500">{{ form.errors.message }}</p>
                    </div>

                    <!-- Cloudflare Turnstile -->
                    <div v-if="turnstileSiteKey" id="turnstile-container" class="flex justify-center"></div>

                    <button type="submit" :disabled="form.processing || (turnstileSiteKey && !turnstileToken)" :style="{ backgroundColor: 'var(--accent, #6366f1)', color: '#fff' }" class="w-full px-6 py-3 font-semibold rounded-lg disabled:opacity-50 transition hover:opacity-90">
                        {{ form.processing ? 'Sending...' : 'Send Message' }}
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
