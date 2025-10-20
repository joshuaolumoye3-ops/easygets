<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $newActivationId = session()->pull('new_activation');

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
                    'date' => Carbon::parse($activation->created_   at)->format('M d, Y h:i A'),
                    'provider' => strtolower($activation->channel ?? 'smsbus'),
                    'expires_at' => $activation->expires_at,
                    'operator_name' => $activation->operator_name,
                ];
            });

        return view('users.history', [
            'activations' => $activations,
            'newActivationId' => $newActivationId
        ]);
    }

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
                ->get('https://sms-bus.com/api/control/get/sms', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            $data = $response->json();

            Log::info('SMS-Bus SMS check', [
                'request_id' => $activation->request_id,
                'response' => $data
            ]);

            if (isset($data['code']) && $data['code'] === 200 && isset($data['data'])) {
                // SMS received
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'sms_code' => $data['data'],
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => true,
                    'sms_code' => $data['data'],
                    'status' => 'completed'
                ]);
            } elseif (isset($data['code']) && $data['code'] === 50101) {
                // Not received yet
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Waiting for SMS'
                ]);
            } elseif (isset($data['code']) && $data['code'] === 50102) {
                // Number released or timeout
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Number expired'
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'SMS not received yet'
            ]);

        } catch (\Exception $e) {
            Log::error('SMS-Bus SMS fetch error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching SMS'
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
                ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

            $data = $response->json();

            Log::info('5sim SMS check', [
                'request_id' => $activation->request_id,
                'response' => $data
            ]);

            if (isset($data['sms']) && !empty($data['sms'])) {
                // Extract code from SMS
                $smsText = is_array($data['sms']) ? $data['sms'][0]['text'] : $data['sms'];
                $code = $this->extractCode($smsText);

                if ($code) {
                    DB::table('transactions')
                        ->where('id', $activation->id)
                        ->update([
                            'sms_code' => $code,
                            'status' => 'completed',
                            'updated_at' => now()
                        ]);

                    return response()->json([
                        'success' => true,
                        'sms_code' => $code,
                        'status' => 'completed'
                    ]);
                }
            }

            // Check if finished without SMS
            if (isset($data['status']) && in_array($data['status'], ['FINISHED', 'CANCELED'])) {
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now()
                    ]);

                return response()->json([
                    'success' => false,
                    'status' => 'expired',
                    'message' => 'Activation finished without SMS'
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => 'Waiting for SMS'
            ]);

        } catch (\Exception $e) {
            Log::error('5sim SMS fetch error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching SMS'
            ], 500);
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

            // Cancel based on provider
            $provider = strtolower($activation->channel ?? 'smsbus');
            
            if ($provider === 'sms-bus' || $provider === 'smsbus') {
                $result = $this->cancelSmsBus($activation);
            } elseif ($provider === '5sim') {
                $result = $this->cancel5sim($activation);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown provider'
                ], 400);
            }

            if ($result['success']) {
                // Update status
                DB::table('transactions')
                    ->where('id', $activation->id)
                    ->update([
                        'status' => 'cancelled',
                        'updated_at' => now()
                    ]);

                // Refund user
                DB::transaction(function () use ($activation) {
                    DB::table('users')
                        ->where('id', $activation->user_id)
                        ->increment('balance', $activation->amount);

                    // Create refund transaction
                    DB::table('transactions')->insert([
                        'reference' => 'REF_' . strtoupper(uniqid()),
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
                });

                Log::info('Activation cancelled and refunded', [
                    'activation_id' => $activation->id,
                    'amount' => $activation->amount
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Activation cancelled successfully. ₦' . number_format($activation->amount, 2) . ' has been refunded to your wallet.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to cancel activation'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error cancelling activation', [
                'error' => $e->getMessage(),
                'activation_id' => $request->activation_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling'
            ], 500);
        }
    }

    private function cancelSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');

        try {
            $response = Http::timeout(15)
                ->get('https://sms-bus.com/api/control/cancel', [
                    'token' => $token,
                    'request_id' => $activation->request_id
                ]);

            $data = $response->json();

            if (isset($data['code']) && $data['code'] === 200) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to cancel'
            ];

        } catch (\Exception $e) {
            Log::error('SMS-Bus cancel error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return [
                'success' => false,
                'message' => 'Error cancelling request'
            ];
        }
    }

    private function cancel5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/json'
                ])
                ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");

            if ($response->successful()) {
                return ['success' => true];
            }

            return [
                'success' => false,
                'message' => 'Failed to cancel activation'
            ];

        } catch (\Exception $e) {
            Log::error('5sim cancel error', [
                'error' => $e->getMessage(),
                'request_id' => $activation->request_id
            ]);

            return [
                'success' => false,
                'message' => 'Error cancelling request'
            ];
        }
    }

    private function extractCode($smsText)
{
    if (empty($smsText)) {
        return null;
    }

    // Convert to string if it's an array or object
    if (is_array($smsText) || is_object($smsText)) {
        $smsText = json_encode($smsText);
    }

    // Try different patterns to extract code
    $patterns = [
        '/\b(\d{4,8})\b/',           // 4-8 digit codes
        '/code[:\s]+(\d{4,8})/i',    // "code: 1234" or "code 1234"
        '/verification[:\s]+(\d{4,8})/i', // "verification: 1234"
        '/otp[:\s]+(\d{4,8})/i',     // "OTP: 1234"
        '/pin[:\s]+(\d{4,8})/i',     // "PIN: 1234"
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $smsText, $matches)) {
            return $matches[1];
        }
    }

    // If no pattern matches, try to find the first 4-8 digit sequence
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
}