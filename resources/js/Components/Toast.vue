<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'success', // success, error, warning, info
        validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    title: {
        type: String,
        default: ''
    },
    message: {
        type: String,
        default: ''
    },
    duration: {
        type: Number,
        default: 5000
    },
    closable: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['close']);

const visible = ref(props.show);
let timeout = null;

watch(() => props.show, (newVal) => {
    visible.value = newVal;
    if (newVal && props.duration > 0) {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            close();
        }, props.duration);
    }
});

onMounted(() => {
    if (props.show && props.duration > 0) {
        timeout = setTimeout(() => {
            close();
        }, props.duration);
    }
});

const close = () => {
    visible.value = false;
    emit('close');
};

const icons = {
    success: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    error: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    warning: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`,
    info: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

const colors = {
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800'
};

const iconColors = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500'
};
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed top-4 right-4 z-50 max-w-sm w-full shadow-lg rounded-xl border overflow-hidden"
            :class="colors[type]"
        >
            <div class="p-4">
                <div class="flex items-start gap-3">
                    <div :class="iconColors[type]" class="flex-shrink-0" v-html="icons[type]"></div>
                    <div class="flex-1 pt-0.5">
                        <p v-if="title" class="text-sm font-semibold">{{ title }}</p>
                        <p class="text-sm" :class="title ? 'mt-1 opacity-90' : ''">{{ message }}</p>
                    </div>
                    <button
                        v-if="closable"
                        @click="close"
                        class="flex-shrink-0 ml-2 inline-flex rounded-md p-1.5 hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                        :class="`focus:ring-${type === 'success' ? 'green' : type === 'error' ? 'red' : type === 'warning' ? 'yellow' : 'blue'}-500`"
                    >
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Progress bar -->
            <div v-if="duration > 0" class="h-1 bg-black/10">
                <div
                    class="h-full transition-all ease-linear"
                    :class="type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'"
                    :style="{ animation: `shrink ${duration}ms linear forwards` }"
                ></div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
</style>
