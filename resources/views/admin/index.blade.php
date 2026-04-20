@extends('layouts.admin')

@section('title', 'Admin Dashboard - Easygets')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

@section('content')
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

    /* Global search form */
    .search-form { display: flex; align-items: center; gap: 8px; }
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
    }
    .tc:hover { border-color: var(--border-hi); box-shadow: 0 4px 16px rgba(59,130,246,0.1); }

    .tc-head {
        padding: 20px 22px; border-bottom: 1.5px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; flex-wrap: wrap;
        background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.02) 100%);
    }
    .tc-title    { display: flex; align-items: center; gap: 12px; }
    .tc-title h3 { font-size: 16px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
    .pill {
        padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
        background: linear-gradient(135deg, var(--accent-d), rgba(59,130,246,0.2));
        color: #93c5fd; border: 1px solid rgba(59,130,246,0.3);
    }
    .tc-ctrl { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }

    /* Table filter form */
    .filter-form { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
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
    .pp-sel:hover, .pp-sel:focus { border-color: var(--accent); outline: none; }

    .filter-btn {
        padding: 8px 14px; background: var(--accent-d); color: #93c5fd;
        border: 1.5px solid rgba(59,130,246,0.3); border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        font-family: var(--font); transition: background .2s, color .2s;
        white-space: nowrap;
    }
    .filter-btn:hover { background: var(--accent); color: #fff; }

    .clear-btn {
        padding: 8px 14px; background: transparent; color: var(--t3);
        border: 1.5px solid var(--border-hi); border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none;
        font-family: var(--font); transition: border-color .2s, color .2s;
        white-space: nowrap; display: inline-flex; align-items: center;
    }
    .clear-btn:hover { border-color: var(--red); color: #fca5a5; }

    /* ─── Table ─── */
    .t-scroll { overflow-x: auto; width: 100%; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
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

    .empty-st { padding: 48px 24px; text-align: center; }
    .empty-st .ei { font-size: 40px; opacity: .25; margin-bottom: 12px; }
    .empty-st p { color: var(--t3); font-size: 14px; font-weight: 500; }

    /* ─── Cell styles ─── */
    .u-cell { display: flex; align-items: center; gap: 10px; }
    .u-av   {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--accent), var(--purple));
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .u-name { font-weight: 600; font-size: 14px; }
    .type-tag {
        padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;
        background: rgba(80,130,220,0.1); color: #93c5fd;
        font-family: var(--mono); border: 1px solid rgba(59,130,246,0.2);
    }
    .bal { color: #34d399; font-weight: 700; font-family: var(--mono); font-size: 14px; }
    .amt { color: #fcd34d; font-weight: 700; font-family: var(--mono); font-size: 14px; }

    .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .bd    { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .badge.ok  { background: rgba(16,185,129,0.15);  color: #6ee7b7; border: 1.5px solid rgba(16,185,129,0.4); } .badge.ok  .bd { background: #6ee7b7; }
    .badge.pnd { background: rgba(245,158,11,0.15);  color: #fbbf24; border: 1.5px solid rgba(245,158,11,0.4); } .badge.pnd .bd { background: #fbbf24; }
    .badge.no  { background: rgba(239,68,68,0.15);   color: #f87171; border: 1.5px solid rgba(239,68,68,0.4);  } .badge.no  .bd { background: #f87171; }
    .badge.ot  { background: rgba(59,130,246,0.15);  color: #93c5fd; border: 1.5px solid rgba(59,130,246,0.4); } .badge.ot  .bd { background: #93c5fd; }

    .view-btn {
        padding: 6px 13px; background: rgba(59,130,246,0.1); color: #93c5fd;
        border: 1.5px solid rgba(59,130,246,0.35); border-radius: 7px;
        font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 5px;
        transition: background .2s, color .2s, box-shadow .2s; font-family: var(--font);
    }
    .view-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 4px 14px rgba(59,130,246,0.35); }

    /* ─── Pagination ─── */
    .pg-bar {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 22px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;
    }
    .pg-info { font-size: 13px; color: var(--t3); font-weight: 500; }
    .pg-info b { color: #93c5fd; font-weight: 700; }
    .pg-nav { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pg-nav a, .pg-nav span {
        min-width: 38px; height: 38px; padding: 0 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #1a2a4a; border: 1.5px solid #3b5998;
        border-radius: var(--r-md); color: #93c5fd;
        font-size: 12px; font-weight: 700; font-family: var(--font);
        text-decoration: none; transition: background .2s, color .2s, box-shadow .2s;
    }
    .pg-nav a:hover { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,0.35); }
    .pg-nav span.active { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border-color: var(--accent); color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    .pg-nav span.disabled { opacity: .35; cursor: not-allowed; }
    .pg-nav span.dots { background: transparent; border-color: transparent; color: var(--t3); }

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
    .m-hd h2 { font-size: 17px; font-weight: 700; color: var(--t1); }
    .m-close {
        width: 30px; height: 30px; border-radius: 50%;
        background: rgba(80,130,220,0.1); border: none; color: var(--t2); font-size: 16px;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background .2s, color .2s;
    }
    .m-close:hover { background: var(--red-d); color: #fca5a5; }
    .m-body { padding: 24px; }
    .m-sec { margin-bottom: 24px; }
    .m-stitle { font-size: 10px; font-weight: 700; color: var(--t3); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 12px; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(175px,1fr)); gap: 12px; }
    .info-tile { padding: 13px 15px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--r-md); }
    .info-tile .lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--t3); margin-bottom: 5px; }
    .info-tile .val { font-size: 14px; font-weight: 600; color: var(--t1); }
    .info-tile .val.g { color: var(--green); font-family: var(--mono); }
    .info-tile .val.gn { color: var(--green); }
    .info-tile .val.rd { color: var(--red); font-weight: 700; }

    .act-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-bottom: 14px; }
    .act-b {
        padding: 10px 13px; border-radius: var(--r-md); font-size: 12px; font-weight: 700;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
        transition: background .2s, color .2s, box-shadow .2s; font-family: var(--font); border: none;
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

    /* Loading state in modal */
    .m-loading { padding: 48px; text-align: center; color: var(--t3); font-size: 14px; }
    .m-loading .spinner {
        width: 32px; height: 32px; border: 3px solid var(--border);
        border-top-color: var(--accent); border-radius: 50%;
        animation: spin .7s linear infinite; margin: 0 auto 14px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

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
    .s-btn.bl { background: var(--accent); color: #fff; } .s-btn.bl:hover { background: #2563eb; box-shadow: 0 4px 14px var(--accent-g); }
    .s-btn.pu { background: var(--purple); color: #fff; } .s-btn.pu:hover { background: #7c3aed; }
    .s-btn.gr { background: var(--green);  color: #fff; } .s-btn.gr:hover { background: #059669; }
    .s-btn.rd { background: var(--red);    color: #fff; } .s-btn.rd:hover { background: #dc2626; }

    .m-txns { overflow-x: auto; }
    .m-txns table { table-layout: auto; }  /* override the global fixed layout */
    .m-txns table th { background: var(--bg-base); }
    .m-txns table td, .m-txns table th { padding: 9px 13px; font-size: 12px; }
    .m-txns .badge { display: inline-flex !important; visibility: visible !important; }

    /* Laravel paginator override */
    .laravel-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; list-style: none; }
    .laravel-pagination li a,
    .laravel-pagination li span {
        min-width: 38px; height: 38px; padding: 0 10px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #1a2a4a; border: 1.5px solid #3b5998;
        border-radius: var(--r-md); color: #93c5fd;
        font-size: 12px; font-weight: 700; text-decoration: none;
        transition: background .2s, color .2s, box-shadow .2s;
    }
    .laravel-pagination li a:hover { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,0.35); }
    .laravel-pagination li span[aria-current] { background: linear-gradient(135deg,#3b82f6,#1d4ed8); border-color: var(--accent); color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    .laravel-pagination li span.disabled { opacity: .35; cursor: not-allowed; }

    @media (max-width: 768px) {
        .d-inner { padding: 16px 12px; }
        .stats   { grid-template-columns: 1fr 1fr; }
        .tb-r    { width: 100%; }
        .search-box input  { width: 100%; }
        .tc-search input   { width: 140px; }
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
            {{-- Global search goes to users tab with query --}}
            <form class="search-form" method="GET" action="{{ url()->current() }}">
                @if(request('tx_search')) <input type="hidden" name="tx_search" value="{{ request('tx_search') }}"> @endif
                <div class="search-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="u_search" placeholder="Search users…" value="{{ request('u_search') }}">
                </div>
            </form>
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
                    <div class="sc-val">{{ number_format($stats['total_users'] ?? 0) }}</div>
                    <span class="sc-chip">↑ Active</span>
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
                    <div class="sc-val">{{ number_format($stats['total_transactions'] ?? 0) }}</div>
                    <span class="sc-chip">↑ 8.3%</span>
                </div>
                <div class="sc-ico am">🛒</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         USERS TABLE  — full server-side
    ══════════════════════════════════════ --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Recent Users</h3>
                <span class="pill">{{ $users->total() }}</span>
            </div>
            <div class="tc-ctrl">
                <form class="filter-form" method="GET" action="{{ url()->current() }}">
                    {{-- Preserve transaction filter while changing user filter --}}
                    @if(request('tx_search')) <input type="hidden" name="tx_search" value="{{ request('tx_search') }}"> @endif
                    @if(request('tx_pp'))     <input type="hidden" name="tx_pp"     value="{{ request('tx_pp') }}">     @endif

                    <div class="tc-search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="u_search" placeholder="Search users…" value="{{ request('u_search') }}">
                    </div>
                    <select class="pp-sel" name="u_pp" onchange="this.form.submit()">
                        @foreach([10,25,50] as $n)
                            <option value="{{ $n }}" {{ request('u_pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit" class="filter-btn">Search</button>
                    @if(request('u_search'))
                        <a href="{{ url()->current() }}{{ request('tx_search') ? '?tx_search='.urlencode(request('tx_search')) : '' }}" class="clear-btn">✕ Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="t-scroll">
            @if($users->count())
            <table>
                <thead>
                    <tr>
                        <th style="width:220px;">User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Balance</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    @php
                        $ini = strtoupper(substr($u->username ?? 'U', 0, 1));
                        $bal = number_format($u->wallet->balance ?? 0, 2);
                    @endphp
                    <tr>
                        <td>
                            <div class="u-cell">
                                <div class="u-av">{{ $ini }}</div>
                                <div class="u-name">{{ $u->username ?? 'User' }}</div>
                            </div>
                        </td>
                        <td style="color:var(--t2);">{{ $u->email ?? '—' }}</td>
                        <td style="color:var(--t2);">{{ $u->phone_number ?? '—' }}</td>
                        <td><span class="bal">₦{{ $bal }}</span></td>
                        <td>
                            <button class="view-btn" onclick="openModal({{ $u->id }})">
                                👁 View
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-st">
                <div class="ei">🔍</div>
                <p>No users match your search.</p>
            </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="pg-bar">
            <div class="pg-info">
                Showing <b>{{ $users->firstItem() }}–{{ $users->lastItem() }}</b> of <b>{{ $users->total() }}</b>
            </div>
            <div>
                {{-- Append current filters to paginator links --}}
                {{ $users->appends(request()->only(['u_search','u_pp','tx_search','tx_pp']))->links('partials.admin.custom') }}
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════
         TRANSACTIONS TABLE — full server-side
    ══════════════════════════════════════ --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Recent Transactions</h3>
                <span class="pill">{{ $transactions?->total() ?? 0 }}</span>
            </div>
            <div class="tc-ctrl">
                <form class="filter-form" method="GET" action="{{ url()->current() }}">
                    {{-- Preserve user filter --}}
                    @if(request('u_search')) <input type="hidden" name="u_search" value="{{ request('u_search') }}"> @endif
                    @if(request('u_pp'))     <input type="hidden" name="u_pp"     value="{{ request('u_pp') }}">     @endif

                    <div class="tc-search">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="tx_search" placeholder="Search transactions…" value="{{ request('tx_search') }}">
                    </div>
                    <select class="pp-sel" name="tx_pp" onchange="this.form.submit()">
                        @foreach([10,25,50] as $n)
                            <option value="{{ $n }}" {{ request('tx_pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / page</option>
                        @endforeach
                    </select>
                    <button type="submit" class="filter-btn">Search</button>
                    @if(request('tx_search'))
                        <a href="{{ url()->current() }}{{ request('u_search') ? '?u_search='.urlencode(request('u_search')) : '' }}" class="clear-btn">✕ Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="t-scroll">
            @if($transactions && count($transactions) > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width:180px;">User</th>
                        <th style="width:140px;">Type</th>
                        <th style="width:130px;">Amount</th>
                        <th style="width:130px;">Status</th>
                        <th style="width:110px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr>
                            <td>
                                <div class="u-cell">
                                    <div class="u-av">{{ strtoupper(substr($tx->user?->username ?? 'N', 0, 1)) }}</div>
                                    <div class="u-name">{{ $tx->user?->username ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td><span class="type-tag">{{ $tx->type ?? '—' }}</span></td>
                            <td><span class="amt">₦{{ number_format($tx->amount ?? 0, 2) }}</span></td>
                            <td>
                                @php
                                    $s = strtolower(trim($tx->status ?? ''));
                                    $badgeClass = in_array($s, ['success','completed','successful']) ? 'ok'
                                                : (str_contains($s, 'pending') ? 'pnd'
                                                : (in_array($s, ['failed','fail','error','cancelled','canceled','expired']) || str_contains($s, 'fail') ? 'no'
                                                : 'ot'));
                                    $badgeLabel = in_array($s, ['success','completed','successful']) ? '✓ Success'
                                                : (str_contains($s, 'pending') ? '⏳ Pending'
                                                : (str_contains($s, 'fail') || in_array($s, ['cancelled','canceled','expired']) ? '✗ Failed'
                                                : '◆ ' . ucfirst($tx->status ?? 'Unknown')));
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    <span class="bd"></span>{{ $badgeLabel }}
                                </span>
                            </td>
                            <td style="color:var(--t3);font-size:12px;">
                                {{ $tx->created_at?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-st">
                <div class="ei">🔍</div>
                <p>No transactions to display.</p>
            </div>
            @endif
        </div>

        @if($transactions->hasPages())
        <div class="pg-bar">
            <div class="pg-info">
                Showing <b>{{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}</b> of <b>{{ $transactions->total() }}</b>
            </div>
            <div>
                {{ $transactions->appends(request()->only(['u_search','u_pp','tx_search','tx_pp']))->links('partials.admin.custom') }}
            </div>
        </div>
        @endif
    </div>

</div>
</div>

{{-- ══════════════════════════════════════
     USER MODAL  — AJAX only, no table rebuild
══════════════════════════════════════ --}}
<div class="m-overlay" id="userModal">
    <div class="m-box">
        <div class="m-hd">
            <h2 id="mTitle">User Details</h2>
            <button class="m-close" id="mCloseBtn">✕</button>
        </div>
        <div class="m-body" id="mBody">
            <div class="m-loading">
                <div class="spinner"></div>
                Loading user details…
            </div>
        </div>
    </div>
</div>

{{-- Modal inner template (hidden, cloned by JS) --}}
<template id="modalTpl">
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
        <div class="act-row">
            <button class="act-b rd" id="btnTogBlock" style="grid-column:1;">🔒 Block User</button>
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
        <div id="fBlock" class="sub-form" style="border-left-color:#ef4444;">
            <h4>Block/Unblock User</h4>
            <div class="f-g">
                <label class="f-l">Reason for blocking</label>
                <textarea id="blockReason" class="f-a" placeholder="Enter reason for blocking this user..."></textarea>
            </div>
            <button class="s-btn rd" id="btnDoBlock">Block User</button>
        </div>
    </div>
    <div class="m-sec">
        <div class="m-stitle">Recent Transactions</div>
        <div class="m-txns" id="mTxns"></div>
    </div>
</template>

<script>
(function () {
    'use strict';

    const BASE = @json(url('/'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ── Helpers ── */
    function esc(s) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s ?? '')));
        return d.innerHTML;
    }
    function ngn(n) {
        return Number(n ?? 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /* ── Modal open / close ── */
    let uid = null;

    window.openModal = async function (userId) {
        uid = userId;
        const modal = document.getElementById('userModal');
        const body  = document.getElementById('mBody');

        // Show spinner
        body.innerHTML = '<div class="m-loading"><div class="spinner"></div>Loading user details…</div>';
        modal.classList.add('open');

        try {
            const r = await fetch(`${BASE}/app/admin/users/${userId}`);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const { user, transactions } = await r.json();

            // Clone template
            const tpl  = document.getElementById('modalTpl');
            const node = tpl.content.cloneNode(true);

            document.getElementById('mTitle').textContent = '@' + (user.username || 'User');

            // Replace body with cloned template first so IDs exist
            body.replaceChildren(node);

            // Info tiles
            const infoGrid = body.querySelector('#mInfo');
            const tiles = [
                ['Full Name',      (user.first_name || '') + ' ' + (user.last_name || ''), null],
                ['Username',       '@' + (user.username || ''), null],
                ['Email',          user.email || '—', null],
                ['Phone',          user.phone_number || '—', null],
                ['Wallet Balance', '₦' + ngn(user.wallet?.balance), 'g'],
                ['Status',         user.is_blocked ? '🔒 BLOCKED' : '✓ Active', user.is_blocked ? 'rd' : 'gn'],
            ];
            // Add blocked info if user is blocked
            if (user.is_blocked) {
                if (user.blocked_reason) {
                    tiles.push(['Blocked Reason', user.blocked_reason, null]);
                }
                if (user.blocked_at) {
                    tiles.push(['Blocked At', new Date(user.blocked_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }), null]);
                }
            }
            tiles.push(['Joined', new Date(user.created_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }), null]);
            
            const igFrag = document.createDocumentFragment();
            tiles.forEach(([lbl, val, cls]) => {
                const tile = document.createElement('div'); tile.className = 'info-tile';
                const l = document.createElement('div'); l.className = 'lbl'; l.textContent = lbl;
                const v = document.createElement('div'); v.className = 'val' + (cls ? ' ' + cls : ''); v.textContent = val;
                tile.append(l, v); igFrag.appendChild(tile);
            });
            infoGrid.replaceChildren(igFrag);

            // Transactions mini table
            const mTxns = body.querySelector('#mTxns');
            if (!(transactions || []).length) {
                const p = document.createElement('p');
                p.style.cssText = 'color:var(--t3);text-align:center;padding:20px 0;font-size:13px;';
                p.textContent = 'No transactions found.';
                mTxns.replaceChildren(p);
            } else {
                const tbl = document.createElement('table');
                tbl.innerHTML = '<thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>';
                const tbody = document.createElement('tbody');
                const tfrag = document.createDocumentFragment();
                transactions.forEach(tx => {
                    const s   = (tx.status || '').toLowerCase().trim();
                    const cls = ['success', 'completed', 'successful'].includes(s) ? 'ok'
                            : s.includes('pending') ? 'pnd'
                            : ['failed', 'fail', 'error', 'cancelled', 'canceled', 'expired'].includes(s) || s.includes('fail') ? 'no'
                            : 'ot';
                    const lbl = ['success', 'completed', 'successful'].includes(s) ? '✓ Success'
                            : s.includes('pending') ? '⏳ Pending'
                            : s.includes('fail') || ['cancelled','canceled','expired'].includes(s) ? '✗ Failed'
                            : '◆ ' + (tx.status || 'Unknown');
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        `<td>${esc(tx.type || '—')}</td>` +
                        `<td style="font-family:var(--mono);font-weight:600;">₦${ngn(tx.amount)}</td>` +
                        `<td><span class="badge ${cls}"><span class="bd"></span>${lbl}</span></td>` +
                        `<td style="color:var(--t3);">${new Date(tx.created_at).toLocaleDateString()}</td>`;
                    tfrag.appendChild(tr);
                });
                tbody.replaceChildren(tfrag);
                tbl.appendChild(tbody);
                mTxns.replaceChildren(tbl);
            }

            // Wire up action buttons (they are now in the DOM)
            body.querySelector('#btnTogPw').addEventListener('click',    () => togForm('fPw', body));
            body.querySelector('#btnTogPin').addEventListener('click',   () => togForm('fPin', body));
            body.querySelector('#btnTogFunds').addEventListener('click', () => togForm('fFunds', body));
            
            // Update block button text based on status
            const blockBtn = body.querySelector('#btnTogBlock');
            const blockForm = body.querySelector('#fBlock');
            const blockReasonInput = body.querySelector('#blockReason');
            if (user.is_blocked) {
                blockBtn.textContent = '🔓 Unblock User';
                blockBtn.classList.remove('rd');
                blockBtn.classList.add('gr');
                blockForm.style.borderLeftColor = 'var(--green)';
                blockReasonInput.style.display = 'none';
                body.querySelector('#btnDoBlock').textContent = 'Unblock User';
            }
            blockBtn.addEventListener('click', () => togForm('fBlock', body));
            
            body.querySelector('#btnDoPw').addEventListener('click',     () => doPw(body));
            body.querySelector('#btnDoPin').addEventListener('click',    () => doPin(body));
            body.querySelector('#btnDoAdd').addEventListener('click',    () => doAdd(body));
            body.querySelector('#btnDoDed').addEventListener('click',    () => doDed(body));
            body.querySelector('#btnDoBlock').addEventListener('click',  () => doBlock(body, user));

        } catch (e) {
            body.innerHTML = `<div class="empty-st"><div class="ei">⚠️</div><p>Failed to load user: ${esc(e.message)}</p></div>`;
        }
    };

    document.getElementById('mCloseBtn').addEventListener('click', closeModal);
    document.getElementById('userModal').addEventListener('click', function (e) {
        if (e.target === e.currentTarget) closeModal();
    });

    function closeModal() {
        document.getElementById('userModal').classList.remove('open');
        uid = null;
    }

    function togForm(id, body) {
        const el  = body.querySelector('#' + id);
        const was = el.classList.contains('open');
        body.querySelectorAll('.sub-form').forEach(f => f.classList.remove('open'));
        if (!was) el.classList.add('open');
    }

    function flash(body, msg, type) {
        const el = body.querySelector('#fMsg');
        if (!el) return;
        if (!msg) { el.className = 'flash'; el.textContent = ''; return; }
        el.textContent = msg;
        el.className   = 'flash ' + type;
    }

    async function post(url, data) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    }

    async function doPw(body) {
        const p = body.querySelector('#nPw').value;
        const c = body.querySelector('#cPw').value;
        if (!p || p !== c) { flash(body, 'Passwords do not match.', 'err'); return; }
        try {
            flash(body, 'Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-password`, { password: p, password_confirmation: c });
            flash(body, d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) { body.querySelector('#nPw').value = ''; body.querySelector('#cPw').value = ''; }
        } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doPin(body) {
        const p = body.querySelector('#nPin').value;
        if (!p || p.length !== 4) { flash(body, 'PIN must be exactly 4 digits.', 'err'); return; }
        try {
            flash(body, 'Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-pin`, { pin: p });
            flash(body, d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) body.querySelector('#nPin').value = '';
        } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doAdd(body) {
        const a    = parseFloat(body.querySelector('#aAmt').value);
        const desc = body.querySelector('#aDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash(body, 'Please fill in amount and reason.', 'err'); return; }
        try {
            flash(body, 'Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/add-funds`, { amount: a, description: desc });
            flash(body, d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                body.querySelector('#aAmt').value = '';
                body.querySelector('#aDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doDed(body) {
        const a    = parseFloat(body.querySelector('#dAmt').value);
        const desc = body.querySelector('#dDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash(body, 'Please fill in amount and reason.', 'err'); return; }
        try {
            flash(body, 'Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/deduct-funds`, { amount: a, description: desc });
            flash(body, d.message || (d.success ? 'Done.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                body.querySelector('#dAmt').value = '';
                body.querySelector('#dDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doBlock(body, user) {
        if (user.is_blocked) {
            // Unblock user - no reason needed
            try {
                flash(body, 'Processing…', 'ok');
                const d = await post(`${BASE}/app/admin/users/${uid}/unblock`, {});
                flash(body, d.message || (d.success ? 'User unblocked.' : 'Failed.'), d.success ? 'ok' : 'err');
                if (d.success) {
                    setTimeout(() => openModal(uid), 900);
                }
            } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
        } else {
            // Block user - reason required
            const reason = body.querySelector('#blockReason').value.trim();
            if (!reason) { flash(body, 'Please provide a reason for blocking.', 'err'); return; }
            try {
                flash(body, 'Processing…', 'ok');
                const d = await post(`${BASE}/app/admin/users/${uid}/block`, { reason: reason });
                flash(body, d.message || (d.success ? 'User blocked.' : 'Failed.'), d.success ? 'ok' : 'err');
                if (d.success) {
                    body.querySelector('#blockReason').value = '';
                    setTimeout(() => openModal(uid), 900);
                }
            } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
        }
    }

})();
</script>

@endsection