<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\MoreThanPanelHelper;

class HistoryController extends Controller
{
    protected $mtpHelper;

    public function __construct()
    {
        $this->mtpHelper = new MoreThanPanelHelper();
    }

    public function index()
    {
        $userId = auth()->id();
        $newActivationId = session()->pull('new_activation');
        $newSocialBoostId = session()->pull('new_social_boost');

        // Fetch activation history from transactions table
        $activations = DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'activation')
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activation) {
                return [
                    'id' => $activation->id,
                    'request_id' => $activation->request_id,
                    'number' => $activation->phone,
                    'service_name' => $activation->service_name ?? $activation->provider,
                    'service_logo' => $activation->service_logo,
                    'country_name' => $activation->country_name ?? 'Unknown',
                    'country_code' => $activation->country_code ?? 'xx',
                    'flag' => strtolower($activation->country_code ?? 'xx'),
                    'amount' => '₦' . number_format($activation->amount, 2),
                    'status' => $this->getStatusLabel($activation->status),
                    'raw_status' => $activation->status,
                    'sms_code' => $activation->sms_code,
                    'date' => Carbon::parse($activation->created_at)->format('M d, Y h:i A'),
                    'provider' => strtolower($activation->channel ?? 'smsbus'),
                    'expires_at' => $activation->expires_at,
                    'operator_name' => $activation->operator_name,
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

            // Get status from MoreThanPanel
            $statusResponse = $this->mtpHelper->getOrderStatus($transaction->request_id);

            if (!$statusResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to fetch status',
                    'status' => 'pending'
                ]);
            }

            // Map status
            $localStatus = $this->mtpHelper->mapStatus($statusResponse['status'] ?? 'Pending');

            // Update local database
            DB::table('transactions')
                ->where('id', $transaction->id)
                ->update([
                    'status' => $localStatus,
                    'response' => json_encode(array_merge(
                        json_decode($transaction->response, true) ?? [],
                        [
                            'mtp_status' => $statusResponse['status'] ?? null,
                            'start_count' => $statusResponse['start_count'] ?? null,
                            'remains' => $statusResponse['remains'] ?? null,
                            'charge' => $statusResponse['charge'] ?? null,
                            'last_checked' => now()->toIso8601String(),
                        ]
                    )),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $statusResponse['status'],
                    'local_status' => $localStatus,
                    'start_count' => $statusResponse['start_count'] ?? null,
                    'remains' => $statusResponse['remains'] ?? null,
                    'charge' => $statusResponse['charge'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching social boost status', [
                'error' => $e->getMessage(),
                'transaction_id' => $request->transaction_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking status',
                'status' => 'pending'
            ], 500);
        }
    }

    // ==================== ACTIVATION METHODS (EXISTING) ====================

    public function getSms(Request $request)
    {
        $request->validate([
            'activation_id' => 'required|integer'
        ]);

        try {
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)
                ->where('user_id', auth()->id())
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

            // Check if expired
            if ($activation->expires_at && Carbon::parse($activation->expires_at)->isPast()) {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Activation expired'
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

            if (isset($data['sms']) && !empty($data['sms'])) {
                $smsData = is_array($data['sms']) ? $data['sms'][0] : $data['sms'];
                $smsText = is_array($smsData) ? ($smsData['text'] ?? $smsData['code'] ?? '') : $smsData;
                
                $code = $this->extractCode($smsText);

                if ($code) {
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
                        Log::warning('Failed to finish Provider1 activation', ['error' => $e->getMessage()]);
                    }

                    return response()->json([
                        'success' => true,
                        'sms_code' => $code,
                        'status' => 'completed'
                    ]);
                }
            }

            $status = $data['status'] ?? 'PENDING';
            
            if ($status === 'FINISHED') {
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'completed', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'completed', 'message' => 'Activation finished']);
            } elseif ($status === 'CANCELED' || $status === 'TIMEOUT') {
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'expired', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Activation expired']);
            }

            return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Waiting for SMS']);

        } catch (\Exception $e) {
            Log::error('Provider1 SMS fetch error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return response()->json(['success' => false, 'message' => 'Error fetching SMS', 'status' => 'pending'], 500);
        }
    }

    public function cancelActivation(Request $request)
    {
        $request->validate([
            'activation_id' => 'required|integer'
        ]);

        try {
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)
                ->where('user_id', auth()->id())
                ->where('type', 'activation')
                ->where('status', 'pending')
                ->first();

            if (!$activation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activation not found or already processed'
                ], 404);
            }

            $provider = strtolower($activation->channel ?? 'smsbus');
            
            if ($provider === 'sms-bus' || $provider === 'smsbus') {
                $result = $this->cancelSmsBus($activation);
            } elseif ($provider === '5sim') {
                $result = $this->cancel5sim($activation);
            } else {
                return response()->json(['success' => false, 'message' => 'Unknown provider'], 400);
            }

            if ($result['success']) {
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
                            'status' => 'success',
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

            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed to cancel activation'], 400);

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