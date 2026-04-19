<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessExpiredActivations extends Command
{
    protected $signature = 'activations:process-expired {--limit=50 : Maximum number of activations to process per run} {--timeout=5 : Minutes threshold for auto-expiration}';
    protected $description = 'Auto-cancel and refund pending activations after 5 minutes without SMS received - SECURE & RATE LIMITED';

    private $rateLimit = 5; // Processing delay in seconds between API calls
    private $maxRetriesPerActivation = 2;
    private $lockTimeout = 300; // 5 minutes lock timeout

    public function handle()
    {
        $limit = (int)$this->option('limit');
        $timeoutMinutes = (int)$this->option('timeout');
        
        // Security: Prevent concurrent execution with distributed lock
        $lockKey = 'activations:process-expired-lock';
        if (Cache::has($lockKey)) {
            $this->warn('Previous process still running. Skipping...');
            Log::warning('ProcessExpiredActivations: Locked execution prevented');
            return 0;
        }
        
        Cache::put($lockKey, true, $this->lockTimeout);
        
        try {
            $this->info("🔄 Starting auto-expiration process (limit: $limit, timeout: {$timeoutMinutes}min)");
            
            // Find pending activations that are older than the timeout threshold
            $expirationThreshold = Carbon::now()->subMinutes($timeoutMinutes);
            $pendingActivations = DB::table('transactions')
                ->where('type', 'activation')
                ->where('status', 'pending')
                ->whereNotNull('request_id')
                ->where('created_at', '<', $expirationThreshold)
                ->whereNotIn('channel', ['Refund', 'admin']) // Exclude non-provider transactions
                ->take($limit)
                ->lockForUpdate() // Pessimistic locking to prevent race conditions
                ->get();

            $this->info("Found " . count($pendingActivations) . " pending activations to process");
            
            $processed = 0;
            $refunded = 0;
            $failed = 0;

            foreach ($pendingActivations as $activation) {
                try {
                    // Rate limiting: delay between API calls
                    usleep($this->rateLimit * 1000000);
                    
                    $result = $this->processActivation($activation);
                    
                    if ($result['success']) {
                        $processed++;
                        if ($result['refunded']) {
                            $refunded++;
                        }
                    } else {
                        $failed++;
                    }
                    
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Error processing activation', [
                        'activation_id' => $activation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->error("✗ Error processing activation {$activation->id}: {$e->getMessage()}");
                }
            }
            
            $this->info("✓ Process completed: $processed processed, $refunded refunded, $failed failed");
            Log::info('ProcessExpiredActivations completed', [
                'total' => count($pendingActivations),
                'processed' => $processed,
                'refunded' => $refunded,
                'failed' => $failed
            ]);
            
            return 0;
            
        } finally {
            // Always release the lock
            Cache::forget($lockKey);
        }
    }

    /**
     * Process a single activation
     * 1. Check API status
     * 2. If expired on API, mark as expired & refund
     * 3. If still pending, attempt cancel & refund
     */
    private function processActivation($activation): array
    {
        try {
            // Normalize provider name
            $provider = $this->normalizeProvider($activation->channel);
            
            // Check API status first
            $apiStatus = $this->checkActivationStatus($activation, $provider);
            
            $this->info("Processing {$provider} activation ID: {$activation->id}, request_id: {$activation->request_id}");
            
            if (!$apiStatus['valid']) {
                // Number already expired/released on API
                $this->info("  → API indicates number is no longer available (expired/released)");
                $refundResult = $this->refundActivation(
                    $activation,
                    'expired',
                    "Auto-expired - Number no longer available from provider after {$this->option('timeout')} minutes"
                );
                
                if ($refundResult['refunded']) {
                    $this->info("  ✓ REFUND SUCCESS - Reference: {$refundResult['reference']} | Amount: ₦" . number_format($activation->amount, 2));
                } else {
                    $this->error("  ✗ REFUND FAILED - Manual check required");
                }
                
                return $refundResult;
            }
            
            // Number still active on API - attempt to cancel
            $this->info("  → Attempting to cancel active number...");
            $cancelResult = $this->attemptCancel($activation, $provider);
            
            if ($cancelResult['success']) {
                // Cancellation successful - refund the user
                $this->info("  → Cancellation confirmed, processing refund");
                $refundResult = $this->refundActivation(
                    $activation,
                    'expired',
                    "Auto-cancelled and refunded - No SMS received after {$this->option('timeout')} minutes"
                );
                
                if ($refundResult['refunded']) {
                    $this->info("  ✓ REFUND SUCCESS - Reference: {$refundResult['reference']} | Amount: ₦" . number_format($activation->amount, 2));
                } else {
                    $this->error("  ✗ REFUND FAILED - Manual check required");
                }
                
                return $refundResult;
            } else {
                // Cancellation failed - log but don't refund yet
                Log::warning('Failed to auto-cancel activation', [
                    'activation_id' => $activation->id,
                    'provider' => $provider,
                    'reason' => $cancelResult['reason'] ?? 'Unknown'
                ]);
                $this->warn("  ✗ Cancellation failed: {$cancelResult['reason']}");
                return ['success' => false, 'refunded' => false];
            }
            
        } catch (\Exception $e) {
            Log::error('Exception in processActivation', [
                'activation_id' => $activation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check current status of activation from provider API
     * Returns: ['valid' => bool, 'status' => string]
     */
    private function checkActivationStatus($activation, $provider): array
    {
        try {
            if ($provider === 'provider1') {
                return $this->checkProvider1Status($activation);
            } else {
                return $this->checkProvider2Status($activation);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check API status', [
                'provider' => $provider,
                'activation_id' => $activation->id,
                'error' => $e->getMessage()
            ]);
            // On API error, assume still valid to prevent false refunds
            return ['valid' => true, 'status' => 'unknown'];
        }
    }

    /**
     * Check Provider1 (5sim) status
     */
    private function checkProvider1Status($activation): array
    {
        $token = env('FIVESIM_TOKEN');
        if (!$token) {
            throw new \Exception('Provider1 token not configured');
        }

        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->withOptions(['verify' => false])
            ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

        if (!$response->successful()) {
            Log::warning('Provider1 status check failed', [
                'request_id' => $activation->request_id,
                'status_code' => $response->status()
            ]);
            return ['valid' => true, 'status' => 'unknown'];
        }

        $data = $response->json();
        $apiStatus = $data['status'] ?? 'UNKNOWN';

        // If SMS already received, don't auto-expire
        if (isset($data['sms']) && is_array($data['sms']) && count($data['sms']) > 0) {
            if (isset($data['sms'][0]['code']) && !empty($data['sms'][0]['code'])) {
                return ['valid' => true, 'status' => $apiStatus];
            }
        }

        // Check if number is still usable
        $stillValid = !in_array($apiStatus, ['CANCELED', 'TIMEOUT', 'FINISHED']);
        
        return ['valid' => $stillValid, 'status' => $apiStatus];
    }

    /**
     * Check Provider2 (SMS-Bus) status
     */
    private function checkProvider2Status($activation): array
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            throw new \Exception('Provider2 token not configured');
        }

        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/get/sms', [
                'token' => $token,
                'request_id' => $activation->request_id
            ]);

        if (!$response->successful()) {
            Log::warning('Provider2 status check failed', [
                'request_id' => $activation->request_id,
                'status_code' => $response->status()
            ]);
            return ['valid' => true, 'status' => 'unknown'];
        }

        $data = $response->json();

        // Code 200 with data = SMS received
        if (isset($data['code']) && $data['code'] === 200 && isset($data['data']) && !empty($data['data'])) {
            return ['valid' => true, 'status' => 'sms_received'];
        }

        // Code 50101 = Not received yet (still valid)
        if (isset($data['code']) && $data['code'] === 50101) {
            return ['valid' => true, 'status' => 'pending'];
        }

        // Code 50102 = Number expired/released
        if (isset($data['code']) && $data['code'] === 50102) {
            return ['valid' => false, 'status' => 'expired'];
        }

        // Unknown status - assume still valid
        return ['valid' => true, 'status' => 'unknown'];
    }

    /**
     * Attempt to cancel activation at provider
     */
    private function attemptCancel($activation, $provider): array
    {
        try {
            if ($provider === 'provider1') {
                return $this->cancelProvider1($activation);
            } else {
                return $this->cancelProvider2($activation);
            }
        } catch (\Exception $e) {
            Log::error('Cancel attempt exception', [
                'provider' => $provider,
                'activation_id' => $activation->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Cancel Provider1 (5sim) activation
     */
    private function cancelProvider1($activation): array
    {
        $token = env('FIVESIM_TOKEN');
        
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->withOptions(['verify' => false])
            ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");

        if ($response->successful()) {
            Log::info('Provider1 auto-cancel successful', ['request_id' => $activation->request_id]);
            return ['success' => true];
        }

        $data = $response->json();
        Log::warning('Provider1 auto-cancel failed', [
            'request_id' => $activation->request_id,
            'response' => $data
        ]);
        
        return ['success' => false, 'reason' => $data['message'] ?? 'API cancel failed'];
    }

    /**
     * Cancel Provider2 (SMS-Bus) activation
     */
    private function cancelProvider2($activation): array
    {
        $token = env('SMSBUS_TOKEN');

        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/cancel', [
                'token' => $token,
                'request_id' => $activation->request_id
            ]);

        if (!$response->successful()) {
            Log::warning('Provider2 auto-cancel HTTP failed', [
                'request_id' => $activation->request_id,
                'status' => $response->status()
            ]);
            return ['success' => false, 'reason' => "HTTP {$response->status()}"];
        }

        $data = $response->json();

        if (isset($data['code']) && $data['code'] === 200) {
            Log::info('Provider2 auto-cancel successful', ['request_id' => $activation->request_id]);
            return ['success' => true];
        }

        if (isset($data['code']) && $data['code'] === 50103) {
            // Already closed/completed
            Log::info('Provider2 request already closed', ['request_id' => $activation->request_id]);
            return ['success' => true];
        }

        Log::warning('Provider2 auto-cancel failed', [
            'request_id' => $activation->request_id,
            'code' => $data['code'] ?? null,
            'message' => $data['message'] ?? null
        ]);
        
        return ['success' => false, 'reason' => $data['message'] ?? 'API cancel failed'];
    }

    /**
     * Refund activation and mark as expired
     */
    /**
     * Refund activation with complete transaction safety and comprehensive logging
     * Professional-grade refund processing with wallet validation and audit trails
     */
    private function refundActivation($activation, $status, $description): array
    {
        try {
            $refunded = false;
            $statusUpdated = false;
            $refundReference = null;
            
            DB::transaction(function() use ($activation, $status, $description, &$refunded, &$statusUpdated, &$refundReference) {
                // Step 1: Update activation status to mark it as processed
                $statusUpdate = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => $status,
                        'updated_at' => now()
                    ]);
                
                if (!$statusUpdate) {
                    throw new \Exception("Failed to update activation status for ID: {$activation->id}");
                }
                $statusUpdated = true;
                
                Log::info('Activation status updated', [
                    'activation_id' => $activation->id,
                    'status_set_to' => $status
                ]);

                // Step 2: Validate and lock wallet for refund
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();
                    
                if (!$wallet) {
                    Log::error('Wallet not found - cannot process refund', [
                        'activation_id' => $activation->id,
                        'user_id' => $activation->user_id,
                        'amount' => $activation->amount
                    ]);
                    throw new \Exception("Wallet not found for user_id: {$activation->user_id}");
                }

                $walletBalanceBefore = $wallet->balance;
                
                // Step 3: Increment wallet balance
                $updated = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);
                
                if (!$updated) {
                    throw new \Exception("Failed to increment wallet balance for user_id: {$activation->user_id}");
                }

                // Get updated wallet balance for logging
                $walletAfter = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->first();
                $walletBalanceAfter = $walletAfter->balance ?? 0;

                Log::info('Wallet balance incremented', [
                    'user_id' => $activation->user_id,
                    'balance_before' => $walletBalanceBefore,
                    'refund_amount' => $activation->amount,
                    'balance_after' => $walletBalanceAfter
                ]);

                // Step 4: Create refund transaction record with complete audit trail
                $refundReference = 'REF_' . strtoupper(Str::random(10));
                
                $refundInserted = DB::table('transactions')->insert([
                    'reference' => $refundReference,
                    'user_id' => $activation->user_id,
                    'type' => 'credit',
                    'channel' => 'Refund',
                    'provider' => $activation->service_name ?? $activation->provider ?? 'Auto-Refund',
                    'amount' => $activation->amount,
                    'status' => 'success',
                    'description' => $description,
                    'balance_after' => $walletBalanceAfter,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if (!$refundInserted) {
                    throw new \Exception("Failed to create refund transaction record");
                }
                
                $refunded = true;
                
                Log::info('✓ Auto-refund completed successfully', [
                    'activation_id' => $activation->id,
                    'user_id' => $activation->user_id,
                    'amount' => $activation->amount,
                    'refund_reference' => $refundReference,
                    'status_set_to' => $status,
                    'reason' => $description,
                    'wallet_balance_before' => $walletBalanceBefore,
                    'wallet_balance_after' => $walletBalanceAfter
                ]);
            });

            return ['success' => true, 'refunded' => $refunded, 'reference' => $refundReference];
            
        } catch (\Exception $e) {
            Log::critical('✗ Auto-refund FAILED - requires manual investigation', [
                'activation_id' => $activation->id,
                'user_id' => $activation->user_id,
                'amount' => $activation->amount,
                'error' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'refunded' => false, 'reference' => null];
        }
    }

    /**
     * Normalize provider name
     */
    private function normalizeProvider($channel): string
    {
        $provider = strtolower($channel ?? 'provider2');
        
        if ($provider === 'sms-bus' || $provider === 'smsbus') {
            return 'provider2';
        } elseif ($provider === '5sim') {
            return 'provider1';
        }
        
        return $provider;
    }
}
