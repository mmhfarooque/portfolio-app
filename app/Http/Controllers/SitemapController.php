<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Support\Facades\Cache;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate and return the sitemap XML.
     */
    public function index(): Response
    {
        $photos = Photo::published()
            ->select(['slug', 'updated_at', 'display_path', 'watermarked_path'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $categories = Category::select(['slug', 'updated_at'])->get();

        $galleries = Gallery::where('is_published', true)
            ->select(['slug', 'updated_at'])
            ->get();

        $tags = Tag::select(['slug', 'updated_at'])->get();

        // Add blog posts to sitemap
        $posts = Post::where('status', 'published')
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $content = view('sitemap', compact('photos', 'categories', 'galleries', 'tags', 'posts'));

        return response($content)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate image sitemap for better image indexing.
     */
    public function images(): Response
    {
        // The whole file only changes when a photo does, so key the cache on
        // the newest photo rather than rebuilding 26+ entries on every crawl.
        $content = Cache::remember(
            'sitemap.images.' . (Photo::published()->max('updated_at') ?? 'empty'),
            now()->addDay(),
            function (): string {
                $photos = Photo::published()
                    ->with('category')
                    ->select(array_merge(
                        Photo::TILE_COLUMNS,
                        ['description', 'category_id', 'location_name', 'updated_at'],
                    ))
                    ->orderBy('updated_at', 'desc')
                    ->get();

                return view('sitemap-images', compact('photos'))->render();
            },
        );

        return response($content)
            ->header('Content-Type', 'application/xml');
    }
}
