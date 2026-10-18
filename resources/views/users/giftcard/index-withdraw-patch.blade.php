{{--
================================================================================
PATCH for resources/views/users/giftcard/index.blade.php  (3 replacements)
================================================================================

(1) In the "Withdrawals" list, replace the status <span> with BLOCK 1.
(2) Replace the whole <div id="wdModal"> … </div> with BLOCK 2.
(3) In the <script>, DELETE openWd/closeWd/wdMsg/submitWd and paste BLOCK 3 (keep gcToast and CSRF).
--}}


{{-- ───────────────────────── BLOCK 1: withdrawal status badge ───────────────────────── --}}
@php
    $wMap = [
        'approved'   => ['Paid',               'bg-green-100 text-green-700'],
        'processing' => ['Processing',         'bg-blue-100 text-blue-700'],
        'pending'    => ['Pending',            'bg-yellow-100 text-yellow-700'],
        'declined'   => ['Declined · refunded', 'bg-red-100 text-red-700'],
    ];
    [$wLabel, $wClass] = $wMap[$w->status] ?? [ucfirst($w->status), 'bg-gray-100 text-gray-600'];
@endphp
<span class="text-xs font-semibold px-2 py-1 rounded-full {{ $wClass }}">{{ $wLabel }}</span>


{{-- ───────────────────────── BLOCK 2: withdraw modal ───────────────────────── --}}
<div id="wdModal" class="fixed inset-0 bg-black/50 hidden items-end sm:items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 max-h-[92vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-1">
            <h2 class="text-lg font-bold">Withdraw to bank</h2>
            <button onclick="closeWd()" class="text-2xl text-gray-400 leading-none" aria-label="Close">&times;</button>
        </div>
        <p class="text-xs text-gray-500 mb-4">Available: <b>₦{{ number_format($withdrawable, 2) }}</b> · Min ₦{{ number_format($minWithdrawal) }}</p>
        <div id="wdMsg" class="hidden text-sm rounded-xl p-3 mb-3"></div>

        <div class="space-y-3">
            <select id="wdBank" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                <option value="">Loading banks…</option>
            </select>
            <input id="wdAcc" type="text" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="10-digit account number"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-wide">
            <div id="wdResolved" class="hidden text-sm rounded-xl px-4 py-3"></div>
            <input id="wdAmount" type="number" step="0.01" min="{{ $minWithdrawal }}" max="{{ $withdrawable }}" placeholder="Amount (₦)"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 font-bold">
            <input id="wdPin" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code" placeholder="Transaction PIN"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-widest">
            <button id="wdBtn" onclick="submitWd()" disabled
                    class="w-full py-3.5 rounded-xl font-bold text-white disabled:opacity-40" style="background:#03624C;">Withdraw</button>
            <p class="text-[11px] text-gray-400 text-center">Paid to your bank via Flutterwave. If a payout is delayed it stays pending and your balance is protected.</p>
        </div>
    </div>
</div>


{{-- ───────────────────────── BLOCK 3: script (replaces old openWd/closeWd/wdMsg/submitWd) ───────────────────────── --}}
<script>
const WD = {
    banks:   @json(route('giftcards.banks')),
    resolve: @json(route('giftcards.resolve')),
    submit:  @json(route('giftcards.withdraw')),
    min:     {{ (float) $minWithdrawal }},
    max:     {{ (float) $withdrawable }},
};
let wdBanksLoaded = false, wdResolvedOk = false, wdToken = null, wdTimer = null;
const $wd = id => document.getElementById(id);
const newToken = () => (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2) + Date.now().toString(36));

function openWd(){ const m=$wd('wdModal'); m.classList.remove('hidden'); m.classList.add('flex'); wdToken = wdToken || newToken(); loadBanks(); }
function closeWd(){ const m=$wd('wdModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
function wdMsg(t, ok){ const e=$wd('wdMsg'); e.textContent=t; e.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700'); }
function wdResolvedBox(t, kind){
    const e=$wd('wdResolved');
    if(!t){ e.className='hidden'; return; }
    e.textContent=t;
    e.className='text-sm rounded-xl px-4 py-3 ' + (kind==='ok' ? 'bg-green-50 text-green-700 font-semibold' : kind==='err' ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-500');
}
function wdUpdateBtn(){
    const a = parseFloat($wd('wdAmount').value) || 0;
    $wd('wdBtn').disabled = !(wdResolvedOk && a >= WD.min && a <= WD.max && /^\d{4}$/.test($wd('wdPin').value));
}

async function loadBanks(){
    if (wdBanksLoaded) return;
    try {
        const r = await fetch(WD.banks, {headers:{'Accept':'application/json'}}); const d = await r.json();
        if (!r.ok || !d.success) throw new Error(d.message || 'Could not load banks.');
        $wd('wdBank').innerHTML = '<option value="">Select your bank</option>' +
            d.banks.map(b => `<option value="${b.code.replace(/[^A-Za-z0-9]/g,'')}">${b.name.replace(/</g,'&lt;')}</option>`).join('');
        wdBanksLoaded = true;
    } catch (e) { $wd('wdBank').innerHTML = '<option value="">Could not load banks</option>'; wdMsg(e.message, false); }
}

function scheduleResolve(){
    clearTimeout(wdTimer); wdResolvedOk = false; wdUpdateBtn();
    const bank = $wd('wdBank').value, acc = $wd('wdAcc').value.trim();
    if (!bank || !/^\d{10}$/.test(acc)) { wdResolvedBox(''); return; }
    wdResolvedBox('Verifying account…', 'info');
    wdTimer = setTimeout(async () => {
        try {
            const r = await fetch(WD.resolve, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
                body: JSON.stringify({bank_code: bank, account_number: acc})});
            const d = await r.json();
            if (r.ok && d.success) { wdResolvedOk = true; wdResolvedBox('✔ ' + d.account_name, 'ok'); }
            else wdResolvedBox(d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Could not verify this account.'), 'err');
        } catch (e) { wdResolvedBox('Network error. Please try again.', 'err'); }
        wdUpdateBtn();
    }, 350);
}
$wd('wdBank').addEventListener('change', scheduleResolve);
$wd('wdAcc').addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g,'').slice(0,10); scheduleResolve(); });
$wd('wdAmount').addEventListener('input', wdUpdateBtn);
$wd('wdPin').addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g,'').slice(0,4); wdUpdateBtn(); });

async function submitWd(){
    const b = $wd('wdBtn'); b.disabled = true; b.textContent = 'Submitting…';
    try {
        const r = await fetch(WD.submit, {method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify({
                amount: $wd('wdAmount').value, bank_code: $wd('wdBank').value, account_number: $wd('wdAcc').value.trim(),
                pin: $wd('wdPin').value, client_token: wdToken
            })});
        const d = await r.json();
        if (r.ok && d.success) { wdMsg(d.message, true); wdToken = null; setTimeout(() => location.reload(), 1800); return; }
        wdMsg(d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Something went wrong.'), false);
    } catch (e) { wdMsg('Network error. Please try again.', false); }
    b.textContent = 'Withdraw'; $wd('wdPin').value = ''; wdUpdateBtn();
}
</script>