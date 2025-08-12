<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClubKonnectHelper;
use App\Models\Transaction;
use Illuminate\Support\Str;

class BettingController extends Controller
{
    public function fund_account(Request $request)
    {
        $validBettingCodes = [
            'msport', 'naijabet', 'nairabet', 'bet9ja-agent',
            'betland', 'betlion', 'supabet', 'bet9ja',
            'bangbet', 'betking', '1xbet', 'betway',
            'merrybet', 'mlotto', 'western-lotto',
            'hallabet', 'green-lotto'
        ];

        $request->validate([
            'betting_company' => 'required|in:' . implode(',', $validBettingCodes),
            'customer_id' => 'required|string|min:3|max:50',
            'amount' => 'required|numeric|min:100|max:50000',
            'request_id' => 'required|string'
        ]);

        $response = ClubKonnectHelper::placeBet(
            $request->betting_company,
            $request->customer_id,
            $request->amount,
            $request->request_id
        );

        // Store transaction
        Transaction::create([
            'reference' => $request->request_id,
            'user_id' => auth()->id(),
            'type' => 'betting',
            'channel' => 'ClubKonnect',
            'provider' => $this->getBettingProviderName($request->betting_company),
            'account' => $request->customer_id,
            'amount' => $request->amount,
            'status' => ($response['statuscode'] ?? null) == '100' ? 'success' : 'failed',
            'response' => json_encode($response),
        ]);

        if ($response && ($response['statuscode'] ?? null) == '100') {
            return back()->with('success', 'Betting account funding request sent.');
        } else {
            return back()->with('error', 'Funding failed: ' . ($response['status'] ?? 'Unknown error'));
        }
    }

    private function getBettingProviderName($code)
    {
        $providers = [
            'msport' => 'Msport',
            'naijabet' => 'Naijabet',
            'nairabet' => 'Nairabet',
            'bet9ja-agent' => 'Bet9ja Agent',
            'betland' => 'Betland',
            'betlion' => 'Betlion',
            'supabet' => 'Supabet',
            'bet9ja' => 'Bet9ja',
            'bangbet' => 'Bangbet',
            'betking' => 'BetKing',
            '1xbet' => '1xBet',
            'betway' => 'Betway',
            'merrybet' => 'Merrybet',
            'mlotto' => 'Mlotto',
            'western-lotto' => 'Western Lotto',
            'hallabet' => 'Hallabet',
            'green-lotto' => 'Green Lotto'
        ];

        return $providers[$code] ?? $code;
    }
}
