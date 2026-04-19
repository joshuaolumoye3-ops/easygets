<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DiagnoseAutoExpire extends Command
{
    protected $signature = 'activations:diagnose';
    protected $description = 'Comprehensive diagnostics for auto-expiration system issues';

    public function handle()
    {
        $this->line('╔════════════════════════════════════════════════════════════════════╗');
        $this->line('║   AUTO-EXPIRATION SYSTEM - COMPREHENSIVE DIAGNOSTICS               ║');
        $this->line('╚════════════════════════════════════════════════════════════════════╝');
        
        // 1. Check database connectivity
        $this->checkDatabaseConnection();
        
        // 2. Check scheduler configuration
        $this->checkSchedulerConfig();
        
        // 3. Check cache/locking system
        $this->checkCacheSystem();
        
        // 4. Check environment variables
        $this->checkEnvironmentVariables();
        
        // 5. Check database schema
        $this->checkDatabaseSchema();
        
        // 6. Check pending activations
        $this->checkPendingActivations();
        
        // 7. Check recent refunds
        $this->checkRecentRefunds();
        
        // 8. Check logs for errors
        $this->checkRecentLogs();
        
        // 9. Create test activation if needed
        if ($this->confirm('Create a test pending activation for debugging?')) {
            $this->createTestActivation();
        }
    }

    private function checkDatabaseConnection()
    {
        $this->line("\n<fg=cyan>▶ DATABASE CONNECTION</>\n");
        try {
            $result = DB::select('SELECT 1 as connected');
            $this->info('✓ Database connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ Database connection FAILED: ' . $e->getMessage());
        }
    }

    private function checkSchedulerConfig()
    {
        $this->line("\n<fg=cyan>▶ SCHEDULER CONFIGURATION</>\n");
        $this->line('Expected configuration in app/Console/Kernel.php:');
        $this->comment("
  \$schedule->command('activations:process-expired', ['--limit=50', '--timeout=5'])
    ->everyThreeMinutes()
    ->withoutOverlapping();
        ");
        
        $this->info('✓ Check that Kernel.php contains the above schedule entry');
        $this->info('✓ Crontab should have: * * * * * cd /var/www/laravel && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1');
    }

    private function checkCacheSystem()
    {
        $this->line("\n<fg=cyan>▶ CACHE/LOCKING SYSTEM</>\n");
        
        try {
            // Test cache put/get
            Cache::put('test_key', 'test_value', 60);
            $value = Cache::get('test_key');
            
            if ($value === 'test_value') {
                $this->info('✓ Cache system: OK');
            } else {
                $this->error('✗ Cache system: Value mismatch');
            }
            
            Cache::forget('test_key');
        } catch (\Exception $e) {
            $this->error('✗ Cache system error: ' . $e->getMessage());
            $this->warn('Note: Ensure CACHE_DRIVER is set in .env (redis, file, database, etc.)');
        }
    }

    private function checkEnvironmentVariables()
    {
        $this->line("\n<fg=cyan>▶ ENVIRONMENT VARIABLES</>\n");
        
        $vars = [
            'APP_ENV' => env('APP_ENV'),
            'APP_DEBUG' => env('APP_DEBUG'),
            'CACHE_DRIVER' => env('CACHE_DRIVER'),
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'FIVESIM_TOKEN' => env('FIVESIM_TOKEN') ? '✓ Set' : '✗ NOT SET',
            'SMSBUS_TOKEN' => env('SMSBUS_TOKEN') ? '✓ Set' : '✗ NOT SET',
        ];
        
        foreach ($vars as $key => $value) {
            $color = (strpos($value, '✓') !== false) ? 'green' : (strpos($value, '✗') !== false ? 'red' : 'cyan');
            $this->line("<fg={$color}>{$key}: {$value}</>");
        }
        
        if (!env('FIVESIM_TOKEN') || !env('SMSBUS_TOKEN')) {
            $this->warn("\n⚠ Provider tokens not configured - refunds will fail!");
            $this->line("Add to your .env file:");
            $this->comment("FIVESIM_TOKEN=your_token_here");
            $this->comment("SMSBUS_TOKEN=your_token_here");
        }
    }

    private function checkDatabaseSchema()
    {
        $this->line("\n<fg=cyan>▶ DATABASE SCHEMA</>\n");
        
        // Check transactions table
        $hasTxnTable = DB::getSchemaBuilder()->hasTable('transactions');
        $this->line(($hasTxnTable ? '✓' : '✗') . ' transactions table: ' . ($hasTxnTable ? 'EXISTS' : 'MISSING'));
        
        if ($hasTxnTable) {
            $columns = DB::getSchemaBuilder()->getColumnListing('transactions');
            $requiredCols = ['id', 'user_id', 'type', 'status', 'amount', 'channel', 'created_at'];
            foreach ($requiredCols as $col) {
                $this->line('  ' . (in_array($col, $columns) ? '✓' : '✗') . ' ' . $col);
            }
        }
        
        // Check wallets table
        $hasWalletTable = DB::getSchemaBuilder()->hasTable('wallets');
        $this->line(($hasWalletTable ? '✓' : '✗') . ' wallets table: ' . ($hasWalletTable ? 'EXISTS' : 'MISSING'));
        
        if (!$hasWalletTable) {
            $this->error('✗ CRITICAL: wallets table missing! Refunds cannot be processed.');
            $this->info('Create it with: php artisan make:migration create_wallets_table');
        }
    }

    private function checkPendingActivations()
    {
        $this->line("\n<fg=cyan>▶ PENDING ACTIVATIONS</>\n");
        
        $pending = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'pending')
            ->select('id', 'user_id', 'phone', 'amount', 'channel', 'created_at', 'request_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pending->isEmpty()) {
            $this->warn('No pending activations found');
            return;
        }

        $this->info("Found {$pending->count()} pending activation(s):\n");
        
        foreach ($pending as $p) {
            $age = Carbon::parse($p->created_at)->diffInMinutes(now());
            $eligible = $age >= 5;
            $status = $eligible ? '<fg=red>ELIGIBLE FOR REFUND' : '<fg=yellow>WAITING';
            
            $this->line("ID: {$p->id} | User: {$p->user_id} | Phone: {$p->phone} | Amount: ₦" . number_format($p->amount, 2) . " | Age: {$age}min | {$status}</>");
            $this->line("  Channel: {$p->channel} | Request ID: {$p->request_id}");
            $this->line("  Created: " . Carbon::parse($p->created_at)->format('Y-m-d H:i:s'));
            
            // Check if wallet exists
            $wallet = DB::table('wallets')->where('user_id', $p->user_id)->first();
            if (!$wallet) {
                $this->error("  ✗ CRITICAL: No wallet found for user {$p->user_id}!");
            } else {
                $this->line("  ✓ Wallet balance: ₦" . number_format($wallet->balance, 2));
            }
            $this->line('');
        }
    }

    private function checkRecentRefunds()
    {
        $this->line("<fg=cyan>▶ RECENT REFUNDS (Last 24 hours)</>\n");
        
        $refunds = DB::table('transactions')
            ->where('type', 'credit')
            ->where('channel', 'Refund')
            ->where('created_at', '>', now()->subHours(24))
            ->select('id', 'reference', 'user_id', 'amount', 'status', 'description', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($refunds->isEmpty()) {
            $this->warn('No refunds processed in last 24 hours');
            return;
        }

        $this->info("Found {$refunds->count()} refund(s):\n");
        
        foreach ($refunds as $r) {
            $this->line("Reference: {$r->reference} | User: {$r->user_id} | Amount: ₦" . number_format($r->amount, 2) . " | Status: {$r->status}");
            $this->line("  Description: {$r->description}");
            $this->line("  Created: " . Carbon::parse($r->created_at)->format('Y-m-d H:i:s'));
            $this->line('');
        }
    }

    private function checkRecentLogs()
    {
        $this->line("<fg=cyan>▶ RECENT LOG ENTRIES</>\n");
        
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            $this->warn('No log file found');
            return;
        }
        
        try {
            // Use tail-like approach to avoid memory issues with large files
            $handle = fopen($logFile, 'r');
            fseek($handle, -1, SEEK_END);
            $lines = [];
            $count = 0;
            
            while (ftell($handle) > 0 && $count < 20) {
                fseek($handle, -1, SEEK_CUR);
                if (fgetc($handle) === "\n") {
                    $line = fgets($handle);
                    if (strpos($line, 'ProcessExpiredActivations') !== false || 
                        strpos($line, 'Auto-refund') !== false ||
                        strpos($line, 'process-expired') !== false) {
                        $this->line(trim($line));
                        $count++;
                    }
                }
            }
            fclose($handle);
            
            if ($count === 0) {
                $this->warn('No auto-expiration logs found');
                $this->info('The scheduler may not be running. Check:');
                $this->line('1. SSH into server and run: crontab -l');
                $this->line('2. Verify cron entry exists: * * * * * cd /var/www/laravel && php artisan schedule:run');
                $this->line('3. Check system is running cron service: systemctl status cron');
            }
        } catch (\Exception $e) {
            $this->warn('Could not read log file: ' . $e->getMessage());
        }
    }

    private function createTestActivation()
    {
        $this->line("\n<fg=cyan>▶ CREATE TEST ACTIVATION</>\n");
        
        // Get first user or use ID 1
        $user = DB::table('users')->first();
        if (!$user) {
            $this->error('No users found in database');
            return;
        }
        
        $userId = $user->id;
        
        // Ensure wallet exists
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            DB::table('wallets')->insert([
                'user_id' => $userId,
                'balance' => 10000,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info("✓ Created wallet for user {$userId}");
        }
        
        // Create test activation (6 minutes old - eligible for refund)
        $testActivation = [
            'reference' => 'TEST_' . strtoupper(uniqid()),
            'user_id' => $userId,
            'type' => 'activation',
            'channel' => 'provider1',
            'provider' => 'provider1',
            'service_name' => 'Diagnostic Test',
            'phone' => '+2348000000000',
            'amount' => 500,
            'status' => 'pending',
            'request_id' => 'test_' . strtoupper(uniqid()),
            'created_at' => now()->subMinutes(6),
            'updated_at' => now()->subMinutes(6)
        ];
        
        $id = DB::table('transactions')->insertGetId($testActivation);
        
        $this->info("\n✓ Test activation created:");
        $this->line("  ID: {$id}");
        $this->line("  User: {$userId}");
        $this->line("  Phone: {$testActivation['phone']}");
        $this->line("  Amount: ₦500.00");
        $this->line("  Status: pending");
        $this->line("  Age: 6 minutes (ELIGIBLE FOR REFUND)");
        
        $this->info("\nNext step: Run the auto-expiration command:");
        $this->comment("  php artisan activations:process-expired -v");
        
        $this->info("\nThen verify refund was created:");
        $this->comment("  php artisan activations:diagnose");
    }
}
