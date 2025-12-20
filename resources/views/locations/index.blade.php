<x-layouts.public>
    <x-slot name="pageTitle">Shooting Locations - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <div class="min-h-screen py-12">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-theme-primary mb-4">Shooting Locations</h1>
                <p class="text-theme-secondary max-w-2xl mx-auto">Discover amazing photography locations with tips and sample photos.</p>
            </div>

            <!-- Featured Locations -->
            @if($featuredLocations->count() > 0)
                <section class="mb-16">
                    <h2 class="text-2xl font-bold text-theme-primary mb-6">Featured Locations</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($featuredLocations as $location)
                            <a href="{{ route('locations.show', $location->slug) }}" class="group">
                                <div class="card-theme rounded-lg overflow-hidden">
                                    <div class="aspect-video overflow-hidden">
                                        @if($location->cover_image_url)
                                            <img src="{{ $location->cover_image_url }}" alt="{{ $location->name }}" class="w-full h-full object-cover transition group-hover:scale-105 duration-500">
                                        @else
                                            <div class="w-full h-full bg-theme-secondary flex items-center justify-center">
                                                <svg class="w-12 h-12 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-theme-primary group-hover:text-theme-accent transition">{{ $location->name }}</h3>
                                        @if($location->region || $location->country)
                                            <p class="text-sm text-theme-muted">{{ $location->region }}{{ $location->region && $location->country ? ', ' : '' }}{{ $location->country }}</p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- All Locations -->
            <section>
                <h2 class="text-2xl font-bold text-theme-primary mb-6">All Locations</h2>
                @if($locations->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($locations as $location)
                            <a href="{{ route('locations.show', $location->slug) }}" class="group">
                                <div class="card-theme rounded-lg p-4 hover:shadow-lg transition">
                                    <h3 class="font-semibold text-theme-primary group-hover:text-theme-accent transition">{{ $location->name }}</h3>
                                    @if($location->region || $location->country)
                                        <p class="text-sm text-theme-muted">{{ $location->region }}{{ $location->region && $location->country ? ', ' : '' }}{{ $location->country }}</p>
                                    @endif
                                    @if($location->difficulty)
                                        <span class="inline-block mt-2 text-xs px-2 py-1 rounded bg-theme-secondary text-theme-secondary">{{ $location->difficulty_label }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <p class="text-theme-muted">No locations available yet.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.public>
