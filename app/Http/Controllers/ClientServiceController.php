<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\LicenseTransaction;
use Illuminate\Http\Request;

class ClientServiceController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'expiration_date' => 'required|date',
            'price' => 'required|numeric|min:0',
        ]);

        $client->clientServices()->create([
            'name' => $validated['name'],
            'expiration_date' => $validated['expiration_date'],
            'price' => $validated['price'],
        ]);

        return back()->with('flash.banner', 'Servicio asignado correctamente.');
    }

    public function update(Request $request, Client $client, ClientService $clientService)
    {
        $validated = $request->validate([
            'expiration_date' => 'required|date',
            'price' => 'required|numeric|min:0',
        ]);

        $clientService->update([
            'expiration_date' => $validated['expiration_date'],
            'price' => $validated['price'],
        ]);

        return back()->with('flash.banner', 'Servicio actualizado correctamente.');
    }

    public function destroy(Client $client, ClientService $clientService)
    {
        // Delete pending transactions for this service
        LicenseTransaction::where('client_id', $client->id)
            ->where('type', 'service')
            ->where('service_name', $clientService->name)
            ->where('status', 'pending')
            ->delete();

        $clientService->delete();

        return back()->with('flash.banner', 'Servicio eliminado correctamente.');
    }
}
