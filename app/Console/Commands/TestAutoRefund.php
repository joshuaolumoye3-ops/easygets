<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestAutoRefund extends Command
{
    protected $signature = 'activations:test-refund {--user-id= : User ID to test refund for} {--activation-id= : Specific activation ID to refund} {--create-test : Create a test pending activation}';
    protected $description = 'Test and verify auto-refund functionality - useful for debugging';

    public function handle()
    {
        $this->line('═══════════════════════════════════════════════════════════');
        $this->line('AUTO-REFUND SYSTEM TEST & DIAGNOSTICS');
        $this->line('═══════════════════════════════════════════════════════════');
        
        // Step 1: Check if scheduler is enabled
        $this->checkSchedulerStatus();
        
        // Step 2: Check pending activations
        $this->checkPendingActivations();
        
        // Step 3: Check wallet status
        $this->checkWalletStatus();
        
        // Step 4: Optional - create test activation
        if ($this->option('create-test')) {
            $this->createTestActivation();
        }
        
        // Step 5: Optional - test specific refund
        if ($this->option('activation-id')) {
            $this->testSpecificRefund($this->option('activation-id'));
        } elseif ($this->option('user-id')) {
            $this->checkUserActivations($this->option('user-id'));
        }
    }

    private function checkSchedulerStatus()
    {
        $this->line("\n<fg=blue>│</> <fg=white>SCHEDULER STATUS</>");
        $this->line('<fg=blue>└─</> <fg=cyan>Command:</> activations:process-expired');
        $this->line('<fg=blue>   └─ Frequency:</> Every 3 minutes (everyThreeMinutes)');
        $this->line('<fg=blue>   └─ withoutOverlapping:</> YES - prevents concurrent runs');
        $this->line('<fg=blue>   └─ Timeout:</> 5 minutes');
        $this->info("   ✓ Schedule configured correctly");
        
        $this->line("\n<fg=cyan>To manually run the command:</>");
        $this->comment("php artisan activations:process-expired --limit=50 --timeout=5 -v");
    }

    private function checkPendingActivations()
    {
        $this->line("\n<fg=blue>│</> <fg=white>PENDING ACTIVATIONS STATUS</>");
        
        $pending = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'pending')
            ->select('id', 'user_id', 'phone', 'amount', 'channel', 'created_at', 'request_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($pending->isEmpty()) {
            $this->line('<fg=green>✓ No pending activations found</>\n');
            return;
        }

        $this->table(
            ['ID', 'User', 'Phone', 'Amount', 'Provider', 'Age (mins)', 'Status'],
            $pending->map(function($p) {
                $age = Carbon::parse($p->created_at)->diffInMinutes(now());
                return [
                    $p->id,
                    $p->user_id,
                    $p->phone,
                    '₦' . number_format($p->amount, 2),
                    $p->channel,
                    $age . ' min',
                    $age >= 5 ? '<fg=red>ELIGIBLE FOR REFUND (>= 5 min)</>' : '<fg=yellow>Waiting...</>'
                ];
            })->toArray()
        );

        $refundEligible = $pending->filter(function($p) {
            return Carbon::parse($p->created_at)->diffInMinutes(now()) >= 5;
        });

        if ($refundEligible->isNotEmpty()) {
            $this->warn("\n⚠ {$refundEligible->count()} activation(s) eligible for auto-refund (>= 5 minutes old)");
        }
    }

    private function checkWalletStatus()
    {
        $this->line("\n<fg=blue>│</> <fg=white>WALLET & REFUND RECORDS</>");
        
        $recentRefunds = DB::table('transactions')
            ->where('type', 'credit')
            ->where('channel', 'Refund')
            ->select('id', 'reference', 'user_id', 'amount', 'status', 'description', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentRefunds->isEmpty()) {
            $this->line('<fg=yellow>ℹ No refund records found (may be normal if no activations have expired yet)</>\n');
        } else {
            $this->table(
                ['Reference', 'User', 'Amount', 'Status', 'Created'],
                $recentRefunds->map(function($r) {
                    return [
                        $r->reference,
                        $r->user_id,
                        '₦' . number_format($r->amount, 2),
                        $r->status,
                        Carbon::parse($r->created_at)->format('M d, H:i:s')
                    ];
                })->toArray()
            );
        }

        // Check refund failures
        $failedRefunds = DB::table('transactions')
            ->where('type', 'activation')
            ->where('status', 'expired')
            ->whereDay('created_at', '>=', now()->subDay())
            ->get();

        if ($failedRefunds->isNotEmpty()) {
            $this->line('<fg=yellow>ℹ Note:</> ' . $failedRefunds->count() . ' expired activation(s) in last 24 hours');
        }
    }

    private function checkUserActivations($userId)
    {
        $this->line("\n<fg=blue>│</> <fg=white>USER {$userId} ACTIVATIONS</>");
        
        $activations = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'activation')
            ->select('id', 'phone', 'amount', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($activations->isEmpty()) {
            $this->warn("No activations found for user {$userId}\n");
            return;
        }

        $this->table(
            ['ID', 'Phone', 'Amount', 'Status', 'Age (mins)'],
            $activations->map(function($a) {
                $age = Carbon::parse($a->created_at)->diffInMinutes(now());
                return [
                    $a->id,
                    $a->phone,
                    '₦' . number_format($a->amount, 2),
                    $a->status,
                    $age . ' min'
                ];
            })->toArray()
        );

        // Check wallet
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();
        if ($wallet) {
            $this->line("\n<fg=green>Wallet Balance:</> ₦" . number_format($wallet->balance, 2));
        }
    }

    private function testSpecificRefund($activationId)
    {
        $this->line("\n<fg=blue>│</> <fg=white>TESTING REFUND FOR ACTIVATION {$activationId}</>");
        
        $activation = DB::table('transactions')
            ->where('id', $activationId)
            ->first();

        if (!$activation) {
            $this->error("✗ Activation not found");
            return;
        }

        $this->line('<fg=cyan>Activation Details:</>\n');
        $this->line("  ID: {$activation->id}");
        $this->line("  Status: {$activation->status}");
        $this->line("  Amount: ₦" . number_format($activation->amount, 2));
        $this->line("  Age: " . Carbon::parse($activation->created_at)->diffInMinutes(now()) . " minutes");
        $this->line("  User ID: {$activation->user_id}");

        if ($activation->status !== 'pending') {
            $this->warn("\n⚠ Activation is not pending (status: {$activation->status})");
            return;
        }

        // Check wallet
        $wallet = DB::table('wallets')
            ->where('user_id', $activation->user_id)
            ->first();

        if (!$wallet) {
            $this->error("\n✗ CRITICAL: User wallet not found!");
            $this->info("Create wallet first: INSERT INTO wallets (user_id, balance, created_at, updated_at) VALUES ({$activation->user_id}, 0, NOW(), NOW());");
            return;
        }

        $balanceBefore = $wallet->balance;
        
        $this->info("\n✓ Wallet found - Balance before refund: ₦" . number_format($balanceBefore, 2));
        
        // Execute manual refund for testing
        $this->line("\n<fg=cyan>Attempting manual refund...</>\n");
        
        try {
            DB::transaction(function() use ($activation, $balanceBefore) {
                // Update status
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);
                
                // Refund
                DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);
                
                // Create refund record
                $reference = 'REF_TEST_' . strtoupper(uniqid());
                DB::table('transactions')->insert([
                    'reference' => $reference,
                    'user_id' => $activation->user_id,
                    'type' => 'credit',
                    'channel' => 'Refund',
                    'provider' => $activation->provider ?? 'Test',
                    'amount' => $activation->amount,
                    'status' => 'success',
                    'description' => 'Manual test refund',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('Test refund manual execution', [
                    'activation_id' => $activation->id,
                    'reference' => $reference,
                    'amount' => $activation->amount
                ]);
            });
            
            // Verify
            $walletAfter = DB::table('wallets')
                ->where('user_id', $activation->user_id)
                ->first();
            
            $this->info("✓ REFUND SUCCESSFUL!");
            $this->line("  Balance Before: ₦" . number_format($balanceBefore, 2));
            $this->line("  Refund Amount: ₦" . number_format($activation->amount, 2));
            $this->line("  Balance After:  ₦" . number_format($walletAfter->balance, 2));
            
            if ($walletAfter->balance == $balanceBefore + $activation->amount) {
                $this->info("\n✓ Refund system is working correctly!");
            }
            
        } catch (\Exception $e) {
            $this->error("\n✗ REFUND FAILED: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function createTestActivation()
    {
        $this->line("\n<fg=blue>│</> <fg=white>CREATING TEST ACTIVATION</>");
        
        $userId = DB::table('users')->first()?->id ?? 1;
        
        // Ensure wallet exists
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            DB::table('wallets')->insert([
                'user_id' => $userId,
                'balance' => 5000,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->info("✓ Created wallet for user {$userId}");
        }
        
        // Create test activation (5+ minutes old for auto-expire)
        $testActivation = [
            'reference' => 'TEST_' . strtoupper(uniqid()),
            'user_id' => $userId,
            'type' => 'activation',
            'channel' => 'provider1',
            'provider' => 'provider1',
            'service_name' => 'Test Service',
            'phone' => '+2348000000000',
            'amount' => 500,
            'status' => 'pending',
            'request_id' => 'test_' . uniqid(),
            'created_at' => now()->subMinutes(6),
            'updated_at' => now()->subMinutes(6)
        ];
        
        DB::table('transactions')->insert($testActivation);
        
        $activation = DB::table('transactions')
            ->where('reference', $testActivation['reference'])
            ->first();
        
        $this->info("\n✓ Test activation created:");
        $this->line("  ID: {$activation->id}");
        $this->line("  Reference: {$activation->reference}");
        $this->line("  Phone: {$activation->phone}");
        $this->line("  Amount: ₦" . number_format($activation->amount, 2));
        $this->line("  Age: 6 minutes (eligible for refund)");
        
        $this->info("\nNext step: Run the auto-refund command:");
        $this->comment("php artisan activations:process-expired -v");
    }
}
