<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = Equipment::orderBy('type')->orderBy('sort_order')->paginate(20);

        return view('admin.equipment.index', compact('equipment'));
    }

    public function create()
    {
        return view('admin.equipment.create');
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

    public function edit(Equipment $equipment)
    {
        return view('admin.equipment.edit', compact('equipment'));
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
