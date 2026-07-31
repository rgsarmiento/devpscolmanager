<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LicensePackage;

class LicensePackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            ['name' => 'Renovación Única', 'min_licenses' => 1, 'max_licenses' => 1, 'total_price' => 281000],
            ['name' => 'Paquete 2 a 3', 'min_licenses' => 2, 'max_licenses' => 3, 'total_price' => 393000],
            ['name' => 'Paquete 4 a 7', 'min_licenses' => 4, 'max_licenses' => 7, 'total_price' => 561750],
            ['name' => 'Paquete 8 a 11', 'min_licenses' => 8, 'max_licenses' => 11, 'total_price' => 730000],
            ['name' => 'Paquete 12 a 15', 'min_licenses' => 12, 'max_licenses' => 15, 'total_price' => 896000],
        ];

        foreach ($packages as $package) {
            LicensePackage::updateOrCreate(
                ['min_licenses' => $package['min_licenses'], 'max_licenses' => $package['max_licenses']],
                $package
            );
        }
    }
}
