<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    email: { type: String, default: null },
    whatsapp: { type: String, default: null }, // digits for wa.me
    watch: { type: String, default: null }, // id of the hero buttons
});

// The mobile bar appears only once the hero buttons have scrolled away.
const showBar = ref(false);
let observer = null;

onMounted(() => {
    const target = props.watch ? document.getElementById(props.watch) : null;
    if (!target || !('IntersectionObserver' in window)) {
        showBar.value = true;
        return;
    }
    observer = new IntersectionObserver(([entry]) => {
        showBar.value = !entry.isIntersecting && entry.boundingClientRect.top < 0;
    });
    observer.observe(target);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="r-no-print">
        <!-- Desktop: small sticky button bottom-right. -->
        <a
            href="#contact"
            class="r-accent-bg fixed bottom-6 right-6 z-30 hidden min-h-[44px] items-center gap-2 rounded-full px-5 text-sm font-semibold shadow-lg transition-transform hover:-translate-y-0.5 md:inline-flex"
        >
            <Icon name="email" class="h-4 w-4" />
            Contact
        </a>

        <!-- Mobile: slim bottom bar. -->
        <nav
            class="r-contact-bar fixed inset-x-0 bottom-0 z-30 flex gap-2 border-t border-theme-border bg-theme-bg-card px-3 py-2 transition-transform duration-300 md:hidden"
            :class="showBar ? 'translate-y-0' : 'translate-y-full'"
            :aria-hidden="showBar ? undefined : 'true'"
            :inert="showBar ? undefined : true"
            aria-label="Contact"
        >
            <a
                v-if="email"
                :href="`mailto:${email}`"
                class="r-accent-bg flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-lg text-sm font-semibold"
            >
                <Icon name="email" class="h-4 w-4" />
                Email
            </a>
            <a
                v-if="whatsapp"
                :href="`https://wa.me/${whatsapp}`"
                target="_blank"
                rel="noopener"
                class="r-accent flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-lg border r-accent-border text-sm font-semibold"
            >
                <Icon name="whatsapp" class="h-4 w-4" />
                WhatsApp
            </a>
        </nav>
    </div>
</template>
