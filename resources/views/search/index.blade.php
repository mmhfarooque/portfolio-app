<x-layouts.public>
    <x-slot name="pageTitle">Search Photos - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Search Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-theme-primary mb-6">Search Photos</h1>

                <!-- Search Form -->
                <form method="GET" action="{{ route('search') }}" class="space-y-4">
                    <!-- Main Search Input -->
                    <div class="flex gap-4">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="Search photos..."
                               class="flex-1 px-4 py-3 rounded-lg input-theme focus:ring-2 focus:ring-theme-accent focus:outline-none">
                        <button type="submit" class="px-6 py-3 bg-theme-accent text-white rounded-lg hover:opacity-90 transition">
                            Search
                        </button>
                    </div>

                    <!-- Filters -->
                    <div x-data="{ showFilters: false }">
                        <button type="button" @click="showFilters = !showFilters" class="text-theme-secondary hover:text-theme-accent transition text-sm">
                            <span x-show="!showFilters">Show Filters</span>
                            <span x-show="showFilters">Hide Filters</span>
                        </button>

                        <div x-show="showFilters" x-cloak class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Category -->
                            <div>
                                <label class="block text-sm text-theme-muted mb-1">Category</label>
                                <select name="category" class="w-full px-3 py-2 rounded-lg input-theme">
                                    <option value="">All Categories</option>
                                    @foreach($filterOptions['categories'] as $id => $name)
                                        <option value="{{ $id }}" {{ ($filters['category'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label class="block text-sm text-theme-muted mb-1">Year</label>
                                <select name="year" class="w-full px-3 py-2 rounded-lg input-theme">
                                    <option value="">All Years</option>
                                    @foreach($filterOptions['years'] as $year)
                                        <option value="{{ $year }}" {{ ($filters['year'] ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Camera -->
                            <div>
                                <label class="block text-sm text-theme-muted mb-1">Camera</label>
                                <select name="camera" class="w-full px-3 py-2 rounded-lg input-theme">
                                    <option value="">All Cameras</option>
                                    @foreach($filterOptions['cameras'] as $camera)
                                        <option value="{{ $camera }}" {{ ($filters['camera'] ?? '') == $camera ? 'selected' : '' }}>{{ $camera }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Orientation -->
                            <div>
                                <label class="block text-sm text-theme-muted mb-1">Orientation</label>
                                <select name="orientation" class="w-full px-3 py-2 rounded-lg input-theme">
                                    <option value="">Any</option>
                                    <option value="landscape" {{ ($filters['orientation'] ?? '') == 'landscape' ? 'selected' : '' }}>Landscape</option>
                                    <option value="portrait" {{ ($filters['orientation'] ?? '') == 'portrait' ? 'selected' : '' }}>Portrait</option>
                                    <option value="square" {{ ($filters['orientation'] ?? '') == 'square' ? 'selected' : '' }}>Square</option>
                                </select>
                            </div>

                            <!-- Has Location -->
                            <div class="flex items-center">
                                <input type="checkbox" name="has_location" id="has_location" value="1" {{ !empty($filters['has_location']) ? 'checked' : '' }}
                                       class="h-4 w-4 text-theme-accent border-theme rounded">
                                <label for="has_location" class="ml-2 text-sm text-theme-secondary">Has GPS data</label>
                            </div>

                            <!-- Featured Only -->
                            <div class="flex items-center">
                                <input type="checkbox" name="featured" id="featured" value="1" {{ !empty($filters['featured']) ? 'checked' : '' }}
                                       class="h-4 w-4 text-theme-accent border-theme rounded">
                                <label for="featured" class="ml-2 text-sm text-theme-secondary">Featured only</label>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label class="block text-sm text-theme-muted mb-1">Sort By</label>
                                <select name="sort" class="w-full px-3 py-2 rounded-lg input-theme">
                                    <option value="captured_at" {{ ($filters['sort'] ?? 'captured_at') == 'captured_at' ? 'selected' : '' }}>Date Taken</option>
                                    <option value="created_at" {{ ($filters['sort'] ?? '') == 'created_at' ? 'selected' : '' }}>Date Added</option>
                                    <option value="title" {{ ($filters['sort'] ?? '') == 'title' ? 'selected' : '' }}>Title</option>
                                    <option value="views" {{ ($filters['sort'] ?? '') == 'views' ? 'selected' : '' }}>Most Viewed</option>
                                </select>
                            </div>

                            <div class="flex items-end">
                                <a href="{{ route('search') }}" class="px-4 py-2 text-theme-secondary hover:text-theme-accent transition">
                                    Clear Filters
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results -->
            @if($photos->count() > 0)
                <div class="mb-4 text-theme-muted">
                    {{ $photos->total() }} photo{{ $photos->total() != 1 ? 's' : '' }} found
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($photos as $photo)
                        <a href="{{ route('photos.show', $photo->slug) }}" class="group">
                            <div class="aspect-square overflow-hidden rounded-lg">
                                <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}"
                                     class="w-full h-full object-cover transition group-hover:scale-105">
                            </div>
                            <p class="mt-2 text-theme-primary text-sm truncate group-hover:text-theme-accent transition">{{ $photo->title }}</p>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $photos->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-theme-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-theme-secondary text-lg">No photos found matching your criteria.</p>
                    <p class="text-theme-muted mt-2">Try adjusting your filters or search terms.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.public>
