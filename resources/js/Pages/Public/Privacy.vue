<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <SeoHead
        title="Privacy Policy - Mahmud Farooque Photography"
        description="What mfaruk.com collects, why, and what happens to it. Short version: almost nothing, and none of it is sold or shared."
        type="website"
        url="https://mfaruk.com/privacy"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Privacy Policy' }
        ]"
    />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-3xl font-bold text-theme-accent mb-8">Privacy Policy</h1>

            <div class="prose prose-lg max-w-none text-theme-text-secondary space-y-6">
                <p>
                    mfaruk.com is the personal photography portfolio and blog of Mahmud Farooque.
                    This site exists to show photographs and share articles &mdash; not to harvest
                    data. Here is exactly what it collects and why.
                </p>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">What is collected</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Contact form:</strong> your name, email address and message &mdash; used only to reply to you.</li>
                    <li><strong>Comments:</strong> your email address, used once to verify you are human via a one-time code, plus the comment itself.</li>
                    <li><strong>Print &amp; licensing enquiries:</strong> the details you provide so the order or enquiry can be handled.</li>
                    <li><strong>Server logs:</strong> standard web-server access logs (IP address, page requested, browser identifier), kept for security and to understand what gets read.</li>
                    <li><strong>Analytics:</strong> aggregate visit statistics via Google Analytics. No advertising identifiers, no cross-site tracking pixels.</li>
                </ul>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">What is never done with it</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li>No selling or renting of any personal data, to anyone, ever.</li>
                    <li>No advertising networks and no third-party marketing.</li>
                    <li>No sharing with anyone except the service providers that make the site run (hosting, email delivery, analytics), who process it only on this site's behalf.</li>
                </ul>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">Cookies</h2>
                <p>
                    The site uses a session cookie to make features like photo likes work, and a
                    preference cookie for the light/dark theme. Google Analytics sets its own
                    cookies for aggregate statistics. That is the full list.
                </p>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">Your rights</h2>
                <p>
                    Want a copy of what this site holds about you, or want it deleted?
                    <Link :href="route('contact')" class="text-theme-accent hover:underline">Get in touch</Link>
                    and it will be handled promptly &mdash; there is one person behind this site and
                    he answers his own email.
                </p>

                <p class="text-sm text-theme-text-muted">Last updated: 16 August 2026.</p>
            </div>
        </div>
    </PublicLayout>
</template>
