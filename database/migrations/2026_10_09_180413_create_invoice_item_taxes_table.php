<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'invoice_item_taxes',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'invoice_item_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'tax_code',
                    3
                );

                $table->string(
                    'tax_type',
                    20
                );

                $table->string(
                    'factor_type',
                    20
                );

                $table->decimal(
                    'base',
                    18,
                    6
                );

                $table->decimal(
                    'rate_or_quota',
                    18,
                    6
                );

                $table->decimal(
                    'amount',
                    18,
                    6
                );

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'invoice_item_taxes'
        );
    }
};
