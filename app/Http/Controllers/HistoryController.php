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
                    'provider' => strtolower($activation->channel ?? 'smsbus'),
                    'expires_at' => $activation->expires_at ?? null,
                    'operator_name' => $activation->operator_name ?? '',
                ];
            });

        // Fetch social boost history
        $socialBoosts = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'social_boost')
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($boost) {
                $responseData = json_decode($boost->response, true) ?? [];
                return [
                    'id' => $boost->id,
                    'reference' => $boost->reference,
                    'request_id' => $boost->request_id, // MTP order ID
                    'service_name' => $boost->service_name,
                    'service_logo' => $boost->service_logo ?? '🚀',
                    'category' => $responseData['category'] ?? 'Social Media',
                    'link' => $responseData['link'] ?? null,
                    'quantity' => $responseData['quantity'] ?? 0,
                    'start_count' => $responseData['start_count'] ?? null,
                    'remains' => $responseData['remains'] ?? null,
                    'amount' => '₦' . number_format($boost->amount, 2),
                    'status' => $this->getSocialStatusLabel($boost->status),
                    'raw_status' => $boost->status,
                    'date' => Carbon::parse($boost->created_at)->format('M d, Y h:i A'),
                    'service_type' => $responseData['service_type'] ?? 'Default',
                    'mtp_status' => $responseData['mtp_status'] ?? null,
                    'refill_available' => $responseData['refill'] ?? false,
                    'can_cancel' => $responseData['cancel'] ?? false,
                ];
            });

        return view('users.history', [
            'activations' => $activations,
            'socialBoosts' => $socialBoosts,
            'newActivationId' => $newActivationId,
            'newSocialBoostId' => $newSocialBoostId
        ]);
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
            \Log::error('Error expiring activation', [
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

            // Get existing response data
            $existingResponseData = json_decode($transaction->response, true) ?? [];

            // Update local database with new status
            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update([
                    'status' => $localStatus,
                    'response' => json_encode(array_merge(
                        $existingResponseData,
                        [
                            'mtp_status' => $mtpStatus,
                            'start_count' => $startCount,
                            'remains' => $remains,
                            'charge' => $charge,
                            'last_checked' => now()->toIso8601String(),
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
            $provider = strtolower($activation->channel ?? 'smsbus');
            if ($provider === 'sms-bus' || $provider === 'smsbus') {
                return $this->fetchSmsBus($activation);
            } elseif ($provider === '5sim') {
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

        try {
            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/get/sms', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            $data = $response->json();

            if (isset($data['code']) && $data['code'] === 200 && isset($data['data'])) {
                $smsCode = $data['data'];
                
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
            } elseif (isset($data['code']) && $data['code'] === 50101) {
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Waiting for SMS'
                ]);
            } elseif (isset($data['code']) && $data['code'] === 50102) {
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

            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'SMS not received yet'
            ]);

        } catch (\Exception $e) {
            Log::error('Provider2 SMS fetch error', [
                'error' => $e->getMessage(),
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
                // Try to get code again even if status is RECEIVED
                if (isset($data['sms']) && is_array($data['sms']) && count($data['sms']) > 0) {
                    if (isset($data['sms'][0]['code'])) {
                        $code = $data['sms'][0]['code'];
                        DB::table('transactions')
                            ->where('id', $activation->id)
                            ->update(['sms_code' => $code, 'status' => 'completed', 'updated_at' => now()]);
                        return response()->json(['success' => true, 'sms_code' => $code, 'status' => 'completed']);
                    }
                }
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'completed', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'completed', 'message' => 'Activation finished']);
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

        try {
            // Fetch activation regardless of status so we can check provider-side
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

            // If SMS already present or activation already completed/expired/cancelled, don't attempt cancel
            $nonCancelableStatuses = ['completed', 'success', 'expired', 'cancelled', 'finished'];
            if (!empty($activation->sms_code) || in_array(strtolower($activation->status ?? ''), $nonCancelableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This activation cannot be cancelled (already completed or SMS received)'
                ], 400);
            }

            $provider = strtolower($activation->channel ?? 'smsbus');
            if ($provider === 'sms-bus' || $provider === 'smsbus') {
                $result = $this->cancelSmsBus($activation);
            } elseif ($provider === '5sim') {
                $result = $this->cancel5sim($activation);
            } else {
                return response()->json(['success' => false, 'message' => 'Unknown provider'], 400);
            }

            if (isset($result['success']) && $result['success']) {
                DB::transaction(function () use ($activation) {
                    DB::table('transactions')->where('id', $activation->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
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
                            'status' => 'cancelled',
                            'description' => 'Activation cancellation refund for ' . $activation->phone,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Activation cancelled successfully. ₦' . number_format($activation->amount, 2) . ' has been refunded to your wallet.'
                ]);
            }

            // Provider returned failure
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to cancel activation at provider'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error cancelling activation', ['error' => $e->getMessage(), 'activation_id' => $request->activation_id]);
            return response()->json(['success' => false, 'message' => 'An error occurred while cancelling'], 500);
        }
    }

    private function cancelSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');

        try {
            $response = Http::timeout(15)->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/cancel', ['token' => $token, 'request_id' => $activation->request_id]);
            $data = $response->json();

            if (isset($data['code']) && $data['code'] === 200) {
                return ['success' => true];
            }

            return ['success' => false, 'message' => $data['message'] ?? 'Failed to cancel activation'];
        } catch (\Exception $e) {
            Log::error('Provider2 cancel error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
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

    private function getSocialStatusLabel($status)
    {
        $labels = [
            'pending' => 'Processing',
            'processing' => 'In Progress',
            'completed' => 'Completed',
            'partial' => 'Partial',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded'
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}