<x-layouts.public>
    <x-slot name="title">Checkout - {{ $photo->title }}</x-slot>

    @if($stripePublicKey)
    <script src="https://js.stripe.com/v3/"></script>
    @endif

    <div class="min-h-screen py-20 px-4" x-data="checkoutForm()">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-theme-primary mb-2">Checkout</h1>
                <p class="text-theme-muted">Complete your purchase</p>
            </div>

            @if(!$isPaymentConfigured)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                <p class="font-medium">Payment system not configured</p>
                <p class="text-sm">Please contact the photographer directly to complete your purchase.</p>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Order Summary -->
                <div class="bg-theme-card border border-theme rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-theme-primary mb-4">Order Summary</h2>

                    <div class="flex gap-4 mb-6">
                        <div class="w-24 h-24 rounded-lg overflow-hidden bg-theme-tertiary">
                            <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h3 class="font-medium text-theme-primary">{{ $photo->title }}</h3>
                            <p class="text-sm text-theme-muted">{{ $productName }}</p>
                        </div>
                    </div>

                    <div class="border-t border-theme pt-4 space-y-2">
                        <div class="flex justify-between text-theme-secondary">
                            <span>Product</span>
                            <span>${{ number_format($price, 2) }}</span>
                        </div>
                        @if($productType !== 'license')
                        <div class="flex justify-between text-theme-secondary">
                            <span>Shipping</span>
                            <span>${{ number_format($shipping, 2) }}</span>
                        </div>
                        @endif
                        @if($tax > 0)
                        <div class="flex justify-between text-theme-secondary">
                            <span>Tax</span>
                            <span>${{ number_format($tax, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-semibold text-lg text-theme-primary pt-2 border-t border-theme">
                            <span>Total</span>
                            <span>${{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="bg-theme-card border border-theme rounded-lg p-6">
                    <form @submit.prevent="submitOrder" class="space-y-4">
                        <input type="hidden" name="product_id" value="{{ $productId }}">
                        <input type="hidden" name="product_name" value="{{ $productName }}">
                        <input type="hidden" name="product_type" value="{{ $productType }}">
                        <input type="hidden" name="price" value="{{ $price }}">
                        <input type="hidden" name="shipping" value="{{ $shipping }}">
                        <input type="hidden" name="tax" value="{{ $tax }}">
                        <input type="hidden" name="total" value="{{ $total }}">

                        <h3 class="font-semibold text-theme-primary">Contact Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-theme-secondary mb-1">Full Name</label>
                            <input type="text" x-model="form.customer_name" required
                                   class="w-full rounded-lg input-theme border-theme">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-theme-secondary mb-1">Email</label>
                            <input type="email" x-model="form.customer_email" required
                                   class="w-full rounded-lg input-theme border-theme">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-theme-secondary mb-1">Phone (Optional)</label>
                            <input type="tel" x-model="form.customer_phone"
                                   class="w-full rounded-lg input-theme border-theme">
                        </div>

                        @if($productType !== 'license')
                        <h3 class="font-semibold text-theme-primary pt-4">Shipping Address</h3>

                        <div>
                            <label class="block text-sm font-medium text-theme-secondary mb-1">Address Line 1</label>
                            <input type="text" x-model="form.shipping_address_line1" required
                                   class="w-full rounded-lg input-theme border-theme">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-theme-secondary mb-1">Address Line 2 (Optional)</label>
                            <input type="text" x-model="form.shipping_address_line2"
                                   class="w-full rounded-lg input-theme border-theme">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-theme-secondary mb-1">City</label>
                                <input type="text" x-model="form.shipping_city" required
                                       class="w-full rounded-lg input-theme border-theme">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-theme-secondary mb-1">State/Province</label>
                                <input type="text" x-model="form.shipping_state" required
                                       class="w-full rounded-lg input-theme border-theme">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-theme-secondary mb-1">Postal Code</label>
                                <input type="text" x-model="form.shipping_postal_code" required
                                       class="w-full rounded-lg input-theme border-theme">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-theme-secondary mb-1">Country</label>
                                <select x-model="form.shipping_country" required
                                        class="w-full rounded-lg input-theme border-theme">
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                </select>
                            </div>
                        </div>
                        @endif

                        @if($isPaymentConfigured)
                        <h3 class="font-semibold text-theme-primary pt-4">Payment</h3>

                        <div id="card-element" class="p-3 bg-white border border-gray-300 rounded-lg"></div>
                        <div id="card-errors" class="text-red-500 text-sm" role="alert"></div>
                        @endif

                        <div x-show="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm" x-text="error"></div>

                        <button type="submit"
                                :disabled="processing"
                                class="w-full btn-theme-primary py-3 rounded-lg font-medium transition disabled:opacity-50">
                            <span x-show="!processing">Pay ${{ number_format($total, 2) }}</span>
                            <span x-show="processing" class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>

                        <p class="text-xs text-theme-muted text-center">
                            Your payment is secured by Stripe. We never store your card details.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkoutForm() {
            return {
                form: {
                    customer_name: '',
                    customer_email: '',
                    customer_phone: '',
                    shipping_address_line1: '',
                    shipping_address_line2: '',
                    shipping_city: '',
                    shipping_state: '',
                    shipping_postal_code: '',
                    shipping_country: 'US',
                },
                processing: false,
                error: null,
                stripe: null,
                cardElement: null,

                init() {
                    @if($stripePublicKey)
                    this.stripe = Stripe('{{ $stripePublicKey }}');
                    const elements = this.stripe.elements();
                    this.cardElement = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#424770',
                                '::placeholder': {
                                    color: '#aab7c4',
                                },
                            },
                        },
                    });
                    this.cardElement.mount('#card-element');

                    this.cardElement.on('change', (event) => {
                        const displayError = document.getElementById('card-errors');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
                    @endif
                },

                async submitOrder() {
                    this.processing = true;
                    this.error = null;

                    try {
                        // Create order on server
                        const response = await fetch('{{ route("checkout.process", $photo) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                ...this.form,
                                product_id: '{{ $productId }}',
                                product_name: '{{ $productName }}',
                                product_type: '{{ $productType }}',
                                price: {{ $price }},
                                shipping: {{ $shipping }},
                                tax: {{ $tax }},
                                total: {{ $total }},
                            })
                        });

                        const data = await response.json();

                        if (!data.success) {
                            throw new Error(data.message || 'Failed to create order');
                        }

                        // Confirm payment with Stripe
                        const { error, paymentIntent } = await this.stripe.confirmCardPayment(
                            data.client_secret,
                            {
                                payment_method: {
                                    card: this.cardElement,
                                    billing_details: {
                                        name: this.form.customer_name,
                                        email: this.form.customer_email,
                                    },
                                },
                            }
                        );

                        if (error) {
                            throw new Error(error.message);
                        }

                        if (paymentIntent.status === 'succeeded') {
                            // Redirect to confirmation page
                            window.location.href = `/order/${data.order_id}/confirmation`;
                        }

                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.processing = false;
                    }
                }
            }
        }
    </script>
</x-layouts.public>
