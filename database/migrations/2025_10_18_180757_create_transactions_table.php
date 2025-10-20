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
        Schema::table('transactions', function (Blueprint $table) {
            // Activation-specific columns
            $table->string('request_id')->nullable()->after('reference'); // Provider's request/order ID
            $table->string('service_id')->nullable()->after('provider'); // Service code/id
            $table->string('service_name')->nullable()->after('service_id');
            $table->string('service_logo')->nullable()->after('service_name');
            $table->string('country_id')->nullable()->after('service_logo'); // SMS-Bus country ID
            $table->string('country_name')->nullable()->after('country_id');
            $table->string('country_code', 2)->nullable()->after('country_name');
            $table->string('operator_name')->nullable()->after('country_code'); // For 5sim
            $table->string('sms_code')->nullable()->after('phone'); // OTP/verification code
            $table->decimal('original_cost_usd', 10, 2)->nullable()->after('amount');
            $table->timestamp('expires_at')->nullable()->after('status'); // For activations
            $table->text('description')->nullable()->after('response'); // Transaction description
            
            // Add index for faster activation queries
            $table->index(['user_id', 'type', 'created_at']);
            $table->index('request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type', 'created_at']);
            $table->dropIndex(['request_id']);
            
            $table->dropColumn([
                'request_id',
                'service_id',
                'service_name',
                'service_logo',
                'country_id',
                'country_name',
                'country_code',
                'operator_name',
                'sms_code',
                'original_cost_usd',
                'expires_at',
                'description'
            ]);
        });
    }
};