<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Translations</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Supported Languages -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Supported Languages</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach($locales as $code => $name)
                        <div class="px-4 py-2 bg-gray-100 rounded-lg">
                            <span class="font-medium">{{ $name }}</span>
                            <span class="text-gray-500 text-sm ml-1">({{ strtoupper($code) }})</span>
                            @if($code === config('app.fallback_locale', 'en'))
                                <span class="ml-2 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Default</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Photos -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Photo Translations</h3>
                </div>
                @if($photos->isEmpty())
                    <div class="p-6 text-center text-gray-500">No photos to translate.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photo</th>
                                @foreach($locales as $code => $name)
                                    @if($code !== config('app.fallback_locale', 'en'))
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ strtoupper($code) }}</th>
                                    @endif
                                @endforeach
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($photos as $photo)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($photo->title, 30) }}</div>
                                    </td>
                                    @foreach($locales as $code => $name)
                                        @if($code !== config('app.fallback_locale', 'en'))
                                            <td class="px-6 py-4 text-center">
                                                @php $pct = $photo->translation_status[$code] ?? 0; @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                                                    $pct === 100 ? 'bg-green-100 text-green-800' :
                                                    ($pct > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')
                                                }}">
                                                    {{ $pct }}%
                                                </span>
                                            </td>
                                        @endif
                                    @endforeach
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.translations.photo', $photo) }}" class="text-indigo-600 hover:text-indigo-900">Translate</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <!-- Posts -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Blog Post Translations</h3>
                </div>
                @if($posts->isEmpty())
                    <div class="p-6 text-center text-gray-500">No posts to translate.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Post</th>
                                @foreach($locales as $code => $name)
                                    @if($code !== config('app.fallback_locale', 'en'))
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ strtoupper($code) }}</th>
                                    @endif
                                @endforeach
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($posts as $post)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($post->title, 30) }}</div>
                                    </td>
                                    @foreach($locales as $code => $name)
                                        @if($code !== config('app.fallback_locale', 'en'))
                                            <td class="px-6 py-4 text-center">
                                                @php $pct = $post->translation_status[$code] ?? 0; @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{
                                                    $pct === 100 ? 'bg-green-100 text-green-800' :
                                                    ($pct > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')
                                                }}">
                                                    {{ $pct }}%
                                                </span>
                                            </td>
                                        @endif
                                    @endforeach
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <a href="{{ route('admin.translations.post', $post) }}" class="text-indigo-600 hover:text-indigo-900">Translate</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
