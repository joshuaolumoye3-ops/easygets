<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GiftcardSetting;
use App\Models\GiftcardWithdrawal;
use App\Services\Flutterwave\FlutterwaveClient;
use App\Services\Payments\FlutterwavePayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * USAGE: in App\Http\Controllers\GiftcardController
 *   1. DELETE the existing public function withdraw(...)
 *   2. add `use \App\Http\Controllers\Concerns\GiftcardWithdrawalActions;` inside the class
 *
 * Relies on the controller's existing private helpers: checkPin() and fail().
 */
trait GiftcardWithdrawalActions
{
    /** GET /giftcards/banks — Nigerian bank list for the withdraw form. */
    public function banks(FlutterwaveClient $flw): JsonResponse
    {
        $banks = $flw->banks();
        if ($banks === null) {
            return $this->fail('Bank list is temporarily unavailable. Please try again shortly.', 503);
        }
        return response()->json(['success' => true, 'banks' => $banks]);
    }

    /** POST /giftcards/resolve-account — shows the account holder's name before the user confirms. */
    public function resolveAccount(Request $request, FlutterwaveClient $flw): JsonResponse
    {
        $data = $request->validate([
            'bank_code'      => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'account_number' => ['required', 'regex:/^[0-9]{10}$/'],
        ], ['account_number.regex' => 'Account number must be 10 digits.']);

        // Daily cap per user: this endpoint is an account-name lookup and must not be usable for enumeration.
        $key = 'gc-resolve:' . Auth::id() . ':' . now()->format('Ymd');
        Cache::add($key, 0, now()->endOfDay());
        if (Cache::increment($key) > 30) {
            return $this->fail('Too many account look-ups today. Please try again tomorrow.', 429);
        }

        if (!$this->bankByCode($flw, $data['bank_code'])) {
            return $this->fail('Please choose a valid bank.', 422);
        }

        $r = $flw->resolveAccount($data['account_number'], $data['bank_code']);
        if (!$r['ok'] || empty($r['data']['account_name'])) {
            return $this->fail('We could not verify this account. Check the bank and account number.', 422);
        }

        return response()->json(['success' => true, 'account_name' => (string) $r['data']['account_name']]);
    }

    /** POST /giftcards/withdraw */
    public function withdraw(Request $request): JsonResponse
    {
        $min = (float) GiftcardSetting::get('min_withdrawal');
        $max = (float) config('services.flutterwave.max_single_payout', 1000000);

        $data = $request->validate([
            'amount'         => ['required', 'numeric', 'min:' . $min, 'max:' . $max],
            'bank_code'      => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'account_number' => ['required', 'regex:/^[0-9]{10}$/'],
            'client_token'   => ['required', 'string', 'max:64'],
            'pin'            => ['required', 'digits:4'],
        ], ['account_number.regex' => 'Account number must be 10 digits.']);

        $user = Auth::user();

        if ($resp = $this->checkPin($request, $user)) {
            return $resp;
        }

        // Idempotent: a retry/double-click with the same token returns the original request.
        if ($existing = GiftcardWithdrawal::where('client_token', $data['client_token'])->where('user_id', $user->id)->first()) {
            return response()->json(['success' => true, 'message' => 'Your withdrawal request has already been received.']);
        }

        $lock = Cache::lock('gc-withdraw:' . $user->id, 30);
        if (!$lock->get()) {
            return $this->fail('Another withdrawal is in progress. Please wait a moment.', 429);
        }

        try {
            $amount = round((float) $data['amount'], 2);

            $flw  = app(FlutterwaveClient::class);
            $bank = $this->bankByCode($flw, $data['bank_code']);
            if (!$bank) {
                return $this->fail('Please choose a valid bank.', 422);
            }

            // The account name is ALWAYS resolved server-side; the browser's copy is never trusted.
            $r = $flw->resolveAccount($data['account_number'], $data['bank_code']);
            if (!$r['ok'] || empty($r['data']['account_name'])) {
                return $this->fail('We could not verify this bank account. Please check the details.', 422);
            }
            $accountName = Str::limit(strip_tags((string) $r['data']['account_name']), 150, '');

            if (config('services.flutterwave.payout_require_name_match') && !$this->namesOverlap($user, $accountName)) {
                return $this->fail('The account name must match the name on your profile.', 422);
            }

            $withdrawal = null;
            $err = DB::transaction(function () use ($user, $data, $amount, $bank, $accountName, &$withdrawal) {
                $row = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
                if ((float) $row->withdrawable_balance < $amount) {
                    return 'Amount exceeds your withdrawable balance.';
                }

                DB::table('users')->where('id', $user->id)->decrement('withdrawable_balance', $amount);

                $withdrawal = (new GiftcardWithdrawal())->forceFill([
                    'reference'      => 'GCW-' . strtoupper(Str::random(10)),
                    'client_token'   => $data['client_token'],
                    'user_id'        => $user->id,
                    'amount'         => $amount,
                    'bank_name'      => $bank['name'],
                    'bank_code'      => $bank['code'],
                    'account_number' => $data['account_number'],
                    'account_name'   => $accountName,
                    'status'         => 'pending',
                    'provider'       => 'flutterwave',
                ]);
                $withdrawal->save();

                return null;
            });

            if ($err) {
                return $this->fail($err, 422);
            }

            // Pay out AFTER the HTTP response is sent so the user is never kept waiting on the bank API.
            if (config('services.flutterwave.payout_auto', true)) {
                $id = $withdrawal->id;
                dispatch(function () use ($id) {
                    app(FlutterwavePayoutService::class)->process($id);
                })->afterResponse();
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal submitted. ₦' . number_format($amount, 2) . ' is being sent to '
                    . $bank['name'] . ' ' . FlutterwaveClient::maskAccount($data['account_number']) . '. Track it below.',
            ]);
        } finally {
            $lock->release();
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function bankByCode(FlutterwaveClient $flw, string $code): ?array
    {
        return collect($flw->banks() ?? [])->firstWhere('code', $code);
    }

    /** True when at least one profile name part appears in the resolved bank account name. */
    private function namesOverlap($user, string $accountName): bool
    {
        $hay = Str::lower($accountName);
        foreach ([$user->first_name, $user->last_name] as $part) {
            $part = Str::lower(trim((string) $part));
            if (strlen($part) >= 3 && str_contains($hay, $part)) {
                return true;
            }
        }
        return false;
    }
}