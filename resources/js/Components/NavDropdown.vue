<script setup>
import { ref } from 'vue';

defineProps({
    active: {
        type: Boolean,
        default: false
    },
    align: {
        type: String,
        default: 'left'
    }
});

const open = ref(false);
</script>

<template>
    <div class="relative" @click.away="open = false">
        <button
            @click="open = !open"
            :class="[
                'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition duration-150 ease-in-out',
                active ? 'text-gray-900 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
            ]"
        >
            <slot name="trigger" />
            <svg
                class="w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                :class="[
                    'absolute mt-2 w-64 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 py-2 z-50',
                    align === 'right' ? 'right-0' : 'left-0'
                ]"
            >
                <slot name="content" />
            </div>
        </Transition>
    </div>
</template>
