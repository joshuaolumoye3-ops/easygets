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
        Schema::create('esim_uploads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->onDelete('cascade');

            $table->string('number')->nullable()->index();
            $table->text('details')->nullable();
            $table->json('images')->nullable(); // store multiple images in JSON

            $table->timestamps();

            // Extra indexes for performance
            $table->index(['user_id', 'transaction_id'], 'esim_user_txn_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esim_uploads');
    }
};
