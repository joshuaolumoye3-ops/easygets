<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dedicated_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();

            $table->string('provider')->default('korapay');

            // Our reference sent to Korapay when creating the account.
            $table->string('account_reference')->unique();

            // Korapay's own internal identifier for the account (returned as unique_id).
            $table->string('provider_unique_id')->nullable();

            $table->string('account_number');
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('currency', 8)->default('NGN');
            $table->string('status')->default('active'); // active | suspended | deleted

            // SECURITY: never store the full BVN/NIN. Only the last 4 digits, for
            // support/reference purposes. The full number is sent to Korapay over
            // HTTPS at creation time and is never persisted or logged in full.
            $table->string('bvn_last4', 4)->nullable();
            $table->string('nin_last4', 4)->nullable();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dedicated_accounts');
    }
};
