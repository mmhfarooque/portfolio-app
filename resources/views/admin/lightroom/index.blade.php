<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lightroom Metadata Sync</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Import XMP Metadata</h3>
                    <p class="text-gray-600 text-sm">Upload XMP sidecar files from Lightroom to sync metadata with your photos. Files will be matched by original filename.</p>
                </div>

                <form action="{{ route('admin.lightroom.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">XMP Files</label>
                        <input type="file" name="xmp_files[]" multiple accept=".xmp"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-sm text-gray-500">Select one or more .xmp sidecar files</p>
                    </div>

                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="overwrite" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Overwrite existing data</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="sync_tags" value="1" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Sync keywords as tags</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Process XMP Files
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Supported Fields</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>Title (dc:title)</li>
                        <li>Description (dc:description)</li>
                        <li>Keywords/Tags (dc:subject)</li>
                        <li>Rating (xmp:Rating)</li>
                        <li>Location (photoshop:City, State, Country)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
