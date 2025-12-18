<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.social.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Social Post</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('admin.social.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Select Photo -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Select Photo</h3>

                    @if($selectedPhoto)
                        <input type="hidden" name="photo_id" value="{{ $selectedPhoto->id }}">
                        <div class="flex items-center gap-4">
                            <img src="{{ $selectedPhoto->getImageUrl('medium') }}" alt="" class="w-32 h-32 rounded-lg object-cover">
                            <div>
                                <div class="font-medium text-gray-900">{{ $selectedPhoto->title }}</div>
                                <a href="{{ route('admin.social.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Change photo</a>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2" x-data="{ selected: null }">
                            @foreach($photos as $photo)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="photo_id" value="{{ $photo->id }}" class="sr-only peer" x-model="selected">
                                    <img src="{{ $photo->getImageUrl('thumbnail') }}" alt="{{ $photo->title }}"
                                         class="w-full aspect-square object-cover rounded-lg border-2 border-transparent peer-checked:border-indigo-600 hover:opacity-80 transition">
                                    <div class="absolute inset-0 rounded-lg peer-checked:ring-2 peer-checked:ring-indigo-600 peer-checked:ring-offset-2"></div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('photo_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Select Platforms -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Select Platforms</h3>
                    <div class="space-y-3">
                        @foreach($platforms as $key => $platform)
                            <label class="flex items-center gap-3 p-3 border rounded-lg {{ $platform['connected'] ? 'cursor-pointer hover:bg-gray-50' : 'opacity-50 cursor-not-allowed' }}">
                                <input type="checkbox" name="platforms[]" value="{{ $key }}"
                                       {{ $platform['connected'] ? '' : 'disabled' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <span class="flex-1">
                                    <span class="font-medium text-gray-900">{{ $platform['name'] }}</span>
                                    @if(!$platform['connected'])
                                        <span class="text-sm text-gray-500 ml-2">(Not connected)</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('platforms')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Caption -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Caption</h3>
                    <textarea name="caption" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Write your caption... (leave empty to auto-generate)">{{ old('caption') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Max 2200 characters for Instagram compatibility</p>
                </div>

                <!-- Hashtags -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Hashtags</h3>
                    <input type="text" name="hashtags" value="{{ old('hashtags') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="photography, landscape, nature (comma-separated, # added automatically)">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate from photo tags</p>
                </div>

                <!-- Schedule -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule</h3>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="schedule_type" value="now" checked class="text-indigo-600 focus:ring-indigo-500">
                            <span>Post immediately</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="schedule_type" value="scheduled" class="text-indigo-600 focus:ring-indigo-500">
                            <span>Schedule for later</span>
                        </label>
                    </div>
                    <div class="mt-4" x-data="{ scheduled: false }" x-show="scheduled">
                        <input type="datetime-local" name="schedule_at"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.social.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Create Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
