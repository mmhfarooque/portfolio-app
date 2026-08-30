<script setup>
import { computed } from 'vue';
import ResponsiveImage from '@/Components/ResponsiveImage.vue';

const props = defineProps({
    photos: {
        type: Array,
        default: () => []
    },
    isPhotographyPost: {
        type: Boolean,
        default: false
    }
});

const heading = computed(() =>
    props.isPhotographyPost ? 'More frames from my camera' : "When I'm not coding"
);

const intro = computed(() =>
    props.isPhotographyPost
        ? 'A few more photographs from my galleries:'
        : "When I'm not coding or deep in development work, I'm usually out with my Fujifilm. A few frames I'm proud of:"
);
</script>

<template>
    <section v-if="photos.length > 0" class="bg-theme-bg-secondary py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-theme-text-primary mb-2">
                {{ heading }} <span aria-hidden="true">📷</span>
            </h2>
            <p class="text-theme-text-secondary mb-8">{{ intro }}</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Link
                    v-for="photo in photos"
                    :key="photo.id"
                    :href="route('photos.show', photo.slug)"
                    class="bg-theme-bg-card rounded-lg shadow-sm overflow-hidden group border border-theme-border"
                >
                    <div class="aspect-video bg-theme-bg-tertiary overflow-hidden">
                        <ResponsiveImage
                            :image="photo.image"
                            :alt="photo.title"
                            sizes="(max-width: 768px) 100vw, (max-width: 1280px) 33vw, 430px"
                            picture-class="block w-full h-full"
                            img-class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-theme-text-primary group-hover:text-theme-accent transition-colors">
                            {{ photo.title }}
                        </h3>
                        <p v-if="photo.location_name" class="mt-1 text-sm text-theme-text-secondary">
                            {{ photo.location_name }}
                        </p>
                    </div>
                </Link>
            </div>
            <div class="mt-8">
                <Link :href="route('photos.index')" class="text-theme-accent hover:text-theme-accent-hover font-medium">
                    Browse all photos →
                </Link>
            </div>
        </div>
    </section>
</template>
