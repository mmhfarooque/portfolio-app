<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Post;
use App\Services\SeoAuditService;
use Illuminate\Http\Request;

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
    public function index()
    {
        $audit = $this->seoService->auditSite();

        return view('admin.seo.index', compact('audit'));
    }

    /**
     * Audit a specific photo.
     */
    public function photo(Photo $photo)
    {
        $photo->load('tags', 'category');
        $audit = $this->seoService->auditPhoto($photo);

        return view('admin.seo.photo', compact('photo', 'audit'));
    }

    /**
     * Audit a specific post.
     */
    public function post(Post $post)
    {
        $audit = $this->seoService->auditPost($post);

        return view('admin.seo.post', compact('post', 'audit'));
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
