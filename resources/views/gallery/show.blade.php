<x-layouts.public>
    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Photography Portfolio'));
        $photographerName = \App\Models\Setting::get('photographer_name', \App\Models\Setting::get('profile_name', $siteName));
        $seoTitle = $photo->seo_title ?: $photo->title;
        $metaDescription = $photo->meta_description ?: $photo->description ?: "View {$photo->title} - a beautiful photograph from our gallery.";
        $imageUrl = url('storage/' . $photo->display_path);
        $canonicalUrl = route('photos.show', $photo);

        // Build JSON-LD Schema
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
            'name' => $photo->title,
            'description' => $metaDescription,
            'contentUrl' => $imageUrl,
            'thumbnailUrl' => url('storage/' . $photo->thumbnail_path),
            'url' => $canonicalUrl,
            'width' => $photo->width,
            'height' => $photo->height,
            'author' => [
                '@type' => 'Person',
                'name' => $photographerName,
            ],
            'copyrightHolder' => [
                '@type' => 'Person',
                'name' => $photographerName,
            ],
        ];

        if ($photo->captured_at) {
            $schema['dateCreated'] = $photo->captured_at->toIso8601String();
            $schema['datePublished'] = $photo->created_at->toIso8601String();
        }

        if ($photo->location_name) {
            $schema['contentLocation'] = [
                '@type' => 'Place',
                'name' => $photo->location_name,
            ];
            if ($photo->hasLocation()) {
                $schema['contentLocation']['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $photo->latitude,
                    'longitude' => $photo->longitude,
                ];
            }
        }

        if ($photo->category) {
            $schema['genre'] = $photo->category->name;
        }

        // Camera info
        $exif = $photo->formatted_exif;
        if ($exif['camera']) {
            $schema['exifData'] = [];
            if ($exif['camera']) $schema['exifData'][] = ['@type' => 'PropertyValue', 'name' => 'Camera', 'value' => $exif['camera']];
            if ($exif['aperture']) $schema['exifData'][] = ['@type' => 'PropertyValue', 'name' => 'Aperture', 'value' => $exif['aperture']];
            if ($exif['shutter_speed']) $schema['exifData'][] = ['@type' => 'PropertyValue', 'name' => 'Shutter Speed', 'value' => $exif['shutter_speed']];
            if ($exif['iso']) $schema['exifData'][] = ['@type' => 'PropertyValue', 'name' => 'ISO', 'value' => $exif['iso']];
            if ($exif['focal_length']) $schema['exifData'][] = ['@type' => 'PropertyValue', 'name' => 'Focal Length', 'value' => $exif['focal_length']];
        }

        // Keywords from tags
        if ($photo->tags->count() > 0) {
            $schema['keywords'] = $photo->tags->pluck('name')->implode(', ');
        }

        // Breadcrumb Schema
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Photos',
                    'item' => route('photos.index'),
                ],
            ],
        ];

        if ($photo->category) {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $photo->category->name,
                'item' => route('category.show', $photo->category),
            ];
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 4,
                'name' => $photo->title,
                'item' => $canonicalUrl,
            ];
        } else {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $photo->title,
                'item' => $canonicalUrl,
            ];
        }
    @endphp

    <x-slot name="pageTitle">{{ $seoTitle }} - {{ $siteName }}</x-slot>

    <x-slot name="seo">
        <x-seo-meta
            :title="$seoTitle"
            :description="$metaDescription"
            :image="$imageUrl"
            :image-width="$photo->width"
            :image-height="$photo->height"
            :url="$canonicalUrl"
            :article="true"
            :published-time="$photo->created_at->toIso8601String()"
            :modified-time="$photo->updated_at->toIso8601String()"
            :author="$photographerName"
            :keywords="$photo->tags->pluck('name')->implode(', ')"
        />

        {{-- JSON-LD Structured Data --}}
        <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
        <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    </x-slot>

    @if ($photo->hasLocation())
    <x-slot name="head">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    </x-slot>
    @endif

    <div class="min-h-screen">
        <!-- Navigation Bar -->
        <div class="bg-theme-secondary border-b border-theme">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <!-- Back to Gallery -->
                    <a href="{{ route('photos.index') }}" class="inline-flex items-center gap-2 text-theme-secondary hover:text-theme-primary transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Back to Gallery</span>
                    </a>

                    <!-- Prev/Next Navigation -->
                    <div class="flex items-center gap-4">
                        @if ($previousPhoto)
                            <a href="{{ route('photos.show', $previousPhoto) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-theme-tertiary hover:bg-theme-hover text-theme-secondary hover:text-theme-primary transition" title="{{ $previousPhoto->title }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span class="hidden sm:inline">Previous</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-theme-tertiary text-theme-muted opacity-50 cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span class="hidden sm:inline">Previous</span>
                            </span>
                        @endif

                        @if ($nextPhoto)
                            <a href="{{ route('photos.show', $nextPhoto) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-theme-tertiary hover:bg-theme-hover text-theme-secondary hover:text-theme-primary transition" title="{{ $nextPhoto->title }}">
                                <span class="hidden sm:inline">Next</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-theme-tertiary text-theme-muted opacity-50 cursor-not-allowed">
                                <span class="hidden sm:inline">Next</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Display -->
        <div class="relative bg-black" x-data="imageModal()">
            <div class="max-w-7xl mx-auto">
                <div class="relative aspect-[16/10] md:aspect-[16/9]">
                    <img src="{{ $photo->primary_url }}"
                         alt="{{ $photo->title }}"
                         class="w-full h-full object-contain cursor-zoom-in"
                         id="main-photo"
                         @click="openModal()"
                         data-full="{{ $photo->primary_url }}">
                </div>
            </div>

            <!-- Image Modal (Popup) -->
            <div x-show="isOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="closeModal()"
                 @keydown.left.window="navigatePrev()"
                 @keydown.right.window="navigateNext()"
                 class="fixed inset-0 z-50 flex items-center justify-center"
                 style="display: none;">

                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/95" @click="closeModal()"></div>

                <!-- Modal Content -->
                <div class="relative z-10 w-full h-full flex items-center justify-center p-4 md:p-8">
                    <!-- Close Button -->
                    <button @click="closeModal()"
                            class="absolute top-4 right-4 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <!-- Previous Button -->
                    @if ($previousPhoto)
                    <a href="{{ route('photos.show', $previousPhoto) }}"
                       class="absolute left-4 z-20 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    @endif

                    <!-- Next Button -->
                    @if ($nextPhoto)
                    <a href="{{ route('photos.show', $nextPhoto) }}"
                       class="absolute right-4 z-20 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @endif

                    <!-- Image Container with Zoom -->
                    <div class="max-w-full max-h-full overflow-auto"
                         :class="{ 'cursor-zoom-out': isZoomed, 'cursor-zoom-in': !isZoomed }"
                         @click="toggleZoom($event)">
                        <img :src="imageSrc"
                             :alt="imageAlt"
                             class="max-w-none transition-transform duration-200"
                             :style="isZoomed ? 'transform: scale(1.5)' : 'transform: scale(1); max-width: 90vw; max-height: 90vh; object-fit: contain;'"
                             @load="imageLoaded = true">
                    </div>

                    <!-- Photo Title -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 text-center">
                        <h3 class="text-white text-lg font-medium bg-black/50 px-4 py-2 rounded-lg">
                            {{ $photo->title }}
                        </h3>
                        <p class="text-white/70 text-sm mt-1">Press ESC to close, Arrow keys to navigate</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function imageModal() {
                return {
                    isOpen: false,
                    isZoomed: false,
                    imageLoaded: false,
                    imageSrc: '{{ $photo->primary_url }}',
                    imageAlt: '{{ $photo->title }}',

                    openModal() {
                        this.isOpen = true;
                        this.isZoomed = false;
                        document.body.style.overflow = 'hidden';
                    },

                    closeModal() {
                        this.isOpen = false;
                        this.isZoomed = false;
                        document.body.style.overflow = '';
                    },

                    toggleZoom(event) {
                        if (event.target.tagName === 'IMG') {
                            this.isZoomed = !this.isZoomed;
                        }
                    },

                    navigatePrev() {
                        if (this.isOpen) {
                            @if ($previousPhoto)
                            window.location.href = '{{ route('photos.show', $previousPhoto) }}';
                            @endif
                        }
                    },

                    navigateNext() {
                        if (this.isOpen) {
                            @if ($nextPhoto)
                            window.location.href = '{{ route('photos.show', $nextPhoto) }}';
                            @endif
                        }
                    }
                }
            }
        </script>

        <!-- Before/After Comparison Slider -->
        @if ($photo->hasBeforeImage())
            <div class="max-w-7xl mx-auto px-4 py-8">
                <h2 class="text-xl font-semibold mb-4 flex items-center text-theme-primary">
                    <svg class="w-5 h-5 mr-2 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                    Before & After Comparison
                </h2>
                <x-before-after-slider
                    :before-image="$photo->before_display_url"
                    :after-image="$photo->primary_url"
                    before-label="Before"
                    after-label="After"
                />
            </div>
        @endif

        <!-- Photo Info -->
        <div class="max-w-4xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Main Info -->
                <div class="md:col-span-2">
                    <h1 class="text-3xl font-bold mb-4 text-theme-primary">{{ $photo->title }}</h1>

                    @if ($photo->description)
                        <p class="text-theme-secondary mb-6">{{ $photo->description }}</p>
                    @endif

                    <!-- Location -->
                    @if ($photo->location_name || $photo->hasLocation())
                        <div class="flex items-center text-theme-secondary mb-6">
                            <svg class="w-5 h-5 mr-2 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ $photo->location_name ?? 'View on Map' }}</span>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-4 mb-6">
                        @if ($photo->category)
                            <a href="{{ route('category.show', $photo->category) }}" class="inline-flex items-center text-sm text-theme-accent hover:opacity-80">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ $photo->category->name }}
                            </a>
                        @endif

                        @if ($photo->gallery && $photo->gallery->is_published)
                            <a href="{{ route('gallery.show', $photo->gallery) }}" class="inline-flex items-center text-sm text-theme-accent hover:opacity-80">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                {{ $photo->gallery->name }}
                            </a>
                        @endif

                        @if ($photo->captured_at)
                            <span class="inline-flex items-center text-sm text-theme-muted">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $photo->captured_at->format('F j, Y') }}
                            </span>
                        @endif
                    </div>

                    @if ($photo->tags->count() > 0)
                        <div class="flex flex-wrap gap-2 mb-8">
                            @foreach ($photo->tags as $tag)
                                <a href="{{ route('tag.show', $tag) }}" class="px-3 py-1 rounded-full text-xs border border-theme text-theme-secondary hover:border-theme-accent hover:text-theme-accent transition">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <!-- Story / Blog Content -->
                    @if ($photo->story)
                        <div class="mt-8 pt-8 border-t border-theme">
                            <h2 class="text-xl font-semibold mb-4 flex items-center text-theme-primary">
                                <svg class="w-5 h-5 mr-2 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                The Story Behind This Photo
                            </h2>
                            <div class="prose prose-lg max-w-none text-theme-secondary">
                                {!! nl2br(e($photo->story)) !!}
                            </div>
                        </div>
                    @endif

                    <!-- Map -->
                    @if ($photo->hasLocation())
                        <div class="mt-8 pt-8 border-t border-theme">
                            <h2 class="text-xl font-semibold mb-4 flex items-center text-theme-primary">
                                <svg class="w-5 h-5 mr-2 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Where This Was Taken
                            </h2>
                            <div id="photo-map" class="h-64 rounded-lg overflow-hidden"></div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6" x-data="photoSelection({{ $photo->id }}, {{ $photo->gallery_id ?? 'null' }})">
                    <!-- Add to Selections Button -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6">
                        <button @click="toggleSelection()"
                                :class="isSelected ? 'bg-red-500 text-white border-red-500' : 'border-theme-accent text-theme-accent hover:bg-theme-accent hover:text-white'"
                                class="w-full flex items-center justify-center gap-2 py-3 px-4 border-2 rounded-lg font-medium transition">
                            <svg class="w-5 h-5" :fill="isSelected ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span x-text="isSelected ? 'Remove from Selections' : 'Add to Selections'"></span>
                        </button>

                        <template x-if="selectionCount > 0">
                            <a href="{{ route('client.selections') }}"
                               class="mt-3 flex items-center justify-center gap-2 text-sm text-theme-accent hover:underline">
                                <span x-text="'View ' + selectionCount + ' selected photos'"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </template>
                    </div>

                    <!-- Share Buttons -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4 text-theme-primary">Share This Photo</h3>
                        <div class="flex gap-4 flex-wrap">
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('photos.show', $photo)) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share on Facebook">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>

                            <!-- Twitter/X -->
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('photos.show', $photo)) }}&text={{ urlencode($photo->title) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share on X (Twitter)">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>

                            <!-- Pinterest -->
                            <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(route('photos.show', $photo)) }}&media={{ urlencode(url('storage/' . $photo->display_path)) }}&description={{ urlencode($photo->title) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Pin on Pinterest">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/>
                                </svg>
                            </a>

                            <!-- WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text={{ urlencode($photo->title . ' - ' . route('photos.show', $photo)) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share on WhatsApp">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </a>

                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('photos.show', $photo)) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share on LinkedIn">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>

                            <!-- Telegram -->
                            <a href="https://t.me/share/url?url={{ urlencode(route('photos.show', $photo)) }}&text={{ urlencode($photo->title) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share on Telegram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                </svg>
                            </a>

                            <!-- Email -->
                            <a href="mailto:?subject={{ urlencode($photo->title) }}&body={{ urlencode('Check out this photo: ' . route('photos.show', $photo)) }}"
                               class="text-theme-muted hover:text-theme-accent transition"
                               title="Share via Email">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </a>

                            <!-- Copy Link -->
                            <button onclick="copyLink()" id="copy-btn"
                                    class="text-theme-muted hover:text-theme-accent transition"
                                    title="Copy Link">
                                <svg id="copy-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg id="check-icon" class="w-5 h-5 text-green-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- EXIF Data Sidebar -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4 flex items-center text-theme-primary">
                            <svg class="w-5 h-5 mr-2 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Camera Settings
                        </h3>

                        <dl class="space-y-3 text-sm">
                            @if ($exif['camera'])
                                <div class="flex items-start">
                                    <dt class="text-theme-muted w-24 shrink-0">Camera</dt>
                                    <dd class="text-theme-secondary">{{ $exif['camera'] }}</dd>
                                </div>
                            @endif

                            @if ($exif['aperture'])
                                <div class="flex items-start">
                                    <dt class="text-theme-muted w-24 shrink-0">Aperture</dt>
                                    <dd class="text-theme-secondary font-mono">{{ $exif['aperture'] }}</dd>
                                </div>
                            @endif

                            @if ($exif['shutter_speed'])
                                <div class="flex items-start">
                                    <dt class="text-theme-muted w-24 shrink-0">Shutter</dt>
                                    <dd class="text-theme-secondary font-mono">{{ $exif['shutter_speed'] }}s</dd>
                                </div>
                            @endif

                            @if ($exif['iso'])
                                <div class="flex items-start">
                                    <dt class="text-theme-muted w-24 shrink-0">ISO</dt>
                                    <dd class="text-theme-secondary font-mono">{{ $exif['iso'] }}</dd>
                                </div>
                            @endif

                            @if ($exif['focal_length'])
                                <div class="flex items-start">
                                    <dt class="text-theme-muted w-24 shrink-0">Focal</dt>
                                    <dd class="text-theme-secondary font-mono">{{ $exif['focal_length'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Image Info -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4 text-theme-primary">Image Info</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-start">
                                <dt class="text-theme-muted w-24 shrink-0">Dimensions</dt>
                                <dd class="text-theme-secondary">{{ $photo->width }} x {{ $photo->height }}</dd>
                            </div>
                            <div class="flex items-start">
                                <dt class="text-theme-muted w-24 shrink-0">Views</dt>
                                <dd class="text-theme-secondary">{{ number_format($photo->views) }}</dd>
                            </div>
                            @if ($photo->captured_at)
                            <div class="flex items-start">
                                <dt class="text-theme-muted w-24 shrink-0">Captured</dt>
                                <dd class="text-theme-secondary">{{ $photo->captured_at->format('M j, Y') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Download Options -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6" x-data="{ showOptions: false }">
                        <h3 class="text-lg font-medium mb-4 flex items-center text-theme-primary">
                            <svg class="w-5 h-5 mr-2 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Photo
                        </h3>

                        <div class="space-y-2">
                            <a href="{{ route('photos.download', ['photo' => $photo, 'format' => 'webp']) }}"
                               class="flex items-center justify-between w-full px-4 py-2 text-sm rounded-lg bg-theme-tertiary hover:bg-theme-hover text-theme-secondary hover:text-theme-primary transition">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    WebP Format
                                </span>
                                <span class="text-xs text-theme-muted">Smaller file</span>
                            </a>

                            <a href="{{ route('photos.download', ['photo' => $photo, 'format' => 'jpeg']) }}"
                               class="flex items-center justify-between w-full px-4 py-2 text-sm rounded-lg bg-theme-tertiary hover:bg-theme-hover text-theme-secondary hover:text-theme-primary transition">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    JPEG Format
                                </span>
                                <span class="text-xs text-theme-muted">Compatible</span>
                            </a>
                        </div>

                        <p class="mt-3 text-xs text-theme-muted">
                            Downloads include watermark. Max 10 downloads/hour.
                        </p>
                    </div>

                    <!-- Buy Print -->
                    <div class="bg-theme-card border border-theme rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4 flex items-center text-theme-primary">
                            <svg class="w-5 h-5 mr-2 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Buy a Print
                        </h3>
                        <p class="text-sm text-theme-muted mb-4">Get this photograph as a museum-quality print for your home or office.</p>
                        <a href="{{ route('print.options', $photo) }}" class="block w-full text-center btn-theme-primary py-3 rounded-lg font-medium transition">
                            View Print Options
                        </a>
                    </div>
                </div>
            </div>

            <!-- Related Photos -->
            @if ($relatedPhotos->count() > 0)
                <div class="mt-16 pt-12 border-t border-theme">
                    <h2 class="text-2xl font-bold mb-8 text-theme-primary">Related Photos</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach ($relatedPhotos as $relatedPhoto)
                            <a href="{{ route('photos.show', $relatedPhoto) }}" class="group relative aspect-[4/3] overflow-hidden rounded-lg">
                                <img src="{{ $relatedPhoto->thumbnail_url }}"
                                     alt="{{ $relatedPhoto->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                     loading="lazy">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">{{ $relatedPhoto->title }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        function copyLink() {
            navigator.clipboard.writeText('{{ route('photos.show', $photo) }}');
            document.getElementById('copy-icon').classList.add('hidden');
            document.getElementById('check-icon').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('copy-icon').classList.remove('hidden');
                document.getElementById('check-icon').classList.add('hidden');
            }, 2000);
        }

        function photoSelection(photoId, galleryId) {
            return {
                photoId: photoId,
                galleryId: galleryId,
                isSelected: false,
                selectionCount: 0,

                init() {
                    this.checkSelection();
                    this.loadSelectionCount();
                },

                checkSelection() {
                    fetch(`/selections/photo/${this.photoId}/check`)
                        .then(response => response.json())
                        .then(data => {
                            this.isSelected = data.selected;
                        });
                },

                loadSelectionCount() {
                    fetch('/selections/count')
                        .then(response => response.json())
                        .then(data => {
                            this.selectionCount = data.count;
                        });
                },

                toggleSelection() {
                    fetch(`/selections/photo/${this.photoId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ gallery_id: this.galleryId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.isSelected = data.selected;
                        this.selectionCount = data.count;
                    });
                }
            }
        }
    </script>

    @if ($photo->hasLocation())
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = {{ $photo->latitude }};
            const lng = {{ $photo->longitude }};

            const map = L.map('photo-map').setView([lat, lng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Custom marker icon
            const marker = L.marker([lat, lng]).addTo(map);

            @if ($photo->location_name)
            marker.bindPopup('<strong>{{ $photo->title }}</strong><br>{{ $photo->location_name }}').openPopup();
            @else
            marker.bindPopup('<strong>{{ $photo->title }}</strong>').openPopup();
            @endif
        });
    </script>
    @endif
</x-layouts.public>
