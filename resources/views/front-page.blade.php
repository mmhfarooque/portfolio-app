<x-layouts.public>
    <x-slot name="title">{{ $profile['name'] }} - {{ $profile['title'] }}</x-slot>

    <!-- Hero Section with Profile -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Left Column - Profile Image & Contact -->
                <div class="lg:w-80 flex-shrink-0 space-y-6">
                    <!-- Profile Image -->
                    <div class="relative">
                        @if ($profile['image'])
                            <img src="{{ asset('storage/' . $profile['image']) }}"
                                 alt="{{ $profile['name'] }}"
                                 class="w-48 h-48 lg:w-64 lg:h-64 mx-auto object-cover rounded-2xl shadow-2xl border-4 border-theme">
                        @else
                            <div class="w-48 h-48 lg:w-64 lg:h-64 mx-auto bg-theme-tertiary rounded-2xl shadow-2xl border-4 border-theme flex items-center justify-center">
                                <svg class="w-20 h-20 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Contact Info Card -->
                    <div class="bg-theme-card rounded-xl p-6 shadow-theme border border-theme">
                        <h3 class="text-lg font-semibold text-theme-primary mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Contact
                        </h3>
                        <div class="space-y-3">
                            @if ($contact['email'])
                                <a href="mailto:{{ $contact['email'] }}" class="flex items-center gap-3 text-theme-secondary hover:text-theme-accent transition group">
                                    <svg class="w-5 h-5 text-theme-muted group-hover:text-theme-accent transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                    <span class="text-sm">{{ $contact['email'] }}</span>
                                </a>
                            @endif

                            @if ($contact['phone'])
                                <a href="tel:{{ $contact['phone'] }}" class="flex items-center gap-3 text-theme-secondary hover:text-theme-accent transition group">
                                    <svg class="w-5 h-5 text-theme-muted group-hover:text-theme-accent transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="text-sm">{{ $contact['phone'] }}</span>
                                </a>
                            @endif

                            @if ($contact['whatsapp'])
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact['whatsapp']) }}" target="_blank" class="flex items-center gap-3 text-theme-secondary hover:text-green-500 transition group">
                                    <svg class="w-5 h-5 text-theme-muted group-hover:text-green-500 transition" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <span class="text-sm">WhatsApp</span>
                                </a>
                            @endif

                            @if ($contact['location'] || $profile['location'])
                                <div class="flex items-center gap-3 text-theme-secondary">
                                    <svg class="w-5 h-5 text-theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-sm">{{ $contact['location'] ?: $profile['location'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="bg-theme-card rounded-xl p-6 shadow-theme border border-theme">
                        <h3 class="text-lg font-semibold text-theme-primary mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            Connect
                        </h3>
                        <div class="flex flex-wrap gap-4">
                            @if ($social['github'])
                                <a href="{{ $social['github'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="GitHub">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                </a>
                            @endif
                            @if ($social['linkedin'])
                                <a href="{{ $social['linkedin'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            @endif
                            @if ($social['instagram'])
                                <a href="{{ $social['instagram'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                            @endif
                            @if ($social['twitter'])
                                <a href="{{ $social['twitter'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="X/Twitter">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                            @endif
                            @if ($social['facebook'])
                                <a href="{{ $social['facebook'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if ($social['youtube'])
                                <a href="{{ $social['youtube'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="YouTube">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            @endif
                            @if ($social['behance'])
                                <a href="{{ $social['behance'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Behance">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-3.074 0-5.564-1.729-5.564-5.675 0-3.91 2.325-5.92 5.466-5.92 3.082 0 4.964 1.782 5.375 4.426.078.506.109 1.188.095 2.14h-8.027c.13 3.211 3.483 3.312 4.588 2.029h3.168zm-7.686-4h4.965c-.105-1.547-1.136-2.219-2.477-2.219-1.466 0-2.277.768-2.488 2.219zm-9.574 6.988h-6.466v-14.967h6.953c5.476.081 5.58 5.444 2.72 6.906 3.461 1.26 3.577 8.061-3.207 8.061zm-3.466-8.988h3.584c2.508 0 2.906-3-.312-3h-3.272v3zm3.391 3h-3.391v3.016h3.341c3.055 0 2.868-3.016.05-3.016z"/></svg>
                                </a>
                            @endif
                            @if ($social['flickr'])
                                <a href="{{ $social['flickr'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Flickr">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M0 12c0 3.074 2.494 5.564 5.565 5.564 3.075 0 5.569-2.49 5.569-5.564s-2.494-5.565-5.569-5.565C2.494 6.435 0 8.926 0 12zm12.866 0c0 3.074 2.493 5.564 5.567 5.564C21.502 17.564 24 15.074 24 12s-2.498-5.565-5.567-5.565c-3.074 0-5.567 2.491-5.567 5.565z"/></svg>
                                </a>
                            @endif
                            @if ($social['500px'])
                                <a href="{{ $social['500px'] }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="500px">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7.439 9.01A3.61 3.61 0 003.828 12.6a3.61 3.61 0 003.611 3.59A3.61 3.61 0 0011.05 12.6a3.61 3.61 0 00-3.611-3.59zm0 5.903a2.304 2.304 0 01-2.31-2.313 2.304 2.304 0 012.31-2.313 2.304 2.304 0 012.31 2.313 2.304 2.304 0 01-2.31 2.313zM16.561 9.01a3.61 3.61 0 00-3.611 3.59 3.61 3.61 0 003.611 3.59 3.61 3.61 0 003.611-3.59 3.61 3.61 0 00-3.611-3.59zm0 5.903a2.304 2.304 0 01-2.31-2.313 2.304 2.304 0 012.31-2.313 2.304 2.304 0 012.31 2.313 2.304 2.304 0 01-2.31 2.313z"/></svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Download Resume Button -->
                    @if ($profile['resume_pdf'])
                        <a href="{{ asset('storage/' . $profile['resume_pdf']) }}" target="_blank" class="block w-full text-center py-3 px-6 bg-theme-accent text-white rounded-xl font-medium hover:opacity-90 transition shadow-lg">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download Resume
                            </span>
                        </a>
                    @endif
                </div>

                <!-- Right Column - Bio & Skills -->
                <div class="flex-1 space-y-8">
                    <!-- Name & Title -->
                    <div>
                        <h1 class="text-4xl md:text-5xl font-bold text-theme-primary mb-2">{{ $profile['name'] }}</h1>
                        <p class="text-xl md:text-2xl text-theme-accent font-medium">{{ $profile['title'] }}</p>
                        @if ($profile['tagline'])
                            <p class="text-lg text-theme-muted mt-2">{{ $profile['tagline'] }}</p>
                        @endif
                    </div>

                    <!-- Bio -->
                    <div class="bg-theme-card rounded-xl p-6 md:p-8 shadow-theme border border-theme">
                        <h2 class="text-xl font-semibold text-theme-primary mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            About Me
                        </h2>
                        <div class="editorjs-content prose prose-theme max-w-none text-theme-secondary">
                            @if ($profile['bio'])
                                <x-editorjs-renderer :content="$profile['bio']" />
                            @else
                                <p class="text-theme-muted italic">Your bio will appear here. Go to Admin &rarr; Front Page to edit your bio content.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Skills Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Development Skills -->
                        @if (!empty($skills['development']))
                            <div class="bg-theme-card rounded-xl p-6 shadow-theme border border-theme">
                                <h3 class="text-lg font-semibold text-theme-primary mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                    Development
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($skills['development'] as $skill)
                                        <span class="px-3 py-1 text-sm rounded-full bg-theme-tertiary text-theme-secondary border border-theme">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Photography Skills -->
                        @if (!empty($skills['photography']))
                            <div class="bg-theme-card rounded-xl p-6 shadow-theme border border-theme">
                                <h3 class="text-lg font-semibold text-theme-primary mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Photography
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($skills['photography'] as $skill)
                                        <span class="px-3 py-1 text-sm rounded-full bg-theme-tertiary text-theme-secondary border border-theme">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Other Skills -->
                        @if (!empty($skills['other']))
                            <div class="bg-theme-card rounded-xl p-6 shadow-theme border border-theme md:col-span-2">
                                <h3 class="text-lg font-semibold text-theme-primary mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    Other Skills
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($skills['other'] as $skill)
                                        <span class="px-3 py-1 text-sm rounded-full bg-theme-tertiary text-theme-secondary border border-theme">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Photography Section -->
    @if ($featuredPhotos->count() > 0)
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-theme-primary mb-3">Photography Portfolio</h2>
                    <p class="text-theme-muted max-w-xl mx-auto">A glimpse into my landscape and nature photography work</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($featuredPhotos as $photo)
                        <a href="{{ route('photos.show', $photo) }}" class="group relative aspect-[4/3] overflow-hidden rounded-lg bg-theme-tertiary">
                            <img src="{{ $photo->thumbnail_url }}"
                                 alt="{{ $photo->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full group-hover:translate-y-0 transition">
                                <h3 class="text-sm font-medium text-white truncate">{{ $photo->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="text-center mt-10">
                    <a href="{{ route('photos.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-theme-card border border-theme hover:border-theme-accent rounded-full transition shadow-theme">
                        <span class="text-theme-primary">View All Photos</span>
                        <svg class="w-5 h-5 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
