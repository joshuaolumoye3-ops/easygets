<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\FlutterwaveInflowProcessor;
use App\Services\Payments\FlutterwavePayoutService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single public endpoint for Flutterwave webhooks:
 *   charge.completed    -> deposit into a dedicated account
 *   transfer.completed  -> final status of a gift-card withdrawal payout
 *
 * Security layers:
 *   1. Shared-secret header (`verif-hash`) compared in constant time.
 *   2. Idempotency table so replays are no-ops.
 *   3. Money-moving data is re-fetched from Flutterwave's API by the services — the body is never trusted.
 *   4. Nothing sensitive (payload, secret, signature) is written to logs.
 */
class FlutterwaveEventController extends Controller
{
    public function handle(Request $request, FlutterwaveInflowProcessor $inflow, FlutterwavePayoutService $payouts)
    {
        $secret = (string) config('services.flutterwave.secret_hash');

        if ($secret === '') {
            Log::critical('Flutterwave webhook rejected: services.flutterwave.secret_hash is not configured.');
            return response('Server misconfigured', 500);
        }

        if (!hash_equals($secret, (string) $request->header('verif-hash', ''))) {
            Log::warning('Flutterwave webhook: invalid signature', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();
        $event   = (string) ($payload['event'] ?? '');
        $data    = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (!in_array($event, ['charge.completed', 'transfer.completed'], true) || empty($data['id'])) {
            return response('Ignored', 200);
        }

        // ── Idempotency ───────────────────────────────────────────────────
        $key = substr(sprintf('%s:%s:%s', $event, $data['id'], strtoupper((string) ($data['status'] ?? ''))), 0, 120);

        $row = DB::table('flutterwave_webhook_events')->where('event_key', $key)->first();
        if ($row && $row->processed_at) {
            return response('Duplicate', 200);
        }
        if (!$row) {
            try {
                DB::table('flutterwave_webhook_events')->insert([
                    'event_key' => $key, 'event' => $event, 'created_at' => now(), 'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                // concurrent identical delivery — downstream processing is idempotent too
            }
        }

        try {
            $result = $event === 'charge.completed'
                ? $inflow->handleChargeCompleted($data)
                : $payouts->applyTransferEvent($data);

            DB::table('flutterwave_webhook_events')->where('event_key', $key)
                ->update(['processed_at' => now(), 'updated_at' => now()]);

            Log::info('Flutterwave webhook processed', [
                'event' => $event, 'id' => $data['id'], 'result' => $result['status'] ?? null,
            ]);

            return response('OK', 200);
        } catch (\Throwable $e) {
            // 500 => Flutterwave retries (3x, 30 min apart). Safe: all handlers are idempotent.
            Log::error('Flutterwave webhook failed', ['event' => $event, 'id' => $data['id'], 'error' => $e->getMessage()]);
            return response('Internal error', 500);
        }
    }
}