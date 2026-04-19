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
     * Refund activation with redundant safety checks and professional-grade error handling
     * This method is designed for maximum reliability and auditability
     */
    private function refundActivation($activation, $status, $description): array
    {
        $operationId = uniqid('OP_');
        $refundReference = null;
        $refunded = false;
        
        try {
            Log::info('=== REFUND OPERATION START ===', [
                'operation_id' => $operationId,
                'activation_id' => $activation->id,
                'user_id' => $activation->user_id,
                'amount' => $activation->amount,
                'status' => $status
            ]);
            
            // ============================================================================
            // REFUND OPERATION - ATOMIC, IDEMPOTENT, AUDITABLE
            // ============================================================================
            $result = DB::transaction(function() use ($activation, $status, $description, $operationId, &$refundReference) {
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 1: CHECK IF ALREADY REFUNDED (Idempotency check)
                // ────────────────────────────────────────────────────────────────────────
                $existingRefund = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)
                    ->where('amount', $activation->amount)
                    ->where('created_at', '>=', Carbon::now()->subMinutes(5)) // Within last 5 minutes
                    ->first();
                
                if ($existingRefund) {
                    Log::warning('Duplicate refund detected - skipping', [
                        'operation_id' => $operationId,
                        'activation_id' => $activation->id,
                        'existing_refund_reference' => $existingRefund->reference,
                        'existing_refund_id' => $existingRefund->id
                    ]);
                    return [
                        'success' => true,
                        'refunded' => true,
                        'reference' => $existingRefund->reference,
                        'duplicate' => true
                    ];
                }
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 2: VERIFY ACTIVATION STATUS (Double-check it's still pending)
                // ────────────────────────────────────────────────────────────────────────
                $currentActivation = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->lockForUpdate()
                    ->first();
                
                if (!$currentActivation) {
                    throw new \Exception("Activation ID {$activation->id} not found in database");
                }
                
                if ($currentActivation->status !== 'pending') {
                    Log::warning('Activation status changed - refund not needed', [
                        'operation_id' => $operationId,
                        'activation_id' => $activation->id,
                        'current_status' => $currentActivation->status,
                        'expected_status' => 'pending'
                    ]);
                    return [
                        'success' => true,
                        'refunded' => false,
                        'reference' => null,
                        'reason' => 'Status changed to ' . $currentActivation->status
                    ];
                }
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 3: UPDATE ACTIVATION STATUS
                // ────────────────────────────────────────────────────────────────────────
                $statusUpdate = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => $status,
                        'updated_at' => now()
                    ]);
                
                if ($statusUpdate !== 1) {
                    throw new \Exception("Status update failed for activation {$activation->id}");
                }
                
                Log::info('Activation status updated to ' . $status, [
                    'operation_id' => $operationId,
                    'activation_id' => $activation->id
                ]);
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 4: VALIDATE WALLET EXISTS AND LOCK IT
                // ────────────────────────────────────────────────────────────────────────
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();
                
                if (!$wallet) {
                    throw new \Exception("Wallet not found for user_id: {$activation->user_id}");
                }
                
                $walletBalanceBefore = (float)$wallet->balance;
                
                Log::info('Wallet locked and validated', [
                    'operation_id' => $operationId,
                    'user_id' => $activation->user_id,
                    'wallet_id' => $wallet->id,
                    'balance_before' => $walletBalanceBefore
                ]);
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 5: INCREMENT WALLET BALANCE
                // ────────────────────────────────────────────────────────────────────────
                $incrementResult = DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'balance' => DB::raw('balance + ' . $activation->amount),
                        'updated_at' => now()
                    ]);
                
                if ($incrementResult !== 1) {
                    throw new \Exception("Failed to increment wallet balance");
                }
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 6: VERIFY BALANCE UPDATED (Safety check)
                // ────────────────────────────────────────────────────────────────────────
                $updatedWallet = DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->first();
                
                $walletBalanceAfter = (float)$updatedWallet->balance;
                $expectedBalance = $walletBalanceBefore + $activation->amount;
                
                if (abs($walletBalanceAfter - $expectedBalance) > 0.01) {
                    throw new \Exception("Wallet balance verification failed: expected {$expectedBalance}, got {$walletBalanceAfter}");
                }
                
                Log::info('Wallet balance incremented and verified', [
                    'operation_id' => $operationId,
                    'user_id' => $activation->user_id,
                    'balance_before' => $walletBalanceBefore,
                    'refund_amount' => $activation->amount,
                    'balance_after' => $walletBalanceAfter
                ]);
                
                // ────────────────────────────────────────────────────────────────────────
                // STEP 7: CREATE REFUND TRANSACTION RECORD
                // ────────────────────────────────────────────────────────────────────────
                $refundReference = 'REF_' . strtoupper(Str::random(10));
                
                $refundInserted = DB::table('transactions')->insert([
                    'reference' => $refundReference,
                    'user_id' => $activation->user_id,
                    'type' => 'credit',
                    'channel' => 'Refund',
                    'provider' => $activation->service_name ?? $activation->provider ?? 'System',
                    'phone' => $activation->phone ?? null,
                    'recipient' => null,
                    'amount' => $activation->amount,
                    'status' => 'success',
                    'description' => $description,
                    'response' => json_encode([
                        'operation_id' => $operationId,
                        'original_activation_id' => $activation->id,
                        'reason' => 'Auto-expiration refund'
                    ]),
                    'balance_after' => $walletBalanceAfter,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                if (!$refundInserted) {
                    throw new \Exception("Failed to create refund transaction record");
                }
                
                Log::info('Refund transaction record created', [
                    'operation_id' => $operationId,
                    'refund_reference' => $refundReference,
                    'user_id' => $activation->user_id
                ]);
                
                return [
                    'success' => true,
                    'refunded' => true,
                    'reference' => $refundReference
                ];
                
            }, attempts: 3, timeout: 30);
            
            // ════════════════════════════════════════════════════════════════════════
            // POST-TRANSACTION LOGGING
            // ════════════════════════════════════════════════════════════════════════
            if ($result['refunded'] ?? false) {
                Log::info('✓✓✓ AUTO-REFUND COMPLETED SUCCESSFULLY ✓✓✓', [
                    'operation_id' => $operationId,
                    'activation_id' => $activation->id,
                    'user_id' => $activation->user_id,
                    'amount' => $activation->amount,
                    'refund_reference' => $result['reference'],
                    'status_set_to' => $status,
                    'description' => $description,
                    'timestamp' => now()->toIso8601String()
                ]);
            } elseif ($result['duplicate'] ?? false) {
                Log::notice('Refund previously processed (idempotency)', [
                    'operation_id' => $operationId,
                    'activation_id' => $activation->id,
                    'refund_reference' => $result['reference']
                ]);
            }
            
            return [
                'success' => true,
                'refunded' => $result['refunded'] ?? false,
                'reference' => $result['reference'] ?? null
            ];
            
        } catch (\Throwable $e) {
            // ════════════════════════════════════════════════════════════════════════
            // CRITICAL ERROR HANDLING
            // ════════════════════════════════════════════════════════════════════════
            Log::critical('✗✗✗ AUTO-REFUND FAILED - MANUAL INTERVENTION REQUIRED ✗✗✗', [
                'operation_id' => $operationId,
                'activation_id' => $activation->id,
                'user_id' => $activation->user_id,
                'amount' => $activation->amount,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'timestamp' => now()->toIso8601String(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            // Alert admins if configured
            $this->alertAdminOnRefundFailure($activation, $e, $operationId);
            
            return [
                'success' => false,
                'refunded' => false,
                'reference' => null,
                'error' => $e->getMessage(),
                'operation_id' => $operationId
            ];
        }
    }
    
    /**
     * Alert administrators when a refund fails
     * Can be extended to send email, Slack, PagerDuty, etc.
     */
    private function alertAdminOnRefundFailure($activation, \Throwable $e, $operationId)
    {
        try {
            // Log to a special "alerts" channel
            $alert = [
                'type' => 'REFUND_FAILURE',
                'operation_id' => $operationId,
                'activation_id' => $activation->id,
                'user_id' => $activation->user_id,
                'amount' => '₦' . number_format($activation->amount, 2),
                'error' => $e->getMessage(),
                'severity' => 'HIGH',
                'action_required' => 'Manual refund processing',
                'timestamp' => now()->toIso8601String()
            ];
            
            // Write to alerts log
            Log::channel('alerts')->error('Refund Failure Alert', $alert);
            
            // Future: Add webhook calls, email notifications, etc.
            // Example: Slack::send(...), Mail::send(...), etc.
            
        } catch (\Exception $alertError) {
            Log::error('Failed to send admin alert', [
                'error' => $alertError->getMessage()
            ]);
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
