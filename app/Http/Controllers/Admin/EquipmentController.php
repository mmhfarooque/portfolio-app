<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function index(): Response
    {
        $equipment = Equipment::orderBy('type')
            ->orderBy('sort_order')
            ->paginate(20)
            ->through(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'type' => $item->type,
                'brand' => $item->brand,
                'model' => $item->model,
                'image_path' => $item->image_path,
                'is_active' => $item->is_active,
            ]);

        return Inertia::render('Admin/Equipment/Index', [
            'equipment' => $equipment,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Equipment/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:camera,lens,accessory,lighting,software',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'specifications' => 'nullable|string',
            'affiliate_link' => 'nullable|url|max:500',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->except('image');
        $data['slug'] = Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('equipment', 'public');
            $data['image_path'] = $path;
        }

        Equipment::create($data);

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    public function edit(Equipment $equipment): Response
    {
        return Inertia::render('Admin/Equipment/Edit', [
            'equipment' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'slug' => $equipment->slug,
                'type' => $equipment->type,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'description' => $equipment->description,
                'specifications' => $equipment->specifications,
                'affiliate_link' => $equipment->affiliate_link,
                'image_path' => $equipment->image_path,
                'is_active' => $equipment->is_active,
            ],
        ]);
    }

    public function update(Request $request, Equipment $equipment)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:camera,lens,accessory,lighting,software',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'specifications' => 'nullable|string',
            'affiliate_link' => 'nullable|url|max:500',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->except('image');
        $data['slug'] = Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($equipment->image_path) {
                Storage::disk('public')->delete($equipment->image_path);
            }
            $path = $request->file('image')->store('equipment', 'public');
            $data['image_path'] = $path;
        }

        $equipment->update($data);

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->image_path) {
            Storage::disk('public')->delete($equipment->image_path);
        }

        $equipment->delete();

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment deleted.');
    }
}
