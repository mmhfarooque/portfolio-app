<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Post;
use App\Services\SeoAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoAuditController extends Controller
{
    protected SeoAuditService $seoService;

    public function __construct(SeoAuditService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Display SEO audit dashboard.
     */
    public function index(): Response
    {
        $audit = $this->seoService->auditSite();

        return Inertia::render('Admin/Seo/Index', [
            'audit' => $audit,
        ]);
    }

    /**
     * Audit a specific photo.
     */
    public function photo(Photo $photo): Response
    {
        $photo->load('tags', 'category');
        $audit = $this->seoService->auditPhoto($photo);

        return Inertia::render('Admin/Seo/Photo', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'description' => $photo->description,
                'seo_title' => $photo->seo_title,
                'meta_description' => $photo->meta_description,
                'thumbnail_path' => $photo->thumbnail_path,
                'category' => $photo->category ? [
                    'id' => $photo->category->id,
                    'name' => $photo->category->name,
                ] : null,
                'tags' => $photo->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]),
            ],
            'audit' => $audit,
        ]);
    }

    /**
     * Audit a specific post.
     */
    public function post(Post $post): Response
    {
        $audit = $this->seoService->auditPost($post);

        return Inertia::render('Admin/Seo/Post', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'seo_title' => $post->seo_title,
                'meta_description' => $post->meta_description,
            ],
            'audit' => $audit,
        ]);
    }

    /**
     * Get audit data as JSON (for AJAX).
     */
    public function data()
    {
        $audit = $this->seoService->auditSite();

        return response()->json($audit);
    }
}
