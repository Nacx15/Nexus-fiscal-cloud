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
        Schema::table(
            'invoices',
            function (Blueprint $table) {
                $table->unsignedInteger(
                    'stamp_attempts'
                )->default(0);

                $table->timestamp(
                    'last_stamp_attempt_at'
                )->nullable();

                $table->string(
                    'stamp_error_code',
                    100
                )->nullable();

                $table->text(
                    'stamp_error_message'
                )->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });Schema::table(
            'invoices',
            function (Blueprint $table) {
                $table->dropColumn([
                    'stamp_attempts',
                    'last_stamp_attempt_at',
                    'stamp_error_code',
                    'stamp_error_message',
                ]);
            }
        );
    }
};
