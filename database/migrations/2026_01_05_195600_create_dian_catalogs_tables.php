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
        // 1. Type Document Identifications
        Schema::create('type_document_identifications', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Manual ID, no auto-increment
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        // 2. Type Liabilities (Responsabilidades)
        Schema::create('type_liabilities', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        // 3. Type Organizations (Juridica / Natural)
        Schema::create('type_organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        // 4. Type Regimes
        Schema::create('type_regimes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });

        // 5. Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->string('country_id')->nullable(); // Based on image showing country_id
            $table->timestamps();
        });

        // 6. Municipalities
        Schema::create('municipalities', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->unsignedBigInteger('department_id');
            // Foreign key constraint optional but recommended
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('type_regimes');
        Schema::dropIfExists('type_organizations');
        Schema::dropIfExists('type_liabilities');
        Schema::dropIfExists('type_document_identifications');
    }
};
