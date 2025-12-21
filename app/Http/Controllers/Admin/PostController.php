<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     */
    public function index(Request $request): Response
    {
        $query = Post::with(['category', 'tags'])
            ->where('user_id', auth()->id())
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $posts = $query->paginate(20);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): Response
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Posts/Create', [
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:10240', // 10MB
            'status' => 'required|in:draft,published',
            'category_id' => 'nullable|exists:categories,id',
            'seo_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(6);
        $validated['user_id'] = auth()->id();

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $this->processFeaturedImage($request->file('featured_image'));
        }

        // Set published_at if publishing
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);

        // Sync tags
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        LoggingService::modelCreated($post);

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post created successfully.');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post->load('tags'),
            'categories' => $categories,
            'tags' => $tags
        ]);
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:posts,slug,' . $post->id,
            ],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:10240', // 10MB
            'status' => 'required|in:draft,published',
            'category_id' => 'nullable|exists:categories,id',
            'seo_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'remove_image' => 'boolean',
        ], [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This URL slug is already taken.',
        ]);

        // Handle image removal
        if ($request->boolean('remove_image') && $post->hasFeaturedImage()) {
            Storage::disk('public')->delete($post->featured_image);
            $validated['featured_image'] = null;
        }

        // Handle new featured image
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($post->hasFeaturedImage()) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $validated['featured_image'] = $this->processFeaturedImage($request->file('featured_image'));
        } else {
            unset($validated['featured_image']);
        }

        // Handle published_at
        if ($validated['status'] === 'published' && !$post->published_at && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        unset($validated['remove_image']);

        $post->update($validated);

        // Sync tags
        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        } else {
            $post->tags()->detach();
        }

        LoggingService::modelUpdated($post, $post->getChanges());

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        // Delete featured image
        if ($post->hasFeaturedImage()) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $postTitle = $post->title;
        $postId = $post->id;

        $post->delete();

        LoggingService::activity(
            'post.deleted',
            "Post deleted: {$postTitle}",
            null,
            ['post_id' => $postId, 'title' => $postTitle]
        );

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }

    /**
     * Process and store a featured image.
     */
    protected function processFeaturedImage($file): string
    {
        $image = Image::read($file->getRealPath());

        // Resize if too large
        if ($image->width() > 1920) {
            $image->scale(width: 1920);
        }

        // Generate filename
        $filename = Str::uuid() . '.webp';
        $path = 'posts/' . $filename;

        // Ensure directory exists
        $storagePath = storage_path('app/public/posts');
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save as WebP
        $image->toWebp(85)->save(storage_path('app/public/' . $path));

        return $path;
    }
}
