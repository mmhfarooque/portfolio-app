<x-layouts.public>
    <x-slot name="title">{{ $gallery->name }} - Client Gallery</x-slot>

    <div class="min-h-screen py-12 px-4" x-data="clientGallery()">
        <div class="max-w-screen-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-theme-primary mb-2">{{ $gallery->name }}</h1>
                @if($gallery->description)
                    <p class="text-theme-muted max-w-2xl mx-auto">{{ $gallery->description }}</p>
                @endif

                @if($gallery->client_name)
                    <p class="text-sm text-theme-muted mt-2">Prepared for {{ $gallery->client_name }}</p>
                @endif

                @if($gallery->expires_at)
                    <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-800 rounded-lg text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Gallery expires {{ $gallery->expires_at->diffForHumans() }}
                        @if($gallery->days_until_expiration !== null)
                            ({{ $gallery->days_until_expiration }} {{ Str::plural('day', $gallery->days_until_expiration) }} remaining)
                        @endif
                    </div>
                @endif
            </div>

            <!-- Selection Bar -->
            @if($gallery->allow_selections)
            <div class="sticky top-0 z-40 bg-theme-secondary border-b border-theme py-3 px-4 mb-6 -mx-4 sm:mx-0 sm:rounded-lg">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-theme-primary font-medium">
                            <span x-text="selectedCount">0</span> photo<span x-show="selectedCount !== 1">s</span> selected
                            @if($gallery->selection_limit)
                                <span class="text-theme-muted">(max {{ $gallery->selection_limit }})</span>
                            @endif
                        </span>
                        <button @click="clearSelections" x-show="selectedCount > 0"
                                class="text-sm text-theme-muted hover:text-theme-primary">
                            Clear all
                        </button>
                    </div>
                    <button @click="showSubmitModal = true" x-show="selectedCount > 0"
                            class="px-4 py-2 bg-theme-accent text-white rounded-lg hover:opacity-90 transition">
                        Submit Selections
                    </button>
                </div>
            </div>
            @endif

            <!-- Photo Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($gallery->photos as $photo)
                    <div class="relative group cursor-pointer"
                         @if($gallery->allow_selections) @click="togglePhoto({{ $photo->id }})" @endif
                         :class="{ 'ring-4 ring-theme-accent': selections.includes({{ $photo->id }}) }">
                        <div class="aspect-square bg-theme-tertiary rounded-lg overflow-hidden">
                            <img src="{{ $photo->thumbnail_url }}"
                                 alt="{{ $photo->title }}"
                                 class="w-full h-full object-cover transition group-hover:scale-105">
                        </div>

                        @if($gallery->allow_selections)
                        <div class="absolute top-2 right-2">
                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition"
                                 :class="selections.includes({{ $photo->id }})
                                    ? 'bg-theme-accent border-theme-accent text-white'
                                    : 'bg-white/80 border-gray-300'">
                                <svg x-show="selections.includes({{ $photo->id }})" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        @endif

                        <div class="absolute inset-x-0 bottom-0 p-2 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition">
                            <p class="text-white text-sm truncate">{{ $photo->title }}</p>
                        </div>

                        @if($gallery->allow_downloads)
                        <a href="{{ route('client-gallery.download', ['token' => $gallery->access_token, 'photo' => $photo->id]) }}"
                           @click.stop
                           class="absolute top-2 left-2 p-2 bg-white/80 rounded-full opacity-0 group-hover:opacity-100 transition hover:bg-white">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($gallery->photos->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-theme-primary">No photos yet</h3>
                    <p class="mt-1 text-theme-muted">Photos will appear here once they are added to this gallery.</p>
                </div>
            @endif
        </div>

        <!-- Submit Modal -->
        @if($gallery->allow_selections)
        <div x-show="showSubmitModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="showSubmitModal = false">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Submit Your Selections</h3>
                <p class="text-gray-600 mb-4">You've selected <span x-text="selectedCount"></span> photo<span x-show="selectedCount !== 1">s</span>.</p>

                <form action="{{ route('client-gallery.submit', $gallery->access_token) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input type="text" name="client_name" required
                               value="{{ $gallery->client_name }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="client_email" required
                               value="{{ $gallery->client_email }}"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Any special requests or notes..."
                                  class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showSubmitModal = false"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Submit Selections
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <script>
        function clientGallery() {
            return {
                selections: [],
                selectedCount: 0,
                showSubmitModal: false,
                limit: {{ $gallery->selection_limit ?? 'null' }},

                init() {
                    this.loadSelections();
                },

                async loadSelections() {
                    try {
                        const response = await fetch('{{ route("client-gallery.selections", $gallery->access_token) }}');
                        const data = await response.json();
                        this.selections = data.selections;
                        this.selectedCount = data.count;
                    } catch (e) {
                        console.error('Failed to load selections', e);
                    }
                },

                async togglePhoto(photoId) {
                    try {
                        const response = await fetch(`/client-gallery/{{ $gallery->access_token }}/toggle/${photoId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        const data = await response.json();

                        if (response.ok) {
                            if (data.selected) {
                                this.selections.push(photoId);
                            } else {
                                this.selections = this.selections.filter(id => id !== photoId);
                            }
                            this.selectedCount = data.count;
                        } else if (data.limit_reached) {
                            alert(`You can only select up to ${this.limit} photos.`);
                        }
                    } catch (e) {
                        console.error('Failed to toggle selection', e);
                    }
                },

                async clearSelections() {
                    for (const photoId of [...this.selections]) {
                        await this.togglePhoto(photoId);
                    }
                }
            }
        }
    </script>
</x-layouts.public>
