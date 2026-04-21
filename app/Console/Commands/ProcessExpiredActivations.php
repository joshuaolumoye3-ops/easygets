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
    protected $signature = 'activations:process-expired
                            {--limit=50 : Maximum number of activations to process per run}
                            {--timeout=3 : Minutes threshold for auto-expiration (default: 3)}';

    protected $description = 'Auto-cancel and refund pending activations after 3 minutes without SMS received';

    private $rateLimit      = 2;   // seconds between API calls (reduced for faster processing)
    private $lockTimeout    = 180; // 3 minutes lock timeout

    public function handle()
    {
        $limit          = (int) $this->option('limit');
        $timeoutMinutes = (int) $this->option('timeout');

        // Prevent concurrent execution with a distributed lock
        $lockKey = 'activations:process-expired-lock';
        if (Cache::has($lockKey)) {
            $this->warn('Previous process still running. Skipping...');
            Log::warning('ProcessExpiredActivations: Locked execution prevented');
            return 0;
        }

        Cache::put($lockKey, true, $this->lockTimeout);

        try {
            $this->info("🔄 Starting auto-expiration (limit: {$limit}, timeout: {$timeoutMinutes}min)");

            $expirationThreshold = Carbon::now()->subMinutes($timeoutMinutes);

            // Only process truly pending activations older than the timeout.
            // Exclude already-refunded/completed/expired rows and non-provider channels.
            $pendingActivations = DB::table('transactions')
                ->where('type', 'activation')
                ->where('status', 'pending')
                ->whereNotNull('request_id')
                ->where('created_at', '<', $expirationThreshold)
                ->whereNotIn('channel', ['Refund', 'admin'])
                ->take($limit)
                ->get();

            $this->info('Found ' . count($pendingActivations) . ' pending activations to process');

            $processed = 0;
            $refunded  = 0;
            $failed    = 0;

            foreach ($pendingActivations as $activation) {
                // Rate-limiting between API calls
                usleep($this->rateLimit * 1_000_000);

                try {
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
                        'error'         => $e->getMessage(),
                        'trace'         => $e->getTraceAsString(),
                    ]);
                    $this->error("✗ Error processing activation {$activation->id}: {$e->getMessage()}");
                }
            }

            $this->info("✓ Completed: {$processed} processed, {$refunded} refunded, {$failed} failed");

            Log::info('ProcessExpiredActivations completed', [
                'total'     => count($pendingActivations),
                'processed' => $processed,
                'refunded'  => $refunded,
                'failed'    => $failed,
            ]);

            return 0;

        } finally {
            Cache::forget($lockKey);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CORE PROCESSING LOGIC
    // ──────────────────────────────────────────────────────────────────────────

    private function processActivation($activation): array
    {
        $provider = $this->normalizeProvider($activation->channel);

        $this->info("Processing [{$provider}] activation ID: {$activation->id}, request_id: {$activation->request_id}");

        // 1. Check current API status (also catches SMS that already arrived)
        $apiStatus = $this->checkActivationStatus($activation, $provider);

        // 2. If an SMS code was already received on the provider side, mark completed — no refund
        if ($apiStatus['sms_received'] ?? false) {
            $this->info("  → SMS already received on provider side — marking completed, no refund needed");
            DB::table('transactions')
                ->where('id', $activation->id)
                ->where('status', 'pending') // guard
                ->update(['status' => 'completed', 'updated_at' => now()]);
            return ['success' => true, 'refunded' => false];
        }

        // 3. Number already expired/cancelled on provider side — refund immediately
        if (!$apiStatus['valid']) {
            $this->info("  → Provider reports number no longer active (expired/cancelled)");
            return $this->refundActivation(
                $activation,
                'expired',
                "Auto-expired: number no longer available from provider after {$this->option('timeout')} minutes"
            );
        }

        // 4. Number still active — attempt to cancel at provider, then refund
        $this->info("  → Number still active; attempting provider cancel...");
        $cancelResult = $this->attemptCancel($activation, $provider);

        if ($cancelResult['success']) {
            $this->info("  → Cancellation confirmed; processing refund");
            return $this->refundActivation(
                $activation,
                'expired',
                "Auto-cancelled & refunded: no SMS received after {$this->option('timeout')} minutes"
            );
        }

        // Cancel failed — do NOT refund yet; leave for next run or manual review
        Log::warning('Failed to auto-cancel activation', [
            'activation_id' => $activation->id,
            'provider'      => $provider,
            'reason'        => $cancelResult['reason'] ?? 'Unknown',
        ]);
        $this->warn("  ✗ Cancellation failed: " . ($cancelResult['reason'] ?? 'Unknown'));
        return ['success' => false, 'refunded' => false];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // API STATUS CHECKS
    // ──────────────────────────────────────────────────────────────────────────

    private function checkActivationStatus($activation, $provider): array
    {
        try {
            return $provider === 'provider1'
                ? $this->checkProvider1Status($activation)
                : $this->checkProvider2Status($activation);
        } catch (\Exception $e) {
            Log::error('Failed to check API status', [
                'provider'      => $provider,
                'activation_id' => $activation->id,
                'error'         => $e->getMessage(),
            ]);
            // On API error, assume still valid to prevent false refunds
            return ['valid' => true, 'status' => 'unknown', 'sms_received' => false];
        }
    }

    /** Check 5sim status */
    private function checkProvider1Status($activation): array
    {
        $token = env('FIVESIM_TOKEN');
        if (!$token) {
            throw new \Exception('Provider1 (5sim) token not configured');
        }

        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
            ->withOptions(['verify' => false])
            ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

        if (!$response->successful()) {
            Log::warning('Provider1 status check failed', [
                'request_id'  => $activation->request_id,
                'status_code' => $response->status(),
            ]);
            return ['valid' => true, 'status' => 'unknown', 'sms_received' => false];
        }

        $data      = $response->json();
        $apiStatus = $data['status'] ?? 'UNKNOWN';

        // If SMS code already arrived, save it and flag as received
        if (!empty($data['sms']) && is_array($data['sms'])) {
            foreach ($data['sms'] as $sms) {
                if (!empty($sms['code'])) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->where('status', 'pending')
                        ->update(['sms_code' => $sms['code'], 'status' => 'completed', 'updated_at' => now()]);
                    return ['valid' => true, 'status' => $apiStatus, 'sms_received' => true];
                }
            }
        }

        $stillValid = !in_array($apiStatus, ['CANCELED', 'TIMEOUT', 'FINISHED'], true);
        return ['valid' => $stillValid, 'status' => $apiStatus, 'sms_received' => false];
    }

    /** Check SMS-Bus status */
    private function checkProvider2Status($activation): array
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            throw new \Exception('Provider2 (SMS-Bus) token not configured');
        }

        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/get/sms', [
                'token'      => $token,
                'request_id' => $activation->request_id,
            ]);

        if (!$response->successful()) {
            Log::warning('Provider2 status check failed', [
                'request_id'  => $activation->request_id,
                'status_code' => $response->status(),
            ]);
            return ['valid' => true, 'status' => 'unknown', 'sms_received' => false];
        }

        $data = $response->json();
        $code = $data['code'] ?? null;

        // SMS received
        if ($code === 200 && !empty($data['data'])) {
            DB::table('transactions')
                ->where('id', $activation->id)
                ->where('status', 'pending')
                ->update(['sms_code' => $data['data'], 'status' => 'completed', 'updated_at' => now()]);
            return ['valid' => true, 'status' => 'sms_received', 'sms_received' => true];
        }

        // Still pending
        if ($code === 50101) {
            return ['valid' => true, 'status' => 'pending', 'sms_received' => false];
        }

        // Number expired/released
        if ($code === 50102) {
            return ['valid' => false, 'status' => 'expired', 'sms_received' => false];
        }

        return ['valid' => true, 'status' => 'unknown', 'sms_received' => false];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PROVIDER CANCEL
    // ──────────────────────────────────────────────────────────────────────────

    private function attemptCancel($activation, $provider): array
    {
        try {
            return $provider === 'provider1'
                ? $this->cancelProvider1($activation)
                : $this->cancelProvider2($activation);
        } catch (\Exception $e) {
            Log::error('Cancel attempt exception', [
                'provider'      => $provider,
                'activation_id' => $activation->id,
                'error'         => $e->getMessage(),
            ]);
            return ['success' => false, 'reason' => $e->getMessage()];
        }
    }

    private function cancelProvider1($activation): array
    {
        $token    = env('FIVESIM_TOKEN');
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
            'response'   => $data,
        ]);
        return ['success' => false, 'reason' => $data['message'] ?? 'API cancel failed'];
    }

    private function cancelProvider2($activation): array
    {
        $token    = env('SMSBUS_TOKEN');
        $response = Http::timeout(15)
            ->withOptions(['verify' => false])
            ->get('https://sms-bus.com/api/control/cancel', [
                'token'      => $token,
                'request_id' => $activation->request_id,
            ]);

        if (!$response->successful()) {
            Log::warning('Provider2 auto-cancel HTTP failed', [
                'request_id' => $activation->request_id,
                'status'     => $response->status(),
            ]);
            return ['success' => false, 'reason' => "HTTP {$response->status()}"];
        }

        $data = $response->json();
        $code = $data['code'] ?? null;

        if ($code === 200) {
            Log::info('Provider2 auto-cancel successful', ['request_id' => $activation->request_id]);
            return ['success' => true];
        }

        // Already closed — treat as success so we still refund
        if ($code === 50103) {
            Log::info('Provider2 request already closed', ['request_id' => $activation->request_id]);
            return ['success' => true];
        }

        Log::warning('Provider2 auto-cancel failed', [
            'request_id' => $activation->request_id,
            'code'       => $code,
            'message'    => $data['message'] ?? null,
        ]);
        return ['success' => false, 'reason' => $data['message'] ?? 'API cancel failed'];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REFUND (ATOMIC, IDEMPOTENT)
    // ──────────────────────────────────────────────────────────────────────────

    private function refundActivation($activation, $status, $description): array
    {
        $operationId = uniqid('OP_');

        Log::info('=== REFUND OPERATION START ===', [
            'operation_id'  => $operationId,
            'activation_id' => $activation->id,
            'user_id'       => $activation->user_id,
            'amount'        => $activation->amount,
            'status'        => $status,
        ]);

        try {
            $result = DB::transaction(function () use ($activation, $status, $description, $operationId) {

                // ── Idempotency: skip if a refund already exists for this activation ──
                $existingRefund = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)
                    ->where('amount', $activation->amount)
                    ->where('created_at', '>=', Carbon::now()->subMinutes(10))
                    ->whereRaw("JSON_EXTRACT(response, '$.original_activation_id') = ?", [$activation->id])
                    ->first();

                if ($existingRefund) {
                    Log::warning('Duplicate refund detected — skipping', [
                        'operation_id'             => $operationId,
                        'activation_id'            => $activation->id,
                        'existing_refund_reference' => $existingRefund->reference,
                    ]);
                    return ['success' => true, 'refunded' => true, 'reference' => $existingRefund->reference, 'duplicate' => true];
                }

                // ── Re-read with row lock to prevent race conditions ──
                $current = DB::table('transactions')
                    ->where('id', $activation->id)
                    ->lockForUpdate()
                    ->first();

                if (!$current) {
                    throw new \Exception("Activation ID {$activation->id} not found");
                }

                // Guard: only refund if still pending
                if ($current->status !== 'pending') {
                    Log::warning('Activation status changed — refund skipped', [
                        'operation_id'   => $operationId,
                        'activation_id'  => $activation->id,
                        'current_status' => $current->status,
                    ]);
                    return ['success' => true, 'refunded' => false, 'reference' => null, 'reason' => 'Status is ' . $current->status];
                }

                // Update activation status
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => $status, 'updated_at' => now()]);

                // Lock wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception("Wallet not found for user_id: {$activation->user_id}");
                }

                $balanceBefore = (float) $wallet->balance;

                // Credit wallet
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->update([
                        'balance'    => DB::raw('balance + ' . (float) $activation->amount),
                        'updated_at' => now(),
                    ]);

                // Verify balance updated correctly
                $balanceAfter = (float) DB::table('wallets')->where('id', $wallet->id)->value('balance');
                $expected     = $balanceBefore + (float) $activation->amount;

                if (abs($balanceAfter - $expected) > 0.01) {
                    throw new \Exception("Balance verification failed: expected {$expected}, got {$balanceAfter}");
                }

                // Create refund transaction record
                $reference = 'REF_' . strtoupper(Str::random(10));

                DB::table('transactions')->insert([
                    'reference'    => $reference,
                    'user_id'      => $activation->user_id,
                    'type'         => 'credit',
                    'channel'      => 'Refund',
                    'provider'     => $activation->service_name ?? $activation->provider ?? 'System',
                    'phone'        => $activation->phone ?? null,
                    'recipient'    => null,
                    'amount'       => $activation->amount,
                    'status'       => 'success',
                    'description'  => $description,
                    'response'     => json_encode([
                        'operation_id'          => $operationId,
                        'original_activation_id' => $activation->id,
                        'reason'                => 'Auto-expiration refund',
                    ]),
                    'balance_after' => $balanceAfter,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                Log::info('✓ AUTO-REFUND COMPLETED', [
                    'operation_id'    => $operationId,
                    'activation_id'   => $activation->id,
                    'user_id'         => $activation->user_id,
                    'amount'          => $activation->amount,
                    'refund_reference' => $reference,
                    'balance_before'  => $balanceBefore,
                    'balance_after'   => $balanceAfter,
                ]);

                return ['success' => true, 'refunded' => true, 'reference' => $reference];

            }, attempts: 3, timeout: 30);

            if ($result['refunded'] ?? false) {
                $this->info("  ✓ REFUND SUCCESS — Ref: {$result['reference']} | ₦" . number_format($activation->amount, 2));
            }

            return ['success' => true, 'refunded' => $result['refunded'] ?? false, 'reference' => $result['reference'] ?? null];

        } catch (\Throwable $e) {
            Log::critical('✗ AUTO-REFUND FAILED — MANUAL INTERVENTION REQUIRED', [
                'operation_id'  => $operationId,
                'activation_id' => $activation->id,
                'user_id'       => $activation->user_id,
                'amount'        => $activation->amount,
                'error'         => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'timestamp'     => now()->toIso8601String(),
            ]);

            $this->alertAdminOnRefundFailure($activation, $e, $operationId);

            return ['success' => false, 'refunded' => false, 'reference' => null, 'error' => $e->getMessage()];
        }
    }

    private function alertAdminOnRefundFailure($activation, \Throwable $e, string $operationId): void
    {
        try {
            Log::channel('alerts')->error('Refund Failure Alert', [
                'type'            => 'REFUND_FAILURE',
                'operation_id'    => $operationId,
                'activation_id'   => $activation->id,
                'user_id'         => $activation->user_id,
                'amount'          => '₦' . number_format($activation->amount, 2),
                'error'           => $e->getMessage(),
                'severity'        => 'HIGH',
                'action_required' => 'Manual refund processing',
                'timestamp'       => now()->toIso8601String(),
            ]);
        } catch (\Exception $alertError) {
            Log::error('Failed to send admin alert', ['error' => $alertError->getMessage()]);
        }
    }

    private function normalizeProvider($channel): string
    {
        $p = strtolower($channel ?? 'provider2');
        if (in_array($p, ['sms-bus', 'smsbus'], true)) return 'provider2';
        if ($p === '5sim')                               return 'provider1';
        return $p;
    }
}