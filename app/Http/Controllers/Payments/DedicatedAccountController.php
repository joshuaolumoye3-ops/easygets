<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\DedicatedAccount;
use App\Models\Wallet;
use App\Services\Flutterwave\FlutterwaveClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DedicatedAccountController extends Controller
{
    public function __construct(private FlutterwaveClient $flw)
    {
    }

    /**
     * Create a permanent Flutterwave virtual account for the logged-in user.
     *
     * SECURITY
     *  - Exactly ONE identifier (BVN xor NIN), 11 digits, validated before anything leaves the server.
     *  - BVN/NIN go to Flutterwave over HTTPS only. They are never stored (only the last 4 digits),
     *    never logged, and never flashed back into the session on a validation error.
     *  - The account name comes from the user's profile, NOT from the form (no user-controlled narration).
     *  - Per-user lock + existing-account check => no duplicate accounts from double clicks / races.
     *  - Creation is never auto-retried: an ambiguous provider response is logged for reconciliation.
     *  - Route-level throttle (5 attempts / 24h per user) lives in routes/web.php.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->only(['bvn', 'nin']), [
            'bvn' => ['nullable', 'digits:11', 'required_without:nin', 'prohibits:nin'],
            'nin' => ['nullable', 'digits:11', 'required_without:bvn', 'prohibits:bvn'],
        ], [
            'bvn.digits'           => 'Your BVN must be exactly 11 digits.',
            'nin.digits'           => 'Your NIN must be exactly 11 digits.',
            'bvn.required_without' => 'Enter your BVN or your NIN.',
            'nin.required_without' => 'Enter your BVN or your NIN.',
            'bvn.prohibits'        => 'Use either your BVN or your NIN, not both.',
            'nin.prohibits'        => 'Use either your BVN or your NIN, not both.',
        ]);

        if ($validator->fails()) {
            // Never echo BVN/NIN back into the session.
            return back()->withErrors($validator)->withInput($request->except(['bvn', 'nin']));
        }

        $bvn = $request->input('bvn') ?: null;
        $nin = $request->input('nin') ?: null;

        $lock = Cache::lock('dedicated-account:' . $user->id, 45);
        if (!$lock->get()) {
            return back()->with('info', 'Your account is already being created. Please wait a moment.');
        }

        try {
            if (DedicatedAccount::where('user_id', $user->id)->where('status', 'active')->exists()) {
                return back()->with('info', 'You already have a dedicated account.');
            }

            $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

            $first   = Str::limit(trim((string) $user->first_name), 50, '');
            $last    = Str::limit(trim((string) $user->last_name), 50, '');
            $full    = trim("{$first} {$last}");
            $phone   = preg_replace('/\D/', '', (string) ($user->phone_number ?? ''));
            $txRef   = 'FLWVA_' . $user->id . '_' . strtoupper(Str::random(14));

            $payload = array_filter([
                'email'       => $user->email,
                'tx_ref'      => $txRef,
                'firstname'   => $first ?: 'Customer',
                'lastname'    => $last ?: ($first ?: 'User'),
                'narration'   => Str::limit($full ?: 'Wallet funding', 60, ''),
                'phonenumber' => (strlen($phone) >= 10 && strlen($phone) <= 15) ? $phone : null,
                'bvn'         => $bvn,
                'nin'         => $nin,
            ], fn ($v) => $v !== null && $v !== '');

            $result = $this->flw->createStaticVirtualAccount($payload);

            if ($result['ambiguous']) {
                Log::critical('Dedicated account creation outcome unknown — reconcile on the Flutterwave dashboard', [
                    'user_id' => $user->id, 'tx_ref' => $txRef,
                ]);
                return back()->with('error', 'We could not confirm your account creation. Please wait a few minutes and check again before retrying.');
            }

            if (!$result['ok'] || !is_array($result['data'])) {
                Log::warning('Dedicated account creation rejected', [
                    'user_id' => $user->id, 'http' => $result['http'], 'message' => $result['message'],
                ]);
                $msg = Str::contains(Str::lower($result['message']), ['bvn', 'nin', 'identity'])
                    ? 'We could not verify that BVN/NIN. Please check it and try again.'
                    : 'We could not create your dedicated account right now. Please try again later.';
                return back()->with('error', $msg);
            }

            $d = $result['data'];

            if (!preg_match('/^\d{10}$/', (string) ($d['account_number'] ?? ''))) {
                Log::error('Dedicated account creation returned an invalid account number', ['user_id' => $user->id, 'tx_ref' => $txRef]);
                return back()->with('error', 'We received an invalid response from our payment partner. Please try again later.');
            }

            // "Please make a bank transfer to <NAME>" -> <NAME>
            $accountName = preg_match('/transfer to\s+(.+)$/i', (string) ($d['note'] ?? ''), $m)
                ? trim($m[1]) : ($full ?: 'Wallet Account');

            try {
                DedicatedAccount::create([
                    'user_id'            => $user->id,
                    'wallet_id'          => $wallet->id,
                    'provider'           => 'flutterwave',
                    'account_reference'  => $txRef,
                    'provider_unique_id' => $d['order_ref'] ?? null,
                    'account_number'     => $d['account_number'],
                    'bank_code'          => $d['bank_code'] ?? null,
                    'bank_name'          => $d['bank_name'] ?? 'Flutterwave MFB',
                    'account_name'       => Str::limit($accountName, 100, ''),
                    'currency'           => 'NGN',
                    'status'             => 'active',
                    'bvn_last4'          => $bvn ? substr($bvn, -4) : null,
                    'nin_last4'          => $nin ? substr($nin, -4) : null,
                ]);
            } catch (\Throwable $e) {
                // The account exists at Flutterwave but we failed to save it — never lose track of it.
                Log::critical('Dedicated account created at Flutterwave but NOT saved locally', [
                    'user_id' => $user->id, 'tx_ref' => $txRef, 'order_ref' => $d['order_ref'] ?? null,
                    'account_number' => FlutterwaveClient::maskAccount((string) $d['account_number']),
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'Your account was created but could not be linked. Our team has been alerted — please contact support.');
            }

            return redirect()->route('dashboard')->with('success', 'Your dedicated account is ready! Transfer to it anytime and your wallet is credited automatically.');
        } finally {
            $lock->release();
        }
    }
}