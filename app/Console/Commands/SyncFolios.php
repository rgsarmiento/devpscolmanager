<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BillingApiService;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class SyncFolios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folios:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize folio consumption from external API for all active clients';

    /**
     * Execute the console command.
     */
    public function handle(BillingApiService $billingService)
    {
        $this->info('Iniciando sincronización masiva de folios...');
        Log::info('SyncFolios Command: Iniciando sincronización masiva de folios.');

        try {
            $externalData = $billingService->syncAllClients();
            $syncedCount = 0;

            foreach ($externalData as $item) {
                $client = Client::where('nit', $item->Nit)->first();
                if ($client && $client->invoicingInfo) {
                    $client->invoicingInfo->update([
                        'folios_total' => $item->absolut_plan_documents,
                        'folios_remaining' => $item->documentos_restantes,
                        'plan_start_date' => $item->absolut_start_plan_date,
                    ]);
                    
                    // Clear individual cache
                    cache()->forget("plan_info_db_" . $item->Nit);
                    $syncedCount++;
                }
            }

            $this->info("Sincronización completada. Clientes actualizados: {$syncedCount}");
            Log::info("SyncFolios Command: Sincronización completada. Clientes actualizados: {$syncedCount}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error durante la sincronización: ' . $e->getMessage());
            Log::error('SyncFolios Command Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
