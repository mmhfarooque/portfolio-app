<script setup>
import { computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    description: String,
    image: String,
    imageAlt: String,
    type: {
        type: String,
        default: 'website' // website, article, photo
    },
    url: String,
    publishedTime: String,
    modifiedTime: String,
    author: String,
    // For photos
    photo: Object,
    // For articles/blog posts
    article: Object,
    // Breadcrumbs
    breadcrumbs: Array
});

const page = usePage();
const appName = computed(() => page.props.appName || 'Mahmud Farooque');
const baseUrl = 'https://mfaruk.com';

// Computed values
const pageTitle = computed(() => props.title || appName.value);
const pageDescription = computed(() => props.description || 'Photography portfolio by Mahmud Farooque. Capturing moments from Bangladesh, Kashmir, Thailand, and beyond.');
const pageUrl = computed(() => props.url || (typeof window !== 'undefined' ? window.location.href : baseUrl));
const pageImage = computed(() => {
    if (props.image) {
        return props.image.startsWith('http') ? props.image : `${baseUrl}/storage/${props.image}`;
    }
    return `${baseUrl}/images/og-default.jpg`;
});

// JSON-LD Structured Data
const jsonLd = computed(() => {
    const schemas = [];

    // Organization schema (always include)
    schemas.push({
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": appName.value,
        "url": baseUrl,
        "logo": `${baseUrl}/favicon-32x32.png`,
        "sameAs": [
            "https://500px.com/p/mfaruk"
        ]
    });

    // Photo/ImageObject schema
    if (props.photo) {
        schemas.push({
            "@context": "https://schema.org",
            "@type": "ImageObject",
            "name": props.photo.title,
            "description": props.photo.description || props.photo.meta_description,
            "contentUrl": pageImage.value,
            "thumbnailUrl": props.photo.thumbnail_path ? `${baseUrl}/storage/${props.photo.thumbnail_path}` : pageImage.value,
            "author": {
                "@type": "Person",
                "name": appName.value
            },
            "creator": {
                "@type": "Person",
                "name": appName.value
            },
            "copyrightHolder": {
                "@type": "Person",
                "name": appName.value
            },
            "copyrightYear": props.photo.captured_at ? new Date(props.photo.captured_at).getFullYear() : new Date().getFullYear(),
            "datePublished": props.photo.created_at,
            "dateModified": props.photo.updated_at,
            ...(props.photo.location_name && {
                "contentLocation": {
                    "@type": "Place",
                    "name": props.photo.location_name,
                    ...(props.photo.latitude && props.photo.longitude && {
                        "geo": {
                            "@type": "GeoCoordinates",
                            "latitude": props.photo.latitude,
                            "longitude": props.photo.longitude
                        }
                    })
                }
            }),
            ...(props.photo.formatted_exif && {
                "exifData": [
                    props.photo.formatted_exif.camera && { "@type": "PropertyValue", "name": "Camera", "value": props.photo.formatted_exif.camera },
                    props.photo.formatted_exif.lens && { "@type": "PropertyValue", "name": "Lens", "value": props.photo.formatted_exif.lens },
                    props.photo.formatted_exif.focal_length && { "@type": "PropertyValue", "name": "Focal Length", "value": props.photo.formatted_exif.focal_length },
                    props.photo.formatted_exif.aperture && { "@type": "PropertyValue", "name": "Aperture", "value": props.photo.formatted_exif.aperture },
                    props.photo.formatted_exif.shutter && { "@type": "PropertyValue", "name": "Shutter Speed", "value": props.photo.formatted_exif.shutter },
                    props.photo.formatted_exif.iso && { "@type": "PropertyValue", "name": "ISO", "value": props.photo.formatted_exif.iso }
                ].filter(Boolean)
            })
        });
    }

    // Article schema for blog posts
    if (props.article) {
        schemas.push({
            "@context": "https://schema.org",
            "@type": "Article",
            "headline": props.article.title,
            "description": props.article.excerpt || props.description,
            "image": pageImage.value,
            "author": {
                "@type": "Person",
                "name": appName.value
            },
            "publisher": {
                "@type": "Organization",
                "name": appName.value,
                "logo": {
                    "@type": "ImageObject",
                    "url": `${baseUrl}/favicon-32x32.png`
                }
            },
            "datePublished": props.article.published_at || props.article.created_at,
            "dateModified": props.article.updated_at,
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": pageUrl.value
            }
        });
    }

    // Breadcrumb schema
    if (props.breadcrumbs && props.breadcrumbs.length > 0) {
        schemas.push({
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": props.breadcrumbs.map((crumb, index) => ({
                "@type": "ListItem",
                "position": index + 1,
                "name": crumb.name,
                "item": crumb.url ? (crumb.url.startsWith('http') ? crumb.url : `${baseUrl}${crumb.url}`) : undefined
            }))
        });
    }

    return schemas;
});

const jsonLdString = computed(() => JSON.stringify(jsonLd.value));
</script>

<template>
    <Head>
        <!-- Primary Meta Tags -->
        <title>{{ pageTitle }}</title>
        <meta name="title" :content="pageTitle" />
        <meta name="description" :content="pageDescription" />
        <meta name="author" :content="appName" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="pageUrl" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" :content="type" />
        <meta property="og:url" :content="pageUrl" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="pageDescription" />
        <meta property="og:image" :content="pageImage" />
        <meta property="og:image:alt" :content="imageAlt || pageTitle" />
        <meta property="og:site_name" :content="appName" />
        <meta property="og:locale" content="en_US" />

        <!-- Article specific OG tags -->
        <meta v-if="publishedTime" property="article:published_time" :content="publishedTime" />
        <meta v-if="modifiedTime" property="article:modified_time" :content="modifiedTime" />
        <meta v-if="author" property="article:author" :content="author" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="pageUrl" />
        <meta name="twitter:title" :content="pageTitle" />
        <meta name="twitter:description" :content="pageDescription" />
        <meta name="twitter:image" :content="pageImage" />
        <meta name="twitter:image:alt" :content="imageAlt || pageTitle" />

        <!-- JSON-LD Structured Data -->
        <component :is="'script'" type="application/ld+json" v-html="jsonLdString" />
    </Head>
</template>
