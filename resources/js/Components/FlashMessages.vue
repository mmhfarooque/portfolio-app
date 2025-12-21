<script setup>
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const messages = ref([]);

const flash = computed(() => page.props.flash);

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        addMessage('success', newFlash.success);
    }
    if (newFlash?.error) {
        addMessage('error', newFlash.error);
    }
    if (newFlash?.warning) {
        addMessage('warning', newFlash.warning);
    }
    if (newFlash?.info) {
        addMessage('info', newFlash.info);
    }
}, { immediate: true, deep: true });

const addMessage = (type, text) => {
    const id = Date.now();
    messages.value.push({ id, type, text });

    setTimeout(() => {
        removeMessage(id);
    }, 5000);
};

const removeMessage = (id) => {
    const index = messages.value.findIndex(m => m.id === id);
    if (index > -1) {
        messages.value.splice(index, 1);
    }
};

const getIcon = (type) => {
    switch (type) {
        case 'success':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'error':
            return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'warning':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
        case 'info':
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

const getClasses = (type) => {
    switch (type) {
        case 'success':
            return 'bg-green-50 border-green-200 text-green-800';
        case 'error':
            return 'bg-red-50 border-red-200 text-red-800';
        case 'warning':
            return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'info':
            return 'bg-blue-50 border-blue-200 text-blue-800';
        default:
            return 'bg-gray-50 border-gray-200 text-gray-800';
    }
};
</script>

<template>
    <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full">
        <TransitionGroup
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-x-4"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-4"
        >
            <div
                v-for="message in messages"
                :key="message.id"
                :class="[
                    'flex items-start gap-3 p-4 rounded-lg border shadow-lg',
                    getClasses(message.type)
                ]"
            >
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(message.type)" />
                </svg>
                <p class="flex-1 text-sm font-medium">{{ message.text }}</p>
                <button
                    @click="removeMessage(message.id)"
                    class="flex-shrink-0 p-1 rounded hover:bg-black/5 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
