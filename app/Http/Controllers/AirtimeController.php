<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Helpers\DemmyPayHelper;
use App\Helpers\PinHelper;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AirtimeController extends Controller
{
    public function show() // or whatever method renders your betting view
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.airtime', compact('wallet'));
    }
    // public function recharge(Request $request)
    // {
    //     $request->validate([
    //         'mobile_network' => 'required|in:01,02,03,04',
    //         'mobile_number' => 'required|regex:/^0\d{10}$/',
    //         'amount' => 'required|numeric|min:50|max:200000',
    //     ]);

    //     $network = $request->mobile_network;
    //     $number = $request->mobile_number;
    //     $amount = $request->amount;
    //     $requestId = uniqid('airtime_');

    //     // ✅ Step 1: Get user wallet & check balance
    //     $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

    //     if (!$wallet || $wallet->balance < $amount) {
    //         return back()->with('error', 'Insufficient wallet balance.');
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // ✅ Step 2: Call ClubKonnect API
    //         $response = ClubKonnectHelper::buyAirtime($network, $amount, $number, $requestId);

    //         // ✅ Step 3: Store transaction
    //         Transaction::create([
    //             'reference' => $requestId,
    //             'user_id' => auth()->id(),
    //             'type' => 'airtime',
    //             'channel' => 'ClubKonnect',
    //             'provider' => $this->getProviderName($network),
    //             'phone' => $number,
    //             'amount' => $amount,
    //             'status' => ($response['statuscode'] ?? null) == '100' ? 'success' : 'failed',
    //             'response' => json_encode($response),
    //         ]);

    //         // ✅ Step 4: Deduct balance only if successful
    //         if (($response['statuscode'] ?? null) == '100') {
    //             $wallet->decrement('balance', $amount);
    //             DB::commit();
    //             return back()->with('success', 'Airtime recharge request sent.');
    //         } else {
    //             DB::rollBack();
    //             return back()->with('error', 'Airtime recharge failed: ' . ($response['status'] ?? 'Unknown error'));
    //         }

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Something went wrong. Please try again.');
    //     }
    // }

     /**
     * Map network codes to DemmyPay product IDs for airtime
     * Make sure these IDs match exactly what you see from DemmyPay getProducts()
     */
    private function getAirtimeProductId($code)
    {
        return match ($code) {
            '01' => 1, // MTN Airtime
            '02' => 2, // Glo Airtime
            '03' => 3, // Airtel Airtime
            '04' => 4, // 9mobile Airtime
            default => null,
        };
    }

    /**
     * Handle airtime purchase
     */
    public function recharge(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:50|max:50000',
            'network' => 'required|string|in:01,02,03,04',
            'recipient' => 'required|string|min:8|max:15',
            'pin' => 'required|string|size:4',
        ]);

        try {
            // ✅ Verify user's transaction PIN first
            PinHelper::verifyPin($validated['pin']);

            // Sanitize input
            $amount = (float) $validated['amount'];
            $network = trim($validated['network']);
            $recipient = preg_replace('/\D/', '', $validated['recipient']); // digits only

            // ✅ Validate recipient phone number
            if (strlen($recipient) < 8 || strlen($recipient) > 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid recipient phone number.',
                ], 400);
            }

            // ✅ Map network codes to product IDs (for DemmyPay API)
            $productId = $this->getAirtimeProductId($network);
            if (!$productId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid network code provided.',
                ], 400);
            }

            // ✅ Fetch user wallet and check balance
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance. Please fund your account.',
                ], 400);
            }

            // ✅ Generate a unique reference
            $customerReference = 'airtime_' . strtolower(Str::slug(Str::random(10) . '_' . uniqid()));

            DB::beginTransaction();

            // ✅ Call DemmyPay API to perform airtime purchase
            $response = DemmyPayHelper::buyAirtime(
                $amount,
                $productId,
                $recipient,
                $customerReference
            );

            // ✅ Network name mapping
            $networkNames = [
                '01' => 'MTN',
                '02' => 'Glo',
                '03' => 'Airtel',
                '04' => '9mobile',
            ];
            $networkName = $networkNames[$network] ?? 'Unknown';

            // ✅ Save transaction with service name as provider
            $transaction = Transaction::create([
                'reference' => substr($customerReference, 0, 100),
                'user_id'   => auth()->id(),
                'type'      => 'airtime',
                'channel'   => 'DemmyPay',
                'provider'  => $networkName, // ✅ Save readable name
                'phone'     => $recipient,
                'amount'    => $amount,
                'status'    => (isset($response['status']) && $response['status'] == 200) ? 'success' : 'failed',
                'response'  => json_encode($response),
            ]);

            // ✅ If successful, deduct wallet balance and commit
            if (isset($response['status']) && $response['status'] == 200) {
                $wallet->decrement('balance', $amount);
                DB::commit();

                $successMessage = "Your {$networkName} airtime recharge of ₦" . number_format($amount, 2) .
                                " to {$recipient} was successful!";

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'transaction_reference' => $transaction->reference,
                        'network' => $networkName,
                        'network_code' => $network,
                        'recipient' => $recipient,
                        'amount' => $amount,
                        'formatted_amount' => '₦' . number_format($amount, 2),
                        'new_balance' => $wallet->fresh()->balance,
                        'formatted_balance' => '₦' . number_format($wallet->fresh()->balance, 2),
                        'transaction_time' => now()->format('Y-m-d H:i:s'),
                        'provider_response' => $response['data'] ?? null,
                    ],
                    'transaction_id' => $transaction->id,
                ]);
            }

            // ❌ Handle failed transaction
            DB::rollBack();
            $errorMessage = $response['detail']
                ?? $response['message']
                ?? $response['error']
                ?? 'Airtime purchase failed';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_code' => $response['status'] ?? 'unknown',
                'provider_response' => $response,
            ], 400);

        } catch (ValidationException $e) {
            DB::rollBack();
            $pinError = $e->validator->errors()->first('pin');
            if ($pinError) {
                return response()->json([
                    'success' => false,
                    'message' => $pinError,
                    'error_type' => 'pin_validation',
                ], 422);
            }
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'error_type' => 'validation',
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Airtime purchase error', [
                'user_id' => auth()->id(),
                'amount' => $amount ?? null,
                'recipient' => $recipient ?? null,
                'network' => $network ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'error_type' => 'system_error',
                // 'debug_message' => app()->environment('local') ? $e->getMessage() : null, // removed for production
            ], 500);
        }
    }

    /**
     * Render success partial for AJAX requests
     */
    public function renderSuccessPartial(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'details' => 'sometimes|array'
        ]);

        // Render the partial component with details
        return view('partials.success', [
            'title' => $data['title'],
            'message' => $data['message'],
            'details' => $data['details'] ?? []
        ])->render();
    }

    }
