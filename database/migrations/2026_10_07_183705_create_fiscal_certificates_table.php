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
        Schema::create('fiscal_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_fiscal_profile_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('certificate_number', 64)
                ->nullable();

            $table->string('certificate_path');

            $table->string('private_key_path');

            $table->text('private_key_password');

            $table->string('fingerprint_sha256', 64)
                ->nullable();

            $table->timestamp('valid_from')
                ->nullable();

            $table->timestamp('valid_until')
                ->nullable();

            $table->string('status', 20)
                ->default('inactive');

            $table->timestamps();

            $table->index([
                'company_id',
                'status',
            ]);

            $table->unique([
                'company_id',
                'certificate_number',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_certificates');
    }
};
