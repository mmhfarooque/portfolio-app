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
                <a href="/" class="inline-flex min-h-[44px] items-center text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition-colors">mfaruk.com</a>
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

/* Hero: soft navy wash. */
.resume .r-hero {
    background:
        radial-gradient(60rem 30rem at 85% -10%, rgba(31, 58, 95, 0.16), transparent 70%),
        linear-gradient(180deg, var(--r-accent-soft), transparent 85%);
}
.resume--dark .r-hero {
    background:
        radial-gradient(60rem 30rem at 85% -10%, rgba(156, 184, 222, 0.14), transparent 70%),
        linear-gradient(180deg, var(--r-accent-soft), transparent 85%);
}

/* AI centrepiece band. */
.resume .r-ai {
    background: linear-gradient(135deg, var(--r-accent-soft), transparent 70%);
    border: 1px solid var(--r-accent-line);
}

.resume .r-stats { background-color: var(--border); }

.resume .r-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.resume .r-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 24px -12px rgba(16, 35, 59, 0.35); }

.resume .r-filtered-out { display: none; }
.resume .r-print-only { display: none; }

/* Stats: the count-up layer sits over the final number, which keeps its space. */
.resume .r-stat-final--hidden { visibility: hidden; }

/* Timeline rail and dots. */
.resume .r-timeline::before {
    content: '';
    position: absolute;
    left: 0.4rem;
    top: 0.5rem;
    bottom: 0.5rem;
    width: 2px;
    background: var(--r-accent-line);
}
.resume .r-timeline > li > .r-dot { left: calc(-1.75rem + 0.06rem); }
@media (min-width: 640px) {
    .resume .r-timeline > li > .r-dot { left: calc(-2.25rem + 0.06rem); }
}
.resume .r-branch > .r-dot { left: calc(-2.75rem + 0.06rem) !important; }
@media (min-width: 640px) {
    .resume .r-branch > .r-dot { left: calc(-4.75rem + 0.06rem) !important; }
}
.resume .r-branch::before {
    content: '';
    position: absolute;
    top: 0.95rem;
    left: -2.3rem;
    width: 2.3rem;
    border-top: 2px dashed var(--r-accent-line);
}
@media (min-width: 640px) {
    .resume .r-branch::before { left: -4.3rem; width: 4.3rem; }
}
.resume .r-role-main { border-color: var(--r-accent-line); }

/* Scroll reveal (only once JS has added .r-motion). */
.resume .r-motion [data-reveal] {
    opacity: 0;
    transform: translateY(14px);
    transition: opacity 0.6s ease, transform 0.6s ease;
    transition-delay: calc(var(--i, 0) * 70ms);
}
.resume .r-motion [data-reveal].is-in { opacity: 1; transform: none; }
.resume .r-motion .r-flow[data-reveal] { opacity: 1; transform: none; }
.resume .r-motion .r-flow [data-step] {
    opacity: 0;
    transform: translateX(-14px);
    transition: opacity 0.5s ease, transform 0.5s ease;
    transition-delay: calc(var(--i, 0) * 260ms + 150ms);
}
.resume .r-motion .r-flow.is-in [data-step] { opacity: 1; transform: none; }

@media (prefers-reduced-motion: reduce) {
    .resume *, .resume *::before, .resume *::after {
        transition: none !important;
        animation: none !important;
    }
}

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

    .resume .r-hero, .resume .r-ai { background: none !important; border: 0 !important; }
    .resume .r-filtered-out { display: block !important; }
    .resume .r-filterable.r-filtered-out { display: block !important; }
    .resume li.r-filterable.r-filtered-out { display: flex !important; }
    .resume .r-role-body { display: block !important; }
    .resume [data-reveal], .resume [data-step] { opacity: 1 !important; transform: none !important; }
    .resume .r-lift { transform: none !important; }
    .resume .r-print-only { display: revert !important; }
    .resume .r-stat-final { visibility: visible !important; }
    .resume .r-stat-anim { display: none !important; }
    .resume a { overflow-wrap: anywhere; }
    .resume a.r-link { text-decoration: none; }
}
</style>
