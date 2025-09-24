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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->string('product_code'); // API product code
            $table->string('product_sno')->nullable(); // API serial number
            $table->string('product_id')->nullable();  // API product id
            $table->string('name'); // e.g. MTN 1GB Daily
            $table->decimal('api_price', 10, 2);
            $table->decimal('client_price', 10, 2)->nullable(); // editable price
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider_id', 'product_code']); // prevent duplicates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
