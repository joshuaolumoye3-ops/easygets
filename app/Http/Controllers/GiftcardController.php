<?php

namespace App\Http\Controllers;

use App\Helpers\PinHelper;
use App\Helpers\PrestmitHelper;
use App\Models\GiftcardOrder;
use App\Models\GiftcardSetting;
use App\Models\GiftcardWithdrawal;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\GiftcardSettlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GiftcardController extends Controller
{
    public function __construct(private PrestmitHelper $prestmit, private GiftcardSettlement $settle)
    {
    }

    // ── Pages ────────────────────────────────────────────────────────────

    public function index()
    {
        $user = Auth::user();
        return view('users.giftcard.index', [
            'withdrawable' => (float) DB::table('users')->where('id', $user->id)->value('withdrawable_balance'),
            'walletBalance' => (float) ($user->wallet->balance ?? 0),
            'buyActive'    => $this->serviceActive('giftcard-buy'),
            'sellActive'   => $this->serviceActive('giftcard-sell'),
            'minWithdrawal' => (float) GiftcardSetting::get('min_withdrawal'),
            'recent'       => GiftcardOrder::where('user_id', $user->id)->latest()->limit(5)->get(),
            'withdrawals'  => GiftcardWithdrawal::where('user_id', $user->id)->latest()->limit(5)->get(),
        ]);
    }

    public function buyPage()
    {
        $user = Auth::user();
        return view('users.giftcard.buy', [
            'walletBalance' => (float) ($user->wallet->balance ?? 0),
            'maxQty'        => (int) GiftcardSetting::get('max_buy_quantity'),
        ]);
    }

    public function sellPage()
    {
        return view('users.giftcard.sell');
    }

    // ── BUY ──────────────────────────────────────────────────────────────

    public function buyCatalog(Request $request): JsonResponse
    {
        if (!$this->serviceActive('giftcard-buy')) {
            return $this->fail('Gift card purchases are currently unavailable.', 503);
        }
        $cards = $this->prestmit->buyCatalog();
        if ($cards === null) {
            return $this->fail('The catalog is temporarily unavailable. Please try again shortly.', 503);
        }

        $q   = Str::lower(trim((string) $request->query('q', '')));
        $cat = trim((string) $request->query('category', ''));
        $all = collect($cards);

        $categories = $all->pluck('categories')->flatten()->unique()->sort()->values();
        $filtered = $all->filter(fn ($c) =>
            ($q === '' || str_contains(Str::lower($c['title']), $q))
            && ($cat === '' || in_array($cat, $c['categories'], true))
        )->values();

        $per  = 12;
        $page = max(1, (int) $request->query('page', 1));

        return response()->json([
            'success'    => true,
            'data'       => $filtered->slice(($page - 1) * $per, $per)->values(),
            'page'       => $page,
            'last_page'  => max(1, (int) ceil($filtered->count() / $per)),
            'total'      => $filtered->count(),
            'categories' => $categories,
        ]);
    }

    public function buyQuote(Request $request): JsonResponse
    {
        if (!$this->serviceActive('giftcard-buy')) {
            return $this->fail('Gift card purchases are currently unavailable.', 503);
        }
        $data = $request->validate([
            'sku' => 'required|integer|min:1', 'price' => 'required|integer|min:1', 'quantity' => 'required|integer|min:1|max:20',
        ]);
        if ($data['quantity'] > (int) GiftcardSetting::get('max_buy_quantity')) {
            return $this->fail('Maximum quantity per order is ' . (int) GiftcardSetting::get('max_buy_quantity') . '.', 422);
        }
        $card = $this->findBuyCard((int) $data['sku']);
        if (!$card) {
            return $this->fail('This card is no longer available.', 404);
        }
        $p = $this->priceBuy($card, (int) $data['price'], (int) $data['quantity']);
        if (!$p['ok']) {
            return $this->fail($p['message'], $p['code']);
        }
        return response()->json(['success' => true, 'total' => $p['total'], 'unit' => $p['unit']]);
    }

    public function buyPurchase(Request $request): JsonResponse
    {
        if (!$this->serviceActive('giftcard-buy')) {
            return $this->fail('Gift card purchases are currently unavailable.', 503);
        }
        $data = $request->validate([
            'sku' => 'required|integer|min:1', 'price' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:20', 'pin' => 'required|digits:4',
            'client_token' => 'required|string|max:64', 'expected_total' => 'required|numeric',
        ]);
        $user = Auth::user();

        if ($resp = $this->checkPin($request, $user)) {
            return $resp;
        }

        $lock = Cache::lock('gc-buy-' . $user->id, 25);
        if (!$lock->get()) {
            return $this->fail('Another purchase is in progress. Please wait a moment.', 429);
        }
        try {
            return $this->doBuy($user, $data);
        } finally {
            $lock->release();
        }
    }

    private function doBuy($user, array $data): JsonResponse
    {
        if ($existing = GiftcardOrder::where('client_token', $data['client_token'])->where('user_id', $user->id)->first()) {
            return response()->json(['success' => true, 'order' => $existing->toUserArray(true)]);
        }

        $qty = (int) $data['quantity'];
        if ($qty > (int) GiftcardSetting::get('max_buy_quantity')) {
            return $this->fail('Maximum quantity per order is ' . (int) GiftcardSetting::get('max_buy_quantity') . '.', 422);
        }
        $card = $this->findBuyCard((int) $data['sku']);
        if (!$card) {
            return $this->fail('This card is no longer available.', 404);
        }

        $p = $this->priceBuy($card, (int) $data['price'], $qty);
        if (!$p['ok']) {
            return $this->fail($p['message'], $p['code']);
        }
        if (abs($p['total'] - (float) $data['expected_total']) > 0.01) {
            return response()->json([
                'success' => false, 'price_changed' => true, 'new_total' => $p['total'],
                'message' => 'The price has changed. Please review the new total.',
            ], 409);
        }

        $limit = (float) GiftcardSetting::get('daily_buy_limit_ngn');
        if ($limit > 0) {
            $today = (float) GiftcardOrder::where('user_id', $user->id)->where('type', 'buy')
                ->whereDate('created_at', today())->whereNotIn('status', ['failed', 'refunded'])->sum('total_ngn');
            if ($today + $p['total'] > $limit) {
                return $this->fail('This purchase would exceed your daily limit of ₦' . number_format($limit) . '.', 422);
            }
        }

        // 1) Debit wallet + create order atomically
        $order = null;
        $err = DB::transaction(function () use ($user, $card, $data, $qty, $p, &$order) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || (float) $wallet->balance < $p['total']) {
                return 'Insufficient wallet balance. Please fund your wallet and try again.';
            }
            $wallet->balance = round((float) $wallet->balance - $p['total'], 2);
            $wallet->save();

            $order = GiftcardOrder::create([
                'reference' => 'GCB-' . strtoupper(Str::random(12)), 'client_token' => $data['client_token'],
                'user_id' => $user->id, 'type' => 'buy', 'status' => 'pending',
                'product_id' => $card['sku'], 'title' => $card['title'], 'category' => $card['categories'][0] ?? null,
                'image_url' => $card['image'], 'currency_code' => $card['currency'],
                'face_value' => (int) $data['price'], 'quantity' => $qty, 'unit_rate' => $p['unit'],
                'total_ngn' => $p['total'], 'pricing' => $p['snapshot'], 'wallet_debited' => true,
            ]);

            Transaction::create([
                'reference' => $order->reference, 'user_id' => $user->id, 'type' => 'giftcard_buy',
                'channel' => 'Wallet', 'provider' => 'Giftcard', 'recipient' => $card['title'],
                'amount' => $p['total'], 'status' => 'pending', 'description' => 'Gift card purchase — ' . $card['title'],
                'balance_after' => $wallet->balance,
            ]);
            return null;
        });
        if ($err) {
            return $this->fail($err, 422);
        }

        // 2) Place the order with Prestmit
        $r = $this->prestmit->request('POST', 'giftcard-trade/buy/create', [
            'giftCardSKU' => $card['sku'], 'price' => (int) $data['price'], 'quantity' => $qty,
            'paymentMethod' => 'NAIRA', 'uniqueIdentifier' => $order->reference,
            'currentAccountPIN' => config('services.prestmit.account_pin'),
        ]);

        if (!$r['ok']) {
            if ($r['ambiguous']) {
                // Outcome unknown: do NOT refund. Reconciliation resolves it safely.
                $order->forceFill(['status' => 'processing', 'needs_review' => true])->save();
                Log::error('Giftcard buy ambiguous', ['order' => $order->reference, 'error' => $r['error']]);
                return response()->json(['success' => true, 'order' => $order->fresh()->toUserArray(true)]);
            }
            Log::error('Giftcard buy rejected by provider', ['order' => $order->reference, 'status' => $r['status'], 'error' => $r['error']]);
            $this->settle->failBuy($order, 'Provider declined the order.');
            return $this->fail("We couldn't complete this purchase right now. You have not been charged.", 422);
        }

        $d = $r['data']['data'] ?? $r['data'];
        $status = strtoupper((string) ($d['status'] ?? 'PENDING'));
        $order->forceFill([
            'provider_reference' => $d['reference'] ?? null,
            'provider_amount_ngn' => $d['totalPaymentAmount'] ?? null,
            'status' => 'processing',
        ])->save();
        $this->settle->applyStatus($order, $status, $d);

        return response()->json(['success' => true, 'order' => $order->fresh()->toUserArray(true)]);
    }

    private function findBuyCard(int $sku): ?array
    {
        foreach (($this->prestmit->buyCatalog() ?? []) as $c) {
            if ($c['sku'] === $sku) {
                return $c;
            }
        }
        return null;
    }

    /** Server-authoritative price. Never trusts anything from the browser. */
    private function priceBuy(array $card, int $price, int $qty): array
    {
        $unavailable = ['ok' => false, 'message' => 'This card is temporarily unavailable. Please try again later.', 'code' => 503];

        if ($price < $card['min'] || $price > $card['max']) {
            return ['ok' => false, 'code' => 422,
                'message' => 'Amount must be between ' . $card['symbol'] . $card['min'] . ' and ' . $card['symbol'] . $card['max'] . '.'];
        }

        $r = $this->prestmit->request('POST', 'giftcard-trade/buy/calculate-payment', [
            'giftCardSKU' => $card['sku'], 'price' => $price, 'quantity' => $qty,
        ]);
        if (!$r['ok']) {
            return $unavailable;
        }
        $d = $r['data']['data'] ?? $r['data'];
        if (array_key_exists('isAvailable', $d) && !$d['isAvailable']) {
            return ['ok' => false, 'message' => 'This card is currently out of stock.', 'code' => 422];
        }

        $costNgn = (float) ($d['NAIRA'] ?? 0);
        $costUsd = (float) ($d['USD'] ?? 0);
        $rate    = (float) GiftcardSetting::get('buy_usd_rate');
        $markup  = (float) GiftcardSetting::get('buy_markup_percent');
        $baseUsd = $card['currency'] === 'USD' ? (float) ($price * $qty) : $costUsd;

        if ($baseUsd <= 0 || $rate <= 0) {
            return $unavailable;
        }
        $total = round($baseUsd * (1 + $markup / 100) * $rate, 2);

        // Margin guard: never sell below what Prestmit charges us.
        if ($costNgn > 0 && $total < $costNgn) {
            Log::error('Giftcard margin guard blocked sale', ['sku' => $card['sku'], 'total' => $total, 'cost' => $costNgn]);
            return $unavailable;
        }

        return [
            'ok' => true, 'total' => $total, 'unit' => round($total / $qty, 2),
            'snapshot' => [
                'usd_rate' => $rate, 'markup_percent' => $markup, 'base_usd' => $baseUsd,
                'provider_cost_ngn' => $costNgn, 'provider_cost_usd' => $costUsd,
            ],
        ];
    }

    // ── SELL ─────────────────────────────────────────────────────────────

    public function sellCatalog(): JsonResponse
    {
        if (!$this->serviceActive('giftcard-sell')) {
            return $this->fail('Gift card sales are currently unavailable.', 503);
        }
        $cat = $this->prestmit->sellCatalog();
        if ($cat === null) {
            return $this->fail('Rates are temporarily unavailable. Please try again shortly.', 503);
        }
        $c = (float) GiftcardSetting::get('sell_commission_percent');

        $cards = collect($cat['cards'])->filter(fn ($x) => (float) ($x['rate'] ?? 0) > 0)->map(function ($x) use ($c) {
            $name = (string) ($x['name'] ?? '');
            return [
                'id'       => (int) $x['id'],
                'name'     => $name,
                'category' => (string) ($x['category']['name'] ?? 'Other'),
                'country'  => (string) ($x['country'] ?? ''),
                'form'     => (string) ($x['form'] ?? ''),
                'minimum'  => (float) ($x['minimum'] ?? 0),
                'rate'     => round((float) $x['rate'] * (1 - $c / 100), 4),
                'terms'    => $this->prestmit->cleanHtml($x['terms'] ?? ''),
                'icons'    => $this->prestmit->brandIconUrls($name),
                'badge'    => $this->prestmit->badge($name),
                'flag'     => $this->prestmit->countryFlag($x['country'] ?? null),
            ];
        })->values();

        $categoryIcons  = $cards->groupBy('category')->map(fn ($g) => $g->first()['icons'])->all();
        $categoryBadges = $cards->groupBy('category')->map(fn ($g) => $g->first()['badge'])->all();

        return response()->json([
            'success'        => true,
            'cards'          => $cards,
            'categories'     => $cards->pluck('category')->unique()->sort()->values(),
            'categoryIcons'  => $categoryIcons,
            'categoryBadges' => $categoryBadges,
        ]);
    }

    public function sellSubmit(Request $request): JsonResponse
    {
        if (!$this->serviceActive('giftcard-sell')) {
            return $this->fail('Gift card sales are currently unavailable.', 503);
        }
        $data = $request->validate([
            'giftcard_id' => 'required|integer|min:1', 'amount' => 'required|numeric|min:1|max:100000',
            'comments' => 'nullable|string|max:2000', 'client_token' => 'required|string|max:64',
            'expected_payout' => 'required|numeric', 'pin' => 'required|digits:4',
            'images' => 'nullable|array|max:20', 'images.*' => 'file|image|mimes:jpg,jpeg,png|max:5120',
        ]);
        $user = Auth::user();

        if ($resp = $this->checkPin($request, $user)) {
            return $resp;
        }

        $lock = Cache::lock('gc-sell-' . $user->id, 60);
        if (!$lock->get()) {
            return $this->fail('Another submission is in progress. Please wait a moment.', 429);
        }
        try {
            return $this->doSell($request, $user, $data);
        } finally {
            $lock->release();
        }
    }

    private function doSell(Request $request, $user, array $data): JsonResponse
    {
        if ($existing = GiftcardOrder::where('client_token', $data['client_token'])->where('user_id', $user->id)->first()) {
            return response()->json(['success' => true, 'order' => $existing->toUserArray()]);
        }

        $catalog = $this->prestmit->sellCatalog();
        $card = collect($catalog['cards'] ?? [])->firstWhere('id', (int) $data['giftcard_id']);
        if (!$card || (float) ($card['rate'] ?? 0) <= 0) {
            return $this->fail('This card type is no longer available.', 404);
        }

        $amount = (float) $data['amount'];
        if ($amount < (float) ($card['minimum'] ?? 0)) {
            return $this->fail('Minimum amount for this card is ' . $card['minimum'] . '.', 422);
        }

        $comments = trim((string) ($data['comments'] ?? ''));
        $images   = $request->file('images', []);
        $form     = strtolower((string) ($card['form'] ?? ''));
        if ($form === 'physical' && empty($images)) {
            return $this->fail('Please upload clear photos of the card.', 422);
        }
        if ($form === 'ecode' && $comments === '') {
            return $this->fail('Please enter the card code.', 422);
        }
        if (empty($images) && $comments === '') {
            return $this->fail('Please upload card photos or enter the card code.', 422);
        }

        $commission = (float) GiftcardSetting::get('sell_commission_percent');
        $payout = floor($amount * (float) $card['rate'] * (1 - $commission / 100) * 100) / 100;
        if (abs($payout - (float) $data['expected_payout']) > max(1, $payout * 0.02)) {
            return response()->json([
                'success' => false, 'price_changed' => true, 'new_payout' => $payout,
                'message' => 'The rate has changed. Please review the new payout.',
            ], 409);
        }

        $paths = [];
        $files = [];
        foreach ($images as $img) {
            $paths[] = $img->store('giftcards/sell/' . $user->id, 'local');
            $files[] = ['content' => file_get_contents($img->getRealPath()), 'name' => Str::random(12) . '.' . $img->extension()];
        }

        $order = GiftcardOrder::create([
            'reference' => 'GCS-' . strtoupper(Str::random(12)), 'client_token' => $data['client_token'],
            'user_id' => $user->id, 'type' => 'sell', 'status' => 'pending',
            'product_id' => (int) $card['id'], 'title' => (string) ($card['name'] ?? 'Gift card'),
            'category' => $card['category'] ?? null, 'card_form' => $card['form'] ?? null,
            'country' => $card['country'] ?? null, 'currency_code' => 'USD',
            'face_value' => $amount, 'quantity' => 1, 'unit_rate' => round((float) $card['rate'] * (1 - $commission / 100), 4),
            'total_ngn' => $payout, 'attachments' => $paths, 'comments' => $comments ?: null,
            'pricing' => ['provider_rate' => (float) $card['rate'], 'commission_percent' => $commission],
        ]);

        $fields = ['giftcard_id' => (int) $card['id'], 'amount' => $amount, 'payoutMethod' => 'NAIRA'];
        if ($comments !== '') {
            $fields['comments'] = $comments;
        }
        $fields['uniqueIdentifier'] = $order->reference;

        $r = $this->prestmit->request('POST', 'giftcard-trade/sell/create', $fields, $files);

        if (!$r['ok']) {
            if ($r['ambiguous']) {
                $order->forceFill(['needs_review' => true])->save();
                Log::error('Giftcard sell ambiguous', ['order' => $order->reference, 'error' => $r['error']]);
                return response()->json(['success' => true, 'order' => $order->fresh()->toUserArray()]);
            }
            $this->settle->rejectSell($order, Str::limit(strip_tags((string) $r['error']), 200), 'failed');
            return $this->fail(Str::limit(strip_tags((string) $r['error']), 200) ?: 'Submission failed. Please try again.', 422);
        }

        $trade = $r['data']['trade'] ?? $r['data']['data'] ?? $r['data'];
        $order->forceFill([
            'provider_reference' => $trade['reference'] ?? null,
            'provider_amount_ngn' => $trade['totalAmount'] ?? null,
            'provider_status' => strtoupper((string) ($trade['status'] ?? 'PENDING')),
        ])->save();

        return response()->json(['success' => true, 'order' => $order->fresh()->toUserArray()]);
    }

    // ── Orders ───────────────────────────────────────────────────────────

    public function orderDetail(string $reference): JsonResponse
    {
        $o = GiftcardOrder::where('reference', $reference)->where('user_id', Auth::id())->first();
        if (!$o) {
            return $this->fail('Order not found.', 404);
        }
        return response()->json(['success' => true, 'order' => $o->toUserArray(true)]);
    }

    public function orderRefresh(string $reference): JsonResponse
    {
        $o = GiftcardOrder::where('reference', $reference)->where('user_id', Auth::id())->first();
        if (!$o) {
            return $this->fail('Order not found.', 404);
        }
        if (!$o->settled_at && Cache::add('gc-refresh-' . $o->id, 1, 10)) {
            $this->settle->syncOrder($o);
        }
        return response()->json(['success' => true, 'order' => $o->fresh()->toUserArray(true)]);
    }

    // ── Withdraw ─────────────────────────────────────────────────────────

    public function withdraw(Request $request): JsonResponse
    {
        $min  = (float) GiftcardSetting::get('min_withdrawal');
        $data = $request->validate([
            'amount' => 'required|numeric|min:' . $min, 'bank_name' => 'required|string|max:100',
            'account_number' => ['required', 'regex:/^[0-9]{10}$/'], 'account_name' => 'required|string|max:150',
            'pin' => 'required|digits:4',
        ], ['account_number.regex' => 'Account number must be 10 digits.']);
        $user = Auth::user();

        if ($resp = $this->checkPin($request, $user)) {
            return $resp;
        }
        $amount = round((float) $data['amount'], 2);

        $err = DB::transaction(function () use ($user, $data, $amount) {
            $row = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if ((float) $row->withdrawable_balance < $amount) {
                return 'Amount exceeds your withdrawable balance.';
            }
            DB::table('users')->where('id', $user->id)->decrement('withdrawable_balance', $amount);
            GiftcardWithdrawal::create([
                'reference' => 'GCW-' . strtoupper(Str::random(10)), 'user_id' => $user->id, 'amount' => $amount,
                'bank_name' => strip_tags($data['bank_name']), 'account_number' => $data['account_number'],
                'account_name' => strip_tags($data['account_name']), 'status' => 'pending',
            ]);
            return null;
        });
        if ($err) {
            return $this->fail($err, 422);
        }
        return response()->json(['success' => true, 'message' => 'Withdrawal request submitted. It is typically processed within 24 hours.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function serviceActive(string $slug): bool
    {
        return (bool) Service::where('slug', $slug)->value('is_active');
    }

    private function checkPin(Request $request, $user): ?JsonResponse
    {
        if (empty($user->pin)) {
            return $this->fail('Please set your transaction PIN in your profile before continuing.', 422);
        }
        try {
            PinHelper::verifyPin((string) $request->input('pin'), $user);
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Incorrect PIN.', 422);
        }
        return null;
    }

    private function fail(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}