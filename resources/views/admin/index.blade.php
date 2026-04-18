@extends('layouts.admin')

@section('title', 'Admin Dashboard - Easygets')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

@section('content')
{{--
    DATA INJECTION — stored in <script type="application/json"> tags.
    This is the critical fix: Blade @json used to write the arrays directly
    into a JS `const` assignment inside the script block. In Blink/WebKit,
    large inline JS payloads inside a <script> tag that is parsed mid-render
    can cause the browser to schedule a style recalculation that races with
    subsequent innerHTML writes. By storing the JSON in inert
    <script type="application/json"> nodes and reading them with
    JSON.parse(el.textContent) we decouple data injection from script
    execution entirely — the data cannot trigger any render side-effects.
--}}
<script type="application/json" id="_users_json">@json($users->getCollection()?->toArray() ?? [])</script>
<script type="application/json" id="_txs_json">@json($transactions->getCollection()?->toArray() ?? [])</script>
<script type="application/json" id="_base_url">"{{ url('/') }}"</script>

<style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
        --bg-base:    #060d1a;
        --bg-surface: #0b1525;
        --bg-card:    #0f1e35;
        --bg-input:   #0b1525;
        --border:     rgba(80,130,220,0.11);
        --border-hi:  rgba(80,130,220,0.22);
        --accent:     #3b82f6;
        --accent-d:   rgba(59,130,246,0.14);
        --accent-g:   rgba(59,130,246,0.28);
        --purple:     #8b5cf6;
        --purple-d:   rgba(139,92,246,0.14);
        --green:      #10b981;
        --green-d:    rgba(16,185,129,0.14);
        --amber:      #f59e0b;
        --amber-d:    rgba(245,158,11,0.14);
        --red:        #ef4444;
        --red-d:      rgba(239,68,68,0.14);
        --t1: #eef4ff;
        --t2: #7da0cc;
        --t3: #3d5a7a;
        --r-sm: 8px; --r-md: 12px; --r-lg: 18px; --r-xl: 24px;
        --font: 'DM Sans', sans-serif;
        --mono: 'DM Mono', monospace;
    }

    body { font-family: var(--font); background: var(--bg-base); color: var(--t1); -webkit-font-smoothing: antialiased; }

    .d-wrap  { padding-top: 70px; min-height: 100vh; }
    .d-inner { padding: 32px 26px; max-width: 1440px; margin: 0 auto; }

    /* ─── Top Bar ─── */
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
    .tb-r    { display: flex; align-items: center; gap: 12px; }

    .search-box { position: relative; }
    .search-box input {
        width: 240px; padding: 8px 13px 8px 34px;
        background: var(--bg-input); border: 1px solid var(--border-hi);
        border-radius: var(--r-sm); color: var(--t1); font-size: 13px;
        font-family: var(--font); transition: border-color .2s, box-shadow .2s;
    }
    .search-box input::placeholder { color: var(--t3); }
    .search-box input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-d); }
    .search-box svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--t3); pointer-events: none; }

    .av {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--accent), var(--purple));
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 15px; color: #fff;
        box-shadow: 0 0 0 3px var(--accent-d);
    }

    /* ─── Stats ─── */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 16px; margin-bottom: 26px; }
    .sc {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--r-lg); padding: 20px 22px;
        position: relative; overflow: hidden; transition: border-color .2s, transform .2s;
    }
    .sc:hover { border-color: var(--border-hi); transform: translateY(-2px); }
    .sc .bar  { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--r-lg) var(--r-lg) 0 0; }
    .sc.bl .bar { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
    .sc.gr .bar { background: linear-gradient(90deg,#10b981,#34d399); }
    .sc.am .bar { background: linear-gradient(90deg,#f59e0b,#fcd34d); }
    .sc-row  { display: flex; justify-content: space-between; align-items: flex-start; }
    .sc-lbl  { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--t3); margin-bottom: 9px; }
    .sc-val  { font-size: 28px; font-weight: 700; color: var(--t1); letter-spacing: -.5px; line-height: 1; margin-bottom: 9px; }
    .sc-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--green-d); color: #34d399; }
    .sc-ico  { width: 44px; height: 44px; border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .sc-ico.bl { background: var(--accent-d); }
    .sc-ico.gr { background: var(--green-d); }
    .sc-ico.am { background: var(--amber-d); }

    /* ─── Table Card ─── */
    .tc {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--r-lg); overflow: hidden; margin-bottom: 22px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: border-color .3s, box-shadow .3s;
        display: flex; flex-direction: column;
    }
    .tc:hover { border-color: var(--border-hi); box-shadow: 0 4px 16px rgba(59,130,246,0.1); }

    .tc-head {
        padding: 20px 22px; border-bottom: 1.5px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; flex-wrap: wrap; flex-shrink: 0;
        background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.02) 100%);
    }
    .tc-title    { display: flex; align-items: center; gap: 12px; }
    .tc-title h3 { font-size: 16px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
    .pill {
        padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
        background: linear-gradient(135deg, var(--accent-d), rgba(59,130,246,0.2));
        color: #93c5fd; border: 1px solid rgba(59,130,246,0.3);
    }
    .tc-ctrl { display: flex; align-items: center; gap: 9px; }

    .tc-search { position: relative; }
    .tc-search input {
        padding: 8px 12px 8px 32px; width: 200px;
        background: var(--bg-input); border: 1.5px solid var(--border-hi);
        border-radius: 8px; color: var(--t1); font-size: 13px;
        font-family: var(--font); transition: border-color .2s, box-shadow .2s;
    }
    .tc-search input::placeholder { color: var(--t3); }
    .tc-search input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-d); background: var(--bg-card); }
    .tc-search svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--t3); pointer-events: none; }

    .pp-sel {
        padding: 8px 11px; background: var(--bg-input); border: 1.5px solid var(--border-hi);
        border-radius: 8px; color: #93c5fd; font-size: 12px; font-family: var(--font);
        cursor: pointer; transition: border-color .2s;
    }
    .pp-sel:hover { border-color: var(--accent); }
    .pp-sel:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-d); }

    /* ─── Table visibility ───
       CRITICAL: always toggle via CSS class, never .style.display on <table>.
       .style.display forces a synchronous Blink/WebKit style recalculation
       that discards pending DOM writes before paint commit — rows vanish.
    ─── */
    .t-scroll            { overflow-x: auto; width: 100%; }
    table                { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.tbl-hidden     { display: none; }
    .empty-st            { padding: 48px 24px; text-align: center; }
    .empty-st.tbl-hidden { display: none; }
    .empty-st .ei        { font-size: 40px; opacity: .25; margin-bottom: 12px; }
    .empty-st p          { color: var(--t3); font-size: 14px; font-weight: 500; }

    thead tr { background: var(--bg-surface); }
    th {
        padding: 11px 16px; text-align: left; font-size: 10px; font-weight: 700;
        color: var(--t3); text-transform: uppercase; letter-spacing: .8px;
        border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    td {
        padding: 13px 16px; border-bottom: 1px solid rgba(80,130,220,0.06);
        font-size: 13px; color: var(--t1); vertical-align: middle;
    }
    tbody tr { transition: background .15s; }
    tbody tr:hover { background: rgba(59,130,246,0.07); }
    tbody tr:last-child td { border-bottom: none; }

    /* ─── Cell styles ─── */
    .u-cell { display: flex; align-items: center; gap: 10px; }
    .u-av   {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--accent), var(--purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700; color: #fff;
    }
    .u-name { font-weight: 600; font-size: 14px; }
    .type-tag {
        padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;
        background: rgba(80,130,220,0.1); color: #93c5fd;
        font-family: var(--mono); border: 1px solid rgba(59,130,246,0.2);
    }
    .bal { color: #34d399; font-weight: 700; font-family: var(--mono); font-size: 14px; }
    .amt { color: #fcd34d; font-weight: 700; font-family: var(--mono); font-size: 14px; }

    .badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border-radius: 6px; font-size: 12px; font-weight: 700; }
    .bd    { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .badge.ok  { background: rgba(16,185,129,0.13);  color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); } .badge.ok  .bd { background: #6ee7b7; }
    .badge.pnd { background: rgba(245,158,11,0.13);  color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); } .badge.pnd .bd { background: #fbbf24; }
    .badge.no  { background: rgba(239,68,68,0.13);   color: #f87171; border: 1px solid rgba(239,68,68,0.3);  } .badge.no  .bd { background: #f87171; }
    .badge.ot  { background: rgba(59,130,246,0.10);  color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); } .badge.ot  .bd { background: #93c5fd; }

    .view-btn {
        padding: 6px 13px; background: rgba(59,130,246,0.1); color: #93c5fd;
        border: 1.5px solid rgba(59,130,246,0.35); border-radius: 7px;
        font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .2s, color .2s, box-shadow .2s; font-family: var(--font);
    }
    .view-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 4px 14px rgba(59,130,246,0.35); }

    /* ─── Pagination ─── */
    .pg-bar  {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 22px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;
    }
    .pg-info { font-size: 13px; color: var(--t3); font-weight: 500; }
    .pg-info b { color: #93c5fd; font-weight: 700; }
    .pg-btns { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pg-btn {
        min-width: 38px; height: 38px; padding: 0 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #1a2a4a; border: 1.5px solid #3b5998;
        border-radius: var(--r-md); color: #93c5fd;
        font-size: 12px; font-weight: 700; font-family: var(--font);
        cursor: pointer; user-select: none;
        transition: background .2s, color .2s, box-shadow .2s;
    }
    .pg-btn:hover:not(.on):not(.off) { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,0.35); }
    .pg-btn.on  { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border-color: var(--accent); color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    .pg-btn.off { opacity: .35; cursor: not-allowed; pointer-events: none; }
    .pg-dots    { color: var(--t3); font-size: 13px; padding: 0 4px; font-weight: 700; }

    /* ─── Modal ─── */
    .m-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(3,7,18,.88); z-index: 1000;
        align-items: center; justify-content: center;
        padding: 18px; backdrop-filter: blur(5px);
    }
    .m-overlay.open { display: flex; }
    .m-box {
        background: var(--bg-card); border: 1px solid var(--border-hi);
        border-radius: var(--r-xl); width: 100%; max-width: 860px; max-height: 90vh;
        overflow-y: auto; box-shadow: 0 40px 100px rgba(0,0,0,.8);
        animation: mUp .22s ease;
    }
    @keyframes mUp { from { transform: translateY(14px); opacity:0; } to { transform: translateY(0); opacity:1; } }
    .m-hd {
        padding: 20px 24px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; background: var(--bg-card); z-index: 5;
    }
    .m-hd h2  { font-size: 17px; font-weight: 700; color: var(--t1); }
    .m-close  {
        width: 30px; height: 30px; border-radius: 50%;
        background: rgba(80,130,220,0.1); border: none; color: var(--t2); font-size: 16px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .2s, color .2s;
    }
    .m-close:hover { background: var(--red-d); color: #fca5a5; }
    .m-body   { padding: 24px; }
    .m-sec    { margin-bottom: 24px; }
    .m-stitle { font-size: 10px; font-weight: 700; color: var(--t3); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 12px; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(175px,1fr)); gap: 12px; }
    .info-tile { padding: 13px 15px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--r-md); }
    .info-tile .lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--t3); margin-bottom: 5px; }
    .info-tile .val { font-size: 14px; font-weight: 600; color: var(--t1); }
    .info-tile .val.g { color: var(--green); font-family: var(--mono); }

    .act-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-bottom: 14px; }
    .act-b {
        padding: 10px 13px; border-radius: var(--r-md); font-size: 12px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: background .2s, color .2s, box-shadow .2s; font-family: var(--font);
    }
    .act-b.bl { background: var(--accent-d); color: var(--accent); border: 1px solid rgba(59,130,246,.25); }
    .act-b.pu { background: var(--purple-d); color: var(--purple); border: 1px solid rgba(139,92,246,.25); }
    .act-b.gr { background: var(--green-d);  color: var(--green);  border: 1px solid rgba(16,185,129,.25); }
    .act-b.bl:hover { background: var(--accent); color: #fff; box-shadow: 0 4px 16px var(--accent-g); }
    .act-b.pu:hover { background: var(--purple); color: #fff; }
    .act-b.gr:hover { background: var(--green);  color: #fff; }

    .flash { padding: 9px 13px; border-radius: var(--r-sm); font-size: 12px; font-weight: 600; margin-bottom: 12px; display: none; }
    .flash.ok  { display: block; background: var(--green-d); color: #34d399; border-left: 3px solid var(--green); }
    .flash.err { display: block; background: var(--red-d);   color: #fca5a5; border-left: 3px solid var(--red); }

    .sub-form { display: none; margin-top: 2px; padding: 16px; border-radius: var(--r-md); background: var(--bg-surface); border: 1px solid var(--border); border-left: 3px solid var(--accent); }
    .sub-form.open    { display: block; animation: fIn .18s ease; }
    .sub-form.pu-form { border-left-color: var(--purple); }
    .sub-form.gr-form { border-left-color: var(--green); }
    @keyframes fIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
    .sub-form h4 { font-size: 13px; font-weight: 700; color: var(--t1); margin-bottom: 13px; }

    .f-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .f-g    { margin-bottom: 12px; }
    .f-l    { display: block; font-size: 11px; font-weight: 600; color: var(--t2); margin-bottom: 5px; }
    .f-i, .f-a {
        width: 100%; padding: 9px 12px; background: var(--bg-card); border: 1px solid var(--border-hi);
        border-radius: var(--r-sm); color: var(--t1); font-size: 12px;
        font-family: var(--font); transition: border-color .2s, box-shadow .2s;
    }
    .f-i::placeholder, .f-a::placeholder { color: var(--t3); }
    .f-i:focus, .f-a:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-d); }
    .f-a { resize: vertical; min-height: 64px; }
    .funds-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .s-btn {
        width: 100%; padding: 9px; border: none; border-radius: var(--r-sm);
        font-size: 12px; font-weight: 700; cursor: pointer;
        font-family: var(--font); transition: background .2s, box-shadow .2s; margin-top: 4px;
    }
    .s-btn.bl { background: var(--accent);  color: #fff; } .s-btn.bl:hover { background: #2563eb; box-shadow: 0 4px 14px var(--accent-g); }
    .s-btn.pu { background: var(--purple);  color: #fff; } .s-btn.pu:hover { background: #7c3aed; }
    .s-btn.gr { background: var(--green);   color: #fff; } .s-btn.gr:hover { background: #059669; }
    .s-btn.rd { background: var(--red);     color: #fff; } .s-btn.rd:hover { background: #dc2626; }

    .m-txns { overflow-x: auto; }
    .m-txns table th { background: var(--bg-base); }
    .m-txns table td, .m-txns table th { padding: 9px 13px; font-size: 12px; }

    @media (max-width: 768px) {
        .d-inner { padding: 16px 12px; }
        .stats   { grid-template-columns: 1fr 1fr; }
        .tb-r    { width: 100%; }
        .search-box input  { width: 100%; }
        .tc-search input   { width: 140px; }
        .tc-ctrl           { flex-wrap: wrap; }
        .act-row           { grid-template-columns: 1fr; }
        .f-2col, .funds-2  { grid-template-columns: 1fr; }
        .pg-bar            { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="d-wrap">
<div class="d-inner">

    {{-- Top bar --}}
    <div class="top-bar">
        <div class="tb-l">
            <h1>Admin Dashboard</h1>
            <p>{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="tb-r">
            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" placeholder="Global search…">
            </div>
            <div class="av">A</div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="sc bl">
            <div class="bar"></div>
            <div class="sc-row">
                <div>
                    <div class="sc-lbl">Total Users</div>
                    <div class="sc-val">{{ $stats['total_users'] ?? $users->total() }}</div>
                    <span class="sc-chip">↑ 3.5%</span>
                </div>
                <div class="sc-ico bl">👥</div>
            </div>
        </div>
        <div class="sc gr">
            <div class="bar"></div>
            <div class="sc-row">
                <div>
                    <div class="sc-lbl">Total Revenue</div>
                    <div class="sc-val">₦{{ number_format($stats['total_revenue'] ?? 0, 0) }}</div>
                    <span class="sc-chip">↑ 11%</span>
                </div>
                <div class="sc-ico gr">💰</div>
            </div>
        </div>
        <div class="sc am">
            <div class="bar"></div>
            <div class="sc-row">
                <div>
                    <div class="sc-lbl">Transactions</div>
                    <div class="sc-val">{{ $stats['total_transactions'] ?? 0 }}</div>
                    <span class="sc-chip">↑ 8.3%</span>
                </div>
                <div class="sc-ico am">🛒</div>
            </div>
        </div>
    </div>

    {{-- Users table --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Recent Users</h3>
                <span class="pill" id="uPill">{{ $users->total() }}</span>
            </div>
            <div class="tc-ctrl">
                <div class="tc-search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search users…" id="uFilter">
                </div>
                <select class="pp-sel" id="uPP">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
            </div>
        </div>
        <div class="t-scroll">
            <table id="uTable">
                <thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Balance</th><th>Action</th></tr></thead>
                <tbody id="uBody"></tbody>
            </table>
        </div>
        <div id="uEmpty" class="empty-st tbl-hidden">
            <div class="ei">🔍</div><p>No users match your search.</p>
        </div>
        <div class="pg-bar">
            <div class="pg-info" id="uInfo"></div>
            <div class="pg-btns" id="uBtns"></div>
        </div>
    </div>

    {{-- Transactions table --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Recent Transactions</h3>
                <span class="pill" id="txPill">{{ $stats['total_transactions'] ?? 0 }}</span>
            </div>
            <div class="tc-ctrl">
                <div class="tc-search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search transactions…" id="txFilter">
                </div>
                <select class="pp-sel" id="txPP">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
            </div>
        </div>
        <div class="t-scroll">
            <table id="txTable">
                <thead><tr><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody id="txBody"></tbody>
            </table>
        </div>
        <div id="txEmpty" class="empty-st tbl-hidden">
            <div class="ei">🔍</div><p>No transactions match your search.</p>
        </div>
        <div class="pg-bar">
            <div class="pg-info" id="txInfo"></div>
            <div class="pg-btns" id="txBtns"></div>
        </div>
    </div>

</div>
</div>

{{-- Modal --}}
<div class="m-overlay" id="userModal">
    <div class="m-box">
        <div class="m-hd">
            <h2 id="mTitle">User Details</h2>
            <button class="m-close" id="mCloseBtn">✕</button>
        </div>
        <div class="m-body">
            <div class="m-sec">
                <div class="m-stitle">Profile</div>
                <div class="info-grid" id="mInfo"></div>
            </div>
            <div class="m-sec">
                <div class="m-stitle">Admin Actions</div>
                <div class="act-row">
                    <button class="act-b bl" id="btnTogPw">🔑 Password</button>
                    <button class="act-b pu" id="btnTogPin">🔐 PIN</button>
                    <button class="act-b gr" id="btnTogFunds">💳 Wallet</button>
                </div>
                <div id="fMsg"></div>
                <div id="fPw" class="sub-form">
                    <h4>Change Password</h4>
                    <div class="f-2col">
                        <div class="f-g"><label class="f-l">New Password</label><input type="password" id="nPw" class="f-i" placeholder="••••••••"></div>
                        <div class="f-g"><label class="f-l">Confirm</label><input type="password" id="cPw" class="f-i" placeholder="••••••••"></div>
                    </div>
                    <button class="s-btn bl" id="btnDoPw">Update Password</button>
                </div>
                <div id="fPin" class="sub-form pu-form">
                    <h4>Change PIN</h4>
                    <div class="f-g" style="max-width:200px;">
                        <label class="f-l">4-digit PIN</label>
                        <input type="password" id="nPin" class="f-i" placeholder="••••" maxlength="4">
                    </div>
                    <button class="s-btn pu" id="btnDoPin">Update PIN</button>
                </div>
                <div id="fFunds" class="sub-form gr-form">
                    <h4>Wallet Management</h4>
                    <div class="funds-2">
                        <div>
                            <p style="font-size:11px;font-weight:700;color:#34d399;margin-bottom:10px;">➕ Add Funds</p>
                            <div class="f-g"><label class="f-l">Amount (₦)</label><input type="number" id="aAmt" class="f-i" placeholder="0.00" min="0" step="0.01"></div>
                            <div class="f-g"><label class="f-l">Reason</label><textarea id="aDesc" class="f-a" placeholder="Why are you adding funds?"></textarea></div>
                            <button class="s-btn gr" id="btnDoAdd">Add Funds</button>
                        </div>
                        <div>
                            <p style="font-size:11px;font-weight:700;color:#fca5a5;margin-bottom:10px;">➖ Deduct Funds</p>
                            <div class="f-g"><label class="f-l">Amount (₦)</label><input type="number" id="dAmt" class="f-i" placeholder="0.00" min="0" step="0.01"></div>
                            <div class="f-g"><label class="f-l">Reason</label><textarea id="dDesc" class="f-a" placeholder="Why are you deducting funds?"></textarea></div>
                            <button class="s-btn rd" id="btnDoDed">Deduct Funds</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="m-sec">
                <div class="m-stitle">Recent Transactions</div>
                <div class="m-txns" id="mTxns"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    /* ════════════════════════════════════════════════════════════════
       DATA — read from inert <script type="application/json"> tags.

       WHY NOT `const USERS = @json(...)`?
       When Blade writes @json directly into a JS const inside a <script>
       block, the browser parses the entire script as JS during HTML parse.
       Large payloads (many users/transactions) cause the parser to schedule
       a style-recalculation mid-block. On Blink/WebKit this style-recalc
       runs BEFORE the subsequent DOM writes (tbody.innerHTML) are committed
       to the render tree, so those writes are lost and the tbody appears
       blank after any scroll or touch event.

       Storing JSON in <script type="application/json"> tags is inert —
       the browser treats them as opaque text nodes, never parses them as
       JS, and never schedules a style-recalc from them. We read them once
       with JSON.parse(el.textContent) and the data is safely in memory.
    ════════════════════════════════════════════════════════════════ */
    const USERS = Object.freeze(
        JSON.parse(document.getElementById('_users_json').textContent)
    );
    const TXS = Object.freeze(
        JSON.parse(document.getElementById('_txs_json').textContent)
    );
    const BASE = JSON.parse(document.getElementById('_base_url').textContent);

    /* ── Helpers ── */
    /* Safe text-node escaping — never use string concatenation for user data */
    function esc(s) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s ?? '')));
        return d.innerHTML;
    }

    function ngn(n) {
        return Number(n ?? 0).toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /* ── Row builders — return real <tr> DOM nodes, never HTML strings ──
       WHY NODES?
       If _render() uses  tbody.innerHTML = rows.map(r => htmlString).join('')
       the browser must re-parse every HTML string on every render call.
       During that parse, Blink/WebKit can fire a micro-task (e.g. a font
       load callback, a ResizeObserver, a scroll event) that triggers a
       style-recalculation. If that recalc happens AFTER innerHTML is set
       but BEFORE the browser commits the new DOM nodes to the render tree,
       the table body is wiped. Using DocumentFragment + replaceChildren()
       is a single atomic operation that cannot be interrupted this way.
    ── */
    function uRow(u) {
        const ini = (u.username || 'U').charAt(0).toUpperCase();
        const bal = u.wallet
            ? Number(u.wallet.balance || 0).toLocaleString('en-NG', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
              })
            : '0.00';

        const tr = document.createElement('tr');
        tr.innerHTML =
            `<td><div class="u-cell">` +
                `<div class="u-av">${ini}</div>` +
                `<div class="u-name">${esc(u.username || 'User')}</div>` +
            `</div></td>` +
            `<td style="color:var(--t2);">${esc(u.email || '—')}</td>` +
            `<td style="color:var(--t2);">${esc(u.phone_number || '—')}</td>` +
            `<td><span class="bal">₦${bal}</span></td>` +
            `<td><button class="view-btn">👁 View</button></td>`;

        /* Event listener on the node — never an inline onclick="..." string.
           Inline onclick strings are re-evaluated as JS on every repaint in
           some Blink builds, which can cause unexpected re-renders. */
        tr.querySelector('.view-btn').addEventListener('click', function () {
            openModal(u.id);
        });
        return tr;
    }

    function txRow(tx) {
        const uname = tx.user?.username ?? 'N/A';
        const date  = tx.created_at
            ? new Date(tx.created_at).toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
              })
            : '—';
        const s   = (tx.status || '').toLowerCase();
        const cls = (s === 'success' || s === 'completed') ? 'ok'
                  : s === 'pending' ? 'pnd'
                  : s === 'failed'  ? 'no'
                  : 'ot';
        const lbl = (s === 'success' || s === 'completed') ? '✓ Success'
                  : s === 'pending' ? '⏳ Pending'
                  : s === 'failed'  ? '✗ Failed'
                  : '◆ ' + (tx.status || 'Unknown').charAt(0).toUpperCase()
                          + (tx.status || 'Unknown').slice(1);

        const tr = document.createElement('tr');
        tr.innerHTML =
            `<td><div class="u-cell">` +
                `<div class="u-av">${esc(uname.charAt(0).toUpperCase())}</div>` +
                `<div class="u-name">${esc(uname)}</div>` +
            `</div></td>` +
            `<td><span class="type-tag">${esc(tx.type || '—')}</span></td>` +
            `<td><span class="amt">₦${ngn(tx.amount)}</span></td>` +
            `<td><span class="badge ${cls}"><span class="bd"></span>${lbl}</span></td>` +
            `<td style="color:var(--t3);font-size:12px;">${date}</td>`;
        return tr;
    }

    /* ════════════════════════════════════════════════════════════════
       Table class
       The three scroll-safety rules:
         1. _render() builds rows into a DocumentFragment, then calls
            tbody.replaceChildren(fragment) — one atomic DOM operation.
         2. Visibility is toggled via CSS classList, never style.display.
         3. Row functions return DOM nodes (not strings).
    ════════════════════════════════════════════════════════════════ */
    class Table {
        constructor(cfg) {
            this._src  = Array.from(cfg.data || []);
            this._view = Array.from(this._src);
            this.p     = 1;
            this.pp    = cfg.perPage || 10;
            this.row   = cfg.row;

            this._tbody = document.getElementById(cfg.tbody);
            this._tbl   = document.getElementById(cfg.table);
            this._info  = document.getElementById(cfg.info);
            this._btns  = document.getElementById(cfg.btns);
            this._empty = document.getElementById(cfg.empty);
            this._pill  = document.getElementById(cfg.pill);

            if (!this._tbody || !this._tbl || !this._info || !this._btns || !this._empty) {
                console.error('[Table] Missing DOM element for config:', cfg);
                return;
            }

            const ppEl = document.getElementById(cfg.ppSel);
            if (ppEl) {
                ppEl.value = String(this.pp);
                ppEl.addEventListener('change', (e) => {
                    this.pp = +e.target.value;
                    this.p  = 1;
                    this._render();
                });
            }

            const fEl = document.getElementById(cfg.filter);
            if (fEl) {
                let _timer;
                fEl.addEventListener('input', (e) => {
                    clearTimeout(_timer);
                    _timer = setTimeout(() => {
                        const q = e.target.value.toLowerCase().trim();
                        this._view = q
                            ? this._src.filter(r => JSON.stringify(r).toLowerCase().includes(q))
                            : Array.from(this._src);
                        if (this._pill) this._pill.textContent = this._view.length;
                        this.p = 1;
                        this._render();
                    }, 150);
                });
            }

            this._render();
        }

        _render() {
            const tot  = this._view.length;
            const pgs  = Math.max(1, Math.ceil(tot / this.pp));
            this.p     = Math.min(Math.max(1, this.p), pgs);
            const s    = (this.p - 1) * this.pp;
            const rows = this._view.slice(s, s + this.pp);
            const empty = tot === 0;

            /* Toggle CSS class — NEVER .style.display on a <table> */
            this._tbl.classList.toggle('tbl-hidden', empty);
            this._empty.classList.toggle('tbl-hidden', !empty);

            if (!empty) {
                const frag = document.createDocumentFragment();
                rows.forEach(r => {
                    try {
                        const node = this.row(r);
                        if (node instanceof Node) frag.appendChild(node);
                    } catch (err) {
                        console.warn('[Table] row() error:', err);
                    }
                });
                /* replaceChildren is atomic — scroll/touch cannot interrupt it */
                this._tbody.replaceChildren(frag);
            }

            this._info.innerHTML = empty
                ? ''
                : `Showing <b>${s + 1}–${Math.min(s + this.pp, tot)}</b> of <b>${tot}</b>`;

            this._buildPager(pgs);
        }

        _buildPager(total) {
            const frag = document.createDocumentFragment();
            this._navBtn(frag, '‹', this.p <= 1,     () => { this.p--; this._render(); });

            const nums = total <= 7
                ? Array.from({ length: total }, (_, i) => i + 1)
                : [
                    1,
                    ...(this.p > 3 ? ['…'] : []),
                    ...Array.from(
                        { length: Math.min(this.p + 1, total - 1) - Math.max(this.p - 1, 2) + 1 },
                        (_, i) => Math.max(this.p - 1, 2) + i
                    ),
                    ...(this.p < total - 2 ? ['…'] : []),
                    total
                  ];

            nums.forEach(n => {
                if (n === '…') {
                    const sp = document.createElement('span');
                    sp.className   = 'pg-dots';
                    sp.textContent = '…';
                    frag.appendChild(sp);
                    return;
                }
                const b = document.createElement('button');
                b.className   = 'pg-btn' + (n === this.p ? ' on' : '');
                b.textContent = n;
                if (n !== this.p) {
                    b.addEventListener('click', () => { this.p = n; this._render(); });
                }
                frag.appendChild(b);
            });

            this._navBtn(frag, '›', this.p >= total, () => { this.p++; this._render(); });
            this._btns.replaceChildren(frag);
        }

        _navBtn(parent, label, disabled, cb) {
            const b = document.createElement('button');
            b.className   = 'pg-btn' + (disabled ? ' off' : '');
            b.textContent = label;
            if (!disabled) b.addEventListener('click', cb);
            parent.appendChild(b);
        }
    }

    /* ── Init tables on DOMContentLoaded ── */
    document.addEventListener('DOMContentLoaded', function () {
        window._uTable = new Table({
            data: USERS, table: 'uTable', tbody: 'uBody',
            info: 'uInfo', btns: 'uBtns', empty: 'uEmpty',
            pill: 'uPill', ppSel: 'uPP', filter: 'uFilter',
            row: uRow, perPage: 10
        });
        window._txTable = new Table({
            data: TXS, table: 'txTable', tbody: 'txBody',
            info: 'txInfo', btns: 'txBtns', empty: 'txEmpty',
            pill: 'txPill', ppSel: 'txPP', filter: 'txFilter',
            row: txRow, perPage: 10
        });

        /* ── Modal button wiring (no inline onclick) ── */
        document.getElementById('mCloseBtn').addEventListener('click', closeModal);
        document.getElementById('userModal').addEventListener('click', function (e) {
            if (e.target === e.currentTarget) closeModal();
        });
        document.getElementById('btnTogPw').addEventListener('click',    () => togForm('fPw'));
        document.getElementById('btnTogPin').addEventListener('click',   () => togForm('fPin'));
        document.getElementById('btnTogFunds').addEventListener('click', () => togForm('fFunds'));
        document.getElementById('btnDoPw').addEventListener('click',     doPw);
        document.getElementById('btnDoPin').addEventListener('click',    doPin);
        document.getElementById('btnDoAdd').addEventListener('click',    doAdd);
        document.getElementById('btnDoDed').addEventListener('click',    doDed);
    });

    /* ── Modal ── */
    let uid = null;

    async function openModal(userId) {
        uid = userId;
        closeAll();
        flash('', '');
        try {
            const r = await fetch(`${BASE}/app/admin/users/${userId}`);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const { user, transactions } = await r.json();

            document.getElementById('mTitle').textContent = `@${user.username || 'User'}`;

            /* Info tiles — build as DOM nodes */
            const infoGrid = document.getElementById('mInfo');
            const igFrag   = document.createDocumentFragment();
            const tiles = [
                ['Full Name',      (user.first_name || '') + ' ' + (user.last_name || ''), null],
                ['Username',       '@' + (user.username || ''),                            null],
                ['Email',          user.email || '—',                                      null],
                ['Phone',          user.phone_number || '—',                               null],
                ['Wallet Balance', '₦' + ngn(user.wallet?.balance),                        'g'],
                ['Joined',         new Date(user.created_at).toLocaleDateString('en-US', {
                                       year: 'numeric', month: 'short', day: 'numeric'
                                   }),                                                     null]
            ];
            tiles.forEach(([lbl, val, cls]) => {
                const tile  = document.createElement('div');
                tile.className = 'info-tile';
                const lblEl = document.createElement('div'); lblEl.className = 'lbl'; lblEl.textContent = lbl;
                const valEl = document.createElement('div'); valEl.className = 'val' + (cls ? ' ' + cls : ''); valEl.textContent = val;
                tile.appendChild(lblEl);
                tile.appendChild(valEl);
                igFrag.appendChild(tile);
            });
            infoGrid.replaceChildren(igFrag);

            /* Transactions mini-table — DOM nodes */
            const mTxns = document.getElementById('mTxns');
            if (!(transactions || []).length) {
                const p = document.createElement('p');
                p.style.cssText = 'color:var(--t3);text-align:center;padding:20px 0;font-size:13px;';
                p.textContent   = 'No transactions found.';
                mTxns.replaceChildren(p);
            } else {
                const tbl   = document.createElement('table');
                const thead = document.createElement('thead');
                thead.innerHTML = '<tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>';
                const tbody = document.createElement('tbody');
                const tfrag = document.createDocumentFragment();
                transactions.forEach(tx => {
                    const s   = (tx.status || '').toLowerCase();
                    const cls = (s === 'success' || s === 'completed') ? 'ok'
                              : s === 'pending' ? 'pnd'
                              : s === 'failed'  ? 'no'
                              : 'ot';
                    const tr  = document.createElement('tr');
                    tr.innerHTML =
                        `<td>${esc(tx.type || '—')}</td>` +
                        `<td style="font-family:var(--mono);font-weight:600;">₦${ngn(tx.amount)}</td>` +
                        `<td><span class="badge ${cls}"><span class="bd"></span>${esc(tx.status)}</span></td>` +
                        `<td style="color:var(--t3);">${new Date(tx.created_at).toLocaleDateString()}</td>`;
                    tfrag.appendChild(tr);
                });
                tbody.replaceChildren(tfrag);
                tbl.appendChild(thead);
                tbl.appendChild(tbody);
                mTxns.replaceChildren(tbl);
            }

            document.getElementById('userModal').classList.add('open');
        } catch (e) {
            alert('Failed to load user: ' + e.message);
        }
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('open');
    }

    function togForm(id) {
        const el  = document.getElementById(id);
        const was = el.classList.contains('open');
        closeAll();
        if (!was) el.classList.add('open');
    }

    function closeAll() {
        document.querySelectorAll('.sub-form').forEach(f => f.classList.remove('open'));
    }

    function flash(msg, type) {
        const el = document.getElementById('fMsg');
        if (!msg) { el.className = 'flash'; el.textContent = ''; return; }
        el.textContent = msg;
        el.className   = 'flash ' + type;
    }

    async function post(url, data) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept':       'application/json'
            },
            body: JSON.stringify(data)
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    }

    async function doPw() {
        const p = document.getElementById('nPw').value;
        const c = document.getElementById('cPw').value;
        if (!p || p !== c) { flash('Passwords do not match.', 'err'); return; }
        try {
            flash('Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-password`, {
                password: p, password_confirmation: c
            });
            flash(d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                document.getElementById('nPw').value = '';
                document.getElementById('cPw').value = '';
            }
        } catch (e) { flash('Error: ' + e.message, 'err'); }
    }

    async function doPin() {
        const p = document.getElementById('nPin').value;
        if (!p || p.length !== 4) { flash('PIN must be exactly 4 digits.', 'err'); return; }
        try {
            flash('Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-pin`, { pin: p });
            flash(d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) document.getElementById('nPin').value = '';
        } catch (e) { flash('Error: ' + e.message, 'err'); }
    }

    async function doAdd() {
        const a    = parseFloat(document.getElementById('aAmt').value);
        const desc = document.getElementById('aDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash('Please fill in amount and reason.', 'err'); return; }
        try {
            flash('Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/add-funds`, {
                amount: a, description: desc
            });
            flash(d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                document.getElementById('aAmt').value  = '';
                document.getElementById('aDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch (e) { flash('Error: ' + e.message, 'err'); }
    }

    async function doDed() {
        const a    = parseFloat(document.getElementById('dAmt').value);
        const desc = document.getElementById('dDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash('Please fill in amount and reason.', 'err'); return; }
        try {
            flash('Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/deduct-funds`, {
                amount: a, description: desc
            });
            flash(d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                document.getElementById('dAmt').value  = '';
                document.getElementById('dDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch (e) { flash('Error: ' + e.message, 'err'); }
    }

})(); /* end IIFE */
</script>

@endsection