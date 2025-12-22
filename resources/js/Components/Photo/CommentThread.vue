<script setup>
const props = defineProps({
    comment: {
        type: Object,
        required: true
    },
    isDark: {
        type: Boolean,
        default: false
    },
    isReply: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['reply']);

// Generate avatar initials
const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Generate consistent color from name
const getAvatarColor = (name) => {
    const colors = [
        'bg-amber-500', 'bg-emerald-500', 'bg-blue-500',
        'bg-purple-500', 'bg-pink-500', 'bg-indigo-500',
        'bg-teal-500', 'bg-rose-500', 'bg-cyan-500'
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
};
</script>

<template>
    <div :class="isReply ? 'ml-10 sm:ml-14' : ''">
        <div class="flex gap-3">
            <!-- Avatar -->
            <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium shadow-sm"
                :class="[
                    getAvatarColor(comment.author_name),
                    comment.is_admin ? 'ring-2 ring-offset-2 ring-amber-400' : ''
                ]"
            >
                {{ getInitials(comment.author_name) }}
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="font-semibold text-sm"
                        :class="props.isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'"
                    >
                        {{ comment.author_name }}
                    </span>
                    <span
                        v-if="comment.is_admin"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
                        :class="props.isDark ? 'bg-amber-500/20 text-amber-400' : 'bg-amber-100 text-amber-700'"
                    >
                        Photographer
                    </span>
                    <span
                        class="text-xs"
                        :class="props.isDark ? 'text-[var(--text-muted)]' : 'text-gray-400'"
                        :title="comment.created_at_formatted"
                    >
                        {{ comment.created_at }}
                    </span>
                </div>

                <p
                    class="text-sm leading-relaxed whitespace-pre-wrap"
                    :class="props.isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'"
                >
                    {{ comment.content }}
                </p>

                <!-- Reply button -->
                <button
                    v-if="!isReply"
                    @click="$emit('reply', comment.id)"
                    class="mt-2.5 text-xs font-medium flex items-center gap-1.5 transition-colors"
                    :class="props.isDark
                        ? 'text-[var(--text-muted)] hover:text-[var(--accent)]'
                        : 'text-gray-400 hover:text-[var(--accent,#d97706)]'"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Reply
                </button>
            </div>
        </div>

        <!-- Nested replies -->
        <div v-if="comment.replies?.length > 0" class="mt-4 space-y-4">
            <CommentThread
                v-for="reply in comment.replies"
                :key="reply.id"
                :comment="reply"
                :is-dark="props.isDark"
                :is-reply="true"
            />
        </div>
    </div>
</template>
