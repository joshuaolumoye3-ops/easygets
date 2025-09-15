<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VtuAfrica;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Helpers\PinHelper;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;


class BettingController extends Controller
{

    public function show() // or whatever method renders your betting view
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        return view('users.betting', compact('wallet'));
    }
    public function fund_account(Request $request)
    {
        // Handle both JSON and FormData requests
        $data = $request->isJson() ? $request->json()->all() : $request->all();
        
        $validBettingCodes = array_keys(VtuAfrica::getSupportedServices());

        $validator = Validator::make($data, [
            'betting_company' => 'required|in:' . implode(',', $validBettingCodes),
            'customer_id' => 'required|string|min:3|max:50',
            'amount' => 'required|numeric|min:100|max:50000',
            'request_id' => 'required|string',
            'pin' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify PIN first
        try {
            PinHelper::verifyPin($data['pin']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid PIN',
                'errors' => $e->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Lock and check wallet balance
            $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $data['amount']) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance. Please fund your wallet.'
                ], 400);
            }

            // 2. Deduct amount immediately
            $wallet->decrement('balance', $data['amount']);

            // 3. Call VTU Africa API
            $response = VtuAfrica::fundBetAccount(
                $data['betting_company'],
                $data['customer_id'],
                $data['amount'],
                $data['request_id'],
                auth()->user()->phone
            );

            // 4. Record transaction
            Transaction::create([
                'reference' => $data['request_id'],
                'user_id' => auth()->id(),
                'type' => 'betting',
                'channel' => 'VTU Africa',
                'provider' => $this->getBettingProviderName($data['betting_company']),
                'account' => $data['customer_id'],
                'amount' => $data['amount'],
                'status' => $response['success'] ? 'success' : 'failed',
                'response' => json_encode($response['data'] ?? $response),
            ]);

            // 5. Handle API response
            if ($response['success']) {
                DB::commit();
                
                return response()->json([
                    'status' => true,
                    'message' => 'Betting account funded successfully! ' . 
                            ($response['data']['description']['message'] ?? ''),
                    'data' => [
                        'reference' => $data['request_id'],
                        'new_balance' => $wallet->fresh()->balance
                    ]
                ]);
            } else {
                // Rollback if API call failed
                DB::rollBack();
                
                return response()->json([
                    'status' => false,
                    'message' => $response['data']['description']['message'] ?? 'Funding failed. Please try again.'
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }

private function getBettingProviderName($code)
{
    return VtuAfrica::getSupportedServices()[$code] ?? $code;
}
}
