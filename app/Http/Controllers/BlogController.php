<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $query = Post::published()
            ->with(['user', 'category', 'tags'])
            ->latest('published_at');

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate(12)->withQueryString();

        $categories = Category::withCount(['posts' => function ($query) {
            $query->published();
        }])->orderBy('name')->get();

        $tags = Tag::withCount(['posts' => function ($query) {
            $query->published();
        }])->orderBy('name')->get();

        $currentCategory = $request->category ? Category::where('slug', $request->category)->first() : null;
        $currentTag = $request->tag ? Tag::where('slug', $request->tag)->first() : null;

        return view('blog.index', compact('posts', 'categories', 'tags', 'currentCategory', 'currentTag'));
    }

    /**
     * Display a single blog post.
     */
    public function show(Post $post)
    {
        if ($post->status !== 'published') {
            abort(404);
        }

        $post->load(['user', 'category', 'tags']);
        $post->incrementViews();

        // Get related posts
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                if ($post->category_id) {
                    $query->where('category_id', $post->category_id);
                }
                $query->orWhereHas('tags', function ($q) use ($post) {
                    $q->whereIn('tags.id', $post->tags->pluck('id'));
                });
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        // Get previous and next posts
        $previousPost = Post::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('published_at', '>', $post->published_at)
                    ->orWhere(function ($q) use ($post) {
                        $q->where('published_at', $post->published_at)
                          ->where('id', '<', $post->id);
                    });
            })
            ->orderBy('published_at', 'asc')
            ->orderBy('id', 'desc')
            ->first();

        $nextPost = Post::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('published_at', '<', $post->published_at)
                    ->orWhere(function ($q) use ($post) {
                        $q->where('published_at', $post->published_at)
                          ->where('id', '>', $post->id);
                    });
            })
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'asc')
            ->first();

        return view('blog.show', compact('post', 'relatedPosts', 'previousPost', 'nextPost'));
    }
}
