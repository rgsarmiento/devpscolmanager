<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Services\LicenseService;

class ComputerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Computer::query()->with('client');

        // Distributor Scoping
        if (auth()->user()->isDistributor()) {
            $query->whereHas('client', function($q) {
                $q->where('distributor_id', auth()->user()->distributor_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_key', 'like', "%{$search}%")
                  ->orWhere('box_number', 'like', "%{$search}%")
                  ->orWhere('pin', 'like', "%{$search}%")
                  ->orWhere('observation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Sorting
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        
        if ($sort === 'client_name') {
            $query->join('clients', 'computers.client_id', '=', 'clients.id')
                  ->select('computers.*')
                  ->orderBy('clients.name', $direction);
        } elseif ($sort === 'days_remaining') {
            $query->orderBy('expiration_date', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $computers = $query->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(function($computer) {
                $now = now();
                $expiration = \Carbon\Carbon::parse($computer->expiration_date);
                $computer->days_remaining = (int) $now->diffInDays($expiration, false);
                return $computer;
            });

        return \Inertia\Inertia::render('Computers/Index', [
            'computers' => $computers,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page', 'client_id']),
            'clients' => auth()->user()->isDistributor() 
                ? \App\Models\Client::where('distributor_id', auth()->user()->distributor_id)->get(['id', 'name'])
                : \App\Models\Client::all(['id', 'name']),
            'isAdmin' => auth()->user()->isAdmin(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'box_number' => 'required|string',
            'name' => 'nullable|string',
            'license_key' => 'nullable|string|unique:computers,license_key',
            'pin' => 'nullable|string',
            'observation' => 'nullable|string',
            'expiration_date' => 'nullable|date',
            'license_type' => 'required|in:normal,vinculado',
            'is_active' => 'boolean',
        ]);

        // Security Check
        $client = \App\Models\Client::findOrFail($validated['client_id']);
        if (auth()->user()->isDistributor() && $client->user_id !== auth()->id()) {
            abort(403);
        }

        // Auto-generate license key if empty
        if (empty($validated['license_key'])) {
            $validated['license_key'] = $this->generateUniqueLicenseKey();
        }

        $computer = \App\Models\Computer::create($validated);

        if ($request->input('generate_charge', true)) {
            \App\Models\LicenseTransaction::create([
                'computer_id' => $computer->id,
                'client_id' => $client->id,
                'distributor_id' => $client->distributor_id,
                'type' => 'new',
                'status' => 'pending'
            ]);
        }

        return back()->with('flash.banner', 'Licencia/Computador Agregado Correctamente');
    }

    /**
     * Generate a unique license key format: XXXX-XXXX-XXXX-XXXX
     */
    protected function generateUniqueLicenseKey()
    {
        do {
            $key = strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                   strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                   strtoupper(\Illuminate\Support\Str::random(4)) . '-' .
                   strtoupper(\Illuminate\Support\Str::random(4));
        } while (\App\Models\Computer::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Computer $computer)
    {
        // Security Check
        $client = $computer->client;
        if (auth()->user()->isDistributor() && $client->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'box_number' => 'required|string',
            'name' => 'nullable|string',
            'license_key' => 'nullable|string',
            'pin' => 'nullable|string',
            'observation' => 'nullable|string',
            'expiration_date' => 'nullable|date',
            'license_type' => 'required|in:normal,vinculado',
            'is_active' => 'boolean',
        ]);

        $oldDate = $computer->expiration_date ? $computer->expiration_date->format('Y-m-d') : null;
        $newDate = empty($validated['expiration_date']) ? null : \Carbon\Carbon::parse($validated['expiration_date'])->format('Y-m-d');

        $computer->update($validated);

        if ($oldDate !== $newDate && $newDate) {
            $hasPending = \App\Models\LicenseTransaction::where('computer_id', $computer->id)
                ->where('status', 'pending')
                ->exists();

            if (!$hasPending && $request->input('generate_charge', true)) {
                \App\Models\LicenseTransaction::create([
                    'computer_id' => $computer->id,
                    'client_id' => $client->id,
                    'distributor_id' => $client->distributor_id,
                    'type' => 'renewal',
                    'status' => 'pending'
                ]);
            }
        }

        return back()->with('flash.banner', 'Licencia Actualizada');
    }

    /**
     * Generate a license key using LicenseService
     */
    public function generateLicense(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
            'expiration_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
        ]);

        $client = \App\Models\Client::findOrFail($request->client_id);
        
        // Si el PIN viene pegado con la empresa (Ej: "12345 Jd8/s..."),
        $pinParts = explode(' ', trim($request->pin));
        $keyPart = $pinParts[0];
        $encPart = count($pinParts) > 1 ? end($pinParts) : null;

        $decryptedData = $encPart ? LicenseService::decrypt($encPart, $keyPart) : 'na';
        $dataParts = explode(',', $decryptedData);

        if (count($dataParts) >= 6) {
            $pinNit = trim($dataParts[1]);
            
            // Extract numeric digits for comparison to avoid issues with formatting or verification digit
            $cleanClientNit = preg_replace('/[^0-9]/', '', $client->nit);
            $cleanPinNit = preg_replace('/[^0-9]/', '', $pinNit);

            if ($cleanPinNit !== $cleanClientNit && !str_starts_with($cleanClientNit, $cleanPinNit) && !str_starts_with($cleanPinNit, $cleanClientNit)) {
                return response()->json([
                    'message' => 'El NIT del PIN no coincide con el del cliente.',
                    'decoded_data' => [
                        'serial' => trim($dataParts[0] ?? ''),
                        'nit' => $pinNit,
                        'client_name' => trim($dataParts[2] ?? ''),
                        'pc_name' => trim($dataParts[3] ?? ''),
                        'address' => trim($dataParts[4] ?? ''),
                        'box_number' => trim($dataParts[5] ?? ''),
                    ]
                ], 422);
            }

            $fechaFormateada = \Carbon\Carbon::parse($request->expiration_date)->format('d-m-Y');
            $licenseKey = LicenseService::encrypt($fechaFormateada, $keyPart);

            return response()->json([
                'license_key' => $licenseKey,
                'extracted_data' => [
                    'pc_name' => trim($dataParts[3]),
                    'box_number' => trim($dataParts[5])
                ]
            ]);
        }

        if ($decryptedData === 'na' || count($dataParts) < 6) {
            return response()->json([
                'message' => 'El PIN proporcionado no es válido o está corrupto.'
            ], 422);
        }

        // Default behavior if something bypassed the above
        $fechaFormateada = \Carbon\Carbon::parse($request->expiration_date)->format('d-m-Y');
        $licenseKey = LicenseService::encrypt($fechaFormateada, $keyPart);

        return response()->json(['license_key' => $licenseKey]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Computer $computer)
    {
         // Security Check
         $client = $computer->client;
         if (auth()->user()->isDistributor() && $client->user_id !== auth()->id()) {
             abort(403);
         }

         $computer->delete();

         return back()->with('flash.banner', 'Licencia Eliminada');
    }
}
