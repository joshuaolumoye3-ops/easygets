<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\DedicatedAccount;
use App\Models\Wallet;
use App\Services\Payments\KorapayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DedicatedAccountController extends Controller
{
    public function __construct(protected KorapayService $korapay)
    {
    }

    /**
     * Create a permanent virtual account for the logged-in user, backed by
     * their BVN (required by Korapay) and optionally their NIN.
     *
     * SECURITY:
     *  - BVN/NIN are validated as 11-digit numbers and sent to Korapay over
     *    HTTPS. They are NEVER written to the database or to logs in full —
     *    only the last 4 digits are persisted, purely for support reference.
     *  - Throttled at the route level (see routes snippet) to slow down
     *    automated abuse of an identity-verification endpoint.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (DedicatedAccount::where('user_id', $user->id)->where('status', 'active')->exists()) {
            return back()->with('info', 'You already have a dedicated account.');
        }

        $validated = $request->validate([
            'account_name' => 'required|string|max:100',
            'bvn'          => 'required|digits:11',
            // Korapay currently requires BVN; NIN is supplementary, not a substitute.
            'nin'          => 'nullable|digits:11',
        ]);

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        $accountReference = 'KPY_VBA_' . $user->id . '_' . Str::random(10);

        $result = $this->korapay->createVirtualAccount([
            'account_name'      => $validated['account_name'],
            'account_reference' => $accountReference,
            'customer'          => [
                'name'  => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
            ],
            'bvn' => $validated['bvn'],
            'nin' => $validated['nin'] ?? null,
        ]);

        if (!$result['success']) {
            return back()->with('error', $result['message'] ?? 'Could not create your dedicated account. Please try again.');
        }

        $data = $result['data'];

        DedicatedAccount::create([
            'user_id'            => $user->id,
            'wallet_id'          => $wallet->id,
            'provider'           => 'korapay',
            'account_reference'  => $data['account_reference'] ?? $accountReference,
            'provider_unique_id' => $data['unique_id'] ?? null,
            'account_number'     => $data['account_number'],
            'bank_code'          => $data['bank_code'],
            'bank_name'          => $data['bank_name'],
            'account_name'       => $data['account_name'],
            'currency'           => $data['currency'] ?? 'NGN',
            'status'             => 'active',
            'bvn_last4'          => substr($validated['bvn'], -4),
            'nin_last4'          => isset($validated['nin']) ? substr($validated['nin'], -4) : null,
        ]);

        return redirect()->route('dashboard')->with('success', 'Your dedicated account has been created! You can now fund your wallet anytime by transferring to it.');
    }
}
