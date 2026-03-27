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
        Schema::table('social_service_descriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('social_service_descriptions', 'admin_percentage')) {
                $table->unsignedInteger('admin_percentage')->default(0)->after('custom_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_service_descriptions', function (Blueprint $table) {
            if (Schema::hasColumn('social_service_descriptions', 'admin_percentage')) {
                $table->dropColumn('admin_percentage');
            }
        });
    }
};