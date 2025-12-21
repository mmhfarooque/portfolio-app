<script setup>
import { ref, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    photo: Object,
    locales: Object,
    translations: Object,
    fields: Array
});

const activeTab = ref(Object.keys(props.locales).filter(l => l !== 'en')[0] || 'en');

const form = useForm({
    translations: props.translations || {}
});

const submit = () => {
    form.post(route('admin.translations.photo.update', props.photo.id));
};

const getFieldLabel = (field) => {
    const labels = {
        title: 'Title',
        description: 'Description',
        meta_description: 'Meta Description'
    };
    return labels[field] || field;
};

const isTextarea = (field) => {
    return ['description', 'content', 'excerpt'].includes(field);
};
</script>

<template>
    <Head :title="`Translate: ${photo.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.translations.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Translate Photo: {{ photo.title }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Original Content -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Original Content (English)</h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel value="Title" />
                                <p class="mt-1 text-gray-900">{{ photo.title }}</p>
                            </div>
                            <div v-if="photo.description">
                                <InputLabel value="Description" />
                                <p class="mt-1 text-gray-900">{{ photo.description }}</p>
                            </div>
                            <div v-if="photo.meta_description">
                                <InputLabel value="Meta Description" />
                                <p class="mt-1 text-gray-900">{{ photo.meta_description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Language Tabs -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="border-b border-gray-200">
                            <nav class="flex">
                                <button v-for="(name, code) in locales" :key="code" type="button" v-show="code !== 'en'" @click="activeTab = code" :class="activeTab === code ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap">
                                    {{ name }}
                                </button>
                            </nav>
                        </div>
                        <div class="p-6">
                            <div v-for="(name, code) in locales" :key="code" v-show="activeTab === code && code !== 'en'">
                                <div class="space-y-4">
                                    <div v-for="field in fields" :key="field">
                                        <InputLabel :value="getFieldLabel(field)" />
                                        <textarea v-if="isTextarea(field)" v-model="form.translations[code][field]" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                        <TextInput v-else v-model="form.translations[code][field]" class="mt-1 block w-full" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link :href="route('admin.translations.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">Save Translations</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
