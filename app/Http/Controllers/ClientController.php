<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Parse RUT PDF and return extracted data.
     */
    public function parseRut(Request $request)
    {
        $request->validate([
            'rut_file' => 'required|file|mimes:pdf|max:2048',
        ]);

        $file = $request->file('rut_file');
        
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
            $lines = explode("\n", $text);
            
            $data = [];
            $candidates = [
                'names' => [],
                'addresses' => []
            ];

            // Keywords to ignore for Name detection
            $blocklist = [
                'COLOMBIA', 'BOGOTA', 'BOYACA', 'ANTIOQUIA', 'CUNDINAMARCA', 'MEDELLIN', 'CALI', 'BARRANQUILLA',
                'DIAN', 'NIT', 'FORMULARIO', 'IDENTIFICACION', 'IMPUESTO', 'ADUANAS', 'VALOR', 'PAGINA', 'HOJA',
                'COPIA', 'DOCUMENTO', 'ACTIVIDAD', 'PRINCIPAL', 'SECUNDARIA', 'CAMARA', 'COMERCIO', 'REGIMEN',
                'COMUN', 'SIMPLIFICADO', 'RESPONSABLE', 'IVA', 'INFO', 'TELEFONO', 'DIRECCION', 'CORREO',
                'UBICACION', 'FECHA', 'CIUDAD', 'MUNICIPIO', 'DEPARTAMENTO', 'PAIS', 'GENERAL', 'CONTRIBUYENTE',
                'PRIMER', 'SEGUNDO', 'APELLIDO', 'NOMBRE', 'RAZON', 'SOCIAL', 'ESTABLECIMIENTO', 'MATRICULA',
                'MERCANTIL', 'CIUU', 'TARIFA', 'RETENCION', 'RENTA', 'ICA'
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (strlen($line) < 3) continue;

                // 1. EMAIL
                if (!isset($data['email']) && preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $line, $m)) {
                    $data['email'] = $m[0];
                    continue; // Skip rest for this line
                }

                // 2. PHONE
                // Priority to Mobile (3xx)
                if (preg_match('/\b(3\d{9})\b/', $line, $m)) {
                    $data['phone'] = $m[1];
                } elseif (!isset($data['phone']) && preg_match('/\b(60\d{8,10})\b/', $line, $m)) {
                    $data['phone'] = $m[1];
                } else if (!isset($data['phone']) && preg_match('/^\d{7,10}$/', $line)) {
                     // Just numbers on a line? ambiguous, might be phone if not NIT
                }

                // 3. ADDRESS
                // Strong Pattern: Start with Address Type + Numbers
                // e.g. "CR 12 # 45 - 67"
                if (preg_match('/^(CR|CL|KR|CRA|CALLE|CARRERA|DG|DIAG|DIAGONAL|TV|TRANSVERSAL|AV|AVENIDA|AUTOPISTA)\.?\s+.*\d+/i', $line)) {
                    // Check if it's the DIAN address (usually "CARRERA 8 N 6C 38")
                    if (strpos($line, '6C') !== false && stripos($line, 'CARRERA') !== false) {
                        // likely DIAN default
                    } else {
                        $candidates['addresses'][] = $line;
                    }
                }

                // 4. NAME CANDIDATES
                // Uppercase letters and spaces ONLY (maybe dots). Length > 4.
                if (preg_match('/^[A-ZÑ\s\.]+$/u', $line)) {
                    // Check blocklist
                    $isBlocked = false;
                    foreach ($blocklist as $bad) {
                        if (strpos($line, $bad) !== false) {
                            $isBlocked = true;
                            break;
                        }
                    }
                    if (!$isBlocked) {
                        $candidates['names'][] = $line;
                    }
                }
            }

            // 5. NIT Extraction (using regex on the whole text as fallback since lines split it sometimes)
            // Look for the "NIT" label and grab numbers nearby
            if (preg_match('/NIT\s*[:\.]?\s*([\d\.]+)(\s*-\s*(\d))?/', $text, $matches)) {
                $data['nit'] = str_replace(['.'], '', $matches[1]);
                if (isset($matches[3])) $data['dv'] = $matches[3];
            }
            // If regex failed, look for any big number in candidates?
            if (empty($data['nit'])) {
                // Find biggest number in text?
                if (preg_match_all('/\b\d{9,10}\b/', $text, $nums)) {
                     $data['nit'] = $nums[0][0]; // Take first valid NIT looking number
                }
            }
            if (empty($data['dv']) && !empty($data['nit'])) {
                // Infer DV? Or check if text had "NIT ... - X"
                if (preg_match('/' . $data['nit'] . '\s*-\s*(\d)/', $text, $matches)) {
                    $data['dv'] = $matches[1];
                } else {
                     // Maybe the DV was captured in NIT
                     if (strlen($data['nit']) > 10) {
                         $data['dv'] = substr($data['nit'], -1);
                         $data['nit'] = substr($data['nit'], 0, -1);
                     }
                }
            }


            // DECISION TIME

            // Address: Pick longest or first
            if (!empty($candidates['addresses'])) {
                $data['address'] = $candidates['addresses'][0];
            }

            // Name: Pick best candidate
            // Heuristic: Company names often look like "FOO BAR SAS" (end with SAS, LTDA)
            // Person names look like "APELLIDO APELLIDO NOMBRE NOMBRE"
            // We'll just take the first candidate that isn't an Address or too short.
            if (!empty($candidates['names'])) {
                foreach ($candidates['names'] as $nameCandidate) {
                    // Validate it's not the address (uppercase check might passed it)
                    if (isset($data['address']) && $nameCandidate === $data['address']) continue;
                    
                    $data['name'] = $nameCandidate;
                    break; // Take first valid name
                }
            }
            
            // Normalize Name (User Request: 1 space)
            if (isset($data['name'])) {
                $data['name'] = preg_replace('/\s+/', ' ', $data['name']);
            }

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error parsing PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Client::with(['user', 'invoicingInfo', 'distributor']);

        if ($user->isDistributor()) {
            $query->where('distributor_id', $user->distributor_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nit', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');

        if ($sort === 'estimated_days') {
            $query->leftJoin('invoicing_infos', 'clients.id', '=', 'invoicing_infos.client_id')
                ->select('clients.*')
                ->orderByRaw('
                    CASE 
                        WHEN invoicing_infos.plan_start_date IS NULL THEN 999999
                        WHEN DATEDIFF(NOW(), invoicing_infos.plan_start_date) <= 0 THEN 999999
                        WHEN (invoicing_infos.folios_total - invoicing_infos.folios_remaining) <= 0 THEN 999999
                        ELSE (invoicing_infos.folios_remaining / ((invoicing_infos.folios_total - invoicing_infos.folios_remaining) / DATEDIFF(NOW(), invoicing_infos.plan_start_date)))
                    END ' . $direction
                );
        } else {
            $query->orderBy($sort, $direction);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $clients = $query->paginate($perPage)->withQueryString();

        return inertia('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // If logged in user is admin, they can select a distributor.
        // If distributor, they strictly assign to themselves (handled in store).
        $distributors = \App\Models\Distributor::all();

        return inertia('Clients/Create', [
            'distributors' => $distributors
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, \App\Services\BillingApiService $billingService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nit' => 'required|string|max:20',
            'dv' => 'nullable|string|max:1',
            'merchant_registration' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'municipality_id' => 'nullable|integer|exists:municipalities,id',
            'type_document_identification_id' => 'nullable|integer|exists:type_document_identifications,id',
            'type_organization_id' => 'nullable|integer|exists:type_organizations,id',
            'type_liability_id' => 'nullable|integer|exists:type_liabilities,id',
            'type_regime_id' => 'nullable|integer|exists:type_regimes,id',
            'user_id' => 'nullable|exists:users,id',
            'distributor_id' => 'nullable|exists:distributors,id',
        ]);

        $validated['user_id'] = $validated['user_id'] ?? auth()->id();

        $client = \App\Models\Client::create($validated);
        
        // Initialize invoicing info
        $info = $client->invoicingInfo()->create();

        // 1. Call External API to Configure Company
        // We use the data from the form to configure the remote company
        try {
            $payload = [
                'type_document_identification_id' => $validated['type_document_identification_id'],
                'type_organization_id' => $validated['type_organization_id'],
                'type_regime_id' => $validated['type_regime_id'],
                'type_liability_id' => $validated['type_liability_id'],
                'business_name' => $validated['name'],
                'merchant_registration' => $validated['merchant_registration'] ?? '0000000-00',
                'municipality_id' => $validated['municipality_id'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                // Mail settings required by API (defaults)
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '587',
                'mail_username' => $validated['email'], // Use client email as placeholder
                'mail_password' => 'password', // Placeholder
                'mail_encryption' => 'tls',
            ];

            // If DV is missing, API might fail if URL needs it. Ensure we have something.
            $nit = $validated['nit'];
            $dv = $validated['dv'] ?? '0'; // Fallback if missing

            $response = $billingService->configCompany($payload, $nit, $dv);

            $token = $response['token'] ?? $response['api_token'] ?? null;
            $companyId = $response['company']['id'] 
                ?? $response['company_id'] 
                ?? (is_array($response['company'] ?? null) ? ($response['company']['id'] ?? null) : null)
                ?? (is_numeric($response['company'] ?? null) ? $response['company'] : null)
                ?? null;

            if (!$companyId && !empty($nit)) {
                try {
                    $extCompany = \Illuminate\Support\Facades\DB::connection('api_external')
                        ->table('companies')
                        ->where('identification_number', $nit)
                        ->first();
                    if ($extCompany) {
                        $companyId = $extCompany->id;
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not query company_id from api_external: ' . $e->getMessage());
                }
            }

            if ($token || $companyId) {
                $info->update([
                    'api_token' => $token,
                    'company_id' => $companyId,
                ]);
            }

        } catch (\Exception $e) {
            // Log the error but don't stop the flow. Flash a warning.
            \Illuminate\Support\Facades\Log::error('Billing API Error: ' . $e->getMessage());
            return redirect()->route('clients.show', $client->id)
                ->with('flash.banner', 'Empresa creada, pero falló la sincronización con la API: ' . $e->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }

        return redirect()->route('clients.show', $client->id)->with('flash.banner', 'Empresa Creada y Sincronizada Correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id, \App\Services\BillingApiService $billingService)
    {
        $client = \App\Models\Client::with(['computers', 'invoicingInfo', 'clientServices', 'distributor', 'resolutions'])->findOrFail($id);

        // Security check for distributors
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para ver este cliente.');
        }

        // Paginate Computers (Licenses)
        $perPage = $request->integer('per_page_pc', 5);
        $computers = $client->computers()
            ->with(['licenseTransactions' => function($q) {
                $q->where('status', 'pending');
            }])
            ->orderBy('expiration_date', 'asc')
            ->orderBy('box_number', 'asc')
            ->paginate($perPage, ['*'], 'page_pc')
            ->withQueryString();

        // Get Plan Info from External DB if NIT exists
        $planInfo = null;
        if ($client->nit) {
            try {
                $planInfo = $billingService->getPlanInfo($client->nit);
                
                // Persist/Sync data to DB for the list view
                if ($planInfo && isset($planInfo['success']) && $planInfo['success']) {
                    $client->invoicingInfo()->updateOrCreate(
                        ['client_id' => $client->id],
                        [
                            'folios_total' => $planInfo['absolut_plan_documents'] ?? ($client->invoicingInfo->folios_total ?? 0),
                            'folios_remaining' => $planInfo['docs_left_absolut'] ?? ($client->invoicingInfo->folios_remaining ?? 0),
                            'plan_start_date' => $planInfo['absolut_start_plan_date'] ?? ($client->invoicingInfo->plan_start_date ?? null),
                            'days_transpired' => $planInfo['dias_transcurridos'] ?? 0,
                            'avg_folios_per_day' => $planInfo['promedio_folios_usados_por_dia'] ?? 0,
                            'estimated_days_to_depletion' => $planInfo['dias_estimados_para_terminar'] ?? 0,
                        ]
                    );
                    // Refresh the relationship after updateOrCreate to ensure $client->invoicingInfo is populated
                    $client->load('invoicingInfo');
                }
            } catch (\Exception $e) {
                // Silently fail or log, we don't want to break the whole view
                \Illuminate\Support\Facades\Log::warning("Could not fetch plan info for client {$id}: " . $e->getMessage());
            }
        }

        $serviceRates = \App\Models\ServiceRate::orderBy('name')->get();
        $folioRates = \App\Models\FolioRate::orderBy('min_folios')->get();

        return inertia('Clients/Show', [
            'client' => $client,
            'computers' => $computers, // Separate prop for paginated results
            'planInfo' => $planInfo,
            'distributors' => \App\Models\Distributor::all(),
            'serviceRates' => $serviceRates,
            'folioRates' => $folioRates,
            'catalogs' => [
                'document_types' => \App\Models\TypeDocumentIdentification::all(),
                'liabilities' => \App\Models\TypeLiability::all(),
                'organizations' => \App\Models\TypeOrganization::all(),
                'regimes' => \App\Models\TypeRegime::all(),
                'municipalities' => \App\Models\Municipality::select('id', 'name', 'department_id')->get(),
                'departments' => \App\Models\Department::all(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'distributor_id' => 'nullable|exists:distributors,id',
        ]);

        $client->update($validated);
        
        return back()->with('flash.banner', 'Cliente actualizado correctamente.');
    }

    public function toggleEnvironment(Request $request, Client $client, \App\Services\BillingApiService $billingService)
    {
        $user = auth()->user();
        if ($user->isDistributor() && $client->distributor_id !== $user->distributor_id) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'environment_status' => 'required|in:pruebas,produccion',
        ]);

        try {
            $apiToken = $client->invoicingInfo->api_token ?? null;
            if ($apiToken) {
                // 1 = Produccion, 2 = Pruebas
                $environmentId = $validated['environment_status'] === 'produccion' ? 1 : 2;
                $billingService->configEnvironment($apiToken, $environmentId);
            }

            $client->update(['environment_status' => $validated['environment_status']]);

            return back()->with('flash.banner', 'Estado de entorno actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->with('flash.banner', 'Error al actualizar el entorno: ' . $e->getMessage())->with('flash.bannerStyle', 'danger');
        }
    }

    public function updateWhatsappContact(Request $request, Client $client)
    {
        $user = auth()->user();
        if ($user->isDistributor() && $client->distributor_id !== $user->distributor_id) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'whatsapp_contact' => 'nullable|string|max:50',
        ]);

        $client->update($validated);
        
        return back()->with('flash.banner', 'Contacto de WhatsApp actualizado correctamente.');
    }

    public function refreshPlan(\App\Models\Client $client, \App\Services\BillingApiService $billingService)
    {
        if ($client->nit) {
            try {
                // Forget cache to force fresh data on manual refresh
                cache()->forget("plan_info_db_" . $client->nit);

                $planInfo = $billingService->getPlanInfo($client->nit);
                if ($planInfo && isset($planInfo['success']) && $planInfo['success']) {
                    $client->invoicingInfo()->updateOrCreate(
                        ['client_id' => $client->id],
                        [
                            'folios_total' => $planInfo['absolut_plan_documents'] ?? ($client->invoicingInfo->folios_total ?? 0),
                            'folios_remaining' => $planInfo['docs_left_absolut'] ?? ($client->invoicingInfo->folios_remaining ?? 0),
                            'plan_start_date' => $planInfo['absolut_start_plan_date'] ?? ($client->invoicingInfo->plan_start_date ?? null),
                            'days_transpired' => $planInfo['dias_transcurridos'] ?? 0,
                            'avg_folios_per_day' => $planInfo['promedio_folios_usados_por_dia'] ?? 0,
                            'estimated_days_to_depletion' => $planInfo['dias_estimados_para_terminar'] ?? 0,
                        ]
                    );
                    return back()->with('flash.banner', "Consumo actualizado para {$client->name}.");
                }
            } catch (\Exception $e) {
                return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'Error al actualizar consumo: ' . $e->getMessage());
            }
        }
        return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'No hay NIT configurado para esta empresa.');
    }

    public function syncAll(\App\Services\BillingApiService $billingService)
    {
        try {
            $externalData = $billingService->syncAllClients();
            $syncedCount = 0;

            foreach ($externalData as $item) {
                $client = \App\Models\Client::where('nit', $item->Nit)->first();
                if ($client && $client->invoicingInfo) {
                    $client->invoicingInfo->update([
                        'folios_total' => $item->absolut_plan_documents,
                        'folios_remaining' => $item->documentos_restantes,
                        'plan_start_date' => $item->absolut_start_plan_date,
                    ]);
                    
                    // Also clear individual cache
                    cache()->forget("plan_info_db_" . $item->Nit);
                    $syncedCount++;
                }
            }

            return back()->with('flash.banner', "Sincronización masiva completada: {$syncedCount} clientes actualizados.");
        } catch (\Exception $e) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'Error en sincronización masiva: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }
}
