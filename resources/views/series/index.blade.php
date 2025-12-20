<x-layouts.public>
    <x-slot name="pageTitle">Photo Series - {{ App\Models\Setting::get('site_name', config('app.name')) }}</x-slot>

    <div class="min-h-screen py-12">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-theme-primary mb-4">Photo Series</h1>
                <p class="text-theme-secondary max-w-2xl mx-auto">Curated collections of photographs exploring themes, locations, and stories.</p>
            </div>

            <!-- Series Grid -->
            @if($series->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($series as $item)
                        <a href="{{ route('series.show', $item->slug) }}" class="group">
                            <div class="card-theme rounded-lg overflow-hidden transition hover:shadow-lg">
                                <div class="aspect-[16/10] overflow-hidden">
                                    @if($item->cover_image_url)
                                        <img src="{{ $item->cover_image_url }}"
                                             alt="{{ $item->title }}"
                                             class="w-full h-full object-cover transition group-hover:scale-105 duration-500">
                                    @else
                                        <div class="w-full h-full bg-theme-secondary flex items-center justify-center">
                                            <svg class="w-12 h-12 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-6">
                                    <h2 class="text-xl font-semibold text-theme-primary mb-2 group-hover:text-theme-accent transition">
                                        {{ $item->title }}
                                    </h2>
                                    @if($item->description)
                                        <p class="text-theme-secondary text-sm line-clamp-2 mb-3">
                                            {{ Str::limit($item->description, 120) }}
                                        </p>
                                    @endif
                                    <div class="flex items-center justify-between text-sm text-theme-muted">
                                        <span>{{ $item->photos_count }} photos</span>
                                        @if($item->project_date)
                                            <span>{{ $item->project_date->format('M Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $series->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-theme-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="text-theme-secondary text-lg">No series available yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.public>
