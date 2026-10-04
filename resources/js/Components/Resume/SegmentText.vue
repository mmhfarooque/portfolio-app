<script setup>
// Renders text the importer split into plain runs and links, so resume
// content never goes through v-html.
defineProps({
    segments: { type: Array, required: true },
});
</script>

<template>
    <template v-for="(segment, i) in segments" :key="i">
        <a
            v-if="segment.url"
            :href="segment.url"
            class="r-link"
            target="_blank"
            rel="noopener noreferrer"
        >{{ segment.text }}</a>
        <template v-else>{{ segment.text }}</template>
    </template>
</template>
