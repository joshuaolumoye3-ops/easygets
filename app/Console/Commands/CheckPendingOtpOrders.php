<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckPendingOtpOrders extends Command
{
    protected $signature = 'otp:check-pending-orders';
    protected $description = 'Check and update status of all pending OTP orders from 5sim API';

    public function handle()
    {
        // Support both legacy and new OTP transaction types
        $otpTypes = ['otp', 'activation'];
        $pending = Transaction::whereIn('type', $otpTypes)
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('request_id')
            ->get();

        $checked = 0;
        $updated = 0;
        $token = env('FIVESIM_TOKEN');

        foreach ($pending as $txn) {
            $checked++;
            try {
                // Call 5sim API directly
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept' => 'application/json'
                    ])
                    ->withOptions(['verify' => false])
                    ->get("https://5sim.net/v1/user/check/{$txn->request_id}");

                if (!$response->ok()) {
                    $this->line("⚠ [ID: {$txn->id}] API error: {$response->status()}");
                    continue;
                }

                $data = $response->json();
                $apiStatus = $data['status'] ?? 'UNKNOWN';

                // Check if SMS data is present
                if (isset($data['sms']) && is_array($data['sms']) && count($data['sms']) > 0) {
                    $smsData = $data['sms'][0];
                    
                    // Extract code from SMS
                    if (isset($smsData['code']) && !empty($smsData['code'])) {
                        $code = $smsData['code'];
                        
                        $txn->sms_code = $code;
                        $txn->status = 'completed';
                        $txn->save();
                        $updated++;
                        
                        $this->info("✓ [ID: {$txn->id}] SMS Code received: <fg=green>{$code}</>");
                        Log::info("OTP order updated for txn {$txn->id}", [
                            'code' => $code,
                            'api_status' => $apiStatus,
                            'phone' => $txn->phone
                        ]);
                        continue;
                    }
                }

                // Handle status changes
                if ($apiStatus === 'RECEIVED') {
                    // SMS might be coming, keep checking
                    $this->line("◌ [ID: {$txn->id}] <fg=blue>RECEIVED</> - SMS should arrive soon");
                } elseif ($apiStatus === 'FINISHED') {
                    // Finished but no SMS received
                    $txn->status = 'completed';
                    $txn->save();
                    $updated++;
                    $this->comment("✓ [ID: {$txn->id}] Status: <fg=yellow>FINISHED</>");
                } elseif ($apiStatus === 'CANCELED') {
                    $txn->status = 'canceled';
                    $txn->save();
                    $updated++;
                    $this->error("✓ [ID: {$txn->id}] Status: <fg=red>CANCELED</>");
                } elseif ($apiStatus === 'TIMEOUT') {
                    $txn->status = 'expired';
                    $txn->save();
                    $updated++;
                    $this->error("✓ [ID: {$txn->id}] Status: <fg=red>TIMEOUT</>");
                } else {
                    // Still pending
                    $this->line("⧖ [ID: {$txn->id}] Still waiting... Status: {$apiStatus}");
                }

            } catch (\Exception $e) {
                Log::error('OTP check failed for txn '.$txn->id, [
                    'error' => $e->getMessage(),
                    'request_id' => $txn->request_id
                ]);
                $this->error("✗ [ID: {$txn->id}] Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>═══════════════════════════════════════</>'); 
        $this->line("<fg=cyan>🔍 OTP Checker Report</>");
        $this->line("  Checked:  <fg=white>{$checked}</> pending orders");
        $this->line("  Updated:  <fg=green>{$updated}</> orders with new data");
        $this->line("  Pending:  <fg=yellow>".($checked - $updated).'</> still waiting');
        $this->line('<fg=cyan>═══════════════════════════════════════</>');
        $this->newLine();
    }
}
