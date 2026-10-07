<script setup>
import { ref } from 'vue';
import Icon from './Icon.vue';
import SegmentText from './SegmentText.vue';

const props = defineProps({
    roles: { type: Array, required: true },
});

// The first main role is open; later main roles start collapsed.
// Concurrent roles hang off the timeline as a side branch.
const firstMain = props.roles.findIndex((r) => !r.concurrent);
const open = ref(props.roles.map((r, i) => r.concurrent || i === firstMain));
const toggle = (i) => { open.value[i] = !open.value[i]; };
const collapsible = (i) => !props.roles[i].concurrent && i !== firstMain;

// The open main role shows its first few bullets, the rest behind "Show all".
const PREVIEW = 6;
const allBullets = ref(props.roles.map(() => false));
const clipped = (i, b) => i === firstMain && !allBullets.value[i] && b >= PREVIEW;
</script>

<template>
    <ol class="r-timeline relative mt-8 space-y-6 pl-7 sm:pl-9">
        <li
            v-for="(role, i) in roles"
            :key="i"
            data-reveal
            class="relative"
            :class="role.concurrent ? 'r-branch ml-4 sm:ml-10' : ''"
        >
            <span
                class="r-dot absolute top-2 h-3.5 w-3.5 rounded-full border-2"
                :class="role.concurrent ? 'border-[color:var(--r-accent)] bg-theme-bg-primary' : 'r-accent-bg border-transparent'"
                aria-hidden="true"
            ></span>

            <div class="r-card rounded-xl border border-theme-border bg-theme-bg-card p-5 shadow-sm" :class="{ 'r-role-main': i === firstMain }">
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

                <button
                    v-if="collapsible(i)"
                    type="button"
                    class="r-no-print r-accent mt-2 inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium"
                    :aria-expanded="open[i] ? 'true' : 'false'"
                    :aria-controls="`role-body-${i}`"
                    @click="toggle(i)"
                >
                    {{ open[i] ? 'Hide details' : 'Show details' }}
                    <Icon name="chevron" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open[i] }" />
                </button>

                <div v-show="open[i]" :id="`role-body-${i}`" class="r-role-body">
                    <p v-if="role.context" class="mt-3 max-w-3xl border-l-2 r-accent-border pl-3 text-sm italic leading-relaxed text-theme-text-secondary">
                        {{ role.context }}
                    </p>
                    <ul class="mt-4 max-w-3xl list-disc space-y-2 pl-5 text-sm leading-relaxed text-theme-text-secondary marker:text-[color:var(--r-accent)]">
                        <li
                            v-for="(bullet, b) in role.bullets"
                            :key="b"
                            :class="{ 'r-print-only': clipped(i, b) }"
                        ><SegmentText :segments="bullet" /></li>
                    </ul>
                    <button
                        v-if="i === firstMain && role.bullets.length > PREVIEW"
                        type="button"
                        class="r-no-print r-accent mt-2 inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium"
                        :aria-expanded="allBullets[i] ? 'true' : 'false'"
                        @click="allBullets[i] = !allBullets[i]"
                    >
                        {{ allBullets[i] ? 'Show fewer' : `Show all (${role.bullets.length})` }}
                        <Icon name="chevron" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': allBullets[i] }" />
                    </button>
                </div>
            </div>
        </li>
    </ol>
</template>
