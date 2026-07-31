<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $expiredServices = \App\Models\ClientService::with('client')
        ->where('expiration_date', '<=', today())
        ->where('is_active', true)
        ->get();

    foreach ($expiredServices as $service) {
        $alreadyBilled = \App\Models\LicenseTransaction::where('client_id', $service->client_id)
            ->where('type', 'service')
            ->where('service_name', $service->name)
            ->whereDate('created_at', '>=', $service->expiration_date)
            ->exists();

        if (!$alreadyBilled) {
            \App\Models\LicenseTransaction::create([
                'client_id' => $service->client_id,
                'distributor_id' => $service->client->distributor_id,
                'type' => 'service',
                'service_name' => $service->name,
                'status' => 'pending'
            ]);
        }
    }
})->daily();

Schedule::command('folios:sync')->everyTwoHours();
