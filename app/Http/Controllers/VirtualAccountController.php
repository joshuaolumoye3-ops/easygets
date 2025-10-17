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
            $user_data = [
                'user' => $user,
                'amount' => $request->amount
            ];

            $existing = VirtualAccount::where('wallet_id', $wallet->id)->first();
            if ($existing) {
                return redirect()->back()->with('error', 'Virtual account already exists.');
            }

            Log::info('Attempting to create virtual account', ['user_id' => $user->id]);

            $response = FlutterwaveHelper::createUserVirtualAccount($user_data);

            if (!$response) {
                Log::error('No response from Flutterwave service');
                return redirect()->back()->with('error', 'Payment service unavailable. Please try again.');
            }

            Log::info('Full Flutterwave Response Structure', ['response' => $response]);

            if (isset($response['status']) && $response['status'] === 'success') {
                $data = $response['data'];

                // For permanent account - uncomment when ready to save
                /*
                $account = VirtualAccount::create([
                    'wallet_id' => $wallet->id,
                    'account_reference' => $data['order_ref'] ?? $data['id'] ?? uniqid(),
                    'customer_email' => $data['email'] ?? $user->email,
                    'customer_name' => $data['customer']['name'] ?? $user->name,
                    'bank_name' => $data['bank_name'] ?? 'Flutterwave Bank',
                    'bank_code' => $data['bank_code'] ?? '',
                    'account_number' => $data['account_number'],
                    'flw_reference' => $data['flw_ref'] ?? null,
                ]);

                Log::info('Virtual account created successfully', [
                    'account_id' => $account->id,
                    'account_number' => $account->account_number
                ]);
                */


                return redirect()->back()->with([
                    'success' => 'Virtual account generate successfully!',
                    'account_data' => $data
                ]);



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
