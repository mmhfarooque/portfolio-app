<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { prefersReducedMotion } from './useReveal';

const props = defineProps({
    stats: { type: Array, required: true }, // [{ key, value, prefix, suffix, display, label }]
});

// The final numbers are always in the DOM (SSR, print, reduced motion,
// screen readers). The count-up is a separate aria-hidden layer drawn on
// top while it runs; print CSS hides that layer.
const animating = ref(false);
const counts = ref(props.stats.map((s) => s.value));
const bar = ref(null);
let observer = null;
let frame = null;

const format = (stat, n) => `${stat.prefix}${n.toLocaleString('en-AU')}${stat.suffix}`;

const countUp = () => {
    const start = performance.now();
    const duration = 1400;
    const tick = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        counts.value = props.stats.map((s) => Math.round(s.value * eased));
        if (t < 1) {
            frame = requestAnimationFrame(tick);
        } else {
            animating.value = false;
        }
    };
    frame = requestAnimationFrame(tick);
};

onMounted(() => {
    if (prefersReducedMotion() || !('IntersectionObserver' in window) || !bar.value) return;

    counts.value = props.stats.map(() => 0);
    animating.value = true;
    observer = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            observer.disconnect();
            countUp();
        }
    }, { threshold: 0.4 });
    observer.observe(bar.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    if (frame) cancelAnimationFrame(frame);
});
</script>

<template>
    <ul ref="bar" class="r-stats grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-theme-border sm:grid-cols-3 lg:grid-cols-6">
        <li v-for="(stat, i) in stats" :key="stat.key" class="bg-theme-bg-card px-4 py-5 text-center">
            <p class="r-serif r-accent relative text-3xl font-semibold tabular-nums leading-none sm:text-4xl">
                <span class="r-stat-final" :class="{ 'r-stat-final--hidden': animating }">{{ stat.display }}</span>
                <span v-if="animating" class="r-stat-anim absolute inset-0" aria-hidden="true">{{ format(stat, counts[i]) }}</span>
            </p>
            <p class="mt-2 text-xs font-medium uppercase tracking-wide text-theme-text-secondary">{{ stat.label }}</p>
        </li>
    </ul>
</template>
