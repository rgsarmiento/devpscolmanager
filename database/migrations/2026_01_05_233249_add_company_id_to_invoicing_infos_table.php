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
            $table->string('company_id')->nullable()->after('client_id'); // External ID might be string or large int
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
