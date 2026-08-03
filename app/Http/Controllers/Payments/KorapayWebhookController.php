<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\KorapayService;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KorapayWebhookController extends Controller
{
    public function __construct(protected KorapayService $korapay, protected PaymentProcessor $processor)
    {
    }

    public function handle(Request $request)
    {
        $payload = $request->json()->all();
        $event   = $payload['event'] ?? null;
        $data    = $payload['data'] ?? [];

        // ── 1. Verify the signature BEFORE doing anything else. ──────────
        $signature = $request->header('x-korapay-signature');

        if (!$this->korapay->verifySignature($data, $signature)) {
            Log::warning('Korapay webhook: invalid signature, rejected.', [
                'ip'    => $request->ip(),
                'event' => $event,
                // Deliberately not logging the payload/signature themselves.
            ]);
            return response('Invalid signature', 401);
        }

        Log::info('Korapay webhook: signature verified', ['event' => $event, 'reference' => $data['reference'] ?? null]);

        // ── 2. We only act on successful charges. Everything else is 200'd
        //      and ignored so Korapay doesn't keep retrying it forever. ───
        if ($event !== 'charge.success') {
            return response('Ignored', 200);
        }

        try {
            $result = $this->processor->handleChargeSuccess($data);

            Log::info('Korapay webhook: processed', [
                'reference' => $data['reference'] ?? null,
                'result'    => $result['status'],
            ]);

            // Always 200 to Korapay once we've handled it (or safely no-op'd),
            // so it doesn't keep retrying an event we've already dealt with.
            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('Korapay webhook: unhandled exception', [
                'reference' => $data['reference'] ?? null,
                'error'     => $e->getMessage(),
            ]);

            // 500 here is intentional: it tells Korapay to retry, which is what
            // we want if something genuinely broke on our end (e.g. DB down).
            // PaymentProcessor's idempotency guarantees a retry is always safe.
            return response('Internal error', 500);
        }
    }
}
