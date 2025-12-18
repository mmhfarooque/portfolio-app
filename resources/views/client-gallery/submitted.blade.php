<x-layouts.public>
    <x-slot name="title">Selections Submitted - {{ $gallery->name }}</x-slot>

    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-theme-card border border-theme rounded-lg p-8 shadow-lg">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-theme-primary mb-2">Selections Submitted!</h1>

                <p class="text-theme-muted mb-4">
                    Thank you for submitting your photo selections.
                </p>

                <div class="bg-theme-tertiary rounded-lg p-4 mb-6">
                    <p class="text-lg font-semibold text-theme-primary">{{ $selectedCount }}</p>
                    <p class="text-sm text-theme-muted">photo{{ $selectedCount !== 1 ? 's' : '' }} selected</p>
                </div>

                <p class="text-sm text-theme-secondary mb-6">
                    The photographer has been notified of your selections and will be in touch soon.
                </p>

                <div class="space-y-3">
                    <a href="{{ route('client-gallery.view', $gallery->access_token) }}"
                       class="block w-full btn-theme-secondary py-2 rounded-lg">
                        Back to Gallery
                    </a>
                    <a href="{{ route('home') }}" class="block w-full text-theme-muted hover:text-theme-primary py-2">
                        Visit Main Site
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
