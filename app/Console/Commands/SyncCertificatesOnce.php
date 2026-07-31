<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCertificatesOnce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:sync-once';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync certificates from external database once';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting one-time certificate sync...');
        
        $query = "
            SELECT 
                companies.identification_number AS Nit,
                certificates.password,
                certificates.expiration_date
            FROM 
                companies
            JOIN 
                certificates ON companies.id = certificates.company_id
        ";

        $results = \Illuminate\Support\Facades\DB::connection('api_external')->select($query);
        $count = 0;

        foreach ($results as $item) {
            $client = \App\Models\Client::where('nit', $item->Nit)->first();
            if ($client && $client->invoicingInfo) {
                $client->invoicingInfo->update([
                    'certificate_password' => $item->password,
                    'certificate_expiration_date' => $item->expiration_date,
                ]);
                $count++;
            }
        }

        $this->info("Successfully synced $count certificates.");
    }
}
