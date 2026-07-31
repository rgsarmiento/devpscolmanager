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
        Schema::create('invoicing_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            // Facturación Config
            $table->integer('folios_remaining')->default(0);
            $table->integer('folios_total')->default(0);
            $table->date('certificate_expiration')->nullable();
            $table->date('resolution_expiration')->nullable();
            // API Connection Details (maybe strictly for the backend to consume)
            $table->string('api_token')->nullable();
            // Usage stats
            $table->boolean('has_web_app')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoicing_infos');
    }
};
