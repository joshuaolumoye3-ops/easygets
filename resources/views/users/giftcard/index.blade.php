@extends('layouts.app')
@section('title', 'Gift Cards')

@section('content')
<div class="max-w-md mx-auto pb-24 min-h-screen bg-gray-50">
    <div class="relative flex items-center px-4 h-14 bg-white border-b border-gray-100">
        <a href="{{ route('dashboard') }}" class="z-10" aria-label="Back">
            <svg class="h-6 w-6 text-gray-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
        </a>
        <h1 class="absolute inset-0 flex justify-center items-center text-lg font-semibold">Gift Cards</h1>
    </div>

    <div class="p-4 space-y-5">
        {{-- Withdrawable balance --}}
        <div class="rounded-2xl p-5 shadow-lg text-white" style="background:linear-gradient(135deg,#03624C 0%,#047857 60%,#065f46 100%);">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color:rgba(255,255,255,.6)">Withdrawable balance</p>
            <p class="text-3xl font-extrabold mt-1 tracking-tight">₦{{ number_format($withdrawable, 2) }}</p>
            <p class="text-xs mt-1" style="color:rgba(255,255,255,.55)">Earnings from cards you sold. Withdraw to your bank.</p>
            <button onclick="openWd()" @disabled($withdrawable < $minWithdrawal)
                class="mt-4 bg-white font-bold py-2.5 px-5 rounded-full text-sm shadow-md active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed" style="color:#03624C;">
                Withdraw
            </button>
            @if($withdrawable < $minWithdrawal)
                <p class="text-[11px] mt-2" style="color:rgba(255,255,255,.55)">Minimum withdrawal: ₦{{ number_format($minWithdrawal) }}</p>
            @endif
        </div>

        {{-- Buy / Sell --}}
        <div class="grid grid-cols-2 gap-3">
            @foreach([['buy',$buyActive,route('giftcards.buy'),'🛒','Buy a card','Instant delivery from your wallet','emerald'],['sell',$sellActive,route('giftcards.sell'),'💰','Sell a card','Get paid in Naira','amber']] as [$k,$on,$url,$ico,$title,$sub,$col])
                <a href="{{ $on ? $url : '#' }}" @if(!$on) onclick="event.preventDefault();gcToast('{{ $title }} is temporarily unavailable.')" @endif
                   class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 {{ $on ? 'hover:shadow-md hover:-translate-y-0.5' : 'opacity-60' }} transition block">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-2xl bg-{{ $col }}-100">{{ $ico }}</div>
                    <p class="font-bold text-gray-800 mt-3">{{ $title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $on ? $sub : 'Currently unavailable' }}</p>
                </a>
            @endforeach
        </div>

        {{-- Recent --}}
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-gray-800">Recent activity</h2>
                <a href="{{ route('history') }}?tab=giftcards" class="text-sm font-semibold text-[#03624C]">View all</a>
            </div>
            @forelse($recent as $o)
                <a href="{{ route('history') }}?tab=giftcards" class="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xl">{{ $o->type === 'buy' ? '🛒' : '💰' }}</span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $o->title }}</p>
                            <p class="text-xs text-gray-400">{{ $o->created_at->format('M d, h:i A') }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full flex-shrink-0 ml-2
                        {{ $o->status === 'completed' ? 'bg-green-100 text-green-700' : (in_array($o->status,['failed','rejected','refunded']) ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ $o->statusLabel() }}
                    </span>
                </a>
            @empty
                <p class="text-sm text-gray-400 text-center py-6">No gift card activity yet.</p>
            @endforelse
        </div>

        @if($withdrawals->count())
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <h2 class="font-bold text-gray-800 mb-3">Withdrawals</h2>
            @foreach($withdrawals as $w)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div><p class="text-sm font-semibold">₦{{ number_format($w->amount, 2) }}</p><p class="text-xs text-gray-400">{{ $w->bank_name }} • {{ $w->created_at->format('M d') }}</p></div>
                    @php
                        $wMap = [
                            'approved' => ['Paid', 'bg-green-100 text-green-700'],
                            'processing' => ['Processing', 'bg-blue-100 text-blue-700'],
                            'pending' => ['Pending', 'bg-yellow-100 text-yellow-700'],
                            'declined' => ['Declined · refunded', 'bg-red-100 text-red-700'],
                        ];
                        [$wLabel, $wClass] = $wMap[$w->status] ?? [ucfirst($w->status), 'bg-gray-100 text-gray-600'];
                    @endphp
                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $wClass }}">{{ $wLabel }}</span>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Withdraw modal --}}
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
            <input id="wdAcc" type="text" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="10-digit account number" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-wide">
            <div id="wdResolved" class="hidden text-sm rounded-xl px-4 py-3"></div>
            <input id="wdAmount" type="number" step="0.01" min="{{ $minWithdrawal }}" placeholder="Amount (₦)" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 font-bold">
            <input id="wdPin" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code" placeholder="Transaction PIN" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-widest">
            <button id="wdBtn" onclick="submitWd()" disabled class="w-full py-3.5 rounded-xl font-bold text-white disabled:opacity-40" style="background:#03624C;">Withdraw</button>
            <p class="text-[11px] text-gray-400 text-center">Paid to your bank via Flutterwave. If a payout is delayed, it stays pending while your balance is reserved.</p>
        </div>
    </div>
</div>

<div id="wdResult" class="fixed inset-0 z-[70] hidden overflow-y-auto bg-slate-50 px-4 py-8 sm:py-12" aria-live="polite" aria-modal="true" role="dialog">
    <div class="mx-auto flex min-h-full w-full max-w-md items-center">
        <div class="w-full rounded-2xl bg-white p-6 text-center shadow-xl ring-1 ring-black/5 sm:p-8">
            <div id="wdResultIcon" class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                <svg id="wdResultSpinner" class="hidden h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <svg id="wdResultCheck" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </div>
            <p id="wdResultEyebrow" class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Transfer complete</p>
            <h2 id="wdResultTitle" class="mt-2 text-2xl font-bold text-slate-900">Transfer successful</h2>
            <p id="wdResultCopy" class="mt-2 text-sm leading-6 text-slate-500"></p>
            <div id="wdReceipt" class="mt-6 hidden divide-y divide-slate-100 rounded-xl bg-slate-50 px-4 text-left">
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Amount sent</span><strong id="wdReceiptAmount" class="text-slate-900"></strong></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Bank account</span><strong id="wdReceiptBank" class="text-right text-slate-900"></strong></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Reference</span><strong id="wdReceiptReference" class="break-all text-right text-slate-900"></strong></div>
                <div id="wdReceiptDateRow" class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Completed</span><strong id="wdReceiptDate" class="text-right text-slate-900"></strong></div>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <a href="{{ route('giftcards.index') }}" class="rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Gift cards</a>
                <a href="{{ route('history', ['tab' => 'giftcards']) }}" class="rounded-xl px-3 py-3 text-sm font-semibold text-white hover:bg-emerald-800" style="background:#03624C;">View activity</a>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const WD = {
    banks: @json(route('giftcards.banks')),
    resolve: @json(route('giftcards.resolve')),
    submit: @json(route('giftcards.withdraw')),
    status: @json(route('giftcards.withdrawals.status', ['id' => '__ID__'])),
    min: {{ (float) $minWithdrawal }},
    max: {{ (float) $withdrawable }},
};
let wdBanksLoaded = false, wdResolvedOk = false, wdToken = null, wdTimer = null;
const $wd = id => document.getElementById(id);
const newWdToken = () => (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2) + Date.now().toString(36));
function openWd(){const m=$wd('wdModal');m.classList.remove('hidden');m.classList.add('flex');wdToken=wdToken||newWdToken();loadWdBanks();}
function closeWd(){const m=$wd('wdModal');m.classList.add('hidden');m.classList.remove('flex');}
function wdMsg(t,ok){const e=document.getElementById('wdMsg');e.textContent=t;e.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700');}
function wdResolvedBox(t,kind){const e=$wd('wdResolved');if(!t){e.className='hidden';return;}e.textContent=t;e.className='text-sm rounded-xl px-4 py-3 '+(kind==='ok'?'bg-green-50 text-green-700 font-semibold':kind==='err'?'bg-red-50 text-red-700':'bg-gray-50 text-gray-500');}
function wdUpdateBtn(){const a=parseFloat($wd('wdAmount').value)||0;$wd('wdBtn').disabled=!(wdResolvedOk&&a>=WD.min&&a<=WD.max&&/^\d{4}$/.test($wd('wdPin').value));}
function wdShowResult(kind, data={}){
    const result=$wd('wdResult'), icon=$wd('wdResultIcon'), spinner=$wd('wdResultSpinner'), check=$wd('wdResultCheck');
    const isSuccess=kind==='approved', isWaiting=kind==='waiting', isDeclined=kind==='declined';
    $wd('wdResultEyebrow').textContent=isSuccess?'Transfer complete':isWaiting?'Transfer submitted':isDeclined?'Transfer not completed':'Confirming transfer';
    $wd('wdResultTitle').textContent=isSuccess?'Transfer successful':isWaiting?'Transfer is processing':isDeclined?'Transfer unsuccessful':'Confirming your transfer';
    $wd('wdResultCopy').textContent=isSuccess?'Your withdrawal has reached your bank account. Here is your transfer receipt.':isWaiting?'Flutterwave is still processing this transfer. Your funds remain tracked securely; check activity for updates.':isDeclined?'The bank did not complete this transfer. The withdrawal amount has been returned to your withdrawable balance.':'We are waiting for Flutterwave to confirm the transfer. This usually takes a moment.';
    icon.className='mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full '+(isSuccess?'bg-emerald-50 text-emerald-700':isDeclined?'bg-red-50 text-red-700':'bg-sky-50 text-sky-700');
    spinner.classList.toggle('hidden',!isWaiting&&kind!=='checking');
    check.classList.toggle('hidden',isWaiting||kind==='checking');
    $wd('wdReceipt').classList.toggle('hidden',!isSuccess);
    if(isSuccess){
        $wd('wdReceiptAmount').textContent='₦'+Number(data.amount).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
        $wd('wdReceiptBank').textContent=`${data.bank_name} •••• ${data.account_tail}`;
        $wd('wdReceiptReference').textContent=data.reference;
        $wd('wdReceiptDate').textContent=data.processed_at||'Confirmed by Flutterwave';
    }
    result.classList.remove('hidden');
}
const wdWait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
async function wdPollStatus(id){
    const url=WD.status.replace('__ID__',encodeURIComponent(id));
    for(let attempt=0;attempt<30;attempt++){
        try{
            const response=await fetch(url,{headers:{'Accept':'application/json','Cache-Control':'no-cache'}}),data=await response.json();
            if(response.ok&&data.success&&['approved','declined'].includes(data.status)){wdShowResult(data.status,data);return;}
        }catch(e){}
        await wdWait(2000);
    }
    wdShowResult('waiting');
}
async function loadWdBanks(){
    if(wdBanksLoaded)return;
    try{
        const r=await fetch(WD.banks,{headers:{'Accept':'application/json'}}),d=await r.json();
        if(!r.ok||!d.success)throw new Error(d.message||'Could not load banks.');
        $wd('wdBank').innerHTML='<option value="">Select your bank</option>'+d.banks.map(b=>`<option value="${String(b.code).replace(/[^A-Za-z0-9]/g,'')}">${String(b.name).replace(/</g,'&lt;')}</option>`).join('');
        wdBanksLoaded=true;
    }catch(e){$wd('wdBank').innerHTML='<option value="">Could not load banks</option>';wdMsg(e.message,false);}
}
function scheduleWdResolve(){
    clearTimeout(wdTimer);wdResolvedOk=false;wdUpdateBtn();
    const bank=$wd('wdBank').value,acc=$wd('wdAcc').value.trim();
    if(!bank||!/^\d{10}$/.test(acc)){wdResolvedBox('');return;}
    wdResolvedBox('Verifying account…','info');
    wdTimer=setTimeout(async()=>{
        try{
            const r=await fetch(WD.resolve,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({bank_code:bank,account_number:acc})}),d=await r.json();
            if(r.ok&&d.success){wdResolvedOk=true;wdResolvedBox('✓ '+d.account_name,'ok');}
            else wdResolvedBox(d.message||(d.errors?Object.values(d.errors)[0][0]:'Could not verify this account.'),'err');
        }catch(e){wdResolvedBox('Network error. Please try again.','err');}
        wdUpdateBtn();
    },350);
}
$wd('wdBank').addEventListener('change',scheduleWdResolve);
$wd('wdAcc').addEventListener('input',e=>{e.target.value=e.target.value.replace(/\D/g,'').slice(0,10);scheduleWdResolve();});
$wd('wdAmount').addEventListener('input',wdUpdateBtn);
$wd('wdPin').addEventListener('input',e=>{e.target.value=e.target.value.replace(/\D/g,'').slice(0,4);wdUpdateBtn();});
async function submitWd(){
    const b=$wd('wdBtn');b.disabled=true;b.textContent='Submitting…';
    try{
        const r=await fetch(WD.submit,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({amount:$wd('wdAmount').value,bank_code:$wd('wdBank').value,account_number:$wd('wdAcc').value.trim(),pin:$wd('wdPin').value,client_token:wdToken})});
        const d=await r.json();
        if(r.ok&&d.success){wdToken=null;closeWd();wdShowResult('checking');wdPollStatus(d.withdrawal_id);return;}
        wdMsg(d.message||(d.errors?Object.values(d.errors)[0][0]:'Something went wrong.'),false);
    }catch(e){wdMsg('Network error. Please try again.',false);}
    b.textContent='Withdraw';$wd('wdPin').value='';wdUpdateBtn();
}
function gcToast(m){const t=document.createElement('div');t.className='fixed inset-x-4 bottom-24 mx-auto max-w-sm rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-800 shadow-lg z-[60]';t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),2200);}
</script>
@include('partials.footer')
@endsection