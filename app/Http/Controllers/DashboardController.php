<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Client;
use App\Models\Computer;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $clientsQuery = Client::query();
        $computersQuery = Computer::with('client')->where('is_active', true);
        $servicesQuery = \App\Models\ClientService::with('client')->where('is_active', true);
        $foliosQuery = \App\Models\InvoicingInfo::with(['client', 'client.distributor'])->where('folios_total', '>', 0)->where('is_active', true);
        $certsQuery = \App\Models\InvoicingInfo::with(['client', 'client.distributor'])->whereNotNull('certificate_expiration_date')->where('is_active', true);

        if ($user->isDistributor()) {
            $clientsQuery->where('distributor_id', $user->distributor_id);
            
            $computersQuery->whereHas('client', function($q) use ($user) {
                $q->where('distributor_id', $user->distributor_id);
            });
            
            $servicesQuery->whereHas('client', function($q) use ($user) {
                $q->where('distributor_id', $user->distributor_id);
            });
            
            $foliosQuery->whereHas('client', function($q) use ($user) {
                $q->where('distributor_id', $user->distributor_id);
            });
            
            $certsQuery->whereHas('client', function($q) use ($user) {
                $q->where('distributor_id', $user->distributor_id);
            });
        }

        return Inertia::render('Dashboard', [
            'totalClients' => $clientsQuery->count(),
            'totalLicenses' => (clone $computersQuery)->count(),
            'activeLicenses' => (clone $computersQuery)->count(), // Since it already has where('is_active', true)
            'expiringLicenses' => (clone $computersQuery)
                ->where('expiration_date', '<=', now()->addDays(30))
                ->orderBy('expiration_date', 'asc')
                ->paginate(5)
                ->withQueryString(),
            'recentClients' => (clone $clientsQuery)->latest()->take(5)->get(),
            'expiringServices' => (clone $servicesQuery)
                ->where('expiration_date', '<=', now()->addDays(15))
                ->orderBy('expiration_date', 'asc')
                ->get(),
            'criticalFolios' => (clone $foliosQuery)
                ->get()
                ->sortBy('dias_estimados_para_terminar')
                ->take(5)
                ->values(),
            'expiringCertificates' => function () use ($user) {
                // Get all company_ids for the allowed clients
                $invoicingInfos = \App\Models\InvoicingInfo::whereNotNull('company_id')
                    ->where('is_active', true)
                    ->whereHas('client', function($q) use ($user) {
                        if ($user->isDistributor()) {
                            $q->where('distributor_id', $user->distributor_id);
                        }
                    })
                    ->with(['client', 'client.distributor'])
                    ->get();
                
                $companyMap = $invoicingInfos->keyBy('company_id');
                $companyIds = $companyMap->keys()->toArray();

                if (empty($companyIds)) {
                    return collect([]);
                }

                try {
                    $externalCerts = \Illuminate\Support\Facades\DB::connection('api_external')
                        ->table('certificates')
                        ->whereIn('company_id', $companyIds)
                        ->where('expiration_date', '<=', now()->addDays(30))
                        ->orderBy('expiration_date', 'asc')
                        ->get();

                    $results = collect();
                    foreach ($externalCerts as $cert) {
                        if (isset($companyMap[$cert->company_id])) {
                            $invInfo = clone $companyMap[$cert->company_id];
                            $invInfo->certificate_expiration_date = \Carbon\Carbon::parse($cert->expiration_date)->toIso8601String();
                            $invInfo->certificate_password = $cert->password;
                            $results->push($invInfo);
                        }
                    }
                    
                    return $results->take(5)->values();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('No se pudo consultar certificados externos: ' . $e->getMessage());
                    // Fallback to local
                    $fallbackCertsQuery = \App\Models\InvoicingInfo::with(['client', 'client.distributor'])
                        ->whereNotNull('certificate_expiration_date')
                        ->where('is_active', true)
                        ->whereHas('client', function($q) use ($user) {
                            if ($user->isDistributor()) {
                                $q->where('distributor_id', $user->distributor_id);
                            }
                        });
                    return $fallbackCertsQuery->orderBy('certificate_expiration_date', 'asc')->take(5)->get();
                }
            },
        ]);
    }
}
