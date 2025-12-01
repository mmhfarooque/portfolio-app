<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tags') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Add New Tag -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Tag</h3>
                    <form method="POST" action="{{ route('admin.tags.store') }}" class="flex gap-4">
                        @csrf
                        <input type="text" name="name" required placeholder="Tag name" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            Add Tag
                        </button>
                    </form>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Existing Tags -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Existing Tags</h3>

                    @if ($tags->count() > 0)
                        <div class="flex flex-wrap gap-3">
                            @foreach ($tags as $tag)
                                <div x-data="{ editing: false }" class="inline-flex items-center bg-gray-100 rounded-full">
                                    <template x-if="!editing">
                                        <div class="flex items-center">
                                            <span class="px-4 py-2 text-sm text-gray-700">{{ $tag->name }}</span>
                                            <span class="text-xs text-gray-500 mr-2">({{ $tag->photos_count }})</span>
                                            <button @click="editing = true" class="text-gray-400 hover:text-gray-600 pr-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-gray-400 hover:text-red-600 pr-3">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                    <template x-if="editing">
                                        <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="flex items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $tag->name }}" required class="text-sm rounded-l-full border-gray-300 py-2 px-4 focus:border-blue-500 focus:ring-blue-500">
                                            <button type="submit" class="bg-blue-600 text-white px-3 py-2 text-sm hover:bg-blue-700">
                                                Save
                                            </button>
                                            <button type="button" @click="editing = false" class="bg-gray-300 text-gray-700 px-3 py-2 text-sm rounded-r-full hover:bg-gray-400">
                                                Cancel
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No tags yet. Create your first tag above.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
