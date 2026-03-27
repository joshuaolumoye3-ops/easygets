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
        Schema::table('esim_countries', function (Blueprint $table) {
            if (!Schema::hasColumn('esim_countries', 'local_number_available')) {
                $table->boolean('local_number_available')->default(false)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('esim_countries', function (Blueprint $table) {
            if (Schema::hasColumn('esim_countries', 'local_number_available')) {
                $table->dropColumn('local_number_available');
            }
        });
    }
};
