<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class GalleryController extends Controller
{
    /**
     * Display a listing of galleries.
     */
    public function index()
    {
        $galleries = Gallery::where('user_id', auth()->id())
            ->withCount('photos')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.galleries.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new gallery.
     */
    public function create()
    {
        return view('admin.galleries.create');
    }

    /**
     * Store a newly created gallery.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:51200', // 50MB - will be resized
            'cover_image_from_media' => 'nullable|string',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
        $validated['user_id'] = auth()->id();
        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        // Handle cover image - either from media library or file upload
        if ($request->filled('cover_image_from_media')) {
            $validated['cover_image'] = $request->input('cover_image_from_media');
        } elseif ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->processAndStoreCoverImage($request->file('cover_image'), 'galleries');
        }
        unset($validated['cover_image_from_media']);

        Gallery::create($validated);

        return redirect()
            ->route('admin.galleries.index')
            ->with('success', 'Gallery created successfully.');
    }

    /**
     * Display the specified gallery with its photos.
     */
    public function show(Gallery $gallery)
    {
        $this->authorize('view', $gallery);

        $photos = $gallery->photos()->latest()->paginate(24);

        return view('admin.galleries.show', compact('gallery', 'photos'));
    }

    /**
     * Show the form for editing the specified gallery.
     */
    public function edit(Gallery $gallery)
    {
        $this->authorize('update', $gallery);

        return view('admin.galleries.edit', compact('gallery'));
    }

    /**
     * Update the specified gallery.
     */
    public function update(Request $request, Gallery $gallery)
    {
        $this->authorize('update', $gallery);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:51200', // 50MB - will be resized
            'cover_image_from_media' => 'nullable|string',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['is_featured'] = $request->boolean('is_featured');

        // Handle cover image - either from media library or file upload
        if ($request->filled('cover_image_from_media')) {
            // Delete old cover image if it was a custom upload (not from photos)
            if ($gallery->cover_image && !str_starts_with($gallery->cover_image, 'photos/')) {
                Storage::disk('public')->delete($gallery->cover_image);
            }
            $validated['cover_image'] = $request->input('cover_image_from_media');
        } elseif ($request->hasFile('cover_image')) {
            // Delete old cover image if it was a custom upload
            if ($gallery->cover_image && !str_starts_with($gallery->cover_image, 'photos/')) {
                Storage::disk('public')->delete($gallery->cover_image);
            }
            $validated['cover_image'] = $this->processAndStoreCoverImage($request->file('cover_image'), 'galleries');
        }
        unset($validated['cover_image_from_media']);

        $gallery->update($validated);

        return redirect()
            ->route('admin.galleries.index')
            ->with('success', 'Gallery updated successfully.');
    }

    /**
     * Remove the specified gallery.
     */
    public function destroy(Gallery $gallery)
    {
        $this->authorize('delete', $gallery);

        // Delete cover image
        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }

        // Unassign photos from gallery (don't delete them)
        $gallery->photos()->update(['gallery_id' => null]);

        $gallery->delete();

        return redirect()
            ->route('admin.galleries.index')
            ->with('success', 'Gallery deleted successfully.');
    }

    /**
     * Add photos to a gallery.
     */
    public function addPhotos(Request $request, Gallery $gallery)
    {
        $this->authorize('update', $gallery);

        $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
        ]);

        Photo::whereIn('id', $request->photo_ids)
            ->where('user_id', auth()->id())
            ->update(['gallery_id' => $gallery->id]);

        return redirect()
            ->back()
            ->with('success', 'Photos added to gallery successfully.');
    }

    /**
     * Remove a photo from a gallery.
     */
    public function removePhoto(Gallery $gallery, Photo $photo)
    {
        $this->authorize('update', $gallery);

        if ($photo->gallery_id === $gallery->id) {
            $photo->update(['gallery_id' => null]);
        }

        return redirect()
            ->back()
            ->with('success', 'Photo removed from gallery.');
    }

    /**
     * Process and store a cover image with automatic resizing.
     */
    protected function processAndStoreCoverImage($file, string $directory): string
    {
        $image = Image::read($file->getRealPath());

        // Resize to max 1200px width while maintaining aspect ratio
        if ($image->width() > 1200) {
            $image->scale(width: 1200);
        }

        // Generate unique filename
        $filename = Str::uuid() . '.jpg';
        $path = $directory . '/' . $filename;

        // Ensure directory exists
        $storagePath = storage_path('app/public/' . $directory);
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Save as optimized JPEG
        $fullPath = storage_path('app/public/' . $path);
        $image->toJpeg(85)->save($fullPath);

        return $path;
    }
}
