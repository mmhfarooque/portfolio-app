<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $gallery->name }}
            </h2>
            <a href="{{ route('admin.galleries.edit', $gallery) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Edit Gallery
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Gallery Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $gallery->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $gallery->is_published ? 'Published' : 'Draft' }}
                        </span>
                        @if ($gallery->is_featured)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                Featured
                            </span>
                        @endif
                    </div>
                    @if ($gallery->description)
                        <p class="mt-2 text-gray-600">{{ $gallery->description }}</p>
                    @endif
                </div>
            </div>

            <!-- Photos Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Photos in this Gallery</h3>

                    @if ($photos->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach ($photos as $photo)
                                <div class="relative group">
                                    <a href="{{ route('admin.photos.edit', $photo) }}" class="block">
                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                                            @if ($photo->thumbnail_path)
                                                <img src="{{ asset('storage/' . $photo->thumbnail_path) }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:opacity-75 transition">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    No Image
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                    <form method="POST" action="{{ route('admin.galleries.remove-photo', [$gallery, $photo]) }}" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white rounded-full p-1" title="Remove from gallery">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                    <p class="text-sm text-gray-900 truncate mt-2">{{ $photo->title }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $photos->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No photos in this gallery</h3>
                            <p class="mt-1 text-sm text-gray-500">Add photos to this gallery from the photo edit page.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
