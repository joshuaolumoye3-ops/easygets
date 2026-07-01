<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Helpers\MoreThanPanelHelper;
use App\Helpers\SmsBowerHelper;
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
        $userId           = Auth::check() ? Auth::id() : null;
        $newActivationId  = session()->pull('new_activation');
        $newSocialBoostId = session()->pull('new_social_boost');

        $activations = \App\Models\Transaction::where('user_id', $userId)
            ->whereIn('type', ['activation', 'otp'])
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activation) {
                $amount      = is_numeric($activation->amount) ? $activation->amount : 0;
                $createdAt   = $activation->created_at ? Carbon::parse($activation->created_at) : now();
                $countryCode = $activation->country_code ?? 'xx';
                return [
                    'id'            => $activation->id,
                    'request_id'    => $activation->request_id ?? '',
                    'number'        => $activation->phone ?? '',
                    'service_name'  => $activation->service_name ?? $activation->provider ?? '',
                    'service_logo'  => $activation->service_logo ?? '',
                    'country_name'  => $activation->country_name ?? 'Unknown',
                    'country_code'  => $countryCode,
                    'flag'          => strtolower($countryCode),
                    'amount'        => '₦' . number_format($amount, 2),
                    'status'        => $this->getStatusLabel($activation->status ?? ''),
                    'raw_status'    => $activation->status ?? '',
                    'sms_code'      => $activation->sms_code ?? '',
                    'date'          => $createdAt->format('M d, Y h:i A'),
                    'provider'      => $this->normalizeProvider(strtolower($activation->channel ?? 'provider3')),
                    'expires_at'    => $activation->expires_at ?? null,
                    'operator_name' => $activation->operator_name ?? '',
                ];
            });

        $socialBoosts = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereIn('type', ['social_boost', 'smm'])
            ->whereNotNull('request_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($boost) {
                $responseData = json_decode($boost->response, true) ?? [];
                return [
                    'id'               => $boost->id,
                    'reference'        => $boost->reference,
                    'request_id'       => $boost->request_id,
                    'service_name'     => $boost->service_name ?? $boost->provider ?? 'Social Boost',
                    'service_logo'     => $boost->service_logo ?? '🚀',
                    'category'         => $responseData['category'] ?? 'Social Media',
                    'link'             => $responseData['link'] ?? $boost->account ?? null,
                    'quantity'         => $responseData['quantity'] ?? 0,
                    'start_count'      => $responseData['start_count'] ?? null,
                    'remains'          => $responseData['remains'] ?? null,
                    'amount'           => '₦' . number_format($boost->amount, 2),
                    'status'           => $this->getSocialStatusLabel($boost->status),
                    'raw_status'       => $boost->status,
                    'date'             => Carbon::parse($boost->created_at)->format('M d, Y h:i A'),
                    'service_type'     => $responseData['service_type'] ?? 'Default',
                    'mtp_status'       => $responseData['mtp_status'] ?? null,
                    'refill_available' => $responseData['refill'] ?? false,
                    'can_cancel'       => $responseData['cancel'] ?? false,
                ];
            });

        return view('users.history', [
            'activations'       => $activations,
            'socialBoosts'      => $socialBoosts,
            'newActivationId'   => $newActivationId,
            'newSocialBoostId'  => $newSocialBoostId,
            'logs'              => $this->fetchLogsForUser($userId),
            'esims'             => $this->fetchEsimsForUser($userId),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // GET SMS CODE
    // ═══════════════════════════════════════════════════════════

    public function getSmsCode(Request $request)
    {
        return $this->getSms($request);
    }

    public function getSms(Request $request)
    {
        $request->validate(['activation_id' => 'required|integer']);

        try {
            $userId     = Auth::check() ? Auth::id() : null;
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)
                ->where('user_id', $userId)
                ->where('type', 'activation')
                ->first();

            if (!$activation) {
                return response()->json(['success' => false, 'message' => 'Activation not found'], 404);
            }

            if ($activation->sms_code) {
                return response()->json(['success' => true, 'sms_code' => $activation->sms_code, 'status' => 'completed']);
            }

            // Check expiry — always refund if expired
            if ($activation->expires_at && Carbon::parse($activation->expires_at)->isPast()) {
                DB::transaction(function () use ($activation) {
                    DB::table('transactions')->where('id', $activation->id)
                        ->update(['status' => 'expired', 'updated_at' => now()]);
                    $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->first();
                    if ($wallet) {
                        DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                        DB::table('transactions')->insert([
                            'reference'   => 'REF_' . strtoupper(Str::random(10)),
                            'user_id'     => $activation->user_id,
                            'type'        => 'credit',
                            'channel'     => 'Refund',
                            'provider'    => $activation->service_name ?? $activation->provider,
                            'amount'      => $activation->amount,
                            'status'      => 'success',
                            'description' => 'Activation expired refund for ' . $activation->phone,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                });
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Activation expired and refunded']);
            }

            $provider = $this->normalizeProvider(strtolower($activation->channel ?? 'provider3'));

            if ($provider === 'provider2') {
                return $this->fetchSmsSmsBower($activation);
            } elseif ($provider === 'provider3') {
                return $this->fetchSmsBus($activation);
            } elseif ($provider === 'provider1') {
                return $this->fetchSms5sim($activation);
            }

            return response()->json(['success' => false, 'message' => 'Unknown provider'], 400);
        } catch (\Exception $e) {
            Log::error('Error fetching SMS', ['error' => $e->getMessage(), 'activation_id' => $request->activation_id]);
            return response()->json(['success' => false, 'message' => 'Error checking SMS'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 2 — SMSBower SMS fetching
    // ═══════════════════════════════════════════════════════════

    private function fetchSmsSmsBower($activation)
    {
        try {
            $statusResponse = SmsBowerHelper::getStatus((string) $activation->request_id);

            Log::info('SMSBower getStatus response', [
                'activation_id' => $activation->id,
                'request_id'    => $activation->request_id,
                'response'      => $statusResponse,
            ]);

            // SMS received
            $code = SmsBowerHelper::parseSmsCode($statusResponse);
            if ($code !== null) {
                DB::table('transactions')->where('id', $activation->id)
                    ->update(['sms_code' => $code, 'status' => 'completed', 'updated_at' => now()]);

                // Finish the activation on SMSBower
                try {
                    SmsBowerHelper::finishActivation((string) $activation->request_id);
                } catch (\Exception $e) {
                    Log::warning('SMSBower finish activation failed', ['error' => $e->getMessage()]);
                }

                return response()->json(['success' => true, 'sms_code' => $code, 'status' => 'completed']);
            }

            // Cancelled on provider side
            if ($statusResponse === 'STATUS_CANCEL') {
                DB::table('transactions')->where('id', $activation->id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Activation cancelled by provider']);
            }

            // Still waiting
            return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Waiting for SMS']);
        } catch (\Exception $e) {
            Log::error('SMSBower SMS fetch error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return response()->json(['success' => false, 'message' => 'Error fetching SMS', 'status' => 'pending'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 3 — SMS-Bus SMS fetching (was provider2)
    // ═══════════════════════════════════════════════════════════

    private function fetchSmsBus($activation)
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Service token not configured'], 500);
        }

        try {
            $response = Http::timeout(15)->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/get/sms', [
                    'token'      => $token,
                    'request_id' => $activation->request_id,
                ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Service temporarily unavailable'], 500);
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] === 200 && isset($data['data']) && !empty($data['data'])) {
                $smsCode = $data['data'];
                DB::table('transactions')->where('id', $activation->id)
                    ->update(['sms_code' => $smsCode, 'status' => 'completed', 'updated_at' => now()]);
                return response()->json(['success' => true, 'sms_code' => $smsCode, 'status' => 'completed']);
            }

            if (isset($data['code']) && $data['code'] === 50101) {
                return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Waiting for SMS']);
            }

            if (isset($data['code']) && $data['code'] === 50102) {
                DB::table('transactions')->where('id', $activation->id)
                    ->update(['status' => 'expired', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Number expired or released']);
            }

            return response()->json(['success' => false, 'status' => 'pending', 'message' => $data['message'] ?? 'SMS not received yet']);
        } catch (\Exception $e) {
            Log::error('SMS-Bus SMS fetch error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return response()->json(['success' => false, 'message' => 'Error fetching SMS', 'status' => 'pending'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PROVIDER 1 — 5sim SMS fetching
    // ═══════════════════════════════════════════════════════════

    private function fetchSms5sim($activation)
    {
        $token = env('FIVESIM_TOKEN');
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                ->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/user/check/{$activation->request_id}");

            $data = $response->json();

            if (isset($data['sms']) && !empty($data['sms']) && is_array($data['sms'])) {
                $smsData = $data['sms'][0] ?? null;
                if ($smsData && isset($smsData['code'])) {
                    $code = $smsData['code'];
                    DB::table('transactions')->where('id', $activation->id)
                        ->update(['sms_code' => $code, 'status' => 'completed', 'updated_at' => now()]);
                    try {
                        Http::timeout(10)->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                            ->withOptions(['verify' => false])
                            ->get("https://5sim.net/v1/user/finish/{$activation->request_id}");
                    } catch (\Exception $e) {
                        Log::warning('Failed to finish 5sim activation', ['error' => $e->getMessage()]);
                    }
                    return response()->json(['success' => true, 'sms_code' => $code, 'status' => 'completed']);
                }
            }

            $status = $data['status'] ?? 'PENDING';
            if ($status === 'CANCELED' || $status === 'TIMEOUT') {
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'expired', 'updated_at' => now()]);
                return response()->json(['success' => false, 'status' => 'expired', 'message' => 'Activation expired']);
            }

            return response()->json(['success' => false, 'status' => 'pending', 'message' => 'Waiting for SMS']);
        } catch (\Exception $e) {
            Log::error('5sim SMS fetch error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return response()->json(['success' => false, 'message' => 'Error fetching SMS', 'status' => 'pending'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // SOCIAL BOOST STATUS
    // ═══════════════════════════════════════════════════════════

    public function getSocialStatus(Request $request)
    {
        $request->validate(['transaction_id' => 'required|integer']);

        try {
            $transaction = DB::table('transactions')
                ->where('id', $request->transaction_id)
                ->where('user_id', auth()->id())
                ->where('type', 'social_boost')
                ->first();

            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
            }

            if (empty($transaction->request_id)) {
                return response()->json(['success' => false, 'message' => 'Order ID not found', 'status' => $transaction->status]);
            }

            $statusResponse = $this->mtpHelper->getOrderStatus($transaction->request_id);

            if (!$statusResponse || isset($statusResponse['error'])) {
                return response()->json(['success' => false, 'message' => $statusResponse['error'] ?? 'Unable to fetch status', 'status' => $transaction->status]);
            }

            $mtpStatus   = $statusResponse['status'] ?? 'Pending';
            $localStatus = $this->mtpHelper->mapStatus($mtpStatus);
            if ($localStatus === 'completed') $localStatus = 'success';

            DB::table('transactions')->where('id', $transaction->id)->update([
                'status'     => $localStatus,
                'response'   => json_encode(array_merge(
                    json_decode($transaction->response, true) ?? [],
                    [
                        'mtp_status'   => $mtpStatus,
                        'start_count'  => $statusResponse['start_count'] ?? null,
                        'remains'      => $statusResponse['remains'] ?? null,
                        'charge'       => $statusResponse['charge'] ?? null,
                        'last_checked' => now()->toIso8601String(),
                    ]
                )),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data'    => [
                    'mtp_status'   => $mtpStatus,
                    'local_status' => $localStatus,
                    'start_count'  => $statusResponse['start_count'] ?? null,
                    'remains'      => $statusResponse['remains'] ?? null,
                    'charge'       => $statusResponse['charge'] ?? null,
                    'status_label' => $this->getSocialStatusLabel($localStatus),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching social boost status', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error checking status', 'status' => 'pending'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // CANCEL ACTIVATION
    // ═══════════════════════════════════════════════════════════

    public function cancelActivation(Request $request)
    {
        $request->validate(['activation_id' => 'required|integer']);
        $userId = Auth::check() ? Auth::id() : null;

        try {
            $result = DB::transaction(function () use ($request, $userId) {
                $activation = DB::table('transactions')
                    ->where('id', $request->activation_id)
                    ->where('user_id', $userId)
                    ->where('type', 'activation')
                    ->lockForUpdate()
                    ->first();

                if (!$activation) {
                    return ['status' => 404, 'success' => false, 'message' => 'Activation not found'];
                }

                $nonCancelable = ['completed', 'success', 'expired', 'cancelled', 'finished', 'refunded'];
                if (!empty($activation->sms_code) || in_array(strtolower($activation->status ?? ''), $nonCancelable, true)) {
                    return ['status' => 400, 'success' => false, 'message' => 'This activation cannot be cancelled (already completed or SMS received)'];
                }

                DB::table('transactions')->where('id', $activation->id)
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);

                return ['status' => 200, 'success' => true, 'activation' => $activation];
            });

            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => $result['message']], $result['status']);
            }

            $activation = $result['activation'];
            $provider   = $this->normalizeProvider($activation->channel);

            $cancelResult = $this->attemptProviderCancel($activation, $provider);
            if (!$cancelResult['success']) {
                Log::warning('Provider cancel failed — proceeding with refund anyway', [
                    'activation_id' => $activation->id, 'provider' => $provider,
                ]);
            }

            $refundResult = $this->processCancellationRefund($activation, $provider);

            if ($refundResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Activation cancelled successfully. ₦' . number_format($activation->amount, 2) . ' has been refunded to your wallet.',
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Cancellation succeeded but refund failed. Please contact support.'], 500);
        } catch (\Exception $e) {
            Log::error('Error in cancelActivation', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred while cancelling'], 500);
        }
    }

    private function attemptProviderCancel($activation, string $provider): array
    {
        if ($provider === 'provider2') {
            return $this->cancelSmsBower($activation);
        } elseif ($provider === 'provider3') {
            return $this->cancelSmsBus($activation);
        } elseif ($provider === 'provider1') {
            return $this->cancel5sim($activation);
        }
        return ['success' => false, 'reason' => 'Unknown provider'];
    }

    private function cancelSmsBower($activation): array
    {
        try {
            $result = SmsBowerHelper::cancelActivation((string) $activation->request_id);
            if ($result) {
                return ['success' => true];
            }
            // Try setStatus 8 regardless (cancel)
            SmsBowerHelper::setStatus((string) $activation->request_id, 8);
            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('SMSBower cancel error', ['error' => $e->getMessage(), 'request_id' => $activation->request_id]);
            return ['success' => false, 'message' => 'Error cancelling request'];
        }
    }

    private function cancelSmsBus($activation): array
    {
        $token = env('SMSBUS_TOKEN');
        if (!$token) return ['success' => false, 'message' => 'Service token not configured'];

        try {
            $response = Http::timeout(15)->withOptions(['verify' => false])
                ->get('https://sms-bus.com/api/control/cancel', [
                    'token'      => $token,
                    'request_id' => $activation->request_id,
                ]);

            if ($response->failed()) return ['success' => false, 'message' => 'Service temporarily unavailable'];
            $data = $response->json();
            if (isset($data['code']) && $data['code'] === 200) return ['success' => true];
            if (isset($data['code']) && $data['code'] === 50103) return ['success' => false, 'message' => 'Request already closed'];
            return ['success' => false, 'message' => $data['message'] ?? 'Failed to cancel'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error cancelling request'];
        }
    }

    private function cancel5sim($activation): array
    {
        $token = env('FIVESIM_TOKEN');
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
                ->withOptions(['verify' => false])
                ->get("https://5sim.net/v1/user/cancel/{$activation->request_id}");
            if ($response->successful()) return ['success' => true];
            $data = $response->json();
            return ['success' => false, 'message' => $data['message'] ?? 'Failed to cancel'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error cancelling request'];
        }
    }

    private function processCancellationRefund($activation, string $provider): array
    {
        try {
            return DB::transaction(function () use ($activation, $provider) {
                $alreadyRefunded = DB::table('transactions')
                    ->where('type', 'credit')->where('channel', 'Refund')
                    ->where('user_id', $activation->user_id)->where('amount', $activation->amount)
                    ->whereRaw("JSON_EXTRACT(response, '$.original_activation_id') = ?", [$activation->id])
                    ->exists();

                if ($alreadyRefunded) return ['success' => true];

                $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->lockForUpdate()->first();
                if (!$wallet) return ['success' => false, 'reason' => 'Wallet not found'];

                DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                $newBalance = (float) DB::table('wallets')->where('user_id', $activation->user_id)->value('balance');

                DB::table('transactions')->insert([
                    'reference'     => 'REF_' . strtoupper(\Illuminate\Support\Str::random(10)),
                    'user_id'       => $activation->user_id,
                    'type'          => 'credit',
                    'channel'       => 'Refund',
                    'provider'      => $activation->service_name ?? $activation->provider ?? 'System',
                    'phone'         => $activation->phone ?? null,
                    'recipient'     => null,
                    'amount'        => $activation->amount,
                    'status'        => 'success',
                    'description'   => "User-initiated cancellation refund for {$provider} — {$activation->phone}",
                    'response'      => json_encode(['original_activation_id' => $activation->id, 'reason' => 'User cancelled']),
                    'balance_after' => $newBalance,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                return ['success' => true];
            });
        } catch (\Exception $e) {
            Log::error('processCancellationRefund failed', ['activation_id' => $activation->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'reason' => 'Refund processing failed'];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // EXPIRE ACTIVATION
    // ═══════════════════════════════════════════════════════════

    public function expireActivation(Request $request)
    {
        $request->validate(['activation_id' => 'required|integer']);

        try {
            $userId     = Auth::check() ? Auth::id() : null;
            $activation = DB::table('transactions')
                ->where('id', $request->activation_id)->where('user_id', $userId)->where('type', 'activation')->first();

            if (!$activation) {
                return response()->json(['success' => false, 'message' => 'Activation not found'], 404);
            }

            if (!empty($activation->sms_code) || strtolower($activation->status ?? '') !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Activation cannot be expired'], 400);
            }

            DB::transaction(function () use ($activation) {
                DB::table('transactions')->where('id', $activation->id)->update(['status' => 'expired', 'updated_at' => now()]);
                $wallet = DB::table('wallets')->where('user_id', $activation->user_id)->first();
                if ($wallet) {
                    DB::table('wallets')->where('user_id', $activation->user_id)->increment('balance', $activation->amount);
                    DB::table('transactions')->insert([
                        'reference'   => 'REF_' . strtoupper(Str::random(10)),
                        'user_id'     => $activation->user_id,
                        'type'        => 'credit',
                        'channel'     => 'Refund',
                        'provider'    => $activation->service_name ?? $activation->provider,
                        'amount'      => $activation->amount,
                        'status'      => 'success',
                        'description' => 'Activation expired refund for ' . $activation->phone,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            });

            return response()->json(['success' => true, 'status' => 'expired', 'message' => 'Activation expired and refunded']);
        } catch (\Exception $e) {
            Log::error('Error expiring activation', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error expiring activation'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE — logs & eSIMs
    // ═══════════════════════════════════════════════════════════

    private function fetchLogsForUser($userId)
    {
        return \App\Models\Transaction::where('user_id', $userId)
            ->where('type', 'logs')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $response = is_array($t->response) ? $t->response : (json_decode($t->response, true) ?? []);

                $country     = null;
                $countryCode = null;
                $productId   = $response['product']['id'] ?? null;
                if ($productId) {
                    try {
                        $p = \App\Models\Product::find($productId);
                        if ($p) { $country = $p->country ?? null; $countryCode = $p->country_code ?? null; }
                    } catch (\Throwable $e) {}
                }

                $credentials = [];
                if (!empty($response['credentials']) && is_array($response['credentials'])) {
                    $credentials = array_values($response['credentials']);
                } elseif (!empty($response['login_details'])) {
                    $credentials = [trim($response['login_details'])];
                }

                $fulfilledCount = count(array_filter($credentials, fn ($v) => trim((string) $v) !== ''));
                $quantity       = max(1, intval($response['quantity'] ?? ($fulfilledCount ?: 1)));

                return [
                    'id'             => $t->id,
                    'reference'      => $t->reference,
                    'service_name'   => $t->service_name ?? $t->recipient ?? 'Logs',
                    'product_name'   => $response['product']['name'] ?? $response['product_name'] ?? null,
                    'amount'         => '₦' . number_format($t->amount ?? 0, 2),
                    'status'         => ucfirst($t->status ?? 'pending'),
                    'date'           => \Carbon\Carbon::parse($t->created_at)->format('M d, Y h:i A'),
                    'login_details'  => $response['login_details'] ?? null,
                    'credentials'    => $credentials,
                    'quantity'       => $quantity,
                    'fulfilled_count'=> $fulfilledCount,
                    'notes'          => $response['notes'] ?? null,
                    'country'        => $country,
                    'country_code'   => $countryCode,
                ];
            });
    }

    private function fetchEsimsForUser($userId)
    {
        $transactions = \App\Models\Transaction::where('user_id', $userId)
            ->where('type', 'esim')
            ->with('esimUpload')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($transactions->isEmpty()) return collect([]);

        $decoded = $transactions->map(function ($t) {
            $response = is_array($t->response) ? $t->response : (json_decode($t->response, true) ?? []);
            return ['txn' => $t, 'response' => $response];
        });

        $namesNeedingCode = $decoded
            ->filter(fn ($pair) => empty($pair['response']['country_code'] ?? null))
            ->map(fn ($pair) => $pair['response']['country'] ?? $pair['txn']->provider ?? null)
            ->filter()->unique()->values();

        $codeMap = [];
        if ($namesNeedingCode->isNotEmpty()) {
            $codeMap = \App\Models\EsimCountry::whereIn('country_name', $namesNeedingCode)
                ->pluck('country_code', 'country_name')->toArray();
        }

        return $decoded->map(function ($pair) use ($codeMap) {
            $t        = $pair['txn'];
            $response = $pair['response'];

            $countryName = $response['country'] ?? $t->provider ?? 'Unknown';
            $countryCode = $response['country_code'] ?? ($codeMap[$countryName] ?? null);
            $packageName = $response['package'] ?? null;
            $validity    = $response['validity'] ?? null;

            $imageUrls = [];
            if ($t->esimUpload) {
                $rawImages = $t->esimUpload->images;
                if (is_string($rawImages)) $rawImages = json_decode($rawImages, true) ?? [];
                $rawImages = is_array($rawImages) ? $rawImages : [];
                foreach ($rawImages as $img) {
                    if (empty($img)) continue;
                    $img = str_replace(['\\/', '\\'], '/', $img);
                    $img = ltrim($img, '/');
                    if (str_starts_with($img, 'storage/')) {
                        $imageUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url(substr($img, strlen('storage/')));
                    } elseif (str_starts_with($img, 'uploads/')) {
                        $imageUrls[] = asset($img);
                    } else {
                        $imageUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($img);
                    }
                }
            }

            $isFulfilled = (bool) $t->esimUpload;
            $rawStatus   = $isFulfilled ? 'success' : strtolower($t->status ?? 'pending');

            return [
                'id'           => $t->id,
                'reference'    => $t->reference,
                'country_name' => $countryName,
                'country_code' => $countryCode ? strtolower($countryCode) : null,
                'package'      => $packageName,
                'validity'     => $validity,
                'amount'       => '₦' . number_format($t->amount ?? 0, 2),
                'status'       => $this->getEsimStatusLabel($rawStatus),
                'raw_status'   => $rawStatus,
                'date'         => Carbon::parse($t->created_at)->format('M d, Y h:i A'),
                'esim_upload'  => $t->esimUpload ? [
                    'number'  => $t->esimUpload->number,
                    'details' => $t->esimUpload->details,
                    'images'  => $imageUrls,
                ] : null,
            ];
        })->values();
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Normalize channel field → internal provider slug
     * Old data (channel='provider2') now maps to 'provider3' (SMS-Bus)
     * New data (channel='provider2') stores SMSBower
     * We distinguish by looking at the provider column too if needed.
     *
     * Strategy:
     * - 'provider1' | '5sim'       → provider1
     * - 'provider2'                → provider2  (SMSBower — new)
     * - 'provider3' | 'sms-bus' | 'smsbus' → provider3 (SMS-Bus — old)
     *
     * NOTE: Old rows that had channel='provider2' for SMS-Bus will now be
     * treated as provider3 — but since we changed the channel value when
     * saving SMS-Bus orders to 'provider3', existing old rows need a one-time
     * migration (see migration file). For now we use the 'provider' column
     * value as a fallback differentiator.
     */
    public function normalizeProvider(?string $channel): string
    {
        $channel = strtolower($channel ?? 'provider3');

        if ($channel === 'provider1' || $channel === '5sim') return 'provider1';
        if ($channel === 'provider2') return 'provider2';   // SMSBower
        if ($channel === 'provider3' || $channel === 'sms-bus' || $channel === 'smsbus') return 'provider3';

        return 'provider3'; // safe default for legacy rows
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending'   => 'Waiting for SMS',
            'completed' => 'Completed',
            'success'   => 'Completed',
            'expired'   => 'Expired',
            'cancelled' => 'Cancelled',
            'failed'    => 'Failed',
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    private function getSocialStatusLabel(string $status): string
    {
        $labels = [
            'pending'    => 'Processing',
            'processing' => 'In Progress',
            'success'    => 'Completed',
            'completed'  => 'Completed',
            'partial'    => 'Partial',
            'failed'     => 'Failed',
            'cancelled'  => 'Cancelled',
            'refunded'   => 'Refunded',
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    private function getEsimStatusLabel(string $status): string
    {
        $labels = [
            'pending'   => 'Preparing',
            'success'   => 'Ready',
            'completed' => 'Ready',
            'failed'    => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded'  => 'Refunded',
        ];
        return $labels[$status] ?? ucfirst($status);
    }
}