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
    variant: {
        type: String,
        default: 'danger', // 'danger', 'warning', 'info'
        validator: (value) => ['danger', 'warning', 'info'].includes(value)
    },
    processing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const close = () => {
    emit('close');
    emit('cancel');
};

const confirm = () => {
    emit('confirm');
};

// Close on escape key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        document.addEventListener('keydown', handleKeydown);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', handleKeydown);
        document.body.style.overflow = '';
    }
});

const variantStyles = {
    danger: {
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        iconBg: 'bg-red-100',
        iconColor: 'text-red-600',
        buttonBg: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    },
    warning: {
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        buttonBg: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    },
    info: {
        icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        buttonBg: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    },
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
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show"
                            class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all"
                        >
                            <!-- Icon -->
                            <div class="flex items-center justify-center mb-4">
                                <div :class="['w-14 h-14 rounded-full flex items-center justify-center', variantStyles[variant].iconBg]">
                                    <svg :class="['w-7 h-7', variantStyles[variant].iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="variantStyles[variant].icon" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ title }}</h3>
                                <p class="text-gray-600">{{ message }}</p>
                                <slot name="content"></slot>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="close"
                                    :disabled="processing"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition disabled:opacity-50"
                                >
                                    {{ cancelText }}
                                </button>
                                <button
                                    type="button"
                                    @click="confirm"
                                    :disabled="processing"
                                    :class="[
                                        'flex-1 px-4 py-2.5 text-sm font-medium text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition disabled:opacity-50',
                                        variantStyles[variant].buttonBg
                                    ]"
                                >
                                    <span v-if="processing" class="flex items-center justify-center gap-2">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                    <span v-else>{{ confirmText }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
