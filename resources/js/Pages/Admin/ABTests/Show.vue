<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    abtest: Object,
    results: Object
});

const showDeleteModal = ref(false);
const showCompleteModal = ref(false);
const isDeleting = ref(false);
const isCompleting = ref(false);
const selectedWinner = ref('');

const startTest = () => {
    router.post(route('admin.abtests.start', props.abtest.id));
};

const pauseTest = () => {
    router.post(route('admin.abtests.pause', props.abtest.id));
};

const completeTest = () => {
    isCompleting.value = true;
    router.post(route('admin.abtests.complete', props.abtest.id), {
        winner: selectedWinner.value
    }, {
        onFinish: () => {
            isCompleting.value = false;
            showCompleteModal.value = false;
        }
    });
};

const deleteTest = () => {
    isDeleting.value = true;
    router.delete(route('admin.abtests.destroy', props.abtest.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        }
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'running': return 'bg-green-100 text-green-800';
        case 'paused': return 'bg-yellow-100 text-yellow-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head :title="`A/B Test: ${abtest.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('admin.abtests.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ abtest.name }}</h2>
                    <span :class="getStatusBadgeClass(abtest.status)" class="ml-3 px-2 py-1 text-xs rounded-full capitalize">{{ abtest.status }}</span>
                </div>
                <div class="flex gap-2">
                    <button v-if="abtest.status === 'draft' || abtest.status === 'paused'" @click="startTest" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Start Test</button>
                    <button v-if="abtest.status === 'running'" @click="pauseTest" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">Pause</button>
                    <button v-if="abtest.status === 'running'" @click="showCompleteModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Complete</button>
                    <Link v-if="abtest.status !== 'running'" :href="route('admin.abtests.edit', abtest.id)">
                        <SecondaryButton>Edit</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Test Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Details</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Type</p>
                            <p class="font-medium capitalize">{{ abtest.type }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Goal</p>
                            <p class="font-medium capitalize">{{ abtest.goal }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Sample Size</p>
                            <p class="font-medium">{{ abtest.sample_size?.toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Confidence Level</p>
                            <p class="font-medium">{{ abtest.confidence_level }}%</p>
                        </div>
                    </div>
                    <p v-if="abtest.description" class="mt-4 text-gray-600">{{ abtest.description }}</p>
                </div>

                <!-- Results -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Results</h3>
                    <div class="grid gap-4">
                        <div v-for="(variant, key) in results?.variants" :key="key" class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium">{{ variant.name }}</span>
                                    <span v-if="abtest.winner === key" class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Winner</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">{{ variant.impressions?.toLocaleString() || 0 }} impressions</p>
                                    <p class="font-medium">{{ variant.conversion_rate?.toFixed(2) || 0 }}% conversion</p>
                                </div>
                            </div>
                            <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-600" :style="{ width: `${variant.conversion_rate || 0}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <DangerButton v-if="abtest.status !== 'running'" @click="showDeleteModal = true">Delete Test</DangerButton>
                </div>
            </div>
        </div>

        <!-- Complete Modal -->
        <ConfirmModal :show="showCompleteModal" title="Complete A/B Test" confirm-text="Complete" variant="info" :processing="isCompleting" @confirm="completeTest" @close="showCompleteModal = false">
            <template #message>
                <div class="space-y-4">
                    <p>Select the winning variant (optional):</p>
                    <select v-model="selectedWinner" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">No winner (inconclusive)</option>
                        <option v-for="(variant, key) in abtest.variants" :key="key" :value="key">{{ variant.name }}</option>
                    </select>
                </div>
            </template>
        </ConfirmModal>

        <!-- Delete Modal -->
        <ConfirmModal :show="showDeleteModal" title="Delete A/B Test" :message="`Are you sure you want to delete '${abtest.name}'? This action cannot be undone.`" confirm-text="Delete" variant="danger" :processing="isDeleting" @confirm="deleteTest" @close="showDeleteModal = false" />
    </AuthenticatedLayout>
</template>
