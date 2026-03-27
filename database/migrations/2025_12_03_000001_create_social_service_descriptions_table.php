<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('social_service_descriptions')) {
            Schema::create('social_service_descriptions', function (Blueprint $table) {
                $table->id();
                $table->string('service_id')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_service_descriptions');
    }
};

