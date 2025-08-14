<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\VtuAfrica; // Changed from ClubKonnectHelper
use App\Models\Transaction;
use Illuminate\Support\Str;

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


        $response = VtuAfrica::fundBetAccount(
            $request->betting_company,
            $request->customer_id,
            $request->amount,
            $request->request_id,
            auth()->user()->phone
        );


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

        dd($response);

        if ($response['success']) {
            return back()->with('success',
                'Betting account funded successfully. ' .
                ($response['data']['description']['message'] ?? '')
            );
        } else {
            $errorMessage = $response['data']['description']['message'] ??
                          $response['error'] ??
                          'Unknown error occurred';
            return back()->with('error', 'Funding failed: ' . $errorMessage);
        }
    }

    private function getBettingProviderName($code)
    {

        return VtuAfrica::getSupportedServices()[$code] ?? $code;
    }
}
