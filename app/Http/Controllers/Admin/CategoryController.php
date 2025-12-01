<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::withCount('photos')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:51200', // 50MB - will be resized
            'cover_image_from_media' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Handle cover image - either from media library or file upload
        if ($request->filled('cover_image_from_media')) {
            $validated['cover_image'] = $request->input('cover_image_from_media');
        } elseif ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->processAndStoreCoverImage($request->file('cover_image'), 'categories');
        }
        unset($validated['cover_image_from_media']);

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:51200', // 50MB - will be resized
            'cover_image_from_media' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        // Update slug if name changed
        if ($category->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle cover image - either from media library or file upload
        if ($request->filled('cover_image_from_media')) {
            // Delete old cover image if it was a custom upload (not from photos)
            if ($category->cover_image && !str_starts_with($category->cover_image, 'photos/')) {
                Storage::disk('public')->delete($category->cover_image);
            }
            $validated['cover_image'] = $request->input('cover_image_from_media');
        } elseif ($request->hasFile('cover_image')) {
            // Delete old cover image if it was a custom upload
            if ($category->cover_image && !str_starts_with($category->cover_image, 'photos/')) {
                Storage::disk('public')->delete($category->cover_image);
            }
            $validated['cover_image'] = $this->processAndStoreCoverImage($request->file('cover_image'), 'categories');
        }
        unset($validated['cover_image_from_media']);

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // Delete cover image
        if ($category->cover_image) {
            Storage::disk('public')->delete($category->cover_image);
        }

        // Photos in this category will have category_id set to null (on delete set null)
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Update sort order for categories.
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:categories,id',
        ]);

        foreach ($request->order as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
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
