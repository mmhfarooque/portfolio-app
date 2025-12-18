<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    /**
     * Display a listing of series.
     */
    public function index(Request $request)
    {
        $series = Series::published()
            ->with(['user'])
            ->withCount('photos')
            ->orderBy('project_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('series.index', compact('series'));
    }

    /**
     * Display the specified series.
     */
    public function show(Series $series)
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
            ->get();

        return view('series.show', compact('series', 'relatedSeries'));
    }
}
