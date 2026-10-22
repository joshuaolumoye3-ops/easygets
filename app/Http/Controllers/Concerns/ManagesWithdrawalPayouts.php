<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GiftcardWithdrawal;
use App\Services\Flutterwave\FlutterwaveClient;
use App\Services\Payments\FlutterwavePayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * USAGE: in App\Http\Controllers\AdminGiftcardController
 *   1. DELETE the existing approveWithdrawal() and declineWithdrawal() methods
 *   2. add `use \App\Http\Controllers\Concerns\ManagesWithdrawalPayouts;` inside the class
 *
 * The existing routes/buttons keep working:
 *   - "Approve" now means "Process payout via Flutterwave"
 *   - "Decline" refunds the user's withdrawable balance (only if no transfer was sent)
 */
trait ManagesWithdrawalPayouts
{
    /** Pay (or re-try paying) a pending withdrawal through Flutterwave. */
    public function approveWithdrawal(Request $request, $id)
    {
        $w = GiftcardWithdrawal::findOrFail($id);

        $res = app(FlutterwavePayoutService::class)->process($w->id, $this->currentAdminId());

        return $this->payoutResponse($request, $res);
    }

    public function declineWithdrawal(Request $request, $id)
    {
        $w = GiftcardWithdrawal::findOrFail($id);

        $reason = trim((string) $request->input('reason', 'Declined by admin'));

        $res = app(FlutterwavePayoutService::class)->decline($w->id, $this->currentAdminId(), $reason);

        return $this->payoutResponse($request, $res);
    }

    /** Ask Flutterwave for the real status of a 'processing' withdrawal. */
    public function syncWithdrawal(Request $request, $id)
    {
        $w = GiftcardWithdrawal::findOrFail($id);

        $res = app(FlutterwavePayoutService::class)->sync($w, $this->currentAdminId());

        return $this->payoutResponse($request, $res);
    }

    /** JSON: current Flutterwave NGN balance for the admin header / withdrawals page. */
    public function payoutBalance(FlutterwaveClient $flw): JsonResponse
    {
        $b = $flw->balance('NGN');

        return response()->json([
            'success'   => $b['ok'],
            'available' => $b['available'],
            'message'   => $b['ok'] ? 'OK' : 'Could not fetch the Flutterwave balance.',
        ]);
    }

    private function payoutResponse(Request $request, array $res)
    {
        $status = $res['status'];
        $completed = in_array($status, ['approved', 'paid'], true);
        $handled = in_array($status, ['approved', 'paid', 'processing', 'declined'], true);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $handled,
                'completed' => $completed,
            ] + $res, $status === 'processing' ? 202 : ($handled ? 200 : 422));
        }

        $flashType = $completed || $status === 'declined'
            ? 'success'
            : ($status === 'processing' ? 'warning' : 'error');

        return back()->with($flashType, $res['message']);
    }

    private function currentAdminId(): ?int
    {
        try {
            return auth('admins')->id() ? (int) auth('admins')->id() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}