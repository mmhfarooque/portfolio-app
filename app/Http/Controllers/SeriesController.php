<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    /**
     * Display a listing of series.
     */
    public function index(Request $request): Response
    {
        $series = Series::published()
            ->with(['user'])
            ->withCount('photos')
            ->orderBy('project_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->through(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'slug' => $s->slug,
                'description' => $s->description,
                'cover_image' => $s->cover_image,
                'project_date' => $s->project_date?->format('M Y'),
                'photos_count' => $s->photos_count,
                'user' => $s->user ? ['name' => $s->user->name] : null,
            ]);

        return Inertia::render('Public/Series/Index', [
            'series' => $series,
        ]);
    }

    /**
     * Display the specified series.
     */
    public function show(Series $series): Response
    {
        if ($series->status !== 'published') {
            abort(404);
        }

        $series->load(['user', 'photos' => function ($query) {
            $query->published()->orderByPivot('sort_order');
        }]);

        $series->incrementViews();

        // Get related series
        $relatedSeries = Series::published()
            ->where('id', '!=', $series->id)
            ->withCount('photos')
            ->orderBy('project_date', 'desc')
            ->take(3)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'slug' => $s->slug,
                'cover_image' => $s->cover_image,
                'photos_count' => $s->photos_count,
            ]);

        return Inertia::render('Public/Series/Show', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
                'slug' => $series->slug,
                'description' => $series->description,
                'story' => $series->story,
                'cover_image' => $series->cover_image,
                'project_date' => $series->project_date?->format('M Y'),
                'location' => $series->location,
                'views' => $series->views,
                'user' => $series->user ? ['name' => $series->user->name] : null,
            ],
            'photos' => $series->photos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
            ]),
            'relatedSeries' => $relatedSeries,
        ]);
    }
}
