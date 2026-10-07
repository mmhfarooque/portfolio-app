<script setup>
import { icons } from './icons';

defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path :d="icons[name] || icons.star" /></svg>
</template>
