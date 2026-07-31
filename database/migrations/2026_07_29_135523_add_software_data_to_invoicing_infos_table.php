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
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->string('software_identifier')->nullable();
            $table->string('software_pin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->dropColumn(['software_identifier', 'software_pin']);
        });
    }
};
