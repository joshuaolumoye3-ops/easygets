<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Monnify;

class WalletController extends Controller
{

    public function createAccount(Request $request)
{
    $request->validate([
        'id_type'  => 'required|in:bvn,nin',
        'id_value' => 'required|string',
    ]);

    $user = Auth::user();

    // Prevent creating multiple accounts for same wallet
    if ($user->wallet && $user->wallet->virtualAccounts()->exists()) {
        return redirect()->route('dashboard')
            ->with('info', 'You already have a virtual account.');
    }

    $accountName = "{$user->first_name} {$user->last_name}";
    $email       = $user->email;

    $bvn = $request->id_type === 'bvn' ? $request->id_value : null;
    $nin = $request->id_type === 'nin' ? $request->id_value : null;

    // Call Monnify API
    $response = Monnify::createVirtualAccount(
        $accountName,
        $email,
        $accountName,
        $bvn,
        $nin
    );

    // Check API response
    if (!isset($response['success']) || !$response['success']) {
        return back()->with('error', $response['message'] ?? 'Failed to create account.');
    }

    $wallet = $user->wallet ?: $user->wallet()->create(['balance' => 0]);

    $accountReference = $response['data']['accountReference'] ?? null;
    $accounts         = $response['data']['accounts'] ?? [];

    if (!$accountReference || empty($accounts)) {
        return back()->with('error', 'Invalid account details received from Monnify.');
    }

    // Prepare data for bulk insert
    $accountsData = [];
    foreach ($accounts as $acct) {
        $accountsData[] = [
            'wallet_id'         => $wallet->id,
            'account_reference' => $accountReference,
            'customer_email'    => $email,
            'customer_name'     => $accountName,
            'bank_name'         => $acct['bankName'],
            'bank_code'         => $acct['bankCode'],
            'account_number'    => $acct['accountNumber'],
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    // Insert both accounts in one query
    VirtualAccount::insert($accountsData);

    return redirect()->route('dashboard')
        ->with('success', 'Virtual account created successfully!');
}

}
