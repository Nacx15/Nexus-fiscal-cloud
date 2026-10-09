<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('fiscal_sequence_id')
                ->constrained('fiscal_sequences')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Folio
            |--------------------------------------------------------------------------
            */

            $table->string('series', 25);

            $table->unsignedBigInteger('folio');

            $table->string(
                'internal_folio',
                64
            );

            /*
            |--------------------------------------------------------------------------
            | Fiscal stamping data
            |--------------------------------------------------------------------------
            |
            | Quedará NULL hasta el futuro timbrado.
            |
            */

            $table->string('uuid', 36)
                ->nullable()
                ->unique();

            $table->timestamp('issued_at')
                ->nullable();

            $table->timestamp('stamped_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Issuer snapshot
            |--------------------------------------------------------------------------
            */

            $table->string('issuer_rfc', 13);

            $table->string('issuer_name');

            $table->string(
                'issuer_tax_regime',
                3
            );

            $table->string(
                'issuer_postal_code',
                5
            );

            /*
            |--------------------------------------------------------------------------
            | Receiver snapshot
            |--------------------------------------------------------------------------
            */

            $table->string('receiver_rfc', 13);

            $table->string('receiver_name');

            $table->string(
                'receiver_tax_regime',
                3
            );

            $table->string(
                'receiver_postal_code',
                5
            );

            $table->string('receiver_email')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | CFDI configuration
            |--------------------------------------------------------------------------
            */

            $table->string('payment_form', 2);

            $table->string(
                'payment_method',
                3
            );

            $table->string('cfdi_use', 3);

            $table->string('currency', 3)
                ->default('MXN');

            $table->decimal(
                'exchange_rate',
                18,
                6
            )->default(1);

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'subtotal',
                18,
                6
            )->default(0);

            $table->decimal(
                'discount',
                18,
                6
            )->default(0);

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
            )->default(0);

            $table->string(
                'status',
                30
            )->default('ready');

            $table->timestamps();

            $table->unique([
                'company_id',
                'series',
                'folio',
            ]);

            $table->unique([
                'company_id',
                'internal_folio',
            ]);

            $table->index([
                'company_id',
                'status',
            ]);

            $table->index([
                'company_id',
                'client_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
