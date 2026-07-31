<?php

namespace App\Http\Controllers;

use App\Models\ServiceRate;
use Illuminate\Http\Request;

class ServiceRateController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'annual_price' => 'nullable|numeric|min:0',
        ]);

        ServiceRate::create($validated);
        return back()->with('flash.banner', 'Tarifa de servicio creada.');
    }

    public function update(Request $request, ServiceRate $serviceRate)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'annual_price' => 'nullable|numeric|min:0',
        ]);

        $serviceRate->update($validated);
        return back()->with('flash.banner', 'Tarifa de servicio actualizada.');
    }

    public function destroy(ServiceRate $serviceRate)
    {
        $serviceRate->delete();
        return back()->with('flash.banner', 'Tarifa de servicio eliminada.');
    }
}
