<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.translations.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Translate Photo: {{ $photo->title }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('admin.translations.photo.update', $photo) }}" method="POST">
                @csrf
                @method('PUT')

                <div x-data="{ activeTab: 'es' }" class="bg-white rounded-lg shadow">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            @foreach($locales as $code => $name)
                                @if($code !== config('app.fallback_locale', 'en'))
                                    <button type="button" @click="activeTab = '{{ $code }}'"
                                            :class="activeTab === '{{ $code }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                            class="py-4 px-6 border-b-2 font-medium text-sm">
                                        {{ $name }}
                                    </button>
                                @endif
                            @endforeach
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    @foreach($locales as $code => $name)
                        @if($code !== config('app.fallback_locale', 'en'))
                            <div x-show="activeTab === '{{ $code }}'" class="p-6 space-y-6">
                                <!-- Original Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Original Title (English)</label>
                                    <div class="px-3 py-2 bg-gray-50 rounded-lg text-gray-700">{{ $photo->title }}</div>
                                </div>
                                <!-- Translated Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title ({{ $name }})</label>
                                    <input type="text" name="translations[{{ $code }}][title]"
                                           value="{{ $translations[$code]['title'] ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <!-- Original Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Original Description (English)</label>
                                    <div class="px-3 py-2 bg-gray-50 rounded-lg text-gray-700 text-sm">{{ $photo->description ?? 'No description' }}</div>
                                </div>
                                <!-- Translated Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description ({{ $name }})</label>
                                    <textarea name="translations[{{ $code }}][description]" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">{{ $translations[$code]['description'] ?? '' }}</textarea>
                                </div>

                                <!-- Original Meta Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-1">Original Meta Description (English)</label>
                                    <div class="px-3 py-2 bg-gray-50 rounded-lg text-gray-700 text-sm">{{ $photo->meta_description ?? 'No meta description' }}</div>
                                </div>
                                <!-- Translated Meta Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description ({{ $name }})</label>
                                    <input type="text" name="translations[{{ $code }}][meta_description]"
                                           value="{{ $translations[$code]['meta_description'] ?? '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                    <p class="mt-1 text-xs text-gray-500">Keep under 160 characters for SEO</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('admin.translations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Save Translations
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
