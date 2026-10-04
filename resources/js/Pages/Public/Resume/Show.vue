<script setup>
import { computed } from 'vue';
import ResumeLayout from '@/Layouts/ResumeLayout.vue';
import SectionNav from '@/Components/Resume/SectionNav.vue';
import SegmentText from '@/Components/Resume/SegmentText.vue';
import PipelineDiagram from '@/Components/Resume/PipelineDiagram.vue';

const props = defineProps({
    resume: { type: Object, required: true },
});

const header = computed(() => props.resume.header || {});

const sections = [
    { id: 'profile', label: 'Profile' },
    { id: 'skills', label: 'Skills' },
    { id: 'experience', label: 'Experience' },
    { id: 'ai-engineering', label: 'AI Engineering' },
    { id: 'projects', label: 'Projects' },
    { id: 'education', label: 'Education' },
    { id: 'contact', label: 'Contact' },
];

const projectGroup = (key) => (props.resume.projects || []).find((g) => g.key === key);
const otherProjectGroups = computed(() =>
    (props.resume.projects || []).filter((g) => g.key !== 'ecommerce'),
);
const ecommerce = computed(() => projectGroup('ecommerce'));

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

const icons = {
    email: 'M3 5h18v14H3z M3 6l9 7 9-7',
    phone: 'M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2',
    whatsapp: 'M4 20l1.3-3.9A8 8 0 1112 20a8 8 0 01-3.9-1z',
    linkedin: 'M4 4h16v16H4z M8 10v6 M8 7.5v.01 M12 16v-6 M12 13a2.5 2.5 0 015 0v3',
    github: 'M9 19c-4 1.3-4-2-6-2.5 M15 21v-3.5a3 3 0 00-.8-2.3c2.7-.3 5.5-1.3 5.5-6a4.7 4.7 0 00-1.3-3.2 4.3 4.3 0 00-.1-3.2s-1-.3-3.4 1.3a11.6 11.6 0 00-6 0C6.5 2.5 5.5 2.8 5.5 2.8a4.3 4.3 0 00-.1 3.2A4.7 4.7 0 004 9.2c0 4.6 2.8 5.7 5.5 6a3 3 0 00-.8 2.3V21',
    website: 'M12 3a9 9 0 100 18 9 9 0 000-18z M3 12h18 M12 3c2.5 2.7 2.5 15.3 0 18 M12 3c-2.5 2.7-2.5 15.3 0 18',
    location: 'M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z M12 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
};
</script>

<template>
    <Head :title="`${header.name} — Resume`">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
        <meta head-key="description" name="description" :content="header.headline" />
        <component :is="'script'" type="application/ld+json">{{ jsonLdString }}</component>
    </Head>

    <ResumeLayout>
        <div class="resume-grid mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[13rem_1fr] lg:gap-12 lg:px-8 lg:py-14">
            <aside class="hidden lg:block">
                <div class="sticky top-8">
                    <SectionNav :sections="sections" />
                </div>
            </aside>

            <main>
                <!-- Header -->
                <header class="border-b border-theme-border pb-8">
                    <h1 class="r-serif text-4xl font-semibold tracking-tight sm:text-5xl">{{ header.name }}</h1>
                    <p class="r-accent mt-3 text-base font-medium leading-relaxed sm:text-lg">{{ header.headline }}</p>

                    <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm text-theme-text-secondary">
                        <li v-if="header.location" class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 r-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="icons.location" /></svg>
                            {{ header.location }}
                        </li>
                        <li v-for="link in contactLinks" :key="link.key" class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 r-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="icons[link.key]" /></svg>
                            <a
                                :href="link.href"
                                class="r-link"
                                :target="link.external ? '_blank' : undefined"
                                :rel="link.external ? 'noopener noreferrer' : undefined"
                            >{{ link.label }}</a>
                        </li>
                    </ul>
                </header>

                <!-- Profile -->
                <section id="profile" class="border-b border-theme-border py-10" aria-labelledby="h-profile">
                    <h2 id="h-profile" class="r-serif text-2xl font-semibold">Profile</h2>
                    <div class="mt-5 max-w-3xl space-y-4 leading-relaxed text-theme-text-secondary">
                        <p v-for="(paragraph, i) in resume.profile" :key="i">{{ paragraph }}</p>
                    </div>
                </section>

                <!-- Skills -->
                <section id="skills" class="border-b border-theme-border py-10" aria-labelledby="h-skills">
                    <h2 id="h-skills" class="r-serif text-2xl font-semibold">Core skills</h2>
                    <div class="mt-6 grid gap-8 md:grid-cols-2">
                        <div v-for="group in resume.skills" :key="group.label">
                            <h3 class="r-accent text-sm font-semibold uppercase tracking-wide">{{ group.label }}</h3>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                <li
                                    v-for="(item, i) in group.items"
                                    :key="i"
                                    class="rounded-md border border-theme-border bg-theme-bg-secondary px-2.5 py-1 text-xs leading-snug text-theme-text-primary"
                                >{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Experience -->
                <section id="experience" class="border-b border-theme-border py-10" aria-labelledby="h-experience">
                    <h2 id="h-experience" class="r-serif text-2xl font-semibold">Experience</h2>
                    <ol class="relative mt-8 space-y-10 border-l r-accent-border pl-6 sm:pl-8">
                        <li v-for="(role, i) in resume.experience" :key="i" class="relative">
                            <span
                                class="absolute -left-[1.84rem] top-1.5 h-3 w-3 rounded-full border-2 sm:-left-[2.34rem]"
                                :class="role.concurrent ? 'border-[color:var(--r-accent)] bg-theme-bg-primary' : 'r-accent-bg border-transparent'"
                                aria-hidden="true"
                            ></span>

                            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-6">
                                <h3 class="r-serif text-lg font-semibold leading-snug">{{ role.role }}</h3>
                                <p class="shrink-0 text-sm text-theme-text-secondary">
                                    <time>{{ role.start }}</time> – <time>{{ role.end }}</time>
                                </p>
                            </div>
                            <p class="mt-1 text-sm text-theme-text-secondary">
                                <span class="font-medium text-theme-text-primary">{{ role.company }}</span><template v-if="role.location"> · {{ role.location }}</template>
                                <span
                                    v-if="role.concurrent"
                                    class="r-accent-soft r-accent ml-2 inline-block rounded px-2 py-0.5 text-xs font-medium"
                                >{{ role.concurrentNote }}</span>
                            </p>

                            <p v-if="role.context" class="mt-3 max-w-3xl border-l-2 r-accent-border pl-3 text-sm italic leading-relaxed text-theme-text-secondary">
                                {{ role.context }}
                            </p>

                            <ul class="mt-4 max-w-3xl list-disc space-y-2 pl-5 text-sm leading-relaxed text-theme-text-secondary marker:text-[color:var(--r-accent)]">
                                <li v-for="(bullet, b) in role.bullets" :key="b"><SegmentText :segments="bullet" /></li>
                            </ul>
                        </li>
                    </ol>
                </section>

                <!-- AI Engineering -->
                <section id="ai-engineering" class="border-b border-theme-border py-10" aria-labelledby="h-ai">
                    <h2 id="h-ai" class="r-serif text-2xl font-semibold">AI Engineering</h2>
                    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="card in resume.aiShowcase"
                            :key="card.key"
                            class="r-card rounded-lg border border-theme-border bg-theme-bg-card p-6 shadow-sm"
                            :class="{ 'md:col-span-2 xl:col-span-3': card.steps && card.steps.length }"
                        >
                            <div class="r-accent-bg mb-4 h-1 w-10 rounded-full" aria-hidden="true"></div>
                            <h3 class="r-serif text-lg font-semibold">{{ card.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-theme-text-secondary">{{ card.body }}</p>
                            <PipelineDiagram
                                v-if="card.steps && card.steps.length"
                                :steps="card.steps"
                                :label="`${card.title}: steps`"
                            />
                        </article>
                    </div>
                </section>

                <!-- Projects -->
                <section id="projects" class="border-b border-theme-border py-10" aria-labelledby="h-projects">
                    <h2 id="h-projects" class="r-serif text-2xl font-semibold">Selected projects</h2>
                    <p v-if="resume.projectsIntro" class="mt-4 max-w-3xl text-sm leading-relaxed text-theme-text-secondary">{{ resume.projectsIntro }}</p>

                    <template v-for="group in otherProjectGroups" :key="group.key">
                        <h3 class="r-accent mt-10 text-sm font-semibold uppercase tracking-wide">{{ group.title }}</h3>

                        <div v-if="group.type === 'table'" class="mt-4 grid gap-4 sm:grid-cols-2">
                            <article
                                v-for="(item, i) in group.items"
                                :key="i"
                                class="r-card rounded-lg border border-theme-border bg-theme-bg-card p-5"
                            >
                                <p class="text-sm font-semibold">
                                    <template v-for="(site, s) in item.sites" :key="site.site">
                                        <template v-if="s">, </template>
                                        <a :href="site.url" class="r-link" target="_blank" rel="noopener noreferrer">{{ site.site }}</a>
                                    </template>
                                </p>
                                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-theme-text-secondary">{{ item.sector }}</p>
                                <p v-if="item.whatIDid" class="mt-3 text-sm leading-relaxed text-theme-text-secondary">{{ item.whatIDid }}</p>
                            </article>
                        </div>

                        <ul v-else class="mt-4 space-y-3">
                            <li
                                v-for="(item, i) in group.items"
                                :key="i"
                                class="r-card rounded-lg border border-theme-border bg-theme-bg-card p-5 text-sm leading-relaxed text-theme-text-secondary"
                            ><SegmentText :segments="item.segments" /></li>
                        </ul>

                        <!-- E-commerce sits third, as in the master. -->
                        <template v-if="group.key === 'multiDomain' && ecommerce">
                            <h3 class="r-accent mt-10 text-sm font-semibold uppercase tracking-wide">{{ ecommerce.title }}</h3>
                            <div class="mt-4 overflow-x-auto rounded-lg border border-theme-border">
                                <table class="w-full min-w-[36rem] text-left text-sm">
                                    <thead class="r-accent-soft">
                                        <tr>
                                            <th v-for="column in ecommerce.columns" :key="column" scope="col" class="px-4 py-3 font-semibold">{{ column }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-theme-border">
                                        <tr v-for="(item, i) in ecommerce.items" :key="i" class="align-top">
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <!-- One link per line when a row lists several domains. -->
                                                <a v-for="site in item.sites" :key="site.site" :href="site.url" class="r-link block" target="_blank" rel="noopener noreferrer">{{ site.site }}</a>
                                            </td>
                                            <td class="px-4 py-3 text-theme-text-secondary">
                                                <span class="text-theme-text-primary">{{ item.sector }}</span><template v-if="item.whatIDid">. {{ item.whatIDid }}</template>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-theme-text-secondary">{{ item.builder }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-if="ecommerce.also.length" class="mt-3 text-sm text-theme-text-secondary">
                                Also:
                                <template v-for="(site, s) in ecommerce.also" :key="site.site">
                                    <template v-if="s">, </template>
                                    <a :href="site.url" class="r-link" target="_blank" rel="noopener noreferrer">{{ site.site }}</a>
                                </template>.
                            </p>
                        </template>
                    </template>

                    <p v-if="resume.clientRange" class="r-accent-soft mt-10 rounded-lg px-5 py-4 text-sm font-medium leading-relaxed">
                        {{ resume.clientRange }}
                    </p>
                </section>

                <!-- Education (with freelance record and languages) -->
                <section id="education" class="border-b border-theme-border py-10" aria-labelledby="h-education">
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
                        <li v-for="link in contactLinks" :key="link.key" class="flex items-center gap-3 rounded-lg border border-theme-border bg-theme-bg-card px-4 py-3 text-sm">
                            <svg class="h-5 w-5 shrink-0 r-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="icons[link.key]" /></svg>
                            <a
                                :href="link.href"
                                class="r-link break-all"
                                :target="link.external ? '_blank' : undefined"
                                :rel="link.external ? 'noopener noreferrer' : undefined"
                            >{{ link.label }}</a>
                        </li>
                    </ul>
                </section>
            </main>
        </div>
    </ResumeLayout>
</template>
