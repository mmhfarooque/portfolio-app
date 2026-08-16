<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <SeoHead
        title="Image Licensing - Mahmud Farooque Photography"
        description="Licensing terms for photographs on mfaruk.com. All images are copyright Mahmud Farooque. Prints and usage licences are available on request."
        type="website"
        url="https://mfaruk.com/image-license"
        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Image Licensing' }
        ]"
    />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h1 class="text-3xl font-bold text-theme-accent mb-8">Image Licensing</h1>

            <div class="prose prose-lg max-w-none text-theme-text-secondary space-y-6">
                <p>
                    Every photograph on this site is my own work. All images are
                    &copy; Mahmud Farooque, all rights reserved, unless a photo page
                    states otherwise.
                </p>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">What you can do freely</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li>View and enjoy the photographs here on mfaruk.com.</li>
                    <li>Share a link to any photo page on social media or elsewhere.</li>
                </ul>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">What needs a licence</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Reproducing, downloading, or republishing an image anywhere &mdash; websites, print, video, or social media posts that embed the file itself.</li>
                    <li>Any commercial or editorial use.</li>
                    <li>Use in machine learning or AI training datasets.</li>
                </ul>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">Prints and licences</h2>
                <p>
                    Fine-art prints can be ordered from the print option on each photo page.
                    For digital licences, editorial use, or anything not covered above,
                    <Link :href="route('contact')" class="text-theme-accent hover:underline">get in touch</Link>
                    with the photo link and your intended use &mdash; I reply to every genuine enquiry.
                </p>

                <h2 class="text-xl font-semibold text-theme-text-primary mt-8 mb-3">Credit line</h2>
                <p>
                    Licensed images should be credited as:
                    <span class="font-mono text-sm bg-theme-bg-secondary px-2 py-1 rounded">Photo &copy; Mahmud Farooque &mdash; mfaruk.com</span>
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
