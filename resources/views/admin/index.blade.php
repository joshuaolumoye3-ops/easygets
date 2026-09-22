@extends('layouts.admin')

@section('title', 'Admin Dashboard - Easygets')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

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
        --r1: 6px; --r2: 10px; --r3: 14px; --r4: 20px;
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

    /* ── Header ── */
    .hdr {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 16px;
        background: var(--bg-3); border: 1px solid var(--border);
        border-radius: var(--r4); padding: 18px 22px; margin-bottom: 24px;
        position: relative; overflow: hidden;
    }
    .hdr::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, var(--blue), var(--violet), transparent);
        opacity: 0.5;
    }
    .hdr-brand { display: flex; flex-direction: column; gap: 3px; }
    .hdr-brand h1 { font-size: 18px; font-weight: 800; color: var(--t1); letter-spacing: -0.4px; }
    .hdr-brand p  { font-size: 11.5px; color: var(--t3); font-weight: 500; }
    .hdr-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

    .gsearch { position: relative; }
    .gsearch input {
        width: 220px; height: 38px; padding: 0 12px 0 36px;
        background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--t1); font-size: 13px; font-family: var(--font);
        transition: border-color .2s, box-shadow .2s, width .3s;
    }
    .gsearch input::placeholder { color: var(--t3); }
    .gsearch input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); width: 260px; }
    .gsearch .ico { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--t3); pointer-events: none; }

    .svc-link {
        height: 38px; padding: 0 14px;
        display: inline-flex; align-items: center; gap: 7px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 12.5px; font-weight: 700; font-family: var(--font);
        text-decoration: none; white-space: nowrap;
        transition: background .2s, color .2s, border-color .2s, box-shadow .2s;
    }
    .svc-link:hover { background: var(--blue); color: #fff; border-color: var(--blue); box-shadow: 0 4px 16px var(--blue-glow); }

    .avatar {
        width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--blue), var(--violet));
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; color: #fff;
        box-shadow: 0 0 0 2px var(--bg-3), 0 0 0 4px var(--blue-dim);
    }

    /* ── Stats ── */
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
    .stat-card {
        background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r4);
        padding: 20px; position: relative; overflow: hidden;
        transition: border-color .25s, transform .25s, box-shadow .25s; cursor: default;
    }
    .stat-card:hover { border-color: var(--border-2); transform: translateY(-2px); box-shadow: var(--shadow-glow); }
    .stat-card .accent-line { position: absolute; top: 0; left: 20px; right: 20px; height: 2px; border-radius: 0 0 4px 4px; }
    .stat-card.blue .accent-line  { background: linear-gradient(90deg, transparent, var(--blue), transparent); }
    .stat-card.green .accent-line { background: linear-gradient(90deg, transparent, var(--emerald), transparent); }
    .stat-card.amber .accent-line { background: linear-gradient(90deg, transparent, var(--amber), transparent); }
    .stat-card.violet .accent-line { background: linear-gradient(90deg, transparent, var(--violet), transparent); }
    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--t3); }
    .stat-icon { width: 40px; height: 40px; border-radius: var(--r2); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .stat-card.blue  .stat-icon { background: var(--blue-dim);   color: var(--blue); }
    .stat-card.green .stat-icon { background: var(--emerald-dim); color: var(--emerald); }
    .stat-card.amber .stat-icon { background: var(--amber-dim);   color: var(--amber); }
    .stat-card.violet .stat-icon { background: var(--violet-dim); color: var(--violet); }
    .stat-value { font-size: 26px; font-weight: 800; color: var(--t1); letter-spacing: -0.5px; line-height: 1; margin-bottom: 10px; }
    .stat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .stat-card.blue  .stat-badge { background: var(--blue-dim);   color: var(--blue-text);   border: 1px solid rgba(79,140,255,0.2); }
    .stat-card.green .stat-badge { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.2); }
    .stat-card.amber .stat-badge { background: var(--amber-dim);   color: var(--amber-text);  border: 1px solid rgba(251,191,36,0.2); }
    .stat-card.violet .stat-badge { background: rgba(139,124,248,0.12); color: var(--violet-text); border: 1px solid rgba(139,124,248,0.25); }

    /* ── Table Card ── */
    .tcard { background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r4); overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-card); transition: border-color .25s; }
    .tcard:hover { border-color: var(--border-2); }
    .tcard-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, rgba(79,140,255,0.04) 0%, transparent 60%); }
    .tcard-title { display: flex; align-items: center; gap: 10px; }
    .tcard-title h3 { font-size: 15px; font-weight: 700; color: var(--t1); letter-spacing: -0.3px; }
    .count-pill { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: var(--blue-dim); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.2); }

    .filter-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .fsearch { position: relative; }
    .fsearch input { height: 36px; width: 190px; padding: 0 10px 0 32px; background: var(--bg-2); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--t1); font-size: 12.5px; font-family: var(--font); transition: border-color .2s, box-shadow .2s; }
    .fsearch input::placeholder { color: var(--t3); }
    .fsearch input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }
    .fsearch .ico { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--t3); pointer-events: none; }

    .per-page {
        height: 36px; padding: 0 10px; background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--blue-text); font-size: 12px; font-family: var(--font); cursor: pointer;
        transition: border-color .2s; appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%233a5578' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; padding-right: 26px;
    }
    .per-page:focus { border-color: var(--blue); outline: none; }

    .date-input, .filter-select { height: 36px; padding: 0 12px; background: var(--bg-3); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--t1); font-size: 12.5px; font-family: var(--font); transition: border-color .2s, box-shadow .2s; }
    .date-input:focus, .filter-select:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }
    .tx-range-fields { display: none; gap: 8px; margin-left: 8px; }
    .tx-range-fields.open { display: flex; }
    .range-summary { font-size: 12px; color: var(--t3); margin-bottom: 14px; font-weight: 700; padding: 0 20px; }

    .btn-filter { height: 36px; padding: 0 14px; background: var(--blue-dim); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2); font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font); white-space: nowrap; transition: background .2s, color .2s, box-shadow .2s; }
    .btn-filter:hover { background: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }
    .btn-clear { height: 36px; padding: 0 12px; background: transparent; color: var(--t3); border: 1px solid var(--border-2); border-radius: var(--r2); font-size: 12px; font-weight: 700; cursor: pointer; font-family: var(--font); white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: border-color .2s, color .2s, background .2s; }
    .btn-clear:hover { border-color: var(--rose); color: var(--rose-text); background: var(--rose-dim); }

    /* ── Table ── */
    .tscroll { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 520px; }
    thead tr { background: var(--bg-2); }
    th { padding: 10px 16px; text-align: left; font-size: 10px; font-weight: 700; color: var(--t3); text-transform: uppercase; letter-spacing: 0.9px; border-bottom: 1px solid var(--border); white-space: nowrap; }
    td { padding: 12px 16px; font-size: 13px; color: var(--t1); vertical-align: middle; border-bottom: 1px solid rgba(79,140,255,0.05); }
    tbody tr { transition: background .15s; }
    tbody tr:hover { background: rgba(79,140,255,0.05); }
    tbody tr:last-child td { border-bottom: none; }

    .empty { padding: 56px 24px; text-align: center; }
    .empty-icon { font-size: 36px; opacity: .2; margin-bottom: 12px; }
    .empty p { color: var(--t3); font-size: 13.5px; font-weight: 500; }

    .ucell { display: flex; align-items: center; gap: 10px; }
    .uav { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg, var(--blue), var(--violet)); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #fff; }
    .uname { font-weight: 600; font-size: 13.5px; }
    .type-chip { display: inline-block; padding: 3px 9px; border-radius: var(--r1); font-size: 11.5px; font-weight: 600; background: rgba(79,140,255,0.10); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.18); font-family: var(--mono); white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
    .bal { color: var(--emerald-text); font-weight: 700; font-family: var(--mono); font-size: 13.5px; }
    .amt { color: var(--amber-text);   font-weight: 700; font-family: var(--mono); font-size: 13.5px; }

    /* ── Pagination ── */
    .pgbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border); }
    .pg-info { font-size: 12.5px; color: var(--t3); font-weight: 500; }
    .pg-info b { color: var(--blue-text); font-weight: 700; }
    .laravel-pagination { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; list-style: none; }
    .laravel-pagination li a, .laravel-pagination li span { min-width: 36px; height: 36px; padding: 0 9px; display: inline-flex; align-items: center; justify-content: center; background: var(--bg-2); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--blue-text); font-size: 12px; font-weight: 700; font-family: var(--font); text-decoration: none; transition: background .2s, color .2s, box-shadow .2s; }
    .laravel-pagination li a:hover { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }
    .laravel-pagination li span[aria-current] { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 0 0 3px var(--blue-dim); }
    .laravel-pagination li span.disabled { opacity: .3; cursor: not-allowed; }

    /* ── Modal ── */
    .overlay { display: none; position: fixed; inset: 0; background: rgba(4,8,20,.85); z-index: 1000; align-items: flex-end; justify-content: center; padding: 0; backdrop-filter: blur(6px); }
    .overlay.open { display: flex; }
    @media (min-width: 640px) { .overlay { align-items: center; padding: 20px; } }
    .mbox { background: var(--bg-3); border: 1px solid var(--border-2); width: 100%; max-width: 860px; max-height: 92vh; overflow-y: auto; box-shadow: 0 32px 80px rgba(0,0,0,0.7), 0 0 0 1px var(--border); }
    @media (max-width: 639px) { .mbox { border-radius: var(--r4) var(--r4) 0 0; max-height: 88vh; } }
    @media (min-width: 640px) { .mbox { border-radius: var(--r4); animation: mUp .2s ease; } }
    @keyframes mUp { from { transform: translateY(12px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .mhd { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: var(--bg-3); z-index: 5; }
    .mhd-left { display: flex; align-items: center; gap: 10px; }
    .mhd-av { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--blue), var(--violet)); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; color: #fff; }
    .mhd h2 { font-size: 15px; font-weight: 700; color: var(--t1); }
    .mhd-sub { font-size: 11.5px; color: var(--t3); margin-top: 1px; }
    .mclose { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-2); border: 1px solid var(--border-2); color: var(--t2); font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s, color .2s, border-color .2s; }
    .mclose:hover { background: var(--rose-dim); border-color: rgba(244,86,110,0.3); color: var(--rose-text); }
    .mbody { padding: 20px; }

    .msec { margin-bottom: 22px; }
    .msec-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--t3); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .msec-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
    .itile { padding: 12px 14px; background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--r3); transition: border-color .2s; }
    .itile:hover { border-color: var(--border-2); }
    .itile .ilbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: var(--t3); margin-bottom: 5px; }
    .itile .ival { font-size: 13.5px; font-weight: 600; color: var(--t1); }
    .itile .ival.green  { color: var(--emerald); font-family: var(--mono); }
    .itile .ival.ok     { color: var(--emerald); }
    .itile .ival.danger { color: var(--rose); font-weight: 700; }

    /* ── Action buttons ── */
    .action-grid { display: grid; gap: 8px; margin-bottom: 10px; }
    .action-grid-4 { grid-template-columns: repeat(4, 1fr); }
    .action-grid-5 { grid-template-columns: repeat(5, 1fr); }

    .act-btn { height: 40px; padding: 0 12px; border-radius: var(--r2); font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px; transition: background .2s, color .2s, box-shadow .2s; font-family: var(--font); border: 1px solid; }
    .act-btn.blue   { background: var(--blue-dim);   color: var(--blue-text);   border-color: rgba(79,140,255,0.25); }
    .act-btn.violet { background: var(--violet-dim);  color: var(--violet-text); border-color: rgba(139,124,248,0.25); }
    .act-btn.green  { background: var(--emerald-dim); color: var(--emerald-text); border-color: rgba(52,217,139,0.25); }
    .act-btn.rose   { background: var(--rose-dim);   color: var(--rose-text);   border-color: rgba(244,86,110,0.25); }
    .act-btn.blue:hover   { background: var(--blue);    color: #fff; border-color: var(--blue);    box-shadow: 0 4px 16px var(--blue-glow); }
    .act-btn.violet:hover { background: var(--violet);  color: #fff; border-color: var(--violet); }
    .act-btn.green:hover  { background: var(--emerald); color: #fff; border-color: var(--emerald); }
    .act-btn.rose:hover   { background: var(--rose);    color: #fff; border-color: var(--rose); }

    .flash-msg { display: none; padding: 10px 14px; border-radius: var(--r2); font-size: 12.5px; font-weight: 600; margin-bottom: 12px; border-left: 3px solid; }
    .flash-msg.show-ok  { display: flex; align-items: center; gap: 8px; background: var(--emerald-dim); color: var(--emerald-text); border-color: var(--emerald); }
    .flash-msg.show-err { display: flex; align-items: center; gap: 8px; background: var(--rose-dim);    color: var(--rose-text);    border-color: var(--rose); }

    .subform { display: none; margin-top: 8px; padding: 16px; background: var(--bg-2); border: 1px solid var(--border); border-left: 3px solid var(--blue); border-radius: 0 var(--r3) var(--r3) 0; animation: fadeIn .18s ease; }
    .subform.open      { display: block; }
    .subform.violet-sf { border-left-color: var(--violet); }
    .subform.green-sf  { border-left-color: var(--emerald); }
    .subform.rose-sf   { border-left-color: var(--rose); }
    .subform.amber-sf  { border-left-color: var(--amber); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

    .subform h4 { font-size: 13px; font-weight: 700; color: var(--t1); margin-bottom: 14px; }
    .f2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .fg    { margin-bottom: 12px; }
    .fl    { display: block; font-size: 11px; font-weight: 600; color: var(--t2); margin-bottom: 5px; }
    .fi, .fa { width: 100%; height: 38px; padding: 0 11px; background: var(--bg-3); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--t1); font-size: 12.5px; font-family: var(--font); transition: border-color .2s, box-shadow .2s; }
    .fi::placeholder, .fa::placeholder { color: var(--t3); }
    .fi:focus, .fa:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-dim); }
    .fa { height: auto; min-height: 68px; padding: 10px 11px; resize: vertical; }
    .funds-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .fund-panel { padding: 14px; background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r3); }
    .fund-panel-label { font-size: 11px; font-weight: 700; margin-bottom: 12px; }
    .fund-panel.add .fund-panel-label { color: var(--emerald-text); }
    .fund-panel.ded .fund-panel-label { color: var(--rose-text); }

    .sbtn { width: 100%; height: 38px; border: none; border-radius: var(--r2); font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .2s, box-shadow .2s; margin-top: 4px; }
    .sbtn.blue   { background: var(--blue);    color: #fff; } .sbtn.blue:hover   { background: #3b7af0; box-shadow: 0 4px 14px var(--blue-glow); }
    .sbtn.violet { background: var(--violet);  color: #fff; } .sbtn.violet:hover { background: #7c6cf0; }
    .sbtn.green  { background: var(--emerald); color: #fff; } .sbtn.green:hover  { background: #22c77a; }
    .sbtn.rose   { background: var(--rose);    color: #fff; } .sbtn.rose:hover   { background: #e03250; }
    .sbtn.amber  { background: var(--amber);   color: #080f1c; } .sbtn.amber:hover  { background: #e5aa10; }

    .mtxns { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .mtxns table { table-layout: auto; min-width: 400px; }
    .mtxns table th { background: var(--bg); font-size: 10px; }
    .mtxns table td, .mtxns table th { padding: 8px 12px; font-size: 12px; }

    .mloading { padding: 56px 24px; text-align: center; color: var(--t3); font-size: 14px; }
    .spinner { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--border-2); border-top-color: var(--blue); animation: spin .65s linear infinite; margin: 0 auto 14px; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .view-btn { display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 12px; background: var(--blue-dim); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2); font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; font-family: var(--font); transition: background .2s, color .2s, box-shadow .2s; }
    .view-btn:hover { background: var(--blue); color: #fff; border-color: var(--blue); box-shadow: 0 4px 14px var(--blue-glow); }
    .view-btn svg { width: 13px; height: 13px; }

    .drawer-handle { display: none; width: 40px; height: 4px; border-radius: 2px; background: var(--border-2); margin: 10px auto 0; }
    @media (max-width: 639px) { .drawer-handle { display: block; } }

    /* ── Referral type toggle ── */
    .ref-type-tabs { display: flex; gap: 6px; margin-bottom: 16px; }
    .ref-tab {
        flex: 1; padding: 10px 12px; border-radius: var(--r2); border: 1px solid var(--border-2);
        background: var(--bg-3); color: var(--t2); font-size: 12px; font-weight: 700;
        cursor: pointer; text-align: center; font-family: var(--font); transition: all .2s;
    }
    .ref-tab.active-normal  { background: var(--blue-dim);  color: var(--blue-text);  border-color: rgba(79,140,255,0.4); }
    .ref-tab.active-affiliate { background: var(--amber-dim); color: var(--amber-text); border-color: rgba(251,191,36,0.4); }
    .ref-tab:hover { border-color: var(--border-3); color: var(--t1); }

    .ref-panel { display: none; }
    .ref-panel.open { display: block; }

    .referral-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
    .ref-stat { padding: 10px 12px; background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r2); text-align: center; }
    .ref-stat .rs-lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: var(--t3); margin-bottom: 4px; }
    .ref-stat .rs-val { font-size: 14px; font-weight: 800; color: var(--t1); }
    .ref-stat .rs-val.green { color: var(--emerald); font-family: var(--mono); }
    .ref-stat .rs-val.amber { color: var(--amber); }

    .info-note { padding: 10px 12px; border-radius: var(--r2); font-size: 12px; font-weight: 500; line-height: 1.5; margin-bottom: 14px; }
    .info-note.blue { background: var(--blue-dim); color: var(--blue-text); border: 1px solid rgba(79,140,255,0.2); }
    .info-note.amber { background: var(--amber-dim); color: var(--amber-text); border: 1px solid rgba(251,191,36,0.2); }

    /* ── Responsive ── */
    @media (max-width: 900px) {
        .stats { grid-template-columns: repeat(3, 1fr); }
        .action-grid-4, .action-grid-5 { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 680px) {
        .stats { grid-template-columns: 1fr 1fr; }
        .stat-card:last-child { grid-column: 1 / -1; }
        .hdr-right { width: 100%; }
        .gsearch input { width: 100%; flex: 1; }
        .gsearch { flex: 1; }
    }
    @media (max-width: 540px) {
        .inner { padding: 16px 14px; }
        .stats { grid-template-columns: 1fr; }
        .stat-card:last-child { grid-column: auto; }
        .stat-value { font-size: 22px; }
        .f2col, .funds-grid { grid-template-columns: 1fr; }
        .action-grid-4, .action-grid-5 { grid-template-columns: 1fr 1fr; }
        .filter-form { width: 100%; }
        .fsearch { flex: 1; }
        .fsearch input { width: 100%; }
        .tcard-head { flex-direction: column; align-items: flex-start; gap: 10px; }
        .mbody { padding: 14px; }
        .subform { padding: 12px; }
        .pg-info { display: none; }
        .referral-stats-grid { grid-template-columns: 1fr 1fr; }
        .ref-type-tabs { flex-direction: column; }
    }
    @media (max-width: 400px) {
        .action-grid-4, .action-grid-5 { grid-template-columns: 1fr; }
    }
    .users-table { table-layout: auto; min-width: 0; width: 100%; }
    .users-table th, .users-table td { white-space: nowrap; }
    @media (max-width: 640px) { .col-phone { display: none; } .col-email { display: none; } .users-table { min-width: 0; } }
    @media (max-width: 820px) { .col-phone { display: none; } }
</style>

<div class="dash">
<div class="inner">

    {{-- Header --}}
    <div class="hdr">
        <div class="hdr-brand">
            <h1>Admin Dashboard</h1>
            <p>{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="hdr-right">
            <form method="GET" action="{{ url()->current() }}" style="display:contents;">
                @if(request('tx_search'))<input type="hidden" name="tx_search" value="{{ request('tx_search') }}">@endif
                <div class="gsearch">
                    <svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="u_search" placeholder="Search users…" value="{{ request('u_search') }}">
                </div>
            </form>
            <a href="{{ route('admin.referral.withdrawals') }}" class="svc-link" style="background:rgba(251,191,36,0.10);color:#fcd34d;border-color:rgba(251,191,36,0.25);">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                Partners
                @php $pendingW = \App\Models\PartnerWithdrawal::where('status','pending')->count(); @endphp
                @if($pendingW > 0)
                    <span style="background:#f4566e;color:#fff;border-radius:99px;font-size:10px;font-weight:800;padding:2px 7px;margin-left:2px;">{{ $pendingW }}</span>
                @endif
            </a>
            <a href="{{ route('admin.manage-services') }}" class="svc-link">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                Services
            </a>
            <a href="{{ route('admin.giftcards.index') }}" class="svc-link">🎁 Gift Cards</a>
            <div class="avatar">A</div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat-card blue">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Total Users</div>
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_users'] ?? 0) }}</div>
            <span class="stat-badge">↑ Active</span>
        </div>
        <div class="stat-card green">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
            <div class="stat-value">₦{{ number_format($stats['total_revenue'] ?? 0, 0) }}</div>
            <span class="stat-badge">↑ 11%</span>
        </div>
        <div class="stat-card amber">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Transactions</div>
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_transactions'] ?? 0) }}</div>
            <span class="stat-badge">↑ 8.3%</span>
        </div>
        <div class="stat-card violet">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Selected period revenue</div>
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v1m0 0v8m0-8h0"/></svg></div>
            </div>
            <div class="stat-value">₦{{ number_format($transactionsTotalAmount ?? 0, 0) }}</div>
        </div>
        <div class="stat-card violet">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Total User Balance</div>
                <div class="stat-icon"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5a2.5 2.5 0 012.5-2.5h13A2.5 2.5 0 0121 10.5v7a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 17.5v-7zm3.5-5h11a2.5 2.5 0 012.5 2.5v1.5H4v-1.5A2.5 2.5 0 016.5 5.5z"/></svg></div>
            </div>
            <div class="stat-value">₦{{ number_format($stats['total_wallet_balance'] ?? 0, 2) }}</div>
            <span class="stat-badge">All wallets</span>
        </div>
    </div>

    {{-- ── Users Table ── --}}
    <div class="tcard">
        <div class="tcard-head">
            <div class="tcard-title">
                <h3>Users</h3>
                <span class="count-pill">{{ $users->total() }}</span>
            </div>
            <div>
                <form class="filter-form" method="GET" action="{{ url()->current() }}">
                    @if(request('tx_search'))<input type="hidden" name="tx_search" value="{{ request('tx_search') }}">@endif
                    @if(request('tx_pp'))<input type="hidden" name="tx_pp" value="{{ request('tx_pp') }}">@endif
                    @if(request('tx_range'))<input type="hidden" name="tx_range" value="{{ request('tx_range') }}">@endif
                    @if(request('tx_start'))<input type="hidden" name="tx_start" value="{{ request('tx_start') }}">@endif
                    @if(request('tx_end'))<input type="hidden" name="tx_end" value="{{ request('tx_end') }}">@endif
                    @if(request('tx_service'))<input type="hidden" name="tx_service" value="{{ request('tx_service') }}">@endif
                    @if(request('tx_provider'))<input type="hidden" name="tx_provider" value="{{ request('tx_provider') }}">@endif
                    <div class="fsearch">
                        <svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="u_search" placeholder="Search users…" value="{{ request('u_search') }}">
                    </div>
                    <select class="per-page filter-select" name="u_activity" onchange="this.form.submit()">
                        <option value="created" {{ request('u_activity', 'created') === 'created' ? 'selected' : '' }}>Created users</option>
                        <option value="active" {{ request('u_activity') === 'active' ? 'selected' : '' }}>Active users</option>
                    </select>
                    <select class="per-page" name="u_pp" onchange="this.form.submit()">
                        @foreach([10,25,50] as $n)
                            <option value="{{ $n }}" {{ request('u_pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / pg</option>
                        @endforeach
                    </select>
                    <select class="per-page filter-select" name="u_range" id="uRangeSelect" onchange="handleDateRangeChange('uRangeSelect', 'u')">
                        <option value="" {{ !request('u_range') ? 'selected' : '' }}>All dates</option>
                        <option value="today" {{ request('u_range') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ request('u_range') === 'this_week' ? 'selected' : '' }}>This week</option>
                        <option value="last_week" {{ request('u_range') === 'last_week' ? 'selected' : '' }}>Last week</option>
                        <option value="this_month" {{ request('u_range') === 'this_month' ? 'selected' : '' }}>This month</option>
                        <option value="last_month" {{ request('u_range') === 'last_month' ? 'selected' : '' }}>Last month</option>
                        <option value="this_year" {{ request('u_range') === 'this_year' ? 'selected' : '' }}>This year</option>
                        <option value="custom" {{ request('u_range') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                    <div class="tx-range-fields {{ request('u_range') === 'custom' ? 'open' : '' }}" id="uRangeFields">
                        <input type="date" class="date-input" name="u_start" id="uStart" value="{{ request('u_start') }}">
                        <input type="date" class="date-input" name="u_end" id="uEnd" value="{{ request('u_end') }}">
                    </div>
                    <button type="submit" class="btn-filter">Search</button>
                    @if(request('u_search') || request('u_range') || request('u_start') || request('u_end') || request('u_activity'))
                        <a href="{{ url()->current() }}{{ !empty(request()->except(['u_search','u_pp','u_range','u_start','u_end','u_activity'])) ? '?'.http_build_query(request()->except(['u_search','u_pp','u_range','u_start','u_end','u_activity'])) : '' }}" class="btn-clear">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>
        <div class="tscroll">
            @if($users->count())
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th class="col-email">Email</th>
                        <th class="col-phone">Phone</th>
                        <th>Balance</th>
                        <th style="width:100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    @php $ini = strtoupper(substr($u->username ?? 'U', 0, 1)); $bal = number_format($u->wallet->balance ?? 0, 2); @endphp
                    <tr>
                        <td>
                            <div class="ucell">
                                <div class="uav">{{ $ini }}</div>
                                <div>
                                    <div class="uname">{{ $u->username ?? 'User' }}</div>
                                    @if($u->is_partner ?? false)
                                        <span style="font-size:10px;font-weight:700;color:#fbbf24;background:rgba(251,191,36,0.12);border:1px solid rgba(251,191,36,0.25);padding:1px 6px;border-radius:4px;">⭐ Affiliate</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="col-email" style="color:var(--t2); font-size:13px;">{{ $u->email ?? '—' }}</td>
                        <td class="col-phone" style="color:var(--t2); font-size:13px;">{{ $u->phone_number ?? '—' }}</td>
                        <td><span class="bal">₦{{ $bal }}</span></td>
                        <td>
                            <button class="view-btn" onclick="openModal({{ $u->id }})">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty"><div class="empty-icon">🔍</div><p>No users match your search.</p></div>
            @endif
        </div>
        @if(!empty($uDateLabel))
        <div class="range-summary">Showing <b>{{ number_format($users->total()) }}</b> user{{ $users->total() === 1 ? '' : 's' }} {{ request('u_activity', 'created') === 'active' ? 'active' : 'created' }} during <b>{{ $uDateLabel }}</b></div>
        @endif
        @if($users->hasPages())
        <div class="pgbar">
            <div class="pg-info">Showing <b>{{ $users->firstItem() }}–{{ $users->lastItem() }}</b> of <b>{{ $users->total() }}</b></div>
            <div>{{ $users->appends(request()->only(['u_search','u_pp','u_activity','tx_search','tx_pp','tx_service','tx_provider','u_range','u_start','u_end','tx_range','tx_start','tx_end']))->links('partials.admin.custom') }}</div>
        </div>
        @endif
    </div>

    {{-- ── Transactions Table ── --}}
    <div class="tcard">
        <div class="tcard-head">
            <div class="tcard-title">
                <h3>Transactions</h3>
                <span class="count-pill">{{ $transactions?->total() ?? 0 }}</span>
            </div>
            <div>
                <form class="filter-form" method="GET" action="{{ url()->current() }}">
                    @if(request('u_search'))<input type="hidden" name="u_search" value="{{ request('u_search') }}">@endif
                    @if(request('u_pp'))<input type="hidden" name="u_pp" value="{{ request('u_pp') }}">@endif
                    @if(request('u_range'))<input type="hidden" name="u_range" value="{{ request('u_range') }}">@endif
                    @if(request('u_start'))<input type="hidden" name="u_start" value="{{ request('u_start') }}">@endif
                    @if(request('u_end'))<input type="hidden" name="u_end" value="{{ request('u_end') }}">@endif
                    @if(request('tx_provider'))<input type="hidden" name="tx_provider" value="{{ request('tx_provider') }}">@endif
                    <div class="fsearch">
                        <svg class="ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="tx_search" placeholder="Search transactions…" value="{{ request('tx_search') }}">
                    </div>
                    <select class="per-page filter-select" name="tx_service" id="txServiceSelect" onchange="this.form.submit()">
                        <option value="">All services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->slug }}" {{ request('tx_service') == $service->slug ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <select class="per-page filter-select" name="tx_provider" id="txProviderSelect" onchange="this.form.submit()">
                        <option value="">All providers</option>
                        @foreach($txProviders as $provider)
                            <option value="{{ $provider }}" {{ request('tx_provider') == $provider ? 'selected' : '' }}>{{ $provider }}</option>
                        @endforeach
                    </select>
                    <select class="per-page filter-select" name="tx_range" id="txRangeSelect" onchange="handleDateRangeChange('txRangeSelect', 'tx')">
                        <option value="" {{ !request('tx_range') ? 'selected' : '' }}>All dates</option>
                        <option value="today" {{ request('tx_range') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ request('tx_range') === 'this_week' ? 'selected' : '' }}>This week</option>
                        <option value="last_week" {{ request('tx_range') === 'last_week' ? 'selected' : '' }}>Last week</option>
                        <option value="this_month" {{ request('tx_range') === 'this_month' ? 'selected' : '' }}>This month</option>
                        <option value="last_month" {{ request('tx_range') === 'last_month' ? 'selected' : '' }}>Last month</option>
                        <option value="this_year" {{ request('tx_range') === 'this_year' ? 'selected' : '' }}>This year</option>
                        <option value="custom" {{ request('tx_range') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                    <div class="tx-range-fields {{ request('tx_range') === 'custom' ? 'open' : '' }}" id="txRangeFields">
                        <input type="date" class="date-input" name="tx_start" id="txStart" value="{{ request('tx_start') }}">
                        <input type="date" class="date-input" name="tx_end" id="txEnd" value="{{ request('tx_end') }}">
                    </div>
                    <select class="per-page" name="tx_pp" onchange="this.form.submit()">
                        @foreach([10,25,50] as $n)
                            <option value="{{ $n }}" {{ request('tx_pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / pg</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-filter">Search</button>
                    @if(request('tx_search') || request('tx_range') || request('tx_start') || request('tx_end'))
                        <a href="{{ url()->current() }}{{ !empty(request()->except(['tx_search','tx_range','tx_start','tx_end'])) ? '?'.http_build_query(request()->except(['tx_search','tx_range','tx_start','tx_end'])) : '' }}" class="btn-clear">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>
        @if(!empty($txDateLabel) || request('tx_provider'))
        <div class="range-summary">
            Showing <b>{{ number_format($transactionsTotalCount) }}</b> transaction{{ $transactionsTotalCount === 1 ? '' : 's' }} for <b>{{ $txServiceLabel }}</b> · <b>{{ $txProviderLabel }}</b> · <b>{{ $txDateLabel }}</b> · Total amount ₦{{ number_format($transactionsTotalAmount, 2) }}
        </div>
        @endif
        <div class="tscroll">
            @if($transactions && count($transactions) > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width:160px;">User</th>
                        <th style="width:120px;">Type</th>
                        <th style="width:140px;">Provider</th>
                        <th style="width:110px;">Tx ID</th>
                        <th style="width:220px;">Details</th>
                        <th style="width:140px;">Amount</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:110px;">Action</th>
                        <th style="width:110px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                    @php
                        $s = strtolower(trim($tx->status ?? ''));
                        if (in_array($s, ['success','completed','successful'])) {
                            $chipColor = '#34d98b'; $chipBg = 'rgba(52,217,139,0.12)'; $chipBdr = 'rgba(52,217,139,0.25)'; $chipLbl = 'Success';
                        } elseif (str_contains($s, 'pending')) {
                            $chipColor = '#fbbf24'; $chipBg = 'rgba(251,191,36,0.12)'; $chipBdr = 'rgba(251,191,36,0.25)'; $chipLbl = 'Pending';
                        } elseif (in_array($s, ['failed','fail','error','cancelled','canceled','expired']) || str_contains($s, 'fail')) {
                            $chipColor = '#f4566e'; $chipBg = 'rgba(244,86,110,0.12)'; $chipBdr = 'rgba(244,86,110,0.25)'; $chipLbl = 'Failed';
                        } else {
                            $chipColor = '#93b8ff'; $chipBg = 'rgba(79,140,255,0.12)'; $chipBdr = 'rgba(79,140,255,0.25)'; $chipLbl = ucfirst($tx->status ?? 'Unknown');
                        }
                        $txResponse = is_array($tx->response) ? $tx->response : json_decode($tx->response ?? '[]', true) ?? [];
                        $details = [];
                        if (strtolower($tx->type ?? '') === 'activation') {
                            if (!empty($tx->service_name))  $details[] = 'Plan: ' . $tx->service_name;
                            if (!empty($tx->country_name))  $details[] = 'Country: ' . $tx->country_name . ($tx->country_code ? ' (' . $tx->country_code . ')' : '');
                            if (!empty($tx->operator_name)) $details[] = 'Operator: ' . $tx->operator_name;
                            if (!empty($tx->phone))         $details[] = 'Number: ' . $tx->phone;
                            elseif (!empty($txResponse['number'])) $details[] = 'Number: ' . $txResponse['number'];
                        } elseif (strtolower($tx->type ?? '') === 'social_boost') {
                            if (!empty($txResponse['link']))      $details[] = 'Link: ' . $txResponse['link'];
                            if (!empty($txResponse['quantity']))  $details[] = 'Quantity: ' . number_format($txResponse['quantity']);
                            if (!empty($tx->service_name))        $details[] = 'Package: ' . $tx->service_name;
                        } else {
                            if (!empty($tx->description)) $details[] = $tx->description;
                        }
                    @endphp
                    <tr>
                        <td><div class="ucell"><div class="uav">{{ strtoupper(substr($tx->user?->username ?? 'N', 0, 1)) }}</div><span class="uname">{{ $tx->user?->username ?? 'N/A' }}</span></div></td>
                        <td><span class="type-chip">{{ $tx->type ?? '—' }}</span></td>
                        <td>{{ $tx->provider ?? ucfirst($tx->channel ?? '—') }}</td>
                        <td><span class="tx-id">#{{ $tx->id }}</span></td>
                        <td>
                            @if(count($details) > 0)
                                <div class="detail-list">@foreach($details as $detail)<div>{{ $detail }}</div>@endforeach</div>
                            @else —
                            @endif
                        </td>
                        <td><span class="amt">₦{{ number_format($tx->amount ?? 0, 2) }}</span></td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;font-size:11.5px;font-weight:700;white-space:nowrap;background:{{ $chipBg }};color:{{ $chipColor }};border:1px solid {{ $chipBdr }};">
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $chipColor }};flex-shrink:0;"></span>
                                {{ $chipLbl }}
                            </span>
                        </td>
                        <td>
                            @if(strtolower($tx->type ?? '') === 'social_boost')
                                <button type="button"
                                        class="social-tx-btn"
                                        data-id="{{ $tx->id }}"
                                        data-user="{{ $tx->user?->username ?? 'Unknown user' }}"
                                        data-service="{{ $tx->service_name ?? 'Social boost' }}"
                                        data-provider="{{ $tx->provider ?? ucfirst($tx->channel ?? '—') }}"
                                        data-amount="{{ $tx->amount ?? 0 }}"
                                        data-status="{{ strtolower($tx->status ?? '') }}"
                                        data-reference="{{ $tx->reference ?? '' }}"
                                        data-request="{{ $tx->request_id ?? '' }}"
                                        style="background:rgba(168,85,247,0.12);color:#8b5cf6;border:1px solid rgba(168,85,247,0.25);padding:6px 10px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">
                                    Manage
                                </button>
                            @else
                                <span style="color:var(--t3);font-size:11px;">—</span>
                            @endif
                        </td>
                        <td style="color:var(--t3);font-size:12px;">{{ $tx->created_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty"><div class="empty-icon">📭</div><p>No transactions to display.</p></div>
            @endif
        </div>
        @if($transactions->hasPages())
        <div class="pgbar">
            <div class="pg-info">Showing <b>{{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}</b> of <b>{{ $transactions->total() }}</b></div>
            <div>{{ $transactions->appends(request()->only(['u_search','u_pp','tx_search','tx_pp','tx_service','tx_provider','tx_range','tx_start','tx_end','u_range','u_start','u_end']))->links('partials.admin.custom') }}</div>
        </div>
        @endif
    </div>

</div>
</div>

{{-- ── Modal ── --}}
<div class="overlay" id="userModal">
    <div class="mbox" id="mboxEl">
        <div class="drawer-handle"></div>
        <div class="mhd">
            <div class="mhd-left">
                <div class="mhd-av" id="mhdAv">U</div>
                <div>
                    <h2 id="mTitle">User Details</h2>
                    <div class="mhd-sub" id="mSub"></div>
                </div>
            </div>
            <button class="mclose" id="mCloseBtn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mbody" id="mBody">
            <div class="mloading"><div class="spinner"></div>Loading…</div>
        </div>
    </div>
</div>

{{-- Social transaction action modal --}}
<div class="overlay" id="socialTxModal">
    <div class="mbox" id="socialTxModalBox" style="max-width:520px;">
        <div class="drawer-handle"></div>
        <div class="mhd">
            <div class="mhd-left">
                <div class="mhd-av" id="socialTxAvatar">S</div>
                <div>
                    <h2 id="socialTxTitle">Social Order Action</h2>
                    <div class="mhd-sub" id="socialTxSubtitle">Manage pending social purchase</div>
                </div>
            </div>
            <button class="mclose" id="socialTxCloseBtn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mbody" id="socialTxBody" style="padding:20px 20px 24px;">
            <div class="mloading"><div class="spinner"></div>Preparing social order actions…</div>
        </div>
    </div>
</div>

{{-- Modal template --}}
<template id="modalTpl">
    <div class="msec">
        <div class="msec-label">Profile</div>
        <div class="info-grid" id="mInfo"></div>
    </div>

    <div class="msec">
        <div class="msec-label">Admin Actions</div>

        <div class="action-grid action-grid-5">
            <button class="act-btn blue" id="btnTogPw">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Password
            </button>
            <button class="act-btn violet" id="btnTogPin">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                PIN
            </button>
            <button class="act-btn green" id="btnTogFunds">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Wallet
            </button>
            <button class="act-btn rose" id="btnTogBlock">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Block
            </button>
            <button class="act-btn" id="btnTogReferral" style="background:rgba(251,191,36,0.12);color:#fcd34d;border-color:rgba(251,191,36,0.25);">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Referral
            </button>
        </div>

        <div id="fMsg" class="flash-msg"></div>

        {{-- Password subform --}}
        <div id="fPw" class="subform">
            <h4>Change Password</h4>
            <div class="f2col">
                <div class="fg"><label class="fl">New Password</label><input type="password" id="nPw" class="fi" placeholder="••••••••"></div>
                <div class="fg"><label class="fl">Confirm Password</label><input type="password" id="cPw" class="fi" placeholder="••••••••"></div>
            </div>
            <button class="sbtn blue" id="btnDoPw">Update Password</button>
        </div>

        {{-- PIN subform --}}
        <div id="fPin" class="subform violet-sf">
            <h4>Change Transaction PIN</h4>
            <div class="fg" style="max-width:180px;">
                <label class="fl">4-digit PIN</label>
                <input type="password" id="nPin" class="fi" placeholder="••••" maxlength="4" inputmode="numeric">
            </div>
            <button class="sbtn violet" id="btnDoPin">Update PIN</button>
        </div>

        {{-- Wallet subform --}}
        <div id="fFunds" class="subform green-sf">
            <h4>Wallet Management</h4>
            <div class="funds-grid">
                <div class="fund-panel add">
                    <div class="fund-panel-label">➕ Add Funds</div>
                    <div class="fg"><label class="fl">Amount (₦)</label><input type="number" id="aAmt" class="fi" placeholder="0.00" min="0" step="0.01"></div>
                    <div class="fg"><label class="fl">Reason</label><textarea id="aDesc" class="fa" placeholder="Reason for adding funds…"></textarea></div>
                    <button class="sbtn green" id="btnDoAdd">Add Funds</button>
                </div>
                <div class="fund-panel ded">
                    <div class="fund-panel-label">➖ Deduct Funds</div>
                    <div class="fg"><label class="fl">Amount (₦)</label><input type="number" id="dAmt" class="fi" placeholder="0.00" min="0" step="0.01"></div>
                    <div class="fg"><label class="fl">Reason</label><textarea id="dDesc" class="fa" placeholder="Reason for deducting funds…"></textarea></div>
                    <button class="sbtn rose" id="btnDoDed">Deduct Funds</button>
                </div>
            </div>
        </div>

        {{-- Block subform --}}
        <div id="fBlock" class="subform rose-sf">
            <h4>Block / Unblock User</h4>
            <div class="fg">
                <label class="fl" id="blockReasonLabel">Reason for blocking</label>
                <textarea id="blockReason" class="fa" placeholder="Enter reason for blocking this user…"></textarea>
            </div>
            <button class="sbtn rose" id="btnDoBlock">Block User</button>
        </div>

        {{-- ── Referral / Affiliate subform ── --}}
        <div id="fReferral" class="subform amber-sf">
            <h4 style="color:var(--amber-text); margin-bottom:16px;">Referral &amp; Affiliate Settings</h4>

            {{-- Stats row --}}
            <div class="referral-stats-grid" id="refStatsGrid">
                <div class="ref-stat"><div class="rs-lbl">Ref. Balance</div><div class="rs-val green" id="rsBal">—</div></div>
                <div class="ref-stat"><div class="rs-lbl">Total Earned</div><div class="rs-val green" id="rsEarned">—</div></div>
                <div class="ref-stat"><div class="rs-lbl">Referrals</div><div class="rs-val amber" id="rsCount">—</div></div>
                <div class="ref-stat"><div class="rs-lbl">Referred By</div><div class="rs-val" id="rsRef">—</div></div>
                <div class="ref-stat"><div class="rs-lbl">Current Type</div><div class="rs-val" id="rsType">—</div></div>
                <div class="ref-stat"><div class="rs-lbl">Commission</div><div class="rs-val" id="rsRate">—</div></div>
            </div>

            {{-- Type switcher tabs --}}
            <div class="ref-type-tabs">
                <button type="button" class="ref-tab" id="tabNormal" onclick="switchRefTab('normal')">
                    👤 Normal Referral
                </button>
                <button type="button" class="ref-tab" id="tabAffiliate" onclick="switchRefTab('affiliate')">
                    ⭐ Affiliate (Partner)
                </button>
            </div>

            {{-- Normal referral panel --}}
            <div class="ref-panel" id="panelNormal">
                <div class="info-note blue">
                    <strong>Normal Referral</strong> — earns the global commission rate ({{ \App\Models\ReferralSetting::globalPercentage() }}%) on each deposit their referred users make. They cannot withdraw their referral balance directly.
                </div>
                <div id="normalCurrentMsg" style="font-size:12px;color:var(--t3);text-align:center;padding:8px 0;"></div>
                <button class="sbtn blue" id="btnMakeNormal" style="margin-top:8px;">Set as Normal Referral</button>
            </div>

            {{-- Affiliate panel --}}
            <div class="ref-panel" id="panelAffiliate">
                <div class="info-note amber">
                    <strong>Affiliate (Partner)</strong> — earns a custom commission rate, can request withdrawals of their referral balance, and is shown as ⭐ in the system.
                </div>
                <div class="f2col" style="margin-top:12px;">
                    <div class="fg">
                        <label class="fl">Commission % <span style="color:var(--t3);font-weight:400;">(their personal rate)</span></label>
                        <input type="number" id="affiliatePct" class="fi" placeholder="e.g. 5" min="0" max="100" step="0.1">
                    </div>
                    <div class="fg">
                        <label class="fl">Min Withdrawal (₦)</label>
                        <input type="number" id="affiliateMinW" class="fi" placeholder="e.g. 5000" min="0" step="0.01">
                    </div>
                </div>
                <button class="sbtn amber" id="btnMakeAffiliate" style="margin-top:4px;">Save Affiliate Settings</button>
            </div>
        </div>
    </div>

    <div class="msec">
        <div class="msec-label">Recent Transactions</div>
        <div class="mtxns" id="mTxns"></div>
    </div>
</template>

<script>
(function () {
    'use strict';

    const BASE = @json(url('/'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const GLOBAL_PCT = {{ \App\Models\ReferralSetting::globalPercentage() }};
    let uid = null;

    // ── helpers ──────────────────────────────────────────────────────────
    function esc(s) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s ?? '')));
        return d.innerHTML;
    }
    function ngn(n) {
        return Number(n ?? 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function flash(body, msg, type) {
        const el = body.querySelector('#fMsg');
        if (!el) return;
        if (!msg) { el.className = 'flash-msg'; el.textContent = ''; return; }
        el.textContent = msg;
        el.className = 'flash-msg ' + (type === 'ok' ? 'show-ok' : 'show-err');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
    function togForm(id, body) {
        const el  = body.querySelector('#' + id);
        const was = el.classList.contains('open');
        body.querySelectorAll('.subform').forEach(f => f.classList.remove('open'));
        if (!was) el.classList.add('open');
    }

    // ── Referral tab switcher (global so onclick can reach it) ────────────
    window.switchRefTab = function(tab) {
        const body = document.getElementById('mBody');
        const tabN = body.querySelector('#tabNormal');
        const tabA = body.querySelector('#tabAffiliate');
        const panN = body.querySelector('#panelNormal');
        const panA = body.querySelector('#panelAffiliate');
        if (!tabN) return;

        tabN.classList.remove('active-normal', 'active-affiliate');
        tabA.classList.remove('active-normal', 'active-affiliate');
        panN.classList.remove('open');
        panA.classList.remove('open');

        if (tab === 'normal') {
            tabN.classList.add('active-normal');
            panN.classList.add('open');
        } else {
            tabA.classList.add('active-affiliate');
            panA.classList.add('open');
        }
    };

    // ── Open modal ────────────────────────────────────────────────────────
    window.openModal = async function(userId) {
        uid = userId;
        const modal = document.getElementById('userModal');
        const body  = document.getElementById('mBody');
        document.getElementById('mTitle').textContent = 'Loading…';
        document.getElementById('mSub').textContent = '';
        document.getElementById('mhdAv').textContent = '…';
        body.innerHTML = '<div class="mloading"><div class="spinner"></div>Loading user details…</div>';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        try {
            const r = await fetch(`${BASE}/app/admin/users/${userId}`);
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const { user, transactions } = await r.json();

            const ini = (user.username || 'U').charAt(0).toUpperCase();
            document.getElementById('mTitle').textContent = '@' + (user.username || 'User')
                + (user.is_partner ? ' ⭐' : '');
            document.getElementById('mSub').textContent  = user.email || '';
            document.getElementById('mhdAv').textContent = ini;

            const tpl  = document.getElementById('modalTpl');
            const node = tpl.content.cloneNode(true);
            body.replaceChildren(node);

            // ── Info tiles ──────────────────────────────────────────────
            const grid = body.querySelector('#mInfo');
            const tiles = [
                ['Full Name',  (user.first_name || '') + ' ' + (user.last_name || ''), null],
                ['Username',   '@' + (user.username || ''), null],
                ['Email',      user.email || '—', null],
                ['Phone',      user.phone_number || '—', null],
                ['Wallet Bal', '₦' + ngn(user.wallet?.balance), 'green'],
                ['Ref. Bal',   '₦' + ngn(user.referral_balance), 'green'],
                ['Status',     user.is_blocked ? 'Blocked' : 'Active', user.is_blocked ? 'danger' : 'ok'],
                ['Type',       user.is_partner ? '⭐ Affiliate' : '👤 Normal', null],
            ];
            if (user.is_blocked) {
                if (user.blocked_reason) tiles.push(['Block Reason', user.blocked_reason, null]);
                if (user.blocked_at)     tiles.push(['Blocked At', new Date(user.blocked_at).toLocaleString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }), null]);
            }
            tiles.push(['Joined', new Date(user.created_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }), null]);

            const frag = document.createDocumentFragment();
            tiles.forEach(([lbl, val, cls]) => {
                const tile = document.createElement('div'); tile.className = 'itile';
                const l = document.createElement('div'); l.className = 'ilbl'; l.textContent = lbl;
                const v = document.createElement('div'); v.className = 'ival' + (cls ? ' ' + cls : ''); v.textContent = val;
                tile.append(l, v); frag.appendChild(tile);
            });
            grid.replaceChildren(frag);

            // ── Transactions mini-table ─────────────────────────────────
            const mTxns = body.querySelector('#mTxns');
            if (!(transactions || []).length) {
                const p = document.createElement('p');
                p.style.cssText = 'color:var(--t3);text-align:center;padding:20px;font-size:13px;';
                p.textContent = 'No transactions found.';
                mTxns.replaceChildren(p);
            } else {
                const tbl = document.createElement('table');
                tbl.innerHTML = '<thead><tr><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>';
                const tbody = document.createElement('tbody');
                const tfrag = document.createDocumentFragment();
                transactions.forEach(tx => {
                    const s = (tx.status || '').toLowerCase().trim();
                    let col, bg, bdr, lbl;
                    if (['success','completed','successful'].includes(s)) {
                        col='#34d98b'; bg='rgba(52,217,139,0.12)'; bdr='rgba(52,217,139,0.25)'; lbl='Success';
                    } else if (s.includes('pending')) {
                        col='#fbbf24'; bg='rgba(251,191,36,0.12)'; bdr='rgba(251,191,36,0.25)'; lbl='Pending';
                    } else if (['failed','fail','error','cancelled','canceled','expired'].includes(s) || s.includes('fail')) {
                        col='#f4566e'; bg='rgba(244,86,110,0.12)'; bdr='rgba(244,86,110,0.25)'; lbl='Failed';
                    } else {
                        col='#93b8ff'; bg='rgba(79,140,255,0.12)'; bdr='rgba(79,140,255,0.25)'; lbl=tx.status||'Unknown';
                    }
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        `<td style="color:var(--t1);">${esc(tx.type||'—')}</td>` +
                        `<td style="color:var(--amber-text);font-family:var(--mono);font-weight:600;">₦${ngn(tx.amount)}</td>` +
                        `<td><span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:5px;font-size:11px;font-weight:700;background:${bg};color:${col};border:1px solid ${bdr};"><span style="width:5px;height:5px;border-radius:50%;background:${col};flex-shrink:0;"></span>${lbl}</span></td>` +
                        `<td style="color:var(--t3);">${new Date(tx.created_at).toLocaleDateString()}</td>`;
                    tfrag.appendChild(tr);
                });
                tbody.replaceChildren(tfrag);
                tbl.appendChild(tbody);
                mTxns.replaceChildren(tbl);
            }

            // ── Wire standard action buttons ────────────────────────────
            body.querySelector('#btnTogPw').addEventListener('click',    () => togForm('fPw', body));
            body.querySelector('#btnTogPin').addEventListener('click',   () => togForm('fPin', body));
            body.querySelector('#btnTogFunds').addEventListener('click', () => togForm('fFunds', body));

            // Block/Unblock setup
            const blockBtn    = body.querySelector('#btnTogBlock');
            const blockForm   = body.querySelector('#fBlock');
            const blockReason = body.querySelector('#blockReason');
            const doBlockBtn  = body.querySelector('#btnDoBlock');
            const rlLabel     = body.querySelector('#blockReasonLabel');
            if (user.is_blocked) {
                blockBtn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg> Unblock`;
                blockBtn.classList.replace('rose', 'green');
                blockForm.classList.add('green-sf');
                blockForm.classList.remove('rose-sf');
                rlLabel.style.display = 'none';
                blockReason.style.display = 'none';
                doBlockBtn.textContent = 'Unblock User';
                doBlockBtn.className = 'sbtn green';
            }
            blockBtn.addEventListener('click', () => togForm('fBlock', body));

            // ── Referral button: open panel + load data ──────────────────
            body.querySelector('#btnTogReferral').addEventListener('click', async () => {
                togForm('fReferral', body);

                // Show loading state in stats
                ['rsBal','rsEarned','rsCount','rsRef','rsType','rsRate'].forEach(id => {
                    const el = body.querySelector('#' + id);
                    if (el) el.textContent = '…';
                });

                try {
                    const pr = await fetch(`${BASE}/app/admin/referral/users/${uid}`);
                    const data = await pr.json();
                    if (!data.success) {
                        flash(body, 'Could not load referral data.', 'err');
                        return;
                    }

                    // Populate stats
                    body.querySelector('#rsBal').textContent     = '₦' + ngn(data.referral_balance);
                    body.querySelector('#rsEarned').textContent  = '₦' + ngn(data.total_earned);
                    body.querySelector('#rsCount').textContent   = data.total_referrals;
                    body.querySelector('#rsRef').textContent     = data.referred_by || 'None';
                    body.querySelector('#rsType').textContent    = data.is_partner ? '⭐ Affiliate' : '👤 Normal';
                    body.querySelector('#rsType').style.color    = data.is_partner ? '#fbbf24' : 'var(--t1)';
                    body.querySelector('#rsRate').textContent    = data.is_partner
                        ? (data.partner_percentage + '%')
                        : ('Global (' + GLOBAL_PCT + '%)');

                    // Set active tab to match current state
                    if (data.is_partner) {
                        switchRefTab('affiliate');
                        body.querySelector('#affiliatePct').value  = data.partner_percentage  || '';
                        body.querySelector('#affiliateMinW').value = data.partner_min_withdrawal || '';
                    } else {
                        switchRefTab('normal');
                    }

                    // Normal msg
                    const normalMsg = body.querySelector('#normalCurrentMsg');
                    if (!data.is_partner) {
                        normalMsg.textContent = '✓ This user is currently set as a Normal Referral.';
                        normalMsg.style.color = 'var(--emerald-text)';
                    } else {
                        normalMsg.textContent = 'Click below to downgrade this affiliate back to a normal referral. They will lose withdrawal access.';
                        normalMsg.style.color = 'var(--t3)';
                    }

                } catch (e) {
                    flash(body, 'Error loading referral info: ' + e.message, 'err');
                }
            });

            // ── Make Normal button ────────────────────────────────────────
            body.querySelector('#btnMakeNormal').addEventListener('click', async () => {
                const isAlreadyNormal = body.querySelector('#rsType')?.textContent?.includes('Normal');
                if (isAlreadyNormal) {
                    flash(body, 'This user is already a Normal Referral.', 'err');
                    return;
                }
                if (!confirm('Downgrade this user to Normal Referral? They will lose affiliate withdrawal access.')) return;
                try {
                    flash(body, 'Saving…', 'ok');
                    const d = await post(`${BASE}/app/admin/referral/users/${uid}/demote`, {});
                    flash(body, d.message || (d.success ? 'User set to Normal Referral.' : 'Failed.'), d.success ? 'ok' : 'err');
                    if (d.success) setTimeout(() => openModal(uid), 900);
                } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
            });

            // ── Make Affiliate button ─────────────────────────────────────
            body.querySelector('#btnMakeAffiliate').addEventListener('click', async () => {
                const pct  = parseFloat(body.querySelector('#affiliatePct').value);
                const minW = parseFloat(body.querySelector('#affiliateMinW').value);
                if (isNaN(pct)  || pct  < 0 || pct  > 100) { flash(body, 'Enter a valid percentage (0–100).', 'err'); return; }
                if (isNaN(minW) || minW < 0)                { flash(body, 'Enter a valid minimum withdrawal amount.', 'err'); return; }
                try {
                    flash(body, 'Saving…', 'ok');
                    const d = await post(`${BASE}/app/admin/referral/users/${uid}/promote`, {
                        partner_percentage:     pct,
                        partner_min_withdrawal: minW,
                    });
                    flash(body, d.message || (d.success ? 'Affiliate settings saved!' : 'Failed.'), d.success ? 'ok' : 'err');
                    if (d.success) setTimeout(() => openModal(uid), 900);
                } catch (e) { flash(body, 'Error: ' + e.message, 'err'); }
            });

            // Standard form actions
            body.querySelector('#btnDoPw').addEventListener('click',    () => doPw(body));
            body.querySelector('#btnDoPin').addEventListener('click',   () => doPin(body));
            body.querySelector('#btnDoAdd').addEventListener('click',   () => doAdd(body));
            body.querySelector('#btnDoDed').addEventListener('click',   () => doDed(body));
            body.querySelector('#btnDoBlock').addEventListener('click', () => doBlock(body, user));

        } catch(e) {
            body.innerHTML = `<div class="empty" style="padding:40px;"><div class="empty-icon">⚠️</div><p>Failed to load: ${esc(e.message)}</p></div>`;
        }
    };

    // ── Close modal ───────────────────────────────────────────────────────
    document.getElementById('mCloseBtn').addEventListener('click', closeModal);
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    function closeModal() {
        document.getElementById('userModal').classList.remove('open');
        document.body.style.overflow = '';
        uid = null;
    }

    // ── Form handlers ─────────────────────────────────────────────────────
    async function doPw(body) {
        const p = body.querySelector('#nPw').value;
        const c = body.querySelector('#cPw').value;
        if (!p || p !== c) { flash(body, 'Passwords do not match.', 'err'); return; }
        try {
            flash(body, 'Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-password`, { password: p, password_confirmation: c });
            flash(body, d.message || (d.success ? 'Password updated.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) { body.querySelector('#nPw').value = ''; body.querySelector('#cPw').value = ''; }
        } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doPin(body) {
        const p = body.querySelector('#nPin').value;
        if (!p || p.length !== 4) { flash(body, 'PIN must be exactly 4 digits.', 'err'); return; }
        try {
            flash(body, 'Saving…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/update-pin`, { pin: p });
            flash(body, d.message || (d.success ? 'PIN updated.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) body.querySelector('#nPin').value = '';
        } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doAdd(body) {
        const a    = parseFloat(body.querySelector('#aAmt').value);
        const desc = body.querySelector('#aDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash(body, 'Please fill in amount and reason.', 'err'); return; }
        try {
            flash(body, 'Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/add-funds`, { amount: a, description: desc });
            flash(body, d.message || (d.success ? 'Funds added.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                body.querySelector('#aAmt').value = '';
                body.querySelector('#aDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doDed(body) {
        const a    = parseFloat(body.querySelector('#dAmt').value);
        const desc = body.querySelector('#dDesc').value.trim();
        if (!a || a <= 0 || !desc) { flash(body, 'Please fill in amount and reason.', 'err'); return; }
        try {
            flash(body, 'Processing…', 'ok');
            const d = await post(`${BASE}/app/admin/users/${uid}/deduct-funds`, { amount: a, description: desc });
            flash(body, d.message || (d.success ? 'Funds deducted.' : 'Failed.'), d.success ? 'ok' : 'err');
            if (d.success) {
                body.querySelector('#dAmt').value = '';
                body.querySelector('#dDesc').value = '';
                setTimeout(() => openModal(uid), 900);
            }
        } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
    }

    async function doBlock(body, user) {
        if (user.is_blocked) {
            try {
                flash(body, 'Processing…', 'ok');
                const d = await post(`${BASE}/app/admin/users/${uid}/unblock`, {});
                flash(body, d.message || (d.success ? 'User unblocked.' : 'Failed.'), d.success ? 'ok' : 'err');
                if (d.success) setTimeout(() => openModal(uid), 900);
            } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
        } else {
            const reason = body.querySelector('#blockReason').value.trim();
            if (!reason) { flash(body, 'Please provide a reason for blocking.', 'err'); return; }
            try {
                flash(body, 'Processing…', 'ok');
                const d = await post(`${BASE}/app/admin/users/${uid}/block`, { reason });
                flash(body, d.message || (d.success ? 'User blocked.' : 'Failed.'), d.success ? 'ok' : 'err');
                if (d.success) {
                    body.querySelector('#blockReason').value = '';
                    setTimeout(() => openModal(uid), 900);
                }
            } catch(e) { flash(body, 'Error: ' + e.message, 'err'); }
        }
    }

    function openSocialTxModalFromButton(btn) {
        const txId = btn.dataset.id;
        const payload = {
            id: txId,
            user: btn.dataset.user || 'Unknown user',
            service: btn.dataset.service || 'Social boost',
            provider: btn.dataset.provider || 'Social',
            amount: Number(btn.dataset.amount || 0),
            status: btn.dataset.status || 'pending',
            reference: btn.dataset.reference || '',
            request: btn.dataset.request || '',
        };

        const modal = document.getElementById('socialTxModal');
        const body = document.getElementById('socialTxBody');
        document.getElementById('socialTxTitle').textContent = 'Social Order Action';
        document.getElementById('socialTxSubtitle').textContent = '@' + payload.user + ' • ' + payload.service;
        document.getElementById('socialTxAvatar').textContent = 'S';

        body.innerHTML = `
            <div style="display:grid;gap:16px;">
                <div style="background:linear-gradient(135deg,#f5f3ff,#eef2ff);border:1px solid rgba(139,92,246,0.18);border-radius:16px;padding:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                        <div>
                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#6d28d9;font-weight:700;">Order summary</div>
                            <div style="font-size:19px;font-weight:800;color:#111827;margin-top:8px;">${payload.service}</div>
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:#fff;color:#6d28d9;border:1px solid rgba(109,40,217,.18);font-size:11px;font-weight:700;">${payload.status}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:16px;">
                        <div style="background:rgba(255,255,255,0.7);border:1px solid rgba(99,102,241,0.12);padding:10px 12px;border-radius:12px;">
                            <div style="font-size:10px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Tx ID</div>
                            <div style="font-family:ui-monospace,monospace;font-size:12px;font-weight:700;color:#111827;margin-top:4px;">#${payload.id}</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.7);border:1px solid rgba(99,102,241,0.12);padding:10px 12px;border-radius:12px;">
                            <div style="font-size:10px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Amount</div>
                            <div style="font-size:12px;font-weight:700;color:#111827;margin-top:4px;">₦${Number(payload.amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.7);border:1px solid rgba(99,102,241,0.12);padding:10px 12px;border-radius:12px;">
                            <div style="font-size:10px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Provider</div>
                            <div style="font-size:12px;font-weight:700;color:#111827;margin-top:4px;">${payload.provider}</div>
                        </div>
                        <div style="background:rgba(255,255,255,0.7);border:1px solid rgba(99,102,241,0.12);padding:10px 12px;border-radius:12px;">
                            <div style="font-size:10px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Reference</div>
                            <div style="font-family:ui-monospace,monospace;font-size:12px;font-weight:700;color:#111827;margin-top:4px;">${payload.reference || '—'}</div>
                        </div>
                    </div>
                </div>

                <div style="border:1px solid rgba(15,118,110,0.16);background:rgba(16,185,129,0.04);border-radius:14px;padding:14px;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#047857;font-weight:700;">Fulfill order</div>
                    <p style="margin:8px 0 0;color:#065f46;font-size:13px;line-height:1.55;">Once fulfilled, the order is marked complete and the user can no longer report it as pending.</p>
                    <button type="button" id="socialFulfillBtn" style="margin-top:12px;width:100%;background:#10b981;color:#fff;border:none;border-radius:10px;padding:12px 14px;font-weight:700;cursor:pointer;">Mark as Completed</button>
                </div>

                <div style="border:1px solid rgba(244,63,94,0.2);background:rgba(244,63,94,0.04);border-radius:14px;padding:14px;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#be123c;font-weight:700;">Cancel & refund</div>
                    <label style="display:block;margin-top:10px;color:#374151;font-size:12px;font-weight:600;">Refund amount</label>
                    <input id="socialRefundAmount" type="number" min="0" step="0.01" max="${Number(payload.amount || 0).toFixed(2)}" value="${Number(payload.amount || 0).toFixed(2)}" style="width:100%;margin-top:6px;padding:11px 12px;border:1px solid #d1d5db;border-radius:10px;outline:none;font-size:14px;">
                    <label style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:12px;color:#374151;">
                        <input id="socialFullRefund" type="checkbox" checked>
                        Full refund
                    </label>
                    <textarea id="socialCancelReason" rows="2" placeholder="Reason for cancellation" style="width:100%;margin-top:12px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;resize:vertical;outline:none;font-size:13px;">Cancelled by admin</textarea>
                    <button type="button" id="socialCancelBtn" style="margin-top:12px;width:100%;background:#ef4444;color:#fff;border:none;border-radius:10px;padding:12px 14px;font-weight:700;cursor:pointer;">Cancel & Refund</button>
                </div>
            </div>
        `;

        const closeBtn = document.getElementById('socialTxCloseBtn');
        closeBtn.onclick = () => { modal.classList.remove('open'); document.body.style.overflow = ''; };

        document.getElementById('socialFulfillBtn').addEventListener('click', async () => {
            try {
                const response = await fetch(`${BASE}/app/admin/transactions/${payload.id}/social/fulfill`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Failed to fulfill order.');
                alert(data.message || 'Social order completed.');
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Failed to fulfill order.');
            }
        });

        document.getElementById('socialCancelBtn').addEventListener('click', async () => {
            const fullRefund = document.getElementById('socialFullRefund').checked;
            const refundInput = document.getElementById('socialRefundAmount').value;
            const refundAmount = fullRefund ? Number(payload.amount || 0) : Number(refundInput || 0);
            const reason = document.getElementById('socialCancelReason').value.trim() || 'Cancelled by admin';

            if (!fullRefund && (!Number.isFinite(refundAmount) || refundAmount < 0)) {
                alert('Enter a valid refund amount.');
                return;
            }

            if (refundAmount > Number(payload.amount || 0)) {
                alert('Refund amount cannot exceed the original amount paid by the user.');
                return;
            }

            try {
                const response = await fetch(`${BASE}/app/admin/transactions/${payload.id}/social/cancel`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ refund_amount: refundAmount, full_refund: fullRefund, reason })
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Failed to cancel order.');
                alert(data.message || 'Social order cancelled.');
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Failed to cancel order.');
            }
        });

        document.getElementById('socialFullRefund').addEventListener('change', function () {
            const refundInput = document.getElementById('socialRefundAmount');
            refundInput.disabled = this.checked;
            if (this.checked) refundInput.value = Number(payload.amount || 0).toFixed(2);
        });

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.social-tx-btn');
        if (btn) {
            openSocialTxModalFromButton(btn);
        }
    });

    document.getElementById('socialTxModal').addEventListener('click', function (event) {
        if (event.target === this) {
            this.classList.remove('open');
            document.body.style.overflow = '';
        }
    });

    // ── Date range helpers ────────────────────────────────────────────────
    function handleDateRangeChange(selectId, prefix) {
        const select = document.getElementById(selectId);
        const fields = document.getElementById(prefix + 'RangeFields');
        if (!select || !fields) return;
        if (select.value === 'custom') { fields.classList.add('open'); return; }
        fields.classList.remove('open');
        const start = document.getElementById(prefix + 'Start');
        const end   = document.getElementById(prefix + 'End');
        if (start) start.value = '';
        if (end) end.value = '';
        if (select.form) select.form.submit();
    }

    // expose for onchange
    window.handleDateRangeChange = handleDateRangeChange;

})();
</script>

@endsection