<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Photo') }}
            </h2>
            <div class="flex items-center gap-3">
                <!-- Save Button (icon) -->
                <button type="button"
                        onclick="document.getElementById('photo-edit-form').submit()"
                        class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition shadow-sm"
                        title="Save Changes">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
                <!-- Delete Button (icon) -->
                <form method="POST" action="{{ route('admin.photos.destroy', $photo) }}" onsubmit="return confirm('Are you sure you want to delete this photo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center w-10 h-10 bg-red-600 hover:bg-red-700 text-white rounded-lg transition shadow-sm"
                            title="Delete Photo">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
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

                        <form method="POST" action="{{ route('admin.photos.update', $photo) }}" enctype="multipart/form-data" id="photo-edit-form">
                            @csrf
                            @method('PUT')

                            <div class="mb-4" x-data="{
                                title: '{{ old('title', addslashes($photo->title)) }}',
                                validating: false,
                                validation: null,
                                debounceTimer: null,

                                onTitleInput() {
                                    clearTimeout(this.debounceTimer);
                                    this.debounceTimer = setTimeout(() => this.validateTitle(), 500);
                                },

                                async validateTitle() {
                                    if (!this.title || this.title.length < 3) {
                                        this.validation = null;
                                        return;
                                    }

                                    this.validating = true;
                                    try {
                                        const response = await fetch('{{ route('admin.photos.validate-title') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                title: this.title,
                                                exclude_id: {{ $photo->id }}
                                            })
                                        });
                                        this.validation = await response.json();
                                    } catch (error) {
                                        console.error('Validation failed:', error);
                                    }
                                    this.validating = false;
                                }
                            }" x-init="validateTitle()">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <div class="relative">
                                    <input type="text"
                                           name="title"
                                           id="title"
                                           x-model="title"
                                           @input="onTitleInput()"
                                           required
                                           :class="validation && !validation.valid ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                           class="w-full pr-10 rounded-md shadow-sm">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center">
                                        <span x-show="validating" class="text-gray-400">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                        <span x-show="!validating && validation && validation.valid && (!validation.similar || validation.similar.length === 0)" class="text-green-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        <span x-show="!validating && validation && !validation.valid" class="text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>

                                <!-- Duplicate Error -->
                                <template x-if="validation && !validation.valid">
                                    <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p class="text-sm text-red-700 font-medium" x-text="validation.error"></p>
                                        <p class="text-xs text-red-600 mt-1">
                                            Existing photo: "<span x-text="validation.existing?.title"></span>"
                                            (/<span x-text="validation.existing?.slug"></span>)
                                        </p>
                                    </div>
                                </template>

                                <!-- Similar Titles Warning -->
                                <template x-if="validation && validation.valid && validation.similar && validation.similar.length > 0">
                                    <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <p class="text-sm text-yellow-700 font-medium">Similar titles already exist:</p>
                                        <ul class="mt-1 text-xs text-yellow-600 space-y-1">
                                            <template x-for="item in validation.similar" :key="item.slug">
                                                <li>
                                                    "<span x-text="item.title"></span>" -
                                                    <span x-text="item.similarity + '%'" class="font-medium"></span> similar
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>

                            <div class="mb-4" x-data="{
                                slug: '{{ old('slug', $photo->slug) }}',
                                generating: false,
                                validating: false,
                                validation: null,
                                debounceTimer: null,

                                async suggestSlug() {
                                    this.generating = true;
                                    try {
                                        const response = await fetch('{{ route('admin.photos.suggest-slug', $photo) }}');
                                        const data = await response.json();
                                        if (data.slug) {
                                            this.slug = data.slug;
                                            this.validateSlug();
                                        }
                                    } catch (error) {
                                        console.error('Failed to get AI suggestion:', error);
                                    }
                                    this.generating = false;
                                },

                                onSlugInput() {
                                    // Auto-format: lowercase and hyphens only
                                    this.slug = this.slug.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');

                                    // Debounce validation
                                    clearTimeout(this.debounceTimer);
                                    this.debounceTimer = setTimeout(() => this.validateSlug(), 500);
                                },

                                async validateSlug() {
                                    if (!this.slug || this.slug.length < 3) {
                                        this.validation = null;
                                        return;
                                    }

                                    this.validating = true;
                                    try {
                                        const response = await fetch('{{ route('admin.photos.validate-slug') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                slug: this.slug,
                                                exclude_id: {{ $photo->id }}
                                            })
                                        });
                                        this.validation = await response.json();
                                    } catch (error) {
                                        console.error('Validation failed:', error);
                                    }
                                    this.validating = false;
                                },

                                useSuggestion(suggestion) {
                                    this.slug = suggestion;
                                    this.validateSlug();
                                }
                            }" x-init="validateSlug()">
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                                    URL Slug
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           name="slug"
                                           id="slug"
                                           x-model="slug"
                                           @input="onSlugInput()"
                                           required
                                           pattern="[a-z0-9-]+"
                                           :class="validation && !validation.valid ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                           class="w-full pr-20 rounded-md shadow-sm"
                                           placeholder="my-photo-name">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <span x-show="validating" class="text-gray-400">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                        <span x-show="!validating && validation && validation.valid && (!validation.similar || validation.similar.length === 0)" class="text-green-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        <span x-show="!validating && validation && !validation.valid" class="text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </span>
                                        <button type="button"
                                                @click="suggestSlug()"
                                                :disabled="generating"
                                                class="p-1.5 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded transition disabled:opacity-50"
                                                title="Generate with AI">
                                            <svg x-show="!generating" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2Z"/>
                                            </svg>
                                            <svg x-show="generating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    URL: {{ url('/photo') }}/<span x-text="slug || 'your-slug-here'" class="font-medium text-gray-700"></span>
                                </p>

                                <!-- Duplicate Error -->
                                <template x-if="validation && !validation.valid">
                                    <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p class="text-sm text-red-700 font-medium" x-text="validation.error"></p>
                                        <p class="text-xs text-red-600 mt-1">
                                            Existing: "<span x-text="validation.existing?.title"></span>"
                                            (<span x-text="validation.existing?.slug"></span>)
                                        </p>
                                        <button type="button"
                                                @click="useSuggestion(validation.suggestion)"
                                                class="mt-2 text-xs bg-red-100 hover:bg-red-200 text-red-800 px-2 py-1 rounded transition">
                                            Use suggested: <span x-text="validation.suggestion" class="font-mono"></span>
                                        </button>
                                    </div>
                                </template>

                                <!-- Similar Slugs Warning -->
                                <template x-if="validation && validation.valid && validation.similar && validation.similar.length > 0">
                                    <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                        <p class="text-sm text-yellow-700 font-medium">Similar URLs already exist:</p>
                                        <ul class="mt-1 text-xs text-yellow-600 space-y-1">
                                            <template x-for="item in validation.similar" :key="item.slug">
                                                <li>
                                                    <span x-text="item.title"></span>
                                                    (<span x-text="item.slug" class="font-mono"></span>) -
                                                    <span x-text="item.similarity + '%'" class="font-medium"></span> similar
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                @error('slug')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-4" x-data="{
                                description: `{{ old('description', addslashes($photo->description ?? '')) }}`,
                                generating: false,

                                async generateDescription() {
                                    this.generating = true;
                                    try {
                                        const response = await fetch('{{ route('admin.photos.suggest-slug', $photo) }}?type=description');
                                        const data = await response.json();
                                        if (data.description) {
                                            this.description = data.description;
                                        }
                                    } catch (error) {
                                        console.error('Failed to generate description:', error);
                                    }
                                    this.generating = false;
                                }
                            }">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                                <div class="relative">
                                    <textarea name="description" id="description" rows="2" x-model="description" class="w-full pr-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Brief description for galleries and search"></textarea>
                                    <button type="button"
                                            @click="generateDescription()"
                                            :disabled="generating"
                                            class="absolute right-2 top-2 p-1.5 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded transition disabled:opacity-50"
                                            title="Generate with AI">
                                        <svg x-show="!generating" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2Z"/>
                                        </svg>
                                        <svg x-show="generating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4" x-data="{
                                story: `{{ old('story', addslashes($photo->story ?? '')) }}`,
                                generating: false,

                                async generateStory() {
                                    this.generating = true;
                                    try {
                                        const response = await fetch('{{ route('admin.photos.suggest-slug', $photo) }}?type=story');
                                        const data = await response.json();
                                        if (data.story) {
                                            this.story = data.story;
                                        }
                                    } catch (error) {
                                        console.error('Failed to generate story:', error);
                                    }
                                    this.generating = false;
                                }
                            }">
                                <label for="story" class="block text-sm font-medium text-gray-700 mb-2">
                                    Story & Thoughts
                                    <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <div class="relative">
                                    <textarea name="story" id="story" rows="8" x-model="story" class="w-full pr-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Share the story behind this photo, your thoughts, memories, and what makes it special to you..."></textarea>
                                    <button type="button"
                                            @click="generateStory()"
                                            :disabled="generating"
                                            class="absolute right-2 top-2 p-1.5 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded transition disabled:opacity-50"
                                            title="Generate with AI">
                                        <svg x-show="!generating" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2Z"/>
                                        </svg>
                                        <svg x-show="generating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </div>
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

                            <!-- SEO Section -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    SEO Settings
                                </h3>

                                <div class="mb-4">
                                    <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-2">
                                        SEO Title
                                        <span class="text-gray-400 font-normal">(optional - defaults to photo title)</span>
                                    </label>
                                    <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title', $photo->seo_title) }}" maxlength="70" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Custom title for search engines (max 70 chars)">
                                    <p class="mt-1 text-xs text-gray-500">Leave blank to use the photo title</p>
                                </div>

                                <div class="mb-0" x-data="{
                                    metaDescription: `{{ old('meta_description', addslashes($photo->meta_description ?? '')) }}`,
                                    generating: false,

                                    async generateMetaDescription() {
                                        this.generating = true;
                                        try {
                                            const response = await fetch('{{ route('admin.photos.suggest-slug', $photo) }}?type=meta_description');
                                            const data = await response.json();
                                            if (data.meta_description) {
                                                this.metaDescription = data.meta_description;
                                            }
                                        } catch (error) {
                                            console.error('Failed to generate meta description:', error);
                                        }
                                        this.generating = false;
                                    }
                                }">
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Meta Description
                                        <span class="text-gray-400 font-normal">(optional - defaults to description)</span>
                                    </label>
                                    <div class="relative">
                                        <textarea name="meta_description" id="meta_description" rows="2" maxlength="160" x-model="metaDescription" class="w-full pr-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Custom description for search results (max 160 chars)"></textarea>
                                        <button type="button"
                                                @click="generateMetaDescription()"
                                                :disabled="generating"
                                                class="absolute right-2 top-2 p-1.5 text-purple-500 hover:text-purple-700 hover:bg-purple-50 rounded transition disabled:opacity-50"
                                                title="Generate with AI">
                                            <svg x-show="!generating" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2L9.19 8.63L2 9.24L7.46 13.97L5.82 21L12 17.27L18.18 21L16.54 13.97L22 9.24L14.81 8.63L12 2Z"/>
                                            </svg>
                                            <svg x-show="generating" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Leave blank to use the short description</p>
                                </div>
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
