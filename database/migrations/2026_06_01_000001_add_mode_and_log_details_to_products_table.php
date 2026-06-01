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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'mode')) {
                $table->string('mode', 16)->default('manual')->after('client_price');
            }
            if (!Schema::hasColumn('products', 'log_details')) {
                $table->text('log_details')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'log_details')) {
                $table->dropColumn('log_details');
            }
            if (Schema::hasColumn('products', 'mode')) {
                $table->dropColumn('mode');
            }
        });
    }
};
