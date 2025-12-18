<x-layouts.public>
    <x-slot name="title">My Photo Selections</x-slot>

    <div class="min-h-screen py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-theme-primary mb-2">My Photo Selections</h1>
                <p class="text-theme-secondary">
                    @if($selectionCount > 0)
                        You have selected {{ $selectionCount }} {{ Str::plural('photo', $selectionCount) }}.
                    @else
                        You haven't selected any photos yet.
                    @endif
                </p>
            </div>

            @if (session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if($selectionCount > 0)
                <!-- Actions Bar -->
                <div class="bg-theme-card border border-theme rounded-lg p-4 mb-8 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="text-theme-secondary">Actions:</span>
                        <a href="{{ route('client.export', ['format' => 'txt']) }}" class="inline-flex items-center gap-2 text-theme-accent hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export as Text
                        </a>
                        <a href="{{ route('client.export', ['format' => 'csv']) }}" class="inline-flex items-center gap-2 text-theme-accent hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export as CSV
                        </a>
                    </div>
                    <button onclick="clearAllSelections()" class="text-red-500 hover:text-red-700">
                        Clear All Selections
                    </button>
                </div>

                <!-- Photo Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
                    @foreach($selections as $selection)
                        <div class="group relative bg-theme-card border border-theme rounded-lg overflow-hidden" data-photo-id="{{ $selection->photo->id }}">
                            <a href="{{ route('photos.show', $selection->photo->slug) }}" class="block">
                                <div class="aspect-square">
                                    <img src="{{ asset('storage/' . $selection->photo->thumbnail_path) }}"
                                         alt="{{ $selection->photo->title }}"
                                         class="w-full h-full object-cover">
                                </div>
                            </a>
                            <div class="p-3">
                                <h3 class="text-sm font-medium text-theme-primary truncate">
                                    {{ $selection->photo->title ?? 'Untitled' }}
                                </h3>
                                @if($selection->gallery)
                                    <p class="text-xs text-theme-muted mt-1">
                                        {{ $selection->gallery->name }}
                                    </p>
                                @endif
                            </div>
                            <!-- Remove Button -->
                            <button onclick="toggleSelection({{ $selection->photo->id }})"
                                    class="absolute top-2 right-2 p-2 bg-black/50 hover:bg-red-500 rounded-full text-white transition-colors"
                                    title="Remove from selections">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- Send to Photographer Form -->
                <div class="bg-theme-card border border-theme rounded-lg p-6">
                    <h2 class="text-xl font-bold text-theme-primary mb-4">Send Selections to Photographer</h2>
                    <p class="text-theme-secondary mb-6">Fill out the form below to send your photo selections to the photographer.</p>

                    <form method="POST" action="{{ route('client.send') }}" class="space-y-4">
                        @csrf

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-theme-secondary mb-1">Your Name</label>
                                <input type="text" name="name" id="name" required
                                       class="w-full input-theme"
                                       placeholder="Your name">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-theme-secondary mb-1">Your Email</label>
                                <input type="email" name="email" id="email" required
                                       class="w-full input-theme"
                                       placeholder="your@email.com">
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-theme-secondary mb-1">Message (Optional)</label>
                            <textarea name="message" id="message" rows="3"
                                      class="w-full input-theme"
                                      placeholder="Any additional notes or requirements..."></textarea>
                        </div>

                        <button type="submit" class="btn-theme-primary font-bold py-3 px-6 rounded-md">
                            Send {{ $selectionCount }} {{ Str::plural('Photo', $selectionCount) }} to Photographer
                        </button>
                    </form>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="w-20 h-20 mx-auto mb-6 bg-theme-tertiary rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-medium text-theme-primary mb-2">No photos selected</h2>
                    <p class="text-theme-secondary mb-6">
                        Browse galleries and click the heart icon on photos you like to add them to your selections.
                    </p>
                    <a href="{{ route('photos.index') }}" class="btn-theme-primary font-bold py-3 px-6 rounded-md inline-block">
                        Browse Photos
                    </a>
                </div>
            @endif

            <!-- Back Link -->
            <div class="mt-8 text-center">
                <a href="{{ route('photos.index') }}" class="text-theme-muted hover:text-theme-accent transition">
                    &larr; Back to Gallery
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleSelection(photoId) {
            fetch(`/selections/photo/${photoId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Remove the photo card from the grid
                const card = document.querySelector(`[data-photo-id="${photoId}"]`);
                if (card) {
                    card.remove();
                }

                // Update count or reload if all removed
                if (data.count === 0) {
                    location.reload();
                }
            });
        }

        function clearAllSelections() {
            if (!confirm('Are you sure you want to clear all your photo selections?')) {
                return;
            }

            fetch('/selections/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
    @endpush
</x-layouts.public>
