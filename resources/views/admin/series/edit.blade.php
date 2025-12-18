<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('admin.series.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Series: {{ $series->title }}</h2>
            </div>
            @if($series->status === 'published')
                <a href="{{ route('series.show', $series->slug) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                    View Series
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2">
                    <form action="{{ route('admin.series.update', $series) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                <input type="text" name="title" value="{{ old('title', $series->title) }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Slug -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                                <input type="text" name="slug" value="{{ old('slug', $series->slug) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea name="description" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $series->description) }}</textarea>
                            </div>

                            <!-- Story -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Story</label>
                                <textarea name="story" rows="6"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ old('story', $series->story) }}</textarea>
                            </div>

                            <!-- Project Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Project Date</label>
                                <input type="date" name="project_date" value="{{ old('project_date', $series->project_date?->format('Y-m-d')) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Location -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" name="location" value="{{ old('location', $series->location) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="draft" {{ old('status', $series->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="published" {{ old('status', $series->status) == 'published' ? 'selected' : '' }}>Published</option>
                                </select>
                            </div>

                            <!-- Featured -->
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $series->is_featured) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_featured" class="ml-2 block text-sm text-gray-900">Featured Series</label>
                            </div>

                            <!-- Cover Image -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                                @if($series->cover_image)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $series->cover_image) }}" alt="" class="h-32 rounded">
                                    </div>
                                @endif
                                <input type="file" name="cover_image" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- SEO Fields -->
                            <div class="md:col-span-2 border-t pt-6 mt-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Title</label>
                                <input type="text" name="seo_title" value="{{ old('seo_title', $series->seo_title) }}" maxlength="60"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                                <textarea name="meta_description" rows="2" maxlength="160"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ old('meta_description', $series->meta_description) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Photos in Series -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Photos in Series ({{ $series->photos->count() }})</h3>

                        @if($series->photos->count() > 0)
                            <div class="space-y-2 mb-6" id="sortable-photos">
                                @foreach($series->photos as $photo)
                                    <div class="flex items-center gap-3 p-2 bg-gray-50 rounded" data-id="{{ $photo->id }}">
                                        <img src="{{ $photo->thumbnail_url }}" alt="" class="h-12 w-12 object-cover rounded">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $photo->title }}</p>
                                        </div>
                                        <form action="{{ route('admin.series.remove-photo', [$series, $photo]) }}" method="POST" class="flex-shrink-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Remove this photo?')">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm mb-4">No photos in this series yet.</p>
                        @endif

                        <!-- Add Photos Form -->
                        <form action="{{ route('admin.series.add-photos', $series) }}" method="POST">
                            @csrf
                            <label class="block text-sm font-medium text-gray-700 mb-2">Add Photos</label>
                            <select name="photo_ids[]" multiple size="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                @foreach($availablePhotos as $photo)
                                    <option value="{{ $photo->id }}">{{ $photo->title }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                            <button type="submit" class="mt-3 w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">
                                Add Selected Photos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
