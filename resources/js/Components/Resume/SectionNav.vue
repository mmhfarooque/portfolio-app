<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    sections: { type: Array, required: true }, // [{ id, label }]
});

const active = ref(props.sections[0]?.id ?? null);
let observer = null;

// Scroll-spy. IntersectionObserver only exists in the browser, so it is
// set up in onMounted (SSR renders the plain list).
onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    active.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-30% 0px -60% 0px' },
    );

    props.sections.forEach(({ id }) => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <nav class="resume-nav" aria-label="Resume sections">
        <ul class="space-y-1 border-l border-theme-border">
            <li v-for="section in sections" :key="section.id">
                <a
                    :href="`#${section.id}`"
                    class="-ml-px block border-l-2 py-1.5 pl-4 text-sm transition-colors"
                    :class="active === section.id
                        ? 'r-accent font-semibold border-current'
                        : 'border-transparent text-theme-text-secondary hover:text-theme-text-primary'"
                    :aria-current="active === section.id ? 'true' : undefined"
                    @click="active = section.id"
                >{{ section.label }}</a>
            </li>
        </ul>
    </nav>
</template>
