@extends('layouts.admin')

@section('title', 'Manage Social Service Descriptions')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #0f172a; }

    .social-container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1a202c 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }

    /* ── Header ─────────────────────────────────────────────────── */
    .header-bar {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .header-left h1 {
        font-size: 28px;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 4px;
    }

    .header-left p {
        font-size: 14px;
        color: #cbd5e1;
        font-weight: 500;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .refresh-btn {
        padding: 12px 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .refresh-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
    .refresh-btn:active { transform: translateY(0); }

    /* ── Global Markup Panel ─────────────────────────────────────── */
    .global-markup-panel {
        background: linear-gradient(135deg, #1e293b 0%, #1a2744 100%);
        border: 1px solid #3b82f6;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 28px;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }

    .gmp-left {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        flex: 1;
        min-width: 260px;
    }

    .gmp-icon {
        font-size: 34px;
        line-height: 1;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .gmp-text h3 {
        font-size: 17px;
        font-weight: 700;
        color: #93c5fd;
        margin-bottom: 5px;
    }

    .gmp-text p {
        font-size: 13px;
        color: #94a3b8;
        line-height: 1.55;
        max-width: 520px;
    }

    .gmp-text p strong { color: #f1f5f9; font-weight: 600; }

    .gmp-form {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .gmp-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .gmp-input {
        width: 110px;
        padding: 12px 34px 12px 16px;
        background: #0f172a;
        border: 2px solid #334155;
        border-radius: 10px;
        font-size: 20px;
        font-weight: 700;
        color: #f1f5f9;
        font-family: 'Courier New', monospace;
        text-align: center;
        transition: border-color 0.25s, box-shadow 0.25s;
        -moz-appearance: textfield;
    }

    .gmp-input::-webkit-outer-spin-button,
    .gmp-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    .gmp-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }

    .gmp-pct-label {
        position: absolute;
        right: 11px;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        pointer-events: none;
    }

    .gmp-btn {
        padding: 13px 22px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        white-space: nowrap;
    }

    .gmp-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); }
    .gmp-btn:active { transform: translateY(0); }
    .gmp-btn:disabled { opacity: 0.65; pointer-events: none; }

    .gmp-divider {
        width: 1px;
        height: 44px;
        background: #334155;
        flex-shrink: 0;
    }

    .gmp-current {
        font-size: 12px;
        color: #64748b;
        text-align: center;
        white-space: nowrap;
    }

    .gmp-current span {
        display: block;
        font-size: 22px;
        font-weight: 700;
        color: #10b981;
        font-family: 'Courier New', monospace;
        line-height: 1.2;
    }

    /* ── Search Section ──────────────────────────────────────────── */
    .search-section {
        background: #1e293b;
        border-radius: 16px;
        padding: 20px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 220px;
        max-width: 500px;
    }

    .search-box input {
        width: 100%;
        padding: 13px 20px 13px 48px;
        border: 2px solid #334155;
        border-radius: 12px;
        font-size: 15px;
        background-color: #0f172a;
        color: #f1f5f9;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .search-box input:focus {
        outline: none;
        border-color: #3b82f6;
        background-color: #1e293b;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .search-box input::placeholder { color: #94a3b8; }

    .search-box svg {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: #94a3b8;
    }

    .search-count {
        font-size: 13px;
        color: #64748b;
        white-space: nowrap;
    }

    .search-count strong { color: #93c5fd; }

    /* ── Stats Cards ─────────────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: #1e293b;
        border-radius: 16px;
        padding: 22px 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        transform: translateY(-2px);
        border-color: #475569;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8b5cf6, #a855f7);
    }

    .stat-content { display: flex; justify-content: space-between; align-items: flex-start; }
    .stat-info { flex: 1; }

    .stat-label {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-value { font-size: 28px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px; }

    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background-color: #10b98144;
        color: #86efac;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
    }

    /* ── Table ───────────────────────────────────────────────────── */
    .table-card {
        background: #1e293b;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        overflow: hidden;
        border: 1px solid #334155;
    }

    .table-header {
        padding: 22px 28px;
        border-bottom: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .table-header h3 { font-size: 18px; font-weight: 700; color: #f1f5f9; }

    .table-badge {
        padding: 5px 14px;
        background-color: #1e3a8a;
        color: #93c5fd;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .table-wrapper { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background-color: #0f172a; }

    th {
        padding: 14px 18px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        border-bottom: 2px solid #334155;
        white-space: nowrap;
    }

    td {
        padding: 14px 18px;
        border-bottom: 1px solid #1e293b;
        font-size: 14px;
        color: #cbd5e1;
        vertical-align: middle;
    }

    tbody tr { transition: background 0.15s ease; }
    tbody tr:hover { background-color: #263348; }

    .service-name {
        font-weight: 600;
        color: #f1f5f9;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .custom-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .pct-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        font-family: 'Courier New', monospace;
    }

    .pct-zero  { background: #1e293b; color: #475569; border: 1px solid #334155; }
    .pct-low   { background: #052e16; color: #86efac; border: 1px solid #166534; }
    .pct-mid   { background: #1c1917; color: #fcd34d; border: 1px solid #78350f; }
    .pct-high  { background: #2d1515; color: #fca5a5; border: 1px solid #7f1d1d; }

    .description-text {
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .description-text.has-desc { color: #94a3b8; }

    .action-btn {
        padding: 7px 14px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state svg { width: 56px; height: 56px; margin: 0 auto 16px; color: #475569; }
    .empty-state h4 { font-size: 18px; font-weight: 600; color: #f1f5f9; margin-bottom: 8px; }

    /* ── Alerts ──────────────────────────────────────────────────── */
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-success { background-color: #064e3b; color: #86efac; border-left: 4px solid #10b981; }
    .alert-error   { background-color: #7f1d1d; color: #fca5a5; border-left: 4px solid #ef4444; }
    .alert svg     { width: 20px; height: 20px; flex-shrink: 0; }

    /* ── Modal ───────────────────────────────────────────────────── */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background-color: rgba(0,0,0,0.72);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .modal.active { display: flex; animation: modalFadeIn 0.25s ease; }

    @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }

    .modal-content {
        background: #1e293b;
        border-radius: 20px;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        animation: modalSlideUp 0.25s ease;
        border: 1px solid #334155;
    }

    @keyframes modalSlideUp {
        from { transform: translateY(20px) scale(0.96); opacity: 0; }
        to   { transform: translateY(0)    scale(1);    opacity: 1; }
    }

    .modal-header {
        padding: 22px 28px;
        border-bottom: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 { font-size: 22px; font-weight: 700; color: #f1f5f9; }
    .modal-service-id { font-size: 12px; color: #64748b; font-family: monospace; margin-top: 2px; }

    .close-btn {
        background: none;
        border: none;
        font-size: 26px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }

    .close-btn:hover { color: #f1f5f9; background-color: #334155; }

    .modal-body { padding: 28px; }

    .form-group { margin-bottom: 22px; }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #cbd5e1;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #334155;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        color: #f1f5f9;
        background-color: #0f172a;
        transition: all 0.25s ease;
        resize: vertical;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        background-color: #131e30;
    }

    .form-input::placeholder, .form-textarea::placeholder { color: #475569; }

    .form-textarea { min-height: 110px; line-height: 1.55; }

    .form-help { font-size: 12px; color: #64748b; margin-top: 6px; }

    .pct-input-wrap { position: relative; display: flex; align-items: center; }

    .pct-input-wrap .form-input {
        padding-right: 38px;
        font-family: 'Courier New', monospace;
        font-size: 18px;
        font-weight: 700;
        width: 140px;
    }

    .pct-suffix {
        position: absolute;
        right: 14px;
        font-size: 16px;
        font-weight: 700;
        color: #64748b;
        pointer-events: none;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 22px;
        border-top: 1px solid #334155;
        margin-top: 24px;
    }

    .btn {
        padding: 11px 20px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 110px;
    }

    .btn-secondary { background-color: #334155; color: #cbd5e1; }
    .btn-secondary:hover { background-color: #475569; color: #f1f5f9; }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }
    .btn-primary:active { transform: translateY(0); }
    .btn-primary:disabled { opacity: 0.6; pointer-events: none; }

    /* ── Spinner ─────────────────────────────────────────────────── */
    @keyframes spin { to { transform: rotate(360deg); } }

    .spin { animation: spin 0.9s linear infinite; display: inline-block; }

    /* ── Responsive ──────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .header-bar { flex-direction: column; align-items: flex-start; }
        .header-left h1 { font-size: 22px; }
        .global-markup-panel { flex-direction: column; align-items: flex-start; }
        .gmp-form { width: 100%; }
        .gmp-divider { display: none; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .table-header { flex-direction: column; align-items: flex-start; }
        .modal-content { max-width: none; }
        .modal-body { padding: 20px; }
        .modal-actions { flex-direction: column; }
        .btn { width: 100%; }
        th, td { padding: 12px 14px; font-size: 13px; }
    }

    @media (max-width: 560px) {
        .main-content { padding: 16px 14px; }
        .stats-grid { grid-template-columns: 1fr; }
        .search-section { flex-direction: column; align-items: stretch; }
        .search-box { max-width: none; }
    }
</style>

<div class="social-container">
<div class="main-content">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="header-bar">
        <div class="header-left">
            <h1>Social Service Management</h1>
            <p>Manage descriptions, markup, and settings for all social media services</p>
        </div>
        <div class="header-right">
            <button onclick="refreshServices(this)" class="refresh-btn">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh Services
            </button>
        </div>
    </div>

    {{-- ── Provider Tabs (filter by provider) ─────────────────────────── --}}
    @if(!empty($providers))
        <div class="mb-6">
            @foreach($providers as $slug => $meta)
                <a
                    href="{{ route('admin.social.services', ['provider' => $meta['route']]) }}"
                    class="inline-block px-3 py-2 mr-2 mb-2 text-sm rounded-lg transition {{ $selectedProvider === $slug ? 'bg-blue-600 text-white ring-1 ring-blue-400' : 'bg-gray-800 text-white hover:bg-gray-700' }}"
                    @if($selectedProvider === $slug) aria-current="page" @endif
                >
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- ── Alert Messages ───────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Global Markup Panel ──────────────────────────────────────── --}}
    @php
        $currentGlobal = \App\Models\SocialServiceDescription::where('provider', $selectedProvider)->avg('admin_percentage');
        $currentGlobal = $currentGlobal ? round($currentGlobal) : 0;
    @endphp

    <div class="global-markup-panel">
        <div class="gmp-left">
            <div class="gmp-icon">💹</div>
            <div class="gmp-text">
                <h3>Global Markup — Apply to All Services</h3>
                <p>
                    Enter a percentage and click <strong>Apply to All</strong> to set the same markup on
                    every service at once — including those not yet saved to the database.
                    Existing <strong>descriptions, custom names, and categories</strong> are preserved;
                    only the markup percentage is overwritten.
                </p>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div class="gmp-current">
                Current avg<br>
                <span id="currentAvgDisplay">{{ $currentGlobal }}%</span>
            </div>
            <div class="gmp-divider"></div>
            <form
                id="globalMarkupForm"
                method="POST"
                action="{{ route('admin.social.bulkPercentage') }}"
                class="gmp-form"
                onsubmit="return handleBulkSubmit(event)"
            >
                @csrf
                <input type="hidden" name="provider" id="globalProviderInput" value="{{ $selectedProvider ?? '' }}">
                <div class="gmp-input-wrap">
                    <input
                        type="number"
                        name="global_percentage"
                        id="globalPercentageInput"
                        class="gmp-input"
                        min="0"
                        max="1000"
                        step="1"
                        placeholder="0"
                        required
                    />
                    <span class="gmp-pct-label">%</span>
                </div>
                <button type="submit" class="gmp-btn" id="globalMarkupBtn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Apply to All
                </button>
            </form>
        </div>
    </div>

    {{-- ── Stats Cards ──────────────────────────────────────────────── --}}
    @php
        $totalServices     = count($services);
        $withCustomName    = collect($services)->filter(fn($s) => !empty($s['custom_name']))->count();
        $withDescription   = collect($services)->filter(fn($s) => !empty($s['description']))->count();
        $withMarkup        = collect($services)->filter(fn($s) => ($s['admin_percentage'] ?? 0) > 0)->count();
    @endphp

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">Total Services</div>
                    <div class="stat-value">{{ number_format($totalServices) }}</div>
                    <div class="stat-badge">
                        <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        Active
                    </div>
                </div>
                <div class="stat-icon">📊</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">With Markup</div>
                    <div class="stat-value">{{ number_format($withMarkup) }}</div>
                    <div class="stat-badge">
                        <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        Priced
                    </div>
                </div>
                <div class="stat-icon">💰</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">Custom Names</div>
                    <div class="stat-value">{{ number_format($withCustomName) }}</div>
                    <div class="stat-badge">
                        <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        Modified
                    </div>
                </div>
                <div class="stat-icon">✏️</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">With Descriptions</div>
                    <div class="stat-value">{{ number_format($withDescription) }}</div>
                    <div class="stat-badge">
                        <svg width="11" height="11" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        Complete
                    </div>
                </div>
                <div class="stat-icon">📝</div>
            </div>
        </div>
    </div>

    {{-- ── Search ───────────────────────────────────────────────────── --}}
    <div class="search-section">
        <div class="search-box">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                id="searchInput"
                placeholder="Search by name, ID, category, or type…"
                oninput="filterServices()"
            >
        </div>
        <div class="search-count">
            Showing <strong id="visibleCount">{{ $totalServices }}</strong> of {{ $totalServices }} services
        </div>
    </div>

    {{-- ── Name Replace Tool ───────────────────────────────────────────── --}}
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:18px;flex-wrap:wrap;">
        <input type="text" id="nameSearchInput" placeholder="Find substring in service names (e.g. 'monarch')" class="form-input" style="max-width:360px;" oninput="previewNameMatches()">
        <input type="text" id="nameReplaceInput" placeholder="Replace with (e.g. 'easyget')" class="form-input" style="max-width:240px;">
        <button class="gmp-btn" onclick="previewNameMatches()">Preview Matches</button>
        <form id="replaceForm" method="POST" action="{{ route('admin.social.replaceNames') }}" style="display:inline-block;margin:0;">
            @csrf
            <input type="hidden" name="provider" value="{{ $selectedProvider ?? '' }}">
            <input type="hidden" name="search_term" id="replaceFormSearch">
            <input type="hidden" name="replace_with" id="replaceFormReplace">
            <button type="button" class="gmp-btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);" onclick="applyReplace()">Apply Replace</button>
        </form>
        <div id="previewCount" style="color:#94a3b8;font-size:13px;margin-left:8px;">Matches: 0</div>
    </div>

    {{-- ── Services Table ───────────────────────────────────────────── --}}
    <div class="table-card">
        <div class="table-header">
            <h3>Social Media Services</h3>
            <span class="table-badge" id="tableCountBadge">{{ $totalServices }} services</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Markup</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody id="servicesTable">
                    @forelse($services as $service)
                        @php
                            $pct = intval($service['admin_percentage'] ?? 0);
                            $pctClass = $pct === 0 ? 'pct-zero' : ($pct <= 10 ? 'pct-low' : ($pct <= 30 ? 'pct-mid' : 'pct-high'));
                        @endphp
                        <tr class="service-row">
                            <td>
                                <span style="font-family:'Courier New',monospace;background:#0f172a;padding:3px 8px;border-radius:6px;font-size:12px;color:#93c5fd;border:1px solid #1e3a8a;">
                                    {{ $service['service_id'] }}
                                </span>
                            </td>
                            <td>
                                <div class="service-name">
                                    {{ $service['name'] }}
                                    @if(!empty($service['custom_name']))
                                        <span class="custom-badge">custom</span>
                                    @endif
                                </div>
                                @if(!empty($service['custom_name']) && $service['custom_name'] !== $service['name'])
                                    <div style="font-size:11px;color:#64748b;margin-top:3px;">
                                        orig: {{ Str::limit($service['name'], 40) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="pct-badge {{ $pctClass }}">{{ $pct }}%</span>
                            </td>
                            <td>
                                <span style="background:#1e293b;border:1px solid #334155;padding:3px 10px;border-radius:8px;font-size:12px;font-weight:500;color:#cbd5e1;">
                                    {{ $service['category'] ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span style="background:#1e3a5f;color:#93c5fd;padding:3px 10px;border-radius:8px;font-size:12px;font-weight:500;">
                                    {{ $service['type'] ?? 'Default' }}
                                </span>
                            </td>
                            <td>
                                <div class="description-text {{ !empty($service['description']) ? 'has-desc' : '' }}"
                                     title="{{ $service['description'] ?? '' }}">
                                    {{ $service['description'] ? Str::limit($service['description'], 55) : '—' }}
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <button
                                    onclick="openModal(
                                        '{{ $service['service_id'] }}',
                                        {{ json_encode($service['name']) }},
                                        {{ json_encode($service['description'] ?? '') }},
                                        {{ json_encode($service['custom_name'] ?? '') }},
                                        {{ json_encode($service['category'] ?? '') }},
                                        {{ intval($service['admin_percentage'] ?? 0) }}
                                    )"
                                    class="action-btn"
                                >
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    <h4>No Services Available</h4>
                                    <p>Click "Refresh Services" to load from the API.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->
</div><!-- /.social-container -->


{{-- ── Edit Modal ───────────────────────────────────────────────────── --}}
<div id="descriptionModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Edit Service Configuration</h2>
                <div class="modal-service-id" id="modalServiceIdDisplay"></div>
            </div>
            <button class="close-btn" onclick="closeModal()" aria-label="Close">×</button>
        </div>

        <div class="modal-body">
            <form id="descriptionForm" method="POST" action="{{ route('admin.social.updateDescription') }}">
                @csrf
                <input type="hidden" name="provider" id="modalProviderInput" value="{{ $selectedProvider ?? '' }}">
                <input type="hidden" name="service_id" id="modalServiceId">

                <div class="form-group">
                    <label class="form-label" for="modalServiceName">Custom Display Name</label>
                    <input
                        type="text"
                        name="custom_name"
                        id="modalServiceName"
                        placeholder="Leave blank to use the default API name"
                        class="form-input"
                    />
                    <p class="form-help">Shown to users instead of the raw API name.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modalServiceCategory">Custom Category</label>
                    <input
                        type="text"
                        name="custom_category"
                        id="modalServiceCategory"
                        placeholder="Leave blank to use the default category"
                        class="form-input"
                    />
                    <p class="form-help">Groups this service under a different section on the user page.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modalAdminPercentage">Admin Markup %</label>
                    <div class="pct-input-wrap">
                        <input
                            type="number"
                            name="admin_percentage"
                            id="modalAdminPercentage"
                            min="0"
                            step="1"
                            placeholder="0"
                            class="form-input"
                        />
                        <span class="pct-suffix">%</span>
                    </div>
                    <p class="form-help">Overrides the global markup for this service only. e.g. 15 = 15% added to base price.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modalDescription">Service Description</label>
                    <textarea
                        name="description"
                        id="modalDescription"
                        rows="5"
                        placeholder="Describe what this service offers, delivery time, requirements…"
                        class="form-textarea"
                    ></textarea>
                    <p class="form-help">Shown to users on the order page.</p>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// ─── CSRF ────────────────────────────────────────────────────────────
const _csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// ─── Refresh services ────────────────────────────────────────────────
function refreshServices(btn) {
    btn = btn || event?.target?.closest('button');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<svg class="spin" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg> Refreshing…`;
    }
    window.location.href = "{{ route('admin.social.refresh', ['provider' => $selectedProviderRoute]) }}";
}

// ─── Global bulk markup ──────────────────────────────────────────────
function handleBulkSubmit(e) {
    e.preventDefault();

    const pct = parseInt(document.getElementById('globalPercentageInput').value, 10);
    if (isNaN(pct) || pct < 0) {
        alert('Please enter a valid percentage (0 or higher).');
        return false;
    }

    const rows  = document.querySelectorAll('.service-row').length;
    const ok = confirm(
        `Set a ${pct}% markup on ALL ${rows.toLocaleString()} services?\n\n` +
        `• Services not yet in the database will be created with this markup.\n` +
        `• Existing descriptions, custom names, and categories are NOT changed.\n\n` +
        `Continue?`
    );
    if (!ok) return false;

    const btn = document.getElementById('globalMarkupBtn');
    btn.disabled = true;
    btn.innerHTML = `<svg class="spin" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg> Applying…`;

    // Refresh CSRF before submit
    const tokenInput = document.querySelector('#globalMarkupForm input[name="_token"]');
    if (tokenInput) tokenInput.value = _csrf();

    document.getElementById('globalMarkupForm').submit();
    return false;
}

// ─── Search / filter ─────────────────────────────────────────────────
function filterServices() {
    const q    = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.service-row');
    let   vis  = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) vis++;
    });

    document.getElementById('visibleCount').textContent   = vis.toLocaleString();
    document.getElementById('tableCountBadge').textContent = vis.toLocaleString() + ' services';
}

// Preview matches for name replace using the current table DOM
function previewNameMatches() {
    const q = document.getElementById('nameSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#servicesTable .service-row');
    let matches = 0;

    rows.forEach(row => {
        const nameCell = row.querySelector('.service-name');
        const text = nameCell ? nameCell.textContent.toLowerCase() : row.textContent.toLowerCase();
        const show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show && q) matches++;
    });

    document.getElementById('previewCount').textContent = 'Matches: ' + matches;
    document.getElementById('visibleCount').textContent = document.querySelectorAll('#servicesTable .service-row:not([style*="display: none"])').length.toLocaleString();
    document.getElementById('tableCountBadge').textContent = document.getElementById('visibleCount').textContent + ' services';
}

function applyReplace() {
    const search = document.getElementById('nameSearchInput').value.trim();
    const replace = document.getElementById('nameReplaceInput').value.trim();
    if (!search) { alert('Enter a search term to replace.'); return; }
    if (!replace) { if(!confirm('Replace with an empty string? This will remove the match from names. Continue?')) return; }

    if (!confirm('This will create/update records for all matching services. Continue?')) return;

    document.getElementById('replaceFormSearch').value = search;
    document.getElementById('replaceFormReplace').value = replace;
    document.getElementById('replaceForm').submit();
}

// ─── Edit modal ───────────────────────────────────────────────────────
function openModal(serviceId, name, description, customName, customCategory, adminPercentage) {
    document.getElementById('modalServiceId').value         = serviceId;
    document.getElementById('modalServiceIdDisplay').textContent = 'Service ID: ' + serviceId;
    document.getElementById('modalServiceName').value       = customName  || '';
    document.getElementById('modalServiceCategory').value   = customCategory || '';
    document.getElementById('modalAdminPercentage').value   = adminPercentage ?? 0;
    document.getElementById('modalDescription').value       = description || '';

    // Update modal title with service name for clarity
    document.getElementById('modalTitle').textContent = 'Edit: ' + (customName || name);

    document.getElementById('descriptionModal').classList.add('active');
    // Focus first input for keyboard users
    setTimeout(() => document.getElementById('modalServiceName').focus(), 100);
}

function closeModal() {
    document.getElementById('descriptionModal').classList.remove('active');
}

// ─── Save modal form ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const form    = document.getElementById('descriptionForm');
    const saveBtn = document.getElementById('saveBtn');

    form.addEventListener('submit', function () {
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<svg class="spin" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg> Saving…`;

        // Refresh CSRF
        const csrfInput = form.querySelector('input[name="_token"]');
        if (csrfInput) csrfInput.value = _csrf();
    });
});

// ─── Close modal on backdrop click or Escape ──────────────────────────
document.getElementById('descriptionModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('descriptionModal').classList.contains('active')) {
        closeModal();
    }
});
</script>
@endsection