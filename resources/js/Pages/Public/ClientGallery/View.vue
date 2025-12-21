<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    gallery: Object,
    photos: Array,
    token: String,
    selections: Array
});

const selectedPhotos = ref([...props.selections]);
const showSubmitModal = ref(false);
const lightboxPhoto = ref(null);

const form = useForm({
    client_name: '',
    client_email: '',
    notes: ''
});

const isSelected = (photoId) => selectedPhotos.value.includes(photoId);

const toggleSelection = async (photoId) => {
    if (!props.gallery.allow_selections) return;

    try {
        const response = await fetch(route('client-gallery.toggle', { token: props.token, photo: photoId }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok) {
            if (data.selected) {
                selectedPhotos.value.push(photoId);
            } else {
                selectedPhotos.value = selectedPhotos.value.filter(id => id !== photoId);
            }
        } else if (data.limit_reached) {
            alert(data.error);
        }
    } catch (error) {
        console.error('Toggle failed:', error);
    }
};

const downloadPhoto = (photoId) => {
    if (!props.gallery.allow_downloads) return;
    window.location.href = route('client-gallery.download', { token: props.token, photo: photoId });
};

const openLightbox = (photo) => {
    lightboxPhoto.value = photo;
};

const closeLightbox = () => {
    lightboxPhoto.value = null;
};

const submitSelections = () => {
    form.post(route('client-gallery.submit', props.token));
};

const selectionProgress = computed(() => {
    if (!props.gallery.selection_limit) return null;
    return `${selectedPhotos.value.length} / ${props.gallery.selection_limit}`;
});
</script>

<template>
    <Head :title="gallery.name" />

    <PublicLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ gallery.name }}</h1>
                <p v-if="gallery.description" class="mt-2 text-gray-600">{{ gallery.description }}</p>

                <!-- Selection Bar -->
                <div v-if="gallery.allow_selections" class="mt-4 p-4 bg-indigo-50 rounded-lg flex items-center justify-between">
                    <div>
                        <span class="font-medium text-indigo-900">{{ selectedPhotos.length }} photos selected</span>
                        <span v-if="selectionProgress" class="text-indigo-600 ml-2">({{ selectionProgress }})</span>
                    </div>
                    <button v-if="selectedPhotos.length > 0" @click="showSubmitModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Submit Selection
                    </button>
                </div>
            </div>

            <!-- Photo Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="photo in photos" :key="photo.id" class="relative group">
                    <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer" @click="openLightbox(photo)">
                        <img :src="`/storage/${photo.thumbnail_path}`" :alt="photo.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    </div>

                    <!-- Selection Checkbox -->
                    <button v-if="gallery.allow_selections" @click.stop="toggleSelection(photo.id)" :class="['absolute top-2 left-2 w-8 h-8 rounded-full border-2 flex items-center justify-center transition', isSelected(photo.id) ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white/80 border-gray-300 hover:border-indigo-500']">
                        <svg v-if="isSelected(photo.id)" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Download Button -->
                    <button v-if="gallery.allow_downloads" @click.stop="downloadPhoto(photo.id)" class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/80 border border-gray-300 flex items-center justify-center hover:bg-white transition">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </button>

                    <p class="mt-2 text-sm text-gray-700 truncate">{{ photo.title }}</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="photos.length === 0" class="text-center py-16">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No photos in this gallery</h3>
            </div>
        </div>

        <!-- Lightbox -->
        <div v-if="lightboxPhoto" class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center" @click="closeLightbox">
            <button @click="closeLightbox" class="absolute top-4 right-4 text-white hover:text-gray-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img :src="`/storage/${lightboxPhoto.display_path || lightboxPhoto.thumbnail_path}`" :alt="lightboxPhoto.title" class="max-w-full max-h-full object-contain" @click.stop />
        </div>

        <!-- Submit Modal -->
        <div v-if="showSubmitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Submit Your Selection</h2>
                <form @submit.prevent="submitSelections">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                            <input type="text" v-model="form.client_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" v-model="form.client_email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea v-model="form.notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="showSubmitModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Submitting...' : 'Submit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
