<script setup>
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    gallery: Object,
    token: String
});

const form = useForm({
    password: ''
});

const submit = () => {
    form.post(route('client-gallery.password', props.token));
};
</script>

<template>
    <Head :title="`${gallery.name} - Password Required`" />

    <PublicLayout>
        <div class="min-h-[60vh] flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <h1 class="text-2xl font-bold text-gray-900 mt-4">{{ gallery.name }}</h1>
                <p class="text-gray-600 mt-2">This gallery is password protected.</p>

                <form @submit.prevent="submit" class="mt-6">
                    <input type="password" v-model="form.password" placeholder="Enter password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                    <button type="submit" :disabled="form.processing" class="mt-4 w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition">
                        {{ form.processing ? 'Verifying...' : 'Enter Gallery' }}
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
