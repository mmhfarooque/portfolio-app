<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Photo;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search photos with filters.
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Photo::published()
            ->with(['category', 'tags', 'gallery']);

        // Text search
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('story', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        // Tag filter
        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag']);
            });
        }

        // Camera filter (from EXIF)
        if (!empty($filters['camera'])) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Make')) = ?", [$filters['camera']]);
        }

        // Lens filter
        if (!empty($filters['lens'])) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.LensModel')) LIKE ?", ["%{$filters['lens']}%"]);
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $query->where('captured_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('captured_at', '<=', $filters['date_to']);
        }

        // Year filter
        if (!empty($filters['year'])) {
            $query->whereYear('captured_at', $filters['year']);
        }

        // Location filter (has GPS)
        if (!empty($filters['has_location'])) {
            $query->withLocation();
        }

        // Orientation filter
        if (!empty($filters['orientation'])) {
            if ($filters['orientation'] === 'landscape') {
                $query->whereRaw('width > height');
            } elseif ($filters['orientation'] === 'portrait') {
                $query->whereRaw('height > width');
            } elseif ($filters['orientation'] === 'square') {
                $query->whereRaw('width = height');
            }
        }

        // Featured only
        if (!empty($filters['featured'])) {
            $query->featured();
        }

        // Sort
        $sortField = $filters['sort'] ?? 'captured_at';
        $sortDir = $filters['order'] ?? 'desc';

        $allowedSorts = ['captured_at', 'created_at', 'title', 'views'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($filters['per_page'] ?? 24)->withQueryString();
    }

    /**
     * Get available filter options.
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('search.filter_options', 3600, function () {
            return [
                'categories' => Category::orderBy('name')->pluck('name', 'id')->toArray(),
                'tags' => Tag::orderBy('name')->pluck('name', 'id')->toArray(),
                'cameras' => $this->getUniqueCameras(),
                'years' => $this->getUniqueYears(),
            ];
        });
    }

    /**
     * Get unique cameras from EXIF data.
     */
    protected function getUniqueCameras(): array
    {
        return Photo::published()
            ->whereNotNull('exif_data')
            ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Make')) as camera")
            ->whereRaw("JSON_EXTRACT(exif_data, '$.Make') IS NOT NULL")
            ->pluck('camera')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get unique years from photos.
     */
    protected function getUniqueYears(): array
    {
        return Photo::published()
            ->whereNotNull('captured_at')
            ->selectRaw('DISTINCT YEAR(captured_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Get search suggestions based on partial query.
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        $suggestions = [];

        // Photo titles
        $photos = Photo::published()
            ->where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('title')
            ->toArray();

        // Tags
        $tags = Tag::where('name', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        // Categories
        $categories = Category::where('name', 'like', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        return array_unique(array_merge($photos, $tags, $categories));
    }
}
