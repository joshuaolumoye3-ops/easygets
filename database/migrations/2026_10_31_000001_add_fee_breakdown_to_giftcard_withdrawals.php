<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('giftcard_withdrawals')) {
            return;
        }

        Schema::table('giftcard_withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('giftcard_withdrawals', 'fee_amount')) {
                $table->decimal('fee_amount', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('giftcard_withdrawals', 'payout_amount')) {
                $table->decimal('payout_amount', 12, 2)->nullable()->after('fee_amount');
            }
        });

        DB::table('giftcard_withdrawals')
            ->whereNull('payout_amount')
            ->update(['payout_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('giftcard_withdrawals')) {
            return;
        }

        Schema::table('giftcard_withdrawals', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('giftcard_withdrawals', 'payout_amount')) {
                $columns[] = 'payout_amount';
            }
            if (Schema::hasColumn('giftcard_withdrawals', 'fee_amount')) {
                $columns[] = 'fee_amount';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
