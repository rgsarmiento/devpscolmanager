const fs = require('fs');

let content = fs.readFileSync('app/Http/Controllers/InvoicingController.php', 'utf8');

const search = `    public function configSoftware(Request $request, $clientId)`;

const replace = `    public function updatePlanDirect(Request $request, $clientId)
    {
        $client = \\App\\Models\\Client::with('invoicingInfo')->findOrFail($clientId);
        
        if (!$client->nit) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'El cliente no tiene un NIT configurado.');
        }

        $validated = $request->validate([
            'plan_documents' => 'required|integer|min:0',
            'plan_start_date' => 'required|string',
        ]);

        try {
            // Update external DB directly
            $planStartDate = str_replace('T', ' ', $validated['plan_start_date']);
            
            \\Illuminate\\Support\\Facades\\DB::connection('api_external')
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
        } catch (\\Exception $e) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'Error al actualizar plan: ' . $e->getMessage());
        }
    }

    public function configSoftware(Request $request, $clientId)`;

if (!content.includes('public function updatePlanDirect')) {
    content = content.replace(search, replace);
}

fs.writeFileSync('app/Http/Controllers/InvoicingController.php', content);
console.log('Added updatePlanDirect to InvoicingController');
