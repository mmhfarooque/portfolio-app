@props(['source' => 'footer', 'compact' => false])

<div x-data="{
    email: '',
    name: '',
    loading: false,
    success: false,
    error: '',
    async submit() {
        if (!this.email) return;
        this.loading = true;
        this.error = '';

        try {
            const response = await fetch('{{ route('newsletter.subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email: this.email,
                    name: this.name,
                    source: '{{ $source }}',
                    honeypot: '',
                }),
            });

            const data = await response.json();

            if (data.success) {
                this.success = true;
                this.email = '';
                this.name = '';
            } else {
                this.error = data.message;
            }
        } catch (e) {
            this.error = 'Something went wrong. Please try again.';
        }

        this.loading = false;
    }
}" {{ $attributes->merge(['class' => 'newsletter-signup']) }}>
    <template x-if="!success">
        <form @submit.prevent="submit" class="space-y-3">
            @if(!$compact)
                <h4 class="font-medium text-theme-primary mb-2">Subscribe to Newsletter</h4>
                <p class="text-sm text-theme-muted mb-4">Get notified about new photos and blog posts.</p>
            @endif

            <div class="{{ $compact ? 'flex gap-2' : 'space-y-3' }}">
                @if(!$compact)
                    <input
                        type="text"
                        x-model="name"
                        placeholder="Your name (optional)"
                        class="w-full px-4 py-2 rounded-lg input-theme focus:ring-2 focus:ring-theme-accent focus:outline-none"
                    >
                @endif

                <input
                    type="email"
                    x-model="email"
                    placeholder="your@email.com"
                    required
                    class="{{ $compact ? 'flex-1' : 'w-full' }} px-4 py-2 rounded-lg input-theme focus:ring-2 focus:ring-theme-accent focus:outline-none"
                >

                <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 bg-theme-accent text-white rounded-lg hover:opacity-90 transition disabled:opacity-50 {{ $compact ? '' : 'w-full' }}"
                >
                    <span x-show="!loading">Subscribe</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- Honeypot field (hidden) -->
            <input type="text" name="honeypot" style="display: none;" tabindex="-1" autocomplete="off">

            <p x-show="error" x-text="error" class="text-red-500 text-sm mt-2"></p>
        </form>
    </template>

    <template x-if="success">
        <div class="text-center py-4">
            <svg class="w-12 h-12 mx-auto text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-theme-primary font-medium">Thank you for subscribing!</p>
            <p class="text-theme-muted text-sm">Please check your email to confirm.</p>
        </div>
    </template>
</div>
