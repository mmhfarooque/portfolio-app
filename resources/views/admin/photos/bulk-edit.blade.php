<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bulk Edit Photos') }}
            </h2>
            <a href="{{ route('admin.photos.index') }}" class="text-gray-600 hover:text-gray-800 inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Photos
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="bulkEditApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium">Quick Edit Mode</p>
                        <p>Click on any photo to open the edit panel. Changes are saved automatically when you click "Save" or move to another photo.</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">All</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">All</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">Filter</button>
                    @if (request()->hasAny(['status', 'category']))
                        <a href="{{ route('admin.photos.bulk-edit') }}" class="text-gray-500 hover:text-gray-700 text-sm">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Photo Grid -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6">
                    @if ($photos->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                            @foreach ($photos as $photo)
                                <div class="relative group cursor-pointer"
                                     @click="openEditor({{ $photo->id }})"
                                     :class="{ 'ring-2 ring-blue-500 rounded-lg': activePhotoId === {{ $photo->id }} }">
                                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                        <img src="{{ asset('storage/' . $photo->thumbnail_path) }}"
                                             alt="{{ $photo->title }}"
                                             class="w-full h-full object-cover group-hover:opacity-75 transition"
                                             loading="lazy">
                                    </div>
                                    <!-- Quick Info Overlay -->
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition rounded-lg flex flex-col justify-end p-2">
                                        <p class="text-white text-xs font-medium truncate">{{ $photo->title }}</p>
                                        <div class="flex gap-1 mt-1">
                                            <span class="px-1.5 py-0.5 text-[10px] rounded {{ $photo->status === 'published' ? 'bg-green-500' : 'bg-yellow-500' }} text-white">
                                                {{ ucfirst($photo->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Edit Icon -->
                                    <div class="absolute top-2 right-2 bg-white rounded-full p-1.5 shadow opacity-0 group-hover:opacity-100 transition">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </div>
                                    <!-- Saved Indicator -->
                                    <div x-show="savedPhotos.includes({{ $photo->id }})"
                                         class="absolute top-2 left-2 bg-green-500 rounded-full p-1 shadow">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $photos->links() }}
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            No photos found matching your filters.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Slide-over Edit Panel -->
        <div x-show="showEditor"
             x-cloak
             class="fixed inset-0 z-50 overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-500/75" @click="closeEditor()"></div>

            <!-- Panel -->
            <div class="absolute inset-y-0 right-0 w-full max-w-lg flex"
                 x-show="showEditor"
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                <div class="w-full bg-white shadow-xl flex flex-col">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Edit Photo</h3>
                        <button @click="closeEditor()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading" class="flex-1 flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Edit Form -->
                    <div x-show="!loading && currentPhoto" class="flex-1 overflow-y-auto">
                        <!-- Photo Preview -->
                        <div class="p-4 bg-gray-900">
                            <img :src="currentPhoto?.thumbnail_url" class="w-full h-48 object-contain rounded">
                        </div>

                        <form @submit.prevent="savePhoto()" class="p-6 space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" x-model="formData.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea x-model="formData.description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>

                            <!-- Story -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Story</label>
                                <textarea x-model="formData.story" rows="4" placeholder="Share the story behind this photo..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>

                            <!-- Location -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Location Name</label>
                                <input type="text" x-model="formData.location_name" placeholder="e.g., Yosemite National Park" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p x-show="currentPhoto?.has_gps" class="mt-1 text-xs text-green-600">📍 GPS coordinates available</p>
                            </div>

                            <!-- Category & Gallery -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category</label>
                                    <select x-model="formData.category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">None</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Gallery</label>
                                    <select x-model="formData.gallery_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">None</option>
                                        @foreach ($galleries as $gallery)
                                            <option value="{{ $gallery->id }}">{{ $gallery->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select x-model="formData.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                <div class="flex flex-wrap gap-2 p-3 border rounded-md bg-gray-50 max-h-32 overflow-y-auto">
                                    @foreach ($tags as $tag)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" :value="{{ $tag->id }}" x-model="formData.tags" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-1 text-sm text-gray-600">{{ $tag->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Featured -->
                            <div>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" x-model="formData.is_featured" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Featured Photo</span>
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Footer -->
                    <div x-show="!loading && currentPhoto" class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                        <div class="flex gap-2">
                            <button @click="navigatePrev()" :disabled="!hasPrev" class="px-3 py-2 border rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                ← Prev
                            </button>
                            <button @click="navigateNext()" :disabled="!hasNext" class="px-3 py-2 border rounded-md text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next →
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <span x-show="saving" class="text-sm text-gray-500">Saving...</span>
                            <span x-show="saved" class="text-sm text-green-600">✓ Saved</span>
                            <button @click="savePhoto()" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 disabled:opacity-50">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bulkEditApp() {
            return {
                showEditor: false,
                loading: false,
                saving: false,
                saved: false,
                activePhotoId: null,
                currentPhoto: null,
                savedPhotos: [],
                photoIds: @json($photos->pluck('id')->toArray()),
                formData: {
                    title: '',
                    description: '',
                    story: '',
                    location_name: '',
                    category_id: '',
                    gallery_id: '',
                    status: 'draft',
                    is_featured: false,
                    tags: []
                },

                get currentIndex() {
                    return this.photoIds.indexOf(this.activePhotoId);
                },

                get hasPrev() {
                    return this.currentIndex > 0;
                },

                get hasNext() {
                    return this.currentIndex < this.photoIds.length - 1;
                },

                async openEditor(photoId) {
                    this.activePhotoId = photoId;
                    this.showEditor = true;
                    this.loading = true;
                    this.saved = false;

                    try {
                        const response = await fetch(`/admin/photos/${photoId}`);
                        const html = await response.text();

                        // Parse the photo data from the page or use a JSON endpoint
                        // For now, we'll get data from the photos collection
                        const photoElement = document.querySelector(`[data-photo-id="${photoId}"]`);

                        // Fetch photo data via quick endpoint
                        const dataResponse = await fetch(`/admin/photos/${photoId}/edit`);
                        const dataHtml = await dataResponse.text();

                        // Extract photo data from current page
                        const photos = @json($photos->keyBy('id'));
                        const photo = photos[photoId];

                        if (photo) {
                            this.currentPhoto = {
                                id: photo.id,
                                thumbnail_url: '/storage/' + photo.thumbnail_path,
                                has_gps: photo.latitude !== null
                            };
                            this.formData = {
                                title: photo.title || '',
                                description: photo.description || '',
                                story: photo.story || '',
                                location_name: photo.location_name || '',
                                category_id: photo.category_id || '',
                                gallery_id: photo.gallery_id || '',
                                status: photo.status || 'draft',
                                is_featured: photo.is_featured || false,
                                tags: photo.tags ? photo.tags.map(t => t.id) : []
                            };
                        }
                    } catch (error) {
                        console.error('Error loading photo:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                closeEditor() {
                    this.showEditor = false;
                    this.activePhotoId = null;
                    this.currentPhoto = null;
                },

                async savePhoto() {
                    if (!this.activePhotoId || this.saving) return;

                    this.saving = true;
                    this.saved = false;

                    try {
                        const response = await fetch(`/admin/photos/${this.activePhotoId}/quick`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ...this.formData,
                                category_id: this.formData.category_id || null,
                                gallery_id: this.formData.gallery_id || null
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.saved = true;
                            if (!this.savedPhotos.includes(this.activePhotoId)) {
                                this.savedPhotos.push(this.activePhotoId);
                            }
                            setTimeout(() => this.saved = false, 2000);
                        }
                    } catch (error) {
                        console.error('Error saving photo:', error);
                        alert('Error saving photo. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                },

                async navigatePrev() {
                    if (this.hasPrev) {
                        await this.savePhoto();
                        this.openEditor(this.photoIds[this.currentIndex - 1]);
                    }
                },

                async navigateNext() {
                    if (this.hasNext) {
                        await this.savePhoto();
                        this.openEditor(this.photoIds[this.currentIndex + 1]);
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
