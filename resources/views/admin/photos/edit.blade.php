<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Photo') }}
            </h2>
            <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Are you sure you want to delete this photo?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Delete Photo
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Photo Preview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="aspect-[4/3] bg-gray-200 rounded-lg overflow-hidden">
                            @if ($photo->display_path)
                                <img src="{{ asset('storage/' . $photo->display_path) }}" alt="{{ $photo->title }}" class="w-full h-full object-contain">
                            @endif
                        </div>

                        <!-- EXIF Data -->
                        @if ($photo->exif_data)
                            <div class="mt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">EXIF Data</h3>
                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                    @php $exif = $photo->formatted_exif; @endphp
                                    @if ($exif['camera'])
                                        <dt class="text-gray-500">Camera</dt>
                                        <dd class="text-gray-900">{{ $exif['camera'] }}</dd>
                                    @endif
                                    @if ($exif['aperture'])
                                        <dt class="text-gray-500">Aperture</dt>
                                        <dd class="text-gray-900">{{ $exif['aperture'] }}</dd>
                                    @endif
                                    @if ($exif['shutter_speed'])
                                        <dt class="text-gray-500">Shutter Speed</dt>
                                        <dd class="text-gray-900">{{ $exif['shutter_speed'] }}</dd>
                                    @endif
                                    @if ($exif['iso'])
                                        <dt class="text-gray-500">ISO</dt>
                                        <dd class="text-gray-900">{{ $exif['iso'] }}</dd>
                                    @endif
                                    @if ($exif['focal_length'])
                                        <dt class="text-gray-500">Focal Length</dt>
                                        <dd class="text-gray-900">{{ $exif['focal_length'] }}</dd>
                                    @endif
                                    @if ($exif['date_taken'])
                                        <dt class="text-gray-500">Date Taken</dt>
                                        <dd class="text-gray-900">{{ $exif['date_taken'] }}</dd>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        <!-- File Info -->
                        <div class="mt-6 border-t pt-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">File Information</h3>
                            <dl class="grid grid-cols-2 gap-2 text-sm">
                                <dt class="text-gray-500">Dimensions</dt>
                                <dd class="text-gray-900">{{ $photo->width }} x {{ $photo->height }}</dd>
                                <dt class="text-gray-500">File Size</dt>
                                <dd class="text-gray-900">{{ number_format($photo->file_size / 1024 / 1024, 2) }} MB</dd>
                                <dt class="text-gray-500">Views</dt>
                                <dd class="text-gray-900">{{ number_format($photo->views) }}</dd>
                                <dt class="text-gray-500">Uploaded</dt>
                                <dd class="text-gray-900">{{ $photo->created_at->format('M j, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        @if ($errors->any())
                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.photos.update', $photo) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" id="title" value="{{ old('title', $photo->title) }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                                <textarea name="description" id="description" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Brief description for galleries and search">{{ old('description', $photo->description) }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label for="story" class="block text-sm font-medium text-gray-700 mb-2">
                                    Story & Thoughts
                                    <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <textarea name="story" id="story" rows="8" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Share the story behind this photo, your thoughts, memories, and what makes it special to you...">{{ old('story', $photo->story) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">This will be displayed on the photo's detail page as a blog-style story.</p>
                            </div>

                            <div class="mb-4">
                                <label for="location_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Location Name
                                    <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <input type="text" name="location_name" id="location_name" value="{{ old('location_name', $photo->location_name) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Yosemite National Park, California">
                                @if ($photo->hasLocation())
                                    <p class="mt-1 text-xs text-green-600">
                                        <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        GPS: {{ number_format($photo->latitude, 6) }}, {{ number_format($photo->longitude, 6) }}
                                    </p>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select name="category_id" id="category_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">No Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $photo->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="gallery_id" class="block text-sm font-medium text-gray-700 mb-2">Gallery</label>
                                    <select name="gallery_id" id="gallery_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">No Gallery</option>
                                        @foreach ($galleries as $gallery)
                                            <option value="{{ $gallery->id }}" {{ old('gallery_id', $photo->gallery_id) == $gallery->id ? 'selected' : '' }}>
                                                {{ $gallery->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="draft" {{ old('status', $photo->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="published" {{ old('status', $photo->status) === 'published' ? 'selected' : '' }}>Published</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($tags as $tag)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $photo->tags->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">{{ $tag->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $photo->is_featured) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Featured Photo</span>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <a href="{{ route('admin.photos.index') }}" class="text-gray-600 hover:text-gray-800">
                                    Back to Photos
                                </a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
