<x-layouts.public>
    <x-slot name="pageTitle">{{ $series->seo_title ?? $series->title }} - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <x-slot name="seo">
        <x-seo-meta
            :title="$series->seo_title ?? $series->title"
            :description="$series->meta_description ?? $series->description"
            :image="$series->cover_image_url"
        />
    </x-slot>

    <div class="min-h-screen">
        <!-- Hero Section -->
        <div class="relative h-[50vh] min-h-[400px] overflow-hidden">
            @if($series->cover_image_url)
                <img src="{{ $series->cover_image_url }}"
                     alt="{{ $series->title }}"
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
            @else
                <div class="w-full h-full bg-theme-secondary"></div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">{{ $series->title }}</h1>
                    <div class="flex flex-wrap gap-4 text-white/80 text-sm">
                        @if($series->project_date)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $series->project_date->format('F Y') }}
                            </span>
                        @endif
                        @if($series->location)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $series->location }}
                            </span>
                        @endif
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $series->photos->count() }} photos
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description & Story -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if($series->description)
                <div class="text-theme-secondary text-lg leading-relaxed mb-8">
                    {{ $series->description }}
                </div>
            @endif

            @if($series->story)
                <div class="prose prose-lg max-w-none text-theme-secondary">
                    {!! nl2br(e($series->story)) !!}
                </div>
            @endif
        </div>

        <!-- Photo Grid -->
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            @if($series->photos->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach($series->photos as $photo)
                        <a href="{{ route('photos.show', $photo->slug) }}" class="group">
                            <div class="overflow-hidden rounded-lg">
                                <img src="{{ $photo->display_url ?? $photo->thumbnail_url }}"
                                     alt="{{ $photo->title }}"
                                     class="w-full h-auto transition group-hover:scale-105 duration-500">
                            </div>
                            <div class="mt-3">
                                <h3 class="text-theme-primary font-medium group-hover:text-theme-accent transition">
                                    {{ $photo->title }}
                                </h3>
                                @if($photo->pivot->caption)
                                    <p class="text-theme-secondary text-sm mt-1">{{ $photo->pivot->caption }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <p class="text-theme-muted">No photos in this series yet.</p>
                </div>
            @endif
        </div>

        <!-- Related Series -->
        @if($relatedSeries->count() > 0)
            <div class="bg-theme-secondary py-16">
                <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-theme-primary mb-8">More Series</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($relatedSeries as $related)
                            <a href="{{ route('series.show', $related->slug) }}" class="group">
                                <div class="card-theme rounded-lg overflow-hidden">
                                    <div class="aspect-video overflow-hidden">
                                        @if($related->cover_image_url)
                                            <img src="{{ $related->cover_image_url }}"
                                                 alt="{{ $related->title }}"
                                                 class="w-full h-full object-cover transition group-hover:scale-105 duration-500">
                                        @else
                                            <div class="w-full h-full bg-theme-primary/10"></div>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-theme-primary group-hover:text-theme-accent transition">
                                            {{ $related->title }}
                                        </h3>
                                        <p class="text-sm text-theme-muted mt-1">{{ $related->photos_count }} photos</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.public>
