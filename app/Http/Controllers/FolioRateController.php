<?php

namespace App\Http\Controllers;

use App\Models\FolioRate;
use Illuminate\Http\Request;

class FolioRateController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'min_folios' => 'required|integer|min:0',
            'max_folios' => 'nullable|integer|gt:min_folios',
            'price' => 'required|numeric|min:0',
        ]);

        FolioRate::create($validated);
        return back()->with('flash.banner', 'Tarifa de folios creada.');
    }

    public function update(Request $request, FolioRate $folioRate)
    {
        $validated = $request->validate([
            'min_folios' => 'required|integer|min:0',
            'max_folios' => 'nullable|integer|gt:min_folios',
            'price' => 'required|numeric|min:0',
        ]);

        $folioRate->update($validated);
        return back()->with('flash.banner', 'Tarifa de folios actualizada.');
    }

    public function destroy(FolioRate $folioRate)
    {
        $folioRate->delete();
        return back()->with('flash.banner', 'Tarifa de folios eliminada.');
    }
}
