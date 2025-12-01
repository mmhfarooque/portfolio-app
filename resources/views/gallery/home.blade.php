<x-layouts.public>
    <x-slot name="title">{{ config('app.name', 'Photography Portfolio') }} - Home</x-slot>

    <!-- Hero Section -->
    <section class="relative h-screen flex items-center justify-center">
        @if ($featuredPhotos->first())
            <div class="absolute inset-0">
                <img src="{{ asset('storage/' . $featuredPhotos->first()->display_path) }}" alt="{{ $featuredPhotos->first()->title }}" class="w-full h-full object-cover opacity-60">
            </div>
            <div class="absolute inset-0 photo-overlay-theme"></div>
        @else
            <div class="absolute inset-0 bg-theme-secondary"></div>
        @endif
        <div class="relative z-10 text-center px-4">
            <h1 class="text-5xl md:text-7xl font-bold mb-4 tracking-tight text-theme-primary">Capturing Moments</h1>
            <p class="text-xl md:text-2xl text-theme-secondary mb-8">Landscape & Nature Photography</p>
            <a href="{{ route('photos.index') }}" class="btn-theme-primary inline-block px-8 py-3 rounded-full font-semibold">
                View Gallery
            </a>
        </div>
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
        </div>
    </section>

    <!-- Categories Section -->
    @if ($categories->count() > 0)
        <section class="py-20 px-4 bg-theme-primary">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-12 text-center text-theme-primary">Browse by Category</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($categories as $category)
                        <a href="{{ route('category.show', $category) }}" class="group relative aspect-[4/3] overflow-hidden rounded-theme-lg card-theme">
                            @if ($category->cover_image)
                                <img src="{{ asset('storage/' . $category->cover_image) }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition"></div>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                                    <h3 class="text-2xl font-bold text-white drop-shadow-lg">{{ $category->name }}</h3>
                                    <p class="text-gray-200 mt-2 drop-shadow">{{ $category->published_photos_count }} photos</p>
                                </div>
                            @else
                                <div class="w-full h-full bg-theme-card"></div>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                                    <h3 class="text-2xl font-bold text-theme-primary">{{ $category->name }}</h3>
                                    <p class="text-theme-secondary mt-2">{{ $category->published_photos_count }} photos</p>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Featured Photos Section -->
    @if ($featuredPhotos->count() > 0)
        <section class="py-20 px-4 bg-theme-secondary">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-12 text-center text-theme-primary">Featured Photos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($featuredPhotos->take(6) as $photo)
                        <a href="{{ route('photos.show', $photo) }}" class="group relative aspect-[4/3] overflow-hidden rounded-theme-lg shadow-theme">
                            <img src="{{ asset('storage/' . $photo->thumbnail_path) }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition">
                                <h3 class="text-lg font-medium text-white">{{ $photo->title }}</h3>
                                @if ($photo->category)
                                    <p class="text-gray-200 text-sm">{{ $photo->category->name }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="text-center mt-12">
                    <a href="{{ route('photos.index') }}" class="btn-theme-outline inline-block px-8 py-3 rounded-full font-semibold">
                        View All Photos
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Recent Photos Section -->
    @if ($recentPhotos->count() > 0)
        <section class="py-20 px-4 bg-theme-primary">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-3xl font-bold mb-12 text-center text-theme-primary">Recent Additions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach ($recentPhotos as $photo)
                        <a href="{{ route('photos.show', $photo) }}" class="group relative aspect-square overflow-hidden rounded-theme-lg shadow-theme">
                            <img src="{{ asset('storage/' . $photo->thumbnail_path) }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <div class="absolute inset-0 photo-overlay-theme opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="text-sm font-medium text-white">{{ $photo->title }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
