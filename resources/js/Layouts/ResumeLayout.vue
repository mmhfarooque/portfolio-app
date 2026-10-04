<script setup>
import { computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import '@fontsource-variable/inter';
import '@fontsource-variable/source-serif-4';

const page = usePage();

const themeName = computed(() => page.props.theme?.name || 'dark');
const isDark = computed(() => Boolean(page.props.theme?.isDark ?? themeName.value === 'dark'));

// Same as PublicLayout: let the theme's CSS variables cascade everywhere.
onMounted(() => {
    document.documentElement.setAttribute('data-theme', themeName.value);
});
</script>

<template>
    <div
        class="resume min-h-screen bg-theme-bg-primary text-theme-text-primary"
        :class="{ 'resume--dark': isDark }"
        :data-theme="themeName"
    >
        <header class="resume-topbar border-b border-theme-border">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                <a href="/" class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition-colors">mfaruk.com</a>
                <span class="text-xs uppercase tracking-widest text-theme-text-secondary">Private resume</span>
            </div>
        </header>

        <slot />

        <footer class="resume-footer border-t border-theme-border">
            <p class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-xs text-theme-text-secondary">
                Shared privately. Please do not redistribute.
            </p>
        </footer>
    </div>
</template>

<style>
.resume {
    --r-accent: #1f3a5f;
    --r-accent-soft: rgba(31, 58, 95, 0.08);
    --r-accent-line: rgba(31, 58, 95, 0.35);
    --r-on-accent: #ffffff;
    font-family: 'Inter Variable', ui-sans-serif, system-ui, sans-serif;
}

/* Navy disappears on the dark themes, so the accent lifts to a steel blue. */
.resume--dark {
    --r-accent: #9cb8de;
    --r-accent-soft: rgba(156, 184, 222, 0.12);
    --r-accent-line: rgba(156, 184, 222, 0.4);
    --r-on-accent: #10233b;
}

.resume .r-serif {
    font-family: 'Source Serif 4 Variable', Georgia, 'Times New Roman', serif;
}

.resume .r-accent { color: var(--r-accent); }
.resume .r-accent-bg { background-color: var(--r-accent); color: var(--r-on-accent); }
.resume .r-accent-soft { background-color: var(--r-accent-soft); }
.resume .r-accent-border { border-color: var(--r-accent-line); }

.resume a.r-link {
    color: var(--r-accent);
    text-decoration: underline;
    text-decoration-color: var(--r-accent-line);
    text-underline-offset: 3px;
}
.resume a.r-link:hover { text-decoration-color: currentColor; }

.resume :focus-visible {
    outline: 2px solid var(--r-accent);
    outline-offset: 2px;
    border-radius: 2px;
}

.resume section[id] { scroll-margin-top: 1.5rem; }

@media print {
    @page { margin: 14mm 12mm; }

    .resume {
        --bg-primary: #ffffff;
        --bg-secondary: #ffffff;
        --bg-card: #ffffff;
        --text-primary: #111111;
        --text-secondary: #333333;
        --text-muted: #555555;
        --border: #cccccc;
        --r-accent: #1f3a5f;
        --r-accent-soft: transparent;
        --r-accent-line: #999999;
        --r-on-accent: #ffffff;
        font-size: 10.5pt;
    }

    .resume-topbar,
    .resume-nav,
    .r-no-print { display: none !important; }

    .resume-grid { display: block !important; }

    .resume .r-card {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .resume h2 { break-after: avoid; }
    .resume a.r-link { text-decoration: none; }
}
</style>
