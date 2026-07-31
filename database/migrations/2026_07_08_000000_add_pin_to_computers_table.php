<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Si la columna ya existe porque falló a medias, la eliminamos primero
        if (Schema::hasColumn('computers', 'pin')) {
            Schema::table('computers', function (Blueprint $table) {
                $table->dropColumn('pin');
            });
        }

        Schema::table('computers', function (Blueprint $table) {
            $table->text('pin')->nullable()->after('name');
        });

        // Copiar los PINs antiguos desde observation a pin (usando el texto completo)
        DB::statement('UPDATE computers SET pin = observation WHERE observation IS NOT NULL AND observation != ""');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
