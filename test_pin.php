<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Fake pin for testing, do we have any in DB?
$computers = \App\Models\Computer::whereNotNull('pin')->pluck('pin')->take(5);
foreach($computers as $p) {
    echo "Raw DB PIN: $p\n";
    $parts = explode(' ', trim($p));
    $keyPart = $parts[0];
    $encPart = count($parts) > 1 ? end($parts) : null;
    
    if ($encPart) {
        $decrypted1 = \App\Services\LicenseService::decrypt($encPart, '');
        $decrypted2 = \App\Services\LicenseService::decrypt($encPart, $keyPart);
        echo "Decrypt with empty key: $decrypted1\n";
        echo "Decrypt with key=$keyPart: $decrypted2\n";
    } else {
        $decrypted = \App\Services\LicenseService::decrypt($keyPart, '');
        echo "Only 1 part, Decrypt with empty key: $decrypted\n";
    }
}
