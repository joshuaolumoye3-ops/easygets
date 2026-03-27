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
            if (!Schema::hasColumn('social_service_descriptions', 'custom_name')) {
                $table->string('custom_name')->nullable()->after('service_id');
            }
            if (!Schema::hasColumn('social_service_descriptions', 'custom_category')) {
                $table->string('custom_category')->nullable()->after('custom_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_service_descriptions', function (Blueprint $table) {
            if (Schema::hasColumn('social_service_descriptions', 'custom_category')) {
                $table->dropColumn('custom_category');
            }
            if (Schema::hasColumn('social_service_descriptions', 'custom_name')) {
                $table->dropColumn('custom_name');
            }
        });
    }
};
