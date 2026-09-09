@extends('layouts.admin')

@section('title', 'Logs Products')

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

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: var(--bg-2); }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--border-3); }

    .dash { padding-top: 70px; min-height: 100vh; }
    .inner { padding: 20px 16px; max-width: 1400px; margin: 0 auto; }

    /* ── Page Header ── */
    .phdr {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 12px;
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r4);
        padding: 16px 18px;
        margin-bottom: 20px;
        position: relative; overflow: hidden;
    }
    .phdr::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, var(--violet), var(--blue), transparent);
        opacity: 0.5;
    }
    .phdr-brand h1 { font-size: 16px; font-weight: 800; color: var(--t1); letter-spacing: -0.4px; }
    .phdr-brand p  { font-size: 11px; color: var(--t3); font-weight: 500; margin-top: 3px; }
    .phdr-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* ── Section Cards ── */
    .tcard {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r4);
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: var(--shadow-card);
        transition: border-color .25s;
    }
    .tcard:hover { border-color: var(--border-2); }

    .tcard-head {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(139,124,248,0.04) 0%, transparent 60%);
    }
    .tcard-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .tcard-title h3 { font-size: 14px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
    .count-pill {
        padding: 2px 9px; border-radius: 20px; font-size: 10px; font-weight: 700;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.2);
        white-space: nowrap;
    }

    /* ── Filter buttons in header ── */
    .filter-group { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

    /* ── Create Form ── */
    .form-body { padding: 16px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px; }
    .fg { display: flex; flex-direction: column; gap: 5px; }
    .fl {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.6px; color: var(--t2);
    }
    .fi, .fa, .fs {
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--t1); font-family: var(--font);
        transition: border-color .2s, box-shadow .2s;
        font-size: 13px;
    }
    .fi { height: 40px; padding: 0 12px; width: 100%; }
    .fa { padding: 10px 12px; resize: vertical; min-height: 72px; width: 100%; }
    .fs { height: 40px; padding: 0 12px; cursor: pointer; appearance: none; -webkit-appearance: none; width: 100%; }
    .fi::placeholder, .fa::placeholder { color: var(--t3); }
    .fi:focus, .fa:focus, .fs:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }

    .form-actions { display: flex; align-items: center; gap: 8px; padding-top: 4px; flex-wrap: wrap; }

    /* ── Buttons ── */
    .btn-primary {
        height: 38px; padding: 0 16px;
        background: var(--emerald); color: #fff; border: none;
        border-radius: var(--r2); font-size: 12px; font-weight: 700; cursor: pointer;
        font-family: var(--font); display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, box-shadow .2s; white-space: nowrap; flex-shrink: 0;
    }
    .btn-primary:hover { background: #22c77a; box-shadow: 0 4px 14px rgba(52,217,139,0.3); }

    .btn-secondary {
        height: 34px; padding: 0 12px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        transition: background .2s, color .2s, box-shadow .2s; white-space: nowrap; flex-shrink: 0;
    }
    .btn-secondary:hover { background: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }

    .btn-danger {
        height: 32px; padding: 0 12px;
        background: var(--rose-dim); color: var(--rose-text);
        border: 1px solid rgba(244,86,110,0.25); border-radius: var(--r2);
        font-size: 11px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .2s, color .2s; white-space: nowrap;
    }
    .btn-danger:hover { background: var(--rose); color: #fff; }

    .btn-enable {
        height: 32px; padding: 0 12px;
        background: var(--emerald-dim); color: var(--emerald-text);
        border: 1px solid rgba(52,217,139,0.25); border-radius: var(--r2);
        font-size: 11px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .2s, color .2s; white-space: nowrap;
    }
    .btn-enable:hover { background: var(--emerald); color: #fff; }

    .btn-edit {
        height: 32px; padding: 0 12px;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.25); border-radius: var(--r2);
        font-size: 11px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .2s, color .2s; white-space: nowrap;
    }
    .btn-edit:hover { background: var(--violet); color: #fff; }

    .btn-ghost {
        height: 32px; padding: 0 12px;
        background: transparent; color: var(--t3);
        border: 1px solid var(--border-2); border-radius: var(--r2);
        font-size: 11px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 5px;
        transition: border-color .2s, color .2s, background .2s; white-space: nowrap;
    }
    .btn-ghost:hover { border-color: var(--t2); color: var(--t1); }

    /* ── Mode toggle ── */
    .mode-toggle {
        display: flex; gap: 6px; align-items: center;
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r3); padding: 4px; margin-bottom: 16px;
        width: fit-content;
    }
    .mode-btn {
        height: 32px; padding: 0 14px;
        border: none; border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s, box-shadow .15s;
        background: transparent; color: var(--t3);
    }
    .mode-btn.active { background: var(--emerald); color: #fff; box-shadow: 0 2px 10px rgba(52,217,139,0.3); }
    .mode-btn:not(.active):hover { color: var(--t1); background: var(--bg-3); }

    /* ── Product List ── */
    .product-list { display: flex; flex-direction: column; }

    .product-card {
        border-bottom: 1px solid var(--border);
        transition: background .15s;
    }
    .product-card:last-child { border-bottom: none; }
    .product-card:hover { background: rgba(79,140,255,0.03); }
    .product-card.inactive { opacity: 0.6; }

    /* ── Product Row — mobile-first ── */
    .product-row {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 14px 14px 12px;
        cursor: pointer;
    }

    .product-row-top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .product-icon {
        width: 40px; height: 40px; border-radius: var(--r2);
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 800; flex-shrink: 0;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.2);
        font-family: var(--mono);
    }

    .product-info { flex: 1; min-width: 0; }
    .product-name {
        font-size: 13px; font-weight: 700; color: var(--t1);
        margin-bottom: 5px;
        display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    }
    .product-meta {
        display: flex; align-items: center; flex-wrap: wrap; gap: 7px;
        font-size: 11px; color: var(--t3); margin-bottom: 4px;
    }
    .product-meta .code {
        font-family: var(--mono); font-size: 10px; font-weight: 600;
        color: var(--blue-text); background: var(--blue-dim);
        border: 1px solid rgba(79,140,255,0.18);
        padding: 2px 7px; border-radius: var(--r1);
    }
    .product-meta .sep { width: 3px; height: 3px; border-radius: 50%; background: var(--t3); flex-shrink: 0; }
    .product-price {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 14px; font-weight: 800; color: var(--amber-text);
        font-family: var(--mono); margin-top: 2px;
    }
    .product-desc {
        margin-top: 4px; font-size: 11px; color: var(--t3); line-height: 1.5;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    /* ── Product footer row (status + actions) ── */
    .product-footer {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px; flex-wrap: wrap;
        padding: 0 14px 14px;
    }

    .status-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
        flex-shrink: 0;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .chip-on  { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.25); }
    .chip-on  .dot { background: var(--emerald); }
    .chip-off { background: var(--rose-dim);    color: var(--rose-text);    border: 1px solid rgba(244,86,110,0.25); }
    .chip-off .dot { background: var(--rose); }
    .chip-mode-auto { background: var(--blue-dim); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.25); }
    .chip-mode-manual { background: var(--amber-dim); color: var(--amber-text); border: 1px solid rgba(251,191,36,0.25); }

    .product-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

    /* ── Edit Panel ── */
    .edit-panel {
        display: none;
        padding: 0 14px 14px;
        animation: slideDown .18s ease;
    }
    .edit-panel.open { display: block; }

    @keyframes slideDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

    .edit-inner {
        background: var(--bg-2);
        border: 1px solid var(--border-2);
        border-left: 3px solid var(--violet);
        border-radius: 0 var(--r3) var(--r3) 0;
        padding: 14px 16px;
    }
    .edit-head {
        display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
    }
    .edit-head h4 { font-size: 13px; font-weight: 700; color: var(--t1); }
    .edit-head .eicon { color: var(--violet); font-size: 15px; }

    /* ── Log Details expandable ── */
    .details-panel {
        display: none;
        background: var(--bg-2);
        border: 1px solid var(--border-2);
        border-radius: var(--r2);
        margin: 0 14px 14px;
        padding: 16px;
        animation: slideDown .18s ease;
    }
    .details-panel.open { display: block; }

    .details-panel-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .details-panel-header-text h4 {
        color: var(--blue-text); font-size: 14px; margin: 0; font-weight: 700;
    }
    .details-panel-header-text p {
        margin: 4px 0 0; font-size: 11px; color: var(--t3); line-height: 1.5;
    }

    /* ── Log Detail Entry ── */
    .log-entry {
        background: var(--bg-3);
        border: 1px solid var(--border);
        border-radius: var(--r2);
        padding: 12px;
        position: relative;
        margin-bottom: 10px;
    }
    .log-entry:last-child { margin-bottom: 0; }

    .log-entry-status {
        position: absolute; top: 10px; right: 10px;
        font-size: 10px; color: #93b8ff; background: var(--bg-2);
        padding: 3px 7px; border-radius: var(--r1);
    }
    .log-entry-status.sold { color: var(--emerald-text); }

    .log-entry-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 10px;
    }

    /* ── Empty State ── */
    .empty {
        padding: 48px 16px; text-align: center;
        display: flex; flex-direction: column; align-items: center; gap: 10px;
    }
    .empty-icon-wrap {
        width: 56px; height: 56px; border-radius: var(--r3);
        background: var(--bg-2); border: 1px solid var(--border-2);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: var(--t3); margin-bottom: 4px;
    }
    .empty h3 { font-size: 14px; font-weight: 700; color: var(--t2); }
    .empty p  { font-size: 12px; color: var(--t3); max-width: 300px; line-height: 1.6; }

    /* ── Info Footer ── */
    .info-footer {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 12px 16px; background: var(--bg-2);
        border-top: 1px solid var(--border);
    }
    .info-footer-icon {
        width: 30px; height: 30px; border-radius: var(--r1); flex-shrink: 0;
        background: var(--amber-dim); border: 1px solid rgba(251,191,36,0.2);
        display: flex; align-items: center; justify-content: center;
        color: var(--amber); font-size: 14px;
    }
    .info-footer-text { font-size: 11.5px; color: var(--t3); line-height: 1.6; padding-top: 4px; }

    /* ── Stats Row ── */
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
    .stat-card {
        background: var(--bg-3); border: 1px solid var(--border);
        border-radius: var(--r3); padding: 14px 14px 16px;
        position: relative; overflow: hidden;
        transition: border-color .25s, transform .25s, box-shadow .25s;
    }
    .stat-card:hover { border-color: var(--border-2); transform: translateY(-2px); box-shadow: var(--shadow-glow); }
    .stat-card .accent-line { position: absolute; top: 0; left: 16px; right: 16px; height: 2px; border-radius: 0 0 4px 4px; }
    .stat-card.violet .accent-line { background: linear-gradient(90deg, transparent, var(--violet), transparent); }
    .stat-card.emerald .accent-line { background: linear-gradient(90deg, transparent, var(--emerald), transparent); }
    .stat-card.amber .accent-line { background: linear-gradient(90deg, transparent, var(--amber), transparent); }
    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
    .stat-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--t3); }
    .stat-icon { width: 34px; height: 34px; border-radius: var(--r2); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .stat-card.violet .stat-icon { background: var(--violet-dim); color: var(--violet); }
    .stat-card.emerald .stat-icon { background: var(--emerald-dim); color: var(--emerald); }
    .stat-card.amber  .stat-icon { background: var(--amber-dim);  color: var(--amber); }
    .stat-value { font-size: 22px; font-weight: 800; color: var(--t1); letter-spacing: -0.5px; line-height: 1; margin-bottom: 8px; }
    .stat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; }
    .stat-card.violet .stat-badge { background: var(--violet-dim); color: var(--violet-text); border: 1px solid rgba(139,124,248,0.2); }
    .stat-card.emerald .stat-badge { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.2); }
    .stat-card.amber  .stat-badge { background: var(--amber-dim);  color: var(--amber-text);  border: 1px solid rgba(251,191,36,0.2); }

    .hidden { display: none !important; }

    /* ── Divider ── */
    .form-divider { border: 0; border-top: 1px solid var(--border-2); margin: 14px 0; }

    /* ── Section label ── */
    .section-label {
        display: flex; align-items: center; gap: 8px; margin-bottom: 10px;
    }
    .section-label h4 { font-size: 13px; font-weight: 700; color: var(--t1); margin: 0; }
    .section-label span { font-size: 11px; color: var(--t3); }

    /* ── Responsive breakpoints ── */
    /* Tablet */
    @media (max-width: 900px) {
        .stats { grid-template-columns: 1fr 1fr; }
        .form-grid-4 { grid-template-columns: 1fr 1fr; }
    }

    /* Mobile */
    @media (max-width: 600px) {
        .dash { padding-top: 60px; }
        .inner { padding: 12px 10px; }

        .stats { grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
        .stat-card { padding: 12px 12px 14px; }
        .stat-value { font-size: 18px; }
        .stat-icon { width: 28px; height: 28px; font-size: 13px; }

        .phdr { flex-direction: column; align-items: flex-start; padding: 12px 14px; gap: 10px; }
        .phdr-right { width: 100%; }
        .phdr-right .btn-secondary { width: 100%; justify-content: center; }

        .tcard-head { flex-direction: column; align-items: flex-start; padding: 12px 12px; }
        .filter-group { width: 100%; overflow-x: auto; padding-bottom: 2px; flex-wrap: nowrap; }
        .filter-group::-webkit-scrollbar { height: 0; }

        .mode-toggle { width: 100%; }
        .mode-btn { flex: 1; justify-content: center; }

        .form-body { padding: 12px; }
        .form-grid-2,
        .form-grid-4 { grid-template-columns: 1fr; gap: 10px; }

        /* Product row stacking */
        .product-row { padding: 12px 12px 8px; gap: 10px; }
        .product-footer { padding: 0 12px 12px; gap: 6px; }
        .product-actions { width: 100%; justify-content: flex-end; }

        .edit-panel { padding: 0 10px 10px; }
        .edit-inner { padding: 12px; }

        .details-panel { margin: 0 10px 10px; padding: 12px; }
        .log-entry-grid { grid-template-columns: 1fr; }

        .form-actions { flex-direction: column; align-items: stretch; }
        .form-actions .btn-primary,
        .form-actions .btn-ghost { width: 100%; justify-content: center; }

        .info-footer { padding: 10px 12px; }
    }

    /* Very small screens */
    @media (max-width: 380px) {
        .stats { grid-template-columns: 1fr; }
        .stat-value { font-size: 20px; }
    }
</style>

<div class="dash">
    <div class="inner">

        {{-- Page Header --}}
        <div class="phdr">
            <div class="phdr-brand">
                <h1>Logs Product Catalog</h1>
                <p>Manage login and IP access products available to customers.</p>
            </div>
            <div class="phdr-right">
                <a href="{{ route('admin.logs.orders') }}" class="btn-secondary">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    View Orders
                </a>
            </div>
        </div>

        {{-- Stats --}}
        @php
            $totalProducts  = $products->count();
            $activeProducts = $products->where('is_active', true)->count();
            $avgPrice       = $products->avg('client_price') ?? 0;
        @endphp
        <div class="stats">
            <div class="stat-card violet">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="stat-value">{{ number_format($totalProducts) }}</div>
                <span class="stat-badge">All products</span>
            </div>

            <div class="stat-card emerald">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Active</div>
                    <div class="stat-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value">{{ number_format($activeProducts) }}</div>
                <span class="stat-badge">Live now</span>
            </div>

            <div class="stat-card amber">
                <div class="accent-line"></div>
                <div class="stat-top">
                    <div class="stat-label">Avg. Price</div>
                    <div class="stat-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value">₦{{ number_format($avgPrice, 0) }}</div>
                <span class="stat-badge">Per product</span>
            </div>
        </div>

        {{-- Create Product --}}
        <div class="tcard">
            <div class="tcard-head">
                <div class="tcard-title">
                    <h3>Add New Logs Product</h3>
                    <span class="count-pill">Create / Update</span>
                </div>
            </div>
            <div class="form-body">
                {{-- Mode Toggle --}}
                <div class="mode-toggle">
                    <button type="button" id="autoBtn" class="mode-btn active">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Automatic
                    </button>
                    <button type="button" id="manualBtn" class="mode-btn">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Manual
                    </button>
                </div>

                {{-- Automatic Form --}}
                <form id="autoForm" action="{{ route('admin.logs.create') }}" method="POST">
                    @csrf
                    <input type="hidden" name="mode" value="automatic">
                    <div class="form-grid-4">
                        <div class="fg">
                            <label class="fl" for="name">Product Name</label>
                            <input class="fi" type="text" id="name" name="name" required maxlength="255" placeholder="Facebook – UK login" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="client_price">Price (NGN)</label>
                            <input class="fi" type="number" id="client_price" name="client_price" required step="0.01" min="0" placeholder="1500" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="country">Country (optional)</label>
                            <input class="fi" type="text" id="country" name="country" maxlength="80" placeholder="United Kingdom" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="country_code">Country Code</label>
                            <input class="fi" type="text" id="country_code" name="country_code" maxlength="2" placeholder="GB" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="image_url">Product Image URL (optional)</label>
                            <input class="fi" type="url" id="image_url" name="image_url" maxlength="1024" placeholder="https://example.com/logo.png" />
                        </div>
                    </div>
                    <div class="fg" style="margin-bottom: 14px;">
                        <label class="fl" for="description">Description (optional)</label>
                        <textarea class="fa" id="description" name="description" rows="2" placeholder="Short product details or login type…"></textarea>
                    </div>
                    <hr class="form-divider">
                    <div class="section-label">
                        <h4>Log Details <span style="color:var(--rose);">*</span></h4>
                        <span>(at least 1 required)</span>
                    </div>
                    <div id="logDetailsList"></div>
                    <button type="button" id="addLogBtn" class="btn-edit" style="margin-top:8px; height:34px;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Log Detail
                    </button>
                    <input type="hidden" name="log_details" id="logDetailsInput">
                    <div id="logDetailsError" style="color:var(--rose);font-size:12px;margin-top:6px;display:none;">At least one log detail is required for automatic logs.</div>
                    <div class="form-actions" style="margin-top:14px;">
                        <button type="submit" class="btn-primary">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Product
                        </button>
                    </div>
                </form>

                {{-- Manual Form --}}
                <form id="manualForm" action="{{ route('admin.logs.create') }}" method="POST" style="display:none;">
                    @csrf
                    <input type="hidden" name="mode" value="manual">
                    <div class="form-grid-4">
                        <div class="fg">
                            <label class="fl" for="name_manual">Product Name</label>
                            <input class="fi" type="text" id="name_manual" name="name" required maxlength="255" placeholder="Facebook – UK login" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="client_price_manual">Price (NGN)</label>
                            <input class="fi" type="number" id="client_price_manual" name="client_price" required step="0.01" min="0" placeholder="1500" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="country_manual">Country (optional)</label>
                            <input class="fi" type="text" id="country_manual" name="country" maxlength="80" placeholder="United Kingdom" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="country_code_manual">Country Code</label>
                            <input class="fi" type="text" id="country_code_manual" name="country_code" maxlength="2" placeholder="GB" />
                        </div>
                        <div class="fg">
                            <label class="fl" for="image_url_manual">Product Image URL (optional)</label>
                            <input class="fi" type="url" id="image_url_manual" name="image_url" maxlength="1024" placeholder="https://example.com/logo.png" />
                        </div>
                    </div>
                    <div class="fg" style="margin-bottom: 14px;">
                        <label class="fl" for="description_manual">Description (optional)</label>
                        <textarea class="fa" id="description_manual" name="description" rows="2" placeholder="Short product details or login type…"></textarea>
                    </div>
                    <div class="form-actions" style="margin-top:14px;">
                        <button type="submit" class="btn-primary">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Product List --}}
        <div class="tcard">
            <div class="tcard-head">
                <div class="tcard-title">
                    <h3>Existing Logs Products</h3>
                    <span class="count-pill">{{ $products->count() }} items</span>
                </div>
                <div class="filter-group">
                    <button type="button" class="btn-secondary" id="filterAll">All</button>
                    <button type="button" class="btn-secondary" id="filterAutomatic">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Automatic
                    </button>
                    <button type="button" class="btn-secondary" id="filterManual">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Manual
                    </button>
                </div>
            </div>

            <div class="product-list" id="productList">
                @forelse($products as $product)
                    <div class="product-card {{ !$product->is_active ? 'inactive' : '' }}" id="pcard-{{ $product->id }}" data-mode="{{ $product->mode }}">

                        {{-- Top clickable row --}}
                        <div class="product-row" onclick="toggleLogDetails({{ $product->id }}, '{{ $product->mode }}')">
                            <div class="product-row-top">
                                <div class="product-icon">
                                    @if(!empty($product->image_url))
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;" onerror="this.onerror=null; this.src='/assets/empty.svg'" />
                                    @else
                                        L
                                    @endif
                                </div>
                                <div class="product-info">
                                    <div class="product-name">
                                        {{ $product->name }}
                                        @if($product->mode === 'automatic')
                                            <span class="status-chip chip-mode-auto">⚡ Auto</span>
                                        @elseif($product->mode === 'manual')
                                            <span class="status-chip chip-mode-manual">✍️ Manual</span>
                                        @endif
                                    </div>
                                    <div class="product-meta">
                                        <span class="code">{{ strtoupper($product->product_code) }}</span>
                                        <span class="sep"></span>
                                        @if($product->country)
                                            <span>
                                                @if($product->country_code)<span>{{ strtoupper($product->country_code) }}</span>@endif
                                                {{ $product->country }}
                                            </span>
                                        @else
                                            <span>Global</span>
                                        @endif
                                    </div>
                                    <div class="product-price">₦{{ number_format($product->client_price, 2) }}</div>
                                    @if($product->description)
                                        <div class="product-desc">{{ $product->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Footer: status + action buttons --}}
                        <div class="product-footer">
                            <span class="status-chip {{ $product->is_active ? 'chip-on' : 'chip-off' }}">
                                <span class="dot"></span>
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="product-actions">
                                <button class="btn-edit" onclick="toggleEdit({{ $product->id }})">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </button>
                                <form action="{{ route('admin.products.toggle', ['id' => $product->id]) }}" method="POST" style="margin:0;">
                                    @csrf
                                    @if($product->is_active)
                                        <button type="submit" class="btn-danger">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Disable
                                        </button>
                                    @else
                                        <button type="submit" class="btn-enable">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Enable
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>

                        {{-- Inline Edit Panel --}}
                        <div class="edit-panel" id="edit-{{ $product->id }}">
                            <div class="edit-inner">
                                <div class="edit-head">
                                    <svg class="eicon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <h4>Edit — {{ $product->name }}</h4>
                                </div>
                                <form action="{{ route('admin.logs.create') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="mode" value="{{ $product->mode ?? 'manual' }}">
                                    <div class="form-grid-4" style="margin-bottom:12px;">
                                        <div class="fg">
                                            <label class="fl">Product Name</label>
                                            <input class="fi" type="text" name="name" value="{{ $product->name }}" required maxlength="255" />
                                        </div>
                                        <div class="fg">
                                            <label class="fl">Price (NGN)</label>
                                            <input class="fi" type="number" name="client_price" value="{{ $product->client_price }}" required step="0.01" min="0" />
                                        </div>
                                        <div class="fg">
                                            <label class="fl">Country</label>
                                            <input class="fi" type="text" name="country" value="{{ $product->country }}" maxlength="80" />
                                        </div>
                                        <div class="fg">
                                            <label class="fl">Country Code</label>
                                            <input class="fi" type="text" name="country_code" value="{{ $product->country_code }}" maxlength="2" />
                                        </div>
                                        <div class="fg">
                                            <label class="fl">Product Image URL</label>
                                            <input class="fi" type="url" name="image_url" value="{{ $product->image_url ?? '' }}" maxlength="1024" placeholder="https://example.com/logo.png" />
                                        </div>
                                    </div>
                                    <div class="fg" style="margin-bottom:12px;">
                                        <label class="fl">Description</label>
                                        <textarea class="fa" name="description" rows="2">{{ $product->description }}</textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn-primary">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Save Changes
                                        </button>
                                        <button type="button" class="btn-ghost" onclick="toggleEdit({{ $product->id }})">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Automatic Log Details Expandable --}}
                        @if($product->mode === 'automatic')
                        <div class="details-panel" id="details-{{ $product->id }}">
                            <div class="details-panel-header">
                                <div class="details-panel-header-text">
                                    <h4>📝 Log Credentials</h4>
                                    <p>Sold credentials are locked and retained for audit. Add new details below to replenish stock.</p>
                                </div>
                                <button type="button" class="btn-edit" onclick="addLogDetailRow({{ $product->id }})" style="flex-shrink:0;">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Credential
                                </button>
                            </div>
                            <form action="{{ route('admin.logs.updateDetails', $product->id) }}" method="POST">
                                @csrf
                                @php
                                    $details = json_decode($product->log_details ?? '[]', true);
                                @endphp
                                <div id="logDetailsFields-{{ $product->id }}" data-next-index="{{ count($details) }}">
                                    @forelse($details as $idx => $detail)
                                        @php $sold = !empty($detail['sold']); @endphp
                                        <div class="log-entry" data-index="{{ $idx }}">
                                            <div class="log-entry-status {{ $sold ? 'sold' : '' }}">{{ $sold ? 'Sold' : 'Available' }}</div>
                                            <div class="log-entry-grid">
                                                <div class="fg">
                                                    <label class="fl" style="color:var(--blue-text);">👤 Username/Email</label>
                                                    <input class="fi" type="text" name="log_details[{{ $idx }}][username]" value="{{ $detail['username'] ?? '' }}" placeholder="user@example.com" {{ $sold ? 'readonly' : '' }} />
                                                </div>
                                                <div class="fg">
                                                    <label class="fl" style="color:var(--rose-text);">🔐 Password</label>
                                                    <input class="fi" type="text" name="log_details[{{ $idx }}][password]" value="{{ $detail['password'] ?? '' }}" placeholder="Password" {{ $sold ? 'readonly' : '' }} />
                                                </div>
                                            </div>
                                            <div class="fg" style="margin-bottom: {{ $sold ? '0' : '10px' }};">
                                                <label class="fl" style="color:var(--amber-text);">ℹ️ Additional Data</label>
                                                <textarea class="fa" style="min-height:56px;" name="log_details[{{ $idx }}][extra]" placeholder="Security questions, PIN codes, account notes, etc." {{ $sold ? 'readonly' : '' }}>{{ $detail['extra'] ?? '' }}</textarea>
                                            </div>
                                            <input type="hidden" name="log_details[{{ $idx }}][sold]" value="{{ $sold ? '1' : '0' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_at]" value="{{ $detail['sold_at'] ?? '' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_transaction_id]" value="{{ $detail['sold_transaction_id'] ?? '' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_to_user_id]" value="{{ $detail['sold_to_user_id'] ?? '' }}">
                                            @if(!$sold)
                                                <button type="button" class="btn-danger" onclick="removeLogDetailRow({{ $product->id }}, {{ $idx }})">
                                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    @empty
                                        <div style="padding:16px;text-align:center;color:var(--t3);font-size:12px;">
                                            No log details added yet. Use the button above to add new credentials.
                                        </div>
                                    @endforelse
                                </div>
                                <div class="form-actions" style="margin-top:12px;">
                                    <button type="submit" class="btn-primary">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Update All Credentials
                                    </button>
                                    <button type="button" class="btn-ghost" onclick="toggleLogDetails({{ $product->id }}, '{{ $product->mode }}')">
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Close
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif

                    </div>
                @empty
                    <div class="empty">
                        <div class="empty-icon-wrap">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3>No logs products yet</h3>
                        <p>Create a product above and it will appear here. Products are shown to users when browsing available logs access packages.</p>
                    </div>
                @endforelse
            </div>

            <div class="info-footer">
                <div class="info-footer-icon">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="info-footer-text">
                    Each product supports an optional country and description. Use the two-letter country code (e.g. <strong style="color:var(--t2);">GB</strong>, <strong style="color:var(--t2);">US</strong>) to display the correct region label. Disabled products are hidden from users but not deleted.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// --- Product Filter ---
document.addEventListener('DOMContentLoaded', function() {
    const filterAll = document.getElementById('filterAll');
    const filterAutomatic = document.getElementById('filterAutomatic');
    const filterManual = document.getElementById('filterManual');
    const productList = document.getElementById('productList');

    function setFilter(mode) {
        const cards = productList.querySelectorAll('.product-card');
        cards.forEach(card => {
            card.style.display = (mode === 'all' || card.getAttribute('data-mode') === mode) ? '' : 'none';
        });
    }

    filterAll.onclick = function() { setFilter('all'); };
    filterAutomatic.onclick = function() { setFilter('automatic'); };
    filterManual.onclick = function() { setFilter('manual'); };

    // --- Mode Toggle ---
    const autoBtn = document.getElementById('autoBtn');
    const manualBtn = document.getElementById('manualBtn');
    const autoForm = document.getElementById('autoForm');
    const manualForm = document.getElementById('manualForm');

    autoBtn.onclick = function() {
        autoBtn.classList.add('active');
        manualBtn.classList.remove('active');
        autoForm.style.display = '';
        manualForm.style.display = 'none';
    };
    manualBtn.onclick = function() {
        manualBtn.classList.add('active');
        autoBtn.classList.remove('active');
        autoForm.style.display = 'none';
        manualForm.style.display = '';
    };

    // --- Dynamic Log Details (Create form) ---
    let logDetails = [];
    const logDetailsList = document.getElementById('logDetailsList');
    const logDetailsInput = document.getElementById('logDetailsInput');
    const addLogBtn = document.getElementById('addLogBtn');

    function renderLogDetails() {
        logDetailsList.innerHTML = '';
        logDetails.forEach((detail, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'log-entry';
            wrap.style.marginBottom = '10px';

            wrap.innerHTML = `
                <div class="log-entry-status">Available</div>
                <div class="log-entry-grid">
                    <div class="fg">
                        <label class="fl" style="color:var(--blue-text);">👤 Username/Email</label>
                        <input class="fi" type="text" placeholder="user@example.com" value="${detail.username || ''}" data-idx="${idx}" data-field="username" />
                    </div>
                    <div class="fg">
                        <label class="fl" style="color:var(--rose-text);">🔐 Password</label>
                        <input class="fi" type="text" placeholder="Password" value="${detail.password || ''}" data-idx="${idx}" data-field="password" />
                    </div>
                </div>
                <div class="fg" style="margin-bottom:10px;">
                    <label class="fl" style="color:var(--amber-text);">ℹ️ Additional Data</label>
                    <textarea class="fa" style="min-height:56px;" placeholder="Any extra info" data-idx="${idx}" data-field="extra">${detail.extra || ''}</textarea>
                </div>
                <button type="button" class="btn-danger" data-del="${idx}">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
                    Delete
                </button>
            `;

            wrap.querySelectorAll('[data-field]').forEach(el => {
                el.addEventListener('input', function() {
                    logDetails[this.dataset.idx][this.dataset.field] = this.value;
                    logDetailsInput.value = JSON.stringify(logDetails);
                });
            });

            wrap.querySelector('[data-del]').addEventListener('click', function() {
                logDetails.splice(parseInt(this.dataset.del), 1);
                renderLogDetails();
            });

            logDetailsList.appendChild(wrap);
        });
        logDetailsInput.value = JSON.stringify(logDetails);
    }

    addLogBtn.onclick = function(e) {
        e.preventDefault();
        logDetails.push({ username: '', password: '', extra: '' });
        renderLogDetails();
    };

    autoForm.onsubmit = function(e) {
        logDetailsInput.value = JSON.stringify(logDetails);
        if (logDetails.length === 0) {
            document.getElementById('logDetailsError').style.display = 'block';
            e.preventDefault();
            return false;
        } else {
            document.getElementById('logDetailsError').style.display = 'none';
        }
    };

    renderLogDetails();
});

// --- Toggle Edit Panel ---
function toggleEdit(id) {
    const panel = document.getElementById('edit-' + id);
    if (!panel) return;
    panel.classList.toggle('open');
}

// --- Toggle Log Details Panel ---
function toggleLogDetails(id, mode) {
    if (mode !== 'automatic') return;
    const panel = document.getElementById('details-' + id);
    if (!panel) return;
    panel.classList.toggle('open');
}

// --- Add new credential row in existing product ---
function addLogDetailRow(productId) {
    const container = document.getElementById('logDetailsFields-' + productId);
    if (!container) return;
    const nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
    const div = document.createElement('div');
    div.className = 'log-entry';
    div.dataset.index = nextIndex;
    div.innerHTML = `
        <div class="log-entry-status">Available</div>
        <div class="log-entry-grid">
            <div class="fg">
                <label class="fl" style="color:var(--blue-text);">👤 Username/Email</label>
                <input class="fi" type="text" name="log_details[${nextIndex}][username]" placeholder="user@example.com" />
            </div>
            <div class="fg">
                <label class="fl" style="color:var(--rose-text);">🔐 Password</label>
                <input class="fi" type="text" name="log_details[${nextIndex}][password]" placeholder="Password" />
            </div>
        </div>
        <div class="fg" style="margin-bottom:10px;">
            <label class="fl" style="color:var(--amber-text);">ℹ️ Additional Data</label>
            <textarea class="fa" style="min-height:56px;" name="log_details[${nextIndex}][extra]" placeholder="Security questions, PIN codes, account notes, etc."></textarea>
        </div>
        <input type="hidden" name="log_details[${nextIndex}][sold]" value="0">
        <input type="hidden" name="log_details[${nextIndex}][sold_at]" value="">
        <input type="hidden" name="log_details[${nextIndex}][sold_transaction_id]" value="">
        <input type="hidden" name="log_details[${nextIndex}][sold_to_user_id]" value="">
        <button type="button" class="btn-danger" onclick="removeLogDetailRow(${productId}, ${nextIndex})">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
            Delete
        </button>
    `;
    container.appendChild(div);
    container.dataset.nextIndex = (nextIndex + 1).toString();
}

// --- Remove credential row ---
function removeLogDetailRow(productId, index) {
    const container = document.getElementById('logDetailsFields-' + productId);
    if (!container) return;
    const row = container.querySelector('[data-index="' + index + '"]');
    if (row) row.remove();
}
</script>
@endsection