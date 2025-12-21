<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Display search page with results.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'q', 'category', 'tag', 'camera', 'lens',
            'date_from', 'date_to', 'year',
            'has_location', 'orientation', 'featured',
            'sort', 'order', 'per_page'
        ]);

        $photos = $this->searchService->search($filters);
        $filterOptions = $this->searchService->getFilterOptions();

        return Inertia::render('Public/Search', [
            'photos' => $photos,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Get search suggestions (AJAX).
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->getSuggestions($query);

        return response()->json($suggestions);
    }
}
