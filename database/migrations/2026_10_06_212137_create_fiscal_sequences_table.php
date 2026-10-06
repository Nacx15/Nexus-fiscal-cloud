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
        Schema::create('fiscal_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string(
                'document_type',
                30
            );

            $table->string(
                'series',
                25
            );

            $table->unsignedBigInteger(
                'next_number'
            )->default(1);

            $table->string(
                'status',
                20
            )->default('active');

            $table->timestamps();

            $table->unique(
                [
                    'company_id',
                    'document_type',
                    'series',
                ],
                'fiscal_sequences_company_document_series_unique'
            );

            $table->index([
                'company_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_sequences');
    }
};
