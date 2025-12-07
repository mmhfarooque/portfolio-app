<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Tag;
use App\Services\PhotoProcessingService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhotoController extends Controller
{
    public function __construct(
        protected PhotoProcessingService $photoService
    ) {}

    /**
     * Display a listing of photos.
     */
    public function index(Request $request)
    {
        $query = Photo::with(['category', 'gallery', 'tags'])
            ->where('user_id', auth()->id())
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $photos = $query->paginate(24);
        $categories = Category::orderBy('name')->get();
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.photos.index', compact('photos', 'categories', 'galleries', 'tags'));
    }

    /**
     * Show the form for creating a new photo.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.photos.create', compact('categories', 'galleries', 'tags'));
    }

    /**
     * Store a newly created photo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|file|mimes:jpg,jpeg,png,gif,webp,heic,heif|max:51200', // 50MB max, includes HEIC/HEIF
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $uploadedCount = 0;

        foreach ($request->file('photos') as $file) {
            $this->photoService->processUpload(
                $file,
                auth()->id(),
                $request->category_id
            );
            $uploadedCount++;
        }

        return redirect()
            ->route('admin.photos.index')
            ->with('success', "{$uploadedCount} photo(s) uploaded successfully.");
    }

    /**
     * Display the specified photo.
     */
    public function show(Photo $photo)
    {
        $this->authorize('view', $photo);

        return view('admin.photos.show', compact('photo'));
    }

    /**
     * Show the form for editing the specified photo.
     */
    public function edit(Photo $photo)
    {
        $this->authorize('update', $photo);

        $categories = Category::orderBy('name')->get();
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.photos.edit', compact('photo', 'categories', 'galleries', 'tags'));
    }

    /**
     * Update the specified photo.
     */
    public function update(Request $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:photos,slug,' . $photo->id,
            ],
            'description' => 'nullable|string',
            'story' => 'nullable|string',
            'location_name' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'category_id' => 'nullable|exists:categories,id',
            'gallery_id' => 'nullable|exists:galleries,id',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ], [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This URL slug is already taken. Please choose a different one.',
        ]);

        $photo->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'story' => $validated['story'],
            'location_name' => $validated['location_name'],
            'seo_title' => $validated['seo_title'],
            'meta_description' => $validated['meta_description'],
            'category_id' => $validated['category_id'],
            'gallery_id' => $validated['gallery_id'],
            'status' => $validated['status'],
            'is_featured' => $request->boolean('is_featured'),
        ]);

        // Sync tags
        if (isset($validated['tags'])) {
            $photo->tags()->sync($validated['tags']);
        } else {
            $photo->tags()->detach();
        }

        // Update category photo count if category changed
        if ($photo->wasChanged('category_id')) {
            if ($photo->getOriginal('category_id')) {
                Category::find($photo->getOriginal('category_id'))?->updatePhotoCount();
            }
            if ($photo->category_id) {
                $photo->category->updatePhotoCount();
            }
        }

        LoggingService::modelUpdated($photo, $photo->getChanges());

        return redirect()
            ->route('admin.photos.index')
            ->with('success', 'Photo updated successfully.');
    }

    /**
     * Remove the specified photo.
     */
    public function destroy(Photo $photo)
    {
        $this->authorize('delete', $photo);

        $categoryId = $photo->category_id;
        $photoTitle = $photo->title;
        $photoId = $photo->id;

        // Delete associated files
        $this->photoService->deletePhotoFiles($photo);

        // Delete the photo record
        $photo->delete();

        // Update category count
        if ($categoryId) {
            Category::find($categoryId)?->updatePhotoCount();
        }

        LoggingService::activity('photo.deleted', "Deleted photo: {$photoTitle}", null, [
            'photo_id' => $photoId,
            'title' => $photoTitle,
        ]);

        return redirect()
            ->route('admin.photos.index')
            ->with('success', 'Photo deleted successfully.');
    }

    /**
     * Bulk action on photos.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,unpublish,delete',
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
        ]);

        $photos = Photo::whereIn('id', $request->photo_ids)
            ->where('user_id', auth()->id())
            ->get();

        $count = 0;

        foreach ($photos as $photo) {
            switch ($request->action) {
                case 'publish':
                    $photo->update(['status' => 'published']);
                    $count++;
                    break;
                case 'unpublish':
                    $photo->update(['status' => 'draft']);
                    $count++;
                    break;
                case 'delete':
                    $this->photoService->deletePhotoFiles($photo);
                    $photo->delete();
                    $count++;
                    break;
            }
        }

        $actionText = match ($request->action) {
            'publish' => 'published',
            'unpublish' => 'unpublished',
            'delete' => 'deleted',
        };

        LoggingService::activity('photo.bulk_action', "{$count} photo(s) {$actionText}", null, [
            'action' => $request->action,
            'count' => $count,
            'photo_ids' => $request->photo_ids,
        ]);

        return redirect()
            ->route('admin.photos.index')
            ->with('success', "{$count} photo(s) {$actionText} successfully.");
    }

    /**
     * Bulk update a single field on multiple photos.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'field' => 'required|in:category_id,gallery_id,location_name',
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
            'value' => 'nullable|string',
        ]);

        $photos = Photo::whereIn('id', $request->photo_ids)
            ->where('user_id', auth()->id())
            ->get();

        $count = 0;
        $field = $request->field;
        $value = $request->value ?: null;

        foreach ($photos as $photo) {
            $photo->update([$field => $value]);
            $count++;
        }

        $fieldNames = [
            'category_id' => 'category',
            'gallery_id' => 'gallery',
            'location_name' => 'location',
        ];

        return redirect()
            ->route('admin.photos.index')
            ->with('success', "Updated {$fieldNames[$field]} for {$count} photo(s).");
    }

    /**
     * Bulk update tags on multiple photos.
     */
    public function bulkTags(Request $request)
    {
        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'replace' => 'nullable|boolean',
        ]);

        $photos = Photo::whereIn('id', $request->photo_ids)
            ->where('user_id', auth()->id())
            ->get();

        $tagIds = $request->tag_ids ?? [];
        $replace = $request->boolean('replace');
        $count = 0;

        foreach ($photos as $photo) {
            if ($replace) {
                $photo->tags()->sync($tagIds);
            } else {
                $photo->tags()->syncWithoutDetaching($tagIds);
            }
            $count++;
        }

        $action = $replace ? 'Set' : 'Added';
        return redirect()
            ->route('admin.photos.index')
            ->with('success', "{$action} tags for {$count} photo(s).");
    }

    /**
     * Show bulk edit page.
     */
    public function bulkEdit(Request $request)
    {
        $query = Photo::with(['category', 'gallery', 'tags'])
            ->where('user_id', auth()->id())
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $photos = $query->paginate(50);
        $categories = Category::orderBy('name')->get();
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.photos.bulk-edit', compact('photos', 'categories', 'galleries', 'tags'));
    }

    /**
     * Re-optimize all photos with current settings.
     */
    public function reoptimize(Request $request)
    {
        // Set a longer execution time for this operation
        set_time_limit(600);

        try {
            $count = $this->photoService->reoptimizeAllPhotos();

            return response()->json([
                'success' => true,
                'message' => "{$count} photo(s) re-optimized successfully.",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            LoggingService::error('photo.reoptimize_failed', 'Failed to re-optimize photos', $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to re-optimize photos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suggest AI-generated content for the photo (slug, description, or story).
     */
    public function suggestSlug(Request $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $type = $request->get('type', 'slug');

        // Build context from photo data
        $context = [];
        if ($photo->title) {
            $context[] = "Title: {$photo->title}";
        }
        if ($photo->description && $type !== 'description') {
            $context[] = "Description: {$photo->description}";
        }
        if ($photo->location_name) {
            $context[] = "Location: {$photo->location_name}";
        }
        if ($photo->category) {
            $context[] = "Category: {$photo->category->name}";
        }
        if ($photo->tags->count() > 0) {
            $context[] = "Tags: " . $photo->tags->pluck('name')->join(', ');
        }
        if ($photo->exif_data) {
            $exif = $photo->formatted_exif;
            if ($exif['camera']) $context[] = "Camera: {$exif['camera']}";
            if ($exif['date_taken']) $context[] = "Date taken: {$exif['date_taken']}";
        }

        $aiService = app(\App\Services\AIImageService::class);

        // Handle different types
        switch ($type) {
            case 'description':
                return $this->generateDescription($aiService, $photo, $context);
            case 'story':
                return $this->generateStory($aiService, $photo, $context);
            default:
                return $this->generateSlug($aiService, $photo, $context);
        }
    }

    /**
     * Generate AI slug suggestion.
     */
    protected function generateSlug($aiService, Photo $photo, array $context)
    {
        if ($aiService->isConfigured()) {
            try {
                $prompt = "Based on this photo information, suggest a short, SEO-friendly URL slug (lowercase, hyphens only, no special characters, 2-5 words max). Just return the slug, nothing else.\n\n" . implode("\n", $context);

                $suggestion = $aiService->generateText($prompt);
                if ($suggestion) {
                    $slug = Str::slug(trim($suggestion));
                    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
                    $slug = substr($slug, 0, 100);

                    $baseSlug = $slug;
                    $counter = 1;
                    while (Photo::where('slug', $slug)->where('id', '!=', $photo->id)->exists()) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    return response()->json(['slug' => $slug]);
                }
            } catch (\Exception $e) {
                // Fall back to title-based slug
            }
        }

        // Fallback: generate from title + location
        $parts = [];
        if ($photo->title) {
            $parts[] = $photo->title;
        }
        if ($photo->location_name && !str_contains(strtolower($photo->title ?? ''), strtolower(explode(',', $photo->location_name)[0]))) {
            $parts[] = explode(',', $photo->location_name)[0];
        }

        $baseSlug = Str::slug(implode(' ', $parts));
        $slug = $baseSlug;
        $counter = 1;
        while (Photo::where('slug', $slug)->where('id', '!=', $photo->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Generate AI description suggestion.
     */
    protected function generateDescription($aiService, Photo $photo, array $context)
    {
        if ($aiService->isConfigured()) {
            try {
                $prompt = "Based on this photo information, write a brief, engaging description (1-2 sentences, under 200 characters) suitable for a photography portfolio. Be descriptive but concise. Just return the description text, nothing else.\n\n" . implode("\n", $context);

                $description = $aiService->generateText($prompt);
                if ($description) {
                    $description = trim($description);
                    $description = trim($description, '"\'');
                    return response()->json(['description' => $description]);
                }
            } catch (\Exception $e) {
                // Return empty
            }
        }

        return response()->json(['description' => '']);
    }

    /**
     * Generate AI story suggestion.
     */
    protected function generateStory($aiService, Photo $photo, array $context)
    {
        if ($aiService->isConfigured()) {
            try {
                $prompt = "Based on this photo information, write a thoughtful, engaging story or narrative (2-4 paragraphs) from the photographer's perspective. Include details about the moment, the atmosphere, what made it special, and any technical or creative choices. Write in first person. Make it personal and evocative.\n\n" . implode("\n", $context);

                $story = $aiService->generateText($prompt);
                if ($story) {
                    $story = trim($story);
                    return response()->json(['story' => $story]);
                }
            } catch (\Exception $e) {
                // Return empty
            }
        }

        return response()->json(['story' => '']);
    }

    /**
     * Save individual photo from bulk edit (AJAX).
     */
    public function quickUpdate(Request $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'story' => 'nullable|string',
            'location_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'gallery_id' => 'nullable|exists:galleries,id',
            'status' => 'sometimes|required|in:draft,published',
            'is_featured' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $photo->update($validated);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $photo->tags()->sync($validated['tags']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully',
            'photo' => $photo->fresh(['category', 'gallery', 'tags'])
        ]);
    }
}
