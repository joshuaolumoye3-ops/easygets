<?php

namespace App\Http\Controllers;

use App\Models\PartnerWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerWithdrawalController extends Controller
{
    public function request(Request $request)
    {
        $user = Auth::user();

        if (!$user->is_partner) {
            return response()->json([
                'success' => false,
                'message' => 'Only partner accounts can request withdrawals.',
            ], 403);
        }

        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:30',
            'account_name'   => 'required|string|max:150',
        ]);

        $amount = (float) $request->amount;

        // Minimum withdrawal check
        if ($user->partner_min_withdrawal && $amount < $user->partner_min_withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum withdrawal is ₦' . number_format($user->partner_min_withdrawal, 2) . '.',
            ], 422);
        }

        // Balance check
        if ($user->referral_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient partner balance. Available: ₦' . number_format($user->referral_balance, 2),
            ], 422);
        }

        // Block if there's already a pending withdrawal
        $hasPending = PartnerWithdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Deduct balance immediately (held until approved/declined)
            if (!$user->debitReferralBalance($amount)) {
                throw new \Exception('Insufficient balance.');
            }

            $reference = 'WITHDRAW_' . strtoupper(Str::random(10));

            PartnerWithdrawal::create([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'bank_name'      => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name'   => $request->account_name,
                'status'         => 'pending',
                'reference'      => $reference,
            ]);

            DB::commit();

            Log::info('Partner withdrawal requested', [
                'user_id'   => $user->id,
                'amount'    => $amount,
                'reference' => $reference,
            ]);

            return response()->json([
                'success'         => true,
                'message'         => '₦' . number_format($amount, 2) . ' withdrawal request submitted. We will process it shortly.',
                'new_balance'     => number_format($user->fresh()->referral_balance, 2),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner withdrawal error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to submit withdrawal request.'], 500);
        }
    }
}