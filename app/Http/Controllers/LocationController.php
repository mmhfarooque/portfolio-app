<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Display all locations.
     */
    public function index(): Response
    {
        $locations = Location::published()
            ->withCount('photos')
            ->orderBy('name')
            ->get()
            ->map(fn($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'slug' => $loc->slug,
                'description' => $loc->description,
                'cover_image' => $loc->cover_image,
                'latitude' => $loc->latitude,
                'longitude' => $loc->longitude,
                'photos_count' => $loc->photos_count,
            ]);

        $featuredLocations = Location::published()
            ->featured()
            ->take(3)
            ->get()
            ->map(fn($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'slug' => $loc->slug,
                'cover_image' => $loc->cover_image,
            ]);

        return Inertia::render('Public/Locations/Index', [
            'locations' => $locations,
            'featuredLocations' => $featuredLocations,
        ]);
    }

    /**
     * Display a specific location.
     */
    public function show(Location $location): Response
    {
        if ($location->status !== 'published') {
            abort(404);
        }

        $location->incrementViews();
        $location->load(['photos' => fn($q) => $q->published()->take(12)]);

        $nearbyLocations = Location::published()
            ->where('id', '!=', $location->id)
            ->when($location->hasCoordinates(), function ($query) use ($location) {
                $query->withCoordinates()
                    ->selectRaw("*, (
                        6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) *
                            cos(radians(longitude) - radians(?)) +
                            sin(radians(?)) * sin(radians(latitude))
                        )
                    ) AS distance", [$location->latitude, $location->longitude, $location->latitude])
                    ->having('distance', '<=', 100)
                    ->orderBy('distance');
            })
            ->take(3)
            ->get()
            ->map(fn($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'slug' => $loc->slug,
                'cover_image' => $loc->cover_image,
            ]);

        return Inertia::render('Public/Locations/Show', [
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
                'story' => $location->story,
                'cover_image' => $location->cover_image,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'country' => $location->country,
                'region' => $location->region,
                'views' => $location->views,
            ],
            'photos' => $location->photos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
            ]),
            'nearbyLocations' => $nearbyLocations,
        ]);
    }
}
