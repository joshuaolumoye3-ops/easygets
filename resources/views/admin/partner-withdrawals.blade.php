@extends('layouts.admin')

@section('title', 'Partner Withdrawals - Easygets')

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
        background: linear-gradient(90deg, transparent, var(--amber), var(--blue), transparent);
        opacity: 0.5;
    }
    .hdr-brand h1 { font-size: 18px; font-weight: 800; color: var(--t1); letter-spacing: -0.4px; }
    .hdr-brand p  { font-size: 11.5px; color: var(--t3); font-weight: 500; margin-top: 3px; }
    .hdr-right { display: flex; align-items: center; gap: 10px; }

    .back-link {
        height: 38px; padding: 0 14px;
        display: inline-flex; align-items: center; gap: 7px;
        background: var(--blue-dim); color: var(--blue-text);
        border: 1px solid rgba(79,140,255,0.25); border-radius: var(--r2);
        font-size: 12.5px; font-weight: 700; font-family: var(--font);
        text-decoration: none; transition: background .2s, color .2s, box-shadow .2s;
    }
    .back-link:hover { background: var(--blue); color: #fff; box-shadow: 0 4px 16px var(--blue-glow); }

    /* ── Stats ── */
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
    .stat-card {
        background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r4);
        padding: 20px; position: relative; overflow: hidden;
        transition: border-color .25s, transform .25s, box-shadow .25s;
    }
    .stat-card:hover { border-color: var(--border-2); transform: translateY(-2px); box-shadow: var(--shadow-glow); }
    .stat-card .accent-line { position: absolute; top: 0; left: 20px; right: 20px; height: 2px; border-radius: 0 0 4px 4px; }
    .stat-card.amber .accent-line { background: linear-gradient(90deg, transparent, var(--amber), transparent); }
    .stat-card.green .accent-line { background: linear-gradient(90deg, transparent, var(--emerald), transparent); }
    .stat-card.rose  .accent-line { background: linear-gradient(90deg, transparent, var(--rose), transparent); }
    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .stat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.9px; color: var(--t3); }
    .stat-icon { width: 40px; height: 40px; border-radius: var(--r2); display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .stat-card.amber .stat-icon { background: var(--amber-dim);   color: var(--amber); }
    .stat-card.green .stat-icon { background: var(--emerald-dim); color: var(--emerald); }
    .stat-card.rose  .stat-icon { background: var(--rose-dim);    color: var(--rose); }
    .stat-value { font-size: 26px; font-weight: 800; color: var(--t1); letter-spacing: -0.5px; line-height: 1; margin-bottom: 10px; }
    .stat-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .stat-card.amber .stat-badge { background: var(--amber-dim);   color: var(--amber-text);   border: 1px solid rgba(251,191,36,0.2); }
    .stat-card.green .stat-badge { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.2); }
    .stat-card.rose  .stat-badge { background: var(--rose-dim);    color: var(--rose-text);    border: 1px solid rgba(244,86,110,0.2); }

    /* ── Global Rate Card ── */
    .rate-card {
        background: var(--bg-3); border: 1px solid rgba(251,191,36,0.2);
        border-radius: var(--r4); padding: 20px; margin-bottom: 24px;
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    }
    .rate-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, var(--amber), transparent);
    }
    .rate-info h3 { font-size: 14px; font-weight: 700; color: var(--t1); }
    .rate-info p  { font-size: 12px; color: var(--t3); margin-top: 4px; }
    .rate-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .rate-input {
        height: 38px; width: 110px; padding: 0 12px;
        background: var(--bg-2); border: 1px solid rgba(251,191,36,0.3);
        border-radius: var(--r2); color: var(--amber-text); font-size: 14px; font-weight: 700;
        font-family: var(--mono); text-align: center; transition: border-color .2s, box-shadow .2s;
    }
    .rate-input:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px var(--amber-dim); }
    .rate-btn {
        height: 38px; padding: 0 18px;
        background: var(--amber-dim); color: var(--amber-text);
        border: 1px solid rgba(251,191,36,0.3); border-radius: var(--r2);
        font-size: 13px; font-weight: 700; font-family: var(--font); cursor: pointer;
        transition: background .2s, color .2s, box-shadow .2s; white-space: nowrap;
    }
    .rate-btn:hover { background: var(--amber); color: #080f1c; box-shadow: 0 4px 14px rgba(251,191,36,0.3); }
    .rate-flash { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: var(--r1); display: none; }
    .rate-flash.ok  { display: inline-block; background: var(--emerald-dim); color: var(--emerald-text); }
    .rate-flash.err { display: inline-block; background: var(--rose-dim);    color: var(--rose-text); }

    /* ── Table Card ── */
    .tcard { background: var(--bg-3); border: 1px solid var(--border); border-radius: var(--r4); overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-card); }
    .tcard-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, rgba(251,191,36,0.04) 0%, transparent 60%); }
    .tcard-title { display: flex; align-items: center; gap: 10px; }
    .tcard-title h3 { font-size: 15px; font-weight: 700; color: var(--t1); }
    .count-pill { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: var(--amber-dim); color: var(--amber-text); border: 1px solid rgba(251,191,36,0.2); }
    .filter-form { display: flex; align-items: center; gap: 8px; }
    .per-page {
        height: 36px; padding: 0 26px 0 10px; background: var(--bg-2); border: 1px solid var(--border-2);
        border-radius: var(--r2); color: var(--blue-text); font-size: 12px; font-family: var(--font); cursor: pointer;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%233a5578' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center;
    }
    .per-page:focus { border-color: var(--blue); outline: none; }

    /* ── Table ── */
    .tscroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table { width: 100%; border-collapse: collapse; min-width: 700px; }
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
    .uav { width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg, var(--amber), #d97706); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #080f1c; }
    .uname { font-weight: 600; font-size: 13.5px; }
    .usub  { font-size: 11px; color: var(--t3); }
    .amt { color: var(--amber-text); font-weight: 700; font-family: var(--mono); font-size: 13.5px; }
    .bank-detail { font-size: 12px; color: var(--t2); }
    .bank-detail strong { color: var(--t1); font-weight: 600; }
    .ref-mono { font-family: var(--mono); font-size: 11px; color: var(--t3); }

    .chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; white-space: nowrap; }
    .chip-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .chip.pending  { background: var(--amber-dim);   color: var(--amber-text);   border: 1px solid rgba(251,191,36,0.25); }
    .chip.approved { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.25); }
    .chip.declined { background: var(--rose-dim);    color: var(--rose-text);    border: 1px solid rgba(244,86,110,0.25); }

    .btn-approve { height: 30px; padding: 0 12px; background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.25); border-radius: var(--r2); font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .2s, color .2s; white-space: nowrap; }
    .btn-approve:hover { background: var(--emerald); color: #fff; }
    .btn-decline { height: 30px; padding: 0 12px; background: var(--rose-dim); color: var(--rose-text); border: 1px solid rgba(244,86,110,0.25); border-radius: var(--r2); font-size: 11.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: background .2s, color .2s; white-space: nowrap; }
    .btn-decline:hover { background: var(--rose); color: #fff; }

    .pgbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border); }
    .pg-info { font-size: 12.5px; color: var(--t3); font-weight: 500; }
    .pg-info b { color: var(--blue-text); font-weight: 700; }
    .laravel-pagination { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; list-style: none; }
    .laravel-pagination li a, .laravel-pagination li span { min-width: 36px; height: 36px; padding: 0 9px; display: inline-flex; align-items: center; justify-content: center; background: var(--bg-2); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--blue-text); font-size: 12px; font-weight: 700; font-family: var(--font); text-decoration: none; transition: background .2s, color .2s, box-shadow .2s; }
    .laravel-pagination li a:hover { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 4px 14px var(--blue-glow); }
    .laravel-pagination li span[aria-current] { background: var(--blue); border-color: var(--blue); color: #fff; box-shadow: 0 0 0 3px var(--blue-dim); }
    .laravel-pagination li span.disabled { opacity: .3; cursor: not-allowed; }

    .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 18px; border-radius: var(--r3); font-size: 13px; font-weight: 700; z-index: 9999; transform: translateY(60px); opacity: 0; transition: transform .3s, opacity .3s; pointer-events: none; max-width: 320px; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.ok  { background: var(--emerald-dim); color: var(--emerald-text); border: 1px solid rgba(52,217,139,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
    .toast.err { background: var(--rose-dim);    color: var(--rose-text);    border: 1px solid rgba(244,86,110,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }

    .note-input { width: 100%; max-width: 240px; height: 32px; padding: 0 10px; background: var(--bg-2); border: 1px solid var(--border-2); border-radius: var(--r2); color: var(--t1); font-size: 12px; font-family: var(--font); }
    .note-input:focus { outline: none; border-color: var(--blue); }

    @media (max-width: 680px) {
        .stats { grid-template-columns: 1fr; }
        .rate-card { flex-direction: column; align-items: flex-start; }
        .inner { padding: 16px 14px; }
    }
</style>

<div class="dash">
<div class="inner">

    {{-- Header --}}
    <div class="hdr">
        <div class="hdr-brand">
            <h1>⭐ Partner Withdrawals</h1>
            <p>{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="hdr-right">
            <a href="{{ route('admin.dashboard') }}" class="back-link">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat-card amber">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Pending</div>
                <div class="stat-icon">⏳</div>
            </div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <span class="stat-badge">Awaiting action</span>
        </div>
        <div class="stat-card green">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Approved</div>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-value">{{ $stats['approved'] }}</div>
            <span class="stat-badge">₦{{ number_format($stats['approved_total'], 0) }}</span>
        </div>
        <div class="stat-card rose">
            <div class="accent-line"></div>
            <div class="stat-top">
                <div class="stat-label">Declined</div>
                <div class="stat-icon">❌</div>
            </div>
            <div class="stat-value">{{ $stats['declined'] }}</div>
            <span class="stat-badge">Rejected</span>
        </div>
    </div>

    {{-- Global Referral Rate --}}
    <div class="rate-card">
        <div class="rate-info">
            <h3>🌐 Global Referral Commission</h3>
            <p>Default % earned by normal (non-affiliate) referrers on each deposit.</p>
        </div>
        <div class="rate-form">
            <span style="font-size:12px;color:var(--t3);font-weight:600;">Current:</span>
            <input type="number" id="globalRateInput" class="rate-input"
                   value="{{ \App\Models\ReferralSetting::globalPercentage() }}"
                   min="0" max="100" step="0.01" placeholder="e.g. 2.00">
            <span style="font-size:14px;color:var(--amber-text);font-weight:700;">%</span>
            <button class="rate-btn" onclick="saveGlobalRate()">Save Rate</button>
            <span id="rateFlash" class="rate-flash"></span>
        </div>
    </div>

    {{-- Withdrawals Table --}}
    <div class="tcard">
        <div class="tcard-head">
            <div class="tcard-title">
                <h3>Withdrawal Requests</h3>
                <span class="count-pill">{{ $withdrawals->total() }}</span>
            </div>
            <form class="filter-form" method="GET" action="{{ url()->current() }}">
                <select class="per-page" name="status" onchange="this.form.submit()">
                    <option value="" {{ !request('status') ? 'selected' : '' }}>All statuses</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
                <select class="per-page" name="pp" onchange="this.form.submit()">
                    @foreach([10,25,50] as $n)
                        <option value="{{ $n }}" {{ request('pp', 10) == $n ? 'selected' : '' }}>{{ $n }} / pg</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="tscroll">
            @if($withdrawals->count())
            <table>
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Amount</th>
                        <th>Bank Details</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawals as $w)
                    <tr id="row-{{ $w->id }}">
                        <td>
                            <div class="ucell">
                                <div class="uav">{{ strtoupper(substr($w->user?->username ?? 'P', 0, 1)) }}</div>
                                <div>
                                    <div class="uname">{{ $w->user?->username ?? 'N/A' }}</div>
                                    <div class="usub">{{ $w->user?->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="amt">₦{{ number_format($w->amount, 2) }}</span></td>
                        <td>
                            <div class="bank-detail"><strong>{{ $w->bank_name }}</strong></div>
                            <div class="bank-detail">{{ $w->account_number }}</div>
                            <div class="bank-detail">{{ $w->account_name }}</div>
                        </td>
                        <td><span class="ref-mono">{{ $w->reference }}</span></td>
                        <td>
                            <span class="chip {{ $w->status }}" id="chip-{{ $w->id }}">
                                <span class="chip-dot" style="background:{{ $w->status === 'pending' ? '#fbbf24' : ($w->status === 'approved' ? '#34d98b' : '#f4566e') }};"></span>
                                {{ ucfirst($w->status) }}
                            </span>
                            @if($w->admin_note)
                                <div style="font-size:11px;color:var(--t3);margin-top:4px;">{{ $w->admin_note }}</div>
                            @endif
                        </td>
                        <td style="color:var(--t3);font-size:12px;">
                            {{ $w->created_at?->format('d M Y H:i') ?? '—' }}
                            @if($w->processed_at)
                                <div style="font-size:10px;color:var(--t3);">Processed {{ $w->processed_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @if($w->status === 'pending')
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                <input type="text" class="note-input" id="note-{{ $w->id }}" placeholder="Optional note…">
                                <div style="display:flex;gap:6px;">
                                    <button class="btn-approve" onclick="processWithdrawal({{ $w->id }}, 'approve')">Approve</button>
                                    <button class="btn-decline" onclick="processWithdrawal({{ $w->id }}, 'decline')">Decline</button>
                                </div>
                            </div>
                            @else
                            <span style="font-size:12px;color:var(--t3);">
                                {{ $w->status === 'approved' ? '✅ Processed' : '❌ Declined' }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">
                <div class="empty-icon">💸</div>
                <p>No withdrawal requests found.</p>
            </div>
            @endif
        </div>

        @if($withdrawals->hasPages())
        <div class="pgbar">
            <div class="pg-info">Showing <b>{{ $withdrawals->firstItem() }}–{{ $withdrawals->lastItem() }}</b> of <b>{{ $withdrawals->total() }}</b></div>
            <div>{{ $withdrawals->appends(request()->only(['status','pp']))->links('partials.admin.custom') }}</div>
        </div>
        @endif
    </div>

</div>
</div>

<div id="toast" class="toast"></div>

<script>
(function () {
    'use strict';

    const BASE = @json(url('/'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function toast(msg, ok) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = 'toast show ' + (ok ? 'ok' : 'err');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 3500);
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

    // action is 'approve' or 'decline' — matches route: /referral/withdrawals/{id}/approve
    window.processWithdrawal = async function(id, action) {
        const note  = document.getElementById('note-' + id)?.value?.trim() || '';
        const label = action === 'approve' ? 'Approve' : 'Decline';
        if (!confirm(`${label} this withdrawal request?`)) return;

        try {
            const d = await post(
                `${BASE}/app/admin/referral/withdrawals/${id}/${action}`,
                { note }
            );
            if (d.success) {
                toast(d.message || `Withdrawal ${action}d.`, true);

                const resolvedStatus = action === 'approve' ? 'approved' : 'declined';
                const dotColor       = action === 'approve' ? '#34d98b' : '#f4566e';

                const chip = document.getElementById('chip-' + id);
                if (chip) {
                    chip.className = 'chip ' + resolvedStatus;
                    chip.innerHTML = `<span class="chip-dot" style="background:${dotColor};"></span>${resolvedStatus.charAt(0).toUpperCase() + resolvedStatus.slice(1)}`;
                }

                const row = document.getElementById('row-' + id);
                if (row) {
                    const actionCell = row.querySelector('td:last-child');
                    if (actionCell) {
                        actionCell.innerHTML = `<span style="font-size:12px;color:var(--t3);">${action === 'approve' ? '✅ Processed' : '❌ Declined'}</span>`;
                    }
                }
            } else {
                toast(d.message || 'Failed.', false);
            }
        } catch (e) {
            toast('Error: ' + e.message, false);
        }
    };

    // Matches route: POST /app/admin/referral/settings/percentage
    window.saveGlobalRate = async function() {
        const val   = parseFloat(document.getElementById('globalRateInput').value);
        const flash = document.getElementById('rateFlash');
        if (isNaN(val) || val < 0 || val > 100) {
            flash.textContent = 'Enter a valid % (0–100)';
            flash.className = 'rate-flash err';
            return;
        }
        try {
            flash.textContent = 'Saving…';
            flash.className = 'rate-flash ok';
            const d = await post(`${BASE}/app/admin/referral/settings/percentage`, { percentage: val });
            if (d.success) {
                flash.textContent = '✓ Saved!';
                flash.className = 'rate-flash ok';
                toast('Global referral rate updated to ' + val + '%', true);
            } else {
                flash.textContent = d.message || 'Failed';
                flash.className = 'rate-flash err';
            }
            setTimeout(() => { flash.className = 'rate-flash'; }, 3000);
        } catch (e) {
            flash.textContent = 'Error: ' + e.message;
            flash.className = 'rate-flash err';
        }
    };
})();
</script>

@endsection