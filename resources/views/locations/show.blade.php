<x-layouts.public>
    <x-slot name="pageTitle">{{ $location->seo_title ?? $location->name }} - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <x-slot name="seo">
        <x-seo-meta
            :title="$location->seo_title ?? $location->name"
            :description="$location->meta_description ?? $location->description"
            :image="$location->cover_image_url"
        />
    </x-slot>

    <div class="min-h-screen">
        <!-- Hero -->
        <div class="relative h-[40vh] min-h-[300px] overflow-hidden">
            @if($location->cover_image_url)
                <img src="{{ $location->cover_image_url }}" alt="{{ $location->name }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            @else
                <div class="w-full h-full bg-theme-secondary"></div>
            @endif
            <div class="absolute bottom-0 left-0 right-0 p-8">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl font-bold text-white mb-2">{{ $location->name }}</h1>
                    @if($location->region || $location->country)
                        <p class="text-white/80">{{ $location->region }}{{ $location->region && $location->country ? ', ' : '' }}{{ $location->country }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Info Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                @if($location->difficulty)
                    <div class="card-theme rounded-lg p-4 text-center">
                        <span class="text-xs text-theme-muted uppercase">Difficulty</span>
                        <p class="text-theme-primary font-medium">{{ $location->difficulty_label }}</p>
                    </div>
                @endif
                <div class="card-theme rounded-lg p-4 text-center">
                    <span class="text-xs text-theme-muted uppercase">Photos</span>
                    <p class="text-theme-primary font-medium">{{ $location->photo_count }}</p>
                </div>
                @if($location->hasCoordinates())
                    <div class="card-theme rounded-lg p-4 text-center">
                        <span class="text-xs text-theme-muted uppercase">GPS</span>
                        <p class="text-theme-primary font-medium text-sm">{{ number_format($location->latitude, 4) }}, {{ number_format($location->longitude, 4) }}</p>
                    </div>
                @endif
            </div>

            <!-- Description -->
            @if($location->description)
                <div class="prose max-w-none text-theme-secondary mb-12">
                    {!! nl2br(e($location->description)) !!}
                </div>
            @endif

            <!-- Tips -->
            @if($location->tips)
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-theme-primary mb-4">Photography Tips</h2>
                    <div class="card-theme rounded-lg p-6">
                        <div class="prose max-w-none text-theme-secondary">
                            {!! nl2br(e($location->tips)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Best Times -->
            @if($location->best_times)
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-theme-primary mb-4">Best Times to Visit</h2>
                    <div class="card-theme rounded-lg p-6">
                        <div class="prose max-w-none text-theme-secondary">
                            {!! nl2br(e($location->best_times)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <!-- Amenities -->
            @if($location->amenities && count($location->amenities) > 0)
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-theme-primary mb-4">Amenities</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($location->amenities as $amenity)
                            <span class="px-3 py-1 bg-theme-secondary text-theme-secondary rounded-full text-sm">{{ $amenity }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Photos from this location -->
            @if($location->photos->count() > 0)
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-theme-primary mb-6">Photos from this Location</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($location->photos->take(9) as $photo)
                            <a href="{{ route('photos.show', $photo->slug) }}" class="group">
                                <div class="aspect-square overflow-hidden rounded-lg">
                                    <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover transition group-hover:scale-105">
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Map -->
            @if($location->hasCoordinates())
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-theme-primary mb-4">Location</h2>
                    <div id="location-map" class="h-64 rounded-lg"></div>
                </div>

                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const map = L.map('location-map').setView([{{ $location->latitude }}, {{ $location->longitude }}], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([{{ $location->latitude }}, {{ $location->longitude }}]).addTo(map)
                            .bindPopup('{{ $location->name }}').openPopup();
                    });
                </script>
            @endif
        </div>

        <!-- Nearby Locations -->
        @if($nearbyLocations->count() > 0)
            <div class="bg-theme-secondary py-12">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-xl font-bold text-theme-primary mb-6">Nearby Locations</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($nearbyLocations as $nearby)
                            <a href="{{ route('locations.show', $nearby->slug) }}" class="card-theme rounded-lg p-4 hover:shadow-lg transition">
                                <h3 class="font-medium text-theme-primary">{{ $nearby->name }}</h3>
                                @if($nearby->distance ?? false)
                                    <p class="text-sm text-theme-muted">{{ number_format($nearby->distance, 1) }} km away</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.public>
