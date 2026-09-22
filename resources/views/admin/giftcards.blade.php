@extends('layouts.admin')
@section('title', 'Gift Cards - Admin')

@section('content')
<style>
  .gc{padding-top:70px;min-height:100vh;color:#eef3ff;font-family:'Plus Jakarta Sans',-apple-system,sans-serif}
  .gc-in{max-width:1300px;margin:0 auto;padding:26px 22px}
  .gc-card{background:#111e38;border:1px solid rgba(99,152,255,.12);border-radius:16px;padding:18px;margin-bottom:18px}
  .gc h1{font-size:20px;font-weight:800}.gc h3{font-size:14px;font-weight:700;margin-bottom:12px}
  .gc-sub{font-size:12px;color:#7a9fd4}
  .gc-tabs{display:flex;gap:8px;margin:16px 0}
  .gc-tab{padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;background:#0d1629;color:#7a9fd4;border:1px solid rgba(99,152,255,.15);text-decoration:none}
  .gc-tab.on{background:#4f8cff;color:#fff;border-color:#4f8cff}
  .gc-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:18px}
  .gc-stat{background:#111e38;border:1px solid rgba(99,152,255,.12);border-radius:14px;padding:14px}
  .gc-stat b{display:block;font-size:20px;margin-top:6px}.gc-stat span{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#3a5578;font-weight:700}
  .gc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
  .gc label{display:block;font-size:11px;font-weight:600;color:#7a9fd4;margin-bottom:5px}
  .gc input,.gc select,.gc textarea{width:100%;height:38px;padding:0 11px;background:#0d1629;border:1px solid rgba(99,152,255,.2);border-radius:10px;color:#eef3ff;font-size:13px}
  .gc textarea{height:auto;padding:9px 11px}
  .gc-btn{height:38px;padding:0 16px;border:0;border-radius:10px;background:#4f8cff;color:#fff;font-weight:700;font-size:12.5px;cursor:pointer}
  .gc-btn.g{background:#34d98b;color:#062}.gc-btn.r{background:#f4566e}.gc-btn.o{background:transparent;border:1px solid rgba(99,152,255,.3);color:#93b8ff}
  .gc table{width:100%;border-collapse:collapse}.gc th{padding:9px 10px;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#3a5578;border-bottom:1px solid rgba(99,152,255,.1)}
  .gc td{padding:11px 10px;font-size:12.5px;border-bottom:1px solid rgba(99,152,255,.06);vertical-align:middle}
  .gc-scroll{overflow-x:auto}
  .pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700}
  .p-ok{background:rgba(52,217,139,.14);color:#6ee7b7}.p-wait{background:rgba(251,191,36,.14);color:#fcd34d}.p-bad{background:rgba(244,86,110,.14);color:#fda4af}
  .gc-toggle{position:relative;width:48px;height:26px;border-radius:99px;background:#374151;border:0;cursor:pointer;transition:.2s}
  .gc-toggle.on{background:#22c55e}.gc-toggle::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.2s}.gc-toggle.on::after{transform:translateX(22px)}
  .gc-modal{display:none;position:fixed;inset:0;background:rgba(4,8,20,.85);z-index:1000;align-items:center;justify-content:center;padding:16px}
  .gc-modal.open{display:flex}.gc-mb{background:#111e38;border:1px solid rgba(99,152,255,.2);border-radius:18px;width:100%;max-width:640px;max-height:90vh;overflow:auto;padding:20px}
  .kv{display:grid;grid-template-columns:150px 1fr;gap:6px 12px;font-size:12.5px}.kv dt{color:#7a9fd4}.kv dd{word-break:break-all}
  .gc-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.gc-filter input,.gc-filter select{width:auto;min-width:140px}
</style>

<div class="gc"><div class="gc-in">
  <h1>Gift Cards</h1>
  <p class="gc-sub">Buy &amp; sell gift cards through Prestmit. Environment: <b>{{ str_contains($prestmit['base'], 'dev-api') ? 'SANDBOX (test mode)' : 'LIVE' }}</b></p>

  <div class="gc-tabs">
    @foreach(['overview'=>'Overview & Settings','orders'=>'Orders','withdrawals'=>'Withdrawals'.($stats['pending_wd']?" ({$stats['pending_wd']})":'')] as $k=>$lbl)
      <a class="gc-tab {{ $tab===$k?'on':'' }}" href="{{ route('admin.giftcards.index',['tab'=>$k]) }}">{{ $lbl }}</a>
    @endforeach
  </div>

  <div class="gc-stats">
    <div class="gc-stat"><span>Pending sells</span><b>{{ $stats['pending_sells'] }}</b></div>
    <div class="gc-stat"><span>Pending buys</span><b>{{ $stats['pending_buys'] }}</b></div>
    <div class="gc-stat"><span>Needs review</span><b style="color:{{ $stats['needs_review']?'#fda4af':'inherit' }}">{{ $stats['needs_review'] }}</b></div>
    <div class="gc-stat"><span>Buy volume</span><b>₦{{ number_format($stats['buy_volume']) }}</b></div>
    <div class="gc-stat"><span>Sell payouts</span><b>₦{{ number_format($stats['sell_payouts']) }}</b></div>
    <div class="gc-stat"><span>Users' withdrawable</span><b>₦{{ number_format($stats['withdrawable']) }}</b></div>
    <div class="gc-stat"><span>Pending withdrawals</span><b>₦{{ number_format($stats['pending_wd_amt']) }}</b></div>
  </div>

@if($tab==='overview')
  <div class="gc-card">
    <h3>Service switches</h3>
    <div class="gc-grid">
      @foreach(['giftcard-buy'=>'Buy gift cards','giftcard-sell'=>'Sell gift cards'] as $slug=>$lbl)
        <div style="display:flex;align-items:center;justify-content:space-between;background:#0d1629;border-radius:12px;padding:12px 14px">
          <div><b style="font-size:13px">{{ $lbl }}</b><div class="gc-sub" id="st-{{ $slug }}">{{ $services[$slug]->is_active ? 'Active — visible to users' : 'Inactive — hidden from users' }}</div></div>
          <button class="gc-toggle {{ $services[$slug]->is_active?'on':'' }}" data-id="{{ $services[$slug]->id }}" data-slug="{{ $slug }}" aria-label="Toggle {{ $lbl }}"></button>
        </div>
      @endforeach
    </div>
  </div>

  <div class="gc-card">
    <h3>Prestmit connection</h3>
    @if(!$prestmit['configured'])
      <span class="pill p-bad">API keys not set</span> <span class="gc-sub">Add PRESTMIT_API_KEY / PRESTMIT_API_SECRET to .env</span>
    @elseif($prestmit['ok'])
      <span class="pill p-ok">Connected</span>
      <span class="gc-sub">Buy: {{ ($prestmit['data']['buyGiftCard'] ?? false)?'on':'off' }} · Sell: {{ ($prestmit['data']['sellGiftCard'] ?? false)?'on':'off' }} · Naira withdrawals: {{ ($prestmit['data']['nairaWalletWithdrawal'] ?? false)?'on':'off' }}</span>
    @else
      <span class="pill p-bad">Connection failed</span> <span class="gc-sub">{{ $prestmit['error'] }}</span>
    @endif
    <p class="gc-sub" style="margin-top:8px">Base URL: {{ $prestmit['base'] }} · Webhook URL to register: <b>{{ url('/app/webhook/prestmit') }}</b></p>
  </div>

  <div class="gc-card">
    <h3>Pricing &amp; limits</h3>
    <form method="POST" action="{{ route('admin.giftcards.settings') }}">@csrf
      <div class="gc-grid">
        <div><label>Buy: USD → NGN rate (₦ per $1)</label><input type="number" step="0.01" name="buy_usd_rate" value="{{ $settings['buy_usd_rate'] }}" required></div>
        <div><label>Buy: markup on card price (%)</label><input type="number" step="0.01" name="buy_markup_percent" value="{{ $settings['buy_markup_percent'] }}" required></div>
        <div><label>Sell: our commission deducted from rate (%)</label><input type="number" step="0.01" name="sell_commission_percent" value="{{ $settings['sell_commission_percent'] }}" required></div>
        <div><label>Minimum withdrawal (₦)</label><input type="number" step="0.01" name="min_withdrawal" value="{{ $settings['min_withdrawal'] }}" required></div>
        <div><label>Max cards per buy order</label><input type="number" name="max_buy_quantity" value="{{ $settings['max_buy_quantity'] }}" required></div>
        <div><label>Daily buy limit per user (₦, 0 = none)</label><input type="number" step="0.01" name="daily_buy_limit_ngn" value="{{ $settings['daily_buy_limit_ngn'] }}" required></div>
      </div>
      <p class="gc-sub" style="margin:12px 0">Example: a $5 card with {{ $settings['buy_markup_percent'] }}% markup = $5 + ${{ number_format(5*$settings['buy_markup_percent']/100,2) }} = ${{ number_format(5*(1+$settings['buy_markup_percent']/100),2) }} × ₦{{ number_format($settings['buy_usd_rate']) }} = <b>₦{{ number_format(5*(1+$settings['buy_markup_percent']/100)*$settings['buy_usd_rate']) }}</b> to the customer. Purchases priced below Prestmit's own cost are automatically blocked.</p>
      <button class="gc-btn" type="submit">Save settings</button> <span class="gc-sub">Super admins only</span>
    </form>
  </div>
@endif

@if($tab==='orders')
  <div class="gc-card">
    <form class="gc-filter" method="GET"><input type="hidden" name="tab" value="orders">
      <input type="search" name="q" placeholder="Reference / user…" value="{{ request('q') }}">
      <select name="type"><option value="">All types</option>@foreach(['buy','sell'] as $t)<option value="{{ $t }}" @selected(request('type')===$t)>{{ ucfirst($t) }}</option>@endforeach</select>
      <select name="status"><option value="">All statuses</option>@foreach(['pending','processing','completed','rejected','failed','refunded'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
      <label style="display:flex;align-items:center;gap:6px;margin:0"><input type="checkbox" name="review" value="1" style="width:auto;height:auto" @checked(request('review'))> Needs review</label>
      <button class="gc-btn" type="submit">Filter</button>
    </form>
    <div class="gc-scroll"><table>
      <thead><tr><th>Ref</th><th>User</th><th>Type</th><th>Card</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
      @forelse($orders as $o)
        <tr>
          <td style="font-family:monospace;font-size:11px">{{ $o->reference }}</td>
          <td>{{ $o->user->username ?? '—' }}</td>
          <td>{{ ucfirst($o->type) }}</td>
          <td>{{ $o->title }}<div class="gc-sub">{{ $o->face_value + 0 }} {{ $o->currency_code }}{{ $o->quantity>1?' × '.$o->quantity:'' }}</div></td>
          <td>₦{{ number_format($o->type==='sell' ? ($o->payout_ngn ?? $o->total_ngn) : $o->total_ngn, 2) }}</td>
          <td><span class="pill {{ $o->status==='completed'?'p-ok':(in_array($o->status,['failed','rejected','refunded'])?'p-bad':'p-wait') }}">{{ $o->status }}</span>@if($o->needs_review && !$o->settled_at) <span class="pill p-bad">review</span>@endif</td>
          <td class="gc-sub">{{ $o->created_at->format('d M Y H:i') }}</td>
          <td><button class="gc-btn o" onclick="openOrder({{ $o->id }})">View</button></td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center;padding:36px" class="gc-sub">No orders found.</td></tr>
      @endforelse
      </tbody></table></div>
    <div style="margin-top:12px">{{ $orders->links('partials.admin.custom') }}</div>
  </div>
@endif

@if($tab==='withdrawals')
  <div class="gc-card">
    <form class="gc-filter" method="GET"><input type="hidden" name="tab" value="withdrawals">
      <select name="wstatus"><option value="">All</option>@foreach(['pending','approved','declined'] as $s)<option value="{{ $s }}" @selected(request('wstatus')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
      <button class="gc-btn" type="submit">Filter</button>
    </form>
    <div class="gc-scroll"><table>
      <thead><tr><th>Ref</th><th>User</th><th>Amount</th><th>Bank details</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      @forelse($withdrawals as $w)
        <tr>
          <td style="font-family:monospace;font-size:11px">{{ $w->reference }}</td>
          <td>{{ $w->user->username ?? '—' }}</td>
          <td><b>₦{{ number_format($w->amount, 2) }}</b></td>
          <td>{{ $w->account_name }}<div class="gc-sub">{{ $w->bank_name }} · {{ $w->account_number }}</div></td>
          <td><span class="pill {{ $w->status==='approved'?'p-ok':($w->status==='declined'?'p-bad':'p-wait') }}">{{ $w->status==='approved'?'paid':$w->status }}</span>@if($w->admin_note)<div class="gc-sub">{{ $w->admin_note }}</div>@endif</td>
          <td class="gc-sub">{{ $w->created_at->format('d M Y H:i') }}</td>
          <td>@if($w->status==='pending')
            <form method="POST" action="{{ route('admin.giftcards.withdrawals.approve',$w->id) }}" style="display:inline" onsubmit="return confirm('Confirm you have transferred ₦{{ number_format($w->amount,2) }} to this account?')">@csrf<input type="hidden" name="note" value=""><button class="gc-btn g" type="submit">Mark paid</button></form>
            <form method="POST" action="{{ route('admin.giftcards.withdrawals.decline',$w->id) }}" style="display:inline" onsubmit="const n=prompt('Reason for declining (user is refunded):');if(!n){return false}this.note.value=n;return true">@csrf<input type="hidden" name="note"><button class="gc-btn r" type="submit">Decline</button></form>
          @else — @endif</td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:36px" class="gc-sub">No withdrawals.</td></tr>
      @endforelse
      </tbody></table></div>
    <div style="margin-top:12px">{{ $withdrawals->links('partials.admin.custom') }}</div>
  </div>
@endif
</div></div>

<div class="gc-modal" id="ordModal"><div class="gc-mb"><div style="display:flex;justify-content:space-between;margin-bottom:12px"><h3 id="ordTitle">Order</h3><button class="gc-btn o" onclick="document.getElementById('ordModal').classList.remove('open')">Close</button></div><div id="ordBody">Loading…</div></div></div>

<script>
(function(){
  const CSRF=document.querySelector('meta[name="csrf-token"]')?.content||'';
  const esc=s=>{const d=document.createElement('div');d.textContent=String(s??'—');return d.innerHTML;};
  document.querySelectorAll('.gc-toggle').forEach(b=>b.onclick=async()=>{
    b.disabled=true;
    try{
      const r=await fetch(@json(url('/app/admin/services')) + '/' + b.dataset.id + '/toggle',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:'{}'});
      const d=await r.json(); if(!r.ok) throw new Error(d.message||'Failed');
      b.classList.toggle('on',!!d.is_active);
      document.getElementById('st-'+b.dataset.slug).textContent=d.is_active?'Active — visible to users':'Inactive — hidden from users';
    }catch(e){alert(e.message);} b.disabled=false;
  });
  window.openOrder=async function(id){
    const m=document.getElementById('ordModal'),body=document.getElementById('ordBody'); body.textContent='Loading…'; m.classList.add('open');
    const r=await fetch(@json(url('/app/admin/giftcards/orders')) + '/' + id,{headers:{'Accept':'application/json'}}); const d=await r.json(); const o=d.order;
    document.getElementById('ordTitle').textContent=o.reference;
    const rows=[['Type',o.type],['Status',o.status+' (provider: '+(o.provider_status||'—')+')'],['User',o.user],['Card',o.title+' · '+(o.category||'')],['Form / country',(o.form||'—')+' / '+(o.country||'—')],['Card value',o.face_value+' '+(o.currency||'')+' × '+o.quantity],
      [o.type==='buy'?'Charged to user':'Quoted payout','₦'+o.total_ngn],['Provider amount','₦'+(o.provider_amount_ngn??'—')],['Payout credited',o.payout_ngn!=null?'₦'+o.payout_ngn:'—'],['Pricing snapshot',JSON.stringify(o.pricing||{})],
      ['Provider reference',o.provider_reference],['Reject reason',o.reject_reason],['Created',o.created_at],['Last synced',o.last_synced_at],['Settled',o.settled_at],['User note / code',o.has_comments?'provided (hidden)':'—']];
    if(o.codes&&o.codes.length) rows.push(['Delivered cards',o.codes.map(c=>c.cardNumber||'claim link').join(', ')+' (masked)']);
    body.innerHTML='<dl class="kv">'+rows.map(([k,v])=>`<dt>${esc(k)}</dt><dd>${esc(v)}</dd>`).join('')+'</dl>'
      +(o.attachment_urls.length?'<h3 style="margin:14px 0 8px">Card images</h3><div style="display:flex;gap:8px;flex-wrap:wrap">'+o.attachment_urls.map(u=>`<a href="${u}" target="_blank" rel="noopener"><img src="${u}" style="width:90px;height:90px;object-fit:cover;border-radius:10px;border:1px solid #334"></a>`).join('')+'</div>':'')
      +((o.settled_at)?'':`<button class="gc-btn" style="margin-top:14px" onclick="syncOrder(${o.id},this)">Re-check status with Prestmit</button>`);
  };
  window.syncOrder=async function(id,btn){
    btn.disabled=true;btn.textContent='Checking…';
    const r=await fetch(@json(url('/app/admin/giftcards/orders')) + '/' + id + '/sync',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}}); const d=await r.json();
    alert(d.message); if(d.success) location.reload(); else {btn.disabled=false;btn.textContent='Re-check status with Prestmit';}
  };
})();
</script>
@endsection