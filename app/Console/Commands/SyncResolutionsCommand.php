<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncResolutionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:resolutions';

    protected $description = 'Sync resolutions from external DB to local DB';

    public function handle()
    {
        $this->info('Starting resolutions sync...');
        $externalResolutions = \Illuminate\Support\Facades\DB::connection('api_external')->table('resolutions')->get();
        $count = 0;
        
        foreach ($externalResolutions as $extRes) {
            $invoicingInfo = \App\Models\InvoicingInfo::where('company_id', $extRes->company_id)->first();
            if ($invoicingInfo) {
                \App\Models\ClientResolution::updateOrCreate(
                    [
                        'client_id' => $invoicingInfo->client_id,
                        'prefix' => $extRes->prefix,
                        'resolution' => $extRes->resolution,
                    ],
                    [
                        'type_document_id' => $extRes->type_document_id,
                        'resolution_date' => $extRes->resolution_date,
                        'technical_key' => $extRes->technical_key,
                        'from' => $extRes->from,
                        'to' => $extRes->to,
                        'date_from' => $extRes->date_from,
                        'date_to' => $extRes->date_to,
                        'environment' => 'produccion', // Default from external
                    ]
                );
                $count++;
            }
        }
        $this->info("Synced {$count} resolutions successfully.");
    }
}
