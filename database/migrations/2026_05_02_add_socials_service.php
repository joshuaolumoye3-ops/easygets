<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Service;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add socials service if it doesn't exist
        Service::firstOrCreate(
            ['slug' => 'socials'],
            ['name' => 'Socials', 'is_active' => false]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Service::where('slug', 'socials')->delete();
    }
};
