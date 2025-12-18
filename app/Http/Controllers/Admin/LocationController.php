<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::withCount('nearbyPhotos')->orderBy('name')->paginate(20);

        return view('admin.locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.locations.create');
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

    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
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
