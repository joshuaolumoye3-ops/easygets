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
                    <div><p class="text-sm font-semibold">Requested ₦{{ number_format($w->amount, 2) }}</p><p class="text-xs text-gray-500">Fee ₦{{ number_format((float) ($w->fee_amount ?? 0), 2) }} · Sent ₦{{ number_format((float) ($w->payout_amount ?? $w->amount), 2) }}</p><p class="text-xs text-gray-400">{{ $w->bank_name }} • {{ $w->created_at->format('M d') }}</p></div>
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
            <div class="relative" id="wdBankPicker">
                <input id="wdBank" type="hidden" value="">
                <button id="wdBankToggle" type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-left shadow-sm transition hover:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-700/20" aria-haspopup="listbox" aria-expanded="false" aria-controls="wdBankOptions">
                    <span class="min-w-0">
                        <span id="wdBankSelected" class="block truncate text-sm font-semibold text-gray-800">Choose your bank</span>
                        <span id="wdBankHint" class="mt-0.5 block text-xs text-gray-400">Loading available banks…</span>
                    </span>
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform" id="wdBankChevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 011.06.03L10 11.46l3.72-3.96a.75.75 0 111.1 1.02l-4.25 4.52a.75.75 0 01-1.1 0L5.2 8.53a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div id="wdBankPanel" class="absolute inset-x-0 top-full z-30 mt-2 hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl" role="group" aria-label="Search and select a bank">
                    <label class="relative block border-b border-gray-100 p-3">
                        <svg class="pointer-events-none absolute left-6 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/></svg>
                        <input id="wdBankSearch" type="search" autocomplete="off" placeholder="Search banks…" class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-700/10" role="combobox" aria-expanded="true" aria-controls="wdBankOptions" aria-autocomplete="list">
                    </label>
                    <div id="wdBankOptions" class="max-h-56 overflow-y-auto overscroll-contain p-1.5" role="listbox" aria-label="Available banks">
                        <p id="wdBankEmpty" class="px-3 py-5 text-center text-sm text-gray-400">Loading banks…</p>
                    </div>
                </div>
            </div>
            <input id="wdAcc" type="text" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="10-digit account number" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-wide">
            <div id="wdResolved" class="hidden text-sm rounded-xl px-4 py-3"></div>
            <input id="wdAmount" type="number" step="0.01" min="{{ $minWithdrawal }}" placeholder="Amount (₦)" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 font-bold">
            <div class="rounded-xl border border-gray-100 bg-white px-4 py-3 text-sm space-y-2">
                <div class="flex justify-between gap-3 text-gray-500"><span>Withdrawal amount</span><span id="wdQuoteGross">₦0.00</span></div>
                <div class="flex justify-between gap-3 text-gray-500"><span>Service charge</span><span id="wdQuoteFee">−₦0.00</span></div>
                <div class="flex justify-between gap-3 border-t border-gray-100 pt-2 font-bold text-gray-900"><span>Sent to bank</span><span id="wdQuotePayout">₦0.00</span></div>
            </div>
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
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Withdrawal amount</span><strong id="wdReceiptAmount" class="text-slate-900"></strong></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Service charge</span><strong id="wdReceiptFee" class="text-slate-900"></strong></div>
                <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-500">Sent to bank</span><strong id="wdReceiptPayout" class="text-emerald-700"></strong></div>
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
    fees: @json($withdrawalFees),
};
let wdBanksLoaded = false, wdResolvedOk = false, wdToken = null, wdTimer = null;
let wdBanks = [], wdBankMatches = [], wdBankActiveIndex = 0;
const $wd = id => document.getElementById(id);
const newWdToken = () => (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2) + Date.now().toString(36));
function setWdBankPickerOpen(open, focusSearch=false){
    const panel=$wd('wdBankPanel'), toggle=$wd('wdBankToggle'), chevron=$wd('wdBankChevron');
    panel.classList.toggle('hidden',!open);
    toggle.setAttribute('aria-expanded',String(open));
    chevron.classList.toggle('rotate-180',open);
    if(open&&focusSearch)setTimeout(()=>$wd('wdBankSearch').focus(),0);
}
function renderWdBankOptions(){
    const query=$wd('wdBankSearch').value.trim().toLocaleLowerCase();
    wdBankMatches=wdBanks.filter(bank=>bank.name.toLocaleLowerCase().includes(query)||(bank.code||'').toLocaleLowerCase().includes(query));
    wdBankActiveIndex=wdBankMatches.length?Math.min(wdBankActiveIndex,wdBankMatches.length-1):0;
    const list=$wd('wdBankOptions');
    list.replaceChildren();
    if(!wdBankMatches.length){
        const empty=document.createElement('p');
        empty.className='px-3 py-5 text-center text-sm text-gray-400';
        empty.textContent=query?'No banks match your search.':'No banks available.';
        list.appendChild(empty);
        $wd('wdBankSearch').removeAttribute('aria-activedescendant');
        return;
    }
    wdBankMatches.forEach((bank,index)=>{
        const option=document.createElement('button');
        option.type='button';
        option.id='wd-bank-option-'+index;
        option.setAttribute('role','option');
        option.setAttribute('aria-selected',String(index===wdBankActiveIndex));
        option.className='flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-left transition '+(index===wdBankActiveIndex?'bg-emerald-50 text-emerald-900':'text-gray-700 hover:bg-gray-50');
        const bankName=document.createElement('span');
        bankName.className='min-w-0 truncate text-sm font-medium';
        bankName.textContent=bank.name;
        const bankCode=document.createElement('span');
        bankCode.className='flex-shrink-0 rounded-md bg-gray-100 px-2 py-1 font-mono text-[10px] text-gray-500';
        bankCode.textContent=bank.code;
        option.append(bankName,bankCode);
        option.addEventListener('mouseenter',()=>{
            wdBankActiveIndex=index;
            list.querySelectorAll('[role="option"]').forEach((item,itemIndex)=>{
                const active=itemIndex===index;
                item.setAttribute('aria-selected',String(active));
                item.classList.toggle('bg-emerald-50',active);
                item.classList.toggle('text-emerald-900',active);
                item.classList.toggle('text-gray-700',!active);
            });
            $wd('wdBankSearch').setAttribute('aria-activedescendant',option.id);
        });
        option.addEventListener('click',()=>selectWdBank(bank));
        list.appendChild(option);
    });
    $wd('wdBankSearch').setAttribute('aria-activedescendant','wd-bank-option-'+wdBankActiveIndex);
}
function selectWdBank(bank){
    $wd('wdBank').value=bank.code;
    $wd('wdBankSelected').textContent=bank.name;
    $wd('wdBankHint').textContent='Bank selected';
    $wd('wdBankToggle').classList.add('border-emerald-300');
    $wd('wdBankSearch').value='';
    renderWdBankOptions();
    setWdBankPickerOpen(false);
    scheduleWdResolve();
}
function handleWdBankSearchKeydown(event){
    if(event.key==='ArrowDown'||event.key==='ArrowUp'){
        event.preventDefault();
        if(!wdBankMatches.length)return;
        const direction=event.key==='ArrowDown'?1:-1;
        wdBankActiveIndex=(wdBankActiveIndex+direction+wdBankMatches.length)%wdBankMatches.length;
        renderWdBankOptions();
        document.getElementById('wd-bank-option-'+wdBankActiveIndex)?.scrollIntoView({block:'nearest'});
    }else if(event.key==='Enter'){
        event.preventDefault();
        if(wdBankMatches[wdBankActiveIndex])selectWdBank(wdBankMatches[wdBankActiveIndex]);
    }else if(event.key==='Escape'){
        event.preventDefault();
        setWdBankPickerOpen(false);
        $wd('wdBankToggle').focus();
    }
}
function openWd(){const m=$wd('wdModal');m.classList.remove('hidden');m.classList.add('flex');wdToken=wdToken||newWdToken();$wd('wdBankSearch').value='';renderWdBankOptions();loadWdBanks();}
function closeWd(){const m=$wd('wdModal');m.classList.add('hidden');m.classList.remove('flex');}
function wdMsg(t,ok){const e=document.getElementById('wdMsg');e.textContent=t;e.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700');}
function wdResolvedBox(t,kind){const e=$wd('wdResolved');if(!t){e.className='hidden';return;}e.textContent=t;e.className='text-sm rounded-xl px-4 py-3 '+(kind==='ok'?'bg-green-50 text-green-700 font-semibold':kind==='err'?'bg-red-50 text-red-700':'bg-gray-50 text-gray-500');}
function wdMoney(value){return '₦'+Number(value||0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});}
function wdFeeForAmount(amount){if(amount<=5000)return Number(WD.fees.up_to_5000||0);if(amount<=50000)return Number(WD.fees['5001_to_50000']||0);return Number(WD.fees.over_50000||0);}
function wdUpdateBtn(){const a=parseFloat($wd('wdAmount').value)||0;const fee=a>0?wdFeeForAmount(a):0;const net=Math.max(0,a-fee);$wd('wdQuoteGross').textContent=wdMoney(a);$wd('wdQuoteFee').textContent='−'+wdMoney(fee);$wd('wdQuotePayout').textContent=wdMoney(net);$wd('wdBtn').disabled=!(wdResolvedOk&&a>=WD.min&&a<=WD.max&&net>0&&/^\d{4}$/.test($wd('wdPin').value));}
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
        $wd('wdReceiptFee').textContent='−'+wdMoney(data.fee_amount);
        $wd('wdReceiptPayout').textContent=wdMoney(data.payout_amount??(data.amount-data.fee_amount));
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
        wdBanks=(Array.isArray(d.banks)?d.banks:[]).filter(bank=>bank&&bank.code&&bank.name).map(bank=>({code:String(bank.code),name:String(bank.name)})).sort((a,b)=>a.name.localeCompare(b.name));
        wdBanksLoaded=true;
        $wd('wdBankHint').textContent=wdBanks.length?`${wdBanks.length} banks available · Search by name`:'No banks available';
        renderWdBankOptions();
    }catch(e){
        $wd('wdBankHint').textContent='Bank list unavailable';
        $wd('wdBankOptions').innerHTML='<p class="px-3 py-5 text-center text-sm text-red-600">Could not load banks. Close and reopen to try again.</p>';
        wdMsg(e.message,false);
    }
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
$wd('wdBankToggle').addEventListener('click',()=>{
    const opening=$wd('wdBankPanel').classList.contains('hidden');
    setWdBankPickerOpen(opening,opening);
});
$wd('wdBankSearch').addEventListener('input',()=>{wdBankActiveIndex=0;renderWdBankOptions();});
$wd('wdBankSearch').addEventListener('keydown',handleWdBankSearchKeydown);
document.addEventListener('click',event=>{
    if(!$wd('wdBankPicker').contains(event.target))setWdBankPickerOpen(false);
});
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