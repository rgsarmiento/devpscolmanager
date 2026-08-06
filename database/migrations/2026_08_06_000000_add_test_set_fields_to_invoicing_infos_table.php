<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->string('test_set_id')->nullable();
            $table->unsignedBigInteger('test_set_consecutive')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoicing_infos', function (Blueprint $table) {
            $table->dropColumn(['test_set_id', 'test_set_consecutive']);
        });
    }
};
