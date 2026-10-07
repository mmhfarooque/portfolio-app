<script setup>
import { useForm } from '@inertiajs/vue3';
import ResumeLayout from '@/Layouts/ResumeLayout.vue';

const props = defineProps({
    summary: { type: Object, required: true }, // { name, headline, email }
    hint: { type: String, default: null },
});

const form = useForm({ password: '' });

const submit = () => {
    form.post('/resume/unlock', {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="`Resume — ${summary.name}`">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>

    <ResumeLayout>
        <main class="r-hero flex min-h-[calc(100vh-7.5rem)] items-center justify-center px-4 py-16">
            <div class="r-card w-full max-w-md rounded-xl border border-theme-border bg-theme-bg-card p-8 shadow-lg">
                <div class="r-accent-bg mb-6 h-1 w-12 rounded-full" aria-hidden="true"></div>

                <h1 class="r-serif text-3xl font-semibold tracking-tight">{{ summary.name }}</h1>
                <p v-if="summary.headline" class="mt-2 text-sm leading-relaxed text-theme-text-secondary">
                    {{ summary.headline }}
                </p>

                <form class="mt-8" novalidate @submit.prevent="submit">
                    <label for="resume-password" class="block text-sm font-medium">Password</label>
                    <input
                        id="resume-password"
                        v-model="form.password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        :aria-invalid="form.errors.password ? 'true' : 'false'"
                        :aria-describedby="form.errors.password ? 'resume-password-error' : (hint ? 'resume-password-hint' : undefined)"
                        class="mt-2 block min-h-[44px] w-full rounded-md border-theme-border bg-theme-bg-input text-theme-text-primary shadow-sm focus:border-[color:var(--r-accent)] focus:ring-[color:var(--r-accent)]"
                    />

                    <p v-if="hint" id="resume-password-hint" class="mt-2 text-sm text-theme-text-secondary">
                        Password: <span class="font-mono">{{ hint }}</span>
                    </p>

                    <p
                        v-if="form.errors.password"
                        id="resume-password-error"
                        class="mt-2 text-sm text-theme-error"
                        role="alert"
                    >{{ form.errors.password }}</p>

                    <button
                        type="submit"
                        class="r-accent-bg mt-6 min-h-[44px] w-full rounded-md px-4 py-2.5 text-sm font-semibold transition-opacity hover:opacity-90 disabled:opacity-60"
                        :disabled="form.processing"
                    >View resume</button>
                </form>

                <p class="mt-8 text-sm text-theme-text-secondary">Detailed resume, shared privately.</p>
            </div>
        </main>
    </ResumeLayout>
</template>
