<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const form = useForm({
    name: '',
    description: '',
    type: 'theme',
    goal: 'conversion',
    sample_size: 1000,
    confidence_level: 95,
    variants: [
        { name: 'Control', value: 'default', weight: 50 },
        { name: 'Variant B', value: '', weight: 50 }
    ]
});

const addVariant = () => {
    const nextLetter = String.fromCharCode(65 + form.variants.length);
    form.variants.push({ name: `Variant ${nextLetter}`, value: '', weight: 50 });
};

const removeVariant = (index) => {
    if (form.variants.length > 2) {
        form.variants.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('admin.abtests.store'));
};
</script>

<template>
    <Head title="Create A/B Test" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.abtests.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create A/B Test</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Test Information</h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Test Name" />
                                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="e.g., Dark Theme Test" required />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="description" value="Description" />
                                <textarea id="description" v-model="form.description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="What are you testing?"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="type" value="Test Type" />
                                    <select id="type" v-model="form.type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="theme">Theme Variation</option>
                                        <option value="layout">Layout Variation</option>
                                        <option value="content">Content Variation</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="goal" value="Goal" />
                                    <select id="goal" v-model="form.goal" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="conversion">Conversion (Sales/Signups)</option>
                                        <option value="engagement">Engagement (Time on Site)</option>
                                        <option value="bounce">Reduce Bounce Rate</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variants -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Variants</h3>
                            <button type="button" @click="addVariant" class="text-sm text-indigo-600 hover:text-indigo-800">+ Add Variant</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(variant, index) in form.variants" :key="index" class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-medium text-gray-700">Variant {{ index + 1 }}</span>
                                    <button v-if="form.variants.length > 2" type="button" @click="removeVariant(index)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                                        <TextInput v-model="variant.name" class="w-full text-sm" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Value (CSS class/theme name)</label>
                                        <TextInput v-model="variant.value" class="w-full text-sm" placeholder="e.g., dark-theme" required />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Weight (%)</label>
                                        <TextInput type="number" v-model="variant.weight" class="w-full text-sm" min="1" max="100" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Test Settings</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="sample_size" value="Sample Size" />
                                <TextInput id="sample_size" type="number" v-model="form.sample_size" class="mt-1 block w-full" min="100" max="100000" required />
                                <p class="mt-1 text-xs text-gray-500">Minimum visitors before declaring winner</p>
                            </div>
                            <div>
                                <InputLabel for="confidence_level" value="Confidence Level (%)" />
                                <TextInput id="confidence_level" type="number" v-model="form.confidence_level" class="mt-1 block w-full" min="80" max="99" required />
                                <p class="mt-1 text-xs text-gray-500">Statistical confidence for results</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link :href="route('admin.abtests.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">Create Test</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
