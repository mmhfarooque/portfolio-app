<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Blog Post') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Main Content -->
                            <div class="lg:col-span-2 space-y-6">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                    <input type="text" name="title" id="title" value="{{ old('title', $post->title) }}" required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Enter post title...">
                                </div>

                                <div>
                                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                    <input type="text" name="slug" id="slug" value="{{ old('slug', $post->slug) }}" required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="post-url-slug">
                                    <p class="mt-1 text-sm text-gray-500">URL: {{ url('/blog') }}/{{ $post->slug }}</p>
                                </div>

                                <div>
                                    <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                                    <textarea name="excerpt" id="excerpt" rows="3"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Brief summary of the post (optional)...">{{ old('excerpt', $post->excerpt) }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">A short summary shown in blog listings.</p>
                                </div>

                                <div>
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                                    <textarea name="content" id="content" rows="20" required
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                              placeholder="Write your post content here...">{{ old('content', $post->content) }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">You can use Markdown for formatting.</p>
                                </div>
                            </div>

                            <!-- Sidebar -->
                            <div class="space-y-6">
                                <!-- Status -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="draft" {{ old('status', $post->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="published" {{ old('status', $post->status) === 'published' ? 'selected' : '' }}>Published</option>
                                    </select>
                                </div>

                                <!-- Published Date -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">Publish Date</label>
                                    <input type="datetime-local" name="published_at" id="published_at"
                                           value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <!-- Category -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select name="category_id" id="category_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">No category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $post->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tags -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        @foreach ($tags as $tag)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                                       {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-600">{{ $tag->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Featured Image -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>

                                    @if ($post->featured_image)
                                        <div class="mb-3">
                                            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="Featured Image" class="w-full h-32 object-cover rounded">
                                            <label class="mt-2 flex items-center">
                                                <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-500 focus:ring-red-500">
                                                <span class="ml-2 text-sm text-red-600">Remove image</span>
                                            </label>
                                        </div>
                                    @endif

                                    <input type="file" name="featured_image" id="featured_image" accept="image/*"
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>

                                <!-- SEO -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-gray-700 mb-3">SEO Settings</h3>

                                    <div class="mb-3">
                                        <label for="seo_title" class="block text-xs font-medium text-gray-500 mb-1">SEO Title</label>
                                        <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title', $post->seo_title) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                               placeholder="Leave empty to use post title">
                                    </div>

                                    <div>
                                        <label for="meta_description" class="block text-xs font-medium text-gray-500 mb-1">Meta Description</label>
                                        <textarea name="meta_description" id="meta_description" rows="3"
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                  placeholder="Brief description for search engines...">{{ old('meta_description', $post->meta_description) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-8 pt-6 border-t">
                            <a href="{{ route('admin.posts.index') }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <div class="space-x-3">
                                @if ($post->status === 'published')
                                    <a href="{{ route('blog.show', $post) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        View Post
                                    </a>
                                @endif
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                    Update Post
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
