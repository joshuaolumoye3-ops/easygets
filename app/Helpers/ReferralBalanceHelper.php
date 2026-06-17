<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Wallet;

class ReferralBalanceHelper
{
    /**
     * Check if a user is eligible to use referral balance.
     * Only non-partners with a positive referral balance qualify.
     */
    public static function isEligible(User $user): bool
    {
        return !$user->is_partner && (float) $user->referral_balance > 0;
    }

    /**
     * Calculate the split between referral balance and main wallet.
     * Call this for UI previews and pre-flight checks.
     *
     * Returns:
     *   referral_deduction  — amount taken from referral_balance
     *   wallet_deduction    — remaining amount taken from main wallet
     *   can_proceed         — whether combined funds cover the cost
     *   shortfall           — how much is still missing (0 if can_proceed)
     */
    public static function calculate(User $user, Wallet $wallet, float $totalCost): array
    {
        $referralBalance = (float) $user->referral_balance;
        $walletBalance   = (float) $wallet->balance;

        $referralDeduction = round(min($referralBalance, $totalCost), 2);
        $walletDeduction   = round($totalCost - $referralDeduction, 2);

        $canProceed = $walletBalance >= $walletDeduction;
        $shortfall  = $canProceed ? 0.00 : round($walletDeduction - $walletBalance, 2);

        return [
            'referral_deduction' => $referralDeduction,
            'wallet_deduction'   => $walletDeduction,
            'can_proceed'        => $canProceed,
            'shortfall'          => $shortfall,
        ];
    }

    /**
     * Apply the actual deductions inside an open DB transaction.
     * Call ONLY after a successful API response.
     * The caller owns the transaction boundary — do NOT commit here.
     *
     * @throws \Exception if balances are insufficient at deduction time
     */
    public static function apply(User $user, Wallet $wallet, float $totalCost): array
    {
        // Fresh read to guard against race conditions
        $user->refresh();
        $wallet->refresh();

        $referralDeduction = round(min((float) $user->referral_balance, $totalCost), 2);
        $walletDeduction   = round($totalCost - $referralDeduction, 2);

        if ((float) $wallet->balance < $walletDeduction) {
            throw new \Exception(
                'Insufficient wallet balance after referral deduction. ' .
                'Needed: ₦' . number_format($walletDeduction, 2) .
                ', Available: ₦' . number_format($wallet->balance, 2)
            );
        }

        if ($referralDeduction > 0) {
            $user->decrement('referral_balance', $referralDeduction);
        }

        if ($walletDeduction > 0) {
            $wallet->decrement('balance', $walletDeduction);
        }

        return [
            'referral_deduction' => $referralDeduction,
            'wallet_deduction'   => $walletDeduction,
        ];
    }
}