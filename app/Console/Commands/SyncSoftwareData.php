<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

class SyncSoftwareData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:software';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los datos de Software (ID y PIN) desde la base de datos externa api_external hacia la tabla local invoicing_infos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronización de Software ID y PIN...");

        $clients = Client::with('invoicingInfo')->get();
        $updatedCount = 0;

        foreach ($clients as $client) {
            if (!$client->nit || !$client->invoicingInfo) {
                continue;
            }

            // Consultar la empresa en la BD externa usando el NIT
            $company = DB::connection('api_external')
                ->table('companies')
                ->where('identification_number', $client->nit)
                ->first();

            if ($company) {
                // Buscar el software de esta empresa
                $software = DB::connection('api_external')
                    ->table('software')
                    ->where('company_id', $company->id)
                    ->first();

                if ($software) {
                    $client->invoicingInfo->update([
                        'software_identifier' => $software->identifier,
                        'software_pin' => $software->pin,
                    ]);
                    $this->info("Sincronizado cliente: {$client->name} (NIT: {$client->nit})");
                    $updatedCount++;
                }
            }
        }

        $this->info("Sincronización completada. Total actualizados: {$updatedCount}");
    }
}
