<script setup>
import { computed, ref } from 'vue';
import ResumeLayout from '@/Layouts/ResumeLayout.vue';
import SectionNav from '@/Components/Resume/SectionNav.vue';
import SegmentText from '@/Components/Resume/SegmentText.vue';
import Icon from '@/Components/Resume/Icon.vue';
import StatsBar from '@/Components/Resume/StatsBar.vue';
import HighlightCards from '@/Components/Resume/HighlightCards.vue';
import StepFlow from '@/Components/Resume/StepFlow.vue';
import ExperienceTimeline from '@/Components/Resume/ExperienceTimeline.vue';
import ProjectsSection from '@/Components/Resume/ProjectsSection.vue';
import ContactCta from '@/Components/Resume/ContactCta.vue';
import { useReveal } from '@/Components/Resume/useReveal';

const props = defineProps({
    resume: { type: Object, required: true },
});

const root = ref(null);
useReveal(root);

const header = computed(() => props.resume.header || {});
const has = (key) => Array.isArray(props.resume[key]) && props.resume[key].length > 0;

const sections = computed(() => [
    has('highlights') && { id: 'highlights', label: 'Highlights' },
    has('profile') && { id: 'profile', label: 'Profile' },
    has('aiShowcase') && { id: 'ai-engineering', label: 'AI Engineering' },
    { id: 'experience', label: 'Experience' },
    has('projects') && { id: 'projects', label: 'Projects' },
    has('skills') && { id: 'skills', label: 'Skills' },
    ...(props.resume.extraSections || []).map((s) => ({ id: `extra-${s.key}`, label: s.title })),
    (has('education') || props.resume.freelance) && { id: 'education', label: 'Education' },
    { id: 'contact', label: 'Contact' },
].filter(Boolean));

const whatsapp = computed(() => header.value.phone?.whatsapp || null);

const contactLinks = computed(() => {
    const h = header.value;
    const links = [];
    if (h.email) links.push({ key: 'email', label: h.email, href: `mailto:${h.email}` });
    if (h.phone) {
        links.push({ key: 'phone', label: h.phone.label, href: `tel:${h.phone.tel}` });
        if (h.phone.whatsapp) links.push({ key: 'whatsapp', label: 'WhatsApp', href: `https://wa.me/${h.phone.whatsapp}`, external: true });
    }
    if (h.linkedin) links.push({ key: 'linkedin', label: h.linkedin.label, href: h.linkedin.url, external: true });
    if (h.github) links.push({ key: 'github', label: h.github.label, href: h.github.url, external: true });
    if (h.website) links.push({ key: 'website', label: h.website.label, href: h.website.url, external: true });
    return links;
});

const heroLinks = computed(() => contactLinks.value.filter((l) => ['linkedin', 'github', 'website'].includes(l.key)));

// Each skill group shows its first few chips, the rest behind "Show all".
const SKILL_PREVIEW = 5;
const openSkills = ref({});

// Chips naming the core stack get the accent treatment.
const emphasised = /\b(WordPress|WooCommerce|Laravel|Claude Code)\b/;

// Person JSON-LD, only on this unlocked page. Text child, not v-html, so it
// survives SSR (see CLAUDE.md, SSR gotchas).
const jsonLdString = computed(() => {
    const h = header.value;
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Person',
        name: h.name,
        jobTitle: h.headline,
        email: h.email ? `mailto:${h.email}` : undefined,
        url: h.website?.url,
        address: h.location ? { '@type': 'PostalAddress', addressLocality: h.location } : undefined,
        sameAs: [h.linkedin?.url, h.github?.url].filter(Boolean),
    });
});
</script>

<template>
    <Head :title="`${header.name} — Resume`">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
        <meta head-key="description" name="description" :content="header.headline" />
        <component :is="'script'" type="application/ld+json">{{ jsonLdString }}</component>
    </Head>

    <ResumeLayout>
        <div ref="root" class="pb-20 md:pb-0">
            <!-- Hero -->
            <header class="r-hero border-b border-theme-border">
                <div class="mx-auto max-w-6xl px-4 pb-10 pt-12 sm:px-6 sm:pt-16 lg:px-8">
                    <h1 class="r-serif text-4xl font-semibold tracking-tight sm:text-6xl">{{ header.name }}</h1>
                    <p class="r-accent mt-4 max-w-3xl text-lg font-medium leading-relaxed sm:text-xl">{{ header.headline }}</p>
                    <p v-if="header.location" class="mt-4 flex items-center gap-2 text-sm text-theme-text-secondary">
                        <Icon name="location" class="r-accent h-4 w-4 shrink-0" />
                        {{ header.location }}
                    </p>

                    <div id="hero-cta" class="r-no-print mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            v-if="header.email"
                            :href="`mailto:${header.email}`"
                            class="r-accent-bg inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg px-6 text-base font-semibold shadow-sm transition-transform hover:-translate-y-0.5"
                        >
                            <Icon name="email" class="h-5 w-5" />
                            Email me
                        </a>
                        <a
                            v-if="whatsapp"
                            :href="`https://wa.me/${whatsapp}`"
                            target="_blank"
                            rel="noopener"
                            class="r-accent inline-flex min-h-[48px] items-center justify-center gap-2 rounded-lg border-2 r-accent-border bg-theme-bg-card px-6 text-base font-semibold transition-transform hover:-translate-y-0.5"
                        >
                            <Icon name="whatsapp" class="h-5 w-5" />
                            WhatsApp
                        </a>
                    </div>

                    <ul v-if="heroLinks.length" class="mt-4 flex flex-wrap gap-x-6 text-sm">
                        <li v-for="link in heroLinks" :key="link.key" class="flex items-center gap-2">
                            <Icon :name="link.key" class="r-accent h-4 w-4 shrink-0" />
                            <a :href="link.href" class="r-link inline-flex min-h-[44px] items-center" target="_blank" rel="noopener">{{ link.label }}</a>
                        </li>
                    </ul>

                    <StatsBar v-if="has('stats')" :stats="resume.stats" class="mt-10" />
                </div>
            </header>

            <div class="resume-grid mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-12 lg:px-8 lg:py-14">
                <aside class="hidden lg:block">
                    <div class="sticky top-8">
                        <SectionNav :sections="sections" />
                    </div>
                </aside>

                <main class="min-w-0">
                    <!-- Highlights -->
                    <section v-if="has('highlights')" id="highlights" class="border-b border-theme-border pb-10" aria-labelledby="h-highlights">
                        <h2 id="h-highlights" class="r-serif text-2xl font-semibold sm:text-3xl">Highlights</h2>
                        <HighlightCards :items="resume.highlights" class="mt-6" />
                    </section>

                    <!-- Profile -->
                    <section v-if="has('profile')" id="profile" class="border-b border-theme-border py-10" aria-labelledby="h-profile">
                        <h2 id="h-profile" class="r-serif text-2xl font-semibold">Profile</h2>
                        <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-theme-text-secondary">
                            <p v-for="(paragraph, i) in resume.profile" :key="i">{{ paragraph }}</p>
                        </div>
                    </section>

                    <!-- AI Engineering: the centrepiece -->
                    <section v-if="has('aiShowcase')" id="ai-engineering" class="r-ai -mx-4 my-10 px-4 py-10 sm:-mx-6 sm:rounded-2xl sm:px-8" aria-labelledby="h-ai">
                        <h2 id="h-ai" class="r-serif flex items-center gap-3 text-2xl font-semibold sm:text-3xl">
                            <span class="r-accent-bg flex h-10 w-10 items-center justify-center rounded-lg"><Icon name="spark" class="h-5 w-5" /></span>
                            AI Engineering
                        </h2>
                        <div class="mt-6 grid gap-5">
                            <article
                                v-for="card in resume.aiShowcase"
                                :key="card.key"
                                data-reveal
                                class="r-card rounded-xl border border-theme-border bg-theme-bg-card p-6 shadow-sm"
                            >
                                <h3 class="r-serif text-xl font-semibold">{{ card.title }}</h3>
                                <p v-if="card.body" class="mt-2 text-sm leading-relaxed text-theme-text-secondary">{{ card.body }}</p>
                                <StepFlow v-if="card.steps.length" :steps="card.steps" :label="`${card.title}: steps`" />
                                <p v-if="card.caption" class="mt-4 text-sm font-medium text-theme-text-secondary">{{ card.caption }}</p>
                            </article>
                        </div>
                    </section>

                    <!-- Experience -->
                    <section id="experience" class="border-b border-theme-border py-10" aria-labelledby="h-experience">
                        <h2 id="h-experience" class="r-serif text-2xl font-semibold sm:text-3xl">Experience</h2>
                        <ExperienceTimeline :roles="resume.experience" />
                    </section>

                    <!-- Projects -->
                    <section v-if="has('projects')" id="projects" class="border-b border-theme-border py-10" aria-labelledby="h-projects">
                        <h2 id="h-projects" class="r-serif text-2xl font-semibold sm:text-3xl">Selected projects</h2>
                        <ProjectsSection :groups="resume.projects" :intro="resume.projectsIntro" :client-range="resume.clientRange" />
                    </section>

                    <!-- Skills -->
                    <section v-if="has('skills')" id="skills" class="border-b border-theme-border py-10" aria-labelledby="h-skills">
                        <h2 id="h-skills" class="r-serif text-2xl font-semibold">Core skills</h2>
                        <div class="mt-6 grid gap-8 md:grid-cols-2">
                            <div v-for="group in resume.skills" :key="group.label">
                                <h3 class="r-accent text-sm font-semibold uppercase tracking-wide">{{ group.label }}</h3>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    <li
                                        v-for="(item, i) in group.items"
                                        :key="i"
                                        class="rounded-md border px-2.5 py-1 text-xs leading-snug"
                                        :class="[
                                            emphasised.test(item)
                                                ? 'r-accent-bg r-chip-strong border-transparent font-semibold'
                                                : 'border-theme-border bg-theme-bg-secondary text-theme-text-primary',
                                            { 'r-print-only': i >= SKILL_PREVIEW && !openSkills[group.label] },
                                        ]"
                                    >{{ item }}</li>
                                </ul>
                                <button
                                    v-if="group.items.length > SKILL_PREVIEW"
                                    type="button"
                                    class="r-no-print r-accent mt-1 inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium"
                                    :aria-expanded="openSkills[group.label] ? 'true' : 'false'"
                                    @click="openSkills[group.label] = !openSkills[group.label]"
                                >
                                    {{ openSkills[group.label] ? 'Show fewer' : `Show all (${group.items.length})` }}
                                    <Icon name="chevron" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSkills[group.label] }" />
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Any section the page has no special place for -->
                    <section
                        v-for="extra in resume.extraSections || []"
                        :id="`extra-${extra.key}`"
                        :key="extra.key"
                        class="border-b border-theme-border py-10"
                        :aria-labelledby="`h-extra-${extra.key}`"
                    >
                        <h2 :id="`h-extra-${extra.key}`" class="r-serif text-2xl font-semibold">{{ extra.title }}</h2>
                        <p v-for="(paragraph, i) in extra.paragraphs" :key="`p${i}`" class="mt-4 max-w-3xl leading-relaxed text-theme-text-secondary">{{ paragraph }}</p>
                        <ul v-if="extra.bullets.length" class="mt-4 max-w-3xl list-disc space-y-2 pl-5 text-sm leading-relaxed text-theme-text-secondary marker:text-[color:var(--r-accent)]">
                            <li v-for="(bullet, b) in extra.bullets" :key="b"><SegmentText :segments="bullet" /></li>
                        </ul>
                    </section>

                    <!-- Education (with freelance record and languages) -->
                    <section v-if="has('education') || resume.freelance" id="education" class="border-b border-theme-border py-10" aria-labelledby="h-education">
                        <h2 id="h-education" class="r-serif text-2xl font-semibold">Education and certification</h2>
                        <ul class="mt-6 space-y-4">
                            <li v-for="(entry, i) in resume.education" :key="i" class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-6">
                                <div>
                                    <p class="font-medium">{{ entry.qualification }}</p>
                                    <p v-if="entry.institution" class="text-sm text-theme-text-secondary">{{ entry.institution }}</p>
                                </div>
                                <p v-if="entry.years" class="shrink-0 text-sm text-theme-text-secondary">{{ entry.years }}</p>
                            </li>
                        </ul>

                        <div class="mt-10 grid gap-8 md:grid-cols-2">
                            <div v-if="resume.freelance">
                                <h3 class="r-accent text-sm font-semibold uppercase tracking-wide">Freelance record</h3>
                                <p class="mt-3 text-sm leading-relaxed text-theme-text-secondary">{{ resume.freelance }}</p>
                            </div>
                            <div v-if="resume.languages && resume.languages.length">
                                <h3 class="r-accent text-sm font-semibold uppercase tracking-wide">Languages</h3>
                                <ul class="mt-3 space-y-1 text-sm text-theme-text-secondary">
                                    <li v-for="language in resume.languages" :key="language">{{ language }}</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- Contact -->
                    <section id="contact" class="py-10" aria-labelledby="h-contact">
                        <h2 id="h-contact" class="r-serif text-2xl font-semibold">Contact</h2>
                        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                            <li v-for="link in contactLinks" :key="link.key">
                                <a
                                    :href="link.href"
                                    class="r-lift flex min-h-[52px] items-center gap-3 rounded-xl border border-theme-border bg-theme-bg-card px-4 py-3 text-sm"
                                    :target="link.external ? '_blank' : undefined"
                                    :rel="link.external ? 'noopener' : undefined"
                                >
                                    <Icon :name="link.key" class="r-accent h-5 w-5 shrink-0" />
                                    <span class="r-accent font-medium [overflow-wrap:anywhere]">{{ link.label }}</span>
                                </a>
                            </li>
                        </ul>
                    </section>
                </main>
            </div>

            <ContactCta :email="header.email" :whatsapp="whatsapp" watch="hero-cta" />
        </div>
    </ResumeLayout>
</template>
