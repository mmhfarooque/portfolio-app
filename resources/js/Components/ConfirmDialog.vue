<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirm Action'
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed?'
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    type: {
        type: String,
        default: 'warning', // warning, danger, info
        validator: (value) => ['warning', 'danger', 'info'].includes(value)
    }
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const visible = ref(props.show);

watch(() => props.show, (newVal) => {
    visible.value = newVal;
});

const confirm = () => {
    visible.value = false;
    emit('confirm');
    emit('close');
};

const cancel = () => {
    visible.value = false;
    emit('cancel');
    emit('close');
};

const buttonColors = {
    warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    info: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
};

const iconColors = {
    warning: 'text-yellow-600 bg-yellow-100',
    danger: 'text-red-600 bg-red-100',
    info: 'text-blue-600 bg-blue-100'
};

const icons = {
    warning: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`,
    danger: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`,
    info: `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
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
            <div v-if="visible" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="cancel"></div>

                    <!-- Dialog -->
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div v-if="visible" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                            <div class="bg-white px-6 pb-6 pt-5">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full" :class="iconColors[type]">
                                        <span v-html="icons[type]"></span>
                                    </div>
                                    <div class="mt-1">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                                        <p class="mt-2 text-sm text-gray-600">{{ message }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                                <button
                                    type="button"
                                    @click="confirm"
                                    class="inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                                    :class="buttonColors[type]"
                                >
                                    {{ confirmText }}
                                </button>
                                <button
                                    type="button"
                                    @click="cancel"
                                    class="inline-flex justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                                >
                                    {{ cancelText }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
