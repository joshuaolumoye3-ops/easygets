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
     * Register the referral relationship when a new user signs up.
     * Called once from RegisterController.
     */
    public function registerReferral(User $referred, User $referrer): void
    {
        try {
            // Prevent duplicate records
            if (Referral::where('referred_id', $referred->id)->exists()) {
                return;
            }

            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'total_earned' => 0,
            ]);

            Log::info('Referral relationship created', [
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
            ]);

        } catch (\Exception $e) {
            // Never block registration over a referral error
            Log::error('ReferralService::registerReferral failed', [
                'error'       => $e->getMessage(),
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
            ]);
        }
    }

    /**
     * Credit the referrer's referral_balance whenever the referred user
     * funds their wallet (real deposit or admin credit).
     *
     * Rules:
     *  - Only fires if the depositing user was referred by someone.
     *  - Uses the referrer's personal affiliate percentage if they are a
     *    partner; otherwise uses the global setting.
     *  - Silently swallows errors so the deposit itself is never blocked.
     *
     * @param  User       $depositor       The user whose wallet was credited.
     * @param  float      $depositAmount   The gross amount added to their wallet.
     * @param  int|null   $transactionId   Optional FK to the transactions table.
     * @param  string     $source          'deposit' | 'admin_credit' | etc.
     */
    public function creditCommissionOnDeposit(
        User $depositor,
        float $depositAmount,
        ?int $transactionId = null,
        string $source = 'deposit'
    ): void {
        try {
            // 1. Does this user have a referrer?
            $referralRecord = Referral::where('referred_id', $depositor->id)
                ->with('referrer')
                ->first();

            if (!$referralRecord || !$referralRecord->referrer) {
                return; // Not referred — nothing to do
            }

            /** @var User $referrer */
            $referrer = $referralRecord->referrer;

            // 2. Determine the correct percentage
            //    Partners use their own rate; everyone else uses global.
            if ($referrer->is_partner && $referrer->partner_percentage !== null) {
                $percentage = (float) $referrer->partner_percentage;
            } else {
                $percentage = ReferralSetting::globalPercentage();
            }

            if ($percentage <= 0) {
                return; // Nothing to award
            }

            // 3. Calculate commission
            $commission = round(($depositAmount * $percentage) / 100, 2);
            if ($commission <= 0) {
                return;
            }

            DB::transaction(function () use (
                $referralRecord, $referrer, $depositor,
                $depositAmount, $percentage, $commission,
                $transactionId, $source
            ) {
                // 4. Credit referrer's referral_balance
                $referrer->increment('referral_balance', $commission);

                // 5. Record the earning
                ReferralEarning::create([
                    'referral_id'    => $referralRecord->id,
                    'referrer_id'    => $referrer->id,
                    'referred_id'    => $depositor->id,
                    'transaction_id' => $transactionId,
                    'deposit_amount' => $depositAmount,
                    'percentage'     => $percentage,
                    'commission'     => $commission,
                ]);

                // 6. Keep the Referral total_earned in sync
                $referralRecord->increment('total_earned', $commission);
            });

            Log::info('Referral commission credited', [
                'referrer_id'    => $referrer->id,
                'referred_id'    => $depositor->id,
                'deposit_amount' => $depositAmount,
                'percentage'     => $percentage,
                'commission'     => $commission,
                'source'         => $source,
            ]);

        } catch (\Exception $e) {
            // Log but never throw — the deposit must not fail because of this
            Log::error('ReferralService::creditCommissionOnDeposit failed', [
                'depositor_id'   => $depositor->id,
                'deposit_amount' => $depositAmount,
                'source'         => $source,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);
        }
    }
}