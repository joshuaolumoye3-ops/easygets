@extends('layouts.admin')

@section('title', 'Services Management - Admin')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

@section('content')
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: var(--font-sans); }

  .page { padding: 1.5rem; max-width: 900px; }

  .page-header { margin-bottom: 1.5rem; }
  .page-header h1 { font-size: 22px; font-weight: 500; color: var(--color-text-primary); }
  .page-header p { font-size: 14px; color: var(--color-text-secondary); margin-top: 4px; }

  .summary-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 1.5rem;
  }
  .stat-card {
    background: var(--color-background-secondary);
    border-radius: var(--border-radius-md);
    padding: 1rem;
  }
  .stat-label { font-size: 12px; color: var(--color-text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
  .stat-value { font-size: 24px; font-weight: 500; color: var(--color-text-primary); }
  .stat-value.green { color: var(--color-text-success); }
  .stat-value.red { color: var(--color-text-danger); }

  .services-panel {
    background: var(--color-background-primary);
    border: 0.5px solid var(--color-border-tertiary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
  }

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 0.5px solid var(--color-border-tertiary);
    gap: 12px;
    flex-wrap: wrap;
  }
  .panel-title { font-size: 15px; font-weight: 500; color: var(--color-text-primary); }
  .panel-filters { display: flex; gap: 8px; align-items: center; }
  .filter-btn {
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 999px;
    border: 0.5px solid var(--color-border-secondary);
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.15s;
  }
  .filter-btn.active, .filter-btn:hover {
    background: var(--color-background-info);
    color: var(--color-text-info);
    border-color: var(--color-border-info);
  }

  .service-list { padding: 8px 0; }

  .service-row {
    display: grid;
    grid-template-columns: 44px 1fr auto;
    align-items: center;
    gap: 16px;
    padding: 14px 1.25rem;
    border-bottom: 0.5px solid var(--color-border-tertiary);
    transition: background 0.15s;
  }
  .service-row:last-child { border-bottom: none; }
  .service-row:hover { background: var(--color-background-secondary); }
  .service-row.disabled { opacity: 0.55; }

  .svc-icon {
    width: 44px; height: 44px;
    border-radius: var(--border-radius-md);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }
  .svc-icon.blue { background: var(--color-background-info); }
  .svc-icon.green { background: var(--color-background-success); }
  .svc-icon.amber { background: var(--color-background-warning); }
  .svc-icon.red { background: var(--color-background-danger); }
  .svc-icon.purple { background: #EEEDFE; }

  .svc-info { min-width: 0; }
  .svc-name { font-size: 14px; font-weight: 500; color: var(--color-text-primary); margin-bottom: 3px; }
  .svc-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .svc-slug {
    font-family: var(--font-mono);
    font-size: 11px;
    color: var(--color-text-info);
    background: var(--color-background-info);
    padding: 2px 7px;
    border-radius: 4px;
  }
  .svc-desc { font-size: 12px; color: var(--color-text-secondary); }

  .svc-controls { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }

  .status-pill {
    font-size: 11px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 999px;
    white-space: nowrap;
    display: flex; align-items: center; gap: 5px;
  }
  .status-pill.on {
    background: var(--color-background-success);
    color: var(--color-text-success);
    border: 0.5px solid var(--color-border-success);
  }
  .status-pill.off {
    background: var(--color-background-danger);
    color: var(--color-text-danger);
    border: 0.5px solid var(--color-border-danger);
  }
  .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  .toggle {
    position: relative; width: 44px; height: 24px;
    background: var(--color-border-secondary);
    border-radius: 999px; cursor: pointer;
    border: none; padding: 0;
    transition: background 0.2s;
    flex-shrink: 0;
  }
  .toggle.on { background: #1D9E75; }
  .toggle::after {
    content: '';
    position: absolute;
    top: 3px; left: 3px;
    width: 18px; height: 18px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  }
  .toggle.on::after { transform: translateX(20px); }

  .panel-footer {
    padding: 1rem 1.25rem;
    border-top: 0.5px solid var(--color-border-tertiary);
    background: var(--color-background-secondary);
    border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
  }
  .info-block { display: flex; gap: 12px; align-items: flex-start; }
  .info-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
  .info-text { font-size: 13px; color: var(--color-text-secondary); line-height: 1.6; }
  .info-text strong { color: var(--color-text-primary); font-weight: 500; }

  .toast {
    position: fixed; bottom: 20px; right: 20px;
    background: var(--color-background-primary);
    border: 0.5px solid var(--color-border-secondary);
    border-radius: var(--border-radius-md);
    padding: 10px 16px;
    font-size: 13px;
    color: var(--color-text-primary);
    display: flex; align-items: center; gap: 8px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.2s;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .toast.show { opacity: 1; pointer-events: auto; }
  .toast-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .toast-dot.on { background: var(--color-text-success); }
  .toast-dot.off { background: var(--color-text-danger); }

  @media (max-width: 560px) {
    .summary-bar { grid-template-columns: 1fr 1fr; }
    .svc-controls { gap: 8px; }
    .status-pill { display: none; }
    .service-row { grid-template-columns: 40px 1fr auto; gap: 12px; }
  }
</style>

<h2 class="sr-only">Services Management — enable or disable services available to users</h2>

<div class="page">
  <div class="page-header">
    <h1>Services management</h1>
    <p>Enable or disable services available to your users in real time</p>
  </div>

  <div class="summary-bar">
    <div class="stat-card">
      <div class="stat-label">Total services</div>
      <div class="stat-value" id="totalCount">8</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Active</div>
      <div class="stat-value green" id="activeCount">6</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Inactive</div>
      <div class="stat-value red" id="inactiveCount">2</div>
    </div>
  </div>

  <div class="services-panel">
    <div class="panel-header">
      <span class="panel-title">All services</span>
      <div class="panel-filters">
        <button class="filter-btn active" data-filter="all" onclick="setFilter(this,'all')">All</button>
        <button class="filter-btn" data-filter="on" onclick="setFilter(this,'on')">Active</button>
        <button class="filter-btn" data-filter="off" onclick="setFilter(this,'off')">Inactive</button>
      </div>
    </div>

    <div class="service-list" id="serviceList"></div>

    <div class="panel-footer">
      <div class="info-block">
        <span class="info-icon">ℹ</span>
        <div class="info-text">
          <strong>Active</strong> services are accessible through all user channels. 
          <strong>Inactive</strong> services redirect users to a maintenance message. 
          All changes are logged for security and audit purposes.
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <span class="toast-dot" id="toastDot"></span>
  <span id="toastMsg"></span>
</div>

<script>
const services = [
  { id: 1, name: 'Mobile data', slug: 'data', desc: 'Data bundles for all networks', icon: '📡', color: 'blue', active: true },
  { id: 2, name: 'Airtime', slug: 'airtime', desc: 'Instant airtime top-up', icon: '📞', color: 'green', active: true },
  { id: 3, name: 'Electricity', slug: 'electricity', desc: 'Utility bill payments', icon: '⚡', color: 'amber', active: true },
  { id: 4, name: 'Cable TV', slug: 'cabletv', desc: 'DSTV, GOtv, Startimes subscriptions', icon: '📺', color: 'purple', active: true },
  { id: 5, name: 'Betting funding', slug: 'betting', desc: 'Top up betting wallet', icon: '🎯', color: 'amber', active: false },
  { id: 6, name: 'OTP / Virtual numbers', slug: 'otp', desc: 'One-time password services', icon: '🔐', color: 'blue', active: true },
  { id: 7, name: 'Social boost', slug: 'social-boost', desc: 'Followers, likes & engagement', icon: '📈', color: 'purple', active: true },
  { id: 8, name: 'eSIM', slug: 'esim', desc: 'International eSIM plans', icon: '📲', color: 'green', active: false },
];

let currentFilter = 'all';

function updateCounts() {
  const on = services.filter(s => s.active).length;
  const off = services.length - on;
  document.getElementById('totalCount').textContent = services.length;
  document.getElementById('activeCount').textContent = on;
  document.getElementById('inactiveCount').textContent = off;
}

function render() {
  const list = document.getElementById('serviceList');
  const filtered = currentFilter === 'all' ? services
    : currentFilter === 'on' ? services.filter(s => s.active)
    : services.filter(s => !s.active);

  list.innerHTML = filtered.map(s => `
    <div class="service-row ${s.active ? '' : 'disabled'}" id="row-${s.id}">
      <div class="svc-icon ${s.color}">${s.icon}</div>
      <div class="svc-info">
        <div class="svc-name">${s.name}</div>
        <div class="svc-meta">
          <span class="svc-slug">${s.slug}</span>
          <span class="svc-desc">${s.desc}</span>
        </div>
      </div>
      <div class="svc-controls">
        <span class="status-pill ${s.active ? 'on' : 'off'}">
          <span class="status-dot"></span>
          ${s.active ? 'Active' : 'Inactive'}
        </span>
        <button
          class="toggle ${s.active ? 'on' : ''}"
          onclick="toggle(${s.id})"
          title="${s.active ? 'Disable ' : 'Enable '}${s.name}"
          aria-label="${s.active ? 'Disable' : 'Enable'} ${s.name}"
          aria-pressed="${s.active}"
        ></button>
      </div>
    </div>
  `).join('');

  if (filtered.length === 0) {
    list.innerHTML = `<div style="padding:2rem; text-align:center; color:var(--color-text-secondary); font-size:14px;">No services match this filter.</div>`;
  }
}

function toggle(id) {
  const svc = services.find(s => s.id === id);
  if (!svc) return;
  svc.active = !svc.active;
  updateCounts();
  render();
  showToast(svc);
}

function setFilter(el, filter) {
  currentFilter = filter;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  render();
}

let toastTimer;
function showToast(svc) {
  const toast = document.getElementById('toast');
  const dot = document.getElementById('toastDot');
  const msg = document.getElementById('toastMsg');
  dot.className = 'toast-dot ' + (svc.active ? 'on' : 'off');
  msg.textContent = `${svc.name} ${svc.active ? 'enabled' : 'disabled'}`;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

updateCounts();
render();
</script>

@endsection