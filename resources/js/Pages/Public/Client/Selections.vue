<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    selections: Array,
    groupedSelections: Array,
    selectionCount: Number
});

const showSendForm = ref(false);

const form = useForm({
    name: '',
    email: '',
    message: ''
});

const clearSelections = async () => {
    if (!confirm('Are you sure you want to clear all selections?')) return;

    try {
        await fetch(route('client.clear'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        router.reload();
    } catch (error) {
        console.error('Failed to clear selections');
    }
};

const sendToPhotographer = () => {
    form.post(route('client.send'), {
        onSuccess: () => {
            showSendForm.value = false;
            form.reset();
        }
    });
};

const exportSelections = (format) => {
    window.location.href = route('client.export') + '?format=' + format;
};
</script>

<template>
    <Head title="My Selections" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Selections</h1>
                    <p class="mt-2 text-gray-600">{{ selectionCount }} photos selected</p>
                </div>
                <div v-if="selectionCount > 0" class="flex gap-3">
                    <button @click="showSendForm = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Send to Photographer
                    </button>
                    <button @click="exportSelections('csv')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Export CSV
                    </button>
                    <button @click="clearSelections" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Clear All
                    </button>
                </div>
            </div>

            <!-- Grouped Selections -->
            <div v-if="groupedSelections.length > 0" class="space-y-8">
                <div v-for="(group, index) in groupedSelections" :key="index">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        {{ group.gallery?.name || 'Ungrouped Photos' }}
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="item in group.photos" :key="item.id" class="group relative">
                            <Link :href="route('photos.show', item.photo.slug)">
                                <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                                    <img :src="`/storage/${item.photo.thumbnail_path}`" :alt="item.photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                                </div>
                                <p class="mt-2 text-sm text-gray-700 truncate">{{ item.photo.title }}</p>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No photos selected</h3>
                <p class="mt-2 text-gray-500">Browse the gallery and select photos you'd like to save</p>
                <Link :href="route('gallery.index')" class="mt-6 inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Browse Gallery
                </Link>
            </div>
        </div>

        <!-- Send Form Modal -->
        <div v-if="showSendForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Send to Photographer</h3>
                <form @submit.prevent="sendToPhotographer" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                        <input type="text" v-model="form.name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" v-model="form.email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message (optional)</label>
                        <textarea v-model="form.message" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="showSendForm = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg disabled:opacity-50">
                            {{ form.processing ? 'Sending...' : 'Send' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
