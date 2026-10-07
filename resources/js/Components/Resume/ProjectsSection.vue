<script setup>
import { computed, ref } from 'vue';
import SegmentText from './SegmentText.vue';

const props = defineProps({
    groups: { type: Array, required: true },
    intro: { type: String, default: '' },
    clientRange: { type: String, default: null },
});

const allChips = [
    { key: 'all', label: 'All' },
    { key: 'government', label: 'Government & Education' },
    { key: 'industrial', label: 'Industrial' },
    { key: 'woocommerce', label: 'WooCommerce' },
    { key: 'shopify', label: 'Shopify' },
    { key: 'multiDomain', label: 'Multi-domain' },
    { key: 'own', label: 'Own work' },
];

const items = computed(() => props.groups.flatMap((g) => g.items));
const chips = computed(() => allChips.filter((c) => c.key === 'all' || items.value.some((i) => (i.tags || []).includes(c.key))));

const active = ref('all');
const shows = (item) => active.value === 'all' || (item.tags || []).includes(active.value);
const groupShows = (group) => group.items.some(shows);
const count = (key) => (key === 'all' ? items.value.length : items.value.filter((i) => (i.tags || []).includes(key)).length);
</script>

<template>
    <div>
        <p v-if="intro" class="mt-4 max-w-3xl text-sm leading-relaxed text-theme-text-secondary">{{ intro }}</p>

        <div class="r-no-print mt-6 flex flex-wrap gap-2" role="group" aria-label="Filter projects">
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                class="r-chip inline-flex min-h-[44px] items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors"
                :class="active === chip.key ? 'r-accent-bg border-transparent' : 'border-theme-border bg-theme-bg-card text-theme-text-primary hover:border-[color:var(--r-accent)]'"
                :aria-pressed="active === chip.key ? 'true' : 'false'"
                @click="active = chip.key"
            >
                {{ chip.label }}
                <span class="text-xs opacity-75">{{ count(chip.key) }}</span>
            </button>
        </div>

        <div
            v-for="group in groups"
            :key="group.key"
            class="r-filterable"
            :class="{ 'r-filtered-out': !groupShows(group) }"
        >
            <h3 class="r-accent mt-10 text-sm font-semibold uppercase tracking-wide">{{ group.title }}</h3>

            <ul class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <li
                    v-for="(item, i) in group.items"
                    :key="i"
                    class="r-card r-lift r-filterable flex flex-col rounded-xl border border-theme-border bg-theme-bg-card p-5 shadow-sm"
                    :class="{ 'r-filtered-out': !shows(item) }"
                >
                    <template v-if="item.sites">
                        <p class="text-sm font-semibold">
                            <a
                                v-for="site in item.sites"
                                :key="site.site"
                                :href="site.url"
                                class="r-link flex min-h-[44px] items-center [overflow-wrap:anywhere]"
                                target="_blank"
                                rel="noopener"
                            >{{ site.site }}</a>
                        </p>
                        <p v-if="item.sector" class="mt-1 text-xs font-medium uppercase tracking-wide text-theme-text-secondary">{{ item.sector }}</p>
                        <p v-if="item.whatIDid" class="mt-3 text-sm leading-relaxed text-theme-text-secondary">{{ item.whatIDid }}</p>
                    </template>
                    <p v-else class="text-sm leading-relaxed text-theme-text-secondary"><SegmentText :segments="item.segments" /></p>

                    <ul v-if="item.stack && item.stack.length" class="mt-auto flex flex-wrap gap-1.5 pt-4" aria-label="Stack">
                        <li v-for="badge in item.stack" :key="badge" class="r-accent-soft r-accent rounded px-2 py-0.5 text-xs font-medium">{{ badge }}</li>
                    </ul>
                </li>
            </ul>

            <p v-if="group.also && group.also.length" class="mt-3 text-sm text-theme-text-secondary">
                Also:
                <template v-for="(site, s) in group.also" :key="site.site">
                    <template v-if="s">, </template>
                    <a :href="site.url" class="r-link" target="_blank" rel="noopener">{{ site.site }}</a>
                </template>.
            </p>
        </div>

        <p v-if="clientRange" class="r-accent-soft mt-10 rounded-xl px-5 py-4 text-sm font-medium leading-relaxed">
            {{ clientRange }}
        </p>
    </div>
</template>
