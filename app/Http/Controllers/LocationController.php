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
        // NOTE: Location::$photos is a computed accessor (Haversine radius query),
        // NOT an Eloquent relationship, so withCount('photos') throws
        // "Call to undefined method App\Models\Location::photos()". Count via the
        // photo_count accessor instead. One radius query per location; the table is
        // small, and a real relationship would need a location_id or pivot that the
        // schema does not have.
        $locations = Location::published()
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
                'photos_count' => $loc->photo_count,
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
        // Same reason as index(): photos is an accessor, so it cannot be eager loaded.
        // The accessor already returns published photos ordered by distance; cap it here.
        $locationPhotos = $location->photos->take(12);

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
            'photos' => $locationPhotos->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'thumbnail_path' => $p->thumbnail_path,
            ]),
            'nearbyLocations' => $nearbyLocations,
        ]);
    }
}
