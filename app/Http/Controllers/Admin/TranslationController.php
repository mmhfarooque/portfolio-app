<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Post;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Display translations dashboard.
     */
    public function index(): Response
    {
        $locales = $this->translationService->getLocales();

        // Get photos and posts with translation status
        $photos = Photo::published()
            ->select('id', 'title', 'slug')
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($photo) {
                $photo->translation_status = $this->translationService->getCompletionPercentage(
                    $photo,
                    ['title', 'description', 'meta_description']
                );
                return $photo;
            });

        $posts = Post::published()
            ->select('id', 'title', 'slug')
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($post) {
                $post->translation_status = $this->translationService->getCompletionPercentage(
                    $post,
                    ['title', 'excerpt', 'content', 'meta_description']
                );
                return $post;
            });

        return Inertia::render('Admin/Translations/Index', [
            'locales' => $locales,
            'photos' => $photos,
            'posts' => $posts,
        ]);
    }

    /**
     * Edit translations for a photo.
     */
    public function editPhoto(Photo $photo): Response
    {
        $locales = $this->translationService->getLocales();
        $translations = $this->translationService->getAllTranslations($photo);
        $fields = ['title', 'description', 'meta_description'];

        return Inertia::render('Admin/Translations/EditPhoto', [
            'photo' => $photo,
            'locales' => $locales,
            'translations' => $translations,
            'fields' => $fields,
        ]);
    }

    /**
     * Update translations for a photo.
     */
    public function updatePhoto(Request $request, Photo $photo)
    {
        $locales = array_keys($this->translationService->getLocales());

        $rules = [];
        foreach ($locales as $locale) {
            if ($locale === config('app.fallback_locale', 'en')) continue;
            $rules["translations.{$locale}.title"] = 'nullable|string|max:255';
            $rules["translations.{$locale}.description"] = 'nullable|string';
            $rules["translations.{$locale}.meta_description"] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $this->translationService->setTranslations($photo, $request->input('translations', []));

        return redirect()->route('admin.translations.index')
            ->with('success', 'Photo translations saved.');
    }

    /**
     * Edit translations for a post.
     */
    public function editPost(Post $post): Response
    {
        $locales = $this->translationService->getLocales();
        $translations = $this->translationService->getAllTranslations($post);
        $fields = ['title', 'excerpt', 'content', 'meta_description'];

        return Inertia::render('Admin/Translations/EditPost', [
            'post' => $post,
            'locales' => $locales,
            'translations' => $translations,
            'fields' => $fields,
        ]);
    }

    /**
     * Update translations for a post.
     */
    public function updatePost(Request $request, Post $post)
    {
        $locales = array_keys($this->translationService->getLocales());

        $rules = [];
        foreach ($locales as $locale) {
            if ($locale === config('app.fallback_locale', 'en')) continue;
            $rules["translations.{$locale}.title"] = 'nullable|string|max:255';
            $rules["translations.{$locale}.excerpt"] = 'nullable|string';
            $rules["translations.{$locale}.content"] = 'nullable|string';
            $rules["translations.{$locale}.meta_description"] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $this->translationService->setTranslations($post, $request->input('translations', []));

        return redirect()->route('admin.translations.index')
            ->with('success', 'Post translations saved.');
    }
}
