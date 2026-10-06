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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku', 64);

            $table->string(
                'sat_product_code',
                8
            );

            $table->text('description');

            $table->string(
                'unit_code',
                3
            );

            $table->decimal(
                'unit_price',
                18,
                6
            );

            $table->string(
                'tax_object',
                2
            );

            $table->decimal(
                'default_tax_rate',
                9,
                6
            )->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->timestamps();

            $table->softDeletes();

            $table->unique([
                'company_id',
                'sku',
            ]);

            $table->index([
                'company_id',
                'status',
            ]);

            $table->index([
                'company_id',
                'sat_product_code',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
