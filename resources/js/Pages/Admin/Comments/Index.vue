<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    comments: Object,
    pendingCount: Number,
    filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'pending');
const selectedIds = ref([]);
const replyingTo = ref(null);
const expandedComment = ref(null);

const replyForm = useForm({
    content: ''
});

const statuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'spam', label: 'Spam' },
];

const filterComments = () => {
    router.get(route('admin.comments.index'), {
        status: status.value,
        search: search.value || undefined,
    }, { preserveState: true });
};

const approveComment = (id) => {
    router.post(route('admin.comments.approve', id), {}, { preserveScroll: true });
};

const rejectComment = (id) => {
    router.post(route('admin.comments.reject', id), {}, { preserveScroll: true });
};

const markAsSpam = (id) => {
    router.post(route('admin.comments.spam', id), {}, { preserveScroll: true });
};

const blockEmail = (comment) => {
    if (confirm(`Are you sure you want to block "${comment.author_email}"? This will also reject all pending comments from this email.`)) {
        router.post(route('admin.comments.block-email', comment.id), {}, { preserveScroll: true });
    }
};

const deleteComment = (id) => {
    if (confirm('Are you sure you want to delete this comment?')) {
        router.delete(route('admin.comments.destroy', id), { preserveScroll: true });
    }
};

const bulkApprove = () => {
    if (selectedIds.value.length === 0) return;
    router.post(route('admin.comments.bulk-approve'), {
        ids: selectedIds.value
    }, {
        preserveScroll: true,
        onSuccess: () => selectedIds.value = []
    });
};

const startReply = (comment) => {
    replyingTo.value = comment;
    replyForm.reset();
};

const submitReply = () => {
    replyForm.post(route('admin.comments.reply', replyingTo.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyingTo.value = null;
            replyForm.reset();
        }
    });
};

const toggleAll = () => {
    if (selectedIds.value.length === props.comments.data.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.comments.data.map(c => c.id);
    }
};

const toggleExpand = (id) => {
    expandedComment.value = expandedComment.value === id ? null : id;
};

const getStatusBadgeClass = (commentStatus) => {
    switch (commentStatus) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'spam':
            return 'bg-gray-100 text-gray-600';
        default:
            return 'bg-gray-100 text-gray-600';
    }
};
</script>

<template>
    <Head title="Comments" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Comments</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage photo comments and replies
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.comments.blocked-emails')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Blocked Emails
                    </Link>
                    <div v-if="pendingCount > 0" class="flex items-center gap-2 px-4 py-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-yellow-800">{{ pendingCount }} pending review</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Status Tabs -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            @click="status = s.value; filterComments()"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="status === s.value
                                ? 'bg-gray-900 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            {{ s.label }}
                            <span v-if="s.value === 'pending' && pendingCount > 0"
                                class="ml-1.5 px-1.5 py-0.5 text-xs bg-red-500 text-white rounded-full">
                                {{ pendingCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                @keyup.enter="filterComments"
                                type="text"
                                placeholder="Search by name, email, or content..."
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="selectedIds.length > 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center justify-between">
                    <span class="text-sm font-medium text-amber-800">{{ selectedIds.length }} comment(s) selected</span>
                    <button
                        @click="bulkApprove"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Approve Selected
                    </button>
                </div>
            </Transition>

            <!-- Comments List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <!-- Table Header -->
                <div class="hidden lg:grid lg:grid-cols-12 gap-4 px-6 py-4 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <div class="col-span-1 flex items-center">
                        <input
                            type="checkbox"
                            @change="toggleAll"
                            :checked="selectedIds.length === comments.data.length && comments.data.length > 0"
                            class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                        />
                    </div>
                    <div class="col-span-3">Author</div>
                    <div class="col-span-4">Comment</div>
                    <div class="col-span-2">Photo</div>
                    <div class="col-span-2 text-right">Actions</div>
                </div>

                <!-- Comment Rows -->
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="comment in comments.data"
                        :key="comment.id"
                        class="p-4 lg:p-6 hover:bg-gray-50 transition-colors"
                    >
                        <div class="lg:grid lg:grid-cols-12 gap-4 items-start">
                            <!-- Checkbox -->
                            <div class="hidden lg:flex lg:col-span-1 items-center">
                                <input
                                    type="checkbox"
                                    v-model="selectedIds"
                                    :value="comment.id"
                                    class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                />
                            </div>

                            <!-- Author -->
                            <div class="lg:col-span-3 mb-3 lg:mb-0">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        v-model="selectedIds"
                                        :value="comment.id"
                                        class="lg:hidden rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">{{ comment.author_name }}</div>
                                        <div class="text-xs text-gray-500">{{ comment.author_email }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span :class="getStatusBadgeClass(comment.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">
                                                {{ comment.status }}
                                            </span>
                                            <span v-if="comment.is_reply" class="text-xs text-indigo-600 font-medium">Reply</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comment Content -->
                            <div class="lg:col-span-4 mb-3 lg:mb-0">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">
                                    {{ expandedComment === comment.id ? comment.full_content : comment.content }}
                                </p>
                                <button
                                    v-if="comment.full_content.length > 150"
                                    @click="toggleExpand(comment.id)"
                                    class="text-xs text-amber-600 hover:text-amber-700 mt-1"
                                >
                                    {{ expandedComment === comment.id ? 'Show less' : 'Show more' }}
                                </button>
                                <div class="text-xs text-gray-400 mt-2">{{ comment.created_at }}</div>
                            </div>

                            <!-- Photo -->
                            <div class="lg:col-span-2 mb-3 lg:mb-0">
                                <Link
                                    v-if="comment.photo"
                                    :href="route('photos.show', comment.photo.slug)"
                                    target="_blank"
                                    class="flex items-center gap-2 group"
                                >
                                    <img
                                        :src="`/storage/${comment.photo.thumbnail_path}`"
                                        :alt="comment.photo.title"
                                        class="w-12 h-12 object-cover rounded-lg ring-1 ring-gray-200 group-hover:ring-amber-300 transition-all"
                                    />
                                    <span class="text-xs text-gray-600 group-hover:text-amber-600 truncate max-w-[100px] hidden sm:block">
                                        {{ comment.photo.title }}
                                    </span>
                                </Link>
                            </div>

                            <!-- Actions -->
                            <div class="lg:col-span-2 flex items-center justify-end gap-1">
                                <button
                                    v-if="comment.status === 'pending'"
                                    @click="approveComment(comment.id)"
                                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                    title="Approve"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    v-if="comment.status === 'pending'"
                                    @click="rejectComment(comment.id)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Reject"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <button
                                    @click="startReply(comment)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                    title="Reply"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                </button>
                                <button
                                    @click="markAsSpam(comment.id)"
                                    class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Mark as spam"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                </button>
                                <button
                                    v-if="comment.author_email"
                                    @click="blockEmail(comment)"
                                    class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                    title="Block email"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteComment(comment.id)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Delete"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="comments.data.length === 0" class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <p class="text-gray-500">No comments found.</p>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination v-if="comments.data.length > 0" :links="comments.links" class="mt-6" />

            <!-- Reply Modal -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="replyingTo" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Reply to Comment</h3>
                                    <button @click="replyingTo = null" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4 mb-4 border border-gray-100">
                                    <p class="text-sm font-medium text-gray-700 mb-1">{{ replyingTo.author_name }}</p>
                                    <p class="text-sm text-gray-600">{{ replyingTo.full_content }}</p>
                                </div>
                                <form @submit.prevent="submitReply">
                                    <textarea
                                        v-model="replyForm.content"
                                        rows="4"
                                        required
                                        placeholder="Write your reply..."
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"
                                    ></textarea>
                                    <p v-if="replyForm.errors.content" class="mt-1 text-sm text-red-500">{{ replyForm.errors.content }}</p>
                                    <div class="flex justify-end gap-3 mt-4">
                                        <button
                                            type="button"
                                            @click="replyingTo = null"
                                            class="px-4 py-2.5 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium transition-colors"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="replyForm.processing"
                                            class="px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium disabled:opacity-50 transition-colors"
                                        >
                                            <span v-if="replyForm.processing" class="flex items-center gap-2">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                                Sending...
                                            </span>
                                            <span v-else>Send Reply</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>
