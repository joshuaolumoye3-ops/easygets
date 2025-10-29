<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\FlutterwaveHelper;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class VirtualAccountController extends Controller
{
    public function createAccount(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100'
        ]);

        try {
            $user = User::findOrFail(auth()->id());
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

            $accountReference = 'VA_' . uniqid() . '_' . time();

            $user_data = [
                'user' => $user,
                'amount' => $request->amount,
                'tx_ref' => $accountReference
            ];

            Log::info('Creating new virtual account', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'reference' => $accountReference
            ]);

            $response = FlutterwaveHelper::createUserVirtualAccount($user_data);

            if (!$response) {
                return redirect()->back()->with('error', 'Payment service unavailable');
            }

            if (isset($response['status']) && $response['status'] === 'success') {
                $data = $response['data'];


                $virtualAccount = VirtualAccount::create([
                    'wallet_id' => $wallet->id,
                    'account_reference' => $accountReference,
                    'customer_email' => $user->email,
                    'customer_name' => "{$user->first_name} {$user->last_name}",
                    'bank_name' => $data['bank_name'] ?? 'Flutterwave Bank',
                    'bank_code' => $data['bank_code'] ?? '',
                    'account_number' => $data['account_number'],
                    'status' => 'pending',
                    'amount' => $request->amount,
                    'expires_at' => Carbon::now()->addHours(24)
                ]);


                session(['account_data' => [
                    'account_number' => $data['account_number'],
                    'bank_name' => $data['bank_name'],
                    'account_name' => "{$user->first_name} {$user->last_name}",
                    'amount' => $request->amount,
                    'expires_at' => $virtualAccount->expires_at->format('Y-m-d H:i:s'),
                    'reference' => $accountReference
                ]]);

                return redirect()->back()->with('success', 'Virtual account generated successfully');



            } else {
                $errorMessage = $response['message'] ?? 'Unknown error occurred';
                if (str_contains(strtolower($errorMessage), 'bvn')) {
                    $errorMessage = 'BVN verification required. Please update your BVN and try again.';
                } elseif (str_contains(strtolower($errorMessage), 'permission') || str_contains(strtolower($errorMessage), 'access')) {
                    $errorMessage = 'Virtual account feature not enabled on your Flutterwave account.';
                }

                Log::error('Virtual account creation failed', [
                    'error_message' => $errorMessage,
                    'full_response' => $response
                ]);

                return redirect()->back()->with('error', "Failed to create account: {$errorMessage}");
            }

        } catch (\Exception $e) {
            Log::error('Virtual Account Controller Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'System error: ' . $e->getMessage());
        }
    }

    public function clearSession(Request $request)
    {
        $request->session()->forget('data');
        return response()->json(['success' => true]);
    }
}
