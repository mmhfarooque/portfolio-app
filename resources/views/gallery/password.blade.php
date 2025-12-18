<x-layouts.public>
    <x-slot name="title">{{ $gallery->name }} - Password Required</x-slot>

    <div class="min-h-screen flex items-center justify-center py-20 px-4">
        <div class="max-w-md w-full">
            <div class="bg-theme-card border border-theme rounded-lg p-8 text-center">
                <!-- Lock Icon -->
                <div class="w-16 h-16 mx-auto mb-6 bg-theme-tertiary rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-theme-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold mb-2 text-theme-primary">{{ $gallery->name }}</h1>
                <p class="text-theme-secondary mb-6">This gallery is password protected. Please enter the password to view.</p>

                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm">
                        {{ $errors->first('password') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('gallery.unlock', $gallery) }}">
                    @csrf

                    <div class="mb-4">
                        <input type="password"
                               name="password"
                               placeholder="Enter password"
                               required
                               autofocus
                               class="w-full input-theme text-center text-lg tracking-widest">
                    </div>

                    <button type="submit" class="w-full btn-theme-primary font-bold py-3 px-6 rounded-md transition">
                        Unlock Gallery
                    </button>
                </form>

                <a href="{{ route('photos.index') }}" class="inline-block mt-6 text-sm text-theme-muted hover:text-theme-accent transition">
                    &larr; Back to Gallery
                </a>
            </div>
        </div>
    </div>
</x-layouts.public>
