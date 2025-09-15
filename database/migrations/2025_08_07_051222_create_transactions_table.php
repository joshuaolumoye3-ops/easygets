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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('reference', 100)->unique(); // e.g. airtime_64h2x2s90v
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // In case it's tied to a user

            $table->string('type'); // 'airtime', 'data', 'payment', 'otp', etc.
            $table->string('channel')->nullable(); // e.g. 'ClubKonnect', 'Paystack', 'Flutterwave', etc.

            $table->string('provider')->nullable(); // e.g. MTN, Glo, 9mobile
            $table->string('phone')->nullable(); // For airtime/data
            $table->string('recipient')->nullable(); // Email or phone

            $table->decimal('amount', 15, 2)->default(0.00); // Money-related
            $table->string('status')->default('pending'); // 'pending', 'success', 'failed'

            $table->text('response')->nullable(); // JSON response or error message
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
