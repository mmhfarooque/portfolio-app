<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import CommentThread from './CommentThread.vue';

const props = defineProps({
    photoSlug: {
        type: String,
        required: true
    },
    initialCommentsCount: {
        type: Number,
        default: 0
    }
});

const page = usePage();
const themeData = computed(() => page.props.theme || {});
const isDark = computed(() => themeData.value?.isDark ?? false);

const comments = ref([]);
const totalComments = ref(props.initialCommentsCount);
const isLoading = ref(true);
const showForm = ref(false);
const replyingTo = ref(null);
const successMessage = ref('');

const form = useForm({
    guest_name: '',
    guest_email: '',
    content: '',
    parent_id: null,
    website: '' // honeypot
});

// Load comments
const loadComments = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('photos.comments', props.photoSlug));
        comments.value = response.data.comments;
        totalComments.value = response.data.total;
    } catch (error) {
        console.error('Failed to load comments:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(loadComments);

const submitComment = () => {
    form.parent_id = replyingTo.value;

    form.post(route('photos.comment', props.photoSlug), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('content');
            replyingTo.value = null;
            showForm.value = false;
            successMessage.value = 'Thank you! Your comment will appear after moderation.';
            setTimeout(() => successMessage.value = '', 5000);
            loadComments();
        }
    });
};

const startReply = (commentId) => {
    replyingTo.value = commentId;
    showForm.value = true;
};

const cancelReply = () => {
    replyingTo.value = null;
    form.reset('content', 'parent_id');
};

const replyingToComment = computed(() => {
    if (!replyingTo.value) return null;
    return comments.value.find(c => c.id === replyingTo.value);
});
</script>

<template>
    <div
        class="rounded-2xl p-6 sm:p-8 transition-colors"
        :class="isDark
            ? 'bg-[var(--bg-secondary)] border border-[var(--border)]'
            : 'bg-[var(--bg-card,#ffffff)] border border-[var(--border,#e7e5e4)] shadow-sm'"
    >
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h3
                class="text-lg font-semibold flex items-center gap-2"
                :class="isDark ? 'text-[var(--text-primary)]' : 'text-[var(--text-primary,#1c1917)]'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                Comments
                <span
                    v-if="totalComments > 0"
                    class="text-sm font-normal px-2 py-0.5 rounded-full"
                    :class="isDark ? 'bg-[var(--bg-hover)] text-[var(--text-muted)]' : 'bg-gray-100 text-gray-600'"
                >
                    {{ totalComments }}
                </span>
            </h3>

            <button
                v-if="!showForm"
                @click="showForm = true"
                class="text-sm font-medium px-4 py-2.5 rounded-lg transition-all duration-200 hover:scale-105"
                :class="isDark
                    ? 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]'
                    : 'bg-[var(--accent,#d97706)] text-white hover:bg-[var(--accent-hover,#b45309)]'"
            >
                Leave a Comment
            </button>
        </div>

        <!-- Success Message -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="successMessage"
                class="mb-6 p-4 rounded-lg flex items-center gap-3"
                :class="isDark ? 'bg-green-500/20 text-green-400' : 'bg-green-50 text-green-700'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ successMessage }}
            </div>
        </Transition>

        <!-- Comment Form -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <form v-if="showForm" @submit.prevent="submitComment" class="mb-8 space-y-4">
                <!-- Honeypot (hidden) -->
                <input type="text" name="website" v-model="form.website" class="hidden" tabindex="-1" autocomplete="off" />

                <!-- Reply indicator -->
                <div v-if="replyingToComment"
                    class="text-sm px-4 py-3 rounded-lg flex items-center justify-between gap-2"
                    :class="isDark ? 'bg-[var(--bg-hover)] text-[var(--text-muted)]' : 'bg-gray-100 text-gray-600'">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Replying to <strong>{{ replyingToComment.author_name }}</strong>
                    </span>
                    <button type="button" @click="cancelReply" class="text-xs font-medium underline hover:no-underline">
                        Cancel
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5"
                            :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'">
                            Name *
                        </label>
                        <input
                            type="text"
                            v-model="form.guest_name"
                            required
                            placeholder="Your name"
                            class="w-full px-4 py-3 rounded-lg border transition-all duration-200 focus:ring-2 focus:ring-offset-0"
                            :class="[
                                isDark
                                    ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                                form.errors.guest_name ? 'border-red-500' : ''
                            ]"
                        />
                        <p v-if="form.errors.guest_name" class="mt-1.5 text-sm text-red-500">{{ form.errors.guest_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5"
                            :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'">
                            Email * <span class="text-xs font-normal opacity-60">(not published)</span>
                        </label>
                        <input
                            type="email"
                            v-model="form.guest_email"
                            required
                            placeholder="your@email.com"
                            class="w-full px-4 py-3 rounded-lg border transition-all duration-200 focus:ring-2 focus:ring-offset-0"
                            :class="[
                                isDark
                                    ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                                form.errors.guest_email ? 'border-red-500' : ''
                            ]"
                        />
                        <p v-if="form.errors.guest_email" class="mt-1.5 text-sm text-red-500">{{ form.errors.guest_email }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1.5"
                        :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'">
                        Comment *
                    </label>
                    <textarea
                        v-model="form.content"
                        rows="4"
                        required
                        placeholder="Share your thoughts about this photo..."
                        class="w-full px-4 py-3 rounded-lg border transition-all duration-200 resize-none focus:ring-2 focus:ring-offset-0"
                        :class="[
                            isDark
                                ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                            form.errors.content ? 'border-red-500' : ''
                        ]"
                    ></textarea>
                    <p v-if="form.errors.content" class="mt-1.5 text-sm text-red-500">{{ form.errors.content }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 hover:scale-105"
                        :class="isDark
                            ? 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]'
                            : 'bg-[var(--accent,#d97706)] text-white hover:bg-[var(--accent-hover,#b45309)]'"
                    >
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Submitting...
                        </span>
                        <span v-else>Submit Comment</span>
                    </button>
                    <button
                        type="button"
                        @click="showForm = false; cancelReply();"
                        class="px-6 py-3 rounded-lg font-medium transition-colors"
                        :class="isDark
                            ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--bg-tertiary)]'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    >
                        Cancel
                    </button>
                </div>

                <p class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                    Your comment will appear after moderation. We typically review comments within 24 hours.
                </p>
            </form>
        </Transition>

        <!-- Comments List -->
        <div v-if="isLoading" class="py-12 text-center">
            <div class="inline-flex items-center gap-2" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Loading comments...
            </div>
        </div>

        <div v-else-if="comments.length === 0" class="py-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-4" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-sm" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                No comments yet. Be the first to share your thoughts!
            </p>
        </div>

        <div v-else class="space-y-6">
            <CommentThread
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                :is-dark="isDark"
                @reply="startReply"
            />
        </div>
    </div>
</template>
