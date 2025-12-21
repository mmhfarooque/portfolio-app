<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    equipment: Object
});

const typeLabels = {
    camera: 'Camera',
    lens: 'Lens',
    accessory: 'Accessory',
    lighting: 'Lighting',
    software: 'Software'
};
</script>

<template>
    <Head :title="equipment.name" />

    <PublicLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back Link -->
            <Link href="/gear" class="text-indigo-600 hover:text-indigo-800 text-sm mb-8 inline-block">
                &larr; Back to Gear
            </Link>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Image -->
                <div>
                    <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden">
                        <img v-if="equipment.image" :src="`/storage/${equipment.image}`" :alt="equipment.name" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div>
                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full mb-2">
                        {{ typeLabels[equipment.type] || equipment.type }}
                    </span>
                    <h1 class="text-3xl font-bold text-gray-900">{{ equipment.name }}</h1>
                    <p v-if="equipment.manufacturer" class="text-lg text-gray-500 mt-1">{{ equipment.manufacturer }}</p>

                    <div class="mt-6 space-y-4">
                        <p v-if="equipment.description" class="text-gray-600">{{ equipment.description }}</p>

                        <div v-if="equipment.acquired_date" class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Acquired {{ equipment.acquired_date }}
                        </div>

                        <span v-if="!equipment.is_current" class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full">
                            No longer in use
                        </span>
                    </div>

                    <!-- Affiliate Link -->
                    <a v-if="equipment.affiliate_link" :href="equipment.affiliate_link" target="_blank" rel="noopener sponsored" class="mt-6 inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        View on Amazon
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Story -->
            <div v-if="equipment.story" class="mt-12 prose prose-lg max-w-none" v-html="equipment.story"></div>

            <!-- Specs -->
            <div v-if="equipment.specs && Object.keys(equipment.specs).length > 0" class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Specifications</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(value, key) in equipment.specs" :key="key" class="flex justify-between py-2 border-b border-gray-100">
                        <dt class="text-gray-500">{{ key }}</dt>
                        <dd class="font-medium text-gray-900">{{ value }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </PublicLayout>
</template>
