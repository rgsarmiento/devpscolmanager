<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get all company_ids for the allowed clients
        $invoicingInfosQuery = \App\Models\InvoicingInfo::whereNotNull('company_id')
            ->where('is_active', true)
            ->with(['client', 'client.distributor']);

        if ($user->isDistributor()) {
            $invoicingInfosQuery->whereHas('client', function($q) use ($user) {
                $q->where('distributor_id', $user->distributor_id);
            });
        }

        $search = $request->input('search');
        if ($search) {
            $invoicingInfosQuery->whereHas('client', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nit', 'like', "%{$search}%");
            });
        }

        $invoicingInfos = $invoicingInfosQuery->get();
        $companyMap = $invoicingInfos->keyBy('company_id');
        $companyIds = $companyMap->keys()->toArray();

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 15;

        if (empty($companyIds)) {
            $certificates = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query()
            ]);
        } else {
            try {
                // Fetch all external certs for these companies
                $externalCerts = \Illuminate\Support\Facades\DB::connection('api_external')
                    ->table('certificates')
                    ->whereIn('company_id', $companyIds)
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

                $total = $results->count();
                $items = $results->slice(($page - 1) * $perPage, $perPage)->values();

                $certificates = new \Illuminate\Pagination\LengthAwarePaginator($items, $total, $perPage, $page, [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'query' => $request->query()
                ]);

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo consultar certificados externos: ' . $e->getMessage());
                
                // Fallback to local
                $fallbackQuery = \App\Models\InvoicingInfo::with(['client', 'client.distributor'])
                    ->whereNotNull('certificate_expiration_date')
                    ->where('is_active', true);
                
                if ($user->isDistributor()) {
                    $fallbackQuery->whereHas('client', function($q) use ($user) {
                        $q->where('distributor_id', $user->distributor_id);
                    });
                }

                $certificates = $fallbackQuery->orderBy('certificate_expiration_date', 'asc')->paginate($perPage)->withQueryString();
            }
        }

        return \Inertia\Inertia::render('Certificates/Index', [
            'certificates' => $certificates,
            'filters' => [
                'search' => $search
            ]
        ]);
    }
}
