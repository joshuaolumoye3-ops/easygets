<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\MoreThanPanelHelper;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    protected $mtpHelper;

    public function __construct()
    {
        $this->mtpHelper = new MoreThanPanelHelper();
    }

    public function index()
    {
        $userId = Auth::check() ? Auth::id() : null;
        $newActivationId = session()->pull('new_activation');
        $newSocialBoostId = session()->pull('new_social_boost');

        // Fetch activation history for both 'activation' and 'otp' types
        $activations = \App\Models\Transaction::where('user_id', $userId)
            ->whereIn('type', ['activation', 'otp'])
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activation) {
                // Defensive: handle nulls and type errors
                $amount = is_numeric($activation->amount) ? $activation->amount : 0;
                $createdAt = $activation->created_at ? Carbon::parse($activation->created_at) : now();
                $countryCode = $activation->country_code ?? 'xx';
                return [
                    'id' => $activation->id,
                    'request_id' => $activation->request_id ?? '',
                    'number' => $activation->phone ?? '',
                    'service_name' => $activation->service_name ?? $activation->provider ?? '',
                    'service_logo' => $activation->service_logo ?? '',
                    'country_name' => $activation->country_name ?? 'Unknown',
                    'country_code' => $countryCode,
                    'flag' => strtolower($countryCode),
                    'amount' => '₦' . number_format($amount, 2),
                    'status' => $this->getStatusLabel($activation->status ?? ''),
                    'raw_status' => $activation->status ?? '',
                    'sms_code' => $activation->sms_code ?? '',
                    'date' => $createdAt->format('M d, Y h:i A'),
                    'provider' => $this->normalizeProvider(strtolower($activation->channel ?? 'provider2')),
                    'expires_at' => $activation->expires_at ?? null,
                    'operator_name' => $activation->operator_name ?? '',
                ];
            });

        // Fetch social boost history
        $socialBoosts = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereIn('type', ['social_boost', 'smm'])   // include both old and new
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($boost) {
                $responseData = json_decode($boost->response, true) ?? [];
                return [
                    'id'           => $boost->id,
                    'reference'    => $boost->reference,
                    'request_id'   => $boost->request_id,
                    'service_name' => $boost->service_name ?? $boost->provider ?? 'Social Boost',
                    'service_logo' => $boost->service_logo ?? '🚀',
                    'category'     => $responseData['category'] ?? 'Social Media',
                    'link'         => $responseData['link'] ?? $boost->account ?? null,
                    'quantity'     => $responseData['quantity'] ?? 0,
                    'start_count'  => $responseData['start_count'] ?? null,
                    'remains'      => $responseData['remains'] ?? null,
                    'amount'       => '₦' . number_format($boost->amount, 2),
                    'status'       => $this->getSocialStatusLabel($boost->status),
                    'raw_status'   => $boost->status,
                    'date'         => Carbon::parse($boost->created_at)->format('M d, Y h:i A'),
                    'service_type' => $responseData['service_type'] ?? 'Default',
                    'mtp_status'   => $responseData['mtp_status'] ?? null,
                    'refill_available' => $responseData['refill'] ?? false,
                    'can_cancel'   => $responseData['cancel'] ?? false,
                ];
            });

        return view('users.history', [
            'activations' => $activations,
            'socialBoosts' => $socialBoosts,
            'newActivationId' => $newActivationId,
            'newSocialBoostId' => $newSocialBoostId,
            'logs' => $this->fetchLogsForUser($userId),
        ]);
    }

    /**
     * Include logs purchases in user history view (as a simple list)
     */
    private function fetchLogsForUser($userId)
    {
        $logs = \App\Models\Transaction::where('user_id', $userId)
            ->where('type', 'logs')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $response = is_array($t->response) ? $t->response : (json_decode($t->response, true) ?? []);
                return [
                    'id' => $t->id,
                    'reference' => $t->reference,
                    'service_name' => $t->service_name ?? $t->recipient ?? 'Logs',
                    'amount' => '₦' . number_format($t->amount ?? 0, 2),
                    'status' => ucfirst($t->status ?? 'pending'),
                    'date' => \Carbon\Carbon::parse($t->created_at)->format('M d, Y h:i A'),
                    'login_details' => $response['login_details'] ?? null,
                    'notes' => $response['notes'] ?? null,
                ];
            });

        return $logs;
    }

    // Expire activation if countdown hits zero and SMS not delivered
    public function expireActivation(Request $request)
    {
        $request->validate([
            'activation_id' => 'required|integer'
        ]);

        try {
            $userId = Auth::check() ? Auth::id() : null;
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)
                ->where('user_id', $userId)
                ->where('type', 'activation')
                ->first();

            if (!$activation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activation not found'
                ], 404);
            }

            // Only expire if SMS not delivered and status is pending
            if (!empty($activation->sms_code) || strtolower($activation->status ?? '') !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Activation cannot be expired (already completed, cancelled, or SMS received)'
                ], 400);
            }

            // Expire and refund
            DB::transaction(function() use ($activation) {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);
                $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->first();
                if ($wallet) {
                    DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                    DB::table('transactions')->insert([
                        'reference' => 'REF_' . strtoupper(Str::random(10)),
                        'user_id' => $activation->user_id,
                        'type' => 'credit',
                        'channel' => 'Refund',
                        'provider' => $activation->service_name ?? $activation->provider,
                        'amount' => $activation->amount,
                        'status' => 'success',
                        'description' => 'Activation expired refund for ' . $activation->phone,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
            return response()->json([
                'success' => true,
                'status' => 'expired',
                'message' => 'Activation expired and refunded'
            ]);
        } catch (\Exception $e) {
            Log::error('Error expiring activation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error expiring activation'
            ], 500);
        }
    }
    // ==================== SOCIAL BOOST METHODS ====================

    public function getSocialStatus(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|integer'
        ]);

        try {
            $transaction = DB::table('transactions')
                ->where('id', $request->transaction_id)
                ->where('user_id', auth()->id())
                ->where('type', 'social_boost')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Check if request_id exists
            if (empty($transaction->request_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID not found',
                    'status' => $transaction->status
                ]);
            }

            Log::info('Checking social boost status', [
                'transaction_id' => $transaction->id,
                'mtp_order_id' => $transaction->request_id
            ]);

            // Get status from MoreThanPanel
            $statusResponse = $this->mtpHelper->getOrderStatus($transaction->request_id);

            if (!$statusResponse) {
                Log::warning('Empty response from MTP API', [
                    'order_id' => $transaction->request_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch status from provider',
                    'status' => $transaction->status
                ]);
            }

            // Check for API error
            if (isset($statusResponse['error'])) {
                Log::error('MTP API returned error', [
                    'order_id' => $transaction->request_id,
                    'error' => $statusResponse['error']
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $statusResponse['error'],
                    'status' => $transaction->status
                ]);
            }

            // Extract status information
            $mtpStatus = $statusResponse['status'] ?? 'Pending';
            $charge = $statusResponse['charge'] ?? null;
            $startCount = $statusResponse['start_count'] ?? null;
            $remains = $statusResponse['remains'] ?? 0;

            // Map MTP status to local status
            $localStatus = $this->mtpHelper->mapStatus($mtpStatus);

            // Treat "Completed" as success so the UI shows green
            if ($localStatus === 'completed') {
                $localStatus = 'success';
            }

            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update([
                    'status'   => $localStatus,
                    'response' => json_encode(array_merge(
                        json_decode($transaction->response, true) ?? [],
                        [
                            'mtp_status'  => $mtpStatus,
                            'start_count' => $statusResponse['start_count'] ?? null,
                            'remains'     => $statusResponse['remains'] ?? null,
                            'charge'      => $statusResponse['charge'] ?? null,
                            'last_checked'=> now()->toIso8601String(),
                        ]
                    )),
                    'updated_at' => now(),
                ]);

            Log::info('Social boost status updated', [
                'transaction_id' => $transaction->id,
                'mtp_status' => $mtpStatus,
                'local_status' => $localStatus,
                'remains' => $remains
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'mtp_status' => $mtpStatus,
                    'local_status' => $localStatus,
                    'start_count' => $startCount,
                    'remains' => $remains,
                    'charge' => $charge,
                    'status_label' => $this->getSocialStatusLabel($localStatus)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching social boost status', [
                'error' => $e->getMessage(),
                'transaction_id' => $request->transaction_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking status: ' . $e->getMessage(),
                'status' => 'pending'
            ], 500);
        }
    }

    // Backwards-compatible wrapper for route binding (some routes use getSmsCode)
    public function getSmsCode(Request $request)
    {
        return $this->getSms($request);
    }

    // ==================== ACTIVATION METHODS (EXISTING) ====================

    public function getSms(Request $request)
    {
        $request->validate([
            'activation_id' => 'required|integer'
        ]);

        try {
            $userId = Auth::check() ? Auth::id() : null;
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)
                ->where('user_id', $userId)
                ->where('type', 'activation')
                ->first();

            if (!$activation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activation not found'
                ], 404);
            }

            // Check if already has SMS code
            if ($activation->sms_code) {
                return response()->json([
                    'success' => true,
                    'sms_code' => $activation->sms_code,
                    'status' => 'completed'
                ]);
            }

            // Check if expired (always set status to expired and refund if expired)
            if ($activation->expires_at && Carbon::parse($activation->expires_at)->isPast()) {
                DB::transaction(function() use ($activation) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update([
                            'status' => 'expired',
                            'updated_at' => now()
                        ]);
                    $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->first();
                    if ($wallet) {
                        DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                        DB::table('transactions')->insert([
                            'reference' => 'REF_' . strtoupper(Str::random(10)),
                            'user_id' => $activation->user_id,
                            'type' => 'credit',
                            'channel' => 'Refund',
                            'provider' => $activation->service_name ?? $activation->provider,
                            'amount' => $activation->amount,
                            'status' => 'success',
                            'description' => 'Activation expired refund for ' . $activation->phone,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Activation expired and refunded'
                ]);
            }

            // Fetch SMS based on provider (channel field)
            $provider = strtolower($activation->channel ?? 'provider2');
            // Normalize old provider names for backwards compatibility
            if ($provider === 'sms-bus' || $provider === 'smsbus') {
                $provider = 'provider2';
            } elseif ($provider === '5sim') {
                $provider = 'provider1';
            }
            
            if ($provider === 'provider2') {
                return $this->fetchSmsBus($activation);
            } elseif ($provider === 'provider1') {
                return $this->fetchSms5sim($activation);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unknown provider'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error fetching SMS', [
                'error' => $e->getMessage(),
                'activation_id' => $request->activation_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking SMS'
            ], 500);
        }
    }

    private function fetchSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');

        if (!$token) {
            Log::error('Provider2 token not configured');
            return response()->json([
                'success' => false,
                'message' => 'Service token not configured'
            ], 500);
        }

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/get/sms', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            Log::info('Provider2 SMS check', [
                'request_id' => $activation->request_id,
                'response_status' => $response->status(),
                'response_successful' => $response->successful()
            ]);

            // Check if HTTP request failed
            if ($response->failed()) {
                Log::error('Provider2 SMS fetch HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_id' => $activation->request_id
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Service temporarily unavailable'
                ], 500);
            }

            $data = $response->json();

            Log::info('Provider2 API response', [
                'request_id' => $activation->request_id,
                'code' => $data['code'] ?? null,
                'has_data' => isset($data['data']) && !empty($data['data'])
            ]);

            // Success: SMS received
            if (isset($data['code']) && $data['code'] === 200 && isset($data['data']) && !empty($data['data'])) {
                $smsCode = $data['data'];

                Log::info("Provider2 SMS received for activation {$activation->id}", [
                    'code' => $smsCode,
                    'request_id' => $activation->request_id
                ]);
                
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'sms_code' => $smsCode,
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => true,
                    'sms_code' => $smsCode,
                    'status' => 'completed'
                ]);
            }

            // Error: Not received yet
            if (isset($data['code']) && $data['code'] === 50101) {
                Log::info("Provider2 SMS not received yet for {$activation->request_id}");
                
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Waiting for SMS'
                ]);
            }

            // Error: Number expired or released
            if (isset($data['code']) && $data['code'] === 50102) {
                Log::warning('Provider2 number expired/released', [
                    'request_id' => $activation->request_id,
                    'message' => $data['message'] ?? 'Unknown'
                ]);

                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Number expired or released'
                ]);
            }

            // Unexpected response
            Log::warning('Provider2 unexpected response code', [
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
                'request_id' => $activation->request_id
            ]);

            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => $data['message'] ?? 'SMS not received yet'
            ]);

        } catch (\Exception $e) {
            Log::error('Provider2 SMS fetch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $activation->request_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching SMS',
                'status' => 'pending'
            ], 500);
        }
    }

    private function fetchSms5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

            $data = $response->json();

            // Check if SMS data is present (status RECEIVED means SMS arrived)
            if (isset($data['sms']) && !empty($data['sms']) && is_array($data['sms'])) {
                $smsData = $data['sms'][0] ?? null;
                
                if ($smsData && isset($smsData['code'])) {
                    // Direct code from API
                    $code = $smsData['code'];
                    
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update([
                            'sms_code' => $code,
                            'status' => 'completed',
                            'updated_at' => now()
                        ]);

                    try {
                        Http::timeout(10)
                            ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                            ->withOptions(['verify' => false])
                            ->get("https://5sim.net/v1/user/finish/{$activation->request_id}");
                    } catch (\Exception $e) {
                        Log::warning('Failed to finish 5sim activation', ['error' => $e->getMessage()]);
                    }

                    Log::info("SMS received for activation {$activation->id}", ['code' => $code]);
                    return response()->json([
                        'success' => true,
                        'sms_code' => $code,
                        'status' => 'completed'
                    ]);
                }
            }

            // Check API status
            $status = $data['status'] ?? 'PENDING';
            
            if ($status === 'FINISHED' || $status === 'RECEIVED') {
                // Try to get code only if SMS data exists
                if (isset($data['sms']) && is_array($data['sms']) && count($data['sms']) > 0) {
                    if (isset($data['sms'][0]['code'])) {
                        $code = $data['sms'][0]['code'];
                        DB::table('transactions')
                            ->where('id', $activation->id)
                            ->update(['sms_code' => $code, 'status' => 'completed', 'updated_at' => now()]);
                        return response()->json(['success' => true, 'sms_code' => $code, 'status' => 'completed']);
                    }
                }
                // If status is FINISHED/RECEIVED but no SMS code, still waiting
                return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Activation received, waiting for SMS']);
            } elseif ($status === 'CANCELED' || $status === 'TIMEOUT') {
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'expired', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Activation expired']);
            }

            return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Waiting for SMS']);

        } catch (\Exception $e) {
            Log::error('5sim SMS fetch error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return response()->json(['success' => false, 'message' => 'Error fetching SMS', 'status' => 'pending'], 500);
        }
    }

    public function cancelActivation(Request $request)
    {
        $request->validate([
            'activation_id' => 'required|integer'
        ]);
 
        $userId = Auth::check() ? Auth::id() : null;
 
        try {
            // Wrap the entire operation — including the initial read — in one
            // transaction so the lockForUpdate() actually prevents the cron
            // jobs from touching the same row at the same time.
            $result = DB::transaction(function () use ($request, $userId) {
 
                // Read + lock inside the transaction
                $activation = DB::table('transactions')
                    ->where('id', $request->activation_id)
                    ->where('user_id', $userId)
                    ->where('type', 'activation')
                    ->lockForUpdate()
                    ->first();
 
                if (!$activation) {
                    return ['status' => 404, 'success' => false, 'message' => 'Activation not found'];
                }
 
                // Terminal statuses — never allow re-cancel
                $nonCancelable = ['completed', 'success', 'expired', 'cancelled', 'finished', 'refunded'];
 
                if (!empty($activation->sms_code) || in_array(strtolower($activation->status ?? ''), $nonCancelable, true)) {
                    Log::warning('Cancel rejected — non-cancelable status', [
                        'activation_id'  => $activation->id,
                        'current_status' => $activation->status,
                        'has_sms_code'   => !empty($activation->sms_code),
                    ]);
                    return [
                        'status'  => 400,
                        'success' => false,
                        'message' => 'This activation cannot be cancelled (already completed or SMS received)',
                    ];
                }
 
                // Mark as cancelled NOW inside the transaction so no cron job
                // can race in and overwrite it with "expired".
                // We always use 'cancelled' for a manual user action — regardless of age.
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);
 
                return ['status' => 200, 'success' => true, 'activation' => $activation];
            });
 
            // If the lock check failed, return early
            if (!$result['success']) {
                return response()->json(
                    ['success' => false, 'message' => $result['message']],
                    $result['status']
                );
            }
 
            $activation = $result['activation'];
            $provider   = $this->normalizeProvider($activation->channel);
 
            Log::info('User-initiated cancellation — status locked to cancelled', [
                'activation_id' => $activation->id,
                'user_id'       => $userId,
                'provider'      => $provider,
                'phone'         => $activation->phone,
                'amount'        => $activation->amount,
            ]);
 
            // Attempt provider-side cancel AFTER we've already secured the status change.
            // Even if the provider API fails we still refund — the number is effectively
            // abandoned from the user's perspective.
            $cancelResult = $this->attemptProviderCancel($activation, $provider);
 
            if (!$cancelResult['success']) {
                // Log the provider failure but continue to refund anyway
                Log::warning('Provider cancel failed during user cancel — proceeding with refund', [
                    'activation_id' => $activation->id,
                    'provider'      => $provider,
                    'reason'        => $cancelResult['reason'] ?? 'Unknown',
                ]);
            }
 
            // Process the wallet refund
            $refundResult = $this->processCancellationRefund($activation, $provider);
 
            if ($refundResult['success']) {
                Log::info('User-initiated cancellation completed', [
                    'activation_id' => $activation->id,
                    'user_id'       => $userId,
                    'refund_amount' => $activation->amount,
                ]);
 
                return response()->json([
                    'success' => true,
                    'message' => 'Activation cancelled successfully. ₦' . number_format($activation->amount, 2) . ' has been refunded to your wallet.',
                ]);
            }
 
            // Refund itself failed — critical
            Log::critical('User cancel: refund failed after status already set to cancelled', [
                'activation_id' => $activation->id,
                'user_id'       => $userId,
                'amount'        => $activation->amount,
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'Cancellation succeeded but refund failed. Please contact support.',
            ], 500);
 
        } catch (\Exception $e) {
            Log::error('Error in cancelActivation', [
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
                'activation_id' => $request->activation_id,
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling',
            ], 500);
        }
    }

    /**
     * Attempt to cancel at provider level
     */
    private function attemptProviderCancel($activation, $provider): array
    {
        if ($provider === 'provider2') {
            return $this->cancelSmsBus($activation);
        } elseif ($provider === 'provider1') {
            return $this->cancel5sim($activation);
        }
        
        return ['success' => false, 'reason' => 'Unknown provider'];
    }

    /**
     * Process refund after successful cancellation
     * Status will be 'cancelled' for early user-initiated cancellation (< 10 minutes)
     * Status will be 'expired' for user-initiated cancellation after 10+ minutes
     */
    private function processCancellationRefund($activation, string $provider): array
    {
        try {
            return DB::transaction(function () use ($activation, $provider) {
 
                // Idempotency: bail out if a refund record already exists for this activation
                $alreadyRefunded = DB::table('transactions')
                    ->where('type', 'credit')
                    ->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)
                    ->where('amount', $activation->amount)
                    ->whereRaw("JSON_EXTRACT(response, '$.original_activation_id') = ?", [$activation->id])
                    ->exists();
 
                if ($alreadyRefunded) {
                    Log::info('processCancellationRefund: duplicate detected — skipping', [
                        'activation_id' => $activation->id,
                    ]);
                    return ['success' => true];
                }
 
                // Lock and credit wallet
                $wallet = DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->lockForUpdate()
                    ->first();
 
                if (!$wallet) {
                    Log::error('Wallet not found for refund', [
                        'user_id'       => $activation->user_id,
                        'activation_id' => $activation->id,
                    ]);
                    return ['success' => false, 'reason' => 'Wallet not found'];
                }
 
                DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->increment('balance', $activation->amount);
 
                $newBalance = (float) DB::table('wallets')
                    ->where('user_id', $activation->user_id)
                    ->value('balance');
 
                // Record the credit transaction
                DB::table('transactions')->insert([
                    'reference'    => 'REF_' . strtoupper(\Illuminate\Support\Str::random(10)),
                    'user_id'      => $activation->user_id,
                    'type'         => 'credit',
                    'channel'      => 'Refund',
                    'provider'     => $activation->service_name ?? $activation->provider ?? 'System',
                    'phone'        => $activation->phone ?? null,
                    'recipient'    => null,
                    'amount'       => $activation->amount,
                    'status'       => 'success',
                    'description'  => "User-initiated cancellation refund for {$provider} — {$activation->phone}",
                    'response'     => json_encode([
                        'original_activation_id' => $activation->id,
                        'reason'                 => 'User cancelled',
                    ]),
                    'balance_after' => $newBalance,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
 
                Log::info('processCancellationRefund: wallet credited', [
                    'activation_id' => $activation->id,
                    'user_id'       => $activation->user_id,
                    'amount'        => $activation->amount,
                    'new_balance'   => $newBalance,
                ]);
 
                return ['success' => true];
            });
 
        } catch (\Exception $e) {
            Log::error('processCancellationRefund failed', [
                'activation_id' => $activation->id,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'reason' => 'Refund processing failed'];
        }
    }

    private function cancelSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            Log::error('Provider2 token not configured');
            return ['success' => false, 'message' => 'Service token not configured'];
        }

        try {
            Log::info('Provider2 cancellation attempt', [
                'request_id' => $activation->request_id,
                'activation_id' => $activation->id
            ]);

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/cancel', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            Log::info('Provider2 cancel response', [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            // Check if HTTP request failed
            if ($response->failed()) {
                Log::error('Provider2 cancel HTTP failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_id' => $activation->request_id
                ]);
                return ['success' => false, 'message' => 'Failed to cancel (service error)'];
            }

            $data = $response->json();

            // Success: cancellation completed
            if (isset($data['code']) && $data['code'] === 200) {
                Log::info('Provider2 cancellation successful', [
                    'request_id' => $activation->request_id,
                    'activation_id' => $activation->id
                ]);
                return ['success' => true];
            }

            // Error: already closed
            if (isset($data['code']) && $data['code'] === 50103) {
                Log::info('Provider2 request already closed', [
                    'request_id' => $activation->request_id,
                    'message' => $data['message'] ?? 'Already closed'
                ]);
                return ['success' => false, 'message' => 'Request already closed'];
            }

            // Other errors
            Log::warning('Provider2 cancel unexpected response', [
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
                'request_id' => $activation->request_id
            ]);

            return ['success' => false, 'message' => $data['message'] ?? 'Failed to cancel activation'];

        } catch (\Exception $e) {
            Log::error('Provider2 cancel error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $activation->request_id
            ]);
            return ['success' => false, 'message' => 'Error cancelling request'];
        }
    }

    private function cancel5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                ->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");

            if ($response->successful()) {
                return ['success' => true];
            }

            $data = $response->json();
            return ['success' => false, 'message' => $data['message'] ?? 'Failed to cancel activation'];
        } catch (\Exception $e) {
            Log::error('Provider1 cancel error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return ['success' => false, 'message' => 'Error cancelling request'];
        }
    }

    /**
     * Extract verification code from SMS text
     */
    private function extractCode($smsText)
    {
        if (empty($smsText)) {
            return null;
        }

        if (is_array($smsText) || is_object($smsText)) {
            $smsText = json_encode($smsText);
        }

        $patterns = [
            '/\b(\d{4,8})\b/',
            '/code[:\s]+(\d{4,8})/i',
            '/verification[:\s]+(\d{4,8})/i',
            '/otp[:\s]+(\d{4,8})/i',
            '/pin[:\s]+(\d{4,8})/i',
            '/is[:\s]+(\d{4,8})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $smsText, $matches)) {
                return $matches[1];
            }
        }

        if (preg_match('/(\d{4,8})/', $smsText, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Waiting for SMS',
            'completed' => 'Completed',
            'success' => 'Completed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    private function getSocialStatusLabel($status): string
    {
        $labels = [
            'pending'    => 'Processing',
            'processing' => 'In Progress',
            'success'    => 'Completed',      // ← add this
            'completed'  => 'Completed',
            'partial'    => 'Partial',
            'failed'     => 'Failed',
            'cancelled'  => 'Cancelled',
            'refunded'   => 'Refunded',
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    private function normalizeProvider($provider)
    {
        // Map old provider names to new ones for backwards compatibility
        $provider = strtolower($provider ?? 'provider2');
        
        if ($provider === 'sms-bus' || $provider === 'smsbus') {
            return 'provider2';
        } elseif ($provider === '5sim') {
            return 'provider1';
        }
        
        return $provider;
    }
}