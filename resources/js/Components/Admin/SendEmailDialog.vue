<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    recipient_email: '',
    recipient_name: '',
    subject: '',
    body_html: '',
});

watch(() => props.show, (newVal) => {
    if (newVal && props.user) {
        form.recipient_email = props.user.email || '';
        form.recipient_name = props.user.name || '';
    }
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const close = () => {
    form.reset();
    emit('close');
};

const send = () => {
    form.post(route('admin.email-templates.send'), {
        preserveScroll: true,
        onSuccess: () => {
            close();
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-xl w-full transform transition-all">
                        <div class="flex items-center justify-between p-4 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900">Send Email</h3>
                            <button @click="close" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input v-model="form.recipient_email" type="email"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                    <p v-if="form.errors.recipient_email" class="mt-1 text-xs text-red-600">{{ form.errors.recipient_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input v-model="form.recipient_name" type="text"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                                <input v-model="form.subject" type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                <p v-if="form.errors.subject" class="mt-1 text-xs text-red-600">{{ form.errors.subject }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Body (HTML) *</label>
                                <textarea v-model="form.body_html" rows="8"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                    placeholder="<p>Hello...</p>"></textarea>
                                <p v-if="form.errors.body_html" class="mt-1 text-xs text-red-600">{{ form.errors.body_html }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 p-4 border-t border-gray-100">
                            <button @click="close"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button @click="send"
                                :disabled="form.processing || !form.recipient_email || !form.subject || !form.body_html"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                <span v-if="form.processing">Sending...</span>
                                <span v-else>Send Email</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
