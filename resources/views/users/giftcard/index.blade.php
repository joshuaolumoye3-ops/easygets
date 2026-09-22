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
                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $w->status === 'approved' ? 'bg-green-100 text-green-700' : ($w->status === 'declined' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ $w->status === 'approved' ? 'Paid' : ucfirst($w->status) }}</span>
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
            <button onclick="closeWd()" class="text-2xl text-gray-400 leading-none">&times;</button>
        </div>
        <p class="text-xs text-gray-500 mb-4">Available: <b>₦{{ number_format($withdrawable, 2) }}</b> · Min ₦{{ number_format($minWithdrawal) }}</p>
        <div id="wdMsg" class="hidden text-sm rounded-xl p-3 mb-3"></div>
        <div class="space-y-3">
            <input id="wdAmount" type="number" step="0.01" min="{{ $minWithdrawal }}" placeholder="Amount (₦)" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 font-bold">
            <input id="wdBank" type="text" maxlength="100" placeholder="Bank name" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
            <input id="wdAcc" type="text" inputmode="numeric" maxlength="10" placeholder="10-digit account number" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
            <input id="wdName" type="text" maxlength="150" placeholder="Account name" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
            <input id="wdPin" type="password" inputmode="numeric" maxlength="4" placeholder="Transaction PIN" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 tracking-widest">
            <button id="wdBtn" onclick="submitWd()" class="w-full py-3.5 rounded-xl font-bold text-white" style="background:#03624C;">Submit request</button>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function openWd(){const m=document.getElementById('wdModal');m.classList.remove('hidden');m.classList.add('flex');}
function closeWd(){const m=document.getElementById('wdModal');m.classList.add('hidden');m.classList.remove('flex');}
function wdMsg(t,ok){const e=document.getElementById('wdMsg');e.textContent=t;e.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700');}
async function submitWd(){
    const b=document.getElementById('wdBtn'); b.disabled=true; b.textContent='Submitting…';
    try{
        const r=await fetch(@json(route('giftcards.withdraw')),{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({amount:document.getElementById('wdAmount').value,bank_name:document.getElementById('wdBank').value,account_number:document.getElementById('wdAcc').value,account_name:document.getElementById('wdName').value,pin:document.getElementById('wdPin').value})});
        const d=await r.json();
        if(r.ok&&d.success){wdMsg(d.message,true);setTimeout(()=>location.reload(),1500);return;}
        wdMsg(d.message||(d.errors?Object.values(d.errors)[0][0]:'Something went wrong.'),false);
    }catch(e){wdMsg('Network error. Please try again.',false);}
    b.disabled=false;b.textContent='Submit request';
}
function gcToast(m){const t=document.createElement('div');t.className='fixed inset-x-4 bottom-24 mx-auto max-w-sm rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-800 shadow-lg z-[60]';t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),2200);}
</script>
@include('partials.footer')
@endsection