<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class VerifyServicesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all services are properly configured and active';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Verifying Services Configuration...\n');

        try {
            // Check if services table exists
            if (!DB::table('services')->exists()) {
                $this->error('❌ Services table does not exist. Run: php artisan migrate');
                return 1;
            }

            // Get all services
            $services = Service::all();

            if ($services->isEmpty()) {
                $this->warn('⚠️  No services found in database. Running seeder...\n');
                $this->call('db:seed', ['--class' => 'ServiceSeeder']);
                $services = Service::all();
            }

            // Display services
            $this->info('✅ Services in Database:');
            $this->line('');

            $table = [];
            foreach ($services as $service) {
                $status = $service->is_active ? '🟢 Active' : '🔴 Inactive';
                $table[] = [
                    $service->id,
                    $service->name,
                    $service->slug,
                    $status,
                    $service->created_at->format('Y-m-d H:i')
                ];
            }

            $this->table(
                ['ID', 'Name', 'Slug', 'Status', 'Created'],
                $table
            );

            // Count statistics
            $totalServices = $services->count();
            $activeServices = $services->where('is_active', true)->count();
            $inactiveServices = $services->where('is_active', false)->count();

            $this->line('');
            $this->info("📊 Summary:");
            $this->line("  Total Services: {$totalServices}");
            $this->line("  🟢 Active: {$activeServices}");
            $this->line("  🔴 Inactive: {$inactiveServices}");

            // Check if all required services exist
            $requiredServices = [
                'data', 'airtime', 'electricity', 'cabletv', 
                'betting', 'esim', 'social-boost', 'otp'
            ];

            $missingServices = [];
            foreach ($requiredServices as $slug) {
                if (!$services->where('slug', $slug)->first()) {
                    $missingServices[] = $slug;
                }
            }

            if (!empty($missingServices)) {
                $this->error('\n❌ Missing Services:');
                foreach ($missingServices as $slug) {
                    $this->line("  - {$slug}");
                }
                $this->warn('\nRun this to fix: php artisan db:seed --class=ServiceSeeder');
                return 1;
            }

            $this->info('\n✅ All required services are present and properly configured!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
