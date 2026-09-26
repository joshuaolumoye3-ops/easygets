@extends('layouts.app')
@section('title', 'Sell Gift Card')

@section('content')
<div class="max-w-md mx-auto pb-28 min-h-screen bg-gray-50">
    <div class="relative flex items-center px-4 h-14 bg-white border-b border-gray-100">
        <button id="backBtn" class="z-10" aria-label="Back"><svg class="h-6 w-6 text-gray-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd"/></svg></button>
        <h1 id="title" class="absolute inset-0 flex justify-center items-center text-lg font-semibold">Sell Gift Card</h1>
    </div>
    <div class="px-4 pt-3"><div class="h-1.5 bg-gray-200 rounded-full overflow-hidden"><div id="bar" class="h-full bg-[#03624C] transition-all" style="width:25%"></div></div></div>
    <div id="view" class="p-4"></div>
</div>

{{-- Confirm PIN modal --}}
<div id="pinConfirmModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xs w-full p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-1">Confirm PIN</h2>
        <p class="text-xs text-gray-500 mb-4">Enter your 4-digit transaction PIN to submit this card.</p>
        <div id="pinConfirmMsg" class="hidden text-sm rounded-xl p-2.5 mb-3 bg-red-50 text-red-700"></div>
        <input id="pinConfirmInput" type="password" inputmode="numeric" maxlength="4" autocomplete="one-time-code"
               class="block w-full text-center text-3xl tracking-[0.5em] bg-gray-100 rounded-xl p-3 border focus:ring-2 focus:ring-[#03624C] focus:outline-none mb-4">
        <button id="pinConfirmBtn" class="w-full text-white font-semibold py-3 rounded-xl disabled:opacity-40" style="background:#03624C;" disabled>Confirm &amp; submit</button>
        <button id="pinConfirmCancel" class="w-full mt-2 text-sm text-gray-500 py-2">Cancel</button>
    </div>
</div>

<script>
const URLS={catalog:@json(route('giftcards.sell.catalog')),submit:@json(route('giftcards.sell.submit')),history:@json(route('history').'?tab=giftcards'),back:@json(route('giftcards.index'))};
const CSRF=document.querySelector('meta[name="csrf-token"]').content;
const esc=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;};
const ngn=n=>'₦'+Number(n).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2});
const token=()=>(crypto.randomUUID?crypto.randomUUID():Math.random().toString(36).slice(2)+Date.now().toString(36));
const flagUrl=f=>f?`https://flagcdn.com/48x36/${f}.png`:null;
const MAX_IMG=20, MAX_BYTES=5*1024*1024;

/** Chained fallback: brand favicon -> colored-initial badge. Never shows broken. Prestmit's sell catalog has no product images. */
window.imgFallback = function(el){
    let list=[]; try{ list=JSON.parse(el.dataset.fallbacks||'[]'); }catch(e){}
    if(list.length){ const next=list.shift(); el.dataset.fallbacks=JSON.stringify(list); el.src=next; }
    else { el.style.display='none'; if(el.nextElementSibling) el.nextElementSibling.style.display='flex'; }
};
function badgeHtml(icons, badge, size='text-lg'){
    const b = badge || {initial:'G', color:'#03624C'};
    const badgeDiv = `<div class="w-full h-full hidden items-center justify-center text-white font-extrabold ${size}" style="background:${esc(b.color)}">${esc(b.initial)}</div>`;
    const list = (icons||[]).slice();
    if(!list.length) return `<div class="w-full h-full flex items-center justify-center text-white font-extrabold ${size}" style="background:${esc(b.color)}">${esc(b.initial)}</div>`;
    const first = list.shift();
    return `<img src="${esc(first)}" alt="" loading="lazy" class="w-full h-full object-contain p-1.5" data-fallbacks='${esc(JSON.stringify(list))}' onerror="imgFallback(this)">${badgeDiv}`;
}

/** Display name / flag / currency symbol for a Prestmit country code. */
const COUNTRY_META = {
    USA:    {name:'United States',      flag:'us', symbol:'$'},
    UK:     {name:'United Kingdom',     flag:'gb', symbol:'£'},
    EURO:   {name:'Euro',               flag:'eu', symbol:'€'},
    EU:     {name:'Euro',               flag:'eu', symbol:'€'},
    CANADA: {name:'Canada',             flag:'ca', symbol:'CAD'},
    CA:     {name:'Canada',             flag:'ca', symbol:'CAD'},
    CAN:    {name:'Canada',             flag:'ca', symbol:'CAD'},
};
function countryMeta(code){
    const c = (code||'').toUpperCase().trim();
    if(COUNTRY_META[c]) return COUNTRY_META[c];
    if(!c || c==='OTHERS') return {name:'Other Countries', flag:null, symbol:''};
    return {name:code, flag:null, symbol:''};
}
function formLabel(f){
    f=(f||'').toLowerCase();
    if(f==='physical') return 'Physical';
    if(f==='ecode') return 'Ecode';
    return f ? f.charAt(0).toUpperCase()+f.slice(1) : '—';
}

// ── Wizard state ──
let DATA=null;
let S = { step:'brand', brand:null, formChoice:null, country:null, card:null };
let hist = [];
let files=[], clientToken=token();
const view=document.getElementById('view');

document.getElementById('backBtn').onclick=()=>{
    if(hist.length===0){ location.href=URLS.back; return; }
    const prev=hist.pop();
    Object.assign(S, prev);
    render();
};

async function init(){
    view.innerHTML='<p class="text-center text-sm text-gray-400 py-16">Loading live rates…</p>';
    try{
        const r=await fetch(URLS.catalog,{headers:{'Accept':'application/json'}}); const d=await r.json();
        if(!r.ok||!d.success) throw new Error(d.message||'Rates unavailable');
        DATA=d; render();
    }catch(e){ view.innerHTML=`<div class="text-center py-16"><p class="text-red-500 text-sm">${esc(e.message)}</p><button onclick="init()" class="mt-3 text-[#03624C] font-semibold text-sm">Retry</button></div>`; }
}

const TITLES   = { brand:'Choose brand', form:'Gift card form', country:'Gift card country', pick:'Choose card type', details:'Card details' };
const PROGRESS = { brand:25, form:50, country:75, pick:100, details:100 };

function render(){
    document.getElementById('bar').style.width = PROGRESS[S.step] + '%';
    document.getElementById('title').textContent = TITLES[S.step];
    if(S.step==='brand')   return stepBrand();
    if(S.step==='form')    return stepForm();
    if(S.step==='country') return stepCountry();
    if(S.step==='pick')    return stepPick();
    return stepDetails();
}

function cardsForBrand(){ return DATA.cards.filter(c=>c.category===S.brand); }

// ── Step 1: Brand ──
function stepBrand(){
    view.innerHTML=`<input id="bq" type="search" placeholder="Search brands…" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-[#03624C]"><div id="bl" class="grid grid-cols-2 gap-3"></div>`;
    const paint=(f='')=>{
        document.getElementById('bl').innerHTML=DATA.categories.filter(c=>c.toLowerCase().includes(f)).map(c=>{
            const icons = (DATA.categoryIcons && DATA.categoryIcons[c]) || [];
            const badge = DATA.categoryBadges && DATA.categoryBadges[c];
            return `<button data-b="${esc(c)}" class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm hover:shadow-md text-left transition">
                <div class="w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center mb-2">${badgeHtml(icons, badge, 'text-base')}</div>
                <p class="font-semibold text-sm text-gray-800">${esc(c)}</p></button>`;
        }).join('')||'<p class="col-span-2 text-center text-sm text-gray-400 py-8">No brands found.</p>';
        document.querySelectorAll('#bl button').forEach(b=>b.onclick=()=>{
            hist.push({...S});
            S.brand=b.dataset.b; S.formChoice=null; S.country=null; S.card=null; S.step='form';
            render();
        });
    };
    paint(); document.getElementById('bq').oninput=e=>paint(e.target.value.toLowerCase());
}

// ── Step 2: Gift Card Form ──
function stepForm(){
    const cs = cardsForBrand();
    const hasPhysical = cs.some(c=>(c.form||'').toLowerCase()==='physical');
    const hasEcode    = cs.some(c=>(c.form||'').toLowerCase()==='ecode');
    const options = [
        { value:'physical', title:'Physical Card', desc:'The gift card is bought from a physical store and you have the card image.', enabled:hasPhysical },
        { value:'ecode',    title:'Ecode',          desc:"The gift card is bought online and you have the code. Or the code is on a paper, or it's scanned.", enabled:hasEcode },
        { value:'all',      title:'All Forms',      desc:'Select this if you want to see all gift card forms, both physical and ecode.', enabled:true },
    ];
    view.innerHTML=`
      <p class="text-xs font-bold text-gray-500 uppercase mb-1">${esc(S.brand)}</p>
      <p class="text-sm text-gray-500 mb-4">Kindly select gift card form below. If you don't know what to choose, just select all forms.</p>
      <div class="space-y-3">${options.map(o=>`
        <button data-v="${o.value}" ${o.enabled?'':'disabled'} class="w-full text-left bg-white rounded-2xl p-4 border transition ${o.enabled?'border-gray-100 shadow-sm hover:shadow-md hover:border-[#03624C]/40':'border-gray-100 opacity-40 cursor-not-allowed'}">
          <p class="font-bold text-gray-900 text-sm">${esc(o.title)}</p>
          <p class="text-xs text-gray-500 mt-1 leading-relaxed">${esc(o.desc)}</p>
          ${o.enabled?'':'<p class="text-[11px] text-red-500 font-semibold mt-2">Not available for this brand</p>'}
        </button>`).join('')}</div>`;
    view.querySelectorAll('button[data-v]:not([disabled])').forEach(b=>b.onclick=()=>{
        hist.push({...S});
        S.formChoice=b.dataset.v; S.country=null; S.card=null; S.step='country';
        render();
    });
}

// ── Step 3: Gift Card Country ──
function stepCountry(){
    const cs = cardsForBrand().filter(c => S.formChoice==='all' ? true : (c.form||'').toLowerCase()===S.formChoice);
    const countries = [...new Set(cs.map(c => (c.country||'').toUpperCase()))];
    view.innerHTML=`
      <p class="text-xs font-bold text-gray-500 uppercase mb-1">${esc(S.brand)} · ${esc(formLabel(S.formChoice))}</p>
      <p class="text-sm text-gray-500 mb-4">Choose the country this gift card was issued in.</p>
      <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50 overflow-hidden">
        ${countries.map(code=>{
            const m = countryMeta(code);
            const icon = m.flag
                ? `<img src="${flagUrl(m.flag)}" alt="" class="w-8 h-6 object-cover rounded shadow-sm" onerror="this.replaceWith(document.createTextNode('🌍'))">`
                : `<span class="text-2xl leading-none">🌍</span>`;
            return `<button data-c="${esc(code)}" class="w-full flex items-center justify-between gap-3 px-4 py-3.5 hover:bg-gray-50 transition text-left">
                <span class="flex items-center gap-3"><span class="w-8 flex items-center justify-center">${icon}</span><span class="font-semibold text-sm text-gray-800">${esc(m.name)}</span></span>
                ${m.symbol?`<span class="text-xs text-gray-400 font-semibold">(${esc(m.symbol)})</span>`:''}
            </button>`;
        }).join('') || '<p class="text-center text-sm text-gray-400 py-8">No countries found.</p>'}
      </div>`;
    view.querySelectorAll('button[data-c]').forEach(b=>b.onclick=()=>{
        hist.push({...S});
        S.country=b.dataset.c; S.card=null;
        afterCountrySelected();
    });
}

function afterCountrySelected(){
    const cs = cardsForBrand().filter(c =>
        (S.formChoice==='all' ? true : (c.form||'').toLowerCase()===S.formChoice) &&
        (c.country||'').toUpperCase() === S.country
    );
    if(cs.length<=1){ S.card = cs[0] || null; enterDetails(); }
    else { S.step='pick'; render(); }
}

// ── Optional step: exact card type (only shown when more than one variant matches) ──
function stepPick(){
    const cs = cardsForBrand().filter(c =>
        (S.formChoice==='all' ? true : (c.form||'').toLowerCase()===S.formChoice) &&
        (c.country||'').toUpperCase() === S.country
    );
    const m = countryMeta(S.country);
    view.innerHTML=`
      <p class="text-xs font-bold text-gray-500 uppercase mb-1">${esc(S.brand)} · ${esc(m.name)}</p>
      <p class="text-sm text-gray-500 mb-4">Select the exact card type.</p>
      <div class="space-y-2">${cs.map(c=>`
        <button data-id="${c.id}" class="w-full bg-white rounded-2xl p-4 border border-gray-100 shadow-sm hover:shadow-md text-left transition flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0">${badgeHtml(c.icons, c.badge)}</div>
          <div class="min-w-0 flex-1"><p class="font-semibold text-sm text-gray-800">${esc(c.name)}</p><p class="text-xs text-gray-400 mt-0.5">${S.formChoice==='all'?esc(formLabel(c.form))+' · ':''}${c.minimum?'min '+c.minimum:''}</p></div>
          <div class="text-right flex-shrink-0"><p class="text-sm font-extrabold text-[#03624C]">₦${Number(c.rate).toLocaleString('en-NG',{maximumFractionDigits:2})}</p><p class="text-[10px] text-gray-400">per $1</p></div>
        </button>`).join('') || '<p class="text-center text-sm text-gray-400 py-8">No matching cards found.</p>'}</div>`;
    view.querySelectorAll('button[data-id]').forEach(b=>b.onclick=()=>{
        hist.push({...S});
        S.card = cs.find(c=>String(c.id)===b.dataset.id) || null;
        enterDetails();
    });
}

function enterDetails(){
    files=[]; clientToken=token();
    S.step='details';
    render();
}

// ── Final step: card details & submit ──
function stepDetails(){
    const card = S.card;
    if(!card){
        view.innerHTML='<div class="text-center py-16"><p class="text-sm text-red-500 mb-3">Something went wrong selecting this card.</p><button onclick="history.back?history.back():location.reload()" class="text-[#03624C] font-semibold text-sm">Go back</button></div>';
        return;
    }
    const form=(card.form||'').toLowerCase(), needImg=form!=='ecode', needCode=form!=='physical';
    view.innerHTML=`
      <div class="bg-white rounded-2xl p-4 border border-gray-100 mb-3 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0">${badgeHtml(card.icons, card.badge)}</div>
        <div><p class="font-bold text-gray-800 text-sm">${esc(card.name)}</p><p class="text-xs text-gray-400">${esc(card.country)}${card.form?' · '+esc(card.form):''} · ₦${Number(card.rate).toLocaleString('en-NG')} per $1</p></div>
      </div>
      <div id="msg" class="hidden text-sm rounded-xl p-3 mb-3"></div>
      <label class="text-xs font-bold text-gray-500 uppercase">Card value (USD)</label>
      <input id="amt" type="number" min="${card.minimum||1}" step="0.01" placeholder="${card.minimum?'Min '+card.minimum:'e.g. 100'}" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-xl bg-white text-lg font-bold">
      <div class="bg-[#03624C]/10 rounded-2xl p-4 my-3 text-center"><p class="text-xs text-gray-500">You will receive</p><p id="pay" class="text-3xl font-extrabold text-[#03624C]">₦0.00</p><p class="text-[11px] text-gray-400 mt-1">Paid to your withdrawable balance after approval</p></div>
      ${needImg?`<label class="text-xs font-bold text-gray-500 uppercase">Card photos ${form==='physical'?'(required)':'(optional)'}</label>
        <label class="block mt-1 border-2 border-dashed border-gray-300 rounded-2xl p-5 text-center bg-white cursor-pointer"><input id="file" type="file" accept="image/png,image/jpeg" multiple class="hidden"><span class="text-sm text-gray-500">Tap to add JPG / PNG (max 5MB each, up to ${MAX_IMG})</span></label>
        <div id="thumbs" class="grid grid-cols-4 gap-2 mt-2"></div>`:''}
      ${needCode?`<label class="text-xs font-bold text-gray-500 uppercase block mt-3">${form==='ecode'?'Card code':'Card code (optional)'}</label>
        <textarea id="code" rows="2" maxlength="2000" placeholder="Enter the code exactly as shown" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-xl bg-white text-sm font-mono"></textarea>`:''}
      ${card.terms?`<details class="mt-3 text-xs text-gray-500 bg-white rounded-xl p-3 border border-gray-100"><summary class="font-semibold cursor-pointer">Rate terms</summary><div class="mt-2 leading-relaxed">${card.terms}</div></details>`:''}
      <label class="flex items-start gap-2 mt-4 text-xs text-gray-500"><input id="ok" type="checkbox" class="mt-0.5"> <span>I own this card, it has not been used or sold elsewhere, and the details I provided are accurate.</span></label>
      <button id="sub" class="w-full mt-4 py-3.5 rounded-xl font-bold text-white disabled:opacity-40" style="background:#03624C;" disabled>Submit for approval</button>`;
    const amt=document.getElementById('amt'), pay=document.getElementById('pay'), sub=document.getElementById('sub');
    const payout=()=>Math.floor((parseFloat(amt.value)||0)*card.rate*100)/100;
    const check=()=>{ const a=parseFloat(amt.value)||0; pay.textContent=ngn(payout());
        const hasImg=files.length>0, hasCode=(document.getElementById('code')?.value.trim()||'')!=='';
        const ready=a>=(card.minimum||1) && document.getElementById('ok').checked && (form==='physical'?hasImg:form==='ecode'?hasCode:(hasImg||hasCode));
        sub.disabled=!ready; };
    amt.oninput=check; document.getElementById('ok').onchange=check; document.getElementById('code')?.addEventListener('input',check);
    const fi=document.getElementById('file');
    if(fi) fi.onchange=e=>{ for(const f of e.target.files){ if(!['image/png','image/jpeg'].includes(f.type)) return note('Only JPG or PNG images are allowed.'); if(f.size>MAX_BYTES) return note('Each image must be 5MB or less.'); if(files.length>=MAX_IMG) return note('Maximum '+MAX_IMG+' images.'); files.push(f); } e.target.value=''; thumbs(); check(); };
    function thumbs(){ document.getElementById('thumbs').innerHTML=files.map((f,i)=>`<div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100"><img src="${URL.createObjectURL(f)}" class="w-full h-full object-cover"><button data-i="${i}" class="rm absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white text-xs leading-none">×</button></div>`).join(''); document.querySelectorAll('.rm').forEach(b=>b.onclick=()=>{ files.splice(b.dataset.i,1); thumbs(); check(); }); }
    function note(t,ok){ const m=document.getElementById('msg'); m.textContent=t; m.className='text-sm rounded-xl p-3 mb-3 '+(ok?'bg-green-50 text-green-700':'bg-red-50 text-red-700'); window.scrollTo({top:0,behavior:'smooth'}); }
    sub.onclick=()=>askPin(async (pin)=>{
        const fd=new FormData(); fd.append('giftcard_id',card.id); fd.append('amount',amt.value); fd.append('client_token',clientToken); fd.append('expected_payout',payout()); fd.append('pin',pin);
        const code=document.getElementById('code')?.value.trim(); if(code) fd.append('comments',code);
        files.forEach(f=>fd.append('images[]',f));
        try{
            const r=await fetch(URLS.submit,{method:'POST',body:fd,headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}}); const d=await r.json();
            if(r.ok&&d.success){ success(d.order); return {done:true}; }
            if(r.status===409&&d.price_changed){ note(d.message+' New payout: '+ngn(d.new_payout)); pay.textContent=ngn(d.new_payout); return {done:true}; }
            return {done:false, message: d.message||(d.errors?Object.values(d.errors)[0][0]:'Submission failed.')};
        }catch(e){ return {done:false, message:'Network error. Please try again.'}; }
    });
}

function success(o){
    document.getElementById('bar').style.width='100%';
    view.innerHTML=`<div class="text-center py-10"><div class="text-6xl mb-3">📨</div><h2 class="text-xl font-bold">Submitted for review</h2>
      <p class="text-sm text-gray-500 mt-2 mb-1">Expected payout: <b>₦${esc(o.amount)}</b></p>
      <p class="text-xs text-gray-400 mb-6">You will be credited to your withdrawable balance once the card is approved. Track it in History.</p>
      <a href="${URLS.history}" class="block w-full py-3 rounded-xl font-bold text-white" style="background:#03624C;">Track in History</a>
      <a href="${URLS.back}" class="block mt-3 text-sm text-gray-500">Back to Gift Cards</a></div>`;
}

// ── PIN confirmation ──
let pendingAction=null;
function askPin(action){
    pendingAction=action;
    const m=document.getElementById('pinConfirmModal'), inp=document.getElementById('pinConfirmInput'), btn=document.getElementById('pinConfirmBtn');
    inp.value=''; btn.disabled=true; btn.textContent='Confirm & submit'; document.getElementById('pinConfirmMsg').className='hidden';
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
    btn.disabled=false; btn.textContent='Confirm & submit';
    document.getElementById('pinConfirmInput').value=''; document.getElementById('pinConfirmInput').focus();
};

init();
</script>
@include('partials.footer')
@endsection