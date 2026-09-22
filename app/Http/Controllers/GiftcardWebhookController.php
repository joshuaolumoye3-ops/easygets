<?php

namespace App\Http\Controllers;

use App\Helpers\PrestmitHelper;
use App\Models\GiftcardOrder;
use App\Services\GiftcardSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GiftcardWebhookController extends Controller
{
    public function handle(Request $request, PrestmitHelper $prestmit, GiftcardSettlement $settle)
    {
        $raw = $request->getContent();

        if (!$prestmit->verifyWebhookSignature($raw, $request->header('x-prestmit-signature'))) {
            Log::warning('Prestmit webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);
        $event   = (string) ($payload['event'] ?? '');
        $data    = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (!str_starts_with($event, 'giftcard-trade.')) {
            return response()->json(['message' => 'Ignored'], 200);
        }

        $refs = array_values(array_filter([
            $data['reference'] ?? null, $data['partnersApiIdentifier'] ?? null,
            $data['uniqueIdentifier'] ?? null, $data['trade']['reference'] ?? null,
        ]));
        if (empty($refs)) {
            return response()->json(['message' => 'No reference'], 200);
        }

        $order = GiftcardOrder::whereIn('provider_reference', $refs)->orWhereIn('reference', $refs)->first();
        if (!$order) {
            Log::info('Prestmit webhook: no matching order', ['event' => $event, 'refs' => $refs]);
            return response()->json(['message' => 'Unknown order'], 200);
        }

        try {
            // Source of truth = Prestmit's API, not the webhook body.
            $res = $settle->syncOrder($order);
            if (!$res['ok'] && str_ends_with($event, '.rejected')) {
                $status = 'REJECTED';
                $settle->applyStatus($order, $status, ['rejectReason' => $data['rejectReason'] ?? $data['rejectionReason'] ?? null]);
            }
        } catch (\Throwable $e) {
            Log::error('Prestmit webhook processing failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'OK'], 200);
    }
}