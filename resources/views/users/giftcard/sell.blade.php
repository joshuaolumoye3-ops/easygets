@extends('layouts.app')
@section('title', 'Sell Gift Card')

@section('content')
<div class="max-w-md mx-auto pb-28 min-h-screen bg-gray-50">
    <div class="relative flex items-center px-4 h-14 bg-white border-b border-gray-100">
        <button id="backBtn" class="z-10" aria-label="Back"><svg class="h-6 w-6 text-gray-800" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd"/></svg></button>
        <h1 id="title" class="absolute inset-0 flex justify-center items-center text-lg font-semibold">Sell Gift Card</h1>
    </div>
    <div class="px-4 pt-3"><div class="h-1.5 bg-gray-200 rounded-full overflow-hidden"><div id="bar" class="h-full bg-[#03624C] transition-all" style="width:33%"></div></div></div>
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
const flagUrl=f=>f?`https://flagcdn.com/24x18/${f}.png`:null;
function logoImg(logo,size='w-full h-full object-cover'){ if(!logo) return null; return `<img src="${esc(logo)}" alt="" loading="lazy" class="${size}" onerror="this.parentElement.dataset.fallback='1';this.remove()">`; }
function flagBadge(flag){ if(!flag) return ''; return `<img src="${flagUrl(flag)}" alt="" class="inline-block rounded-sm" style="width:16px;height:12px;object-fit:cover" onerror="this.remove()">`; }
const MAX_IMG=20, MAX_BYTES=5*1024*1024;

let DATA=null, step=1, brand=null, card=null, files=[], clientToken=token();
const view=document.getElementById('view');

document.getElementById('backBtn').onclick=()=>{ if(step===1) location.href=URLS.back; else { step--; render(); } };

async function init(){
    view.innerHTML='<p class="text-center text-sm text-gray-400 py-16">Loading live rates…</p>';
    try{
        const r=await fetch(URLS.catalog,{headers:{'Accept':'application/json'}}); const d=await r.json();
        if(!r.ok||!d.success) throw new Error(d.message||'Rates unavailable');
        DATA=d; render();
    }catch(e){ view.innerHTML=`<div class="text-center py-16"><p class="text-red-500 text-sm">${esc(e.message)}</p><button onclick="init()" class="mt-3 text-[#03624C] font-semibold text-sm">Retry</button></div>`; }
}
function render(){
    document.getElementById('bar').style.width=(step*33.3)+'%';
    document.getElementById('title').textContent=['','Choose brand','Choose card type','Card details'][step];
    if(step===1) return stepBrand();
    if(step===2) return stepCard();
    return stepForm();
}
function stepBrand(){
    view.innerHTML=`<input id="bq" type="search" placeholder="Search brands…" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-[#03624C]"><div id="bl" class="grid grid-cols-2 gap-3"></div>`;
    const paint=(f='')=>{
        document.getElementById('bl').innerHTML=DATA.categories.filter(c=>c.toLowerCase().includes(f)).map(c=>{
            const logo=DATA.categoryLogos && DATA.categoryLogos[c];
            const icon=logoImg(logo,'w-full h-full object-contain p-1.5');
            return `<button data-b="${esc(c)}" class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm hover:shadow-md text-left transition">
                <div class="w-10 h-10 rounded-xl bg-[#03624C]/10 overflow-hidden flex items-center justify-center mb-2 text-lg font-extrabold text-[#03624C]">${icon || esc(c.charAt(0).toUpperCase())}</div>
                <p class="font-semibold text-sm text-gray-800">${esc(c)}</p></button>`;
        }).join('')||'<p class="col-span-2 text-center text-sm text-gray-400 py-8">No brands found.</p>';
        document.querySelectorAll('#bl button').forEach(b=>b.onclick=()=>{ brand=b.dataset.b; step=2; render(); }); };
    paint(); document.getElementById('bq').oninput=e=>paint(e.target.value.toLowerCase());
}
function stepCard(){
    const list=DATA.cards.filter(c=>c.category===brand);
    view.innerHTML=`<p class="text-xs font-bold text-gray-500 uppercase mb-2">${esc(brand)}</p><div class="space-y-2">${list.map(c=>{
        const icon=logoImg(c.logo,'w-full h-full object-contain p-1.5');
        return `<button data-id="${c.id}" class="w-full bg-white rounded-2xl p-4 border border-gray-100 shadow-sm hover:shadow-md text-left transition flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0 text-lg">${icon || '🎁'}</div>
        <div class="min-w-0 flex-1"><p class="font-semibold text-sm text-gray-800">${esc(c.name)}</p><p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">${flagBadge(c.flag)} ${esc(c.country)}${c.form?' · '+esc(c.form):''}${c.minimum?' · min '+c.minimum:''}</p></div>
        <div class="text-right flex-shrink-0"><p class="text-sm font-extrabold text-[#03624C]">₦${Number(c.rate).toLocaleString('en-NG',{maximumFractionDigits:2})}</p><p class="text-[10px] text-gray-400">per $1</p></div></button>`;
    }).join('')}</div>`;
    document.querySelectorAll('[data-id]').forEach(b=>b.onclick=()=>{ card=DATA.cards.find(c=>c.id==b.dataset.id); files=[]; clientToken=token(); step=3; render(); });
}
function stepForm(){
    const form=(card.form||'').toLowerCase(), needImg=form!=='ecode', needCode=form!=='physical';
    const icon=logoImg(card.logo,'w-full h-full object-contain p-1');
    view.innerHTML=`
      <div class="bg-white rounded-2xl p-4 border border-gray-100 mb-3 flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0 text-lg">${icon||'🎁'}</div>
        <div><p class="font-bold text-gray-800 text-sm">${esc(card.name)}</p><p class="text-xs text-gray-400 flex items-center gap-1">${flagBadge(card.flag)} ${esc(card.country)}${card.form?' · '+esc(card.form):''} · ₦${Number(card.rate).toLocaleString('en-NG')} per $1</p></div>
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

// ── PIN confirmation (shared pattern with buy page) ──
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