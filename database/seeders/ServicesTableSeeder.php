<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $services = [
        // ['name' => 'Data', 'slug' => 'data'],
        // ['name' => 'Electricity', 'slug' => 'electricity'],
        // ['name' => 'Cable TV', 'slug' => 'cable'],
        // ['name' => 'ESIM', 'slug' => 'esim'],
        // ['name' => 'Socials', 'slug' => 'socials'],
        // ['name' => 'Airtime', 'slug' => 'airtime'],
        ['name' => 'Otp', 'slug' => 'otp'],
    ];

    foreach ($services as $service) {
        \App\Models\Service::create($service);
    }
}

}
