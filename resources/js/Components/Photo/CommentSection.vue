<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
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
const errorMessage = ref('');

// Form state
const formStep = ref(1); // 1 = comment form, 2 = OTP verification
const isSubmitting = ref(false);
const verificationId = ref(null);
const expiresIn = ref(0);
const countdownTimer = ref(null);

const formData = ref({
    guest_name: '',
    guest_email: '',
    content: '',
    parent_id: null,
    website: '' // honeypot
});

const otpCode = ref('');
const otpInputs = ref(['', '', '', '', '', '']);
const formErrors = ref({});

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

onUnmounted(() => {
    if (countdownTimer.value) {
        clearInterval(countdownTimer.value);
    }
});

// Start countdown timer
const startCountdown = (seconds) => {
    expiresIn.value = seconds;
    if (countdownTimer.value) {
        clearInterval(countdownTimer.value);
    }
    countdownTimer.value = setInterval(() => {
        expiresIn.value--;
        if (expiresIn.value <= 0) {
            clearInterval(countdownTimer.value);
        }
    }, 1000);
};

// Format countdown
const formattedCountdown = computed(() => {
    const mins = Math.floor(expiresIn.value / 60);
    const secs = expiresIn.value % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
});

// Step 1: Request OTP
const requestOtp = async () => {
    formErrors.value = {};
    errorMessage.value = '';
    isSubmitting.value = true;

    try {
        const response = await axios.post(route('photos.comment.request-otp', props.photoSlug), {
            guest_name: formData.value.guest_name,
            guest_email: formData.value.guest_email,
            content: formData.value.content,
            parent_id: replyingTo.value,
            website: formData.value.website
        });

        if (response.data.success) {
            verificationId.value = response.data.verification_id;
            startCountdown(response.data.expires_in);
            formStep.value = 2;
            // Reset OTP inputs
            otpInputs.value = ['', '', '', '', '', ''];
        }
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors || {};
        } else if (error.response?.data?.error) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = 'Something went wrong. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Step 2: Verify OTP
const verifyOtp = async () => {
    const code = otpInputs.value.join('');
    if (code.length !== 6) {
        errorMessage.value = 'Please enter the complete 6-digit code.';
        return;
    }

    errorMessage.value = '';
    isSubmitting.value = true;

    try {
        const response = await axios.post(route('photos.comment.verify-otp', props.photoSlug), {
            verification_id: verificationId.value,
            otp_code: code
        });

        if (response.data.success) {
            successMessage.value = response.data.message;
            resetForm();
            setTimeout(() => successMessage.value = '', 5000);
            loadComments();
        }
    } catch (error) {
        if (error.response?.data?.error) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = 'Verification failed. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Resend OTP
const resendOtp = async () => {
    errorMessage.value = '';
    isSubmitting.value = true;

    try {
        const response = await axios.post(route('photos.comment.resend-otp', props.photoSlug), {
            verification_id: verificationId.value
        });

        if (response.data.success) {
            startCountdown(response.data.expires_in);
            otpInputs.value = ['', '', '', '', '', ''];
            successMessage.value = response.data.message;
            setTimeout(() => successMessage.value = '', 3000);
        }
    } catch (error) {
        if (error.response?.data?.error) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = 'Failed to resend code. Please try again.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Handle OTP input
const handleOtpInput = (index, event) => {
    const value = event.target.value;

    // Only allow numbers
    if (!/^\d*$/.test(value)) {
        event.target.value = otpInputs.value[index];
        return;
    }

    // Handle paste
    if (value.length > 1) {
        const digits = value.slice(0, 6).split('');
        digits.forEach((digit, i) => {
            if (i < 6) otpInputs.value[i] = digit;
        });
        // Focus last filled or next empty
        const nextIndex = Math.min(digits.length, 5);
        document.getElementById(`otp-${nextIndex}`)?.focus();
        return;
    }

    otpInputs.value[index] = value;

    // Auto-focus next input
    if (value && index < 5) {
        document.getElementById(`otp-${index + 1}`)?.focus();
    }

    // Auto-submit when all digits entered
    if (otpInputs.value.every(d => d !== '') && otpInputs.value.join('').length === 6) {
        verifyOtp();
    }
};

// Handle backspace in OTP
const handleOtpKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpInputs.value[index] && index > 0) {
        document.getElementById(`otp-${index - 1}`)?.focus();
    }
};

// Reset form
const resetForm = () => {
    formData.value = {
        guest_name: '',
        guest_email: '',
        content: '',
        parent_id: null,
        website: ''
    };
    otpInputs.value = ['', '', '', '', '', ''];
    formErrors.value = {};
    formStep.value = 1;
    verificationId.value = null;
    replyingTo.value = null;
    showForm.value = false;
    if (countdownTimer.value) {
        clearInterval(countdownTimer.value);
    }
};

const startReply = (commentId) => {
    replyingTo.value = commentId;
    showForm.value = true;
    formStep.value = 1;
};

const cancelReply = () => {
    replyingTo.value = null;
};

const goBack = () => {
    formStep.value = 1;
    errorMessage.value = '';
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

        <!-- Error Message -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="errorMessage"
                class="mb-6 p-4 rounded-lg flex items-center gap-3"
                :class="isDark ? 'bg-red-500/20 text-red-400' : 'bg-red-50 text-red-700'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ errorMessage }}
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
            <div v-if="showForm" class="mb-8">
                <!-- Step 1: Comment Form -->
                <form v-if="formStep === 1" @submit.prevent="requestOtp" class="space-y-4">
                    <!-- Honeypot (hidden) -->
                    <input type="text" name="website" v-model="formData.website" class="hidden" tabindex="-1" autocomplete="off" />

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
                                v-model="formData.guest_name"
                                required
                                placeholder="Your name"
                                class="w-full px-4 py-3 rounded-lg border transition-all duration-200 focus:ring-2 focus:ring-offset-0"
                                :class="[
                                    isDark
                                        ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                        : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                                    formErrors.guest_name ? 'border-red-500' : ''
                                ]"
                            />
                            <p v-if="formErrors.guest_name" class="mt-1.5 text-sm text-red-500">{{ formErrors.guest_name[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5"
                                :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'">
                                Email * <span class="text-xs font-normal opacity-60">(verified, not published)</span>
                            </label>
                            <input
                                type="email"
                                v-model="formData.guest_email"
                                required
                                placeholder="your@email.com"
                                class="w-full px-4 py-3 rounded-lg border transition-all duration-200 focus:ring-2 focus:ring-offset-0"
                                :class="[
                                    isDark
                                        ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                        : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                                    formErrors.guest_email ? 'border-red-500' : ''
                                ]"
                            />
                            <p v-if="formErrors.guest_email" class="mt-1.5 text-sm text-red-500">{{ formErrors.guest_email[0] }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5"
                            :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-700'">
                            Comment *
                        </label>
                        <textarea
                            v-model="formData.content"
                            rows="4"
                            required
                            placeholder="Share your thoughts about this photo..."
                            class="w-full px-4 py-3 rounded-lg border transition-all duration-200 resize-none focus:ring-2 focus:ring-offset-0"
                            :class="[
                                isDark
                                    ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] placeholder-[var(--text-muted)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20',
                                formErrors.content ? 'border-red-500' : ''
                            ]"
                        ></textarea>
                        <p v-if="formErrors.content" class="mt-1.5 text-sm text-red-500">{{ formErrors.content[0] }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-6 py-3 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 hover:scale-105"
                            :class="isDark
                                ? 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]'
                                : 'bg-[var(--accent,#d97706)] text-white hover:bg-[var(--accent-hover,#b45309)]'"
                        >
                            <span v-if="isSubmitting" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Sending Code...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Verify Email & Submit
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-6 py-3 rounded-lg font-medium transition-colors"
                            :class="isDark
                                ? 'bg-[var(--bg-hover)] text-[var(--text-secondary)] hover:bg-[var(--bg-tertiary)]'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            Cancel
                        </button>
                    </div>

                    <p class="text-xs" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                        A verification code will be sent to your email. Your comment will appear after moderation.
                    </p>
                </form>

                <!-- Step 2: OTP Verification -->
                <div v-else-if="formStep === 2" class="space-y-6">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
                            :class="isDark ? 'bg-[var(--accent)]/20' : 'bg-amber-100'">
                            <svg class="w-8 h-8" :class="isDark ? 'text-[var(--accent)]' : 'text-amber-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold mb-2"
                            :class="isDark ? 'text-[var(--text-primary)]' : 'text-gray-900'">
                            Verify Your Email
                        </h4>
                        <p class="text-sm mb-1" :class="isDark ? 'text-[var(--text-secondary)]' : 'text-gray-600'">
                            We've sent a 6-digit verification code to
                        </p>
                        <p class="font-medium" :class="isDark ? 'text-[var(--accent)]' : 'text-amber-600'">
                            {{ formData.guest_email }}
                        </p>
                    </div>

                    <!-- OTP Input -->
                    <div class="flex justify-center gap-2 sm:gap-3">
                        <input
                            v-for="(digit, index) in otpInputs"
                            :key="index"
                            :id="`otp-${index}`"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            :value="digit"
                            @input="handleOtpInput(index, $event)"
                            @keydown="handleOtpKeydown(index, $event)"
                            class="w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold rounded-lg border-2 transition-all duration-200 focus:ring-2 focus:ring-offset-0"
                            :class="isDark
                                ? 'bg-[var(--bg-primary)] border-[var(--border)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)]/20'
                                : 'bg-white border-gray-300 text-gray-900 focus:border-[var(--accent,#d97706)] focus:ring-[var(--accent,#d97706)]/20'"
                        />
                    </div>

                    <!-- Timer -->
                    <div class="text-center">
                        <p v-if="expiresIn > 0" class="text-sm" :class="isDark ? 'text-[var(--text-muted)]' : 'text-gray-500'">
                            Code expires in <span class="font-medium" :class="expiresIn <= 60 ? 'text-red-500' : ''">{{ formattedCountdown }}</span>
                        </p>
                        <p v-else class="text-sm text-red-500">
                            Code expired. Please request a new one.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button
                            @click="verifyOtp"
                            :disabled="isSubmitting || otpInputs.some(d => !d)"
                            class="w-full sm:w-auto px-8 py-3 rounded-lg font-medium transition-all duration-200 disabled:opacity-50 hover:scale-105"
                            :class="isDark
                                ? 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]'
                                : 'bg-[var(--accent,#d97706)] text-white hover:bg-[var(--accent-hover,#b45309)]'"
                        >
                            <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Verifying...
                            </span>
                            <span v-else>Verify & Submit</span>
                        </button>
                        <button
                            @click="resendOtp"
                            :disabled="isSubmitting"
                            class="text-sm font-medium transition-colors"
                            :class="isDark
                                ? 'text-[var(--text-secondary)] hover:text-[var(--accent)]'
                                : 'text-gray-600 hover:text-amber-600'"
                        >
                            Resend Code
                        </button>
                    </div>

                    <!-- Back button -->
                    <div class="text-center">
                        <button
                            @click="goBack"
                            class="text-sm font-medium flex items-center gap-1 mx-auto transition-colors"
                            :class="isDark ? 'text-[var(--text-muted)] hover:text-[var(--text-secondary)]' : 'text-gray-500 hover:text-gray-700'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to edit comment
                        </button>
                    </div>
                </div>
            </div>
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
