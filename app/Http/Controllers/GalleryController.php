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
            // Inertia::scroll() marks the paginator's data array for appending on
            // partial reloads and normalises the page metadata the <InfiniteScroll>
            // component needs. The prop shape is unchanged, so photos.data still works.
            'photos' => Inertia::scroll($photos),
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

        // Get nearby photos using Haversine formula (tiered: 100km → 500km)
        $nearbyPhotos = collect();
        if ($photo->latitude && $photo->longitude) {
            $lat = $photo->latitude;
            $lng = $photo->longitude;
            $haversine = "(6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))) AS distance_km";

            foreach ([100, 500] as $radius) {
                $nearbyPhotos = Photo::published()
                    ->where('id', '!=', $photo->id)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw("id, title, slug, thumbnail_path, dominant_color, location_name, latitude, longitude, $haversine", [$lat, $lng, $lat])
                    ->having('distance_km', '<', $radius)
                    ->orderBy('distance_km')
                    ->take(6)
                    ->get();

                if ($nearbyPhotos->isNotEmpty()) break;
            }
        }

        // Get related photos (same category or tags), excluding nearby ones
        $excludeIds = $nearbyPhotos->pluck('id')->push($photo->id)->toArray();
        $relatedPhotos = Photo::published()
            ->whereNotIn('id', $excludeIds)
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
            ->get(['id', 'title', 'slug', 'thumbnail_path', 'dominant_color']);

        return Inertia::render('Public/Gallery/Show', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'description' => $photo->description,
                'story' => $photo->story,
                'location_name' => $photo->location_name,
                'latitude' => $photo->latitude,
                'longitude' => $photo->longitude,
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
                'captured_at' => $photo->captured_at?->toISOString(),
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
            'nearbyPhotos' => $nearbyPhotos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
                'dominant_color' => $p->dominant_color,
                'location_name' => $p->location_name,
                'distance_km' => round($p->distance_km, 1),
            ]),
            'relatedPhotos' => $relatedPhotos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
                'dominant_color' => $p->dominant_color,
            ]),
            'previousPhoto' => $previousPhoto,
            'nextPhoto' => $nextPhoto,
        ]);
    }

    /**
     * Display photos by category.
     */
    /**
     * Social share card — a JPEG derivative for og:image / social scrapers.
     * AVIF og:image breaks on several platforms (Pinterest, iOS Messenger),
     * so social cards are always served as JPEG, lazily transcoded and cached.
     */
    public function socialCard(Photo $photo)
    {
        abort_unless($photo->status === 'published', 404);

        $source = $photo->watermarked_path ?? $photo->display_path;
        abort_unless((bool) $source, 404);

        $sourceFile = storage_path('app/public/' . $source);
        abort_unless(file_exists($sourceFile), 404);

        $cacheRel = 'photos/social/' . pathinfo($source, PATHINFO_FILENAME) . '.jpg';
        $cacheFile = storage_path('app/public/' . $cacheRel);

        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($sourceFile)) {
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }
            $img = str_ends_with($source, '.avif')
                ? imagecreatefromavif($sourceFile)
                : imagecreatefromstring(file_get_contents($sourceFile));
            abort_if($img === false, 500);

            $w = imagesx($img);
            if ($w > 1200) {
                $img = imagescale($img, 1200, (int) round(imagesy($img) * 1200 / $w));
            }
            imagejpeg($img, $cacheFile, 85);
            imagedestroy($img);
        }

        return response()->file($cacheFile, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Vertical 4:5 social card (1080×1350) — the photo fit full-width on a
     * blurred, darkened fill of itself. Instagram's publishing API rejects
     * anything taller than 4:5, so this is the tallest feed-safe treatment
     * for landscape photos.
     */
    public function socialCardVertical(Photo $photo)
    {
        abort_unless($photo->status === 'published', 404);

        $source = $photo->watermarked_path ?? $photo->display_path;
        abort_unless((bool) $source, 404);

        $sourceFile = storage_path('app/public/' . $source);
        abort_unless(file_exists($sourceFile), 404);

        $cacheRel = 'photos/social/' . pathinfo($source, PATHINFO_FILENAME) . '-45.jpg';
        $cacheFile = storage_path('app/public/' . $cacheRel);

        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($sourceFile)) {
            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }
            $src = str_ends_with($source, '.avif')
                ? imagecreatefromavif($sourceFile)
                : imagecreatefromstring(file_get_contents($sourceFile));
            abort_if($src === false, 500);

            $sw = imagesx($src);
            $sh = imagesy($src);
            $cw = 1080;
            $ch = 1350;
            $canvas = imagecreatetruecolor($cw, $ch);

            // Background: fill-crop the photo to the canvas, blur hard, darken.
            // Blur trick: GD gaussian is weak, so blur a 1/20-scale copy and
            // scale it back up — cheap and strong.
            $bgScale = max($cw / $sw, $ch / $sh);
            $bw = (int) ceil($sw * $bgScale);
            $bh = (int) ceil($sh * $bgScale);
            $bg = imagescale($src, max(1, (int) round($bw / 20)), max(1, (int) round($bh / 20)));
            for ($i = 0; $i < 8; $i++) {
                imagefilter($bg, IMG_FILTER_GAUSSIAN_BLUR);
            }
            imagefilter($bg, IMG_FILTER_BRIGHTNESS, -55);
            $bgFull = imagescale($bg, $bw, $bh, IMG_BILINEAR_FIXED);
            imagecopy($canvas, $bgFull, (int) (($cw - $bw) / 2), (int) (($ch - $bh) / 2), 0, 0, $bw, $bh);
            imagedestroy($bg);
            imagedestroy($bgFull);

            // Foreground: the photo fit inside with a small margin.
            $margin = 40;
            $fgScale = min(($cw - 2 * $margin) / $sw, ($ch - 2 * $margin) / $sh);
            $fw = (int) round($sw * $fgScale);
            $fh = (int) round($sh * $fgScale);
            $fg = imagescale($src, $fw, $fh);
            imagecopy($canvas, $fg, (int) (($cw - $fw) / 2), (int) (($ch - $fh) / 2), 0, 0, $fw, $fh);
            imagedestroy($fg);
            imagedestroy($src);

            imagejpeg($canvas, $cacheFile, 85);
            imagedestroy($canvas);
        }

        return response()->file($cacheFile, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

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
