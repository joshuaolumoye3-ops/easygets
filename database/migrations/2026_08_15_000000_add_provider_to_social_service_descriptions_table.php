<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('social_service_descriptions', function (Blueprint $table) {
            $table->string('provider', 32)->default('morethanpanel')->after('service_id');
        });

        // Old data was implicitly MoreThanPanel-only — nothing else to backfill.

        Schema::table('social_service_descriptions', function (Blueprint $table) {
            try {
                $table->dropUnique(['service_id']); // adjust index name if this fails
            } catch (\Throwable $e) {
                // index name may differ — check `SHOW INDEX FROM social_service_descriptions`
            }
            $table->unique(['provider', 'service_id'], 'social_service_provider_unique');
        });
    }

    public function down()
    {
        Schema::table('social_service_descriptions', function (Blueprint $table) {
            $table->dropUnique('social_service_provider_unique');
            $table->dropColumn('provider');
        });
    }
};