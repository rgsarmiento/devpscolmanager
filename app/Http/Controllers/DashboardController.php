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
            'expiringCertificates' => (clone $certsQuery)
                ->orderBy('certificate_expiration_date', 'asc')
                ->take(5)
                ->get(),
        ]);
    }
}
