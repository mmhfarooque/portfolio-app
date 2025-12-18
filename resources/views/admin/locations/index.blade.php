<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Shooting Locations</h2>
            <a href="{{ route('admin.locations.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                Add Location
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                @if($locations->isEmpty())
                    <div class="p-6 text-center text-gray-500">No locations added yet.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City/Country</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photos Nearby</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Featured</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($locations as $location)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            @if($location->cover_image)
                                                <img src="{{ Storage::url($location->cover_image) }}" alt="{{ $location->name }}" class="w-10 h-10 rounded object-cover mr-3">
                                            @endif
                                            <div class="text-sm font-medium text-gray-900">{{ $location->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $location->city ?? '-' }}{{ $location->country ? ', ' . $location->country : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $location->nearby_photos_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($location->is_featured)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Featured</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('locations.show', $location) }}" target="_blank" class="text-gray-600 hover:text-gray-900 mr-3">View</a>
                                        <a href="{{ route('admin.locations.edit', $location) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                        <form action="{{ route('admin.locations.destroy', $location) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this location?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $locations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
