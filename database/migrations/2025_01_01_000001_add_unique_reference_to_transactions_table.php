<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This is the single most important fix for the "credited multiple times" bug.
     *
     * Application-level "check if it exists, then insert" logic is NOT safe against
     * concurrent requests (two webhook retries, or a webhook + a browser callback,
     * arriving milliseconds apart). Only a database-level unique constraint guarantees
     * that the exact same transaction reference can never be inserted twice, no matter
     * how many processes race to do it.
     *
     * IMPORTANT: Before running this migration, check for existing duplicate references:
     *
     *   SELECT reference, COUNT(*) c FROM transactions GROUP BY reference HAVING c > 1;
     *
     * and resolve them (this migration will fail on existing duplicate data).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('reference', 'transactions_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_reference_unique');
        });
    }
};
