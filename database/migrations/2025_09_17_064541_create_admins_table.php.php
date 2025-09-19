<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin'])->default('admin');
            $table->string('phone', 20)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('admin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->string('action'); // e.g. 'credit', 'debit', 'add_charge', 'update_user_password', 'update_user_pin'
            $table->decimal('amount', 12, 2)->nullable(); // for financial transactions
            $table->unsignedBigInteger('affected_user_id')->nullable(); // if transaction involves a user
            $table->text('description')->nullable(); // extra details
            $table->string('reference', 100)->unique()->nullable(); // optional reference
            $table->timestamps();

            $table->foreign('affected_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_transactions');
        Schema::dropIfExists('admins');
    }
};
