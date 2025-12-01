<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Tag;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Display the homepage with featured photos.
     */
    public function home()
    {
        $featuredPhotos = Photo::published()
            ->featured()
            ->with('category')
            ->latest('captured_at')
            ->take(12)
            ->get();

        $recentPhotos = Photo::published()
            ->with('category')
            ->latest('created_at')
            ->take(8)
            ->get();

        $categories = Category::withCount('publishedPhotos')
            ->orderBy('sort_order')
            ->get();

        return view('gallery.home', compact('featuredPhotos', 'recentPhotos', 'categories'));
    }

    /**
     * Display all photos with optional filtering.
     */
    public function index(Request $request)
    {
        $query = Photo::published()->with(['category', 'tags']);

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
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('story', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhereJsonContains('exif_data->Make', $search)
                  ->orWhereJsonContains('exif_data->Model', $search);
            });
        }

        $photos = $query->latest('captured_at')->paginate(24)->withQueryString();

        $categories = Category::withCount('publishedPhotos')
            ->orderBy('sort_order')
            ->get();

        $tags = Tag::withCount('publishedPhotos')
            ->orderBy('name')
            ->get();

        $currentCategory = $request->category ? Category::where('slug', $request->category)->first() : null;
        $currentTag = $request->tag ? Tag::where('slug', $request->tag)->first() : null;

        // Count photos with location for map link
        $photosWithLocation = Photo::published()->withLocation()->count();

        return view('gallery.index', compact('photos', 'categories', 'tags', 'currentCategory', 'currentTag', 'photosWithLocation'));
    }

    /**
     * Display all photos on a map.
     */
    public function map()
    {
        $photos = Photo::published()
            ->withLocation()
            ->select(['id', 'title', 'slug', 'thumbnail_path', 'latitude', 'longitude', 'location_name'])
            ->get();

        return view('gallery.map', compact('photos'));
    }

    /**
     * Display a single photo.
     */
    public function show(Photo $photo)
    {
        if ($photo->status !== 'published') {
            abort(404);
        }

        $photo->load(['category', 'gallery', 'tags']);
        $photo->incrementViews();

        // Get previous and next photos (ordered by created_at as fallback)
        $previousPhoto = Photo::published()
            ->where('id', '!=', $photo->id)
            ->where(function ($query) use ($photo) {
                $query->where('created_at', '>', $photo->created_at)
                    ->orWhere(function ($q) use ($photo) {
                        $q->where('created_at', $photo->created_at)
                          ->where('id', '<', $photo->id);
                    });
            })
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'desc')
            ->first();

        $nextPhoto = Photo::published()
            ->where('id', '!=', $photo->id)
            ->where(function ($query) use ($photo) {
                $query->where('created_at', '<', $photo->created_at)
                    ->orWhere(function ($q) use ($photo) {
                        $q->where('created_at', $photo->created_at)
                          ->where('id', '>', $photo->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->first();

        // Get related photos (same category or tags)
        $relatedPhotos = Photo::published()
            ->where('id', '!=', $photo->id)
            ->where(function ($query) use ($photo) {
                if ($photo->category_id) {
                    $query->where('category_id', $photo->category_id);
                }
                $query->orWhereHas('tags', function ($q) use ($photo) {
                    $q->whereIn('tags.id', $photo->tags->pluck('id'));
                });
            })
            ->inRandomOrder()
            ->take(6)
            ->get();

        return view('gallery.show', compact('photo', 'relatedPhotos', 'previousPhoto', 'nextPhoto'));
    }

    /**
     * Display photos by category.
     */
    public function category(Category $category)
    {
        $photos = $category->publishedPhotos()
            ->with('tags')
            ->latest('captured_at')
            ->paginate(24);

        return view('gallery.category', compact('category', 'photos'));
    }

    /**
     * Display a public gallery.
     */
    public function gallery(Gallery $gallery)
    {
        if (!$gallery->is_published) {
            abort(404);
        }

        $photos = $gallery->photos()
            ->published()
            ->latest('captured_at')
            ->paginate(24);

        return view('gallery.gallery', compact('gallery', 'photos'));
    }

    /**
     * Display photos by tag.
     */
    public function tag(Tag $tag)
    {
        $photos = $tag->publishedPhotos()
            ->with('category')
            ->latest('captured_at')
            ->paginate(24);

        return view('gallery.tag', compact('tag', 'photos'));
    }
}
