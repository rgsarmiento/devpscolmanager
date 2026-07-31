<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoicingController extends Controller
{
    protected $billingService;

    public function __construct(\App\Services\BillingApiService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function configCompany(Request $request, $clientId)
    {
        $client = \App\Models\Client::findOrFail($clientId);
        
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para modificar este cliente.');
        }
        
        $data = $request->validate([
            'type_document_identification_id' => 'nullable|integer',
            'business_name' => 'nullable|string',
            'merchant_registration' => 'nullable|string',
            'nit' => 'required|string', // Keep NIT required for URL
            'dv' => 'required|string', // Keep DV required for URL
            'type_organization_id' => 'nullable|integer',
            'type_regime_id' => 'nullable|integer',
            'type_liability_id' => 'nullable|integer',
            'municipality_id' => 'nullable|integer',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'plan_documents' => 'nullable|integer',
            'plan_start_date' => 'nullable|string', // Format: Y-m-d H:i:s
            // Mail & IMAP (Nullable as requested)
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|string',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'nullable|string',
            'mail_from_name' => 'nullable|string',
            
            'imap_server' => 'nullable|string',
            'imap_port' => 'nullable|string',
            'imap_user' => 'nullable|string',
            'imap_password' => 'nullable|string',
            'imap_encryption' => 'nullable|in:tls,ssl',
        ]);

        try {
            // Filter payload based on user request:
            // - If IMAP Password is empty, remove all IMAP properties
            // - If Mail Password is empty, remove all Mail properties
            $payload = $data;

            if (empty($payload['mail_password'])) {
                $payload = array_filter($payload, function($key) {
                    return !str_starts_with($key, 'mail_');
                }, ARRAY_FILTER_USE_KEY);
            }

            if (empty($payload['imap_password'])) {
                 $payload = array_filter($payload, function($key) {
                    return !str_starts_with($key, 'imap_');
                }, ARRAY_FILTER_USE_KEY);
            }

            // Mapping for API as requested
            if (isset($data['plan_documents'])) {
               $payload['absolut_plan_documents'] = $data['plan_documents'];
            }
            if (isset($data['plan_start_date'])) {
               $data['plan_start_date'] = str_replace('T', ' ', $data['plan_start_date']);
               $payload['absolut_start_plan_date'] = $data['plan_start_date'];
            }

            // Service expects $payload, $nit, $dv
            $response = $this->billingService->configCompany($payload, $data['nit'], $data['dv']);
            
            // Save token AND mail config settings
            if (isset($response['token']) && isset($response['company']['id'])) {
                 // Update Client Data with latest info from form
                 $client->update([
                    'name' => $data['business_name'] ?? $client->name,
                    'merchant_registration' => $data['merchant_registration'] ?? $client->merchant_registration,
                    'type_document_identification_id' => $data['type_document_identification_id'] ?? $client->type_document_identification_id,
                    'type_organization_id' => $data['type_organization_id'] ?? $client->type_organization_id,
                    'type_regime_id' => $data['type_regime_id'] ?? $client->type_regime_id,
                    'type_liability_id' => $data['type_liability_id'] ?? $client->type_liability_id,
                    'municipality_id' => $data['municipality_id'] ?? $client->municipality_id,
                    'address' => $data['address'] ?? $client->address,
                    'phone' => $data['phone'] ?? $client->phone,
                    'email' => $data['email'] ?? $client->email,
                    'dv' => $data['dv'] ?? $client->dv,
                 ]);

                 $oldPlanDocuments = $client->invoicingInfo->plan_documents ?? 0;
                 $newPlanDocuments = $data['plan_documents'] ?? 0;
                 
                 $oldPlanDate = isset($client->invoicingInfo->plan_start_date) ? date('Y-m-d H:i:s', strtotime($client->invoicingInfo->plan_start_date)) : '';
                 $newPlanDate = isset($data['plan_start_date']) ? date('Y-m-d H:i:s', strtotime($data['plan_start_date'])) : '';

                 if (($oldPlanDocuments != $newPlanDocuments || $oldPlanDate != $newPlanDate) && $newPlanDocuments > 0 && $request->boolean('generate_pending_folios')) {
                     $type = $newPlanDocuments >= 1000000 ? 'unlimited_folios' : 'folios';
                     $hasPending = \App\Models\LicenseTransaction::where('client_id', $client->id)
                         ->whereIn('type', ['folios', 'unlimited_folios'])
                         ->where('status', 'pending')
                         ->first();

                     if ($hasPending) {
                         $hasPending->update([
                             'folios_count' => $newPlanDocuments,
                             'type' => $type
                         ]);
                     } else {
                         \App\Models\LicenseTransaction::create([
                             'client_id' => $client->id,
                             'distributor_id' => $client->distributor_id,
                             'type' => $type,
                             'folios_count' => $newPlanDocuments,
                             'status' => 'pending'
                         ]);
                     }
                 }

                 $client->invoicingInfo()->updateOrCreate(
                    ['client_id' => $client->id],
                    [
                        'api_token' => $response['token'],
                        'company_id' => $response['company']['id'],
                        'plan_documents' => $data['plan_documents'] ?? null,
                        'plan_start_date' => $data['plan_start_date'] ?? null,
                        // Save Configs
                        'mail_host' => $data['mail_host'] ?? null,
                        'mail_port' => $data['mail_port'] ?? null,
                        'mail_username' => $data['mail_username'] ?? null,
                        'mail_password' => $data['mail_password'] ?? null,
                        'mail_encryption' => $data['mail_encryption'] ?? null,
                        'mail_from_address' => $data['mail_from_address'] ?? null,
                        'mail_from_name' => $data['mail_from_name'] ?? null,

                        'imap_server' => $data['imap_server'] ?? null,
                        'imap_port' => $data['imap_port'] ?? null,
                        'imap_user' => $data['imap_user'] ?? null,
                        'imap_password' => $data['imap_password'] ?? null,
                        'imap_encryption' => $data['imap_encryption'] ?? null,
                    ]
                 );
            }

            return back()->with('flash.banner', 'Compañía Configurada. Token Recibido y Configuraciones Guardadas.');
        } catch (\Exception $e) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', $e->getMessage());
        }
    }

    public function updatePlanDirect(Request $request, $clientId)
    {
        $client = \App\Models\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (!$client->nit) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'El cliente no tiene un NIT configurado.');
        }

        $validated = $request->validate([
            'plan_documents' => 'required|integer|min:0',
            'plan_start_date' => 'required|string',
        ]);

        try {
            // Update external DB directly
            $planStartDate = \Carbon\Carbon::parse($validated['plan_start_date'])->addHours(5)->format('Y-m-d H:i:s');
            
            \Illuminate\Support\Facades\DB::connection('api_external')
                ->table('companies')
                ->where('identification_number', $client->nit)
                ->update([
                    'absolut_plan_documents' => $validated['plan_documents'],
                    'absolut_start_plan_date' => $planStartDate,
                ]);

            // Update local info
            if ($client->invoicingInfo) {
                $client->invoicingInfo->update([
                    'plan_documents' => $validated['plan_documents'],
                    'plan_start_date' => $planStartDate,
                ]);
            } else {
                $client->invoicingInfo()->create([
                    'plan_documents' => $validated['plan_documents'],
                    'plan_start_date' => $planStartDate,
                ]);
            }

            return back()->with('flash.banner', 'Plan actualizado directamente con éxito.');
        } catch (\Exception $e) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'Error al actualizar plan: ' . $e->getMessage());
        }
    }

    public function configSoftware(Request $request, $clientId)
    {
        $client = \App\Models\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para modificar este cliente.');
        }
        $token = $client->invoicingInfo?->api_token;

        if (!$token) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'No hay token configurado. Configura la compañía primero.');
        }

        $validated = $request->validate([
            'id' => 'required|string', // Software ID
            'pin' => 'required|string',
        ]);

        try {
            $this->billingService->configSoftware($token, $validated['id'], $validated['pin']);
            
            if ($client->invoicingInfo) {
                $client->invoicingInfo->update([
                    'software_identifier' => $validated['id'],
                    'software_pin' => $validated['pin'],
                ]);
            }

            return back()->with('flash.banner', 'Software Configurado Correctamente.');
        } catch (\Exception $e) {
             return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', $e->getMessage());
        }
    }
    
    public function configResolution(Request $request, $clientId)
    {
        $client = \App\Models\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para modificar este cliente.');
        }
        $token = $client->invoicingInfo?->api_token;

        if (!$token) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'No hay token configurado.');
        }

        $type = $request->input('type_document_id');

        // Validation based on type
        $rules = [
            'type_document_id' => 'required|integer',
            'prefix' => 'required|string',
            'from' => 'required|integer',
            'to' => 'required|integer',
        ];

        if (in_array($type, [1, 3, 11])) {
            $rules['resolution'] = 'required|string';
            $rules['resolution_date'] = 'required|date';
            $rules['date_from'] = 'required|date';
            $rules['date_to'] = 'required|date';
        }
        
        if ($type == 1) {
            $rules['technical_key'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        // Build payload
        $payload = [
            'type_document_id' => $validated['type_document_id'],
            'prefix' => $validated['prefix'],
            'from' => $validated['from'],
            'to' => $validated['to'],
        ];

        if (in_array($type, [1, 3, 11])) {
            $payload['resolution'] = $validated['resolution'];
            $payload['resolution_date'] = $validated['resolution_date'];
            $payload['date_from'] = $validated['date_from'];
            $payload['date_to'] = $validated['date_to'];
            $payload['generated_to_date'] = 0;
            if ($type == 1) {
                $payload['technical_key'] = $request->input('technical_key') ?? '';
            }
        } elseif (in_array($type, [4, 5])) {
            $payload['resolution'] = '0000000000';
        }
        
        try {
            $this->billingService->configResolution($token, $payload);
            
            \App\Models\ClientResolution::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'prefix' => $payload['prefix'],
                    'resolution' => $payload['resolution'] ?? null,
                ],
                [
                    'type_document_id' => $payload['type_document_id'],
                    'resolution_date' => $payload['resolution_date'] ?? null,
                    'technical_key' => $payload['technical_key'] ?? null,
                    'from' => $payload['from'],
                    'to' => $payload['to'],
                    'date_from' => $payload['date_from'] ?? null,
                    'date_to' => $payload['date_to'] ?? null,
                    'environment' => 'produccion'
                ]
            );

            return back()->with('flash.banner', 'Resolución Configurada Correctamente.');
        } catch (\Exception $e) {
             return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', $e->getMessage());
        }
    }

    public function fetchResolutions(Request $request, $clientId)
    {
        $client = \App\Models\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para modificar este cliente.');
        }
        $token = $client->invoicingInfo?->api_token;

        if (!$token || !$client->nit || !$client->invoicingInfo->software_identifier) {
            return response()->json(['error' => 'Falta token, NIT o Software ID para consultar.'], 400);
        }

        try {
            $data = $this->billingService->getNumberingRange($token, $client->nit, $client->invoicingInfo->software_identifier);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function configCertificate(Request $request, $clientId)
    {
        $client = \App\Models\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (auth()->user()->isDistributor()) {
            abort_if($client->distributor_id !== auth()->user()->distributor_id, 403, 'No tienes permiso para modificar este cliente.');
        }
        $token = $client->invoicingInfo?->api_token;

        if (!$token) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'No hay token configurado.');
        }

        $request->validate([
            'certificate' => 'required|file', // .p12 file
            'password' => 'required|string',
        ]);

        try {
            // Read file and encode to base64
            $file = $request->file('certificate');
            $content = file_get_contents($file->getPathname());
            $base64 = base64_encode($content);
            $password = $request->input('password');

            $response = $this->billingService->configCertificate($token, $base64, $password);

            if (isset($response['certificate']['expiration_date'])) {
                if ($client->invoicingInfo) {
                    $client->invoicingInfo->update([
                        'certificate_expiration_date' => $response['certificate']['expiration_date'],
                        'certificate_password' => $password,
                    ]);
                }
            }

            return back()->with('flash.banner', 'Certificado Digital Configurado Correctamente.');
        } catch (\Exception $e) {
             return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', $e->getMessage());
        }
    }
}
