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
    description: '',
    latitude: '',
    longitude: '',
    address: '',
    city: '',
    state: '',
    country: '',
    tips: '',
    best_time: '',
    amenities: '',
    image: null,
    is_featured: false,
});

const submit = () => {
    form.post(route('admin.locations.store'));
};

const handleImageChange = (e) => {
    form.image = e.target.files[0];
};
</script>

<template>
    <Head title="Add Location" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.locations.index')" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Location</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="md:col-span-2">
                            <InputLabel for="name" value="Location Name *" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Latitude -->
                        <div>
                            <InputLabel for="latitude" value="Latitude *" />
                            <TextInput
                                id="latitude"
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.latitude" class="mt-2" />
                        </div>

                        <!-- Longitude -->
                        <div>
                            <InputLabel for="longitude" value="Longitude *" />
                            <TextInput
                                id="longitude"
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.longitude" class="mt-2" />
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Address" />
                            <TextInput
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.address" class="mt-2" />
                        </div>

                        <!-- City -->
                        <div>
                            <InputLabel for="city" value="City" />
                            <TextInput
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.city" class="mt-2" />
                        </div>

                        <!-- State -->
                        <div>
                            <InputLabel for="state" value="State/Province" />
                            <TextInput
                                id="state"
                                v-model="form.state"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.state" class="mt-2" />
                        </div>

                        <!-- Country -->
                        <div>
                            <InputLabel for="country" value="Country" />
                            <TextInput
                                id="country"
                                v-model="form.country"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.country" class="mt-2" />
                        </div>

                        <!-- Best Time -->
                        <div>
                            <InputLabel for="best_time" value="Best Time to Visit" />
                            <TextInput
                                id="best_time"
                                v-model="form.best_time"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., Golden hour, Spring"
                            />
                            <InputError :message="form.errors.best_time" class="mt-2" />
                        </div>

                        <!-- Tips -->
                        <div class="md:col-span-2">
                            <InputLabel for="tips" value="Photography Tips" />
                            <textarea
                                id="tips"
                                v-model="form.tips"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Tips for photographing this location..."
                            ></textarea>
                            <InputError :message="form.errors.tips" class="mt-2" />
                        </div>

                        <!-- Amenities -->
                        <div class="md:col-span-2">
                            <InputLabel for="amenities" value="Amenities (comma-separated)" />
                            <TextInput
                                id="amenities"
                                v-model="form.amenities"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Parking, Restrooms, Hiking trails"
                            />
                            <InputError :message="form.errors.amenities" class="mt-2" />
                        </div>

                        <!-- Cover Image -->
                        <div class="md:col-span-2">
                            <InputLabel for="image" value="Cover Image" />
                            <input
                                type="file"
                                id="image"
                                @change="handleImageChange"
                                accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <InputError :message="form.errors.image" class="mt-2" />
                        </div>

                        <!-- Featured -->
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.is_featured"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Featured location</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-4">
                        <Link :href="route('admin.locations.index')">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Add Location
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
