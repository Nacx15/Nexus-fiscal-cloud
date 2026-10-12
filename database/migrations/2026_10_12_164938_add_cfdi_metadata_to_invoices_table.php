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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId(
                'fiscal_certificate_id'
            )
                ->nullable()
                ->after('fiscal_sequence_id')
                ->constrained(
                    'fiscal_certificates'
                )
                ->restrictOnDelete();

            $table->string(
                'cfdi_version',
                5
            )
                ->default('4.0')
                ->after('internal_folio');

            $table->string(
                'export_code',
                2
            )
                ->default('01')
                ->after('cfdi_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign([
                'fiscal_certificate_id',
            ]);

            $table->dropColumn([
                'fiscal_certificate_id',
                'cfdi_version',
                'export_code',
            ]);
        });
    }
};
