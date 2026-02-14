<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    template: Object,
});

const form = useForm({
    subject: props.template.subject,
    body_html: props.template.body_html,
    is_active: props.template.is_active,
});

const previewHtml = ref('');
const previewSubject = ref('');
const showPreview = ref(false);
const showResetConfirm = ref(false);
const loadingPreview = ref(false);

const save = () => {
    form.put(route('admin.email-templates.update', props.template.id), {
        preserveScroll: true,
    });
};

const preview = async () => {
    loadingPreview.value = true;
    try {
        const response = await fetch(route('admin.email-templates.preview', props.template.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        previewHtml.value = data.html;
        previewSubject.value = data.subject;
        showPreview.value = true;
    } catch (e) {
        console.error('Preview failed:', e);
    } finally {
        loadingPreview.value = false;
    }
};

const sendTest = () => {
    router.post(route('admin.email-templates.send-test', props.template.id), {}, {
        preserveScroll: true,
    });
};

const resetTemplate = () => {
    router.post(route('admin.email-templates.reset', props.template.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showResetConfirm.value = false;
            // Reload the page to get fresh data
            router.reload();
        },
    });
};

const insertVariable = (variable) => {
    const textarea = document.querySelector('#body-editor');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = form.body_html;
    const tag = `{{${variable}}}`;

    form.body_html = text.substring(0, start) + tag + text.substring(end);

    // Restore cursor position
    setTimeout(() => {
        textarea.focus();
        textarea.setSelectionRange(start + tag.length, start + tag.length);
    }, 0);
};

const wrapVar = (v) => '{' + '{' + v + '}' + '}';

const htmlSnippets = [
    { label: '<p> Paragraph', code: '\n<p></p>' },
    { label: '<a class="btn"> Button', code: '\n<a href="" class="btn">Button Text</a>' },
    { label: '<h2> Heading', code: '\n<h2></h2>' },
    { label: '<strong> Bold', code: '\n<strong></strong>' },
    { label: '<ul> List', code: '\n<ul>\n  <li></li>\n</ul>' },
];

const insertVariableInSubject = (variable) => {
    const input = document.querySelector('#subject-input');
    if (!input) return;

    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = form.subject;
    const tag = `{{${variable}}}`;

    form.subject = text.substring(0, start) + tag + text.substring(end);

    setTimeout(() => {
        input.focus();
        input.setSelectionRange(start + tag.length, start + tag.length);
    }, 0);
};
</script>

<template>
    <Head :title="`Edit: ${template.name}`" />

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
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ template.name }}</h2>
                    <span class="px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">{{ template.slug }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="preview" :disabled="loadingPreview"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Preview
                    </button>
                    <button @click="sendTest"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Test
                    </button>
                    <button @click="save" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex gap-6">
                    <!-- Main Editor -->
                    <div class="flex-1 space-y-6">
                        <!-- Subject -->
                        <div class="bg-white shadow-sm sm:rounded-xl p-6">
                            <label for="subject-input" class="block text-sm font-medium text-gray-700 mb-2">Subject Line</label>
                            <input
                                id="subject-input"
                                v-model="form.subject"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Email subject..."
                            />
                            <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                        </div>

                        <!-- Body HTML -->
                        <div class="bg-white shadow-sm sm:rounded-xl p-6">
                            <label for="body-editor" class="block text-sm font-medium text-gray-700 mb-2">HTML Body</label>
                            <textarea
                                id="body-editor"
                                v-model="form.body_html"
                                rows="20"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder="<h2>Hello {{user_name}}</h2>..."
                            ></textarea>
                            <p v-if="form.errors.body_html" class="mt-1 text-sm text-red-600">{{ form.errors.body_html }}</p>
                        </div>

                        <!-- Settings -->
                        <div class="bg-white shadow-sm sm:rounded-xl p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Active Status</h3>
                                    <p class="text-sm text-gray-500">When inactive, the system will use the default hardcoded email.</p>
                                </div>
                                <button
                                    @click="form.is_active = !form.is_active"
                                    :class="[
                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                        form.is_active ? 'bg-indigo-600' : 'bg-gray-200'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            form.is_active ? 'translate-x-5' : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <button @click="showResetConfirm = true"
                                    class="text-sm text-red-600 hover:text-red-700 font-medium">
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar - Variable Reference -->
                    <div class="w-72 flex-shrink-0">
                        <div class="bg-white shadow-sm sm:rounded-xl p-6 sticky top-24">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Available Variables</h3>
                            <p class="text-xs text-gray-500 mb-4">Click to insert into the body. Use the subject field buttons to insert into the subject line.</p>

                            <div class="space-y-2">
                                <div v-for="variable in template.available_variables" :key="variable"
                                    class="flex items-center gap-2">
                                    <button @click="insertVariable(variable)"
                                        class="flex-1 text-left px-3 py-2 text-xs font-mono bg-gray-50 rounded-lg hover:bg-indigo-50 hover:text-indigo-700 transition truncate"
                                        :title="'Insert ' + wrapVar(variable) + ' into body'">
                                        {{ wrapVar(variable) }}
                                    </button>
                                    <button @click="insertVariableInSubject(variable)"
                                        class="px-2 py-2 text-xs text-gray-400 hover:text-indigo-600 transition"
                                        title="Insert into subject">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div v-if="!template.available_variables?.length" class="text-sm text-gray-400 italic">
                                No variables available for this template.
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Quick HTML</h4>
                                <div class="space-y-1">
                                    <button v-for="snippet in htmlSnippets" :key="snippet.label"
                                        @click="form.body_html += snippet.code"
                                        class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 rounded transition">
                                        {{ snippet.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400">
                                <p>Last updated: {{ template.updated_at }}</p>
                            </div>
                        </div>
                    </div>
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
                                    <p class="text-xs text-gray-500 mt-0.5">Subject: {{ previewSubject }}</p>
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

        <!-- Reset Confirmation -->
        <ConfirmModal
            :show="showResetConfirm"
            title="Reset Template"
            message="This will restore the template to its original default content. Any customizations will be lost."
            confirm-text="Reset to Default"
            variant="warning"
            @confirm="resetTemplate"
            @close="showResetConfirm = false"
        />
    </AuthenticatedLayout>
</template>
