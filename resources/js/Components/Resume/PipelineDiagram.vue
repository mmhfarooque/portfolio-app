<script setup>
defineProps({
    steps: { type: Array, required: true },
    label: { type: String, required: true },
});
</script>

<template>
    <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" :aria-label="label">
        <li
            v-for="(step, i) in steps"
            :key="i"
            class="relative flex gap-3 rounded-md border r-accent-border r-accent-soft p-3"
        >
            <span
                class="r-accent-bg flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                aria-hidden="true"
            >{{ i + 1 }}</span>
            <span class="text-sm leading-snug text-theme-text-primary">{{ step }}</span>
            <svg
                v-if="i < steps.length - 1"
                class="r-accent absolute -right-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 lg:block"
                viewBox="0 0 16 16"
                fill="none"
                aria-hidden="true"
            >
                <path d="M5 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </li>
    </ol>
</template>
