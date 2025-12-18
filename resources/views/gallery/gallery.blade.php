<x-layouts.public>
    <x-slot name="title">{{ $gallery->name }} - {{ config('app.name', 'Photography Portfolio') }}</x-slot>

    <div class="min-h-screen py-20 px-4" x-data="photoSelections({{ $gallery->id }})">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4">{{ $gallery->name }}</h1>
                @if ($gallery->description)
                    <p class="text-gray-400 max-w-2xl mx-auto">{{ $gallery->description }}</p>
                @endif

                <!-- Selection Count Badge -->
                <div x-show="selectionCount > 0" x-cloak class="mt-4">
                    <a href="{{ route('client.selections') }}"
                       class="inline-flex items-center gap-2 bg-theme-accent text-white px-4 py-2 rounded-full text-sm font-medium hover:opacity-90 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span x-text="selectionCount + ' selected'"></span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Photos Grid -->
            @if ($photos->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($photos as $photo)
                        <div class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-gray-800">
                            <a href="{{ route('photos.show', $photo) }}">
                                <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                                <div class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition">
                                    <h3 class="text-lg font-medium">{{ $photo->title }}</h3>
                                </div>
                            </a>
                            <!-- Selection Heart Button -->
                            <button @click="toggleSelection({{ $photo->id }})"
                                    :class="isSelected({{ $photo->id }}) ? 'bg-red-500 text-white' : 'bg-black/50 text-white hover:bg-red-500'"
                                    class="absolute top-3 right-3 p-2 rounded-full transition-all duration-200 opacity-0 group-hover:opacity-100 z-10"
                                    title="Add to selections">
                                <svg class="w-5 h-5" :fill="isSelected({{ $photo->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $photos->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="mx-auto h-16 w-16 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-gray-400">No photos in this gallery</h3>
                    <p class="mt-2 text-gray-500">Check back later for new additions.</p>
                </div>
            @endif

            <!-- Back Link -->
            <div class="mt-12">
                <a href="{{ route('photos.index') }}" class="inline-flex items-center text-gray-400 hover:text-white transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Gallery
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function photoSelections(galleryId = null) {
            return {
                galleryId: galleryId,
                selectedPhotos: [],
                selectionCount: 0,

                init() {
                    this.loadSelectionCount();
                    this.loadSelectedPhotos();
                },

                loadSelectionCount() {
                    fetch('/selections/count')
                        .then(response => response.json())
                        .then(data => {
                            this.selectionCount = data.count;
                        });
                },

                loadSelectedPhotos() {
                    // Check each photo on the page
                    const photoButtons = document.querySelectorAll('[data-photo-id]');
                    photoButtons.forEach(button => {
                        const photoId = button.dataset.photoId;
                        fetch(`/selections/photo/${photoId}/check`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.selected) {
                                    this.selectedPhotos.push(parseInt(photoId));
                                }
                            });
                    });

                    // Alternative: Get all photo IDs from the page and check them
                    @foreach ($photos as $photo)
                    fetch('/selections/photo/{{ $photo->id }}/check')
                        .then(response => response.json())
                        .then(data => {
                            if (data.selected && !this.selectedPhotos.includes({{ $photo->id }})) {
                                this.selectedPhotos.push({{ $photo->id }});
                            }
                        });
                    @endforeach
                },

                isSelected(photoId) {
                    return this.selectedPhotos.includes(photoId);
                },

                toggleSelection(photoId) {
                    fetch(`/selections/photo/${photoId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ gallery_id: this.galleryId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.selected) {
                            this.selectedPhotos.push(photoId);
                        } else {
                            this.selectedPhotos = this.selectedPhotos.filter(id => id !== photoId);
                        }
                        this.selectionCount = data.count;
                    });
                }
            }
        }
    </script>
    @endpush
</x-layouts.public>
