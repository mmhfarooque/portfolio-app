<?php

namespace App\Http\Controllers;

use App\Models\Equipment;

class EquipmentController extends Controller
{
    /**
     * Display the gear page.
     */
    public function index()
    {
        $cameras = Equipment::current()->ofType('camera')->orderBy('sort_order')->get();
        $lenses = Equipment::current()->ofType('lens')->orderBy('sort_order')->get();
        $accessories = Equipment::current()->ofType('accessory')->orderBy('sort_order')->get();
        $lighting = Equipment::current()->ofType('lighting')->orderBy('sort_order')->get();
        $software = Equipment::current()->ofType('software')->orderBy('sort_order')->get();

        $previousGear = Equipment::where('is_current', false)->orderBy('type')->orderBy('acquired_date', 'desc')->get();

        return view('pages.gear', compact('cameras', 'lenses', 'accessories', 'lighting', 'software', 'previousGear'));
    }

    /**
     * Display a specific piece of equipment.
     */
    public function show(Equipment $equipment)
    {
        return view('pages.gear-show', compact('equipment'));
    }
}
