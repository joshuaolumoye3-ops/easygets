<?php

namespace App\Http\Controllers;

use App\Helpers\PrestmitHelper;
use App\Models\AdminTransaction;
use App\Models\GiftcardOrder;
use App\Models\GiftcardSetting;
use App\Models\GiftcardWithdrawal;
use App\Models\Service;
use App\Services\GiftcardSettlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminGiftcardController extends Controller
{
    use \App\Http\Controllers\Concerns\ManagesWithdrawalPayouts;

    private const SERVICES = ['giftcard-buy' => 'Gift Card – Buy', 'giftcard-sell' => 'Gift Card – Sell'];

    public function index(Request $request, PrestmitHelper $prestmit)
    {
        foreach (self::SERVICES as $slug => $name) {
            Service::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => false]);
        }
        $services = Service::whereIn('slug', array_keys(self::SERVICES))->get()->keyBy('slug');

        $tab = in_array($request->query('tab'), ['overview', 'orders', 'withdrawals'], true) ? $request->query('tab') : 'overview';

        $orders = GiftcardOrder::with('user:id,username,email')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('review'), fn ($q) => $q->where('needs_review', true))
            ->when(trim((string) $request->q) !== '', function ($q) use ($request) {
                $s = trim($request->q);
                $q->where(fn ($w) => $w->where('reference', 'like', "%{$s}%")->orWhere('provider_reference', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")));
            })
            ->latest()->paginate(15, ['*'], 'op')->withQueryString();

        $withdrawals = GiftcardWithdrawal::with('user:id,username,email,withdrawable_balance')
            ->when($request->filled('wstatus'), fn ($q) => $q->where('status', $request->wstatus))
            ->latest()->paginate(15, ['*'], 'wp')->withQueryString();

        $stats = [
            'pending_sells'   => GiftcardOrder::where('type', 'sell')->whereIn('status', ['pending', 'processing'])->count(),
            'pending_buys'    => GiftcardOrder::where('type', 'buy')->whereIn('status', ['pending', 'processing'])->count(),
            'needs_review'    => GiftcardOrder::where('needs_review', true)->whereNull('settled_at')->count(),
            'buy_volume'      => (float) GiftcardOrder::where('type', 'buy')->where('status', 'completed')->sum('total_ngn'),
            'sell_payouts'    => (float) GiftcardOrder::where('type', 'sell')->where('status', 'completed')->sum('payout_ngn'),
            'withdrawable'    => (float) DB::table('users')->sum('withdrawable_balance'),
            'pending_wd'      => GiftcardWithdrawal::where('status', 'pending')->count(),
            'pending_wd_amt'  => (float) GiftcardWithdrawal::where('status', 'pending')->sum('amount'),
        ];

        $avail = $prestmit->serviceAvailability();

        return view('admin.giftcards', [
            'tab' => $tab, 'services' => $services, 'orders' => $orders, 'withdrawals' => $withdrawals,
            'stats' => $stats, 'settings' => GiftcardSetting::all_(),
            'prestmit' => ['configured' => $prestmit->isConfigured(), 'ok' => $avail['ok'], 'data' => $avail['data'], 'error' => $avail['error'], 'base' => config('services.prestmit.base_url')],
        ]);
    }

    public function saveSettings(Request $request)
    {
        $admin = Auth::guard('admins')->user();
        if (!$admin || $admin->role !== 'super_admin') {
            return back()->with('error', 'Only super admins can change gift card pricing.');
        }
        $data = $request->validate([
            'buy_usd_rate' => 'required|numeric|min:1|max:100000',
            'buy_markup_percent' => 'required|numeric|min:0|max:100',
            'sell_commission_percent' => 'required|numeric|min:0|max:90',
            'min_withdrawal' => 'required|numeric|min:100|max:100000000',
            'max_buy_quantity' => 'required|integer|min:1|max:20',
            'daily_buy_limit_ngn' => 'required|numeric|min:0|max:1000000000',
        ]);
        $old = GiftcardSetting::all_();
        foreach ($data as $k => $v) {
            GiftcardSetting::set($k, $v);
        }
        \Illuminate\Support\Facades\Cache::forget('prestmit:sell_catalog');

        $this->log($admin->id, 'GIFTCARD_SETTINGS', 0, null, 'Gift card settings updated. ' . json_encode(['from' => array_intersect_key($old, $data), 'to' => $data]));
        return back()->with('success', 'Gift card settings saved.');
    }

    public function orderDetail($id)
    {
        $o = GiftcardOrder::with('user:id,username,email,phone_number')->findOrFail($id);
        $codes = collect($o->codes ?? [])->map(fn ($c) => [
            'cardNumber' => $c['cardNumber'] !== '' ? '••••' . substr($c['cardNumber'], -4) : '',
            'claimUrl' => $c['claimUrl'] !== '' ? 'set' : '',
        ])->all();

        return response()->json(['success' => true, 'order' => [
            'id' => $o->id, 'reference' => $o->reference, 'provider_reference' => $o->provider_reference,
            'type' => $o->type, 'status' => $o->status, 'provider_status' => $o->provider_status,
            'user' => $o->user ? ($o->user->username . ' (' . $o->user->email . ')') : '—',
            'title' => $o->title, 'category' => $o->category, 'form' => $o->card_form, 'country' => $o->country,
            'face_value' => $o->face_value, 'currency' => $o->currency_code, 'quantity' => $o->quantity,
            'total_ngn' => $o->total_ngn, 'provider_amount_ngn' => $o->provider_amount_ngn, 'payout_ngn' => $o->payout_ngn,
            'pricing' => $o->pricing, 'reject_reason' => $o->reject_reason, 'needs_review' => $o->needs_review,
            'attachments' => count($o->attachments ?? []), 'has_comments' => !empty($o->comments),
            'codes' => $codes, 'settled_at' => optional($o->settled_at)->toDateTimeString(),
            'last_synced_at' => optional($o->last_synced_at)->toDateTimeString(), 'created_at' => $o->created_at->toDateTimeString(),
            'attachment_urls' => collect($o->attachments ?? [])->keys()->map(fn ($i) => route('admin.giftcards.attachment', [$o->id, $i]))->all(),
        ]]);
    }

    public function syncOrder($id, GiftcardSettlement $settle)
    {
        $o = GiftcardOrder::findOrFail($id);
        $r = $settle->syncOrder($o);
        $o->refresh();
        return response()->json([
            'success' => (bool) ($r['ok'] ?? false),
            'message' => ($r['ok'] ?? false) ? 'Status: ' . $o->status : ('Could not sync: ' . ($r['error'] ?? 'unknown')),
            'status' => $o->status,
        ]);
    }

    public function attachment($id, $n)
    {
        $o = GiftcardOrder::findOrFail($id);
        $path = ($o->attachments ?? [])[(int) $n] ?? null;
        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }
        return Storage::disk('local')->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function log($adminId, string $action, $amount, $userId, string $desc, ?string $ref = null): void
    {
        AdminTransaction::create([
            'admin_id' => $adminId, 'action' => $action, 'amount' => $amount ?: 0, 'affected_user_id' => $userId,
            'description' => Str::limit($desc, 500), 'reference' => $ref ?: 'GC_' . strtoupper(Str::random(8)),
        ]);
    }
}