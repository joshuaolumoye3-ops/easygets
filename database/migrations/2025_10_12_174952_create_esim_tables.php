<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('esim_countries', function (Blueprint $table) {
            $table->id();
            $table->string('country_name');
            $table->string('country_code', 5)->unique();
            $table->decimal('base_price', 10, 2)->default(0); // admin set price
            $table->string('flag_url')->nullable();
            $table->timestamps();
        });

        Schema::create('esim_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esim_country_id')->constrained('esim_countries')->onDelete('cascade');
            $table->string('plan'); // e.g. 1GB, 5GB
            $table->integer('validity_days'); // e.g. 7, 30
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_packages');
        Schema::dropIfExists('esim_countries');
    }
};

