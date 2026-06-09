<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminTransaction;
use App\Models\PartnerWithdrawal;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AdminReferralController extends Controller
{
    // ── Global Settings ───────────────────────────────────────

    public function updateGlobalPercentage(Request $request)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        ReferralSetting::set('global_referral_percentage', $request->percentage);

        return response()->json([
            'success'    => true,
            'message'    => 'Global referral percentage updated to ' . $request->percentage . '%.',
            'percentage' => $request->percentage,
        ]);
    }

    // ── Promote / Demote Partner ──────────────────────────────

    public function promoteToPartner(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'partner_percentage'     => 'required|numeric|min:0|max:100',
            'partner_min_withdrawal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $user  = User::findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $user->promoteToPartner(
                (float) $request->partner_percentage,
                (float) $request->partner_min_withdrawal
            );

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'PROMOTE_PARTNER',
                'amount'           => 0,
                'affected_user_id' => $user->id,
                'description'      => sprintf(
                    'Promoted %s to Partner. Commission: %s%%. Min withdrawal: ₦%s.',
                    $user->username,
                    $request->partner_percentage,
                    number_format($request->partner_min_withdrawal, 2)
                ),
                'reference' => 'PARTNER_' . strtoupper(Str::random(8)),
            ]);

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => $user->username . ' has been promoted to Partner.',
                'is_partner' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Promote partner error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to promote user.'], 500);
        }
    }

    public function demoteFromPartner(Request $request, $userId)
    {
        try {
            DB::beginTransaction();

            $user  = User::findOrFail($userId);
            $admin = Auth::guard('admins')->user();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $user->demoteFromPartner();

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'DEMOTE_PARTNER',
                'amount'           => 0,
                'affected_user_id' => $user->id,
                'description'      => 'Demoted ' . $user->username . ' from Partner to standard user.',
                'reference'        => 'DEMOTE_' . strtoupper(Str::random(8)),
            ]);

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => $user->username . ' has been demoted to standard user.',
                'is_partner' => false,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Demote partner error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to demote user.'], 500);
        }
    }

    // ── Get partner/referral info for user modal ──────────────

    public function getUserReferralInfo($userId)
    {
        try {
            $user = User::with([
                'referrer',
                'referrals.referred',
                'referralEarnings' => fn ($q) => $q->latest()->limit(10),
                'partnerWithdrawals' => fn ($q) => $q->latest()->limit(5),
            ])->findOrFail($userId);

            return response()->json([
                'success'             => true,
                'is_partner'          => $user->is_partner,
                'partner_percentage'  => $user->partner_percentage,
                'partner_min_withdrawal' => $user->partner_min_withdrawal,
                'referral_balance'    => $user->referral_balance,
                'referral_code'       => $user->username,
                'referred_by'         => $user->referrer ? $user->referrer->username : null,
                'total_referrals'     => $user->referrals->count(),
                'total_earned'        => $user->referrals->sum('total_earned'),
                'recent_earnings'     => $user->referralEarnings->map(fn ($e) => [
                    'commission'     => $e->commission,
                    'deposit_amount' => $e->deposit_amount,
                    'percentage'     => $e->percentage,
                    'created_at'     => $e->created_at?->format('d M Y'),
                ]),
                'recent_withdrawals'  => $user->partnerWithdrawals->map(fn ($w) => [
                    'amount'    => $w->amount,
                    'status'    => $w->status,
                    'reference' => $w->reference,
                    'created_at'=> $w->created_at?->format('d M Y'),
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
    }

    // ── Withdrawals List ──────────────────────────────────────

    public function withdrawals(Request $request)
    {
        $withdrawals = PartnerWithdrawal::with(['user', 'processedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('admin.partner-withdrawals', compact('withdrawals'));
    }

    public function approveWithdrawal(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $withdrawal = PartnerWithdrawal::where('status', 'pending')->findOrFail($id);
            $admin      = Auth::guard('admins')->user();

            $withdrawal->update([
                'status'       => 'approved',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_note'   => $request->note ?? null,
            ]);

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'APPROVE_WITHDRAWAL',
                'amount'           => $withdrawal->amount,
                'affected_user_id' => $withdrawal->user_id,
                'description'      => sprintf(
                    'Approved partner withdrawal of ₦%s for %s. Ref: %s.',
                    number_format($withdrawal->amount, 2),
                    $withdrawal->user->username ?? 'unknown',
                    $withdrawal->reference
                ),
                'reference' => 'APV_' . strtoupper(Str::random(8)),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve withdrawal error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to approve withdrawal.'], 500);
        }
    }

    public function declineWithdrawal(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $withdrawal = PartnerWithdrawal::where('status', 'pending')->findOrFail($id);
            $admin      = Auth::guard('admins')->user();
            $user       = $withdrawal->user;

            // Refund the balance
            $user->creditReferralBalance((float) $withdrawal->amount);

            $withdrawal->update([
                'status'       => 'declined',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_note'   => $request->note ?? null,
            ]);

            AdminTransaction::create([
                'admin_id'         => $admin->id,
                'action'           => 'DECLINE_WITHDRAWAL',
                'amount'           => $withdrawal->amount,
                'affected_user_id' => $withdrawal->user_id,
                'description'      => sprintf(
                    'Declined partner withdrawal of ₦%s for %s. Refunded. Ref: %s.',
                    number_format($withdrawal->amount, 2),
                    $user->username ?? 'unknown',
                    $withdrawal->reference
                ),
                'reference' => 'DEC_' . strtoupper(Str::random(8)),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal declined and balance refunded.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Decline withdrawal error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to decline withdrawal.'], 500);
        }
    }
}