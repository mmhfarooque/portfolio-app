<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    cameras: Array,
    lenses: Array,
    accessories: Array,
    lighting: Array,
    software: Array,
    previousGear: Array
});

const sections = [
    { key: 'cameras', title: 'Cameras', items: props.cameras },
    { key: 'lenses', title: 'Lenses', items: props.lenses },
    { key: 'accessories', title: 'Accessories', items: props.accessories },
    { key: 'lighting', title: 'Lighting', items: props.lighting },
    { key: 'software', title: 'Software', items: props.software },
];
</script>

<template>
    <Head title="Gear" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">My Gear</h1>
                <p class="mt-2 text-gray-600">The equipment I use for photography and video</p>
            </div>

            <!-- Current Gear Sections -->
            <div v-for="section in sections" :key="section.key" class="mb-12">
                <template v-if="section.items.length > 0">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ section.title }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link v-for="item in section.items" :key="item.id" :href="route('gear.show', item.slug)" class="group bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <div v-if="item.image" class="aspect-video bg-gray-100">
                                <img :src="`/storage/${item.image}`" :alt="item.name" class="w-full h-full object-cover" loading="lazy" />
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ item.name }}</h3>
                                <p v-if="item.manufacturer" class="text-sm text-gray-500">{{ item.manufacturer }}</p>
                                <p v-if="item.description" class="text-sm text-gray-600 mt-2 line-clamp-2">{{ item.description }}</p>
                            </div>
                        </Link>
                    </div>
                </template>
            </div>

            <!-- Previous Gear -->
            <div v-if="previousGear.length > 0" class="mt-16 pt-12 border-t border-gray-200">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Previous Gear</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div v-for="item in previousGear" :key="item.id" class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="font-medium text-gray-700">{{ item.name }}</p>
                        <p v-if="item.acquired_date" class="text-sm text-gray-500">{{ item.acquired_date }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="cameras.length === 0 && lenses.length === 0 && accessories.length === 0" class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No gear listed yet</h3>
                <p class="mt-2 text-gray-500">Check back later for equipment details</p>
            </div>
        </div>
    </PublicLayout>
</template>
