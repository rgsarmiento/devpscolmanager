<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistributorController extends Controller
{
    public function index()
    {
        $distributors = Distributor::withCount('clients')->get();
        return Inertia::render('Distributors/Index', [
            'distributors' => $distributors
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        Distributor::create($validated);
        return back()->with('flash.banner', 'Distribuidor creado exitosamente.');
    }

    public function update(Request $request, Distributor $distributor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $distributor->update($validated);
        return back()->with('flash.banner', 'Distribuidor actualizado.');
    }

    public function destroy(Distributor $distributor)
    {
        $distributor->delete();
        return back()->with('flash.banner', 'Distribuidor eliminado.');
    }
}
