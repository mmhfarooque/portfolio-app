<x-layouts.public>
    <x-slot name="title">Enter Password - {{ $gallery->name }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">
            <div class="bg-theme-card border border-theme rounded-lg p-8 shadow-lg">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-12 w-12 text-theme-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-theme-primary mb-2">{{ $gallery->name }}</h1>
                    <p class="text-theme-muted">This gallery is password protected.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        {{ $errors->first('password') }}
                    </div>
                @endif

                <form action="{{ route('client-gallery.password', $token) }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-theme-secondary mb-1">Password</label>
                        <input type="password" name="password" id="password" required autofocus
                               class="w-full rounded-lg input-theme border-theme focus:border-theme-accent"
                               placeholder="Enter gallery password">
                    </div>

                    <button type="submit" class="w-full btn-theme-primary py-3 rounded-lg font-medium transition">
                        Access Gallery
                    </button>
                </form>

                @if($gallery->client_name)
                    <p class="mt-4 text-sm text-center text-theme-muted">
                        Prepared for {{ $gallery->client_name }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.public>
