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
            if (!Schema::hasColumn('products', 'country')) {
                $table->string('country')->nullable()->after('category');
            }
            if (!Schema::hasColumn('products', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('country');
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('client_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('products', 'country_code')) {
                $table->dropColumn('country_code');
            }
            if (Schema::hasColumn('products', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
