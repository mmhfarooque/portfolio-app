<x-layouts.public>
    <x-slot name="title">Order Confirmation - {{ $order->order_number }}</x-slot>

    <div class="min-h-screen py-20 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Success Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-theme-primary mb-2">Thank You!</h1>
                <p class="text-theme-muted">Your order has been confirmed</p>
            </div>

            <!-- Order Details Card -->
            <div class="bg-theme-card border border-theme rounded-lg p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-theme-primary">Order Details</h2>
                        <p class="text-sm text-theme-muted">{{ $order->created_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-theme-muted">Order Number</span>
                        <span class="font-medium text-theme-primary">{{ $order->order_number }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-theme-muted">Product</span>
                        <span class="font-medium text-theme-primary">{{ $order->product_name }}</span>
                    </div>

                    @if($order->photo)
                    <div class="flex justify-between text-sm">
                        <span class="text-theme-muted">Photo</span>
                        <span class="font-medium text-theme-primary">{{ $order->photo->title }}</span>
                    </div>
                    @endif

                    <div class="border-t border-theme pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-theme-muted">Subtotal</span>
                            <span class="text-theme-secondary">${{ number_format($order->price, 2) }}</span>
                        </div>
                        @if($order->shipping > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-theme-muted">Shipping</span>
                            <span class="text-theme-secondary">${{ number_format($order->shipping, 2) }}</span>
                        </div>
                        @endif
                        @if($order->tax > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-theme-muted">Tax</span>
                            <span class="text-theme-secondary">${{ number_format($order->tax, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-semibold text-lg pt-2 border-t border-theme">
                            <span class="text-theme-primary">Total</span>
                            <span class="text-theme-primary">${{ number_format($order->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Key (for digital products) -->
            @if($order->product_type === 'license' && $order->license_key)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-2">Your License Key</h3>
                <p class="text-sm text-blue-700 mb-3">Save this key - you'll need it to download your photo.</p>
                <div class="bg-white rounded-lg p-3 font-mono text-center text-lg tracking-wider">
                    {{ $order->license_key }}
                </div>
                @if($order->license_expires_at)
                <p class="text-xs text-blue-600 mt-2 text-center">
                    Valid until: {{ $order->license_expires_at->format('F j, Y') }}
                </p>
                @endif

                @if($order->canDownload())
                <div class="mt-4 text-center">
                    <a href="{{ route('download.photo', ['order' => $order, 'key' => $order->license_key]) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Photo
                    </a>
                    <p class="text-xs text-blue-600 mt-2">
                        {{ $order->max_downloads - $order->download_count }} downloads remaining
                    </p>
                </div>
                @endif
            </div>
            @endif

            <!-- Shipping Address (for physical products) -->
            @if($order->product_type !== 'license' && $order->shipping_address_line1)
            <div class="bg-theme-card border border-theme rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-theme-primary mb-3">Shipping Address</h3>
                <address class="text-theme-secondary not-italic">
                    {{ $order->customer_name }}<br>
                    {{ $order->shipping_address_line1 }}<br>
                    @if($order->shipping_address_line2)
                    {{ $order->shipping_address_line2 }}<br>
                    @endif
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </address>
            </div>
            @endif

            <!-- Contact Info -->
            <div class="bg-theme-card border border-theme rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-theme-primary mb-3">Contact Information</h3>
                <div class="text-theme-secondary space-y-1">
                    <p>{{ $order->customer_name }}</p>
                    <p>{{ $order->customer_email }}</p>
                    @if($order->customer_phone)
                    <p>{{ $order->customer_phone }}</p>
                    @endif
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-theme-tertiary rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-theme-primary mb-3">What's Next?</h3>
                @if($order->product_type === 'license')
                <ul class="text-sm text-theme-secondary space-y-2">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        A confirmation email has been sent to {{ $order->customer_email }}
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Use your license key above to download your high-resolution photo
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        License includes {{ $order->max_downloads }} downloads within 1 year
                    </li>
                </ul>
                @else
                <ul class="text-sm text-theme-secondary space-y-2">
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        A confirmation email has been sent to {{ $order->customer_email }}
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Your print will be produced and shipped within 5-7 business days
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        You'll receive tracking information once shipped
                    </li>
                </ul>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('gallery') }}" class="btn-theme-secondary px-6 py-3 rounded-lg text-center">
                    Continue Browsing
                </a>
                <a href="{{ route('contact') }}" class="btn-theme-primary px-6 py-3 rounded-lg text-center">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</x-layouts.public>
