<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class FeedController extends Controller
{
    /**
     * RSS 2.0 Feed for blog posts.
     */
    public function rss()
    {
        $posts = Cache::remember('feed.rss', 1800, function () {
            return Post::published()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->take(20)
                ->get();
        });

        $siteName = Setting::get('site_name', config('app.name'));
        $siteDescription = Setting::get('site_description', 'Photography Portfolio');

        return response()
            ->view('feed.rss', compact('posts', 'siteName', 'siteDescription'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Atom Feed for blog posts.
     */
    public function atom()
    {
        $posts = Cache::remember('feed.atom', 1800, function () {
            return Post::published()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->take(20)
                ->get();
        });

        $siteName = Setting::get('site_name', config('app.name'));
        $siteDescription = Setting::get('site_description', 'Photography Portfolio');

        return response()
            ->view('feed.atom', compact('posts', 'siteName', 'siteDescription'))
            ->header('Content-Type', 'application/atom+xml; charset=utf-8');
    }
}
