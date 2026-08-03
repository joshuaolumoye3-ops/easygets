<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payments\KorapayService;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KorapayCheckoutController extends Controller
{
    public function __construct(protected KorapayService $korapay, protected PaymentProcessor $processor)
    {
    }

    /**
     * Step 1: user clicks "Add Funds" -> we create a `pending` Transaction
     * row with a reference WE control, then send them to Korapay's hosted
     * checkout page. This pending row is what the webhook (or the return
     * page below) will later flip to `success`.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $user = Auth::user();
        Wallet::firstOrCreate(['user_id' => $user->id]);

        // Reference format kept similar to the old FLW_ one for easy log searching,
        // but namespaced per provider so old/new references never collide.
        $reference = 'KPY_' . $user->id . '_' . time() . '_' . Str::random(8);

        Transaction::create([
            'reference'   => $reference,
            'user_id'     => $user->id,
            'type'        => 'credit',
            'channel'     => 'Korapay',
            'provider'    => 'Korapay Checkout',
            'amount'      => $request->amount,
            'status'      => 'pending',
            'description' => 'Wallet funding via Korapay Checkout',
        ]);

        $result = $this->korapay->initializeCharge([
            'amount'           => $request->amount,
            'currency'         => 'NGN',
            'reference'        => $reference,
            'redirect_url'     => route('korapay.callback'),
            'notification_url' => route('korapay.webhook'),
            'narration'        => 'Wallet funding',
            'customer'         => [
                'email' => $user->email,
                'name'  => trim("{$user->first_name} {$user->last_name}"),
            ],
        ]);

        if (!$result['success']) {
            Transaction::where('reference', $reference)->update(['status' => 'failed']);
            return back()->with('error', $result['message'] ?? 'Failed to start payment. Please try again.');
        }

        return redirect()->away($result['checkout_url']);
    }

    /**
     * Step 2: the customer is redirected here by Korapay after paying (or
     * cancelling). We NEVER credit the wallet purely because the user landed
     * here — we independently verify with Korapay first. This exists so the
     * user gets immediate, accurate feedback even if the webhook is a few
     * seconds behind, not as a replacement for the webhook.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('dashboard')->with('error', 'Missing transaction reference.');
        }

        $verified = $this->korapay->verifyCharge($reference);

        if (!$verified['success']) {
            return redirect()->route('dashboard')
                ->with('info', 'We could not confirm your payment yet. If money was deducted, your wallet will update shortly once we hear back from Korapay.');
        }

        $status = $verified['data']['status'] ?? null;

        if ($status === 'success') {
            // Safe to call even if the webhook already processed this — see PaymentProcessor.
            $result = $this->processor->handleChargeSuccess($verified['data'] + ['reference' => $reference]);

            if (in_array($result['status'], ['credited', 'duplicate'])) {
                return redirect()->route('dashboard')->with('success', 'Payment successful! Your wallet has been credited.');
            }
        }

        if ($status === 'processing' || $status === 'pending') {
            return redirect()->route('dashboard')
                ->with('info', 'Your payment is still being confirmed. Your wallet will update automatically once it clears.');
        }

        return redirect()->route('dashboard')->with('error', 'Payment was not successful.');
    }
}
