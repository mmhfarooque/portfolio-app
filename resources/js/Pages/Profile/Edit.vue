<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    user: Object,
    mustVerifyEmail: Boolean,
    status: String
});

// Profile Information Form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: ''
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset()
    });
};

// Delete Account
const showDeleteModal = ref(false);
const deleteForm = useForm({
    password: ''
});

const deleteAccount = () => {
    deleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => showDeleteModal.value = false,
        onError: () => deleteForm.reset()
    });
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Profile Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                        <p class="mt-1 text-sm text-gray-600">Update your account's profile information and email address.</p>

                        <form @submit.prevent="updateProfile" class="mt-6 space-y-6 max-w-xl">
                            <div>
                                <InputLabel for="name" value="Name" />
                                <TextInput
                                    id="name"
                                    v-model="profileForm.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <InputError :message="profileForm.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="profileForm.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="username"
                                />
                                <InputError :message="profileForm.errors.email" />

                                <div v-if="mustVerifyEmail && !user.email_verified_at" class="mt-2">
                                    <p class="text-sm text-gray-800">
                                        Your email address is unverified.
                                        <Link
                                            :href="route('verification.send')"
                                            method="post"
                                            as="button"
                                            class="underline text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            Click here to re-send the verification email.
                                        </Link>
                                    </p>
                                    <p v-if="status === 'verification-link-sent'" class="mt-2 font-medium text-sm text-green-600">
                                        A new verification link has been sent to your email address.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="profileForm.processing">Save</PrimaryButton>
                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="profileForm.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Update Password -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Update Password</h3>
                        <p class="mt-1 text-sm text-gray-600">Ensure your account is using a long, random password to stay secure.</p>

                        <form @submit.prevent="updatePassword" class="mt-6 space-y-6 max-w-xl">
                            <div>
                                <InputLabel for="current_password" value="Current Password" />
                                <TextInput
                                    id="current_password"
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="current-password"
                                />
                                <InputError :message="passwordForm.errors.current_password" />
                            </div>

                            <div>
                                <InputLabel for="password" value="New Password" />
                                <TextInput
                                    id="password"
                                    v-model="passwordForm.password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                />
                                <InputError :message="passwordForm.errors.password" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password" />
                                <TextInput
                                    id="password_confirmation"
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    autocomplete="new-password"
                                />
                                <InputError :message="passwordForm.errors.password_confirmation" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="passwordForm.processing">Save</PrimaryButton>
                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="passwordForm.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Once your account is deleted, all of its resources and data will be permanently deleted.
                            Before deleting your account, please download any data or information that you wish to retain.
                        </p>

                        <div class="mt-6">
                            <DangerButton @click="showDeleteModal = true">Delete Account</DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Account"
            message="Are you sure you want to delete your account? This action cannot be undone."
            confirm-text="Delete Account"
            variant="danger"
            :processing="deleteForm.processing"
            @confirm="deleteAccount"
            @close="showDeleteModal = false"
        >
            <template #content>
                <div class="mt-4">
                    <InputLabel for="delete_password" value="Password" class="sr-only" />
                    <TextInput
                        id="delete_password"
                        v-model="deleteForm.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Enter your password to confirm"
                    />
                    <InputError :message="deleteForm.errors.password" class="mt-2" />
                </div>
            </template>
        </ConfirmModal>
    </AuthenticatedLayout>
</template>
