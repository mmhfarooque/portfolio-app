<x-layouts.public>
    <x-slot name="pageTitle">Subscription Confirmed</x-slot>

    <div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full text-center">
            @if($success)
                <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-theme-primary mb-4">Subscription Confirmed!</h1>
            @else
                <div class="w-16 h-16 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-theme-primary mb-4">Oops!</h1>
            @endif

            <p class="text-theme-secondary mb-8">{{ $message }}</p>

            <a href="{{ route('home') }}" class="inline-block px-6 py-3 bg-theme-accent text-white rounded-lg hover:opacity-90 transition">
                Return Home
            </a>
        </div>
    </div>
</x-layouts.public>
