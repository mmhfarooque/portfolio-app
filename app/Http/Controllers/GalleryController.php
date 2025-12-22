<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    /**
     * Display the homepage with featured photos.
     */
    public function home(): Response
    {
        $featuredPhotos = Photo::published()
            ->featured()
            ->with('category')
            ->latest('captured_at')
            ->take(12)
            ->get()
            ->map(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'display_path' => $photo->display_path,
                'category' => $photo->category ? [
                    'name' => $photo->category->name,
                    'slug' => $photo->category->slug,
                ] : null,
            ]);

        $recentPhotos = Photo::published()
            ->with('category')
            ->latest('created_at')
            ->take(8)
            ->get()
            ->map(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'category' => $photo->category ? [
                    'name' => $photo->category->name,
                    'slug' => $photo->category->slug,
                ] : null,
            ]);

        $categories = Category::withCount('publishedPhotos')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'published_photos_count' => $cat->published_photos_count,
            ]);

        return Inertia::render('Public/Home', [
            'featuredPhotos' => $featuredPhotos,
            'recentPhotos' => $recentPhotos,
            'categories' => $categories,
        ]);
    }

    /**
     * Display all photos with optional filtering.
     */
    public function index(Request $request): Response
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

        $photos = $query->latest('captured_at')->paginate(24)->withQueryString()->through(fn($photo) => [
            'id' => $photo->id,
            'title' => $photo->title,
            'slug' => $photo->slug,
            'thumbnail_path' => $photo->thumbnail_path,
            'category' => $photo->category ? [
                'id' => $photo->category->id,
                'name' => $photo->category->name,
                'slug' => $photo->category->slug,
            ] : null,
            'tags' => $photo->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
        ]);

        $categories = Category::withCount('publishedPhotos')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'published_photos_count']);

        $tags = Tag::withCount('publishedPhotos')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'published_photos_count']);

        $currentCategory = $request->category ? Category::where('slug', $request->category)->first(['id', 'name', 'slug']) : null;
        $currentTag = $request->tag ? Tag::where('slug', $request->tag)->first(['id', 'name', 'slug']) : null;

        // Count photos with location for map link
        $photosWithLocation = Photo::published()->withLocation()->count();

        return Inertia::render('Public/Gallery/Index', [
            'photos' => $photos,
            'categories' => $categories,
            'tags' => $tags,
            'currentCategory' => $currentCategory,
            'currentTag' => $currentTag,
            'photosWithLocation' => $photosWithLocation,
            'filters' => [
                'category' => $request->category,
                'tag' => $request->tag,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Display all photos on a map.
     */
    public function map(): Response
    {
        $photos = Photo::published()
            ->withLocation()
            ->select(['id', 'title', 'slug', 'thumbnail_path', 'latitude', 'longitude', 'location_name'])
            ->get()
            ->map(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'latitude' => $photo->latitude,
                'longitude' => $photo->longitude,
                'location_name' => $photo->location_name,
            ]);

        return Inertia::render('Public/Gallery/Map', [
            'photos' => $photos,
        ]);
    }

    /**
     * Display a single photo.
     */
    public function show(Photo $photo): Response
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
            ->first(['id', 'title', 'slug', 'thumbnail_path']);

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
            ->first(['id', 'title', 'slug', 'thumbnail_path']);

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
            ->get(['id', 'title', 'slug', 'thumbnail_path']);

        return Inertia::render('Public/Gallery/Show', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'description' => $photo->description,
                'story' => $photo->story,
                'location_name' => $photo->location_name,
                'display_path' => $photo->display_path,
                'watermarked_path' => $photo->watermarked_path,
                'width' => $photo->width,
                'height' => $photo->height,
                'views' => $photo->views,
                'likes_count' => $photo->likes_count ?? 0,
                'comments_count' => $photo->comments_count ?? 0,
                'formatted_exif' => $photo->formatted_exif,
                'seo_title' => $photo->seo_title,
                'meta_description' => $photo->meta_description,
                'category' => $photo->category ? [
                    'id' => $photo->category->id,
                    'name' => $photo->category->name,
                    'slug' => $photo->category->slug,
                ] : null,
                'gallery' => $photo->gallery ? [
                    'id' => $photo->gallery->id,
                    'name' => $photo->gallery->name,
                    'slug' => $photo->gallery->slug,
                ] : null,
                'tags' => $photo->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ]),
            ],
            'relatedPhotos' => $relatedPhotos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
            ]),
            'previousPhoto' => $previousPhoto,
            'nextPhoto' => $nextPhoto,
        ]);
    }

    /**
     * Display photos by category.
     */
    public function category(Category $category): Response
    {
        $photos = $category->publishedPhotos()
            ->with('tags')
            ->latest('captured_at')
            ->paginate(24)
            ->through(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
            ]);

        return Inertia::render('Public/Gallery/Category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'photos' => $photos,
        ]);
    }

    /**
     * Display a public gallery.
     */
    public function gallery(Request $request, Gallery $gallery): Response
    {
        if (!$gallery->is_published) {
            abort(404);
        }

        // Check if gallery is password protected
        $needsPassword = $gallery->isPasswordProtected() && !$gallery->hasAccess();

        $photos = null;
        if (!$needsPassword) {
            $photos = $gallery->photos()
                ->published()
                ->latest('captured_at')
                ->paginate(24)
                ->through(fn($photo) => [
                    'id' => $photo->id,
                    'title' => $photo->title,
                    'slug' => $photo->slug,
                    'thumbnail_path' => $photo->thumbnail_path,
                ]);
        }

        return Inertia::render('Public/Gallery/GalleryView', [
            'gallery' => [
                'id' => $gallery->id,
                'name' => $gallery->name,
                'slug' => $gallery->slug,
                'description' => $gallery->description,
            ],
            'photos' => $photos ?? ['data' => [], 'links' => []],
            'needsPassword' => $needsPassword,
        ]);
    }

    /**
     * Verify gallery password.
     */
    public function verifyGalleryPassword(Request $request, Gallery $gallery)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if ($gallery->verifyPassword($request->password)) {
            $gallery->grantAccess();
            return redirect()->route('gallery.show', $gallery);
        }

        return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
    }

    /**
     * Display photos by tag.
     */
    public function tag(Tag $tag): Response
    {
        $photos = $tag->publishedPhotos()
            ->with('category')
            ->latest('captured_at')
            ->paginate(24)
            ->through(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
            ]);

        return Inertia::render('Public/Gallery/Tag', [
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'photos' => $photos,
        ]);
    }
}
