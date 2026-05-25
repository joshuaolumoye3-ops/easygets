@extends('layouts.admin')

@section('title', 'Logs Orders')

@section('content')
<style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
        --bg:          #080f1c;
        --bg-2:        #0d1629;
        --bg-3:        #111e38;
        --bg-4:        #162340;
        --border:      rgba(99,152,255,0.10);
        --border-2:    rgba(99,152,255,0.20);
        --border-3:    rgba(99,152,255,0.30);

        --blue:        #4f8cff;
        --blue-dim:    rgba(79,140,255,0.12);
        --blue-glow:   rgba(79,140,255,0.22);
        --blue-text:   #93b8ff;

        --violet:      #8b7cf8;
        --violet-dim:  rgba(139,124,248,0.12);
        --violet-text: #c4b8fd;

        --emerald:     #34d98b;
        --emerald-dim: rgba(52,217,139,0.12);
        --emerald-text:#6ee7b7;

        --amber:       #fbbf24;
        --amber-dim:   rgba(251,191,36,0.12);
        --amber-text:  #fcd34d;

        --rose:        #f4566e;
        --rose-dim:    rgba(244,86,110,0.12);
        --rose-text:   #fda4af;

        --t1: #eef3ff;
        --t2: #7a9fd4;
        --t3: #3a5578;

        --r1: 6px;
        --r2: 10px;
        --r3: 14px;
        --r4: 20px;

        --font: 'Plus Jakarta Sans', sans-serif;
        --mono: 'JetBrains Mono', monospace;

        --shadow-card: 0 1px 3px rgba(0,0,0,0.4), 0 4px 20px rgba(0,0,0,0.25);
        --shadow-glow: 0 0 0 1px var(--border-2), 0 8px 32px rgba(79,140,255,0.08);
    }

    html { scroll-behavior: smooth; }
    body { font-family: var(--font); background: var(--bg); color: var(--t1); -webkit-font-smoothing: antialiased; min-height: 100vh; }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: var(--bg-2); }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--border-3); }

    .dash { padding-top: 70px; min-height: 100vh; }
    .inner { padding: 28px 24px; max-width: 1400px; margin: 0 auto; }

    /* ── Page Header ── */
    .phdr {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 16px;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r4);
        padding: 18px 22px;
        margin-bottom: 24px;
        position: relative; overflow: hidden;
    }
    .phdr::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, var(--violet), var(--blue), transparent);
        opacity: 0.5;
    }
    .phdr-brand h1 { font-size: 18px; font-weight: 800; color: var(--t1); letter-spacing: -0.4px; }
    .phdr-brand p  { font-size: 11.5px; color: var(--t3); font-weight: 500; margin-top: 3px; }

    .phdr-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    /* ── Stats ── */
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }

    .stat-card {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r4);
        padding: 20px;
        position: relative; overflow: hidden;
        transition: border-color .25s, transform .25s, box-shadow .25s;
        cursor: default;
    }
    .stat-card:hover { border-color: var(--border-2); transform: translateY(-2px); box-shadow: var(--shadow-glow); }
    .stat-card .accent-line { position: absolute; top: 0; left: 20px; right: 20px; height: 2px; border-radius: 0 0 4px 4px; }
    .stat-card.blue   .accent-line { background: linear-gradient(90deg, transparent, var(--blue), transparent); }
    .stat-card.amber  .accent-line { background: linear-gradient(90deg, transparent, var(--amber), transparent); }
    .stat-card.emerald .accent-line { background: linear-gradient(90deg, transparent, var(--emerald), transparent); }
    .stat-card.violet .accent-line { background: linear-gradient(90deg, transparent, var(--violet), transparent); }

    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--t3); }
    .stat-icon { width: 40px; height: 40px; border-radius: var(--r2); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .stat-card.blue    .stat-icon { background: var(--blue-dim);    color: var(--blue); }
    .stat-card.amber   .stat-icon { background: var(--amber-dim);   color: var(--amber); }
    .stat-card.emerald .stat-icon { background: var(--emerald-dim); color: var(--emerald); }
    .stat-card.violet  .stat-icon { background: var(--violet-dim);  color: var(--violet); }
    .stat-value { font-size: 26px; font-weight: 800; color: var(--t1); letter-spacing: -0.5px; line-height: 1; margin-bottom: 10px; }
    .stat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .stat-card.blue    .stat-badge { background: var(--blue-dim);    color: var(--blue-text);    border: 1px solid rgba(79,140,255,0.2); }
    .stat-card.amber   .stat-badge { background: var(--amber-dim);   color: var(--amber-text);   border: 1px solid rgba(251,191,36,0.2); }
    .stat-card.emerald .stat-badge { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.2); }
    .stat-card.violet  .stat-badge { background: var(--violet-dim);  color: var(--violet-text);  border: 1px solid rgba(139,124,248,0.2); }

    /* ── Table Card ── */
    .tcard {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r4);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: var(--shadow-card);
        transition: border-color .25s;
    }
    .tcard:hover { border-color: var(--border-2); }

    .tcard-head {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(139,124,248,0.04) 0%, transparent 60%);
    }
    .tcard-title { display: flex; align-items: center; gap: 10px; }
    .tcard-title h3 { font-size: 15px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
    .count-pill {
        padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.2);
    }

    /* Filter bar */
    .filter-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .fsearch { position: relative; }
    .fsearch input {
        height: 36px; width: 200px; padding: 0 10px 0 32px;
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--t1); font-size: 12.5px; font-family: var(--font);
        transition: border-color .2s, box-shadow .2s;
    }
    .fsearch input::placeholder { color: var(--t3); }
    .fsearch input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }
    .fsearch .ico { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--t3); pointer-events: none; }

    .filter-select, .per-page {
        height: 36px; padding: 0 26px 0 10px;
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--blue-text); font-size: 12px; font-family: var(--font);
        cursor: pointer; appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%233a5578' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center;
        transition: border-color .2s;
    }
    .filter-select:focus, .per-page:focus { border-color: var(--blue); outline: none; }

    .btn-filter {
        height: 36px; padding: 0 14px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s, box-shadow .2s;
    }
    .btn-filter:hover { background: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }

    .btn-clear {
        height: 36px; padding: 0 12px;
        background: transparent; color: var(--t3);
        border: 1px solid var(--border-2); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        transition: border-color .2s, color .2s, background .2s;
    }
    .btn-clear:hover { border-color: var(--rose); color: var(--rose-text); background: var(--rose-dim); }

    /* ── Order List ── */
    .order-list { display: flex; flex-direction: column; }

    .order-card {
        border-bottom: 1px solid var(--border);
        transition: background .15s;
    }
    .order-card:last-child { border-bottom: none; }
    .order-card:hover { background: rgba(79,140,255,0.03); }

    .order-row {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 18px 20px;
    }

    /* Left: Icon badge */
    .order-icon {
        width: 42px; height: 42px; border-radius: var(--r2);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
        background: var(--violet-dim); color: var(--violet);
        border: 1px solid rgba(139,124,248,0.2);
    }
    .order-icon.fulfilled { background: var(--emerald-dim); color: var(--emerald); border-color: rgba(52,217,139,0.2); }

    /* Center: Info */
    .order-info { min-width: 0; }
    .order-name {
        font-size: 14px; font-weight: 700; color: var(--t1);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        margin-bottom: 5px;
    }
    .order-meta {
        display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
        font-size: 12px; color: var(--t3);
    }
    .order-meta .ref {
        font-family: var(--mono); font-size: 11px; font-weight: 600;
        color: var(--blue-text); background: var(--blue-dim);
        border: 1px solid rgba(79,140,255,0.18);
        padding: 2px 8px; border-radius: var(--r1);
    }
    .order-meta .sep { width: 3px; height: 3px; border-radius: 50%; background: var(--t3); flex-shrink: 0; }
    .order-desc {
        margin-top: 7px; font-size: 12.5px; color: var(--t2);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .login-preview {
        margin-top: 8px; display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; color: var(--emerald-text);
        background: var(--emerald-dim); border: 1px solid rgba(52,217,139,0.2);
        padding: 3px 10px; border-radius: var(--r1); font-family: var(--mono);
        max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Right: Controls */
    .order-controls {
        display: flex; flex-direction: column; align-items: flex-end; gap: 10px;
        flex-shrink: 0;
    }

    /* Status chips */
    .status-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; animation: pulse 2s infinite; }
    .chip-pending { background: var(--amber-dim); color: var(--amber-text); border: 1px solid rgba(251,191,36,0.25); }
    .chip-pending .dot { background: var(--amber); }
    .chip-success { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.25); }
    .chip-success .dot { background: var(--emerald); animation: none; }
    .chip-failed  { background: var(--rose-dim); color: var(--rose-text); border: 1px solid rgba(244,86,110,0.25); }
    .chip-failed  .dot { background: var(--rose); animation: none; }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* Action buttons */
    .btn-fulfil {
        height: 34px; padding: 0 14px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s, box-shadow .2s;
        white-space: nowrap;
    }
    .btn-fulfil:hover { background: var(--blue); color: #fff; border-color: var(--blue); box-shadow: 0 4px 14px var(--blue-glow); }
    .btn-fulfil.active { background: var(--violet); color: #fff; border-color: var(--violet); }

    /* ── Fulfil Form Panel ── */
    .fulfil-panel {
        display: none;
        padding: 0 20px 20px 20px;
        animation: slideDown .18s ease;
    }
    .fulfil-panel.open { display: block; }

    @keyframes slideDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

    .fulfil-inner {
        background: var(--bg-2);
        border: 1px solid var(--border-2);
        border-left: 3px solid var(--blue);
        border-radius: 0 var(--r3) var(--r3) 0;
        padding: 18px 20px;
    }

    .fulfil-head {
        display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
    }
    .fulfil-head h4 { font-size: 13px; font-weight: 700; color: var(--t1); }
    .fulfil-head .ficon { color: var(--blue); font-size: 15px; }

    .fg { margin-bottom: 14px; }
    .fl { display: block; font-size: 11px; font-weight: 600; color: var(--t2); margin-bottom: 6px; letter-spacing: 0.1px; text-transform: uppercase; letter-spacing: 0.6px; }
    .fi, .fa {
        width: 100%; height: 38px; padding: 0 12px;
        background: var(--bg-3); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--t1); font-size: 13px;
        font-family: var(--font); transition: border-color .2s, box-shadow .2s;
    }
    .fi::placeholder, .fa::placeholder { color: var(--t3); }
    .fi:focus, .fa:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }
    .fa { height: auto; min-height: 76px; padding: 10px 12px; resize: vertical; font-family: var(--mono); font-size: 12.5px; }

    .fulfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .fulfil-actions { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
    .sbtn-submit {
        height: 38px; padding: 0 20px;
        background: var(--emerald); color: #fff; border: none;
        border-radius: var(--r2); font-size: 13px; font-weight: 700; cursor: pointer;
        font-family: var(--font); display: inline-flex; align-items: center; gap: 7px;
        transition: background .2s, box-shadow .2s;
    }
    .sbtn-submit:hover { background: #22c77a; box-shadow: 0 4px 14px rgba(52,217,139,0.3); }
    .sbtn-cancel {
        height: 38px; padding: 0 16px;
        background: transparent; color: var(--t3); border: 1px solid var(--border-2);
        border-radius: var(--r2); font-size: 13px; font-weight: 700; cursor: pointer;
        font-family: var(--font); display: inline-flex; align-items: center; gap: 6px;
        transition: border-color .2s, color .2s, background .2s;
    }
    .sbtn-cancel:hover { border-color: var(--rose); color: var(--rose-text); background: var(--rose-dim); }

    /* ── Empty State ── */
    .empty {
        padding: 64px 24px; text-align: center;
        display: flex; flex-direction: column; align-items: center; gap: 12px;
    }
    .empty-icon-wrap {
        width: 60px; height: 60px; border-radius: var(--r3);
        background: var(--bg-2); border: 1px solid var(--border-2);
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; color: var(--t3); margin-bottom: 4px;
    }
    .empty h3 { font-size: 15px; font-weight: 700; color: var(--t2); }
    .empty p { font-size: 13px; color: var(--t3); max-width: 340px; line-height: 1.6; }

    /* ── Pagination ── */
    .pgbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; padding: 14px 20px;
        border-top: 1px solid var(--border);
    }
    .pg-info { font-size: 12.5px; color: var(--t3); font-weight: 500; }
    .pg-info b { color: var(--blue-text); font-weight: 700; }

    /* Info footer */
    .info-footer {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 20px; background: var(--bg-2);
        border-top: 1px solid var(--border);
    }
    .info-footer-icon {
        width: 32px; height: 32px; border-radius: var(--r1); flex-shrink: 0;
        background: var(--amber-dim); border: 1px solid rgba(251,191,36,0.2);
        display: flex; align-items: center; justify-content: center;
        color: var(--amber); font-size: 15px;
    }
    .info-footer-text { font-size: 12px; color: var(--t3); line-height: 1.6; padding-top: 6px; }

    /* Amount badge */
    .amt { color: var(--amber-text); font-weight: 700; font-family: var(--mono); font-size: 12.5px; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
        .stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .stats { grid-template-columns: 1fr 1fr; }
        .inner { padding: 16px 14px; }
        .order-row { grid-template-columns: auto 1fr; gap: 12px; }
        .order-controls { flex-direction: row; align-items: center; flex-wrap: wrap; }
        .order-meta { gap: 6px; }
        .fulfil-grid { grid-template-columns: 1fr; }
        .filter-bar { width: 100%; }
        .fsearch { flex: 1; }
        .fsearch input { width: 100%; }
        .pg-info { display: none; }
        .phdr { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 420px) {
        .stats { grid-template-columns: 1fr; }
        .stat-card:last-child { grid-column: auto; }
    }

    .hidden { display: none !important; }
</style>

<div class="dash">
    <div class="inner">

        {{-- Page Header --}}
        <div class="phdr">
            <div class="phdr-brand">
                <h1>Logs Orders</h1>
                <p>Review pending orders and fulfil them with login credentials.</p>
            </div>
            <div class="phdr-right">
                <a href="{{ route('admin.logs.products') }}" style="height:38px; padding:0 14px; display:inline-flex; align-items:center; gap:7px; background:var(--violet-dim); color:var(--violet-text); border:1px solid rgba(139,124,248,0.25); border-radius:var(--r2); font-size:12.5px; font-weight:700; text-decoration:none; white-space:nowrap; transition:background .2s, color .2s, box-shadow .2s;" onmouseover="this.style.background='var(--violet)';this.style.color='#fff';" onmouseout="this.style.background='var(--violet-dim)';this.style.color='var(--violet-text)';">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H7m0 0l5-5m-5 5l5 5"/></svg>
                    Manage Products
                </a>
            </div>
        </div>

        {{-- Stats --}}
        <div class="stats">
            @php
                $totalOrders   = $transactions->total();
                $pendingCount  = $transactions->getCollection()->where('status', 'pending')->count();
                $fulfilledCount= $transactions->getCollection()->whereIn('status', ['success','completed'])->count();
                $totalRevenue  = $transactions->getCollection()->sum('amount');
            @endphp
            <div class="stat-card blue">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>
                <div class="stat-value">{{ number_format($totalOrders) }}</div>
                <span class="stat-badge">All time</span>
            </div>

            <div class="stat-card amber">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Pending</div>
                    <div class="stat-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value">{{ number_format($pendingCount) }}</div>
                <span class="stat-badge">Awaiting action</span>
            </div>

            <div class="stat-card emerald">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Fulfilled</div>
                    <div class="stat-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value">{{ number_format($fulfilledCount) }}</div>
                <span class="stat-badge">This page</span>
            </div>

            <div class="stat-card violet">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Page Revenue</div>
                    <div class="stat-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value">₦{{ number_format($totalRevenue, 0) }}</div>
                <span class="stat-badge">Current page</span>
            </div>
        </div>

        {{-- Orders Table Card --}}
        <div class="tcard">
            <div class="tcard-head">
                <div class="tcard-title">
                    <h3>Pending & Completed Orders</h3>
                    <span class="count-pill">{{ $transactions->total() }} total</span>
                </div>
                <div>
                    <form class="filter-bar" method="GET" action="{{ url()->current() }}">
                        <div class="fsearch">
                            <svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" name="search" placeholder="Ref, product, user…" value="{{ request('search') }}">
                        </div>

                        <select class="filter-select" name="status" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                            <option value="success"   {{ request('status') === 'success'   ? 'selected' : '' }}>Fulfilled</option>
                            <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
                        </select>

                        <select class="per-page" name="pp" onchange="this.form.submit()">
                            @foreach([10, 25, 50] as $n)
                                <option value="{{ $n }}" {{ request('pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / pg</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn-filter">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search
                        </button>

                        @if(request('search') || request('status'))
                            <a href="{{ url()->current() }}" class="btn-clear">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="order-list">
                @forelse($transactions as $transaction)
                    @php
                        $response     = is_array($transaction->response) ? $transaction->response : json_decode($transaction->response ?? '[]', true) ?: [];
                        $status       = strtolower($transaction->status ?? 'pending');
                        $isPending    = $status === 'pending';
                        $isFulfilled  = in_array($status, ['success', 'completed', 'successful']);
                    @endphp

                    <div class="order-card" id="order-{{ $transaction->id }}">
                        <div class="order-row">

                            {{-- Icon --}}
                            <div class="order-icon {{ $isFulfilled ? 'fulfilled' : '' }}">
                                @if($isFulfilled)
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="order-info">
                                <div class="order-name">{{ $transaction->service_name ?? $transaction->recipient ?? 'Logs Order' }}</div>
                                <div class="order-meta">
                                    <span class="ref">{{ $transaction->reference }}</span>
                                    @if($transaction->country_name)
                                        <span class="sep"></span>
                                        <span>{{ $transaction->country_name }}</span>
                                    @endif
                                    <span class="sep"></span>
                                    <span class="amt">₦{{ number_format($transaction->amount, 2) }}</span>
                                    <span class="sep"></span>
                                    <span style="font-size:11.5px;">{{ $transaction->created_at?->format('d M Y, H:i') }}</span>
                                </div>
                                @if($transaction->description)
                                    <div class="order-desc">{{ $transaction->description }}</div>
                                @endif
                                @if(!empty($response['login_details']))
                                    <div class="login-preview">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        {{ Str::limit($response['login_details'], 80) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Controls --}}
                            <div class="order-controls">
                                <span class="status-chip {{ $isPending ? 'chip-pending' : ($isFulfilled ? 'chip-success' : 'chip-failed') }}">
                                    <span class="dot"></span>
                                    {{ $isPending ? 'Pending' : ($isFulfilled ? 'Fulfilled' : ucfirst($transaction->status)) }}
                                </span>

                                @if($isPending)
                                    <button
                                        class="btn-fulfil"
                                        id="btn-{{ $transaction->id }}"
                                        onclick="toggleFulfil({{ $transaction->id }})">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Fulfil Order
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Fulfil Form --}}
                        @if($isPending)
                            <div class="fulfil-panel" id="fulfil-{{ $transaction->id }}">
                                <div class="fulfil-inner">
                                    <div class="fulfil-head">
                                        <svg class="ficon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <h4>Fulfil Order — {{ $transaction->service_name ?? $transaction->reference }}</h4>
                                    </div>
                                    <form action="{{ route('admin.logs.fulfill', ['id' => $transaction->id]) }}" method="POST">
                                        @csrf
                                        <div class="fg">
                                            <label class="fl" for="login_details_{{ $transaction->id }}">Login Details</label>
                                            <textarea
                                                class="fa"
                                                id="login_details_{{ $transaction->id }}"
                                                name="login_details"
                                                rows="4"
                                                required
                                                placeholder="Username: john@example.com&#10;Password: ••••••••&#10;IP: 192.168.0.1 | Port: 8080&#10;Platform: Facebook / Netflix / etc."></textarea>
                                        </div>
                                        <div class="fg">
                                            <label class="fl" for="notes_{{ $transaction->id }}">Support Notes (optional)</label>
                                            <textarea
                                                class="fa"
                                                id="notes_{{ $transaction->id }}"
                                                name="notes"
                                                rows="2"
                                                placeholder="Any additional notes for the user…"
                                                style="min-height: 52px; font-family: var(--font);"></textarea>
                                        </div>
                                        <div class="fulfil-actions">
                                            <button type="submit" class="sbtn-submit">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Mark as Fulfilled
                                            </button>
                                            <button type="button" class="sbtn-cancel" onclick="toggleFulfil({{ $transaction->id }})">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon-wrap">
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        </div>
                        <h3>No orders yet</h3>
                        <p>Orders will appear here once users purchase logs access products. Pending orders require manual fulfilment.</p>
                    </div>
                @endforelse
            </div>

            @if($transactions->hasPages())
                <div class="pgbar">
                    <div class="pg-info">
                        Showing <b>{{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}</b> of <b>{{ $transactions->total() }}</b> orders
                    </div>
                    <div>{{ $transactions->appends(request()->only(['search','status','pp']))->links('partials.admin.custom') }}</div>
                </div>
            @endif

            <div class="info-footer">
                <div class="info-footer-icon">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="info-footer-text">
                    Fulfilled orders automatically transition to <strong style="color: var(--emerald-text);">Success</strong> and the user can view their login details directly on their order history page. Pending orders with a pulsing dot require your action.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function toggleFulfil(id) {
    const panel = document.getElementById('fulfil-' + id);
    const btn   = document.getElementById('btn-' + id);
    if (!panel) return;
    const isOpen = panel.classList.contains('open');
    panel.classList.toggle('open', !isOpen);
    if (btn) btn.classList.toggle('active', !isOpen);
}
</script>
@endsection