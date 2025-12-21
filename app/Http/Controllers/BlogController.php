<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request): Response
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

        $posts = $query->paginate(12)->withQueryString()->through(fn($post) => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'featured_image' => $post->featured_image,
            'published_at' => $post->published_at?->format('M d, Y'),
            'reading_time' => $post->reading_time,
            'category' => $post->category ? [
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'user' => $post->user ? [
                'name' => $post->user->name,
            ] : null,
        ]);

        $categories = Category::withCount(['posts' => function ($query) {
            $query->published();
        }])->orderBy('name')->get()->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'posts_count' => $cat->posts_count,
        ]);

        $tags = Tag::withCount(['posts' => function ($query) {
            $query->published();
        }])->orderBy('name')->get()->map(fn($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'posts_count' => $tag->posts_count,
        ]);

        $currentCategory = $request->category ? Category::where('slug', $request->category)->first(['id', 'name', 'slug']) : null;
        $currentTag = $request->tag ? Tag::where('slug', $request->tag)->first(['id', 'name', 'slug']) : null;

        return Inertia::render('Public/Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
            'currentCategory' => $currentCategory,
            'currentTag' => $currentTag,
            'filters' => [
                'category' => $request->category,
                'tag' => $request->tag,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Display a single blog post.
     */
    public function show(Post $post): Response
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
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt,
                'featured_image' => $p->featured_image,
                'published_at' => $p->published_at?->format('M d, Y'),
            ]);

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
            ->first(['id', 'title', 'slug']);

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
            ->first(['id', 'title', 'slug']);

        return Inertia::render('Public/Blog/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image' => $post->featured_image,
                'published_at' => $post->published_at?->format('M d, Y'),
                'reading_time' => $post->reading_time,
                'views' => $post->views,
                'seo_title' => $post->seo_title,
                'meta_description' => $post->meta_description,
                'category' => $post->category ? [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'tags' => $post->tags->map(fn($tag) => [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ]),
                'user' => $post->user ? [
                    'name' => $post->user->name,
                ] : null,
            ],
            'relatedPosts' => $relatedPosts,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
        ]);
    }
}
