<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeriesController extends Controller
{
    /**
     * Display a listing of series.
     */
    public function index(Request $request)
    {
        $query = Series::with('user')->withCount('photos');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $series = $query->latest()->paginate(15)->withQueryString();

        return view('admin.series.index', compact('series'));
    }

    /**
     * Show the form for creating a new series.
     */
    public function create()
    {
        return view('admin.series.create');
    }

    /**
     * Store a newly created series.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:series,slug',
            'description' => 'nullable|string',
            'story' => 'nullable|string',
            'project_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'cover_image' => 'nullable|image|max:5120',
            'seo_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')
                ->store('series/covers', 'public');
        }

        $validated['user_id'] = auth()->id();
        $validated['is_featured'] = $request->boolean('is_featured');

        $series = Series::create($validated);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Series created successfully.');
    }

    /**
     * Show the form for editing the specified series.
     */
    public function edit(Series $series)
    {
        $series->load(['photos' => function ($query) {
            $query->orderByPivot('sort_order');
        }]);

        $availablePhotos = Photo::published()
            ->whereDoesntHave('series', function ($query) use ($series) {
                $query->where('series.id', $series->id);
            })
            ->orderBy('captured_at', 'desc')
            ->take(50)
            ->get();

        return view('admin.series.edit', compact('series', 'availablePhotos'));
    }

    /**
     * Update the specified series.
     */
    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:series,slug,' . $series->id,
            'description' => 'nullable|string',
            'story' => 'nullable|string',
            'project_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'cover_image' => 'nullable|image|max:5120',
            'seo_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover if exists
            if ($series->cover_image) {
                \Storage::disk('public')->delete($series->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')
                ->store('series/covers', 'public');
        }

        $validated['is_featured'] = $request->boolean('is_featured');

        $series->update($validated);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Series updated successfully.');
    }

    /**
     * Remove the specified series.
     */
    public function destroy(Series $series)
    {
        // Delete cover image if exists
        if ($series->cover_image) {
            \Storage::disk('public')->delete($series->cover_image);
        }

        $series->delete();

        return redirect()->route('admin.series.index')
            ->with('success', 'Series deleted successfully.');
    }

    /**
     * Add photos to series.
     */
    public function addPhotos(Request $request, Series $series)
    {
        $validated = $request->validate([
            'photo_ids' => 'required|array',
            'photo_ids.*' => 'exists:photos,id',
        ]);

        $maxOrder = $series->photos()->max('photo_series.sort_order') ?? 0;

        foreach ($validated['photo_ids'] as $index => $photoId) {
            $series->photos()->syncWithoutDetaching([
                $photoId => ['sort_order' => $maxOrder + $index + 1],
            ]);
        }

        return back()->with('success', 'Photos added to series.');
    }

    /**
     * Remove photo from series.
     */
    public function removePhoto(Series $series, Photo $photo)
    {
        $series->photos()->detach($photo->id);

        return back()->with('success', 'Photo removed from series.');
    }

    /**
     * Update photo order in series.
     */
    public function updateOrder(Request $request, Series $series)
    {
        $validated = $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|exists:photos,id',
            'photos.*.order' => 'required|integer',
        ]);

        foreach ($validated['photos'] as $photoData) {
            $series->photos()->updateExistingPivot($photoData['id'], [
                'sort_order' => $photoData['order'],
            ]);
        }

        return response()->json(['success' => true]);
    }
}
