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
        Schema::create(
            'company_fiscal_profiles',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_id')
                    ->unique()
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('rfc', 13)
                    ->index();

                $table->string('legal_name');

                $table->string(
                    'tax_regime',
                    3
                );

                $table->string(
                    'postal_code',
                    5
                );

                $table->string('email')
                    ->nullable();

                $table->string(
                    'status',
                    20
                )->default('active');

                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_fiscal_profiles');
    }
};
