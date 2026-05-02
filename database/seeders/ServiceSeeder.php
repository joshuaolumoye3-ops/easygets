<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all services with their slugs
        $services = [
            [
                'name' => 'Data',
                'slug' => 'data',
                'is_active' => true,
            ],
            [
                'name' => 'Airtime',
                'slug' => 'airtime',
                'is_active' => true,
            ],
            [
                'name' => 'Electricity',
                'slug' => 'electricity',
                'is_active' => true,
            ],
            [
                'name' => 'Cable TV',
                'slug' => 'cabletv',
                'is_active' => true,
            ],
            [
                'name' => 'Betting',
                'slug' => 'betting',
                'is_active' => true,
            ],
            [
                'name' => 'eSIM',
                'slug' => 'esim',
                'is_active' => true,
            ],
            [
                'name' => 'Social Boost',
                'slug' => 'social-boost',
                'is_active' => true,
            ],
            [
                'name' => 'OTP',
                'slug' => 'otp',
                'is_active' => true,
            ],
        ];

        // Create or update services
        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']], // Match by slug
                $service // Update/create with all data
            );
        }

        echo "✓ All services have been ensured in the database.\n";
    }
}
