<script setup>
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
    cover_image: null,
    sort_order: 0
});

const handleFileChange = (e) => {
    form.cover_image = e.target.files[0];
};

const submit = () => {
    form.post(route('admin.categories.store'));
};
</script>

<template>
    <Head title="Add Category" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Category</h2>
                <Link :href="route('admin.categories.index')" class="text-gray-600 hover:text-gray-900">
                    &larr; Back to Categories
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Name" required />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            ></textarea>
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <InputLabel for="cover_image" value="Cover Image" />
                            <input
                                type="file"
                                id="cover_image"
                                accept="image/*"
                                @change="handleFileChange"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <InputError :message="form.errors.cover_image" />
                        </div>

                        <!-- Sort Order -->
                        <div>
                            <InputLabel for="sort_order" value="Sort Order" />
                            <TextInput
                                id="sort_order"
                                v-model="form.sort_order"
                                type="number"
                                class="mt-1 block w-32"
                            />
                            <InputError :message="form.errors.sort_order" />
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <Link :href="route('admin.categories.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Create Category
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
