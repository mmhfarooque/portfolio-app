<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPhotoUpload;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Photo;
use App\Models\Tag;
use App\Services\PhotoProcessingService;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PhotoController extends Controller
{
    public function __construct(
        protected PhotoProcessingService $photoService
    ) {}

    /**
     * Display a listing of photos.
     */
    public function index(Request $request): Response
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

        $photos = $query->paginate(24)->through(fn($photo) => [
            'id' => $photo->id,
            'title' => $photo->title,
            'slug' => $photo->slug,
            'thumbnail_path' => $photo->thumbnail_path,
            'status' => $photo->status,
            'processing_stage' => $photo->processing_stage,
            'views' => $photo->views,
            'is_featured' => $photo->is_featured,
            'created_at' => $photo->created_at->format('M j, Y'),
            'category' => $photo->category ? [
                'id' => $photo->category->id,
                'name' => $photo->category->name,
            ] : null,
            'gallery' => $photo->gallery ? [
                'id' => $photo->gallery->id,
                'name' => $photo->gallery->name,
            ] : null,
            'tags' => $photo->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
        ]);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Photos/Index', [
            'photos' => $photos,
            'categories' => $categories,
            'galleries' => $galleries,
            'tags' => $tags,
            'filters' => [
                'status' => $request->status,
                'category' => $request->category,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new photo.
     */
    public function create(): Response
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Photos/Create', [
            'categories' => $categories,
            'galleries' => $galleries,
            'tags' => $tags,
        ]);
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

        $files = $request->file('photos');
        $uploadedCount = 0;
        $queuedCount = 0;
        $skippedDuplicates = [];
        $queuedPhotoIds = [];

        // Check for duplicates
        $duplicateCheck = $this->photoService->checkFilesForDuplicates($files);
        $skippedDuplicates = $duplicateCheck['duplicates'];
        $filesToUpload = $duplicateCheck['valid'];

        // Determine processing mode (async by default when queue is configured)
        $useQueue = config('queue.default') !== 'sync';

        // Process valid (non-duplicate) files
        foreach ($filesToUpload as $file) {
            try {
                if ($useQueue) {
                    // Quick upload: create record immediately, process in background
                    $result = $this->photoService->quickUpload(
                        $file,
                        auth()->id(),
                        $request->category_id
                    );

                    // Dispatch job for background processing
                    ProcessPhotoUpload::dispatch(
                        $result['photo'],
                        $result['temp_path'],
                        $result['original_filename']
                    );

                    $queuedCount++;
                    $queuedPhotoIds[] = $result['photo']->id;
                } else {
                    // Synchronous processing (when queue is set to 'sync')
                    $this->photoService->processUpload(
                        $file,
                        auth()->id(),
                        $request->category_id
                    );
                    $uploadedCount++;
                }
            } catch (\Exception $e) {
                \Log::error('Photo upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Prepare response message
        if ($useQueue && $queuedCount > 0) {
            $message = "{$queuedCount} photo(s) queued for processing.";
        } else {
            $message = "{$uploadedCount} photo(s) uploaded successfully.";
        }

        $notification = null;

        if (count($skippedDuplicates) > 0) {
            $skippedNames = array_map(fn($d) => $d['filename'] . ' (matches: ' . $d['existing_photo']['title'] . ')', $skippedDuplicates);
            $message .= " " . count($skippedDuplicates) . " duplicate(s) skipped.";

            $notification = [
                'type' => 'warning',
                'title' => count($skippedDuplicates) . ' Duplicate(s) Skipped',
                'message' => 'The following files were not uploaded because they appear to already exist in your library:',
                'details' => $skippedNames,
                'duration' => 10000, // 10 seconds for longer notice
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'uploaded' => $uploadedCount,
                'queued' => $queuedCount,
                'queued_photo_ids' => $queuedPhotoIds,
                'skipped' => count($skippedDuplicates),
                'skipped_files' => $skippedDuplicates,
                'message' => $message,
                'processing_mode' => $useQueue ? 'async' : 'sync',
            ]);
        }

        $redirect = redirect()->route('admin.photos.index')->with('success', $message);

        if ($notification) {
            $redirect->with('notification', $notification);
        }

        // Add info about processing if using queue
        if ($useQueue && $queuedCount > 0) {
            $redirect->with('info', 'Photos are being processed in the background. Refresh the page to see updated status.');
        }

        return $redirect;
    }

    /**
     * Display the specified photo.
     */
    public function show(Request $request, Photo $photo): Response|\Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $photo);

        $photo->load(['category', 'gallery', 'tags']);

        // Return JSON for AJAX status checks
        if ($request->wantsJson()) {
            return response()->json([
                'photo' => [
                    'id' => $photo->id,
                    'status' => $photo->status,
                    'processing_stage' => $photo->processing_stage,
                    'processing_error' => $photo->processing_error,
                    'display_path' => $photo->display_path,
                    'thumbnail_path' => $photo->thumbnail_path,
                ]
            ]);
        }

        return Inertia::render('Admin/Photos/Show', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'description' => $photo->description,
                'story' => $photo->story,
                'location_name' => $photo->location_name,
                'seo_title' => $photo->seo_title,
                'meta_description' => $photo->meta_description,
                'status' => $photo->status,
                'is_featured' => $photo->is_featured,
                'views' => $photo->views,
                'display_path' => $photo->display_path,
                'thumbnail_path' => $photo->thumbnail_path,
                'watermarked_path' => $photo->watermarked_path,
                'original_filename' => $photo->original_filename,
                'file_size' => $photo->file_size,
                'width' => $photo->width,
                'height' => $photo->height,
                'exif_data' => $photo->exif_data,
                'formatted_exif' => $photo->formatted_exif,
                'processing_stage' => $photo->processing_stage,
                'processing_error' => $photo->processing_error,
                'created_at' => $photo->created_at->format('M j, Y g:i A'),
                'updated_at' => $photo->updated_at->format('M j, Y g:i A'),
                'category' => $photo->category ? [
                    'id' => $photo->category->id,
                    'name' => $photo->category->name,
                ] : null,
                'gallery' => $photo->gallery ? [
                    'id' => $photo->gallery->id,
                    'name' => $photo->gallery->name,
                ] : null,
                'tags' => $photo->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified photo.
     */
    public function edit(Photo $photo): Response
    {
        $this->authorize('update', $photo);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Photos/Edit', [
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'slug' => $photo->slug,
                'description' => $photo->description,
                'story' => $photo->story,
                'location_name' => $photo->location_name,
                'seo_title' => $photo->seo_title,
                'meta_description' => $photo->meta_description,
                'category_id' => $photo->category_id,
                'gallery_id' => $photo->gallery_id,
                'status' => $photo->status,
                'is_featured' => $photo->is_featured,
                'views' => $photo->views,
                'display_path' => $photo->display_path,
                'thumbnail_path' => $photo->thumbnail_path,
                'watermarked_path' => $photo->watermarked_path,
                'original_filename' => $photo->original_filename,
                'file_size' => $photo->file_size,
                'width' => $photo->width,
                'height' => $photo->height,
                'original_width' => $photo->original_width,
                'original_height' => $photo->original_height,
                'custom_max_resolution' => $photo->custom_max_resolution,
                'custom_quality' => $photo->custom_quality,
                'has_original' => $photo->hasOriginal(),
                'is_cloud_original' => $photo->isOriginalInCloud(),
                'exif_data' => $photo->exif_data,
                'formatted_exif' => $photo->formatted_exif,
                'processing_stage' => $photo->processing_stage,
                'processing_error' => $photo->processing_error,
                'created_at' => $photo->created_at->format('M j, Y g:i A'),
                'tag_ids' => $photo->tags->pluck('id'),
            ],
            'categories' => $categories,
            'galleries' => $galleries,
            'tags' => $tags,
            'globalSettings' => [
                'max_resolution' => (int) \App\Models\Setting::get('image_max_resolution', 1920),
                'quality' => (int) \App\Models\Setting::get('image_quality', 82),
            ],
        ]);
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
            'is_featured' => 'sometimes|boolean',
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
    public function bulkEdit(Request $request): Response
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

        $photos = $query->paginate(50)->through(fn($photo) => [
            'id' => $photo->id,
            'title' => $photo->title,
            'slug' => $photo->slug,
            'description' => $photo->description,
            'location_name' => $photo->location_name,
            'thumbnail_path' => $photo->thumbnail_path,
            'status' => $photo->status,
            'is_featured' => $photo->is_featured,
            'category_id' => $photo->category_id,
            'gallery_id' => $photo->gallery_id,
            'category' => $photo->category ? [
                'id' => $photo->category->id,
                'name' => $photo->category->name,
            ] : null,
            'gallery' => $photo->gallery ? [
                'id' => $photo->gallery->id,
                'name' => $photo->gallery->name,
            ] : null,
            'tags' => $photo->tags->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'tag_ids' => $photo->tags->pluck('id'),
        ]);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $galleries = Gallery::where('user_id', auth()->id())->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Photos/BulkEdit', [
            'photos' => $photos,
            'categories' => $categories,
            'galleries' => $galleries,
            'tags' => $tags,
            'filters' => [
                'status' => $request->status,
                'category' => $request->category,
            ],
        ]);
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
     * Re-optimize a single photo with custom settings.
     */
    public function reoptimizeSingle(Request $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $validated = $request->validate([
            'custom_max_resolution' => 'nullable|integer|in:800,1024,1280,1440,1600,1920,2048,2560,3840',
            'custom_quality' => 'nullable|integer|min:60|max:95',
        ]);

        try {
            $success = $this->photoService->reoptimizePhoto(
                $photo,
                $validated['custom_max_resolution'] ?? null,
                $validated['custom_quality'] ?? null
            );

            if ($success) {
                $photo->refresh();

                // Get file size in human-readable format
                $filePath = storage_path('app/public/' . $photo->watermarked_path);
                $fileSize = file_exists($filePath) ? $this->formatBytes(filesize($filePath)) : 'Unknown';

                return response()->json([
                    'success' => true,
                    'message' => 'Photo re-optimized successfully.',
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'file_size' => $fileSize,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to re-optimize photo. Source file may be missing.'
                ], 422);
            }
        } catch (\Exception $e) {
            LoggingService::error('photo.reoptimize_single_failed', 'Failed to re-optimize photo', $e, $photo);
            return response()->json([
                'success' => false,
                'message' => 'Failed to re-optimize photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
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
            case 'meta_description':
                return $this->generateMetaDescription($aiService, $photo, $context);
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
     * Generate AI meta description suggestion.
     */
    protected function generateMetaDescription($aiService, Photo $photo, array $context)
    {
        if ($aiService->isConfigured()) {
            try {
                $prompt = "Based on this photo information, write an SEO-optimized meta description (max 155 characters) for search engine results. Be concise, compelling, and include relevant keywords. Just return the meta description text, nothing else.\n\n" . implode("\n", $context);

                $metaDescription = $aiService->generateText($prompt);
                if ($metaDescription) {
                    $metaDescription = trim($metaDescription);
                    $metaDescription = trim($metaDescription, '"\'');
                    // Ensure it's under 160 chars
                    if (strlen($metaDescription) > 160) {
                        $metaDescription = substr($metaDescription, 0, 157) . '...';
                    }
                    return response()->json(['meta_description' => $metaDescription]);
                }
            } catch (\Exception $e) {
                // Return empty
            }
        }

        return response()->json(['meta_description' => '']);
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

    /**
     * Get processing status for photos (AJAX).
     */
    public function processingStatus(Request $request)
    {
        $photoIds = $request->get('photo_ids', []);

        if (empty($photoIds)) {
            // Return all processing photos for current user
            $photos = Photo::where('user_id', auth()->id())
                ->whereIn('status', ['processing', 'failed'])
                ->select(['id', 'title', 'status', 'processing_stage', 'processing_error', 'thumbnail_path'])
                ->get();
        } else {
            // Return status for specific photos
            $photos = Photo::where('user_id', auth()->id())
                ->whereIn('id', $photoIds)
                ->select(['id', 'title', 'status', 'processing_stage', 'processing_error', 'thumbnail_path'])
                ->get();
        }

        return response()->json([
            'photos' => $photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'title' => $photo->title,
                    'status' => $photo->status,
                    'processing_stage' => $photo->processing_stage,
                    'processing_stage_text' => $photo->processing_stage_text,
                    'processing_error' => $photo->processing_error,
                    'thumbnail_url' => $photo->thumbnail_path ? asset('storage/' . $photo->thumbnail_path) : null,
                    'is_complete' => !in_array($photo->status, ['processing', 'failed']),
                    'has_failed' => $photo->status === 'failed',
                ];
            }),
            'processing_count' => $photos->where('status', 'processing')->count(),
            'failed_count' => $photos->where('status', 'failed')->count(),
        ]);
    }

    /**
     * Retry processing a failed photo.
     */
    public function retryProcessing(Photo $photo)
    {
        $this->authorize('update', $photo);

        if ($photo->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Photo is not in failed state',
            ], 400);
        }

        // Check if we have a temp file to retry with
        // If not, we need to inform the user to re-upload
        if (!$photo->display_path && !$photo->original_path) {
            return response()->json([
                'success' => false,
                'message' => 'No image file available. Please re-upload the photo.',
            ], 400);
        }

        // Reset status and try re-processing from display image if available
        $photo->update([
            'status' => 'processing',
            'processing_stage' => 'retrying',
            'processing_error' => null,
        ]);

        // If we have a display path, we can try to regenerate other versions
        if ($photo->display_path) {
            try {
                $displayPath = storage_path('app/public/' . $photo->display_path);

                if (file_exists($displayPath)) {
                    // Create temp file for re-processing
                    $tempPath = storage_path('app/private/temp/' . Str::uuid() . '.tmp');
                    if (!is_dir(dirname($tempPath))) {
                        mkdir(dirname($tempPath), 0755, true);
                    }
                    copy($displayPath, $tempPath);

                    // Dispatch job for background processing
                    ProcessPhotoUpload::dispatch(
                        $photo,
                        $tempPath,
                        $photo->original_filename ?? 'retry.jpg'
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Photo re-queued for processing',
                    ]);
                }
            } catch (\Exception $e) {
                $photo->update([
                    'status' => 'failed',
                    'processing_stage' => 'error',
                    'processing_error' => 'Retry failed: ' . $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retry: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No source image available for re-processing. Please re-upload the photo.',
        ], 400);
    }

    /**
     * Replace the image for an existing photo.
     */
    public function replaceImage(Request $request, Photo $photo)
    {
        $this->authorize('update', $photo);

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,avif|max:51200', // 50MB max
        ]);

        $file = $request->file('image');

        try {
            // Delete old image files
            $this->photoService->deletePhotoFiles($photo);

            // Determine processing mode
            $useQueue = config('queue.default') !== 'sync';

            if ($useQueue) {
                // Store temp file and queue for processing
                $tempPath = $file->store('temp', 'local');
                $tempFullPath = storage_path('app/private/' . $tempPath);

                // Reset photo status
                $photo->update([
                    'status' => 'processing',
                    'processing_stage' => 'queued',
                    'processing_error' => null,
                    'display_path' => null,
                    'thumbnail_path' => null,
                    'watermarked_path' => null,
                ]);

                // Dispatch job
                ProcessPhotoUpload::dispatch(
                    $photo,
                    $tempFullPath,
                    $file->getClientOriginalName()
                );

                return redirect()
                    ->route('admin.photos.edit', $photo)
                    ->with('success', 'Image queued for processing. Refresh to see the updated photo.');
            } else {
                // Synchronous processing
                $this->photoService->reprocessPhoto($photo, $file);

                return redirect()
                    ->route('admin.photos.edit', $photo)
                    ->with('success', 'Image replaced successfully.');
            }
        } catch (\Exception $e) {
            LoggingService::error('photo.replace_failed', 'Failed to replace photo image: ' . $e->getMessage(), $photo);

            return redirect()
                ->route('admin.photos.edit', $photo)
                ->with('error', 'Failed to replace image: ' . $e->getMessage());
        }
    }

    /**
     * Validate slug for uniqueness and similarity.
     */
    public function validateSlug(Request $request)
    {
        $slug = $request->input('slug');
        $excludeId = $request->input('exclude_id');

        if (empty($slug)) {
            return response()->json(['valid' => true]);
        }

        // Check for exact match
        $exactMatch = Photo::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($exactMatch) {
            // Suggest an alternative
            $counter = 2;
            $baseSlug = $slug;
            while (Photo::where('slug', $baseSlug . '-' . $counter)->exists()) {
                $counter++;
            }

            return response()->json([
                'valid' => false,
                'error' => 'This URL slug is already taken',
                'existing' => [
                    'title' => $exactMatch->title,
                    'slug' => $exactMatch->slug,
                ],
                'suggestion' => $baseSlug . '-' . $counter,
            ]);
        }

        // Check for similar slugs (using LIKE for partial matches)
        $similarSlugs = Photo::where(function ($q) use ($slug) {
                $q->where('slug', 'like', '%' . $slug . '%')
                  ->orWhere('slug', 'like', $slug . '%')
                  ->orWhere('slug', 'like', '%' . $slug);
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->limit(5)
            ->get(['id', 'title', 'slug']);

        // Calculate similarity and filter (exclude 100% matches - that's the same item)
        $similar = $similarSlugs->filter(function ($photo) use ($slug) {
            similar_text($slug, $photo->slug, $percent);
            return $percent > 60 && $percent < 100; // Similar but not exact same
        })->map(function ($photo) use ($slug) {
            similar_text($slug, $photo->slug, $percent);
            return [
                'title' => $photo->title,
                'slug' => $photo->slug,
                'similarity' => round($percent),
            ];
        })->sortByDesc('similarity')->values();

        return response()->json([
            'valid' => true,
            'similar' => $similar->take(3),
        ]);
    }

    /**
     * Validate title for duplicates.
     */
    public function validateTitle(Request $request)
    {
        $title = $request->input('title');
        $excludeId = $request->input('exclude_id');

        if (empty($title)) {
            return response()->json(['valid' => true]);
        }

        // Check for exact match
        $exactMatch = Photo::where('title', $title)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($exactMatch) {
            return response()->json([
                'valid' => false,
                'error' => 'A photo with this exact title already exists',
                'existing' => [
                    'title' => $exactMatch->title,
                    'slug' => $exactMatch->slug,
                ],
            ]);
        }

        // Check for similar titles
        $words = explode(' ', strtolower($title));
        $similarTitles = Photo::where(function ($q) use ($words) {
                foreach ($words as $word) {
                    if (strlen($word) > 3) {
                        $q->orWhere('title', 'like', '%' . $word . '%');
                    }
                }
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->limit(10)
            ->get(['id', 'title', 'slug']);

        // Calculate similarity (exclude 100% matches - that's the same item)
        $similar = $similarTitles->map(function ($photo) use ($title) {
            similar_text(strtolower($title), strtolower($photo->title), $percent);
            return [
                'title' => $photo->title,
                'slug' => $photo->slug,
                'similarity' => round($percent),
            ];
        })->filter(fn($item) => $item['similarity'] > 50 && $item['similarity'] < 100)
          ->sortByDesc('similarity')
          ->values()
          ->take(3);

        return response()->json([
            'valid' => true,
            'similar' => $similar,
        ]);
    }
}
