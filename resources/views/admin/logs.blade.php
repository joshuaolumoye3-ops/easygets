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

    /* ── Section Cards ── */
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

    /* ── Create Form ── */
    .form-body { padding: 22px 20px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .fg { display: flex; flex-direction: column; gap: 6px; }
    .fl {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.6px; color: var(--t2);
    }
    .fi, .fa, .fs {
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--t1); font-family: var(--font);
        transition: border-color .2s, box-shadow .2s;
        font-size: 13px;
    }
    .fi { height: 40px; padding: 0 12px; }
    .fa { padding: 10px 12px; resize: vertical; min-height: 80px; }
    .fs { height: 40px; padding: 0 12px; cursor: pointer; appearance: none; -webkit-appearance: none; }
    .fi::placeholder, .fa::placeholder { color: var(--t3); }
    .fi:focus, .fa:focus, .fs:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }

    .form-actions { display: flex; align-items: center; gap: 10px; padding-top: 4px; }

    .btn-primary {
        height: 40px; padding: 0 20px;
        background: var(--emerald); color: #fff; border: none;
        border-radius: var(--r2); font-size: 13px; font-weight: 700; cursor: pointer;
        font-family: var(--font); display: inline-flex; align-items: center; gap: 7px;
        transition: background .2s, box-shadow .2s;
    }
    .btn-primary:hover { background: #22c77a; box-shadow: 0 4px 14px rgba(52,217,139,0.3); }

    .btn-secondary {
        height: 40px; padding: 0 16px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 13px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        transition: background .2s, color .2s, box-shadow .2s;
    }
    .btn-secondary:hover { background: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }

    .btn-danger {
        height: 34px; padding: 0 14px;
        background: var(--rose-dim); color: var(--rose-text);
        border: 1px solid rgba(244,86,110,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s;
    }
    .btn-danger:hover { background: var(--rose); color: #fff; }

    .btn-enable {
        height: 34px; padding: 0 14px;
        background: var(--emerald-dim); color: var(--emerald-text);
        border: 1px solid rgba(52,217,139,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s;
    }
    .btn-enable:hover { background: var(--emerald); color: #fff; }

    .btn-edit {
        height: 34px; padding: 0 14px;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.25); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .2s, color .2s;
    }
    .btn-edit:hover { background: var(--violet); color: #fff; }

    .btn-ghost {
        height: 34px; padding: 0 14px;
        background: transparent; color: var(--t3);
        border: 1px solid var(--border-2); border-radius: var(--r2);
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font);
        display: inline-flex; align-items: center; gap: 6px;
        transition: border-color .2s, color .2s, background .2s;
    }
    .btn-ghost:hover { border-color: var(--t2); color: var(--t1); }

    /* ── Product List ── */
    .product-list { display: flex; flex-direction: column; }

    .product-card {
        border-bottom: 1px solid var(--border);
        transition: background .15s;
    }
    .product-card:last-child { border-bottom: none; }
    .product-card:hover { background: rgba(79,140,255,0.03); }
    .product-card.inactive { opacity: 0.6; }

    .product-row {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 16px;
        align-items: center;
        padding: 18px 20px;
    }

    .product-icon {
        width: 42px; height: 42px; border-radius: var(--r2);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 800; flex-shrink: 0;
        background: var(--violet-dim); color: var(--violet-text);
        border: 1px solid rgba(139,124,248,0.2);
        font-family: var(--mono);
    }

    .product-info { min-width: 0; }
    .product-name {
        font-size: 14px; font-weight: 700; color: var(--t1);
        margin-bottom: 5px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .product-meta {
        display: flex; align-items: center; flex-wrap: wrap; gap: 10px;
        font-size: 12px; color: var(--t3);
    }
    .product-meta .code {
        font-family: var(--mono); font-size: 11px; font-weight: 600;
        color: var(--blue-text); background: var(--blue-dim);
        border: 1px solid rgba(79,140,255,0.18);
        padding: 2px 8px; border-radius: var(--r1);
    }
    .product-meta .sep { width: 3px; height: 3px; border-radius: 50%; background: var(--t3); flex-shrink: 0; }
    .product-price {
        margin-top: 5px; display: inline-flex; align-items: center; gap: 5px;
        font-size: 13px; font-weight: 700; color: var(--amber-text);
        font-family: var(--mono);
    }
    .product-desc {
        margin-top: 5px; font-size: 12px; color: var(--t3);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .product-controls {
        display: flex; flex-direction: column; align-items: flex-end; gap: 10px;
        flex-shrink: 0;
    }

    .status-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    }
    .status-chip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .chip-on  { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.25); }
    .chip-on  .dot { background: var(--emerald); }
    .chip-off { background: var(--rose-dim);    color: var(--rose-text);    border: 1px solid rgba(244,86,110,0.25); }
    .chip-off .dot { background: var(--rose); }

    /* ── Edit Panel ── */
    .edit-panel {
        display: none;
        padding: 0 20px 20px;
        animation: slideDown .18s ease;
    }
    .edit-panel.open { display: block; }

    @keyframes slideDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

    .edit-inner {
        background: var(--bg-2);
        border: 1px solid var(--border-2);
        border-left: 3px solid var(--violet);
        border-radius: 0 var(--r3) var(--r3) 0;
        padding: 18px 20px;
    }
    .edit-head {
        display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
    }
    .edit-head h4 { font-size: 13px; font-weight: 700; color: var(--t1); }
    .edit-head .eicon { color: var(--violet); font-size: 15px; }

    /* ── Country Flag ── */
    .flag {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; color: var(--t2);
    }

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
    .empty p  { font-size: 13px; color: var(--t3); max-width: 340px; line-height: 1.6; }

    /* ── Info Footer ── */
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

    /* ── Stats Row ── */
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
    .stat-card {
        background: var(--bg-3); border: 1px solid var(--border);
        border-radius: var(--r4); padding: 20px;
        position: relative; overflow: hidden;
        transition: border-color .25s, transform .25s, box-shadow .25s;
    }
    .stat-card:hover { border-color: var(--border-2); transform: translateY(-2px); box-shadow: var(--shadow-glow); }
    .stat-card .accent-line { position: absolute; top: 0; left: 20px; right: 20px; height: 2px; border-radius: 0 0 4px 4px; }
    .stat-card.violet .accent-line { background: linear-gradient(90deg, transparent, var(--violet), transparent); }
    .stat-card.emerald .accent-line { background: linear-gradient(90deg, transparent, var(--emerald), transparent); }
    .stat-card.amber .accent-line { background: linear-gradient(90deg, transparent, var(--amber), transparent); }
    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--t3); }
    .stat-icon { width: 40px; height: 40px; border-radius: var(--r2); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .stat-card.violet .stat-icon { background: var(--violet-dim); color: var(--violet); }
    .stat-card.emerald .stat-icon { background: var(--emerald-dim); color: var(--emerald); }
    .stat-card.amber  .stat-icon { background: var(--amber-dim);  color: var(--amber); }
    .stat-value { font-size: 26px; font-weight: 800; color: var(--t1); letter-spacing: -0.5px; line-height: 1; margin-bottom: 10px; }
    .stat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .stat-card.violet .stat-badge { background: var(--violet-dim); color: var(--violet-text); border: 1px solid rgba(139,124,248,0.2); }
    .stat-card.emerald .stat-badge { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.2); }
    .stat-card.amber  .stat-badge { background: var(--amber-dim);  color: var(--amber-text);  border: 1px solid rgba(251,191,36,0.2); }

    .hidden { display: none !important; }

    @media (max-width: 900px) { .stats { grid-template-columns: 1fr 1fr; } .form-grid-4 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) {
        .stats { grid-template-columns: 1fr; }
        .form-grid-2, .form-grid-4 { grid-template-columns: 1fr; }
        .inner { padding: 16px 14px; }
        .product-row { grid-template-columns: auto 1fr; }
        .product-controls { flex-direction: row; align-items: center; flex-wrap: wrap; }
        .phdr { flex-direction: column; align-items: flex-start; }
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
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
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
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
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
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                <!-- Toggle for Automatic/Manual -->
                <div style="margin-bottom: 18px; display: flex; gap: 18px; align-items: center;">
                    <label style="font-weight:700; color:var(--t2);">Mode:</label>
                    <button type="button" id="autoBtn" class="btn-primary" style="height:34px; font-size:13px;">Automatic</button>
                    <button type="button" id="manualBtn" class="btn-secondary" style="height:34px; font-size:13px;">Manual</button>
                </div>
                <!-- Automatic Form -->
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
                    </div>
                    <div class="fg" style="margin-bottom: 18px;">
                        <label class="fl" for="description">Description (optional)</label>
                        <textarea class="fa" id="description" name="description" rows="3" placeholder="Short product details or login type…"></textarea>
                    </div>
                    <hr style="border:0;border-top:1px solid var(--border-2);margin:18px 0;">
                    <div style="margin-bottom: 12px;display:flex;align-items:center;gap:10px;">
                        <h4 style="font-size:14px;font-weight:700;color:var(--t1);margin:0;">Log Details <span style="color:var(--rose);font-weight:700;">*</span></h4>
                        <span style="font-size:12px;color:var(--t3);">(Automatic, at least 1 required)</span>
                    </div>
                    <div id="logDetailsList"></div>
                    <button type="button" id="addLogBtn" class="btn-edit" style="margin-top:10px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Log Detail
                    </button>
                    <input type="hidden" name="log_details" id="logDetailsInput">
                    <div id="logDetailsError" style="color:var(--rose);font-size:13px;margin-top:8px;display:none;">At least one log detail is required for automatic logs.</div>
                    <div class="form-actions" style="margin-top:18px;">
                        <button type="submit" class="btn-primary">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Product
                        </button>
                    </div>
                </form>
                <!-- Manual Form -->
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
                    </div>
                    <div class="fg" style="margin-bottom: 18px;">
                        <label class="fl" for="description_manual">Description (optional)</label>
                        <textarea class="fa" id="description_manual" name="description" rows="3" placeholder="Short product details or login type…"></textarea>
                    </div>
                    <div class="form-actions" style="margin-top:18px;">
                        <button type="submit" class="btn-primary">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
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
                <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" class="btn-secondary" id="filterAll">All</button>
                    <button type="button" class="btn-secondary" id="filterAutomatic">Automatic</button>
                    <button type="button" class="btn-secondary" id="filterManual">Manual</button>
                </div>
            </div>

            <div class="product-list" id="productList">
                @forelse($products as $product)
                    <div class="product-card {{ !$product->is_active ? 'inactive' : '' }}" id="pcard-{{ $product->id }}" data-mode="{{ $product->mode }}">
                        <div class="product-row" style="cursor:pointer;" onclick="toggleLogDetails({{ $product->id }}, '{{ $product->mode }}')">

                            {{-- Icon --}}
                            <div class="product-icon">L</div>

                            {{-- Info --}}
                            <div class="product-info">
                                <div class="product-name">{{ $product->name }}
                                    @if($product->mode === 'automatic')
                                        <span class="status-chip chip-on" style="margin-left:8px;background:var(--blue-dim);color:var(--blue-text);border:1px solid var(--blue-glow);">Automatic Logs</span>
                                    @elseif($product->mode === 'manual')
                                        <span class="status-chip chip-off" style="margin-left:8px;background:var(--amber-dim);color:var(--amber-text);border:1px solid var(--amber);">Manual Logs</span>
                                    @else
                                        <span class="status-chip chip-off" style="margin-left:8px;background:var(--rose-dim);color:var(--rose-text);border:1px solid var(--rose);">Unknown</span>
                                    @endif
                                </div>
                                <div class="product-meta">
                                    <span class="code">{{ strtoupper($product->product_code) }}</span>
                                    @if($product->country)
                                        <span class="sep"></span>
                                        <span class="flag">
                                            @if($product->country_code)
                                                <span style="font-size:14px;">{{ strtoupper($product->country_code) }}</span>
                                            @endif
                                            {{ $product->country }}
                                        </span>
                                    @else
                                        <span class="sep"></span>
                                        <span>Global</span>
                                    @endif
                                </div>
                                <div class="product-price">₦{{ number_format($product->client_price, 2) }}</div>
                                @if($product->description)
                                    <div class="product-desc">{{ Str::limit($product->description, 80) }}</div>
                                @endif
                            </div>

                            {{-- Controls --}}
                            <div class="product-controls">
                                <span class="status-chip {{ $product->is_active ? 'chip-on' : 'chip-off' }}">
                                    <span class="dot"></span>
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div style="display:flex; gap:8px;">
                                    <button
                                        class="btn-edit"
                                        onclick="toggleEdit({{ $product->id }})">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.products.toggle', ['id' => $product->id]) }}" method="POST" style="margin:0;">
                                        @csrf
                                        @if($product->is_active)
                                            <button type="submit" class="btn-danger">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Disable
                                            </button>
                                        @else
                                            <button type="submit" class="btn-enable">
                                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Enable
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Inline Edit Form --}}
                        <div class="edit-panel" id="edit-{{ $product->id }}">
                            <div class="edit-inner">
                                <div class="edit-head">
                                    <svg class="eicon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <h4>Edit — {{ $product->name }}</h4>
                                </div>
                                <form action="{{ route('admin.logs.create') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="mode" value="{{ $product->mode ?? 'manual' }}">
                                    <div class="form-grid-4" style="margin-bottom:14px;">
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
                                    </div>
                                    <div class="fg" style="margin-bottom:14px;">
                                        <label class="fl">Description</label>
                                        <textarea class="fa" name="description" rows="2">{{ $product->description }}</textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn-primary" style="height:36px; font-size:12.5px;">
                                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Save Changes
                                        </button>
                                        <button type="button" class="btn-ghost" onclick="toggleEdit({{ $product->id }})">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        {{-- Automatic Log Details Expandable --}}
                        @if($product->mode === 'automatic')
                        <div class="edit-panel" id="details-{{ $product->id }}" style="display:none;background:var(--bg-2);border:1px solid var(--border-2);border-radius:var(--r2);margin:0 20px 18px 20px;padding:20px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;">
                                <div>
                                    <h4 style="color:var(--blue-text);font-size:15px;margin:0;font-weight:700;">📝 Log Credentials</h4>
                                    <p style="margin:4px 0 0;font-size:12px;color:var(--t3);">Sold credentials are locked and retained for audit. Add new details below to replenish stock.</p>
                                </div>
                                <button type="button" class="btn-edit" onclick="addLogDetailRow({{ $product->id }})" style="height:34px;font-size:13px;">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add New Credential
                                </button>
                            </div>
                            <form action="{{ route('admin.logs.updateDetails', $product->id) }}" method="POST">
                                @csrf
                                @php
                                    $details = json_decode($product->log_details ?? '[]', true);
                                @endphp
                                <div id="logDetailsFields-{{ $product->id }}" data-next-index="{{ count($details) }}" style="display:flex;flex-direction:column;gap:14px;">
                                    @forelse($details as $idx => $detail)
                                        @php $sold = !empty($detail['sold']); @endphp
                                        <div class="log-detail-entry" data-index="{{ $idx }}" style="background:var(--bg-3);border:1px solid var(--border);border-radius:var(--r2);padding:14px;position:relative;">
                                            <div style="position:absolute;top:10px;right:10px;">
                                                <span style="font-size:11px;color:{{ $sold ? '#10b981' : '#93b8ff' }};background:var(--bg-2);padding:4px 8px;border-radius:var(--r1);">{{ $sold ? 'Sold' : 'Available' }}</span>
                                            </div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                                                <div class="fg">
                                                    <label class="fl" style="color:var(--blue-text);">👤 Username/Email</label>
                                                    <input class="fi" type="text" name="log_details[{{ $idx }}][username]" value="{{ $detail['username'] ?? '' }}" placeholder="user@example.com" {{ $sold ? 'readonly' : '' }} />
                                                </div>
                                                <div class="fg">
                                                    <label class="fl" style="color:var(--rose-text);">🔐 Password</label>
                                                    <input class="fi" type="text" name="log_details[{{ $idx }}][password]" value="{{ $detail['password'] ?? '' }}" placeholder="Password" {{ $sold ? 'readonly' : '' }} />
                                                </div>
                                            </div>
                                            <div class="fg">
                                                <label class="fl" style="color:var(--amber-text);">ℹ️ Additional Data</label>
                                                <textarea class="fa" style="min-height:60px;" name="log_details[{{ $idx }}][extra]" placeholder="Security questions, PIN codes, account notes, etc." {{ $sold ? 'readonly' : '' }}>{{ $detail['extra'] ?? '' }}</textarea>
                                            </div>
                                            <input type="hidden" name="log_details[{{ $idx }}][sold]" value="{{ $sold ? '1' : '0' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_at]" value="{{ $detail['sold_at'] ?? '' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_transaction_id]" value="{{ $detail['sold_transaction_id'] ?? '' }}">
                                            <input type="hidden" name="log_details[{{ $idx }}][sold_to_user_id]" value="{{ $detail['sold_to_user_id'] ?? '' }}">
                                            @if(!$sold)
                                                <button type="button" class="btn-danger" style="margin-top:12px;" onclick="removeLogDetailRow({{ $product->id }}, {{ $idx }})">
                                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    @empty
                                        <div style="padding:20px;text-align:center;color:var(--t3);">
                                            <p>No log details added yet. Use the button above to add new credentials.</p>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="form-actions" style="margin-top:16px;gap:10px;flex-wrap:wrap;">
                                    <button type="submit" class="btn-primary" style="height:36px;font-size:13px;">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Update All Credentials
                                    </button>
                                    <button type="button" class="btn-ghost" onclick="toggleLogDetails({{ $product->id }}, '{{ $product->mode }}')" style="height:36px;font-size:13px;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
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
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3>No logs products yet</h3>
                        <p>Create a product above and it will appear here. Products are shown to users when browsing available logs access packages.</p>
                    </div>
                @endforelse
            </div>

            <div class="info-footer">
                <div class="info-footer-icon">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
            if (mode === 'all') card.style.display = '';
            else if (card.getAttribute('data-mode') === mode) card.style.display = '';
            else card.style.display = 'none';
        });
    }
    filterAll.onclick = function() { setFilter('all'); };
    filterAutomatic.onclick = function() { setFilter('automatic'); };
    filterManual.onclick = function() { setFilter('manual'); };
});

// --- Expand/Collapse Log Details ---
function toggleLogDetails(id, mode) {
    if (mode !== 'automatic') return;
    const panel = document.getElementById('details-' + id);
    if (!panel) return;
    panel.style.display = (panel.style.display === 'none' || panel.style.display === '') ? 'block' : 'none';
}
// --- Automatic/Manual Toggle ---
document.addEventListener('DOMContentLoaded', function() {
    const autoBtn = document.getElementById('autoBtn');
    const manualBtn = document.getElementById('manualBtn');
    const autoForm = document.getElementById('autoForm');
    const manualForm = document.getElementById('manualForm');
    autoBtn.onclick = function() {
        autoBtn.classList.add('btn-primary');
        autoBtn.classList.remove('btn-secondary');
        manualBtn.classList.remove('btn-primary');
        manualBtn.classList.add('btn-secondary');
        autoForm.style.display = '';
        manualForm.style.display = 'none';
    };
    manualBtn.onclick = function() {
        manualBtn.classList.add('btn-primary');
        manualBtn.classList.remove('btn-secondary');
        autoBtn.classList.remove('btn-primary');
        autoBtn.classList.add('btn-secondary');
        autoForm.style.display = 'none';
        manualForm.style.display = '';
    };

    // --- Dynamic Log Details ---
    let logDetails = [];
    const logDetailsList = document.getElementById('logDetailsList');
    const logDetailsInput = document.getElementById('logDetailsInput');
    const addLogBtn = document.getElementById('addLogBtn');

    function renderLogDetails() {
        logDetailsList.innerHTML = '';
        logDetails.forEach((detail, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'tcard';
            wrap.style = 'margin-bottom:10px;background:var(--bg-2);padding:18px 16px;position:relative;border-radius:var(--r2);border:1px solid var(--border-2);';

            // Create the inner structure
            const innerDiv = document.createElement('div');
            innerDiv.style.display = 'flex';
            innerDiv.style.gap = '16px';
            innerDiv.style.alignItems = 'center';

            // Left (inputs)
            const leftDiv = document.createElement('div');
            leftDiv.style.flex = '1';

            // Username/Email
            const fgUser = document.createElement('div');
            fgUser.className = 'fg';
            fgUser.style.marginBottom = '8px';
            const labelUser = document.createElement('label');
            labelUser.className = 'fl';
            labelUser.textContent = 'Username/Email';
            const inputUser = document.createElement('input');
            inputUser.className = 'fi';
            inputUser.type = 'text';
            inputUser.value = detail.username || '';
            inputUser.placeholder = 'e.g. user@email.com';
            inputUser.addEventListener('input', function() {
                logDetails[idx].username = this.value;
                logDetailsInput.value = JSON.stringify(logDetails);
            });
            fgUser.appendChild(labelUser);
            fgUser.appendChild(inputUser);

            // Password
            const fgPass = document.createElement('div');
            fgPass.className = 'fg';
            fgPass.style.marginBottom = '8px';
            const labelPass = document.createElement('label');
            labelPass.className = 'fl';
            labelPass.textContent = 'Password';
            const inputPass = document.createElement('input');
            inputPass.className = 'fi';
            inputPass.type = 'text';
            inputPass.value = detail.password || '';
            inputPass.placeholder = 'Password';
            inputPass.addEventListener('input', function() {
                logDetails[idx].password = this.value;
                logDetailsInput.value = JSON.stringify(logDetails);
            });
            fgPass.appendChild(labelPass);
            fgPass.appendChild(inputPass);

            // Extra
            const fgExtra = document.createElement('div');
            fgExtra.className = 'fg';
            const labelExtra = document.createElement('label');
            labelExtra.className = 'fl';
            labelExtra.textContent = 'Additional Data';
            const textareaExtra = document.createElement('textarea');
            textareaExtra.className = 'fa';
            textareaExtra.rows = 2;
            textareaExtra.placeholder = 'Any extra info';
            textareaExtra.value = detail.extra || '';
            textareaExtra.addEventListener('input', function() {
                logDetails[idx].extra = this.value;
                logDetailsInput.value = JSON.stringify(logDetails);
            });
            fgExtra.appendChild(labelExtra);
            fgExtra.appendChild(textareaExtra);

            leftDiv.appendChild(fgUser);
            leftDiv.appendChild(fgPass);
            leftDiv.appendChild(fgExtra);

            // Delete button
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-danger';
            delBtn.style.height = '34px';
            delBtn.innerHTML = "<svg width='13' height='13' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M18 6L6 18M6 6l12 12'/></svg> Delete";
            delBtn.addEventListener('click', function() {
                logDetails.splice(idx, 1);
                renderLogDetails();
            });

            innerDiv.appendChild(leftDiv);
            innerDiv.appendChild(delBtn);
            wrap.appendChild(innerDiv);
            logDetailsList.appendChild(wrap);
        });
        logDetailsInput.value = JSON.stringify(logDetails);
    }

    addLogBtn.onclick = function(e) {
        e.preventDefault();
        logDetails.push({username:'',password:'',extra:''});
        renderLogDetails();
    };

    logDetailsList.addEventListener('logEdit', function(e) {
        const {idx,field,value} = e.detail;
        logDetails[idx][field] = value;
        renderLogDetails();
    });
    logDetailsList.addEventListener('logDelete', function(e) {
        const {idx} = e.detail;
        logDetails.splice(idx,1);
        renderLogDetails();
    });

    // On submit, update hidden input
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
function toggleEdit(id) {
    const panel = document.getElementById('edit-' + id);
    if (!panel) return;
    panel.classList.toggle('open');
}

function addLogDetailRow(productId) {
    const container = document.getElementById('logDetailsFields-' + productId);
    if (!container) return;

    const nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
    const html = `
        <div class="log-detail-entry" data-index="${nextIndex}" style="background:var(--bg-3);border:1px solid var(--border);border-radius:var(--r2);padding:14px;position:relative;">
            <div style="position:absolute;top:10px;right:10px;">
                <span style="font-size:11px;color:#93b8ff;background:var(--bg-2);padding:4px 8px;border-radius:var(--r1);">Available</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="fg">
                    <label class="fl" style="color:var(--blue-text);">👤 Username/Email</label>
                    <input class="fi" type="text" name="log_details[${nextIndex}][username]" placeholder="user@example.com" />
                </div>
                <div class="fg">
                    <label class="fl" style="color:var(--rose-text);">🔐 Password</label>
                    <input class="fi" type="text" name="log_details[${nextIndex}][password]" placeholder="Password" />
                </div>
            </div>
            <div class="fg">
                <label class="fl" style="color:var(--amber-text);">ℹ️ Additional Data</label>
                <textarea class="fa" style="min-height:60px;" name="log_details[${nextIndex}][extra]" placeholder="Security questions, PIN codes, account notes, etc."></textarea>
            </div>
            <input type="hidden" name="log_details[${nextIndex}][sold]" value="0">
            <input type="hidden" name="log_details[${nextIndex}][sold_at]" value="">
            <input type="hidden" name="log_details[${nextIndex}][sold_transaction_id]" value="">
            <input type="hidden" name="log_details[${nextIndex}][sold_to_user_id]" value="">
            <button type="button" class="btn-danger" style="margin-top:12px;" onclick="removeLogDetailRow(${productId}, ${nextIndex})">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
                Delete
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    container.dataset.nextIndex = (nextIndex + 1).toString();
}

function removeLogDetailRow(productId, index) {
    const container = document.getElementById('logDetailsFields-' + productId);
    if (!container) return;
    const row = container.querySelector('[data-index="' + index + '"]');
    if (row) row.remove();
}
</script>
@endsection