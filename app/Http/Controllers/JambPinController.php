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
use Illuminate\Support\Facades\Log;

class JambPinController extends Controller
{
    /**
     * Display JAMB e-PIN purchase page
     */
    public function show()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();

        // Fetch available JAMB packages
        $jambPackages = Cache::remember('nellobytes_jamb_packages', 3600, function () {
            try {
                $response = ClubKonnectHelper::getJambPackages();

                if (isset($response['JAMB_PACKAGE']) && is_array($response['JAMB_PACKAGE'])) {
                    return $response['JAMB_PACKAGE'];
                }

                return $this->getDefaultJambPackages();
            } catch (\Exception $e) {
                Log::error('Error fetching JAMB packages: ' . $e->getMessage());
                return $this->getDefaultJambPackages();
            }
        });

        return view('users.jambPin', compact('wallet', 'jambPackages'));
    }

    /**
     * Purchase JAMB e-PIN
     */
    public function purchase(Request $request)
    {
        $data = $request->isJson() ? $request->json()->all() : $request->all();

        // === 1️⃣ VALIDATION ===
        $validator = Validator::make($data, [
            'exam_type' => 'required|string|in:utme-mock,utme-no-mock,de',
            'phone_number' => 'required|string|regex:/^0[7-9]\d{9}$/',
            'amount' => 'required|numeric|min:1000|max:50000',
            'pin' => 'required|string|digits:4',
            'profile_id' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            Log::warning('JAMB PIN validation failed', [
                'user_id' => auth()->id(),
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'title' => '❌ VALIDATION ERROR',
                'message' => 'Please check your input and try again.',
                'details' => $validator->errors()->toArray(),
                'suggestion' => 'Ensure exam type, phone number, amount, and PIN are correct.',
            ], 422);
        }

        // === 2️⃣ PIN VERIFICATION ===
        try {
            PinHelper::verifyPin($data['pin']);
        } catch (ValidationException $e) {
            Log::warning('JAMB PIN verification failed', [
                'user_id' => auth()->id(),
                'reference' => 'JAMB-' . time(),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'title' => '🔐 INVALID PIN',
                'message' => 'Invalid PIN. Please try again.',
                'details' => 'ℹ️ Please enter your 4-digit transaction PIN.',
                'suggestion' => 'If you forgot your PIN, visit Settings → Security to reset it.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // === 3️⃣ WALLET CHECK ===
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

            if (!$wallet) {
                DB::rollBack();

                Log::error('JAMB PIN wallet not found', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => false,
                    'error_type' => 'system_error',
                    'title' => '⚠️ WALLET ERROR',
                    'message' => 'Unable to access your wallet. Please contact support.',
                    'details' => 'Your wallet account could not be found.',
                    'suggestion' => 'Please try again or contact support if the problem persists.',
                ], 500);
            }

            // === 4️⃣ BALANCE CHECK ===
            if ($wallet->balance < $data['amount']) {
                DB::rollBack();

                $shortfall = $data['amount'] - $wallet->balance;

                Log::warning('JAMB PIN insufficient balance', [
                    'user_id' => auth()->id(),
                    'required' => $data['amount'],
                    'available' => $wallet->balance,
                    'shortfall' => $shortfall,
                ]);

                return response()->json([
                    'success' => false,
                    'error_type' => 'insufficient_balance',
                    'title' => '💰 INSUFFICIENT BALANCE',
                    'message' => 'Your wallet balance is not enough for this purchase.',
                    'details' => "Required: ₦" . number_format($data['amount'], 2) . 
                                 " | Available: ₦" . number_format($wallet->balance, 2) . 
                                 " | Shortfall: ₦" . number_format($shortfall, 2),
                    'suggestion' => "Please fund your wallet with at least ₦" . number_format($shortfall, 2),
                ], 400);
            }

            // === 5️⃣ VERIFY PROFILE ID (if provided) ===
            if (!empty($data['profile_id'])) {
                try {
                    $verifyResponse = ClubKonnectHelper::verifyJambProfileId(
                        $data['exam_type'],
                        $data['profile_id']
                    );

                    if (isset($verifyResponse['customer_name']) && 
                        strpos($verifyResponse['customer_name'], 'INVALID') !== false) {
                        DB::rollBack();

                        Log::warning('JAMB profile ID verification failed', [
                            'user_id' => auth()->id(),
                            'profile_id' => $data['profile_id'],
                            'response' => $verifyResponse,
                        ]);

                        return response()->json([
                            'success' => false,
                            'error_type' => 'validation',
                            'title' => '❌ INVALID PROFILE ID',
                            'message' => 'The profile ID you entered is invalid.',
                            'details' => 'Please verify your JAMB profile ID and try again.',
                            'suggestion' => 'Check your JAMB profile ID and ensure it\'s correct.',
                        ], 422);
                    }

                    Log::info('JAMB profile ID verified', [
                        'user_id' => auth()->id(),
                        'profile_id' => $data['profile_id'],
                        'name' => $verifyResponse['customer_name'] ?? 'Unknown',
                    ]);

                } catch (\Exception $e) {
                    Log::warning('JAMB profile verification error', [
                        'user_id' => auth()->id(),
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail on verification error - continue with purchase
                }
            }

            // === 6️⃣ DEDUCT FUNDS ===
            $reference = 'JAMB-' . strtoupper(Str::random(10)) . '-' . time();
            $oldBalance = $wallet->balance;
            $wallet->decrement('balance', $data['amount']);

            Log::info('Wallet debited for JAMB PIN purchase', [
                'user_id' => auth()->id(),
                'reference' => $reference,
                'amount' => $data['amount'],
                'old_balance' => $oldBalance,
                'new_balance' => $wallet->fresh()->balance,
            ]);

            // === 7️⃣ BUY JAMB PIN FROM API ===
            $response = ClubKonnectHelper::buyJamb(
                $data['phone_number'],
                $reference,
                $data['exam_type'],
                env('APP_CALLBACK_URL') . '/jamb-pin/callback'
            );

            Log::info('JAMB API call initiated', [
                'user_id' => auth()->id(),
                'reference' => $reference,
                'exam_type' => $data['exam_type'],
                'phone' => substr($data['phone_number'], 0, 4) . '****' . substr($data['phone_number'], -3),
            ]);

            // --- ✅ Normalize response structure ---
            $response = is_array($response) ? $response : json_decode($response, true);
            if (!is_array($response)) {
                throw new \Exception('Invalid API response format');
            }

            $statusCode = $response['statuscode'] ?? $response['StatusCode'] ?? null;
            $orderId = $response['orderid'] ?? $response['OrderID'] ?? $reference;
            $rawStatus = strtoupper(trim($response['status'] ?? $response['Status'] ?? ''));
            $message = $response['message'] ?? $response['Message'] ?? 'Unknown response';
            $remark = $response['remark'] ?? $response['Remark'] ?? $message;
            $cardDetails = $response['carddetails'] ?? null;

            // --- 🟢 Determine transaction state ---
            $status = match (true) {
                in_array($statusCode, ['200', 200]) || in_array($rawStatus, ['ORDER_COMPLETED', 'SUCCESS']) => 'success',
                in_array($statusCode, ['100', 100]) || in_array($rawStatus, ['ORDER_RECEIVED', 'ORDER_ONHOLD', 'PENDING']) => 'pending',
                default => 'failed',
            };

            Log::info('API Response processed', [
                'user_id' => auth()->id(),
                'reference' => $reference,
                'statusCode' => $statusCode,
                'status' => $status,
                'rawStatus' => $rawStatus,
            ]);

            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'type' => 'jamb_pin',
                'amount' => $data['amount'],
                'status' => $status,
                'request_id' => $reference,
                'order_id' => $orderId,
                'details' => json_encode([
                    'exam_type' => $data['exam_type'],
                    'phone' => $data['phone_number'],
                    'profile_id' => $data['profile_id'] ?? null,
                    'card_details' => $cardDetails,
                    'api_status' => $rawStatus,
                    'api_remark' => $remark,
                ]),
                'description' => "JAMB e-PIN purchase - {$data['exam_type']} exam",
            ]);

            // --- 🟢 SUCCESS or PENDING - Commit transaction ---
            if (in_array($status, ['success', 'pending'])) {
                DB::commit();

                Log::info('JAMB PIN transaction successful', [
                    'user_id' => auth()->id(),
                    'reference' => $reference,
                    'status' => $status,
                    'card_details' => $cardDetails,
                ]);

                return response()->json([
                    'success' => true,
                    'status' => $status,
                    'message' => $status === 'success' 
                        ? 'Your JAMB e-PIN has been successfully purchased!' 
                        : 'Your order is being processed. You\'ll receive the e-PIN shortly.',
                    'transaction' => [
                        'reference' => $reference,
                        'order_id' => $orderId,
                        'exam_type' => $data['exam_type'],
                        'phone' => $data['phone_number'],
                        'amount' => $data['amount'],
                        'card_details' => $cardDetails,
                        'status' => ucfirst($status),
                        'new_balance' => $wallet->fresh()->balance,
                        'timestamp' => now()->format('g:i A'),
                    ],
                ], 200);
            }

            // --- 🔴 FAILED - Refund and rollback ---
            $wallet->increment('balance', $data['amount']);
            DB::commit();

            Log::warning('JAMB PIN transaction failed', [
                'user_id' => auth()->id(),
                'reference' => $reference,
                'api_status' => $rawStatus,
                'message' => $remark,
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'service_error',
                'title' => '⚠️ PURCHASE FAILED',
                'message' => 'We\'re experiencing difficulties processing your request. Your funds have been refunded.',
                'details' => $remark,
                'suggestion' => 'Please try again in a few moments. Your wallet balance was not affected.',
                'refunded' => true,
                'fund_refunded' => $data['amount'],
            ], 503);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('JAMB PIN transaction exception', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'system_error',
                'title' => '⚠️ SYSTEM ERROR',
                'message' => 'Something went wrong during the purchase. Your funds are safe.',
                'details' => 'An unexpected error occurred. Please try again.',
                'suggestion' => 'Please try again later or contact support if the problem continues.',
                'fund_safe' => true,
            ], 500);
        }
    }

    /**
     * Get available JAMB packages
     */
    public function getPackages()
    {
        try {
            $packages = Cache::remember('nellobytes_jamb_packages', 3600, function () {
                $response = ClubKonnectHelper::getJambPackages();
                return $response['JAMB_PACKAGE'] ?? $this->getDefaultJambPackages();
            });

            return response()->json([
                'success' => true,
                'packages' => $packages,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching JAMB packages', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch packages',
                'packages' => $this->getDefaultJambPackages(),
            ]);
        }
    }

    /**
     * Verify JAMB profile ID
     */
    public function verifyProfileId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_type' => 'required|string|in:utme-mock,utme-no-mock,de',
            'profile_id' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = ClubKonnectHelper::verifyJambProfileId(
                $request->exam_type,
                $request->profile_id
            );

            if (isset($response['customer_name'])) {
                $isValid = strpos($response['customer_name'], 'INVALID') === false;

                Log::info('JAMB profile verified', [
                    'user_id' => auth()->id(),
                    'profile_id' => $request->profile_id,
                    'is_valid' => $isValid,
                    'name' => $response['customer_name'],
                ]);

                return response()->json([
                    'success' => $isValid,
                    'message' => $isValid ? 'Profile verified' : 'Invalid profile ID',
                    'name' => $isValid ? $response['customer_name'] : null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification failed',
            ]);

        } catch (\Exception $e) {
            Log::error('Profile verification error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'profile_id' => $request->profile_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification service unavailable. You can proceed without verification.',
            ]);
        }
    }

    /**
     * Handle ClubKonnect callback for JAMB PIN
     */
    public function handleCallback(Request $request)
    {
        Log::info('ClubKonnect JAMB callback received', $request->all());

        try {
            $orderId = $request->query('orderid') ?? $request->query('OrderID');
            $statusCode = $request->query('statuscode') ?? $request->query('StatusCode');
            $orderStatus = strtoupper($request->query('orderstatus') ?? $request->query('OrderStatus') ?? '');
            $orderRemark = $request->query('orderremark') ?? $request->query('OrderRemark') ?? 'No remark provided';
            $cardDetails = $request->query('carddetails') ?? $request->query('CardDetails') ?? null;

            if (!$orderId) {
                Log::warning('Callback missing OrderID');
                return response()->json(['status' => 'error', 'message' => 'Missing OrderID'], 400);
            }

            $transaction = Transaction::where('order_id', $orderId)
                ->orWhere('request_id', 'LIKE', '%' . $orderId . '%')
                ->first();

            if (!$transaction) {
                Log::warning('Transaction not found for callback', ['orderid' => $orderId]);
                return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
            }

            $oldStatus = $transaction->status;

            Log::info('Processing JAMB callback', [
                'order_id' => $orderId,
                'old_status' => $oldStatus,
                'new_status' => $orderStatus,
                'status_code' => $statusCode,
            ]);

            DB::beginTransaction();

            // ✅ Define success/pending/failure conditions
            $isSuccess = in_array($statusCode, ['200', 200]) ||
                        strpos($orderStatus, 'COMPLETED') !== false ||
                        strpos($orderStatus, 'SUCCESS') !== false;

            $isPending = in_array($statusCode, ['100', 100]) ||
                        strpos($orderStatus, 'RECEIVED') !== false ||
                        strpos($orderStatus, 'ONHOLD') !== false;

            $isFailed = !$isSuccess && !$isPending;

            // 🟢 SUCCESS
            if ($isSuccess) {
                $transaction->update([
                    'status' => 'success',
                    'details' => json_encode([
                        'previous_status' => $oldStatus,
                        'callback_status' => $orderStatus,
                        'callback_remark' => $orderRemark,
                        'card_details' => $cardDetails,
                        'callback_time' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('JAMB callback SUCCESS', [
                    'order_id' => $orderId,
                    'user_id' => $transaction->user_id,
                    'amount' => $transaction->amount,
                ]);

            // 🟡 PENDING
            } elseif ($isPending) {
                $transaction->update([
                    'status' => 'pending',
                    'details' => json_encode([
                        'previous_status' => $oldStatus,
                        'callback_status' => $orderStatus,
                        'callback_remark' => $orderRemark,
                        'callback_time' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('JAMB callback PENDING', [
                    'order_id' => $orderId,
                    'user_id' => $transaction->user_id,
                ]);

            // 🔴 FAILED
            } else {
                $wallet = Wallet::where('user_id', $transaction->user_id)->first();

                if ($wallet) {
                    $wallet->increment('balance', $transaction->amount);
                    Log::info('JAMB callback FAILED - Refund issued', [
                        'order_id' => $orderId,
                        'user_id' => $transaction->user_id,
                        'refund_amount' => $transaction->amount,
                    ]);
                }

                $transaction->update([
                    'status' => 'failed',
                    'details' => json_encode([
                        'previous_status' => $oldStatus,
                        'callback_status' => $orderStatus,
                        'callback_remark' => $orderRemark,
                        'refunded_amount' => $transaction->amount,
                        'callback_time' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('JAMB callback FAILED', [
                    'order_id' => $orderId,
                    'user_id' => $transaction->user_id,
                    'remark' => $orderRemark,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Callback processed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JAMB callback processing error: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Get default JAMB packages
     */
    private function getDefaultJambPackages()
    {
        return [
            ['code' => 'utme-mock', 'name' => 'UTME e-PIN (with Mock)', 'amount' => 3500],
            ['code' => 'utme-no-mock', 'name' => 'UTME e-PIN (without Mock)', 'amount' => 3000],
            ['code' => 'de', 'name' => 'Direct Entry (DE) e-PIN', 'amount' => 2500],
        ];
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
            'exam_type' => 'Exam Type',
            'phone_number' => 'Phone Number',
            'amount' => 'Amount',
            'pin' => 'Transaction PIN',
            'profile_id' => 'Profile ID',
        ];

        return $labels[$field] ?? $field;
    }
}
