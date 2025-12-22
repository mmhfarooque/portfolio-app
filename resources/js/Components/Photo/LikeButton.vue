<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    photoSlug: {
        type: String,
        required: true
    },
    initialLikesCount: {
        type: Number,
        default: 0
    }
});

const page = usePage();
const themeData = computed(() => page.props.theme || {});
const isDark = computed(() => themeData.value?.isDark ?? false);

const liked = ref(false);
const likesCount = ref(props.initialLikesCount);
const isAnimating = ref(false);
const isLoading = ref(false);

// Check initial like status
onMounted(async () => {
    try {
        const response = await axios.get(route('photos.like.check', props.photoSlug));
        liked.value = response.data.liked;
        likesCount.value = response.data.likes_count;
    } catch (error) {
        console.error('Failed to check like status:', error);
    }
});

const toggleLike = async () => {
    if (isLoading.value) return;

    isLoading.value = true;

    // Optimistic update
    const wasLiked = liked.value;
    liked.value = !liked.value;
    likesCount.value += liked.value ? 1 : -1;

    // Trigger animation
    if (liked.value) {
        isAnimating.value = true;
        setTimeout(() => isAnimating.value = false, 600);
    }

    try {
        const response = await axios.post(route('photos.like', props.photoSlug));
        liked.value = response.data.liked;
        likesCount.value = response.data.likes_count;
    } catch (error) {
        // Revert on error
        liked.value = wasLiked;
        likesCount.value += wasLiked ? 1 : -1;
        console.error('Failed to toggle like:', error);
    } finally {
        isLoading.value = false;
    }
};

const formattedCount = computed(() => {
    if (likesCount.value >= 1000) {
        return (likesCount.value / 1000).toFixed(1) + 'k';
    }
    return likesCount.value.toString();
});
</script>

<template>
    <button
        @click="toggleLike"
        :disabled="isLoading"
        class="group flex items-center gap-2 px-4 py-2.5 rounded-full transition-all duration-200"
        :class="[
            liked
                ? (isDark ? 'bg-red-500/20 text-red-400' : 'bg-red-50 text-red-500')
                : (isDark ? 'bg-[var(--bg-hover)] text-[var(--text-muted)] hover:text-red-400' : 'bg-gray-100 text-gray-500 hover:text-red-500'),
            isLoading ? 'opacity-75 cursor-not-allowed' : 'cursor-pointer hover:scale-105'
        ]"
    >
        <!-- Heart Icon -->
        <svg
            class="w-5 h-5 transition-transform duration-200"
            :class="[
                isAnimating ? 'animate-ping-heart scale-125' : '',
                liked ? 'fill-current' : 'stroke-current fill-none'
            ]"
            viewBox="0 0 24 24"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            />
        </svg>

        <!-- Count -->
        <span class="text-sm font-medium tabular-nums">
            {{ formattedCount }}
        </span>
    </button>
</template>

<style scoped>
@keyframes ping-heart {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}
.animate-ping-heart {
    animation: ping-heart 0.3s ease-in-out;
}
</style>
