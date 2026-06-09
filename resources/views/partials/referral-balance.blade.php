@php
    $user            = Auth::user();
    $refBal          = $user->referral_balance ?? 0;
    $isPartner       = $user->is_partner;
    $minWithdrawal   = $user->partner_min_withdrawal ?? 0;
    $pendingW        = $isPartner
        ? \App\Models\PartnerWithdrawal::where('user_id', $user->id)->where('status', 'pending')->exists()
        : false;
@endphp

<div class="rounded-xl overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
    <div class="p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider" style="color: rgba(255,255,255,0.55);">
                    {{ $isPartner ? 'Partner Balance' : 'Referral Balance' }}
                </p>
                <p class="text-2xl font-bold text-white mt-1">
                    ₦{{ number_format($refBal, 2) }}
                </p>
                <p class="text-xs mt-1" style="color: rgba(255,255,255,0.45);">
                    Referral code: <span class="font-mono font-bold text-yellow-300">{{ $user->username }}</span>
                </p>
            </div>

            <div class="flex flex-col items-end gap-2">
                @if ($isPartner)
                    @if ($pendingW)
                        <span class="text-xs px-3 py-1.5 rounded-full font-semibold"
                              style="background:rgba(251,191,36,0.15);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);">
                            Withdrawal pending
                        </span>
                    @else
                        <button id="openWithdrawalModal"
                            class="text-sm font-bold px-4 py-2 rounded-full transition-all"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,0.35);"
                            onmouseover="this.style.transform='translateY(-1px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            Withdraw
                        </button>
                    @endif
                @else
                    <span class="text-xs px-3 py-1.5 rounded-full font-semibold"
                          style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.12);">
                        Invite &amp; earn
                    </span>
                @endif

                {{-- Copy referral link --}}
                <button onclick="copyReferralLink()" class="text-xs font-semibold px-3 py-1.5 rounded-full transition-all"
                        style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.15);"
                        id="copyRefBtn">
                    📋 Copy link
                </button>
            </div>
        </div>

        @if (!$isPartner && $refBal > 0)
        <div class="mt-3 pt-3 border-t" style="border-color: rgba(255,255,255,0.08);">
            <p class="text-xs" style="color:rgba(255,255,255,0.4);">
                Your referral earnings accumulate here. Contact support to learn about the Partner programme.
            </p>
        </div>
        @endif
    </div>
</div>

{{-- ── Withdrawal Modal (partner only) ──────────────────── --}}
@if ($isPartner)
<div id="withdrawalModal"
    class="fixed inset-0 hidden items-center justify-center z-50"
    style="background: rgba(0,0,0,0.65); backdrop-filter: blur(6px);">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">

        {{-- Header --}}
        <div class="p-5" style="background: linear-gradient(135deg, #1a1a2e, #0f3460);">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Withdraw Partner Balance</h2>
                    <p class="text-xs mt-1" style="color:rgba(255,255,255,0.55);">
                        Available: <strong class="text-yellow-300">₦{{ number_format($refBal, 2) }}</strong>
                        &nbsp;·&nbsp; Min: <strong class="text-yellow-300">₦{{ number_format($minWithdrawal, 2) }}</strong>
                    </p>
                </div>
                <button onclick="closeWithdrawalModal()" class="text-white text-2xl leading-none"
                        style="background:rgba(255,255,255,0.12);width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;">×</button>
            </div>
        </div>

        {{-- Flash message --}}
        <div id="wFlash" class="hidden mx-5 mt-4 p-3 rounded-xl text-sm font-semibold"></div>

        {{-- Form --}}
        <div class="p-5 space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Amount (₦)</label>
                <input type="number" id="wAmount" placeholder="0.00" min="{{ $minWithdrawal }}" step="0.01"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 text-lg font-bold focus:outline-none focus:ring-2 focus:ring-[#0f3460]"
                    style="background:#f9fafb;" />
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Bank Name</label>
                <input type="text" id="wBankName" placeholder="e.g. GTBank, Access Bank"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460]"
                    style="background:#f9fafb;" />
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Account Number</label>
                <input type="text" id="wAccNumber" placeholder="10-digit account number" maxlength="20"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460]"
                    style="background:#f9fafb;" />
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-1">Account Name</label>
                <input type="text" id="wAccName" placeholder="Name on the account"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460]"
                    style="background:#f9fafb;" />
            </div>

            <button id="wSubmitBtn" onclick="submitWithdrawal()"
                class="w-full py-3 rounded-xl font-bold text-white text-sm transition-all"
                style="background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(245,158,11,0.35);">
                Submit Withdrawal Request
            </button>

            <p class="text-xs text-center text-gray-400">
                Withdrawals are processed manually by our team, typically within 24 hours.
            </p>
        </div>
    </div>
</div>
@endif

<script>
var BASE_URL = '{{ url('/') }}';
var CSRF_TOK = '{{ csrf_token() }}';
var PARTNER_MIN = {{ $minWithdrawal }};
var PARTNER_BAL = {{ $refBal }};

function copyReferralLink() {
    var link = BASE_URL + '/app/signup?ref={{ $user->username }}';
    navigator.clipboard.writeText(link).then(function() {
        var btn = document.getElementById('copyRefBtn');
        btn.textContent = '✅ Copied!';
        setTimeout(function() { btn.textContent = '📋 Copy link'; }, 2000);
    });
}

@if ($isPartner)
function openWithdrawalModal() {
    var modal = document.getElementById('withdrawalModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeWithdrawalModal() {
    var modal = document.getElementById('withdrawalModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('wFlash').className = 'hidden mx-5 mt-4 p-3 rounded-xl text-sm font-semibold';
}
function flashW(msg, ok) {
    var el = document.getElementById('wFlash');
    el.textContent = msg;
    el.className = 'mx-5 mt-4 p-3 rounded-xl text-sm font-semibold ' + (ok
        ? 'bg-green-50 text-green-700 border border-green-200'
        : 'bg-red-50 text-red-700 border border-red-200');
}
function submitWithdrawal() {
    var amount    = parseFloat(document.getElementById('wAmount').value);
    var bankName  = document.getElementById('wBankName').value.trim();
    var accNum    = document.getElementById('wAccNumber').value.trim();
    var accName   = document.getElementById('wAccName').value.trim();
    var btn       = document.getElementById('wSubmitBtn');

    if (!amount || amount < PARTNER_MIN) {
        flashW('Minimum withdrawal is ₦' + PARTNER_MIN.toLocaleString('en-NG', {minimumFractionDigits:2}), false);
        return;
    }
    if (amount > PARTNER_BAL) {
        flashW('Insufficient balance.', false);
        return;
    }
    if (!bankName || !accNum || !accName) {
        flashW('Please fill in all bank details.', false);
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Submitting…';

    fetch(BASE_URL + '/app/partner/withdraw', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOK, 'Accept': 'application/json' },
        body: JSON.stringify({ amount, bank_name: bankName, account_number: accNum, account_name: accName })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.textContent = 'Submit Withdrawal Request';
        if (data.success) {
            flashW(data.message, true);
            PARTNER_BAL = parseFloat(data.new_balance.replace(/,/g, '')) || 0;
            setTimeout(function() { window.location.reload(); }, 2000);
        } else {
            flashW(data.message, false);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = 'Submit Withdrawal Request';
        flashW('An error occurred. Please try again.', false);
    });
}

var openBtn = document.getElementById('openWithdrawalModal');
if (openBtn) openBtn.addEventListener('click', openWithdrawalModal);

document.getElementById('withdrawalModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeWithdrawalModal();
});
@endif
</script>