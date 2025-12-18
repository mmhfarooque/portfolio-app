<x-layouts.public>
    <x-slot name="title">Gallery Expired - {{ $gallery->name }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-theme-card border border-theme rounded-lg p-8 shadow-lg">
                <svg class="mx-auto h-16 w-16 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>

                <h1 class="text-2xl font-bold text-theme-primary mb-2">Gallery Expired</h1>

                <p class="text-theme-muted mb-4">
                    This gallery is no longer available. It expired on
                    <span class="font-medium text-theme-secondary">{{ $gallery->expires_at->format('F j, Y') }}</span>.
                </p>

                @if($gallery->client_name)
                    <p class="text-sm text-theme-muted mb-6">
                        Gallery prepared for {{ $gallery->client_name }}
                    </p>
                @endif

                <div class="bg-theme-tertiary rounded-lg p-4 text-sm text-theme-secondary">
                    <p class="font-medium mb-2">Need access?</p>
                    <p>Please contact the photographer to request an extension or a new gallery link.</p>
                </div>

                <a href="{{ route('contact') }}" class="inline-block mt-6 btn-theme-primary px-6 py-2 rounded-lg">
                    Contact Photographer
                </a>
            </div>
        </div>
    </div>
</x-layouts.public>
