<x-layouts.public>
    <x-slot name="title">{{ $tag->name }} - {{ config('app.name', 'Photography Portfolio') }}</x-slot>

    <div class="min-h-screen py-20 px-4">
        <div class="max-w-screen-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <p class="text-gray-400 mb-2">Tagged with</p>
                <h1 class="text-4xl font-bold">{{ $tag->name }}</h1>
            </div>

            <!-- Photos Grid -->
            @if ($photos->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($photos as $photo)
                        <a href="{{ route('photos.show', $photo) }}" class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-gray-800">
                            <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition">
                                <h3 class="text-lg font-medium">{{ $photo->title }}</h3>
                                @if ($photo->category)
                                    <p class="text-gray-300 text-sm">{{ $photo->category->name }}</p>
                                @endif
                            </div>
                        </a>
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
                    <h3 class="mt-4 text-xl font-medium text-gray-400">No photos with this tag</h3>
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
</x-layouts.public>
