<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('admin.orders.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm">&larr; Back to Orders</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">
                    Order {{ $order->order_number }}
                </h2>
            </div>
            <div class="flex gap-2">
                <span class="px-3 py-1 text-sm rounded-full bg-{{ $order->payment_status_color }}-100 text-{{ $order->payment_status_color }}-800">
                    Payment: {{ ucfirst($order->payment_status) }}
                </span>
                <span class="px-3 py-1 text-sm rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Order Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>

                        <div class="flex gap-4 mb-6">
                            @if($order->photo)
                                <div class="w-24 h-24 rounded-lg overflow-hidden bg-gray-100">
                                    <img src="{{ $order->photo->thumbnail_url }}" alt="{{ $order->photo->title }}" class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $order->product_name }}</h4>
                                @if($order->photo)
                                    <p class="text-sm text-gray-500">Photo: {{ $order->photo->title }}</p>
                                @endif
                                <p class="text-sm text-gray-500 capitalize">Type: {{ $order->product_type }}</p>
                                @if($order->product_size)
                                    <p class="text-sm text-gray-500">Size: {{ $order->product_size }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Product</span>
                                <span class="text-gray-900">${{ number_format($order->price, 2) }}</span>
                            </div>
                            @if($order->shipping > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Shipping</span>
                                    <span class="text-gray-900">${{ number_format($order->shipping, 2) }}</span>
                                </div>
                            @endif
                            @if($order->tax > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Tax</span>
                                    <span class="text-gray-900">${{ number_format($order->tax, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between font-semibold text-lg pt-2 border-t border-gray-200">
                                <span>Total</span>
                                <span>${{ number_format($order->total, 2) }} {{ $order->currency }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Contact</h4>
                                <p class="text-gray-900">{{ $order->customer_name }}</p>
                                <p class="text-gray-600">{{ $order->customer_email }}</p>
                                @if($order->customer_phone)
                                    <p class="text-gray-600">{{ $order->customer_phone }}</p>
                                @endif
                            </div>

                            @if($order->product_type !== 'license' && $order->shipping_address_line1)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Shipping Address</h4>
                                    <address class="text-gray-900 not-italic">
                                        {{ $order->shipping_address_line1 }}<br>
                                        @if($order->shipping_address_line2)
                                            {{ $order->shipping_address_line2 }}<br>
                                        @endif
                                        {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                                        {{ $order->shipping_country }}
                                    </address>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- License Info (for digital products) -->
                    @if($order->product_type === 'license')
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">License Information</h3>

                            <div class="space-y-3">
                                @if($order->license_key)
                                    <div>
                                        <span class="text-sm text-gray-500">License Key:</span>
                                        <code class="ml-2 px-2 py-1 bg-gray-100 rounded text-sm font-mono">{{ $order->license_key }}</code>
                                    </div>
                                @endif
                                @if($order->license_type)
                                    <div>
                                        <span class="text-sm text-gray-500">License Type:</span>
                                        <span class="ml-2 capitalize">{{ $order->license_type }}</span>
                                    </div>
                                @endif
                                @if($order->license_expires_at)
                                    <div>
                                        <span class="text-sm text-gray-500">Expires:</span>
                                        <span class="ml-2">{{ $order->license_expires_at->format('F j, Y') }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-sm text-gray-500">Downloads:</span>
                                    <span class="ml-2">{{ $order->download_count }} / {{ $order->max_downloads }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Tracking Info (for physical products) -->
                    @if($order->product_type !== 'license' && ($order->tracking_number || $order->status === 'confirmed' || $order->status === 'processing'))
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping & Tracking</h3>

                            @if($order->tracking_number)
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-sm text-gray-500">Tracking Number:</span>
                                        <span class="ml-2 font-mono">{{ $order->tracking_number }}</span>
                                    </div>
                                    @if($order->tracking_url)
                                        <div>
                                            <a href="{{ $order->tracking_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                                Track Package &rarr;
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <form action="{{ route('admin.orders.ship', $order) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
                                        <input type="text" name="tracking_number" required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tracking URL (optional)</label>
                                        <input type="url" name="tracking_url"
                                               placeholder="https://..."
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                        Mark as Shipped
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <!-- Notes -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>

                        @if($order->notes)
                            <div class="bg-gray-50 rounded-lg p-4 mb-4 whitespace-pre-wrap text-sm text-gray-700">{{ $order->notes }}</div>
                        @endif

                        <form action="{{ route('admin.orders.note', $order) }}" method="POST">
                            @csrf
                            <textarea name="notes" rows="2" required
                                      placeholder="Add a note..."
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-2"></textarea>
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">
                                Add Note
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Update Status -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>

                        <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Order Status</label>
                                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                    <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Update Status
                            </button>
                        </form>
                    </div>

                    <!-- Order Details -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h3>

                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Order Number</dt>
                                <dd class="font-medium text-gray-900">{{ $order->order_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Created</dt>
                                <dd class="text-gray-900">{{ $order->created_at->format('M j, Y g:i A') }}</dd>
                            </div>
                            @if($order->paid_at)
                                <div>
                                    <dt class="text-gray-500">Paid At</dt>
                                    <dd class="text-gray-900">{{ $order->paid_at->format('M j, Y g:i A') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-gray-500">Payment Provider</dt>
                                <dd class="text-gray-900 capitalize">{{ $order->payment_provider }}</dd>
                            </div>
                            @if($order->payment_intent_id)
                                <div>
                                    <dt class="text-gray-500">Payment ID</dt>
                                    <dd class="text-gray-900 font-mono text-xs break-all">{{ $order->payment_intent_id }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                        <div class="space-y-2">
                            <a href="mailto:{{ $order->customer_email }}" class="block w-full px-4 py-2 text-center bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                Email Customer
                            </a>
                            @if($order->photo)
                                <a href="{{ route('photos.show', $order->photo) }}" target="_blank" class="block w-full px-4 py-2 text-center bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                    View Photo
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
