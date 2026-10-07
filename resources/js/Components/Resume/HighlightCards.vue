<script setup>
import Icon from './Icon.vue';
import SegmentText from './SegmentText.vue';

defineProps({
    items: { type: Array, required: true }, // segment arrays
});

// Pick an icon from the achievement's own wording.
const rules = [
    [/plugin/i, 'puzzle'],
    [/\bAI\b|Claude/, 'spark'],
    [/ticket/i, 'check'],
    [/Laravel|vulnerab/i, 'shield'],
    [/Upwork|\$/, 'trophy'],
    [/site|store/i, 'globe'],
];

const iconFor = (segments) => {
    const text = segments.map((s) => s.text).join('');
    return rules.find(([re]) => re.test(text))?.[1] ?? 'star';
};
</script>

<template>
    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <li
            v-for="(item, i) in items"
            :key="i"
            data-reveal
            class="r-card r-lift flex gap-4 rounded-xl border border-theme-border bg-theme-bg-card p-5 shadow-sm"
            :style="{ '--i': i }"
        >
            <span class="r-accent-soft r-accent flex h-11 w-11 shrink-0 items-center justify-center rounded-lg">
                <Icon :name="iconFor(item)" class="h-6 w-6" />
            </span>
            <p class="text-sm leading-relaxed text-theme-text-primary"><SegmentText :segments="item" /></p>
        </li>
    </ul>
</template>
