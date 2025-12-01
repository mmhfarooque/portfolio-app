<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Theme Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="themePreview()">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Site Theme</h3>
                                <p class="text-sm text-gray-600 mt-1">Choose a theme for your portfolio. Click to preview and apply.</p>
                            </div>
                            <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Preview Site
                            </a>
                        </div>

                        <!-- Theme Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @foreach (config('themes.themes') as $themeKey => $theme)
                                <label class="cursor-pointer group" @click="selectTheme('{{ $themeKey }}')">
                                    <input type="radio" name="site_theme" value="{{ $themeKey }}" class="sr-only peer" {{ $currentTheme === $themeKey ? 'checked' : '' }}>
                                    <div class="relative bg-white rounded-2xl border-2 transition-all overflow-hidden peer-checked:border-blue-500 peer-checked:ring-4 peer-checked:ring-blue-100 hover:border-gray-300 border-gray-200 group-hover:shadow-xl shadow-sm">

                                        <!-- Theme Preview Area -->
                                        <div class="p-3">
                                            <div class="rounded-xl overflow-hidden p-3" style="background-color: {{ $theme['preview']['bg'] ?? $theme['colors']['bg-primary'] }};">
                                                <!-- Color blocks showing theme palette -->
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div class="aspect-[4/3] rounded-lg" style="background-color: {{ $theme['preview']['accent'] ?? $theme['colors']['accent'] }};"></div>
                                                    <div class="aspect-[4/3] rounded-lg" style="background-color: {{ $theme['colors']['bg-secondary'] ?? '#f5f5f5' }};"></div>
                                                    <div class="aspect-[4/3] rounded-lg" style="background-color: {{ $theme['colors']['bg-tertiary'] ?? '#eee' }};"></div>
                                                    <div class="aspect-[4/3] rounded-lg" style="background-color: {{ $theme['preview']['accent'] ?? $theme['colors']['accent'] }}; opacity: 0.6;"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Theme Info -->
                                        <div class="px-4 pb-4">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <h4 class="font-bold text-gray-900">{{ $theme['name'] }}</h4>
                                                    <p class="text-sm text-gray-500 mt-0.5">{{ $theme['description'] ?? '' }}</p>
                                                </div>
                                                @if ($theme['is_dark'] ?? false)
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-900 text-white">Dark</span>
                                                @else
                                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">Light</span>
                                                @endif
                                            </div>

                                            <!-- Color Swatches -->
                                            <div class="flex items-center gap-2 mt-3">
                                                <span class="text-xs text-gray-400 uppercase tracking-wide">Colors:</span>
                                                <div class="flex gap-1">
                                                    <div class="w-5 h-5 rounded-full ring-2 ring-white shadow" style="background-color: {{ $theme['preview']['bg'] ?? $theme['colors']['bg-primary'] }};" title="Background"></div>
                                                    <div class="w-5 h-5 rounded-full ring-2 ring-white shadow" style="background-color: {{ $theme['preview']['accent'] ?? $theme['colors']['accent'] }};" title="Accent"></div>
                                                    <div class="w-5 h-5 rounded-full ring-2 ring-white shadow" style="background-color: {{ $theme['preview']['text'] ?? $theme['colors']['text-primary'] }};" title="Text"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selected checkmark -->
                                        <div class="absolute top-4 right-4 w-6 h-6 rounded-full bg-blue-500 text-white flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <!-- Applied notification -->
                        <div x-show="justApplied" x-transition class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-green-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-sm font-medium">Theme applied successfully!</span>
                        </div>
                    </div>

                    <script>
                        const allThemes = @json(config('themes.themes'));
                    </script>
                </div>

                <!-- Branding Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Branding</h3>

                        <div class="space-y-4">
                            @foreach ($settings->get('branding', collect()) as $setting)
                                <div>
                                    @if ($setting->type === 'image')
                                        <x-media-picker
                                            name="{{ $setting->key }}"
                                            :label="ucwords(str_replace('_', ' ', str_replace(['site_', 'hero_'], '', $setting->key)))"
                                            :current-image="$setting->value ? asset('storage/' . $setting->value) : null"
                                            :value="$setting->value"
                                            preview-class="h-16 object-contain"
                                        />
                                    @else
                                        <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ ucwords(str_replace('_', ' ', str_replace(['site_', 'hero_'], '', $setting->key))) }}
                                        </label>
                                        @if ($setting->type === 'textarea')
                                            <textarea name="{{ $setting->key }}" id="{{ $setting->key }}" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $setting->value }}</textarea>
                                        @else
                                            <input type="text" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Social Media Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Social Media & Links</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($settings->get('social', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ ucwords(str_replace('social_', '', $setting->key)) }}
                                    </label>
                                    <input type="url" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" placeholder="https://..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Watermark Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Watermark</h3>

                        <div class="space-y-4">
                            @foreach ($settings->get('watermark', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ ucwords(str_replace('watermark_', '', $setting->key)) }}
                                    </label>

                                    @if ($setting->type === 'boolean')
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="{{ $setting->key }}" id="{{ $setting->key }}" value="1" {{ $setting->value ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">Enable watermarking</span>
                                        </label>
                                    @elseif ($setting->type === 'select' && $setting->key === 'watermark_position')
                                        <select name="{{ $setting->key }}" id="{{ $setting->key }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="bottom-right" {{ $setting->value === 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                                            <option value="bottom-left" {{ $setting->value === 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                                            <option value="top-right" {{ $setting->value === 'top-right' ? 'selected' : '' }}>Top Right</option>
                                            <option value="top-left" {{ $setting->value === 'top-left' ? 'selected' : '' }}>Top Left</option>
                                            <option value="center" {{ $setting->value === 'center' ? 'selected' : '' }}>Center</option>
                                        </select>
                                    @elseif ($setting->type === 'number')
                                        <input type="number" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" min="1" max="100" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @if (str_contains($setting->key, 'opacity'))
                                            <span class="text-sm text-gray-500 ml-2">%</span>
                                        @elseif (str_contains($setting->key, 'size'))
                                            <span class="text-sm text-gray-500 ml-2">px</span>
                                        @endif
                                    @else
                                        <input type="text" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Contact Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>

                        <div class="space-y-4">
                            @foreach ($settings->get('contact', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ ucwords(str_replace('contact_', '', $setting->key)) }}
                                    </label>
                                    <input type="{{ $setting->key === 'contact_email' ? 'email' : 'text' }}" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- About Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900">About Page</h3>
                            <p class="text-sm text-gray-500 mt-1">Design your about page with one of our editors</p>
                        </div>

                        <!-- Editor Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <!-- Editor.js Option (Simple) -->
                            <div class="border-2 border-blue-200 bg-blue-50 rounded-xl p-5 hover:border-blue-400 transition">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-semibold text-gray-900">Editor.js</h4>
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Recommended</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-3">
                                            Simple block-based editor. Easy to use, clean output.
                                        </p>
                                        <a href="{{ route('admin.about.editorjs') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Open Simple Editor
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- GrapesJS Option (Advanced) -->
                            <div class="border border-gray-200 bg-gray-50 rounded-xl p-5 hover:border-gray-300 transition">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-semibold text-gray-900">GrapesJS</h4>
                                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">Advanced</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-3">
                                            Full drag-and-drop page builder with style controls.
                                        </p>
                                        <a href="{{ route('admin.about.editor') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Open Advanced Editor
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legacy fields for About Image -->
                        <div class="border-t pt-6 space-y-4">
                            <p class="text-sm text-gray-500">Optional: Upload an image for the default about page layout (if not using editors)</p>
                            @foreach ($settings->get('about', collect()) as $setting)
                                @if ($setting->type === 'image')
                                <x-media-picker
                                    name="{{ $setting->key }}"
                                    :label="ucwords(str_replace('about_', '', $setting->key))"
                                    :current-image="$setting->value ? asset('storage/' . $setting->value) : null"
                                    :value="$setting->value"
                                    preview-class="h-32 object-contain rounded"
                                />
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- AI Image Analysis Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="aiSettings()">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">AI Image Analysis</h3>
                                <p class="text-sm text-gray-500 mt-1">Automatically generate titles and descriptions using AI</p>
                            </div>
                            <input type="hidden" name="ai_enabled" :value="enabled ? '1' : '0'">
                            <button type="button"
                                    @click="enabled = !enabled"
                                    :class="enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 hover:bg-gray-500'"
                                    class="px-4 py-2 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                                <svg x-show="enabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="!enabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span x-text="enabled ? 'Enabled' : 'Disabled'"></span>
                            </button>
                        </div>

                        <div x-show="enabled" x-transition class="space-y-6">
                            <!-- Provider Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">AI Provider</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <!-- Google AI -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="ai_provider" value="google" x-model="provider" class="sr-only peer">
                                        <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-300">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">Google AI</p>
                                                    <p class="text-xs text-gray-500">Gemini 1.5 Flash</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- OpenAI -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="ai_provider" value="openai" x-model="provider" class="sr-only peer">
                                        <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-300">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-black flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.833-3.387L15.119 7.2a.076.076 0 0 1 .071 0l4.83 2.791a4.494 4.494 0 0 1-.676 8.105v-5.678a.79.79 0 0 0-.407-.667zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.607 1.5v2.999l-2.597 1.5-2.607-1.5z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">OpenAI</p>
                                                    <p class="text-xs text-gray-500">GPT-4 Vision</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Claude -->
                                    <label class="cursor-pointer">
                                        <input type="radio" name="ai_provider" value="claude" x-model="provider" class="sr-only peer">
                                        <div class="p-4 border-2 rounded-lg transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-gray-300">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-400 to-amber-600 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">Anthropic</p>
                                                    <p class="text-xs text-gray-500">Claude 3.5 Sonnet</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- API Key Inputs -->
                            <div class="space-y-4">
                                <!-- Google AI API Key -->
                                <div x-show="provider === 'google'" x-transition>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Google AI API Key</label>
                                    <div class="flex gap-2">
                                        <input type="password" name="google_ai_api_key" id="google_ai_api_key"
                                               value="{{ App\Models\Setting::get('google_ai_api_key', '') }}"
                                               placeholder="Enter your Google AI Studio API key"
                                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <button type="button" @click="validateApiKey('google')"
                                                :disabled="validating"
                                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition flex items-center gap-2">
                                            <svg x-show="validating && validatingProvider === 'google'" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="validating && validatingProvider === 'google' ? 'Validating...' : 'Validate'"></span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline">Google AI Studio</a></p>
                                    <div x-show="validationResult.google" x-transition class="mt-2 p-3 rounded-lg text-sm" :class="validationResult.google.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        <span x-text="validationResult.google.message"></span>
                                    </div>
                                </div>

                                <!-- OpenAI API Key -->
                                <div x-show="provider === 'openai'" x-transition>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">OpenAI API Key</label>
                                    <div class="flex gap-2">
                                        <input type="password" name="openai_api_key" id="openai_api_key"
                                               value="{{ App\Models\Setting::get('openai_api_key', '') }}"
                                               placeholder="Enter your OpenAI API key"
                                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                        <button type="button" @click="validateApiKey('openai')"
                                                :disabled="validating"
                                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 transition flex items-center gap-2">
                                            <svg x-show="validating && validatingProvider === 'openai'" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="validating && validatingProvider === 'openai' ? 'Validating...' : 'Validate'"></span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:underline">OpenAI Platform</a></p>
                                    <div x-show="validationResult.openai" x-transition class="mt-2 p-3 rounded-lg text-sm" :class="validationResult.openai.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        <span x-text="validationResult.openai.message"></span>
                                    </div>
                                </div>

                                <!-- Claude API Key -->
                                <div x-show="provider === 'claude'" x-transition>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Claude API Key</label>
                                    <div class="flex gap-2">
                                        <input type="password" name="claude_api_key" id="claude_api_key"
                                               value="{{ App\Models\Setting::get('claude_api_key', '') }}"
                                               placeholder="Enter your Anthropic API key"
                                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        <button type="button" @click="validateApiKey('claude')"
                                                :disabled="validating"
                                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50 transition flex items-center gap-2">
                                            <svg x-show="validating && validatingProvider === 'claude'" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="validating && validatingProvider === 'claude' ? 'Validating...' : 'Validate'"></span>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Get your API key from <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-blue-600 hover:underline">Anthropic Console</a></p>
                                    <div x-show="validationResult.claude" x-transition class="mt-2 p-3 rounded-lg text-sm" :class="validationResult.claude.valid ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        <span x-text="validationResult.claude.message"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-generate Options -->
                            <div class="border-t pt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Auto-generate on Upload</h4>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="ai_auto_title" value="1" {{ App\Models\Setting::get('ai_auto_title', '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Generate creative titles</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="ai_auto_description" value="1" {{ App\Models\Setting::get('ai_auto_description', '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-600">Generate descriptions and stories</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex gap-3">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="text-sm text-blue-700">
                                        <p class="font-medium mb-1">How it works</p>
                                        <p>When you upload photos, AI will analyze the image along with EXIF data (camera settings, date) and location information to generate creative titles and descriptions. You can always edit the generated content.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Optimization Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Image Optimization</h3>
                        <p class="text-sm text-gray-500 mb-4">All images are automatically converted to WebP format for optimal file size and quality.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="image_max_resolution" class="block text-sm font-medium text-gray-700 mb-1">Max Resolution</label>
                                <select name="image_max_resolution" id="image_max_resolution" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1920" {{ App\Models\Setting::get('image_max_resolution', '2048') == '1920' ? 'selected' : '' }}>1920px (Full HD)</option>
                                    <option value="2048" {{ App\Models\Setting::get('image_max_resolution', '2048') == '2048' ? 'selected' : '' }}>2048px (2K - Recommended)</option>
                                    <option value="2560" {{ App\Models\Setting::get('image_max_resolution', '2048') == '2560' ? 'selected' : '' }}>2560px (QHD)</option>
                                    <option value="3840" {{ App\Models\Setting::get('image_max_resolution', '2048') == '3840' ? 'selected' : '' }}>3840px (4K)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Maximum width for display images. Smaller = smaller file size.</p>
                            </div>

                            <div>
                                <label for="image_quality" class="block text-sm font-medium text-gray-700 mb-1">Quality: <span id="quality-value">{{ App\Models\Setting::get('image_quality', '82') }}%</span></label>
                                <input type="range" name="image_quality" id="image_quality" min="60" max="95" value="{{ App\Models\Setting::get('image_quality', '82') }}" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer" oninput="document.getElementById('quality-value').textContent = this.value + '%'">
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>60% (Smaller)</span>
                                    <span>82% (Balanced)</span>
                                    <span>95% (Best)</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Re-optimize Existing Photos</h4>
                            <p class="text-xs text-blue-600 mb-3">Apply new settings to all existing photos. This will regenerate all image versions.</p>
                            <button type="button" onclick="reoptimizePhotos()" id="reoptimize-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Re-optimize All Photos
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function themePreview() {
            return {
                selectedTheme: '{{ $currentTheme }}',
                justApplied: false,

                selectTheme(themeKey) {
                    if (this.selectedTheme === themeKey) return;

                    this.selectedTheme = themeKey;

                    // Apply theme instantly via AJAX
                    this.applyThemeInstantly(themeKey);
                },

                async applyThemeInstantly(themeKey) {
                    try {
                        const response = await fetch('{{ route("admin.settings.update") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                _method: 'PUT',
                                site_theme: themeKey
                            })
                        });

                        // Show success notification
                        this.justApplied = true;
                        setTimeout(() => {
                            this.justApplied = false;
                        }, 3000);

                    } catch (error) {
                        console.error('Error applying theme:', error);
                    }
                }
            }
        }
    </script>

    <script>
        async function reoptimizePhotos() {
            if (!confirm('This will re-process all photos with the current settings. This may take a while. Continue?')) {
                return;
            }

            const btn = document.getElementById('reoptimize-btn');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;

            try {
                const response = await fetch('{{ route("admin.photos.reoptimize") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Successfully re-optimized ${data.count} photos!`);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during re-optimization.');
            }

            btn.disabled = false;
            btn.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Re-optimize All Photos
            `;
        }
    </script>

    <script>
        function aiSettings() {
            return {
                enabled: {{ App\Models\Setting::get('ai_enabled', '0') === '1' ? 'true' : 'false' }},
                provider: '{{ App\Models\Setting::get('ai_provider', 'google') }}',
                validating: false,
                validatingProvider: null,
                validationResult: {
                    google: null,
                    openai: null,
                    claude: null
                },

                async validateApiKey(provider) {
                    const inputId = {
                        google: 'google_ai_api_key',
                        openai: 'openai_api_key',
                        claude: 'claude_api_key'
                    }[provider];

                    const apiKey = document.getElementById(inputId).value;

                    if (!apiKey) {
                        this.validationResult[provider] = {
                            valid: false,
                            message: 'Please enter an API key first'
                        };
                        return;
                    }

                    this.validating = true;
                    this.validatingProvider = provider;
                    this.validationResult[provider] = null;

                    try {
                        const response = await fetch('{{ route("admin.settings.validate-ai-api") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                provider: provider,
                                api_key: apiKey
                            })
                        });

                        const data = await response.json();
                        this.validationResult[provider] = data;

                    } catch (error) {
                        console.error('Validation error:', error);
                        this.validationResult[provider] = {
                            valid: false,
                            message: 'Error validating API key: ' + error.message
                        };
                    }

                    this.validating = false;
                    this.validatingProvider = null;
                }
            }
        }
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
</x-app-layout>
