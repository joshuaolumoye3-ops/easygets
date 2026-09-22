@extends('layouts.app')
@section('title', 'Buy Gift Card')

@section('content')
<div class="max-w-md mx-auto pb-28 min-h-screen bg-gray-50">
    <div class="relative flex items-center px-4 h-14 bg-white border-b border-gray-100">
        <a href="{{ route('giftcards.index') }}" class="z-10" aria-label="Back"><svg class="h-6 w-6 text-gray-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd"/></svg></a>
        <h1 class="absolute inset-0 flex justify-center items-center text-lg font-semibold">Buy Gift Card</h1>
    </div>

    <div class="p-4 space-y-3">
        <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Wallet balance</span><b class="text-gray-800">₦{{ number_format($walletBalance, 2) }}</b>
        </div>
        <input id="q" type="search" placeholder="Search brands e.g. Amazon, iTunes…" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#03624C]">
        <div id="chips" class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1"></div>
        <div id="grid" class="grid grid-cols-2 gap-3"></div>
        <div id="state" class="text-center text-sm text-gray-400 py-8"></div>
        <button id="more" class="hidden w-full py-3 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-700">Load more</button>
    </div>
</div>

{{-- Purchase sheet --}}
<div id="sheet" class="fixed inset-0 bg-black/50 hidden items-end sm:items-center justify-center z-50">
    <div class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl max-h-[92vh] overflow-y-auto">
        <div id="sheetBody" class="p-6"></div>
    </div>
</div>

{{-- Confirm PIN modal --}}
<div id="pinConfirmModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xs w-full p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Confirm PIN</h2>
        <p class="text-xs text-gray-500 mb-4">Enter your 4-digit transaction PIN to complete this purchase.</p>
        <div id="pinConfirmMsg" class="hidden text-sm rounded-xl p-2.5 mb-3 bg-red-50 text-red-700"></div>
        <input id="pinConfirmInput" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code"
               class="block w-full text-center text-3xl tracking-[0.5em] bg-gray-100 rounded-xl p-3 border focus:ring-2 focus:ring-[#03624C] focus:outline-none mb-4">
        <button id="pinConfirmBtn" class="w-full bg-brand-green bg-[#03624C] text-white font-semibold py-3 rounded-xl disabled:opacity-40" disabled>Confirm &amp; pay</button>
        <button id="pinConfirmCancel" class="w-full mt-2 text-sm text-gray-500 py-2">Cancel</button>
    </div>
</div>

<script>
const URLS = { catalog:@json(route('giftcards.buy.catalog')), quote:@json(route('giftcards.buy.quote')), purchase:@json(route('giftcards.buy.purchase')), history:@json(route('history').'?tab=giftcards') };
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const WALLET = {{ (float) $walletBalance }}, MAXQ = {{ (int) $maxQty }};
const esc = s => { const d=document.createElement('div'); d.textContent=String(s??''); return d.innerHTML; };
const ngn = n => '₦' + Number(n).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
const token = () => (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2)+Date.now().toString(36));
const flagUrl = f => f ? `https://flagcdn.com/24x18/${f}.png` : null;
function flagBadge(flag, extraClass=''){
    if(!flag) return '';
    return `<img src="${flagUrl(flag)}" alt="" class="inline-block rounded-sm ${extraClass}" style="width:18px;height:13px;object-fit:cover" onerror="this.remove()">`;
}
/** Real product image with a colored-initial fallback that can never CORS-fail or 404. */
function mediaHtml(image, badge, imgClass){
    const b = badge || {initial:'G', color:'#03624C'};
    const badgeDiv = `<div class="w-full h-full flex items-center justify-center text-white font-extrabold text-lg" style="background:${esc(b.color)}">${esc(b.initial)}</div>`;
    if(!image) return badgeDiv;
    return `<img src="${esc(image)}" alt="" loading="lazy" class="${imgClass}" style="display:block" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="w-full h-full hidden items-center justify-center text-white font-extrabold text-lg" style="background:${esc(b.color)}">${esc(b.initial)}</div>`;
}

async function api(url, opts={}) {
    const isBody = opts.body && !(opts.body instanceof FormData);
    const r = await fetch(url, {...opts, headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF, ...(isBody?{'Content-Type':'application/json'}:{})}});
    let d={}; try{ d=await r.json(); }catch(e){}
    return {ok:r.ok, status:r.status, d};
}
const errMsg = d => d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Something went wrong. Please try again.');

// ── catalog ──
let page=1, q='', cat='', loading=false, cards={};
const grid=document.getElementById('grid'), state=document.getElementById('state'), more=document.getElementById('more');

async function load(reset=false){
    if(loading) return; loading=true;
    if(reset){ page=1; grid.innerHTML=''; cards={}; more.classList.add('hidden'); }
    state.textContent='Loading…';
    const {ok,d}=await api(`${URLS.catalog}?page=${page}&q=${encodeURIComponent(q)}&category=${encodeURIComponent(cat)}`);
    loading=false;
    if(!ok){ state.innerHTML=`<span class="text-red-500">${esc(errMsg(d))}</span><br><button onclick="load(true)" class="mt-2 text-[#03624C] font-semibold">Retry</button>`; return; }
    if(reset) renderChips(d.categories);
    d.data.forEach(c=>{ cards[c.sku]=c; grid.insertAdjacentHTML('beforeend', cardHtml(c)); });
    state.textContent = d.total===0 ? 'No cards found.' : '';
    more.classList.toggle('hidden', d.page>=d.last_page);
}
function cardHtml(c){
    const range = c.min===c.max ? `${c.symbol}${c.min}` : `${c.symbol}${c.min} – ${c.symbol}${c.max}`;
    return `<button onclick="openSheet(${c.sku})" class="bg-white rounded-2xl p-3 shadow-sm border border-gray-100 text-left hover:shadow-md hover:-translate-y-0.5 transition relative">
        ${c.flag?`<span class="absolute top-2 right-2 z-10 shadow-sm rounded-sm overflow-hidden">${flagBadge(c.flag)}</span>`:''}
        <div class="aspect-[16/10] rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center mb-2">
            ${mediaHtml(c.image, c.badge, 'w-full h-full object-contain p-3')}
        </div>
        <p class="text-sm font-semibold text-gray-800 line-clamp-2 leading-snug">${esc(c.title)}</p>
        <p class="text-xs text-gray-400 mt-1">${esc(range)}${c.pre_order?' · Pre-order':''}</p></button>`;
}
function renderChips(cats){
    const el=document.getElementById('chips'); if(el.dataset.done) return; el.dataset.done=1;
    el.innerHTML=['',...cats].map(c=>`<button data-c="${esc(c)}" class="chip whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-semibold border ${c===''?'bg-[#03624C] text-white border-[#03624C]':'bg-white text-gray-600 border-gray-200'}">${c===''?'All':esc(c)}</button>`).join('');
    el.querySelectorAll('.chip').forEach(b=>b.onclick=()=>{ cat=b.dataset.c; el.querySelectorAll('.chip').forEach(x=>x.className='chip whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-semibold border bg-white text-gray-600 border-gray-200'); b.className='chip whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-semibold border bg-[#03624C] text-white border-[#03624C]'; load(true); });
}
let t; document.getElementById('q').addEventListener('input',e=>{ clearTimeout(t); t=setTimeout(()=>{ q=e.target.value.trim(); load(true); },350); });
more.onclick=()=>{ page++; load(); };

// ── purchase sheet ──
let cur=null, price=0, qty=1, quote=null, clientToken=token(), qt;
function openSheet(sku){
    cur=cards[sku]; qty=1; quote=null; clientToken=token();
    price = cur.min===cur.max ? cur.min : 0;
    const chips = [5,10,15,20,25,50,100,200,500].filter(v=>v>=cur.min&&v<=cur.max);
    document.getElementById('sheetBody').innerHTML = `
      <div class="flex justify-between items-start mb-4">
            <div class="flex gap-3 items-center min-w-0"><div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">${mediaHtml(cur.image, cur.badge, 'w-full h-full object-contain p-2')}</div>
        <div class="min-w-0"><p class="font-bold text-gray-900 leading-tight">${esc(cur.title)}</p><p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">${flagBadge(cur.flag)} ${esc(cur.regions.slice(0,3).join(', '))}</p></div></div>
        <button onclick="closeSheet()" class="text-2xl text-gray-400 leading-none">&times;</button>
      </div>
      <div id="sheetMsg" class="hidden text-sm rounded-xl p-3 mb-3"></div>
      <label class="text-xs font-bold text-gray-500 uppercase">Card value (${esc(cur.currency)})</label>
      ${cur.min===cur.max?`<p class="text-2xl font-extrabold mt-1 mb-3">${esc(cur.symbol)}${cur.min}</p>`:`
        <input id="price" type="number" min="${cur.min}" max="${cur.max}" step="1" placeholder="${cur.min} – ${cur.max}" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-lg font-bold">
        <div class="flex flex-wrap gap-2 mt-2 mb-3">${chips.map(v=>`<button type="button" onclick="setPrice(${v})" class="px-3 py-1 rounded-full border border-gray-200 text-xs font-semibold text-gray-600">${esc(cur.symbol)}${v}</button>`).join('')}</div>`}
      <div class="flex items-center justify-between mb-4"><span class="text-xs font-bold text-gray-500 uppercase">Quantity</span>
        <div class="flex items-center gap-3"><button onclick="setQty(-1)" class="w-9 h-9 rounded-full border border-gray-200 text-lg">−</button><b id="qty" class="w-6 text-center">1</b><button onclick="setQty(1)" class="w-9 h-9 rounded-full border border-gray-200 text-lg">+</button></div></div>
      <div class="bg-gray-50 rounded-2xl p-4 mb-4"><div class="flex justify-between text-sm"><span class="text-gray-500">Total</span><b id="total" class="text-lg">—</b></div>
        <div class="flex justify-between text-xs mt-1 text-gray-400"><span>Wallet balance</span><span>${ngn(WALLET)}</span></div></div>
      ${cur.description?`<details class="mb-4 text-xs text-gray-500"><summary class="font-semibold cursor-pointer">About this card</summary><div class="mt-2 leading-relaxed">${cur.description}</div></details>`:''}
      <button id="buyBtn" onclick="startPay()" disabled class="w-full py-3.5 rounded-xl font-bold text-white disabled:opacity-40" style="background:#03624C;">Pay now</button>
      <p class="text-[11px] text-gray-400 text-center mt-2">Codes are delivered instantly in most cases and saved in your History.</p>`;
    const el=document.getElementById('sheet'); el.classList.remove('hidden'); el.classList.add('flex');
    const pi=document.getElementById('price'); if(pi) pi.addEventListener('input',e=>{ price=parseInt(e.target.value)||0; requote(); });
    if(price) requote();
}
function closeSheet(){ const el=document.getElementById('sheet'); el.classList.add('hidden'); el.classList.remove('flex'); }
function setPrice(v){ price=v; const pi=document.getElementById('price'); if(pi) pi.value=v; requote(); }
function setQty(d){ qty=Math.max(1,Math.min(MAXQ,qty+d)); document.getElementById('qty').textContent=qty; requote(); }
function sheetMsg(t,ok){ const e=document.getElementById('sheetMsg'); if(!t){e.className='hidden';return;} e.textContent=t; e.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700'); }
function requote(){
    quote=null; document.getElementById('total').textContent='…'; sheetMsg(''); document.getElementById('buyBtn').disabled=true; clearTimeout(qt);
    if(!price || price<cur.min || price>cur.max){ document.getElementById('total').textContent='—'; return; }
    qt=setTimeout(async()=>{
        const {ok,d}=await api(URLS.quote,{method:'POST',body:JSON.stringify({sku:cur.sku,price,quantity:qty})});
        if(!ok){ document.getElementById('total').textContent='—'; sheetMsg(errMsg(d),false); return; }
        quote=d; document.getElementById('total').textContent=ngn(d.total);
        if(d.total>WALLET) sheetMsg('Insufficient wallet balance. Please fund your wallet first.',false);
        document.getElementById('buyBtn').disabled = d.total>WALLET;
    },450);
}

// ── PIN confirmation ──
let pendingAction=null; // fn(pin) -> Promise<{done:bool, keepOpen:bool}>
function askPin(action){
    pendingAction=action;
    const m=document.getElementById('pinConfirmModal'), inp=document.getElementById('pinConfirmInput'), btn=document.getElementById('pinConfirmBtn');
    inp.value=''; btn.disabled=true; btn.textContent='Confirm & pay'; document.getElementById('pinConfirmMsg').className='hidden';
    m.classList.remove('hidden'); m.classList.add('flex');
    setTimeout(()=>inp.focus(),100);
}
function closePinModal(){ const m=document.getElementById('pinConfirmModal'); m.classList.add('hidden'); m.classList.remove('flex'); pendingAction=null; }
document.getElementById('pinConfirmInput').addEventListener('input', e=>{
    e.target.value = e.target.value.replace(/\D/g,'').slice(0,4);
    document.getElementById('pinConfirmBtn').disabled = e.target.value.length!==4;
});
document.getElementById('pinConfirmCancel').onclick = closePinModal;
document.getElementById('pinConfirmModal').addEventListener('click', e=>{ if(e.target===e.currentTarget) closePinModal(); });
document.getElementById('pinConfirmBtn').onclick = async ()=>{
    if(!pendingAction) return;
    const btn=document.getElementById('pinConfirmBtn'), msg=document.getElementById('pinConfirmMsg');
    btn.disabled=true; btn.textContent='Processing…'; msg.className='hidden';
    const pin=document.getElementById('pinConfirmInput').value;
    const res = await pendingAction(pin);
    if(res.done){ closePinModal(); return; }
    msg.textContent=res.message||'Incorrect PIN.'; msg.className='text-sm rounded-xl p-2.5 mb-3 bg-red-50 text-red-700';
    btn.disabled=false; btn.textContent='Confirm & pay';
    document.getElementById('pinConfirmInput').value=''; document.getElementById('pinConfirmInput').focus();
};

function startPay(){
    if(!quote) return;
    askPin(async (pin)=>{
        const {ok,status,d}=await api(URLS.purchase,{method:'POST',body:JSON.stringify({sku:cur.sku,price,quantity:qty,pin,client_token:clientToken,expected_total:quote.total})});
        if(ok&&d.success){ done(d.order); return {done:true}; }
        if(status===409&&d.price_changed){
            quote={total:d.new_total}; document.getElementById('total').textContent=ngn(d.new_total); sheetMsg(d.message,false);
            return {done:true}; // close pin modal, user re-reviews price in the sheet
        }
        return {done:false, message: errMsg(d)};
    });
}
function done(o){
    const codes=(o.codes||[]).map((c,i)=>`<div class="border border-gray-200 rounded-xl p-3 mb-2 text-left">
        <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Card ${i+1}</p>
        ${c.cardNumber?`<div class="flex justify-between gap-2"><span class="font-mono text-sm break-all">${esc(c.cardNumber)}</span><button onclick="navigator.clipboard.writeText('${esc(c.cardNumber)}')" class="text-xs font-semibold text-[#03624C]">Copy</button></div>`:''}
        ${c.pinCode?`<div class="flex justify-between gap-2 mt-1"><span class="text-xs text-gray-500">PIN <span class="font-mono text-sm text-gray-800">${esc(c.pinCode)}</span></span><button onclick="navigator.clipboard.writeText('${esc(c.pinCode)}')" class="text-xs font-semibold text-[#03624C]">Copy</button></div>`:''}
        ${c.claimUrl?`<a href="${esc(c.claimUrl)}" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 underline">Open claim link</a>`:''}</div>`).join('');
    const delivered=o.status==='completed';
    document.getElementById('sheetBody').innerHTML=`<div class="text-center py-2">
        <div class="text-5xl mb-2">${delivered?'✅':'⏳'}</div>
        <h2 class="text-xl font-bold">${delivered?'Purchase complete':'Order received'}</h2>
        <p class="text-sm text-gray-500 mt-1 mb-4">${delivered?'Your card details are below and saved in History.':'We are preparing your card. It will appear in History as soon as it is delivered.'}</p>
        ${codes}
        <a href="${URLS.history}" class="block w-full mt-3 py-3 rounded-xl font-bold text-white" style="background:#03624C;">View in History</a>
        <button onclick="location.reload()" class="mt-2 text-sm text-gray-500">Buy another</button></div>`;
}
load(true);
</script>
@include('partials.footer')
@endsection