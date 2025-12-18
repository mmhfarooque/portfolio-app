<?php

namespace App\Http\Controllers;

use App\Models\Location;

class LocationController extends Controller
{
    /**
     * Display all locations.
     */
    public function index()
    {
        $locations = Location::published()
            ->withCount(['photos' => function ($query) {
                // This won't work directly, but we'll handle it in the view
            }])
            ->orderBy('name')
            ->get();

        $featuredLocations = Location::published()->featured()->take(3)->get();

        return view('locations.index', compact('locations', 'featuredLocations'));
    }

    /**
     * Display a specific location.
     */
    public function show(Location $location)
    {
        if ($location->status !== 'published') {
            abort(404);
        }

        $location->incrementViews();

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
            ->get();

        return view('locations.show', compact('location', 'nearbyLocations'));
    }
}
