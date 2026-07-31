<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\InvoicingInfo;

class FolioConsumptionController extends Controller
{
    public function index(Request $request)
    {
        // For the paginated global view, we will use a raw query or collection pagination.
        // Since we need to sort by a computed attribute, and there could be thousands of records,
        // it's much better to do the math in the database query.
        
        $query = InvoicingInfo::with(['client', 'client.distributor'])
            ->where('folios_total', '>', 0)
            ->where('is_active', true)
            ->selectRaw('
                invoicing_infos.*, 
                DATEDIFF(NOW(), plan_start_date) as sql_dias,
                (folios_total - folios_remaining) as sql_usados,
                ((folios_total - folios_remaining) / GREATEST(DATEDIFF(NOW(), plan_start_date), 1)) as sql_promedio
            ')
            ->selectRaw('
                CASE 
                    WHEN ((folios_total - folios_remaining) / GREATEST(DATEDIFF(NOW(), plan_start_date), 1)) <= 0 THEN 999999
                    ELSE (folios_remaining / ((folios_total - folios_remaining) / GREATEST(DATEDIFF(NOW(), plan_start_date), 1)))
                END as sql_estimados
            ')
            ->orderBy('sql_estimados', 'asc');

        $consumptions = $query->paginate(20)->withQueryString();

        return Inertia::render('FolioConsumption/Index', [
            'consumptions' => $consumptions
        ]);
    }
}
