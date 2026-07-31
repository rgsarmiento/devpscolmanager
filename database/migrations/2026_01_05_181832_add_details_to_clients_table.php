<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('dv', 1)->nullable()->after('nit');
            $table->string('merchant_registration')->nullable()->after('address');
            $table->string('municipality_id')->nullable()->after('merchant_registration');
            $table->string('type_document_identification_id')->nullable()->after('municipality_id'); // 3 for NIT
            $table->string('type_organization_id')->nullable()->after('type_document_identification_id');
            $table->string('type_liability_id')->nullable()->after('type_organization_id');
            $table->string('type_regime_id')->nullable()->after('type_liability_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'dv', 
                'merchant_registration', 
                'municipality_id', 
                'type_document_identification_id',
                'type_organization_id',
                'type_liability_id',
                'type_regime_id'
            ]);
        });
    }
};
