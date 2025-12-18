<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Social Media</h2>
            <a href="{{ route('admin.social.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                New Post
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total_posts'] }}</div>
                    <div class="text-sm text-gray-500">Total Posts</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['published'] }}</div>
                    <div class="text-sm text-gray-500">Published</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['scheduled'] }}</div>
                    <div class="text-sm text-gray-500">Scheduled</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</div>
                    <div class="text-sm text-gray-500">Failed</div>
                </div>
            </div>

            <!-- Connected Platforms -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Connected Platforms</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($platforms as $key => $platform)
                            <div class="border rounded-lg p-4 {{ $platform['connected'] ? 'border-green-200 bg-green-50' : 'border-gray-200' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $platform['connected'] ? 'bg-green-100' : 'bg-gray-100' }} flex items-center justify-center">
                                            @if($key === 'twitter')
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                                </svg>
                                            @elseif($key === 'facebook')
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $platform['name'] }}</div>
                                            <div class="text-sm {{ $platform['connected'] ? 'text-green-600' : 'text-gray-500' }}">
                                                {{ $platform['connected'] ? 'Connected' : 'Not connected' }}
                                            </div>
                                        </div>
                                    </div>
                                    @if(!$platform['connected'])
                                        <button class="text-sm text-indigo-600 hover:text-indigo-800">Connect</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm text-gray-500">
                        To connect accounts, configure your API credentials in settings and use OAuth flow.
                    </p>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Posts</h3>
                </div>
                @if($recentPosts->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        No social posts yet. Create your first post!
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Content</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($recentPosts as $post)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if($post->photo)
                                                <img src="{{ $post->photo->getImageUrl('thumbnail') }}" alt="" class="w-10 h-10 rounded object-cover">
                                                <span class="text-sm text-gray-900">{{ Str::limit($post->photo->title, 30) }}</span>
                                            @elseif($post->post)
                                                <span class="text-sm text-gray-900">{{ Str::limit($post->post->title, 30) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="capitalize text-sm text-gray-700">{{ $post->platform }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($post->status === 'published')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Published</span>
                                        @elseif($post->status === 'scheduled')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Scheduled</span>
                                        @elseif($post->status === 'failed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $post->published_at?->format('M d, Y H:i') ?? $post->scheduled_at?->format('M d, Y H:i') ?? $post->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if($post->external_url)
                                            <a href="{{ $post->external_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                        @endif
                                        @if($post->status === 'pending' || $post->status === 'scheduled')
                                            <form action="{{ route('admin.social.publish', $post) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Publish Now</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.social.destroy', $post) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this post?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
