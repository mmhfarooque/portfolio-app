<x-layouts.public>
    <x-slot name="title">Photos - {{ config('app.name', 'Photography Portfolio') }}</x-slot>

    <div class="min-h-screen py-20 px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4 text-theme-primary">
                    @if ($currentCategory)
                        {{ $currentCategory->name }}
                    @elseif ($currentTag)
                        Tagged: {{ $currentTag->name }}
                    @else
                        Photo Gallery
                    @endif
                </h1>
                @if ($currentCategory && $currentCategory->description)
                    <p class="text-theme-muted max-w-2xl mx-auto">{{ $currentCategory->description }}</p>
                @endif
            </div>

            <!-- Search Bar -->
            <div class="max-w-xl mx-auto mb-8">
                <form action="{{ route('photos.index') }}" method="GET" class="relative">
                    @if ($currentCategory)
                        <input type="hidden" name="category" value="{{ $currentCategory->slug }}">
                    @endif
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search photos by title, description, or camera..."
                           class="w-full rounded-full py-3 px-5 pr-12 transition input-theme border-2">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-theme-muted hover:text-theme-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
                @if (request('search'))
                    <div class="text-center mt-3">
                        <span class="text-theme-muted">Showing results for "{{ request('search') }}"</span>
                        <a href="{{ route('photos.index', $currentCategory ? ['category' => $currentCategory->slug] : []) }}" class="text-theme-accent hover:opacity-80 ml-2">Clear</a>
                    </div>
                @endif
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 justify-center mb-12">
                <a href="{{ route('photos.index') }}" class="px-4 py-2 rounded-full text-sm transition {{ !$currentCategory && !$currentTag ? 'btn-theme-primary' : 'border border-theme text-theme-secondary hover:text-theme-primary hover:border-theme-accent' }}">
                    All
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('photos.index', ['category' => $category->slug]) }}" class="px-4 py-2 rounded-full text-sm transition {{ $currentCategory && $currentCategory->id === $category->id ? 'btn-theme-primary' : 'border border-theme text-theme-secondary hover:text-theme-primary hover:border-theme-accent' }}">
                        {{ $category->name }} ({{ $category->published_photos_count }})
                    </a>
                @endforeach
            </div>

            <!-- Photos Grid -->
            @if ($photos->count() > 0)
                <div id="photos-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($photos as $photo)
                        <a href="{{ route('photos.show', $photo) }}" class="photo-item group relative aspect-[4/3] overflow-hidden rounded-lg bg-theme-tertiary shadow-theme block">
                            <img src="{{ asset('storage/' . $photo->thumbnail_path) }}"
                                 alt="{{ $photo->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition">
                                <h3 class="text-lg font-medium text-white">{{ $photo->title }}</h3>
                                @if ($photo->category)
                                    <p class="text-gray-300 text-sm">{{ $photo->category->name }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Infinite Scroll Loading Indicator -->
                <div id="loading-indicator" class="hidden text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-theme-accent" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-theme-muted mt-2">Loading more photos...</p>
                </div>

                <!-- End of photos message -->
                <div id="no-more-photos" class="hidden text-center py-8">
                    <p class="text-theme-muted">You've reached the end of the gallery</p>
                </div>

                <!-- Pagination (hidden when infinite scroll is active, fallback for no-JS) -->
                <div id="pagination" class="mt-12">
                    {{ $photos->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="mx-auto h-16 w-16 text-theme-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-xl font-medium text-theme-secondary">No photos found</h3>
                    @if (request('search'))
                        <p class="mt-2 text-theme-muted">Try a different search term.</p>
                    @else
                        <p class="mt-2 text-theme-muted">Check back later for new additions.</p>
                    @endif
                </div>
            @endif

            <!-- Tags -->
            @if ($tags->count() > 0)
                <div class="mt-20 border-t border-theme pt-12">
                    <h2 class="text-xl font-medium mb-6 text-center text-theme-primary">Browse by Tag</h2>
                    <div class="flex flex-wrap gap-2 justify-center">
                        @foreach ($tags as $tag)
                            @if ($tag->published_photos_count > 0)
                                <a href="{{ route('tag.show', $tag) }}" class="px-3 py-1 rounded-full text-sm border border-theme text-theme-secondary hover:border-theme-accent hover:text-theme-accent transition">
                                    {{ $tag->name }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Photo Map Link -->
            @if ($photosWithLocation > 0)
                <div class="mt-12 text-center">
                    <a href="{{ route('photos.map') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-theme-card border border-theme hover:border-theme-accent rounded-full transition shadow-theme">
                        <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-theme-primary">View {{ $photosWithLocation }} Photos on Map</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Infinite scroll
            let page = {{ $photos->currentPage() }};
            let lastPage = {{ $photos->lastPage() }};
            let loading = false;

            const photosGrid = document.getElementById('photos-grid');
            const loadingIndicator = document.getElementById('loading-indicator');
            const noMorePhotos = document.getElementById('no-more-photos');
            const pagination = document.getElementById('pagination');

            // Hide pagination when JS is enabled
            if (pagination) {
                pagination.style.display = 'none';
            }

            function loadMorePhotos() {
                if (loading || page >= lastPage) {
                    if (page >= lastPage) {
                        noMorePhotos.classList.remove('hidden');
                    }
                    return;
                }

                loading = true;
                loadingIndicator.classList.remove('hidden');

                const nextPage = page + 1;
                const url = new URL(window.location.href);
                url.searchParams.set('page', nextPage);

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPhotos = doc.querySelectorAll('.photo-item');

                    newPhotos.forEach(photo => {
                        photosGrid.appendChild(photo);
                    });

                    page = nextPage;
                    loading = false;
                    loadingIndicator.classList.add('hidden');

                    if (page >= lastPage) {
                        noMorePhotos.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading more photos:', error);
                    loading = false;
                    loadingIndicator.classList.add('hidden');
                });
            }

            // Intersection Observer for infinite scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        loadMorePhotos();
                    }
                });
            }, {
                rootMargin: '200px'
            });

            // Observe the loading indicator
            if (loadingIndicator && lastPage > 1) {
                observer.observe(loadingIndicator);
            }
        });
    </script>
</x-layouts.public>
