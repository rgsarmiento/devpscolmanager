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
            $table->dateTime('certificate_expiration_date')->nullable()->after('plan_start_date');
            $table->string('certificate_password')->nullable()->after('certificate_expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->dropColumn(['certificate_expiration_date', 'certificate_password']);
        });
    }
};
