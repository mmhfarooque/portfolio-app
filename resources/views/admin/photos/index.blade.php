<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Photos') }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.photos.bulk-edit') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Bulk Edit
                </a>
                <a href="{{ route('admin.photos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Upload Photos
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="bulkActions()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                    {{ session('info') }}
                </div>
            @endif

            <!-- Processing Status Banner -->
            <div x-data="processingStatus()" x-show="processingCount > 0 || failedCount > 0" x-cloak class="mb-4">
                <div x-show="processingCount > 0" class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-2 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span><span x-text="processingCount"></span> photo(s) processing in the background...</span>
                    </div>
                    <button @click="refreshStatus" class="text-sm text-yellow-600 hover:text-yellow-800 underline">Refresh</button>
                </div>
                <div x-show="failedCount > 0" class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span><span x-text="failedCount"></span> photo(s) failed to process. Edit them to retry or delete.</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.photos.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by title..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Filter
                        </button>
                        @if (request()->hasAny(['status', 'category', 'search']))
                            <a href="{{ route('admin.photos.index') }}" class="text-gray-600 hover:text-gray-800 py-2 px-4">
                                Clear Filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @change="toggleAll($event)">
                            <label for="select-all" class="text-sm text-gray-600">Select All</label>
                        </div>

                        <span class="text-gray-400">|</span>

                        <span class="text-sm text-gray-600" x-text="selectedCount + ' selected'"></span>

                        <span class="text-gray-400">|</span>

                        <!-- Quick Actions -->
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="showModal('status')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Set Status
                            </button>
                            <button type="button" @click="showModal('category')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Set Category
                            </button>
                            <button type="button" @click="showModal('gallery')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-purple-100 text-purple-700 rounded hover:bg-purple-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Set Gallery
                            </button>
                            <button type="button" @click="showModal('tags')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Add Tags
                            </button>
                            <button type="button" @click="showModal('location')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-teal-100 text-teal-700 rounded hover:bg-teal-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Set Location
                            </button>
                            <button type="button" @click="showModal('delete')" :disabled="selectedCount === 0" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($photos->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            @foreach ($photos as $photo)
                                <div class="relative group">
                                    <input type="checkbox"
                                           value="{{ $photo->id }}"
                                           class="photo-checkbox absolute top-2 left-2 z-10 w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           @change="updateSelection($event)">
                                    <a href="{{ route('admin.photos.edit', $photo) }}" class="block">
                                        <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                                            @if ($photo->thumbnail_path)
                                                <img src="{{ asset('storage/' . $photo->thumbnail_path) }}" alt="{{ $photo->title }}" class="w-full h-full object-cover group-hover:opacity-75 transition" loading="lazy">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    No Image
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                    <div class="mt-2">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $photo->title }}</p>
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            @if ($photo->status === 'processing')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 animate-pulse">
                                                    <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    {{ $photo->processing_stage_text ?? 'Processing' }}
                                                </span>
                                            @elseif ($photo->status === 'failed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    Failed
                                                </span>
                                            @elseif ($photo->status === 'published')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Published
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Draft
                                                </span>
                                            @endif
                                            @if ($photo->is_featured)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    Featured
                                                </span>
                                            @endif
                                            @if ($photo->hasLocation())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-100 text-teal-800">
                                                    📍
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $photos->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No photos</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by uploading some photos.</p>
                            <div class="mt-6">
                                <a href="{{ route('admin.photos.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Upload Photos
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bulk Action Modals -->
        <!-- Status Modal -->
        <div x-show="activeModal === 'status'" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-action') }}" method="POST">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Set Status for Selected Photos</h3>
                            <select name="action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="publish">Publish</option>
                                <option value="unpublish">Unpublish (Draft)</option>
                            </select>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Apply
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Category Modal -->
        <div x-show="activeModal === 'category'" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="field" value="category_id">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Set Category for Selected Photos</h3>
                            <select name="value" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">No Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Apply
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Gallery Modal -->
        <div x-show="activeModal === 'gallery'" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="field" value="gallery_id">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Set Gallery for Selected Photos</h3>
                            <select name="value" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">No Gallery</option>
                                @foreach ($galleries ?? [] as $gallery)
                                    <option value="{{ $gallery->id }}">{{ $gallery->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Apply
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tags Modal -->
        <div x-show="activeModal === 'tags'" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-tags') }}" method="POST">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Tags to Selected Photos</h3>
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @foreach ($tags ?? [] as $tag)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="replace" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Replace existing tags (instead of adding)</span>
                                </label>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Apply
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Location Modal -->
        <div x-show="activeModal === 'location'" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="field" value="location_name">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Set Location Name for Selected Photos</h3>
                            <input type="text" name="value" placeholder="e.g., Yosemite National Park, California" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-2 text-sm text-gray-500">This sets the display name. GPS coordinates from EXIF data are preserved.</p>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Apply
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="activeModal === 'delete'" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="activeModal = null"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.photos.bulk-action') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="photo_ids[]" :value="id">
                        </template>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium text-gray-900">Delete Photos</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">Are you sure you want to delete <span x-text="selectedCount" class="font-semibold"></span> photo(s)? This action cannot be undone.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Delete
                            </button>
                            <button type="button" @click="activeModal = null" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bulkActions() {
            return {
                selectedIds: [],
                selectedCount: 0,
                activeModal: null,

                updateSelection(event) {
                    const id = parseInt(event.target.value);
                    if (event.target.checked) {
                        if (!this.selectedIds.includes(id)) {
                            this.selectedIds.push(id);
                        }
                    } else {
                        this.selectedIds = this.selectedIds.filter(i => i !== id);
                    }
                    this.selectedCount = this.selectedIds.length;
                },

                toggleAll(event) {
                    const checkboxes = document.querySelectorAll('.photo-checkbox');
                    this.selectedIds = [];
                    checkboxes.forEach(cb => {
                        cb.checked = event.target.checked;
                        if (event.target.checked) {
                            this.selectedIds.push(parseInt(cb.value));
                        }
                    });
                    this.selectedCount = this.selectedIds.length;
                },

                showModal(type) {
                    if (this.selectedCount > 0) {
                        this.activeModal = type;
                    }
                }
            }
        }

        function processingStatus() {
            return {
                processingCount: 0,
                failedCount: 0,
                photos: [],
                pollInterval: null,

                init() {
                    this.refreshStatus();
                    // Poll every 5 seconds while there are processing photos
                    this.pollInterval = setInterval(() => {
                        if (this.processingCount > 0) {
                            this.refreshStatus();
                        }
                    }, 5000);
                },

                async refreshStatus() {
                    try {
                        const response = await fetch('{{ route("admin.photos.processing-status") }}');
                        const data = await response.json();
                        this.processingCount = data.processing_count;
                        this.failedCount = data.failed_count;
                        this.photos = data.photos;

                        // If processing is done and we had processing photos, refresh the page
                        if (this.processingCount === 0 && this.photos.length > 0 && this.photos.some(p => p.is_complete)) {
                            window.location.reload();
                        }
                    } catch (e) {
                        console.error('Failed to fetch processing status:', e);
                    }
                },

                destroy() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
