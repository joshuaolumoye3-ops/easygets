@extends('layouts.admin')

@section('title', 'Services Management - Admin')

@section('content')
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg-base:    #060d1a;
    --bg-surface: #0b1525;
    --bg-card:    #0f1e35;
    --border:     rgba(80,130,220,0.11);
    --border-hi:  rgba(80,130,220,0.22);
    --accent:     #3b82f6;
    --accent-d:   rgba(59,130,246,0.14);
    --green:      #10b981;
    --green-d:    rgba(16,185,129,0.14);
    --red:        #ef4444;
    --red-d:      rgba(239,68,68,0.14);
    --t1: #eef4ff;
    --t2: #7da0cc;
    --t3: #3d5a7a;
    --r-sm: 8px; --r-md: 12px; --r-lg: 18px;
    --font: 'DM Sans', sans-serif;
    --mono: 'DM Mono', monospace;
  }

  body { font-family: var(--font); background: var(--bg-base); color: var(--t1); -webkit-font-smoothing: antialiased; }

  /* ─── Layout wrapper (mirrors admin dashboard) ─── */
  .d-wrap  { padding-top: 70px; min-height: 100vh; }
  .d-inner { padding: 32px 26px; max-width: 1100px; margin: 0 auto; }

  /* ─── Top bar ─── */
  .top-bar {
    position: relative;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 14px;
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--r-lg); padding: 20px 26px; margin-bottom: 26px;
  }
  .top-bar::after {
    content: ''; position: absolute; inset: 0; border-radius: var(--r-lg);
    background: linear-gradient(135deg, rgba(59,130,246,0.04) 0%, transparent 60%);
    pointer-events: none;
  }
  .tb-l h1 { font-size: 21px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
  .tb-l p  { font-size: 12px; color: var(--t3); margin-top: 3px; font-weight: 500; }

  /* ─── Summary cards ─── */
  .summary-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 26px;
  }
  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: border-color .2s, transform .2s;
  }
  .stat-card:hover { border-color: var(--border-hi); transform: translateY(-2px); }
  .stat-card .bar { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--r-lg) var(--r-lg) 0 0; }
  .stat-card.bl .bar { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
  .stat-card.gr .bar { background: linear-gradient(90deg,#10b981,#34d399); }
  .stat-card.rd .bar { background: linear-gradient(90deg,#ef4444,#f87171); }
  .stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--t3); margin-bottom: 9px; }
  .stat-value { font-size: 32px; font-weight: 700; color: var(--t1); letter-spacing: -.5px; line-height: 1; }
  .stat-value.green { color: #34d399; }
  .stat-value.red   { color: #f87171; }

  /* ─── Services panel ─── */
  .services-panel {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    overflow: hidden;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: border-color .3s, box-shadow .3s;
  }
  .services-panel:hover { border-color: var(--border-hi); box-shadow: 0 4px 16px rgba(59,130,246,0.1); }

  .panel-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap; gap: 12px;
    background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.02) 100%);
  }
  .panel-title-group { display: flex; align-items: center; gap: 12px; }
  .panel-title { font-size: 16px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
  .pill {
    padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
    background: linear-gradient(135deg, var(--accent-d), rgba(59,130,246,0.2));
    color: #93c5fd; border: 1px solid rgba(59,130,246,0.3);
  }

  .panel-filters { display: flex; gap: 6px; align-items: center; }
  .filter-btn {
    font-size: 11px; font-weight: 700;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--border-hi);
    background: transparent;
    color: var(--t3);
    cursor: pointer;
    transition: all 0.2s;
    font-family: var(--font);
    letter-spacing: 0.3px;
  }
  .filter-btn:hover { border-color: var(--accent); color: #93c5fd; background: var(--accent-d); }
  .filter-btn.active { background: var(--accent-d); color: #93c5fd; border-color: rgba(59,130,246,0.4); }

  /* ─── Service rows ─── */
  .service-list { padding: 8px 0; }

  .service-row {
    display: grid;
    grid-template-columns: 52px 1fr auto;
    align-items: center;
    gap: 18px;
    padding: 16px 22px;
    border-bottom: 1px solid rgba(80,130,220,0.06);
    transition: background 0.15s;
  }
  .service-row:last-child { border-bottom: none; }
  .service-row:hover { background: rgba(59,130,246,0.05); }
  .service-row.row-disabled { opacity: 0.5; }

  .svc-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.05);
  }
  .svc-icon.blue   { background: rgba(59,130,246,0.15); }
  .svc-icon.green  { background: rgba(16,185,129,0.15); }
  .svc-icon.amber  { background: rgba(245,158,11,0.15); }
  .svc-icon.purple { background: rgba(139,92,246,0.15); }
  .svc-icon.red    { background: rgba(239,68,68,0.15); }

  .svc-info { min-width: 0; }
  .svc-name { font-size: 15px; font-weight: 700; color: var(--t1); margin-bottom: 5px; letter-spacing: -0.2px; }
  .svc-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .svc-slug {
    font-family: var(--mono);
    font-size: 11px; font-weight: 500;
    color: #93c5fd;
    background: var(--accent-d);
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid rgba(59,130,246,0.2);
    letter-spacing: 0.4px;
  }
  .svc-desc { font-size: 12px; color: var(--t3); }

  .svc-controls { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

  .status-pill {
    font-size: 11px; font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
    white-space: nowrap;
    display: inline-flex; align-items: center; gap: 6px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
  }
  .status-pill.on {
    background: rgba(16,185,129,0.15);
    color: #34d399;
    border: 1px solid rgba(16,185,129,0.3);
  }
  .status-pill.off {
    background: rgba(239,68,68,0.15);
    color: #f87171;
    border: 1px solid rgba(239,68,68,0.3);
  }
  .status-dot {
    width: 6px; height: 6px; border-radius: 50%; background: currentColor;
    animation: pulse 2s cubic-bezier(0.4,0,0.6,1) infinite;
  }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

  /* Toggle switch */
  .toggle {
    position: relative; width: 52px; height: 28px;
    background: #374151;
    border-radius: 999px; cursor: pointer;
    border: 2px solid #4b5563;
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    flex-shrink: 0;
  }
  .toggle:hover { border-color: #5f6b7a; }
  .toggle.on { background: #22c55e; border-color: #16a34a; box-shadow: 0 0 10px rgba(34,197,94,0.3); }
  .toggle::after {
    content: '';
    position: absolute;
    top: 3px; left: 3px;
    width: 18px; height: 18px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
  }
  .toggle.on::after { transform: translateX(24px); }
  .toggle.loading-toggle { opacity: 0.5; pointer-events: none; }

  /* ─── Panel footer / info box ─── */
  .panel-footer {
    padding: 18px 22px;
    border-top: 1px solid var(--border);
    background: rgba(59,130,246,0.03);
  }
  .info-block { display: flex; gap: 14px; align-items: flex-start; }
  .info-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
  .info-text { font-size: 13px; color: var(--t2); line-height: 1.7; }
  .info-text strong { color: #93c5fd; font-weight: 700; }

  /* ─── Empty state ─── */
  .empty-state {
    padding: 48px 24px; text-align: center;
  }
  .empty-state .ei { font-size: 40px; opacity: .3; margin-bottom: 12px; display: block; }
  .empty-state p { color: var(--t3); font-size: 14px; font-weight: 500; }

  /* ─── Toast ─── */
  .toast {
    position: fixed; bottom: 24px; right: 24px;
    background: var(--bg-card);
    border: 1px solid var(--border-hi);
    border-radius: var(--r-md);
    padding: 12px 18px;
    font-size: 13px; font-weight: 600;
    color: var(--t1);
    display: flex; align-items: center; gap: 10px;
    opacity: 0; pointer-events: none;
    transform: translateY(8px);
    transition: opacity 0.2s, transform 0.2s;
    z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    min-width: 220px;
  }
  .toast.show { opacity: 1; pointer-events: auto; transform: translateY(0); }
  .toast-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .toast-dot.on  { background: #34d399; }
  .toast-dot.off { background: #f87171; }

  /* ─── Flash messages ─── */
  .flash {
    margin-bottom: 20px; padding: 14px 18px;
    border-radius: var(--r-md); font-size: 14px; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    animation: slideIn 0.3s ease;
  }
  .flash.ok  { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
  .flash.err { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
  @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }

  /* ─── Responsive ─── */
  @media (max-width: 768px) {
    .d-inner { padding: 16px 14px; }
    .summary-bar { grid-template-columns: 1fr 1fr; }
    .service-row { grid-template-columns: 44px 1fr auto; gap: 12px; padding: 14px 16px; }
    .svc-controls { gap: 8px; }
    .status-pill { display: none; }
  }
  @media (max-width: 480px) {
    .summary-bar { grid-template-columns: 1fr; }
    .panel-header { flex-direction: column; align-items: flex-start; }
    .svc-name { font-size: 13px; }
  }
</style>

<div class="d-wrap">
<div class="d-inner">

  {{-- Top bar --}}
  <div class="top-bar">
    <div class="tb-l">
      <h1>Services Management</h1>
      <p>Enable or disable services available to your users in real time</p>
    </div>
  </div>

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="flash ok">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="flash err">{{ session('error') }}</div>
  @endif

  {{-- Summary cards --}}
  <div class="summary-bar">
    <div class="stat-card bl">
      <div class="bar"></div>
      <div class="stat-label">Total Services</div>
      <div class="stat-value" id="totalCount">{{ $services->count() }}</div>
    </div>
    <div class="stat-card gr">
      <div class="bar"></div>
      <div class="stat-label">Active</div>
      <div class="stat-value green" id="activeCount">{{ $services->where('is_active', true)->count() }}</div>
    </div>
    <div class="stat-card rd">
      <div class="bar"></div>
      <div class="stat-label">Inactive</div>
      <div class="stat-value red" id="inactiveCount">{{ $services->where('is_active', false)->count() }}</div>
    </div>
  </div>

  {{-- Services panel --}}
  <div class="services-panel">
    <div class="panel-header">
      <div class="panel-title-group">
        <span class="panel-title">All Services</span>
        <span class="pill" id="visibleCount">{{ $services->count() }} Total</span>
      </div>
      <div class="panel-filters">
        <button class="filter-btn active" onclick="setFilter(this,'all')">All</button>
        <button class="filter-btn" onclick="setFilter(this,'on')">Active</button>
        <button class="filter-btn" onclick="setFilter(this,'off')">Inactive</button>
      </div>
    </div>

    <div class="service-list" id="serviceList">
      @forelse($services as $service)
        @php
          $iconMap = [
            'data'         => ['icon' => '📡', 'color' => 'blue'],
            'airtime'      => ['icon' => '📞', 'color' => 'green'],
            'electricity'  => ['icon' => '⚡', 'color' => 'amber'],
            'cabletv'      => ['icon' => '📺', 'color' => 'purple'],
            'betting'      => ['icon' => '🎯', 'color' => 'amber'],
            'otp'          => ['icon' => '🔐', 'color' => 'blue'],
            'social-boost' => ['icon' => '📈', 'color' => 'purple'],
            'esim'         => ['icon' => '📲', 'color' => 'green'],
          ];
          $meta  = $iconMap[$service->slug] ?? ['icon' => '⚙️', 'color' => 'blue'];
          $descMap = [
            'data'         => 'Data bundles for all networks',
            'airtime'      => 'Instant airtime top-up',
            'electricity'  => 'Utility bill payments',
            'cabletv'      => 'DSTV, GOtv, Startimes subscriptions',
            'betting'      => 'Top up betting wallet',
            'otp'          => 'One-time password services',
            'social-boost' => 'Followers, likes & engagement',
            'esim'         => 'International eSIM plans',
          ];
          $desc = $descMap[$service->slug] ?? 'Service description';
        @endphp
        <div class="service-row {{ $service->is_active ? '' : 'row-disabled' }}"
             data-id="{{ $service->id }}"
             data-active="{{ $service->is_active ? '1' : '0' }}">

          <div class="svc-icon {{ $meta['color'] }}">{{ $meta['icon'] }}</div>

          <div class="svc-info">
            <div class="svc-name">{{ $service->name }}</div>
            <div class="svc-meta">
              <span class="svc-slug">{{ $service->slug }}</span>
              <span class="svc-desc">{{ $desc }}</span>
            </div>
          </div>

          <div class="svc-controls">
            <span class="status-pill {{ $service->is_active ? 'on' : 'off' }}">
              <span class="status-dot"></span>
              {{ $service->is_active ? 'Active' : 'Inactive' }}
            </span>
            <button
              class="toggle {{ $service->is_active ? 'on' : '' }}"
              onclick="toggleService(this, {{ $service->id }})"
              title="{{ $service->is_active ? 'Disable' : 'Enable' }} {{ $service->name }}"
              aria-label="{{ $service->is_active ? 'Disable' : 'Enable' }} {{ $service->name }}"
              aria-pressed="{{ $service->is_active ? 'true' : 'false' }}"
            ></button>
          </div>
        </div>
      @empty
        <div class="empty-state">
          <span class="ei">📭</span>
          <p>No services found in the system yet.</p>
        </div>
      @endforelse
    </div>

    <div class="panel-footer">
      <div class="info-block">
        <span class="info-icon">ℹ️</span>
        <div class="info-text">
          <strong>Active</strong> services are accessible through all user channels.
          <strong>Inactive</strong> services redirect users to a maintenance message.
          Direct URLs to disabled services are automatically blocked.
          All status changes are logged for security and accountability.
        </div>
      </div>
    </div>
  </div>

</div>
</div>

{{-- Toast notification --}}
<div class="toast" id="toast">
  <span class="toast-dot" id="toastDot"></span>
  <span id="toastMsg"></span>
</div>

<script>
(function () {
  'use strict';

  const BASE = @json(url('/'));
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  let toastTimer;

  /* ── Filter ── */
  window.setFilter = function (el, filter) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');

    let visible = 0;
    document.querySelectorAll('.service-row').forEach(row => {
      const isActive = row.dataset.active === '1';
      const show = filter === 'all'
        || (filter === 'on'  &&  isActive)
        || (filter === 'off' && !isActive);
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    document.getElementById('visibleCount').textContent =
      visible + (filter === 'all' ? ' Total' : filter === 'on' ? ' Active' : ' Inactive');
  };

  /* ── Toggle ── */
  window.toggleService = async function (btn, serviceId) {
    const row     = btn.closest('.service-row');
    const pill    = row.querySelector('.status-pill');
    const wasOn   = btn.classList.contains('on');

    btn.classList.add('loading-toggle');

    try {
      const res  = await fetch(`${BASE}/app/admin/services/${serviceId}/toggle`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json'
        },
        body: JSON.stringify({})
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to update');

      const nowOn = !!data.is_active;

      /* Update toggle */
      btn.classList.toggle('on', nowOn);
      btn.setAttribute('aria-pressed', nowOn);
      btn.title = (nowOn ? 'Disable' : 'Enable') + ' this service';

      /* Update pill */
      pill.className = 'status-pill ' + (nowOn ? 'on' : 'off');
      pill.innerHTML = `<span class="status-dot"></span>${nowOn ? 'Active' : 'Inactive'}`;

      /* Update row dim */
      row.classList.toggle('row-disabled', !nowOn);
      row.dataset.active = nowOn ? '1' : '0';

      /* Update summary counts */
      updateCounts();

      /* Toast */
      showToast(data.message || (nowOn ? 'Service enabled' : 'Service disabled'), nowOn);

    } catch (err) {
      console.error(err);
      showToast(err.message || 'Something went wrong', false, true);
    } finally {
      btn.classList.remove('loading-toggle');
    }
  };

  function updateCounts () {
    const rows    = [...document.querySelectorAll('.service-row')];
    const total   = rows.length;
    const active  = rows.filter(r => r.dataset.active === '1').length;
    const inactive = total - active;

    document.getElementById('totalCount').textContent   = total;
    document.getElementById('activeCount').textContent  = active;
    document.getElementById('inactiveCount').textContent = inactive;
  }

  function showToast (msg, isOn, isError = false) {
    const toast = document.getElementById('toast');
    const dot   = document.getElementById('toastDot');
    const text  = document.getElementById('toastMsg');

    dot.className = 'toast-dot ' + (isError ? 'off' : isOn ? 'on' : 'off');
    text.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
  }

})();
</script>

@endsection