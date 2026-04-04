<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Setting;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrontPageController extends Controller
{
    public function __construct(
        protected ThemeService $themeService
    ) {}

    /**
     * Display the front page / homepage with CV/Resume layout.
     */
    public function index(Request $request): Response
    {
        // Get theme data
        $currentTheme = $this->themeService->getCurrentTheme();
        $theme = $this->themeService->getTheme($currentTheme);
        $themeData = [
            'name' => $currentTheme,
            'colors' => $theme['colors'] ?? [],
            'styles' => $theme['styles'] ?? [],
            'isDark' => $theme['is_dark'] ?? false,
        ];

        // Get profile settings
        $profile = [
            'image' => Setting::get('profile_image'),
            'name' => Setting::get('profile_name', 'Your Name'),
            'title' => Setting::get('profile_title', 'Web Developer & Photographer'),
            'tagline' => Setting::get('profile_tagline'),
            'bio' => Setting::get('profile_bio'),
            'location' => Setting::get('profile_location'),
            'resume_pdf' => Setting::get('profile_resume_pdf'),
        ];

        // Get contact info
        $contact = [
            'email' => Setting::get('contact_email'),
            'phone' => Setting::get('contact_phone'),
            'whatsapp' => Setting::get('contact_whatsapp'),
            'location' => Setting::get('contact_location'),
        ];

        // Get social links
        $social = [
            'github' => Setting::get('social_github'),
            'linkedin' => Setting::get('social_linkedin'),
            'instagram' => Setting::get('social_instagram'),
            'twitter' => Setting::get('social_twitter'),
            'facebook' => Setting::get('social_facebook'),
            'youtube' => Setting::get('social_youtube'),
            '500px' => Setting::get('social_500px'),
        ];

        // Get skills
        $skills = [
            'development' => $this->parseSkills(Setting::get('skills_development', '')),
            'photography' => $this->parseSkills(Setting::get('skills_photography', '')),
            'other' => $this->parseSkills(Setting::get('skills_other', '')),
        ];

        // Get featured photos for the portfolio section
        $featuredPhotos = Photo::published()
            ->featured()
            ->with('category')
            ->latest('captured_at')
            ->take(6)
            ->get();

        // Get recent photos if no featured
        if ($featuredPhotos->isEmpty()) {
            $featuredPhotos = Photo::published()
                ->with('category')
                ->latest('created_at')
                ->take(6)
                ->get();
        }

        // Gallery data — all published photos with filtering
        $galleryQuery = Photo::published()->with(['category', 'tags']);

        if ($request->filled('category')) {
            $galleryQuery->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('tag')) {
            $galleryQuery->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $galleryQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        $galleryPhotos = $galleryQuery->latest('captured_at')->paginate(24)->withQueryString()->through(fn($photo) => [
            'id' => $photo->id,
            'title' => $photo->title,
            'slug' => $photo->slug,
            'thumbnail_path' => $photo->thumbnail_path,
            'dominant_color' => $photo->dominant_color,
            'category' => $photo->category ? [
                'id' => $photo->category->id,
                'name' => $photo->category->name,
                'slug' => $photo->category->slug,
            ] : null,
        ]);

        $categories = Category::withCount('publishedPhotos')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'published_photos_count']);

        $currentCategory = $request->category ? Category::where('slug', $request->category)->first(['id', 'name', 'slug']) : null;
        $currentTag = $request->tag ? Tag::where('slug', $request->tag)->first(['id', 'name', 'slug']) : null;

        return Inertia::render('Public/Home', [
            'profile' => $profile,
            'contact' => $contact,
            'social' => $social,
            'skills' => $skills,
            'theme' => $themeData,
            'featuredPhotos' => $featuredPhotos->map(fn($photo) => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'thumbnail_path' => $photo->thumbnail_path,
                'display_path' => $photo->display_path,
                'category' => $photo->category?->name,
            ]),
            'galleryPhotos' => $galleryPhotos,
            'categories' => $categories,
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
     * Parse comma-separated skills into array.
     */
    private function parseSkills(?string $skills): array
    {
        if (empty($skills)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $skills)));
    }
}
