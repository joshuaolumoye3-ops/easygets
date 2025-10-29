<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('virtual_accounts', function (Blueprint $table) {
            $table->dropColumn(['status', 'expires_at', 'amount', 'customer_email', 'customer_name', 'bank_name', 'bank_code']);
        });
    }
};
