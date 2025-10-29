<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_accounts', 'status')) {
                $table->string('status')->default('pending');
            }
            if (!Schema::hasColumn('virtual_accounts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('virtual_accounts', 'amount')) {
                $table->decimal('amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('virtual_accounts', 'account_reference')) {
                $table->string('account_reference')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn(['status', 'expires_at', 'amount', 'account_reference']);
        });
    }
};
