<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->unsignedInteger(
                'line_number'
            );

            /*
            |--------------------------------------------------------------------------
            | Product snapshot
            |--------------------------------------------------------------------------
            */

            $table->string(
                'sat_product_code',
                8
            );

            $table->string(
                'unit_code',
                3
            );

            $table->text('description');

            $table->string(
                'tax_object',
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Quantities / amounts
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'quantity',
                18,
                6
            );

            $table->decimal(
                'unit_price',
                18,
                6
            );

            $table->decimal(
                'subtotal',
                18,
                6
            );

            $table->decimal(
                'discount',
                18,
                6
            )->default(0);

            $table->decimal(
                'taxable_base',
                18,
                6
            );

            $table->decimal(
                'transferred_taxes',
                18,
                6
            )->default(0);

            $table->decimal(
                'withheld_taxes',
                18,
                6
            )->default(0);

            $table->decimal(
                'total',
                18,
                6
            );

            $table->timestamps();

            $table->unique([
                'invoice_id',
                'line_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
