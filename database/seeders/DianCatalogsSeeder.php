<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DianCatalogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Type Document Identifications
        $documents = [
            ['id' => 1, 'name' => 'Registro civil', 'code' => '11'],
            ['id' => 2, 'name' => 'Tarjeta de identidad', 'code' => '12'],
            ['id' => 3, 'name' => 'Cédula de ciudadanía', 'code' => '13'],
            ['id' => 4, 'name' => 'Tarjeta de extranjería', 'code' => '21'],
            ['id' => 5, 'name' => 'Cédula de extranjería', 'code' => '22'],
            ['id' => 6, 'name' => 'NIT', 'code' => '31'],
            ['id' => 7, 'name' => 'Pasaporte', 'code' => '41'],
            ['id' => 8, 'name' => 'Documento de identificación extranjero', 'code' => '42'],
            ['id' => 9, 'name' => 'NIT de otro país', 'code' => '50'],
            ['id' => 10, 'name' => 'NUIP *', 'code' => '91'],
            ['id' => 11, 'name' => 'PEP (Permiso Especial de Permanencia)', 'code' => '47'],
            ['id' => 12, 'name' => 'PPT (Permiso Protección Temporal)', 'code' => '48'],
        ];

        foreach ($documents as $doc) {
            \App\Models\TypeDocumentIdentification::updateOrCreate(['id' => $doc['id']], $doc);
        }

        // 2. Type Liabilities
        $liabilities = [
            ['id' => 7, 'name' => 'Gran contribuyente', 'code' => 'O-13'],
            ['id' => 9, 'name' => 'Autorretenedor', 'code' => 'O-15'],
            ['id' => 14, 'name' => 'Agente de retención en el impuesto sobre las ventas', 'code' => 'O-23'],
            ['id' => 112, 'name' => 'Régimen Simple de Tributación – SIMPLE', 'code' => 'O-47'],
            ['id' => 117, 'name' => 'No responsable', 'code' => 'R-99-PN'],
        ];
        
        foreach ($liabilities as $lia) {
            \App\Models\TypeLiability::updateOrCreate(['id' => $lia['id']], $lia);
        }

        // 3. Type Organizations
        $organizations = [
            ['id' => 1, 'name' => 'Persona Jurídica y asimiladas', 'code' => '1'],
            ['id' => 2, 'name' => 'Persona Natural y asimiladas', 'code' => '2'],
        ];

        foreach ($organizations as $org) {
            \App\Models\TypeOrganization::updateOrCreate(['id' => $org['id']], $org);
        }

        // 4. Type Regimes
        $regimes = [
            ['id' => 1, 'name' => 'Responsable de IVA', 'code' => '48'],
            ['id' => 2, 'name' => 'No Responsable de IVA', 'code' => '49'],
        ];

        foreach ($regimes as $reg) {
            \App\Models\TypeRegime::updateOrCreate(['id' => $reg['id']], $reg);
        }
    }
}
