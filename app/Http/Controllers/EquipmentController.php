<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    /**
     * Display the gear page.
     */
    public function index(): Response
    {
        $mapEquipment = fn($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'slug' => $e->slug,
            'description' => $e->description,
            'image' => $e->image,
            'manufacturer' => $e->manufacturer,
            'model' => $e->model,
            'acquired_date' => $e->acquired_date?->format('Y'),
        ];

        $cameras = Equipment::current()->ofType('camera')->orderBy('sort_order')->get()->map($mapEquipment);
        $lenses = Equipment::current()->ofType('lens')->orderBy('sort_order')->get()->map($mapEquipment);
        $accessories = Equipment::current()->ofType('accessory')->orderBy('sort_order')->get()->map($mapEquipment);
        $lighting = Equipment::current()->ofType('lighting')->orderBy('sort_order')->get()->map($mapEquipment);
        $software = Equipment::current()->ofType('software')->orderBy('sort_order')->get()->map($mapEquipment);

        $previousGear = Equipment::where('is_current', false)
            ->orderBy('type')
            ->orderBy('acquired_date', 'desc')
            ->get()
            ->map($mapEquipment);

        return Inertia::render('Public/Gear/Index', [
            'cameras' => $cameras,
            'lenses' => $lenses,
            'accessories' => $accessories,
            'lighting' => $lighting,
            'software' => $software,
            'previousGear' => $previousGear,
        ]);
    }

    /**
     * Display a specific piece of equipment.
     */
    public function show(Equipment $equipment): Response
    {
        return Inertia::render('Public/Gear/Show', [
            'equipment' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'slug' => $equipment->slug,
                'type' => $equipment->type,
                'description' => $equipment->description,
                'story' => $equipment->story,
                'image' => $equipment->image,
                'manufacturer' => $equipment->manufacturer,
                'model' => $equipment->model,
                'specs' => $equipment->specs,
                'acquired_date' => $equipment->acquired_date?->format('M Y'),
                'is_current' => $equipment->is_current,
                'affiliate_link' => $equipment->affiliate_link,
            ],
        ]);
    }
}
