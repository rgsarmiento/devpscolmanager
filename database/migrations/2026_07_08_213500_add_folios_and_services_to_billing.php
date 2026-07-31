<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter license_transactions
        // MySQL doesn't support altering enums directly easily with Blueprint in older versions, 
        // so we can change it to a string column or just use DB::statement for the enum.
        // It's safer to change to string.
        Schema::table('license_transactions', function (Blueprint $table) {
            $table->string('type')->change(); // From enum to string
            $table->integer('folios_count')->nullable()->after('type');
            $table->string('service_name')->nullable()->after('folios_count');
            
            // Computer_id can be null if it's a folio or service transaction
            $table->foreignId('computer_id')->nullable()->change();
        });

        // Folio Rates
        Schema::create('folio_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('min_folios');
            $table->integer('max_folios')->nullable(); // null = unlimited
            $table->decimal('price', 12, 2); // price per folio, or total price if unlimited
            $table->timestamps();
        });

        // Service Rates
        Schema::create('service_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('annual_price', 12, 2);
            $table->timestamps();
        });

        // Client Services (Suscriptions)
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('expiration_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('service_rates');
        Schema::dropIfExists('folio_rates');

        Schema::table('license_transactions', function (Blueprint $table) {
            $table->dropColumn('folios_count');
            $table->dropColumn('service_name');
        });
    }
};
