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

    $bettingCompanies = Cache::remember('nellobytes_betting_companies', 3600, function () {
        try {
            $response = ClubKonnectHelper::getBettingCompanies();

            if (isset($response['BETTING_COMPANY']) && is_array($response['BETTING_COMPANY'])) {
                return $response['BETTING_COMPANY'];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching betting companies: ' . $e->getMessage());
            return [];
        }
    });
    // dd($bettingCompanies);
    return view('users.betting', compact('wallet', 'bettingCompanies'));
}


    public function fund_account(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($data, [
            'betting_company' => 'required|string',
            'customer_id' => 'required|string|min:3|max:50',
            'amount' => 'required|numeric|min:100|max:50000',
            'pin' => 'required|string|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            PinHelper::verifyPin($data['pin']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid PIN',
                'errors' => $e->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Wallet not found. Please contact support.',
                ], 400);
            }

            if ($wallet->balance < $data['amount']) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance. Please fund your wallet.',
                ], 400);
            }

            $reference = 'BET-' . strtoupper(Str::random(10)) . '-' . time();
            $wallet->decrement('balance', $data['amount']);

            $response = ClubKonnectHelper::placeBet(
                $data['betting_company'],
                $data['customer_id'],
                $data['amount'],
                $reference,
                route('clubkonnect.callback', [], true)
            );

            Log::info('ClubKonnect Betting Response', ['reference' => $reference, 'response' => $response]);

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

            Log::info('Transaction status resolved', [
                'reference' => $reference,
                'status' => $status,
                'statusCode' => $statusCode,
                'rawStatus' => $rawStatus,
                'remark' => $remark,
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

            // --- 🧾 Commit or refund ---
            if (in_array($status, ['success', 'pending'])) {
                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => $status === 'success'
                        ? 'Betting account funded successfully!'
                        : 'Your betting request has been received and is being processed.',
                    'data' => [
                        'reference' => $reference,
                        'order_id' => $orderId,
                        'transaction_status' => $status,
                        'new_balance' => $wallet->fresh()->balance,
                    ],
                ]);
            }

            // Refund for failed transaction
            $wallet->increment('balance', $data['amount']);
            DB::commit();

            return response()->json([
                'status' => false,
                'message' => 'Betting funding failed: ' . $remark,
                'data' => [
                    'reference' => $reference,
                    'response' => $response,
                ],
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Betting transaction exception: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.',
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                ] : null,
            ], 500);
        }
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

    private function getDefaultBettingCompanies()
    {
        // Fallback betting companies if API fails
        return [
            ['code' => 'bet9ja', 'name' => 'Bet9ja'],
            ['code' => 'betking', 'name' => 'BetKing'],
            ['code' => '1xbet', 'name' => '1XBet'],
            ['code' => 'nairabet', 'name' => 'NairaBet'],
            ['code' => 'betway', 'name' => 'Betway'],
            ['code' => 'merrybet', 'name' => 'MerryBet'],
            ['code' => 'msport', 'name' => 'MSport'],
            ['code' => 'naijabet', 'name' => 'NaijaBet'],
            ['code' => 'bangbet', 'name' => 'BangBet'],
            ['code' => 'supabet', 'name' => 'SupaBet'],
            ['code' => 'betland', 'name' => 'BetLand'],
            ['code' => 'betlion', 'name' => 'BetLion'],
            ['code' => 'mlotto', 'name' => 'MLotto'],
            ['code' => 'green-lotto', 'name' => 'Green Lotto'],
            ['code' => 'western-lotto', 'name' => 'Western Lotto'],
            ['code' => 'hallabet', 'name' => 'HallaBet'],
        ];
    }
}