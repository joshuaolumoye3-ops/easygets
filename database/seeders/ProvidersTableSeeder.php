<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Provider;

class ProvidersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch services
        $dataService        = Service::where('slug', 'data')->first();
        $electricityService = Service::where('slug', 'electricity')->first();
        $cableService       = Service::where('slug', 'cable')->first();
        $esimService  = Service::where('slug', 'esim')->first();

        if ($dataService) {
            Provider::updateOrCreate(
                ['api_identifier' => 'demmyglobal'],
                [
                    'service_id'     => $dataService->id,
                    'name'           => 'Demmy Global',
                    'api_identifier' => 'demmyglobal'
                ]
            );
        }

        if ($electricityService) {
            Provider::updateOrCreate(
                ['api_identifier' => 'clubkonnect-electricity'],
                [
                    'service_id'     => $electricityService->id,
                    'name'           => 'Clubkonnect Electricity',
                    'api_identifier' => 'clubkonnect-electricity'
                ]
            );
        }

        if ($cableService) {
            Provider::updateOrCreate(
                ['api_identifier' => 'clubkonnect-cable'],
                [
                    'service_id'     => $cableService->id,
                    'name'           => 'Clubkonnect Cable TV',
                    'api_identifier' => 'clubkonnect-cable'
                ]
            );
        }

        if ($esimService) {
            Provider::updateOrCreate(
                ['api_identifier' => 'admin-esim'],
                [
                    'service_id'     => $esimService->id,
                    'name'           => 'Admin eSIM',
                    'api_identifier' => 'Admin-esim',
                    'percentage'     => 0,
                    'status'         => 'active',
                ]
            );
        }
    }
}
