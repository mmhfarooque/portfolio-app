<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    templates: Array,
});

const form = useForm({
    recipient_email: '',
    recipient_name: '',
    subject: '',
    body_html: '',
});

const selectedTemplate = ref(null);

const applyTemplate = () => {
    if (!selectedTemplate.value) return;
    const template = props.templates.find(t => t.id === selectedTemplate.value);
    if (template) {
        form.subject = template.subject;
        form.body_html = template.body_html;
    }
};

const clearForm = () => {
    selectedTemplate.value = null;
    form.reset();
};

const send = () => {
    form.post(route('admin.email-templates.send'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedTemplate.value = null;
        },
    });
};

const previewHtml = ref('');
const showPreview = ref(false);

const preview = () => {
    // Render a quick preview using the template blade wrapper styling
    previewHtml.value = `<!DOCTYPE html>
<html><head><style>
body { margin: 0; padding: 0; background: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #374151; line-height: 1.6; }
.wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
.header { text-align: center; padding: 24px; }
.header a { font-size: 22px; font-weight: 700; color: #111827; text-decoration: none; }
.content { background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.content h1,.content h2,.content h3 { color: #111827; margin-top: 0; }
.content p { margin: 0 0 16px; color: #4b5563; }
.content a { color: #4f46e5; }
.btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
.footer { text-align: center; padding: 24px; font-size: 13px; color: #9ca3af; }
</style></head><body>
<div class="wrapper">
<div class="header"><span style="font-size:22px;font-weight:700;color:#111827;">Preview</span></div>
<div class="content">${form.body_html}</div>
<div class="footer"><p>&copy; ${new Date().getFullYear()} Preview</p></div>
</div></body></html>`;
    showPreview.value = true;
};
</script>

<template>
    <Head title="Compose Email" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.email-templates.index')"
                        class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compose Email</h2>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Template Selector -->
                <div class="bg-white shadow-sm sm:rounded-xl p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start from Template (optional)</label>
                    <div class="flex gap-3">
                        <select v-model="selectedTemplate"
                            class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null">-- Write from scratch --</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">
                                {{ t.name }}
                            </option>
                        </select>
                        <button @click="applyTemplate" :disabled="!selectedTemplate"
                            class="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition disabled:opacity-50">
                            Apply
                        </button>
                        <button @click="clearForm"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Recipient -->
                <div class="bg-white shadow-sm sm:rounded-xl p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-900">Recipient</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="recipient-email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input
                                id="recipient-email"
                                v-model="form.recipient_email"
                                type="email"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="recipient@example.com"
                            />
                            <p v-if="form.errors.recipient_email" class="mt-1 text-sm text-red-600">{{ form.errors.recipient_email }}</p>
                        </div>
                        <div>
                            <label for="recipient-name" class="block text-sm font-medium text-gray-700 mb-1">Name (optional)</label>
                            <input
                                id="recipient-name"
                                v-model="form.recipient_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="John Doe"
                            />
                        </div>
                    </div>
                </div>

                <!-- Subject & Body -->
                <div class="bg-white shadow-sm sm:rounded-xl p-6 space-y-4">
                    <div>
                        <label for="compose-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                        <input
                            id="compose-subject"
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Email subject..."
                        />
                        <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                    </div>

                    <div>
                        <label for="compose-body" class="block text-sm font-medium text-gray-700 mb-1">HTML Body *</label>
                        <textarea
                            id="compose-body"
                            v-model="form.body_html"
                            rows="16"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                            placeholder="<h2>Hello!</h2><p>Your email content here...</p>"
                        ></textarea>
                        <p v-if="form.errors.body_html" class="mt-1 text-sm text-red-600">{{ form.errors.body_html }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <button @click="preview" :disabled="!form.body_html"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Preview
                    </button>
                    <button @click="send" :disabled="form.processing || !form.recipient_email || !form.subject || !form.body_html"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Email
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPreview" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPreview = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all">
                            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">Email Preview</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Subject: {{ form.subject || '(no subject)' }}</p>
                                </div>
                                <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div class="p-4 max-h-[70vh] overflow-y-auto">
                                <iframe
                                    :srcdoc="previewHtml"
                                    class="w-full border border-gray-200 rounded-lg"
                                    style="min-height: 500px;"
                                    sandbox="allow-same-origin"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
