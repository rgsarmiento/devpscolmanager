<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Distributor;
use App\Models\LicensePackage;
use App\Models\LicenseTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebtController extends Controller
{
    public function index()
    {
        // Auto-generate pending transactions for expired services
        $expiredServices = \App\Models\ClientService::where('is_active', true)
            ->where('expiration_date', '<=', now())
            ->get();

        foreach ($expiredServices as $svc) {
            $client = \App\Models\Client::find($svc->client_id);
            if ($client) {
                \App\Models\LicenseTransaction::firstOrCreate([
                    'client_id' => $client->id,
                    'type' => 'service',
                    'service_name' => $svc->name,
                    'status' => 'pending'
                ], [
                    'distributor_id' => $client->distributor_id
                ]);
            }
        }

        // Get all distributors with their pending transactions, grouped by client
        $distributors = Distributor::with(['clients' => function($q) {
            $q->whereHas('licenseTransactions', function($q2) {
                $q2->where('status', 'pending');
            })->with(['licenseTransactions' => function($q2) {
                $q2->where('status', 'pending');
            }]);
        }])->get();

        $directClients = \App\Models\Client::whereNull('distributor_id')
            ->whereHas('licenseTransactions', function($q) {
                $q->where('status', 'pending');
            })
            ->with(['licenseTransactions' => function($q) {
                $q->where('status', 'pending');
            }])->get();

        // Calculate totals for each client
        foreach ($distributors as $distributor) {
            foreach ($distributor->clients as $client) {
                $client->pending_amount = $this->calculateDebtInfo($client, 'distributor')['amount'];
                // Inject computed price
                foreach ($client->licenseTransactions as $tx) {
                    $clone = clone $client;
                    $clone->setRelation('licenseTransactions', collect([$tx]));
                    $tx->computed_price = $this->calculateDebtInfo($clone, 'distributor')['amount'];
                    if ($tx->type === 'service') {
                        $svc = \App\Models\ClientService::where('client_id', $client->id)->where('name', $tx->service_name)->first();
                        $tx->service_expiration = $svc ? $svc->expiration_date : null;
                    }
                }
            }
        }
        foreach ($directClients as $client) {
            $client->pending_amount = $this->calculateDebtInfo($client, 'direct')['amount'];
            foreach ($client->licenseTransactions as $tx) {
                $clone = clone $client;
                $clone->setRelation('licenseTransactions', collect([$tx]));
                $tx->computed_price = $this->calculateDebtInfo($clone, 'direct')['amount'];
                if ($tx->type === 'service') {
                    $svc = \App\Models\ClientService::where('client_id', $client->id)->where('name', $tx->service_name)->first();
                    $tx->service_expiration = $svc ? $svc->expiration_date : null;
                }
            }
        }

        $debts = Debt::with(['distributor', 'client'])->where('status', 'paid')->orderByDesc('created_at')->get();

        return Inertia::render('Debts/Index', [
            'distributors' => $distributors,
            'directClients' => $directClients,
            'debts' => $debts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'distributor_id' => 'nullable|exists:distributors,id',
            'client_id' => 'required|exists:clients,id',
        ]);

        $client = \App\Models\Client::with(['licenseTransactions' => function($q) {
            $q->where('status', 'pending');
        }])->findOrFail($validated['client_id']);

        $transactions = $client->licenseTransactions;
        
        if ($transactions->isEmpty()) {
            return back()->with('flash.banner', 'No hay transacciones pendientes para liquidar.');
        }

        $type = empty($validated['distributor_id']) ? 'direct' : 'distributor';
        $debtInfo = $this->calculateDebtInfo($client, $type);

        if ($debtInfo['amount'] <= 0 && empty($debtInfo['details'])) {
            return back()->with('flash.banner', 'No hay transacciones valorizadas pendientes.');
        }

        $debt = Debt::create([
            'distributor_id' => $validated['distributor_id'] ?? null,
            'client_id' => $validated['client_id'],
            'amount' => $debtInfo['amount'],
            'details' => implode(', ', $debtInfo['details']),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Mark as billed
        foreach ($transactions as $tx) {
            $tx->update([
                'status' => 'billed',
                'debt_id' => $debt->id
            ]);
        }

        return back()->with('flash.banner', 'Pago registrado correctamente.');
    }

    public function payTransaction(Request $request, LicenseTransaction $transaction)
    {
        $client = \App\Models\Client::findOrFail($transaction->client_id);
        $type = empty($client->distributor_id) ? 'direct' : 'distributor';
        
        // Temporarily calculate debt info for just this transaction by mocking the relation
        $client->setRelation('licenseTransactions', collect([$transaction]));
        $debtInfo = $this->calculateDebtInfo($client, $type);

        if ($debtInfo['amount'] <= 0 && empty($debtInfo['details'])) {
            // Even if amount is 0, we can still mark it billed if they insist, but let's just mark it
            $transaction->update(['status' => 'billed']);
            return back()->with('flash.banner', 'Transacción sin valor marcada como completada.');
        }

        $debt = Debt::create([
            'distributor_id' => $client->distributor_id,
            'client_id' => $client->id,
            'amount' => $debtInfo['amount'],
            'details' => implode(', ', $debtInfo['details']),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $transaction->update([
            'status' => 'billed',
            'debt_id' => $debt->id
        ]);

        return back()->with('flash.banner', 'Pago individual registrado correctamente.');
    }

    private function calculateDebtInfo($client, $type)
    {
        $transactions = $client->licenseTransactions;
        if (!$transactions || $transactions->isEmpty()) {
            return ['amount' => 0, 'details' => []];
        }

        $newCount = $transactions->where('type', 'new')->count();
        $renewalCount = $transactions->where('type', 'renewal')->count();
        $foliosTx = $transactions->whereIn('type', ['folios', 'unlimited_folios'])->first();
        $serviceTxs = $transactions->where('type', 'service');

        $amount = 0;
        $details = [];

        if ($newCount > 0) {
            $amount += $newCount * 450000;
            $details[] = "{$newCount} licencias nuevas ($450,000 c/u)";
        }

        if ($renewalCount > 0) {
            $package = \App\Models\LicensePackage::where('type', $type)
                ->where('min_licenses', '<=', $renewalCount)
                ->where('max_licenses', '>=', $renewalCount)
                ->first();

            if ($package) {
                $amount += $package->total_price;
                $details[] = "{$renewalCount} renov. (Paquete: {$package->name})";
            } else {
                $largest = \App\Models\LicensePackage::where('type', $type)->orderByDesc('max_licenses')->first();
                if ($largest) {
                    $amount += $largest->total_price;
                    $details[] = "{$renewalCount} renov. (Aplica paquete: {$largest->name})";
                }
            }
        }

        if ($foliosTx) {
            if ($foliosTx->type === 'unlimited_folios') {
                $unlimited = \App\Models\FolioRate::whereNull('max_folios')->first();
                if ($unlimited) {
                    $amount += $unlimited->price;
                    $details[] = "Folios Ilimitados ({$foliosTx->folios_count})";
                } else {
                    $details[] = "Folios Ilimitados (Sin tarifa)";
                }
            } else {
                $qty = $foliosTx->folios_count;
                $rate = \App\Models\FolioRate::whereNotNull('max_folios')
                    ->where('min_folios', '<=', $qty)
                    ->where('max_folios', '>=', $qty)
                    ->first();
                if ($rate) {
                    $cost = $qty * $rate->price;
                    $amount += $cost;
                    $details[] = "{$qty} folios a \${$rate->price} c/u";
                } else {
                    $details[] = "{$qty} folios (Sin tarifa en rango)";
                }
            }
        }

        foreach ($serviceTxs as $svcTx) {
            $clientService = \App\Models\ClientService::where('client_id', $client->id)
                ->where('name', $svcTx->service_name)
                ->first();
                
            if ($clientService && $clientService->price !== null) {
                $amount += $clientService->price;
                $details[] = "Servicio {$svcTx->service_name} (\$" . number_format($clientService->price, 0) . ")";
            } else {
                $details[] = "Servicio {$svcTx->service_name} (Sin tarifa)";
            }
        }

        return ['amount' => $amount, 'details' => $details];
    }

    public function pay(Debt $debt)
    {
        $debt->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('flash.banner', 'Deuda marcada como pagada.');
    }

    public function renewService(\App\Models\LicenseTransaction $transaction)
    {
        $clientService = \App\Models\ClientService::where('client_id', $transaction->client_id)
            ->where('name', $transaction->service_name)
            ->first();

        if ($clientService) {
            $clientService->update([
                'expiration_date' => \Carbon\Carbon::parse($clientService->expiration_date)->addYear()
            ]);
        }

        $transaction->update(['status' => 'paid']);

        return back()->with('flash.banner', 'Servicio renovado por 1 año exitosamente.');
    }

    public function cancelService(\App\Models\LicenseTransaction $transaction)
    {
        $clientService = \App\Models\ClientService::where('client_id', $transaction->client_id)
            ->where('name', $transaction->service_name)
            ->first();

        if ($clientService) {
            $clientService->update(['is_active' => false]);
        }

        $transaction->delete();

        return back()->with('flash.banner', 'Servicio cancelado.');
    }
}
