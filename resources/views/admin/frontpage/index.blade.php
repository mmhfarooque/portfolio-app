<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Front Page Settings') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Configure your home page / CV / Resume content</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Preview Front Page
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.frontpage.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Profile Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                                    <p class="text-sm text-gray-500">Your personal details displayed on the home page</p>
                                </div>
                            </div>
                            <x-section-save-button />
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Profile Image -->
                            <div class="lg:row-span-3">
                                @php
                                    $profileImage = $settings->get('profile', collect())->firstWhere('key', 'profile_image');
                                @endphp
                                <x-media-picker
                                    name="profile_image"
                                    label="Profile Photo"
                                    :current-image="$profileImage?->value ? asset('storage/' . $profileImage->value) : null"
                                    :value="$profileImage?->value"
                                    preview-class="w-40 h-40 object-cover rounded-full mx-auto"
                                />
                            </div>

                            <!-- Name & Title -->
                            <div class="lg:col-span-2 space-y-4">
                                @php
                                    $profileName = $settings->get('profile', collect())->firstWhere('key', 'profile_name');
                                    $profileTitle = $settings->get('profile', collect())->firstWhere('key', 'profile_title');
                                    $profileTagline = $settings->get('profile', collect())->firstWhere('key', 'profile_tagline');
                                    $profileLocation = $settings->get('profile', collect())->firstWhere('key', 'profile_location');
                                @endphp

                                <div>
                                    <label for="profile_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" name="profile_name" id="profile_name" value="{{ $profileName?->value }}" placeholder="Your Name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="profile_title" class="block text-sm font-medium text-gray-700 mb-1">Professional Title</label>
                                    <input type="text" name="profile_title" id="profile_title" value="{{ $profileTitle?->value }}" placeholder="Web Developer & Landscape Photographer" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="profile_tagline" class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                                    <input type="text" name="profile_tagline" id="profile_tagline" value="{{ $profileTagline?->value }}" placeholder="Creating digital experiences and capturing natural beauty" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="profile_location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <input type="text" name="profile_location" id="profile_location" value="{{ $profileLocation?->value }}" placeholder="City, Country" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- About Me / Bio -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">About Me / Bio</h3>
                                    <p class="text-sm text-gray-500">Write your personal story and introduction</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                            <p class="text-gray-600 mb-4">Use the block-based editor to create rich bio content with headings, paragraphs, quotes, and more.</p>
                            <a href="{{ route('admin.about.editorjs') }}?target=profile" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Open Bio Editor
                            </a>
                            @php
                                $profileBio = $settings->get('profile', collect())->firstWhere('key', 'profile_bio');
                            @endphp
                            @if ($profileBio?->value)
                                <p class="text-sm text-green-600 mt-3 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Bio content saved
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Resume / CV -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Resume / CV</h3>
                                    <p class="text-sm text-gray-500">Upload your resume for visitors to download</p>
                                </div>
                            </div>
                            <x-section-save-button />
                        </div>

                        @php
                            $resumeSetting = $settings->get('profile', collect())->firstWhere('key', 'profile_resume_pdf');
                        @endphp
                        <div>
                            @if ($resumeSetting?->value)
                                <div class="flex items-center gap-3 mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-green-800">Resume uploaded</p>
                                        <a href="{{ asset('storage/' . $resumeSetting->value) }}" target="_blank" class="text-sm text-green-600 hover:underline">View current resume</a>
                                    </div>
                                </div>
                            @endif
                            <input type="file" name="profile_resume_pdf" id="profile_resume_pdf" accept=".pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            <p class="text-xs text-gray-500 mt-2">Upload a PDF file (max 10MB). This will appear as a "Download Resume" button on your front page.</p>
                        </div>
                    </div>
                </div>

                <!-- Skills & Expertise -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Skills & Expertise</h3>
                                    <p class="text-sm text-gray-500">Enter skills separated by commas</p>
                                </div>
                            </div>
                            <x-section-save-button />
                        </div>

                        <div class="space-y-4">
                            @foreach ($settings->get('skills', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        @if ($setting->key === 'skills_development')
                                            <span class="inline-flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                                </svg>
                                                Development Skills
                                            </span>
                                        @elseif ($setting->key === 'skills_photography')
                                            <span class="inline-flex items-center gap-2">
                                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Photography Skills
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Other Skills
                                            </span>
                                        @endif
                                    </label>
                                    <textarea name="{{ $setting->key }}" id="{{ $setting->key }}" rows="2" placeholder="Skill 1, Skill 2, Skill 3..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $setting->value }}</textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                                    <p class="text-sm text-gray-500">How visitors can reach you</p>
                                </div>
                            </div>
                            <x-section-save-button />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($settings->get('contact', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ ucwords(str_replace(['contact_', '_'], ['', ' '], $setting->key)) }}
                                    </label>
                                    <input type="{{ $setting->key === 'contact_email' ? 'email' : 'text' }}"
                                           name="{{ $setting->key }}"
                                           id="{{ $setting->key }}"
                                           value="{{ $setting->value }}"
                                           placeholder="{{ $setting->key === 'contact_email' ? 'email@example.com' : ($setting->key === 'contact_phone' ? '+1234567890' : ($setting->key === 'contact_whatsapp' ? '+1234567890' : '')) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Social Media Links -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Social Media Links</h3>
                                    <p class="text-sm text-gray-500">Connect your social profiles</p>
                                </div>
                            </div>
                            <x-section-save-button />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($settings->get('social', collect()) as $setting)
                                <div>
                                    <label for="{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                                        @php
                                            $socialName = str_replace('social_', '', $setting->key);
                                        @endphp
                                        @switch($socialName)
                                            @case('github')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                                @break
                                            @case('linkedin')
                                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                                @break
                                            @case('instagram')
                                                <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/></svg>
                                                @break
                                            @case('twitter')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                                @break
                                            @case('facebook')
                                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                                @break
                                            @case('youtube')
                                                <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                                @break
                                            @case('behance')
                                                <svg class="w-4 h-4 text-blue-700" fill="currentColor" viewBox="0 0 24 24"><path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-3.074 0-5.564-1.729-5.564-5.675 0-3.91 2.325-5.92 5.466-5.92 3.082 0 4.964 1.782 5.375 4.426.078.506.109 1.188.095 2.14h-8.027c.13 3.211 3.483 3.312 4.588 2.029h3.168zm-7.686-4h4.965c-.105-1.547-1.136-2.219-2.477-2.219-1.466 0-2.277.768-2.488 2.219zm-9.574 6.988h-6.466v-14.967h6.953c5.476.081 5.58 5.444 2.72 6.906 3.461 1.26 3.577 8.061-3.207 8.061zm-3.466-8.988h3.584c2.508 0 2.906-3-.312-3h-3.272v3zm3.391 3h-3.391v3.016h3.341c3.055 0 2.868-3.016.05-3.016z"/></svg>
                                                @break
                                            @case('flickr')
                                                <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M0 12c0 3.074 2.494 5.564 5.565 5.564 3.075 0 5.569-2.49 5.569-5.564s-2.494-5.565-5.569-5.565C2.494 6.435 0 8.926 0 12zm12.866 0c0 3.074 2.493 5.564 5.567 5.564C21.502 17.564 24 15.074 24 12s-2.498-5.565-5.567-5.565c-3.074 0-5.567 2.491-5.567 5.565z"/></svg>
                                                @break
                                            @case('500px')
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.439 9.01A3.61 3.61 0 003.828 12.6a3.61 3.61 0 003.611 3.59A3.61 3.61 0 0011.05 12.6a3.61 3.61 0 00-3.611-3.59zm0 5.903a2.304 2.304 0 01-2.31-2.313 2.304 2.304 0 012.31-2.313 2.304 2.304 0 012.31 2.313 2.304 2.304 0 01-2.31 2.313z"/></svg>
                                                @break
                                            @default
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        @endswitch
                                        {{ ucwords($socialName) }}
                                    </label>
                                    <input type="url" name="{{ $setting->key }}" id="{{ $setting->key }}" value="{{ $setting->value }}" placeholder="https://..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Front Page Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
