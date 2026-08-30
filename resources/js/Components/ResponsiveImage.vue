<script setup>
import { computed, ref } from 'vue';

/**
 * The one element that renders a photo.
 *
 * It consumes the payload built by App\Support\Images\VariantSet, so the
 * format ladder, the width ladder and the URL shape are decided in PHP and
 * this component never has to know about storage paths or file extensions.
 */
const props = defineProps({
    // VariantSet::toArray() — { alt, width, height, sizes, src, srcset, sources, placeholder }
    image: { type: Object, default: null },
    sizes: { type: String, default: null },
    alt: { type: String, default: null },
    loading: { type: String, default: 'lazy' },
    fetchpriority: { type: String, default: 'auto' },
    decoding: { type: String, default: 'async' },
    imgClass: { type: [String, Array, Object], default: '' },
    pictureClass: { type: [String, Array, Object], default: '' },
});

const loaded = ref(false);

const resolvedSizes = computed(() => props.sizes || props.image?.sizes || '100vw');
const resolvedAlt = computed(() => props.alt ?? props.image?.alt ?? '');

// A flat colour behind the frame keeps the layout from flashing white while
// the bytes arrive. It is painted out the moment the image decodes.
const placeholderStyle = computed(() => {
    if (loaded.value || !props.image?.placeholder) {
        return null;
    }

    return { backgroundColor: props.image.placeholder };
});
</script>

<template>
    <picture v-if="image" :class="pictureClass">
        <source
            v-for="source in image.sources"
            :key="source.type"
            :type="source.type"
            :srcset="source.srcset"
            :sizes="resolvedSizes"
        />
        <img
            :src="image.src"
            :srcset="image.srcset"
            :sizes="resolvedSizes"
            :alt="resolvedAlt"
            :width="image.width"
            :height="image.height"
            :loading="loading"
            :decoding="decoding"
            :fetchpriority="fetchpriority"
            :class="imgClass"
            :style="placeholderStyle"
            @load="loaded = true"
        />
    </picture>
</template>
