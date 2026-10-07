<script setup>
import Icon from './Icon.vue';

defineProps({
    steps: { type: Array, required: true },
    label: { type: String, required: true },
});
</script>

<template>
    <!-- Stacks on phones, runs left to right from md up; steps reveal in order. -->
    <ol class="r-flow mt-5 flex flex-col gap-2 md:flex-row md:items-stretch md:gap-0" :aria-label="label" data-reveal>
        <template v-for="(step, i) in steps" :key="i">
            <li data-step class="flex min-w-0 flex-1 items-center gap-3 rounded-lg border r-accent-border r-accent-soft px-3 py-3 md:flex-col md:items-start md:gap-2" :style="{ '--i': i }">
                <span class="r-accent-bg flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold" aria-hidden="true">{{ i + 1 }}</span>
                <span class="text-sm font-medium leading-snug text-theme-text-primary">{{ step }}</span>
            </li>
            <li v-if="i < steps.length - 1" data-step class="r-accent flex justify-center md:items-center md:px-1.5" :style="{ '--i': i + 0.5 }" aria-hidden="true">
                <Icon name="arrow" class="h-5 w-5 rotate-90 md:rotate-0" />
            </li>
        </template>
    </ol>
</template>
