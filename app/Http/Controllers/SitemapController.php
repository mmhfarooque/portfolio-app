<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
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
            ->select(['slug', 'updated_at', 'display_path'])
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
        $photos = Photo::published()
            ->with('category')
            ->select(['id', 'slug', 'title', 'description', 'display_path', 'category_id', 'location_name', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $content = view('sitemap-images', compact('photos'));

        return response($content)
            ->header('Content-Type', 'application/xml');
    }
}
