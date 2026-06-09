<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralEarning;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Called after a successful deposit/credit to trigger referral commission.
     * Pass the Transaction model (or just the amount + user).
     */
    public function handleDeposit(User $depositor, float $depositAmount, ?int $transactionId = null): void
    {
        // Does this depositor have a referrer?
        if (!$depositor->referred_by) {
            return;
        }

        $referral = Referral::where('referrer_id', $depositor->referred_by)
            ->where('referred_id', $depositor->id)
            ->first();

        if (!$referral) {
            Log::warning('ReferralService: referral record missing', [
                'depositor'   => $depositor->id,
                'referred_by' => $depositor->referred_by,
            ]);
            return;
        }

        $referrer   = $referral->referrer;
        if (!$referrer) return;

        $percentage = $referrer->referralPercentage();
        if ($percentage <= 0) return;

        $commission = round($depositAmount * $percentage / 100, 2);
        if ($commission <= 0) return;

        DB::transaction(function () use ($referrer, $depositor, $referral, $commission, $percentage, $depositAmount, $transactionId) {
            // Credit referral balance
            $referrer->creditReferralBalance($commission);

            // Log the earning
            ReferralEarning::create([
                'referral_id'    => $referral->id,
                'referrer_id'    => $referrer->id,
                'referred_id'    => $depositor->id,
                'transaction_id' => $transactionId,
                'deposit_amount' => $depositAmount,
                'percentage'     => $percentage,
                'commission'     => $commission,
            ]);

            // Update cumulative total on the referral record
            $referral->increment('total_earned', $commission);
        });

        Log::info('ReferralService: commission credited', [
            'referrer'   => $referrer->id,
            'referred'   => $depositor->id,
            'commission' => $commission,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Register a new referral relationship after registration.
     */
    public function registerReferral(User $referred, User $referrer): void
    {
        Referral::firstOrCreate(
            ['referrer_id' => $referrer->id, 'referred_id' => $referred->id],
            ['total_earned' => 0.00]
        );
    }

    /**
     * Validate a referral code (username). Returns the User or null.
     */
    public function resolveCode(string $code): ?User
    {
        return User::where('username', $code)->first();
    }
}