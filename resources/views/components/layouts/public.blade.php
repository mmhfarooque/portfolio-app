<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle ?? App\Models\Setting::get('site_name', config('app.name', 'Photography Portfolio')) }}</title>

        {{-- SEO Meta Tags --}}
        @if(isset($seo))
            {{ $seo }}
        @else
            <x-seo-meta
                :title="$title ?? null"
                :description="$description ?? null"
            />
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Theme Styles -->
        <x-theme-styles />

        {{-- Additional Head Content --}}
        {{ $head ?? '' }}
    </head>
    <body class="font-sans antialiased min-h-screen bg-theme-primary text-theme-primary">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 nav-theme border-b border-theme" style="background-color: rgba(var(--bg-card-rgb), 0.8); backdrop-filter: blur(12px);" x-data="{ mobileOpen: false, needsMobile: false }" x-init="
            const checkNavOverflow = () => {
                const nav = $refs.desktopNav;
                if (nav) {
                    needsMobile = nav.scrollWidth > nav.clientWidth || window.innerWidth < 640;
                }
            };
            checkNavOverflow();
            window.addEventListener('resize', checkNavOverflow);
        ">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-theme-primary">
                            @if (App\Models\Setting::get('site_logo'))
                                <img src="{{ asset('storage/' . App\Models\Setting::get('site_logo')) }}" alt="{{ App\Models\Setting::get('site_name') }}" class="h-8">
                            @else
                                {{ App\Models\Setting::get('site_name', config('app.name', 'Photography')) }}
                            @endif
                        </a>
                    </div>
                    <!-- Desktop Navigation -->
                    <div x-ref="desktopNav" class="flex items-center space-x-8" :class="{ 'hidden': needsMobile }">
                        <a href="{{ route('home') }}" class="nav-theme-link transition whitespace-nowrap {{ request()->routeIs('home') ? 'active text-theme-accent' : '' }}">
                            Home
                        </a>
                        <a href="{{ route('photos.index') }}" class="nav-theme-link transition whitespace-nowrap {{ request()->routeIs('photos.*') ? 'active text-theme-accent' : '' }}">
                            Photos
                        </a>
                        <a href="{{ route('contact') }}" class="nav-theme-link transition whitespace-nowrap {{ request()->routeIs('contact') ? 'text-theme-accent' : '' }}">
                            Contact
                        </a>
                        @auth
                            <a href="{{ route('admin.photos.index') }}" class="nav-theme-link transition whitespace-nowrap">
                                Admin
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="nav-theme-link transition whitespace-nowrap">
                                Login
                            </a>
                        @endauth
                    </div>
                    <!-- Mobile menu button -->
                    <div class="flex items-center" :class="{ 'hidden': !needsMobile }">
                        <button type="button" @click="mobileOpen = !mobileOpen" class="text-theme-secondary hover:text-theme-primary p-2">
                            <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Mobile menu -->
                <div x-show="mobileOpen && needsMobile" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="pb-4">
                    <div class="flex flex-col space-y-3">
                        <a href="{{ route('home') }}" class="nav-theme-link py-2 {{ request()->routeIs('home') ? 'text-theme-accent' : '' }}">Home</a>
                        <a href="{{ route('photos.index') }}" class="nav-theme-link py-2 {{ request()->routeIs('photos.*') ? 'text-theme-accent' : '' }}">Photos</a>
                        <a href="{{ route('contact') }}" class="nav-theme-link py-2 {{ request()->routeIs('contact') ? 'text-theme-accent' : '' }}">Contact</a>
                        @auth
                            <a href="{{ route('admin.photos.index') }}" class="nav-theme-link py-2">Admin</a>
                        @else
                            <a href="{{ route('login') }}" class="nav-theme-link py-2">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="pt-16">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="mt-20 py-12 bg-theme-secondary border-t border-theme">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Brand -->
                    <div>
                        <h3 class="text-xl font-bold mb-4 text-theme-primary">{{ App\Models\Setting::get('site_name', config('app.name', 'Photography Portfolio')) }}</h3>
                        <p class="text-theme-muted">{{ App\Models\Setting::get('site_tagline', 'Capturing moments in time') }}</p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-medium mb-4 text-theme-primary">Quick Links</h4>
                        <ul class="flex gap-6 text-theme-secondary">
                            <li><a href="{{ route('home') }}" class="hover:text-theme-accent transition">Home</a></li>
                            <li><a href="{{ route('photos.index') }}" class="hover:text-theme-accent transition">Gallery</a></li>
                            <li><a href="{{ route('contact') }}" class="hover:text-theme-accent transition">Contact</a></li>
                        </ul>
                    </div>

                    <!-- Social -->
                    <div>
                        <h4 class="font-medium mb-4 text-theme-primary">Connect</h4>
                        <div class="flex gap-4 flex-wrap">
                            @if (App\Models\Setting::get('social_github'))
                                <a href="{{ App\Models\Setting::get('social_github') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="GitHub">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                </a>
                            @endif
                            @if (App\Models\Setting::get('social_linkedin'))
                                <a href="{{ App\Models\Setting::get('social_linkedin') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            @endif
                            @if (App\Models\Setting::get('social_instagram'))
                                <a href="{{ App\Models\Setting::get('social_instagram') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                            @endif
                            @if (App\Models\Setting::get('social_twitter'))
                                <a href="{{ App\Models\Setting::get('social_twitter') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="X/Twitter">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                            @endif
                            @if (App\Models\Setting::get('social_facebook'))
                                <a href="{{ App\Models\Setting::get('social_facebook') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if (App\Models\Setting::get('social_youtube'))
                                <a href="{{ App\Models\Setting::get('social_youtube') }}" target="_blank" rel="noopener noreferrer" class="text-theme-muted hover:text-theme-accent transition" title="YouTube">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-theme text-center text-theme-muted">
                    <p>&copy; {{ date('Y') }} {{ App\Models\Setting::get('profile_name', App\Models\Setting::get('site_name', 'Photography Portfolio')) }}. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </body>
</html>
