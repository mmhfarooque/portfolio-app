<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    type: 'camera',
    brand: '',
    model: '',
    description: '',
    specifications: '',
    affiliate_link: '',
    image: null,
    is_active: true,
});

const submit = () => {
    form.post(route('admin.equipment.store'));
};

const handleImageChange = (e) => {
    form.image = e.target.files[0];
};
</script>

<template>
    <Head title="Add Equipment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.equipment.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Equipment</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <InputLabel for="name" value="Equipment Name *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Type -->
                        <div>
                            <InputLabel for="type" value="Type *" />
                            <select
                                id="type"
                                v-model="form.type"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="camera">Camera</option>
                                <option value="lens">Lens</option>
                                <option value="accessory">Accessory</option>
                                <option value="lighting">Lighting</option>
                                <option value="software">Software</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <!-- Brand -->
                        <div>
                            <InputLabel for="brand" value="Brand" />
                            <TextInput
                                id="brand"
                                v-model="form.brand"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.brand" class="mt-2" />
                        </div>

                        <!-- Model -->
                        <div class="md:col-span-2">
                            <InputLabel for="model" value="Model" />
                            <TextInput
                                id="model"
                                v-model="form.model"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.model" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Specifications -->
                        <div class="md:col-span-2">
                            <InputLabel for="specifications" value="Specifications" />
                            <textarea
                                id="specifications"
                                v-model="form.specifications"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Technical specifications..."
                            ></textarea>
                            <InputError :message="form.errors.specifications" class="mt-2" />
                        </div>

                        <!-- Affiliate Link -->
                        <div class="md:col-span-2">
                            <InputLabel for="affiliate_link" value="Affiliate Link" />
                            <TextInput
                                id="affiliate_link"
                                v-model="form.affiliate_link"
                                type="url"
                                class="mt-1 block w-full"
                                placeholder="https://..."
                            />
                            <InputError :message="form.errors.affiliate_link" class="mt-2" />
                        </div>

                        <!-- Image -->
                        <div class="md:col-span-2">
                            <InputLabel for="image" value="Image" />
                            <input
                                type="file"
                                id="image"
                                @change="handleImageChange"
                                accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <InputError :message="form.errors.image" class="mt-2" />
                        </div>

                        <!-- Active -->
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Active (visible on website)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <Link :href="route('admin.equipment.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Add Equipment
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
