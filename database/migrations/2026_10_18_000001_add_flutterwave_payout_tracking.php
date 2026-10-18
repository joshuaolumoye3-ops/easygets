<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('giftcard_withdrawals')) {
            Schema::table('giftcard_withdrawals', function (Blueprint $table) {
                if (!Schema::hasColumn('giftcard_withdrawals', 'provider')) {
                    $table->string('provider', 32)->nullable();
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'bank_code')) {
                    $table->string('bank_code', 12)->nullable();
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'client_token')) {
                    $table->string('client_token', 64)->nullable();
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'provider_transfer_id')) {
                    $table->string('provider_transfer_id', 64)->nullable();
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'failure_reason')) {
                    $table->text('failure_reason')->nullable();
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'payout_attempts')) {
                    $table->unsignedInteger('payout_attempts')->default(0);
                }
                if (!Schema::hasColumn('giftcard_withdrawals', 'payout_alerted_at')) {
                    $table->timestamp('payout_alerted_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('flutterwave_webhook_events')) {
            Schema::create('flutterwave_webhook_events', function (Blueprint $table) {
                $table->string('event_key', 120)->primary();
                $table->string('event', 60);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flutterwave_webhook_events');

        if (Schema::hasTable('giftcard_withdrawals')) {
            Schema::table('giftcard_withdrawals', function (Blueprint $table) {
                foreach ([
                    'provider', 'bank_code', 'client_token', 'provider_transfer_id', 'failure_reason',
                    'payout_attempts', 'payout_alerted_at',
                ] as $column) {
                    if (Schema::hasColumn('giftcard_withdrawals', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};