<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Helpers\PinHelper;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BettingController extends Controller
{
    public function show()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();

        $bettingCompanies = Cache::remember('clubkonnect_betting_companies', 3600, function () {
            try {
                $response = ClubKonnectHelper::getBettingCompanies();
                return $this->normalizeBettingCompanies($response);
            } catch (\Exception $e) {
                Log::error('Error fetching betting companies: ' . $e->getMessage());
                return $this->getDefaultBettingCompanies();
            }
        });

        if (empty($bettingCompanies)) {
            $bettingCompanies = $this->getDefaultBettingCompanies();
        }

        return view('users.betting', compact('wallet', 'bettingCompanies'));
    }

    private function normalizeBettingCompanies(array $raw): array
    {
        $normalized = [];

        // V2 returns: { "BETTING_COMPANY": [ { "PRODUCT_CODE": "...", "PRODUCT_NAME": "...", ... } ] }
        $entries = $raw['BETTING_COMPANY'] ?? $raw['SERVICES'] ?? $raw;

        if (!is_array($entries)) return [];

        if (array_is_list($entries)) {
            // Flat array — the actual V2 format
            foreach ($entries as $company) {
                if (!is_array($company)) continue;

                $code = $company['PRODUCT_CODE'] ?? $company['CODE'] ?? $company['code'] ?? null;
                // PRODUCT_NAME is the correct field in V2
                $name = $company['PRODUCT_NAME'] ?? $company['NAME'] ?? $company['DESCRIPTION'] ?? $company['name'] ?? $code;
                $min  = (int) ($company['MINAMOUNT'] ?? $company['MIN_AMOUNT'] ?? 100);
                $max  = (int) ($company['MAXAMOUNT'] ?? $company['MAX_AMOUNT'] ?? 50000);

                if (!$code) continue;

                $normalized[] = [
                    'code' => (string) $code,
                    'name' => (string) $name,
                    'min'  => $min,
                    'max'  => $max,
                ];
            }
        } else {
            // Keyed object fallback: { "BET9JA": [ {...} ] }
            foreach ($entries as $key => $wrapper) {
                if (!is_array($wrapper)) continue;
                $company = isset($wrapper[0]) ? $wrapper[0] : $wrapper;
                if (!is_array($company)) continue;

                $code = $company['PRODUCT_CODE'] ?? $company['CODE'] ?? strtolower($key);
                $name = $company['PRODUCT_NAME'] ?? $company['NAME'] ?? $company['DESCRIPTION'] ?? $key;
                $min  = (int) ($company['MINAMOUNT'] ?? 100);
                $max  = (int) ($company['MAXAMOUNT'] ?? 50000);

                $normalized[] = [
                    'code' => (string) $code,
                    'name' => (string) $name,
                    'min'  => $min,
                    'max'  => $max,
                ];
            }
        }

        // Merge with default companies — add any defaults whose code isn't already in API results
        $apiCodes    = array_column($normalized, 'code');
        $defaults    = $this->getDefaultBettingCompanies();

        foreach ($defaults as $default) {
            if (!in_array($default['code'], $apiCodes)) {
                $normalized[] = $default;
            }
        }

        // Sort alphabetically by name
        usort($normalized, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $normalized;
    }


    public function fund_account(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : $request->all();

        // === 1️⃣ VALIDATION ===
        $validator = Validator::make($data, [
            'betting_company' => 'required|string|min:3|max:50',
            'customer_id' => 'required|string|min:3|max:50',
            'amount' => 'required|numeric|min:100|max:50000',
            'pin' => 'required|string|digits:4',
        ]);

        if ($validator->fails()) {
            Log::warning('Betting validation failed', [
                'user_id' => auth()->id(),
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'title' => 'Invalid Request',
                'message' => 'Please check all fields and try again.',
                'details' => $this->formatValidationErrors($validator->errors()),
                'suggestion' => 'Ensure betting company, customer ID, amount, and PIN are correct.',
            ], 422);
        }

        // === 2️⃣ PIN VERIFICATION ===
        try {
            PinHelper::verifyPin($data['pin']);
        } catch (ValidationException $e) {
            Log::warning('Betting PIN verification failed', [
                'user_id' => auth()->id(),
                'reference' => 'PIN-' . time(),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'title' => 'Invalid PIN',
                'message' => 'Your transaction PIN is not correct.',
                'details' => 'Please enter your 4-digit transaction PIN.',
                'suggestion' => 'If you forgot your PIN, visit Settings → Security to reset it.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // === 3️⃣ WALLET CHECK ===
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                Log::error('Wallet not found for betting', ['user_id' => auth()->id()]);

                return response()->json([
                    'success' => false,
                    'error_type' => 'system_error',
                    'title' => 'Account Error',
                    'message' => 'We could not access your wallet. Please contact support.',
                    'details' => 'Wallet initialization failed.',
                    'suggestion' => 'Contact our support team if this persists.',
                ], 500);
            }

            // === 4️⃣ BALANCE CHECK ===
            if ($wallet->balance < $data['amount']) {
                DB::rollBack();

                $shortfall = $data['amount'] - $wallet->balance;

                Log::info('Insufficient balance for betting', [
                    'user_id' => auth()->id(),
                    'required' => $data['amount'],
                    'available' => $wallet->balance,
                    'shortfall' => $shortfall,
                ]);

                return response()->json([
                    'success' => false,
                    'error_type' => 'insufficient_balance',
                    'title' => 'Insufficient Balance',
                    'message' => 'Your wallet balance is not enough for this transaction.',
                    'details' => [
                        'Required Amount' => '₦' . number_format($data['amount'], 2),
                        'Available Balance' => '₦' . number_format($wallet->balance, 2),
                        'Shortfall' => '₦' . number_format($shortfall, 2),
                    ],
                    'suggestion' => 'Please fund your wallet with at least ₦' . number_format($shortfall, 0) . ' to proceed.',
                ], 400);
            }

            // === 5️⃣ DEDUCT FUNDS ===
            $reference = 'BET-' . strtoupper(Str::random(10)) . '-' . time();
            $oldBalance = $wallet->balance;
            $wallet->decrement('balance', $data['amount']);

            Log::info('Wallet debited for betting', [
                'user_id' => auth()->id(),
                'reference' => $reference,
                'amount' => $data['amount'],
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->fresh()->balance,
            ]);

            // === 6️⃣ VERIFY CUSTOMER ===
            try {
                $verifyResponse = ClubKonnectHelper::verifyBettingCustomer(
                    $data['betting_company'],
                    $data['customer_id']
                );

                Log::debug('Customer verification response', [
                    'reference' => $reference,
                    'response' => $verifyResponse,
                ]);

                if (!$verifyResponse || 
                    (isset($verifyResponse['customer_name']) && 
                     (str_contains(strtolower($verifyResponse['customer_name']), 'error') ||
                      str_contains(strtolower($verifyResponse['customer_name']), 'invalid')))) {
                    
                    $wallet->increment('balance', $data['amount']);
                    DB::rollBack();

                    Log::warning('Customer verification failed', [
                        'reference' => $reference,
                        'betting_company' => $data['betting_company'],
                        'customer_id' => $data['customer_id'],
                    ]);

                    return response()->json([
                        'success' => false,
                        'error_type' => 'validation',
                        'title' => 'Invalid Customer ID',
                        'message' => 'The customer ID you provided is not valid for this betting provider.',
                        'details' => 'Please verify your customer/account ID with your betting provider.',
                        'suggestion' => 'Double-check your customer ID and try again.',
                    ], 400);
                }
            } catch (\Exception $e) {
                $wallet->increment('balance', $data['amount']);
                DB::rollBack();

                Log::error('Customer verification exception', [
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'error_type' => 'service_error',
                    'title' => 'Verification Temporarily Unavailable',
                    'message' => 'We are unable to verify your customer ID at the moment.',
                    'details' => 'The verification service is temporarily unavailable.',
                    'suggestion' => 'Please try again in a few moments.',
                ], 503);
            }

            // === 7️⃣ PLACE BET ===
            $response = ClubKonnectHelper::placeBet(
                $data['betting_company'],
                $data['customer_id'],
                $data['amount'],
                $reference,
                route('clubkonnect.callback', [], true)
            );

            Log::info('Betting API call initiated', [
                'reference' => $reference,
                'betting_company' => $data['betting_company'],
                'amount' => $data['amount'],
            ]);

            // --- ✅ Normalize response structure ---
            $response = is_array($response) ? $response : json_decode($response, true);
            if (!is_array($response)) {
                $response = ['status' => false, 'message' => 'Invalid API response'];
            }

            $statusCode = $response['statuscode'] ?? $response['StatusCode'] ?? null;
            $orderId = $response['orderid'] ?? $response['OrderID'] ?? $reference;
            $rawStatus = strtoupper(trim($response['status'] ?? $response['Status'] ?? ''));
            $message = $response['message'] ?? $response['Message'] ?? 'Unknown response';
            $remark = $response['remark'] ?? $response['Remark'] ?? $message;

            // --- 🟢 Determine transaction state ---
            $status = match (true) {
                in_array($statusCode, ['200', 200]),
                in_array($rawStatus, ['SUCCESS', 'SUCCESSFUL', 'ORDER_COMPLETED']) => 'success',

                in_array($statusCode, ['100', 100]),
                in_array($rawStatus, ['PENDING', 'ORDER_RECEIVED', 'ORDER_ONHOLD']) => 'pending',

                default => 'failed',
            };

            Log::info('API Response processed', [
                'reference' => $reference,
                'status' => $status,
                'statusCode' => $statusCode,
                'rawStatus' => $rawStatus,
            ]);

            $transaction = Transaction::create([
                'reference' => $reference,
                'request_id' => $orderId,
                'user_id' => auth()->id(),
                'type' => 'betting',
                'channel' => 'ClubKonnect',
                'provider' => $this->getBettingProviderName($data['betting_company']),
                'service_id' => $data['betting_company'],
                'service_name' => $this->getBettingProviderName($data['betting_company']),
                'account' => $data['customer_id'],
                'amount' => $data['amount'],
                'status' => $status,
                'response' => json_encode($response),
                'description' => "Betting account funding for {$this->getBettingProviderName($data['betting_company'])}",
            ]);

            // --- 🧾 SUCCESS or PENDING - Commit transaction ---
            if (in_array($status, ['success', 'pending'])) {
                DB::commit();

                Log::info('Betting transaction successful', [
                    'reference' => $reference,
                    'order_id' => $orderId,
                    'status' => $status,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'title' => $status === 'success' ? 'Funding Successful' : 'Funding Received',
                    'message' => $status === 'success'
                        ? 'Your betting account has been funded successfully!'
                        : 'Your betting fund request has been received and is being processed.',
                    'data' => [
                        'reference' => $reference,
                        'order_id' => $orderId,
                        'transaction_status' => $status,
                        'amount' => $data['amount'],
                        'new_balance' => $wallet->fresh()->balance,
                        'provider' => $this->getBettingProviderName($data['betting_company']),
                        'customer_id' => $data['customer_id'],
                        'timestamp' => now()->toDateTimeString(),
                    ],
                ], 200);
            }

            // --- 🔴 FAILED - Refund and rollback ---
            $wallet->increment('balance', $data['amount']);
            DB::commit();

            Log::warning('Betting transaction failed', [
                'reference' => $reference,
                'status_code' => $statusCode,
                'api_status' => $rawStatus,
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'service_error',
                'title' => 'Funding Failed',
                'message' => 'We could not process your betting account funding at this time.',
                'details' => 'The provider returned an error: ' . $remark,
                'suggestion' => 'Your funds have been refunded. Please try again or contact support if the issue persists.',
                'data' => [
                    'reference' => $reference,
                    'wallet_refunded' => true,
                    'refunded_amount' => $data['amount'],
                    'current_balance' => $wallet->fresh()->balance,
                ],
            ], 503);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Betting transaction exception', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'system_error',
                'title' => 'Something Went Wrong',
                'message' => 'An unexpected error occurred while processing your request.',
                'details' => 'Our team has been notified of this issue.',
                'suggestion' => 'Please try again later or contact support if the problem continues.',
            ], 500);
        }
    }

    /**
     * Format validation errors in a user-friendly way
     */
    private function formatValidationErrors($errors)
    {
        $formatted = [];
        foreach ($errors->messages() as $field => $messages) {
            $formatted[$this->getFieldLabel($field)] = $messages[0] ?? 'Invalid input';
        }
        return $formatted;
    }

    /**
     * Get user-friendly field labels
     */
    private function getFieldLabel($field)
    {
        $labels = [
            'betting_company' => 'Betting Provider',
            'customer_id' => 'Customer/Account ID',
            'amount' => 'Amount',
            'pin' => 'Transaction PIN',
        ];

        return $labels[$field] ?? $field;
    }


    public function handleCallback(Request $request)
    {
        Log::info('ClubKonnect callback received', $request->all());

        try {
            $orderId = $request->query('OrderID');
            $statusCode = $request->query('StatusCode');
            $orderStatus = strtoupper($request->query('OrderStatus', ''));
            $orderRemark = $request->query('OrderRemark', 'No remark provided');

            if (!$orderId) {
                Log::warning('Callback received without OrderID', $request->all());
                return response()->json(['status' => 'error', 'message' => 'OrderID missing'], 400);
            }

            $transaction = Transaction::where('request_id', $orderId)
                ->where('type', 'betting')
                ->first();

            if (!$transaction) {
                Log::warning('Transaction not found for OrderID', ['order_id' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $oldStatus = $transaction->status;

            Log::info('Processing callback', [
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'order_id' => $orderId,
                'previous_status' => $oldStatus,
                'status_code' => $statusCode,
                'order_status' => $orderStatus
            ]);

            DB::beginTransaction();

            // ✅ Define success/pending/failure conditions
            $isSuccess = in_array($statusCode, ['200', 200]) ||
                        in_array($orderStatus, ['SUCCESS', 'SUCCESSFUL', 'ORDER_COMPLETED']);

            $isPending = in_array($statusCode, ['100', 100]) ||
                        in_array($orderStatus, ['ORDER_RECEIVED', 'ORDER_ONHOLD', 'PENDING']);

            $isFailed = !$isSuccess && !$isPending;

            // 🟢 SUCCESS
            if ($isSuccess) {
                if ($oldStatus !== 'success') {
                    $transaction->update([
                        'status' => 'success',
                        'response' => json_encode(array_merge(
                            json_decode($transaction->response, true) ?? [],
                            [
                                'callback_status' => $orderStatus,
                                'callback_remark' => $orderRemark,
                                'callback_received_at' => now()->toDateTimeString(),
                            ]
                        ))
                    ]);
                    Log::info('Transaction marked as successful', [
                        'transaction_id' => $transaction->id,
                        'reference' => $transaction->reference,
                    ]);
                }

            // 🟡 PENDING
            } elseif ($isPending) {
                $transaction->update([
                    'status' => 'pending',
                    'response' => json_encode(array_merge(
                        json_decode($transaction->response, true) ?? [],
                        [
                            'callback_status' => $orderStatus,
                            'callback_remark' => $orderRemark,
                            'callback_received_at' => now()->toDateTimeString(),
                        ]
                    ))
                ]);
                Log::info('Transaction marked as pending', [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference,
                ]);

            // 🔴 FAILED
            } else {
                $transaction->update([
                    'status' => 'failed',
                    'response' => json_encode(array_merge(
                        json_decode($transaction->response, true) ?? [],
                        [
                            'callback_status' => $orderStatus,
                            'callback_remark' => $orderRemark,
                            'callback_received_at' => now()->toDateTimeString(),
                        ]
                    ))
                ]);

                // Refund if not already failed
                if ($oldStatus !== 'failed') {
                    $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();
                    if ($wallet) {
                        $wallet->increment('balance', $transaction->amount);
                        Log::info('Amount refunded due to failed transaction', [
                            'transaction_id' => $transaction->id,
                            'user_id' => $transaction->user_id,
                            'amount' => $transaction->amount,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Callback processed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Callback processing error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Verify customer ID before transaction
     */
    public function verifyCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'betting_company' => 'required|string',
            'customer_id' => 'required|string|min:3|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $response = ClubKonnectHelper::verifyBettingCustomer(
                $request->betting_company,
                $request->customer_id
            );

            if (isset($response['customer_name']) && 
                !str_contains(strtolower($response['customer_name']), 'error') &&
                !str_contains(strtolower($response['customer_name']), 'invalid')) {
                
                return response()->json([
                    'status' => true,
                    'message' => 'Customer verified successfully',
                    'data' => [
                        'customer_name' => $response['customer_name']
                    ]
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid Customer ID'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Customer verification error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Verification failed. Please try again.'
            ], 500);
        }
    }

    private function getBettingProviderName($code)
    {
        $providers = [
            'msport' => 'MSport',
            'naijabet' => 'NaijaBet',
            'nairabet' => 'NairaBet',
            'bet9ja-agent' => 'Bet9ja Agent',
            'betland' => 'BetLand',
            'betlion' => 'BetLion',
            'supabet' => 'SupaBet',
            'bet9ja' => 'Bet9ja',
            'bangbet' => 'BangBet',
            'betking' => 'BetKing',
            '1xbet' => '1XBet',
            'betway' => 'Betway',
            'merrybet' => 'MerryBet',
            'mlotto' => 'MLotto',
            'western-lotto' => 'Western Lotto',
            'hallabet' => 'HallaBet',
            'green-lotto' => 'Green Lotto',
        ];

        return $providers[strtolower($code)] ?? ucfirst($code);
    }

    private function getDefaultBettingCompanies(): array
    {
        return [
            ['code' => 'prd-bet9ja',          'name' => 'Bet9ja',          'min' => 100, 'max' => 50000],
            ['code' => 'prd-betking',          'name' => 'BetKing',         'min' => 100, 'max' => 50000],
            ['code' => 'prd-betway',           'name' => 'Betway',          'min' => 100, 'max' => 50000],
            ['code' => 'prd-nairabet',         'name' => 'NairaBet',        'min' => 100, 'max' => 50000],
            ['code' => 'prd-merrybet',         'name' => 'MerryBet',        'min' => 100, 'max' => 50000],
            ['code' => 'prd-msport',           'name' => 'MSport',          'min' => 100, 'max' => 50000],
            ['code' => 'prd-bangbet',          'name' => 'BangBet',         'min' => 100, 'max' => 50000],
            ['code' => 'prd-supabet',          'name' => 'SupaBet',         'min' => 100, 'max' => 50000],
            ['code' => 'prd-betlion',          'name' => 'BetLion',         'min' => 100, 'max' => 50000],
            ['code' => 'prd-paripesa',         'name' => 'Paripesa',        'min' => 100, 'max' => 50000],
            ['code' => 'prd-22bet',            'name' => '22Bet',           'min' => 100, 'max' => 50000],
            ['code' => 'prd-betwinner',        'name' => 'BetWinner',       'min' => 100, 'max' => 50000],
            ['code' => 'prd-cloudbet',         'name' => 'Cloudbet',        'min' => 100, 'max' => 50000],
            ['code' => 'prd-western-lotto',    'name' => 'Western Lotto',   'min' => 100, 'max' => 50000],
            ['code' => 'prd-green-lotto',      'name' => 'Green Lotto',     'min' => 100, 'max' => 50000],
            ['code' => 'prd-hallabet',         'name' => 'HallaBet',        'min' => 100, 'max' => 50000],
            ['code' => 'prd-mlotto',           'name' => 'MLotto',          'min' => 100, 'max' => 50000],
            ['code' => 'prd-bet9ja-agent',     'name' => 'Bet9ja Agent',    'min' => 100, 'max' => 50000],
        ];
    }

    
}