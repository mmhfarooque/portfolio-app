<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(): Response
    {
        $locations = Location::withCount('nearbyPhotos')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'city' => $location->city,
                'state' => $location->state,
                'country' => $location->country,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'cover_image' => $location->cover_image,
                'is_featured' => $location->is_featured,
                'nearby_photos_count' => $location->nearby_photos_count,
            ]);

        return Inertia::render('Admin/Locations/Index', [
            'locations' => $locations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Locations/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tips' => 'nullable|string',
            'best_time' => 'nullable|string|max:255',
            'amenities' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
        ]);

        $data = $request->except('image', 'amenities');
        $data['slug'] = Str::slug($request->name);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['amenities'] = $request->amenities ? array_map('trim', explode(',', $request->amenities)) : null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('locations', 'public');
            $data['cover_image'] = $path;
        }

        Location::create($data);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location added successfully.');
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('Admin/Locations/Edit', [
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'address' => $location->address,
                'city' => $location->city,
                'state' => $location->state,
                'country' => $location->country,
                'tips' => $location->tips,
                'best_time' => $location->best_time,
                'amenities' => $location->amenities,
                'cover_image' => $location->cover_image,
                'is_featured' => $location->is_featured,
            ],
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tips' => 'nullable|string',
            'best_time' => 'nullable|string|max:255',
            'amenities' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
        ]);

        $data = $request->except('image', 'amenities');
        $data['slug'] = Str::slug($request->name);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['amenities'] = $request->amenities ? array_map('trim', explode(',', $request->amenities)) : null;

        if ($request->hasFile('image')) {
            if ($location->cover_image) {
                Storage::disk('public')->delete($location->cover_image);
            }
            $path = $request->file('image')->store('locations', 'public');
            $data['cover_image'] = $path;
        }

        $location->update($data);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        if ($location->cover_image) {
            Storage::disk('public')->delete($location->cover_image);
        }

        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location deleted.');
    }
}
