<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VtuAfrica;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class BettingController extends Controller
{
   public function fund_account(Request $request)
{
    $validBettingCodes = array_keys(VtuAfrica::getSupportedServices());

    $request->validate([
        'betting_company' => 'required|in:' . implode(',', $validBettingCodes),
        'customer_id' => 'required|string|min:3|max:50',
        'amount' => 'required|numeric|min:100|max:50000',
        'request_id' => 'required|string'
    ]);

    DB::beginTransaction();

    try {
        // 1. Lock and check wallet
        $wallet = Wallet::where('user_id', auth()->id())->lockForUpdate()->first();

        if (!$wallet || $wallet->balance < $request->amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        // 2. Deduct amount immediately
        $wallet->decrement('balance', $request->amount);

        // 3. Call VTU Africa API
        $response = VtuAfrica::fundBetAccount(
            $request->betting_company,
            $request->customer_id,
            $request->amount,
            $request->request_id,
            auth()->user()->phone
        );

        // 4. Record transaction
        Transaction::create([
            'reference' => $request->request_id,
            'user_id' => auth()->id(),
            'type' => 'betting',
            'channel' => 'VTU Africa',
            'provider' => $this->getBettingProviderName($request->betting_company),
            'account' => $request->customer_id,
            'amount' => $request->amount,
            'status' => $response['success'] ? 'success' : 'failed',
            'response' => json_encode($response['data'] ?? $response),
        ]);

        // 5. Handle API response
        if ($response['success']) {
            DB::commit();
            return back()->with('success',
                'Betting account funded successfully. ' .
                ($response['data']['description']['message'] ?? '')
            );
        } else {
            throw new \Exception($response['data']['description']['message'] ?? 'Funding failed');
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', $e->getMessage())->withInput();
    }
}

private function getBettingProviderName($code)
{
    return VtuAfrica::getSupportedServices()[$code] ?? $code;
}
}
