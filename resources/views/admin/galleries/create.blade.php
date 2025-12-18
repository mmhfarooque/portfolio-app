<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Gallery') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.galleries.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        </div>

                        <x-media-picker
                            name="cover_image"
                            label="Cover Image"
                            preview-class="w-32 h-24 object-cover rounded"
                        />

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Published</span>
                                </label>
                            </div>
                            <div>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Featured</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Password Protection -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Password Protection (Optional)
                                </span>
                            </label>
                            <input type="text" name="password" id="password" value="{{ old('password') }}" placeholder="Leave empty for public gallery" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Set a password to restrict access to this gallery. Visitors will need to enter this password to view photos.</p>
                        </div>

                        <!-- Client Gallery Section -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200" x-data="{ isClientGallery: {{ old('is_client_gallery') ? 'true' : 'false' }} }">
                            <div class="mb-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_client_gallery" value="1" x-model="isClientGallery" {{ old('is_client_gallery') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Client Gallery</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500 ml-6">Enable to create a private gallery with a shareable link for clients.</p>
                            </div>

                            <div x-show="isClientGallery" x-cloak class="space-y-4 ml-6 mt-4 pt-4 border-t border-blue-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
                                        <input type="text" name="client_name" id="client_name" value="{{ old('client_name') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="client_email" class="block text-sm font-medium text-gray-700 mb-1">Client Email</label>
                                        <input type="email" name="client_email" id="client_email" value="{{ old('client_email') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                                    <input type="datetime-local" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">Leave empty for no expiration.</p>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="allow_downloads" value="1" {{ old('allow_downloads') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">Allow Downloads</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="allow_selections" value="1" checked {{ old('allow_selections', true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-600">Allow Selections</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label for="selection_limit" class="block text-sm font-medium text-gray-700 mb-1">Selection Limit</label>
                                        <input type="number" name="selection_limit" id="selection_limit" value="{{ old('selection_limit') }}" min="1" placeholder="Unlimited" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.galleries.index') }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                Create Gallery
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
