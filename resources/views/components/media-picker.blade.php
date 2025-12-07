@props([
    'name',
    'id' => null,
    'value' => null,
    'label' => 'Image',
    'currentImage' => null,
    'accept' => 'image/*',
    'previewClass' => 'w-32 h-32 object-cover rounded',
])

@php
    $inputId = $id ?? $name;
    $pickerId = 'media-picker-' . $inputId;
@endphp

<div x-data="{
    showModal: false,
    photos: [],
    loading: false,
    search: '',
    categoryFilter: '',
    categories: [],
    selectedPhoto: null,
    currentImage: '{{ $currentImage }}',
    selectedPath: '{{ $value }}',
    uploadedFile: false,
    fieldName: '{{ $name }}',

    init() {
        // Listen for media upload completion event
        window.addEventListener('media-uploaded', (e) => {
            if (e.detail.fieldName === this.fieldName) {
                this.currentImage = e.detail.url;
                this.uploadedFile = false;
            }
        });
    },

    async fetchPhotos() {
        this.loading = true;
        try {
            const params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            if (this.categoryFilter) params.append('category', this.categoryFilter);

            const response = await fetch(`{{ route('admin.media.photos') }}?${params}`);
            const data = await response.json();
            this.photos = data.photos;
            this.categories = data.categories;
        } catch (error) {
            console.error('Failed to fetch photos:', error);
        }
        this.loading = false;
    },

    openModal() {
        this.showModal = true;
        this.fetchPhotos();
    },

    selectPhoto(photo) {
        this.selectedPhoto = photo;
        this.selectedPath = photo.thumbnail_path;
        this.currentImage = photo.thumbnail_url;
        this.showModal = false;

        // Update hidden input
        document.getElementById('{{ $pickerId }}-path').value = photo.thumbnail_path;

        // Trigger change event
        document.getElementById('{{ $pickerId }}-path').dispatchEvent(new Event('change'));
    },

    clearSelection() {
        this.selectedPhoto = null;
        this.selectedPath = '';
        this.currentImage = '';
        this.uploadedFile = false;
        document.getElementById('{{ $pickerId }}-path').value = '';
        // Clear file input
        const fileInput = document.getElementById('{{ $inputId }}');
        if (fileInput) fileInput.value = '';
    }
}" class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>

    <!-- Current Image Preview -->
    <template x-if="currentImage">
        <div class="mb-3 relative inline-block">
            <img :src="currentImage.startsWith('blob:') || currentImage.startsWith('http') || currentImage.startsWith('/') ? currentImage : '/storage/' + currentImage"
                 alt="Current image"
                 class="{{ $previewClass }}">
            <button type="button"
                    @click="clearSelection()"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition"
                    title="Remove image">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>

    <!-- Hidden input to store selected path -->
    <input type="hidden" name="{{ $name }}_from_media" id="{{ $pickerId }}-path" :value="selectedPath">

    <!-- Upload and Choose from Media buttons -->
    <div class="flex flex-col gap-2">
        <!-- File Upload Input -->
        <label class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-lg transition cursor-pointer border border-blue-200 w-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Upload File
            <input type="file"
                   name="{{ $name }}"
                   id="{{ $inputId }}"
                   accept="{{ $accept }}"
                   @change="currentImage = URL.createObjectURL($event.target.files[0]); selectedPath = ''; uploadedFile = true"
                   class="sr-only">
        </label>

        <!-- Choose from Media Button -->
        <button type="button"
                @click="openModal()"
                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition border border-gray-200 w-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Choose from Media
        </button>
    </div>

    <!-- Media Picker Modal -->
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showModal = false"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal panel -->
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">

                <!-- Modal Header -->
                <div class="bg-gray-50 px-6 py-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Choose from Media Library</h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Filters -->
                <div class="px-6 py-4 border-b bg-white">
                    <div class="flex flex-wrap gap-4">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <input type="text"
                                   x-model="search"
                                   @input.debounce.300ms="fetchPhotos()"
                                   placeholder="Search photos..."
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>

                        <!-- Category Filter -->
                        <div class="w-48">
                            <select x-model="categoryFilter"
                                    @change="fetchPhotos()"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">All Categories</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Photos Grid -->
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    <!-- Loading State -->
                    <template x-if="loading">
                        <div class="flex items-center justify-center py-12">
                            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loading && photos.length === 0">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No photos found</p>
                        </div>
                    </template>

                    <!-- Photos Grid -->
                    <template x-if="!loading && photos.length > 0">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <template x-for="photo in photos" :key="photo.id">
                                <div @click="selectPhoto(photo)"
                                     class="relative aspect-square cursor-pointer group rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition">
                                    <img :src="photo.thumbnail_url"
                                         :alt="photo.title"
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                        <span class="text-white text-sm font-medium px-2 text-center" x-text="photo.title"></span>
                                    </div>
                                    <!-- Selection indicator -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                                        <div class="bg-blue-500 rounded-full p-1">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t flex justify-end">
                    <button type="button"
                            @click="showModal = false"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
