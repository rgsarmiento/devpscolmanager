<?php

namespace App\Http\Controllers;

use App\Models\LicensePackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicensePackageController extends Controller
{
    public function index()
    {
        $packages = LicensePackage::orderBy('type')->orderBy('min_licenses')->get();
        $folioRates = \App\Models\FolioRate::orderBy('min_folios')->get();
        $serviceRates = \App\Models\ServiceRate::orderBy('name')->get();
        
        return Inertia::render('LicensePackages/Index', [
            'packages' => $packages,
            'folioRates' => $folioRates,
            'serviceRates' => $serviceRates
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:distributor,direct',
            'min_licenses' => 'required|integer|min:1',
            'max_licenses' => 'required|integer|min:1|gte:min_licenses',
            'total_price' => 'required|numeric|min:0',
        ]);

        LicensePackage::create($validated);
        return back()->with('flash.banner', 'Paquete creado exitosamente.');
    }

    public function update(Request $request, LicensePackage $licensePackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:distributor,direct',
            'min_licenses' => 'required|integer|min:1',
            'max_licenses' => 'required|integer|min:1|gte:min_licenses',
            'total_price' => 'required|numeric|min:0',
        ]);

        $licensePackage->update($validated);
        return back()->with('flash.banner', 'Paquete actualizado.');
    }

    public function destroy(LicensePackage $licensePackage)
    {
        $licensePackage->delete();
        return back()->with('flash.banner', 'Paquete eliminado.');
    }
}
