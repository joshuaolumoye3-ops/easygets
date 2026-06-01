@extends('layouts.app')

@section('title', 'History')

@section('content')
<div class="max-w-md mx-auto py-6 min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="relative flex items-center px-4 mb-4 h-12 bg-white">
        <a href="{{ route('dashboard') }}" class="focus:outline-none z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 hover:text-green-700" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd" />
            </svg>
        </a>
        <h1 class="absolute inset-0 flex justify-center items-center text-lg font-semibold">History</h1>
    </div>

    <!-- Tabs -->
    <div class="flex justify-between px-4 mb-6 bg-white py-4">
        <button id="activationsBtn" class="flex-1 text-center py-3 rounded-full font-semibold bg-green-800 text-white mr-2">Activations</button>
        <button id="socialsBtn"     class="flex-1 text-center py-3 rounded-full font-semibold bg-gray-200 text-gray-600 mr-2">Socials</button>
        <button id="logsBtn"        class="flex-1 text-center py-3 rounded-full font-semibold bg-gray-200 text-gray-600">Logs</button>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         ACTIVATIONS TAB
    ══════════════════════════════════════════════════════════ -->
    <div id="activationsContent" class="space-y-3 px-4 transition-all duration-500 ease-in-out">
        <div id="pollingIndicator" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent"></div>
            <span class="text-sm text-blue-700 font-medium">Auto-checking for SMS codes...</span>
        </div>

        @forelse($activations as $activation)
            <div data-activation-id="{{ $activation['id'] }}"
                 data-otp-order-id="{{ $activation['request_id'] ?? '' }}"
                 data-otp-status="{{ $activation['raw_status'] ?? '' }}"
                 onclick="openActivationModal(event, {{ $activation['id'] }})"
                 class="cursor-pointer flex items-center justify-between bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow relative group">
                <div class="flex items-center space-x-3">
                    <img src="https://flagcdn.com/w40/{{ $activation['flag'] }}.png"
                         alt="flag"
                         class="w-10 h-10 rounded-full object-cover border-2 border-gray-100">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $activation['number'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $activation['service_name'] }} • {{ $activation['country_name'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activation['date'] }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1
                        {{ $activation['raw_status'] === 'completed' ? 'bg-green-100 text-green-700' :
                          ($activation['raw_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $activation['status'] }}
                    </span>
                    @if($activation['raw_status'] === 'pending')
                        <div class="hidden group-hover:flex items-center space-x-1 text-xs text-blue-600 mt-1">
                            <div class="animate-spin rounded-full h-3 w-3 border-2 border-blue-600 border-t-transparent"></div>
                            <span>Checking...</span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📱</div>
                <p class="text-gray-500 font-medium">No activation history yet</p>
                <p class="text-sm text-gray-400 mt-1">Your SMS activations will appear here</p>
            </div>
        @endforelse
    </div>

    <!-- ══════════════════════════════════════════════════════════
         SOCIALS TAB
    ══════════════════════════════════════════════════════════ -->
    <div id="socialsContent" class="space-y-3 px-4 transition-all duration-500 ease-in-out hidden">

        <div id="socialPollingIndicator" class="hidden bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center space-x-2">
            <div class="animate-spin rounded-full h-4 w-4 border-2 border-purple-600 border-t-transparent"></div>
            <span class="text-sm text-purple-700 font-medium">Updating order statuses...</span>
        </div>

        @forelse($socialBoosts as $boost)
            <div id="social-card-{{ $boost['id'] }}"
                 onclick="openSocialModal({{ $boost['id'] }})"
                 class="cursor-pointer flex items-center justify-between bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-xl">
                        {{ $boost['service_logo'] }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $boost['service_name'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $boost['category'] }} • {{ number_format($boost['quantity']) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $boost['date'] }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span id="social-badge-{{ $boost['id'] }}"
                          class="inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 {{ socialBadgeClass($boost['raw_status']) }}">
                        {{ $boost['status'] }}
                    </span>
                    @if(in_array($boost['raw_status'], ['pending', 'processing']))
                        <div id="social-spinner-{{ $boost['id'] }}" class="flex items-center justify-end space-x-1 text-xs text-purple-600 mt-1">
                            <div class="animate-spin rounded-full h-3 w-3 border-2 border-purple-400 border-t-transparent"></div>
                            <span>Checking...</span>
                        </div>
                    @endif
                    @if($boost['quantity'] > 0 && $boost['remains'] !== null)
                        @php $pct = max(0, min(100, round(($boost['quantity'] - $boost['remains']) / $boost['quantity'] * 100))); @endphp
                        <div id="social-progress-bar-{{ $boost['id'] }}" class="w-16 mt-1 ml-auto">
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div id="social-progress-fill-{{ $boost['id'] }}"
                                     class="bg-purple-500 h-1 rounded-full transition-all"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                            <p id="social-progress-pct-{{ $boost['id'] }}" class="text-xs text-gray-400 text-right mt-0.5">{{ $pct }}%</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <div class="text-6xl mb-4">🚀</div>
                <p class="text-gray-500 font-medium">No social boost history yet</p>
                <p class="text-sm text-gray-400 mt-1">Your social orders will appear here</p>
                <a href="{{ route('social-boost.index') }}" class="inline-block mt-4 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    Start Boosting
                </a>
            </div>
        @endforelse
    </div>

    <!-- ══════════════════════════════════════════════════════════
         LOGS TAB
    ══════════════════════════════════════════════════════════ -->
    <div id="logsContent" class="space-y-3 px-4 transition-all duration-500 ease-in-out hidden">

        @if(empty($logs) || count($logs) === 0)
            <div class="logs-empty">
                <div class="logs-empty-icon">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h3>No logs orders yet</h3>
                <p>Once you purchase a logs access package, your credentials and order status will appear here.</p>
                <a href="{{ route('logs') }}">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Browse Logs Products
                </a>
            </div>
        @else
            @foreach($logs as $log)
                @php
                    $isFulfilled = in_array(strtolower($log['status'] ?? ''), ['success', 'completed', 'fulfilled']);
                    $isPending   = strtolower($log['status'] ?? '') === 'pending';
                    $hasDetails  = !empty($log['login_details']);
                @endphp

                <div class="log-card" id="log-{{ $log['id'] }}">

                    {{-- Header row --}}
                    <div class="log-card-head">
                        <div class="log-icon {{ $hasDetails ? 'fulfilled' : '' }}">
                            @if($hasDetails)
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            @else
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>

                        <div class="log-meta">
                            <div class="log-name">{{ $log['service_name'] ?? 'Logs' }}</div>
                            <div class="log-sub">
                                @if(!empty($log['reference']))
                                    <span class="log-ref">{{ $log['reference'] }}</span>
                                @endif
                                @if(!empty($log['country']))
                                    <span class="log-sep"></span>
                                    <span>{{ $log['country'] }}</span>
                                @endif
                                <span class="log-sep"></span>
                                <span class="log-amt">{{ $log['amount'] ?? '₦0.00' }}</span>
                                <span class="log-sep"></span>
                                <span>{{ $log['date'] ?? 'N/A' }}</span>
                            </div>
                        </div>

                        <div style="flex-shrink:0;">
                            <span class="log-status-chip {{ $hasDetails ? 'chip-fulfilled' : ($isPending ? 'chip-pending' : 'chip-failed') }}">
                                <span class="dot"></span>
                                {{ $hasDetails ? 'Fulfilled' : ($isPending ? 'Pending' : ucfirst($log['status'] ?? 'Unknown')) }}
                            </span>
                        </div>
                    </div>

                    {{-- Credentials block --}}
                    @if($hasDetails)
                        <div class="log-creds" id="creds-wrap-{{ $log['id'] }}">
                            <div class="log-creds-title">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Your Login Details
                            </div>
                            <pre class="log-creds-body" id="creds-body-{{ $log['id'] }}">{{ $log['login_details'] }}</pre>
                            <button
                                class="log-copy-btn"
                                id="copy-btn-{{ $log['id'] }}"
                                onclick="copyLogCreds({{ $log['id'] }})">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                    @endif

                    {{-- Admin notes block --}}
                    @if(!empty($log['notes']))
                        <div class="log-notes">
                            <div class="log-notes-label">Note from support</div>
                            {{ $log['notes'] }}
                        </div>
                    @endif

                    {{-- Pending / no-credentials state --}}
                    @if(!$hasDetails)
                        <div class="log-pending-block">
                            <div class="log-pending-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="log-pending-text">
                                <h4>We're preparing your order</h4>
                                <p>
                                    Our team is working on delivering your login credentials.
                                    This usually takes a few minutes — check back shortly
                                    or refresh the page to see the latest update.
                                </p>
                                <div class="log-pending-steps">
                                    <span class="log-pending-step done">
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Payment confirmed
                                    </span>
                                    <span class="log-pending-step done">
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Order received
                                    </span>
                                    <span class="log-pending-step" style="color:#fcd34d; border-color:rgba(251,191,36,0.25);">
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Delivering credentials…
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>{{-- .log-card --}}
            @endforeach
        @endif

    </div>{{-- #logsContent --}}

</div>{{-- .max-w-md --}}

<!-- ══════════════════════════════════════════════════════════
     ACTIVATION MODAL
══════════════════════════════════════════════════════════ -->
<div id="activationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"></div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SOCIAL BOOST MODAL
══════════════════════════════════════════════════════════ -->
<div id="socialModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"></div>
</div>

@include('partials.footer')

@php
function socialBadgeClass(string $status): string {
    return match($status) {
        'success', 'completed' => 'bg-green-100 text-green-700',
        'processing'           => 'bg-blue-100 text-blue-700',
        'pending'              => 'bg-yellow-100 text-yellow-700',
        'partial'              => 'bg-orange-100 text-orange-700',
        'failed'               => 'bg-red-100 text-red-700',
        default                => 'bg-gray-100 text-gray-700',
    };
}
@endphp

{{-- ══════════════════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════════════════ --}}
<style>
/* ── Animations ──────────────────────────────────────────── */
@keyframes spin      { to { transform: rotate(360deg); } }
@keyframes pulse     { 0%,100%{opacity:1} 50%{opacity:.5} }
@keyframes slide-in  { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
@keyframes slide-out { from{transform:translateX(0);opacity:1}    to{transform:translateX(100%);opacity:0} }
@keyframes logPulse  { 0%,100%{opacity:1} 50%{opacity:.35} }

.animate-spin      { animation: spin 1s linear infinite; }
.animate-pulse     { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
.animate-slide-in  { animation: slide-in .3s ease-out; }
.animate-slide-out { animation: slide-out .3s ease-out; }

/* ── Log cards ───────────────────────────────────────────── */
.log-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    margin-bottom: 14px;
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
}
.log-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}

.log-card-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px 14px;
}

.log-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
    background: rgba(107,114,128,.08);
    border: 1px solid rgba(107,114,128,.15);
    color: #6b7280;
}
.log-icon.fulfilled {
    background: rgba(16,185,129,.1);
    border-color: rgba(16,185,129,.2);
    color: #059669;
}

.log-meta { flex: 1; min-width: 0; }
.log-name {
    font-size: 14px; font-weight: 700; color: #111827;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 5px;
}
.log-sub {
    display: flex; align-items: center; gap: 7px;
    font-size: 12px; color: #9ca3af; flex-wrap: wrap;
}
.log-ref {
    font-family: ui-monospace, monospace; font-size: 11px; font-weight: 600;
    color: #4f46e5;
    background: rgba(79,70,229,.08);
    border: 1px solid rgba(79,70,229,.15);
    padding: 2px 7px; border-radius: 6px;
}
.log-amt   { color: #d97706; font-weight: 700; font-family: ui-monospace, monospace; }
.log-sep   { width: 3px; height: 3px; border-radius: 50%; background: #d1d5db; flex-shrink: 0; }

/* Status chips */
.log-status-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px;
    font-size: 11.5px; font-weight: 700; white-space: nowrap;
}
.log-status-chip .dot { width: 6px; height: 6px; border-radius: 50%; }
.chip-fulfilled { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.chip-fulfilled .dot { background: #10b981; }
.chip-pending   { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
.chip-pending   .dot { background: #f59e0b; animation: logPulse 2s infinite; }
.chip-failed    { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
.chip-failed    .dot { background: #ef4444; }

/* Credentials block */
.log-creds {
    margin: 0 18px 16px;
    background: #f9fafb;
    border: 1px solid #d1fae5;
    border-left: 3px solid #10b981;
    border-radius: 0 10px 10px 0;
    padding: 14px 16px;
    position: relative;
}
.log-creds-title {
    display: flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 700;
    color: #065f46; margin-bottom: 10px;
    text-transform: uppercase; letter-spacing: 0.6px;
}
.log-creds-body {
    font-family: ui-monospace, monospace;
    font-size: 12.5px; color: #374151;
    white-space: pre-wrap; word-break: break-all; line-height: 1.8;
    margin: 0;
}
.log-copy-btn {
    position: absolute; top: 12px; right: 12px;
    background: #fff; border: 1px solid #d1d5eb;
    color: #4f46e5; border-radius: 8px;
    padding: 5px 11px; font-size: 11.5px; font-weight: 700;
    cursor: pointer; font-family: inherit;
    display: flex; align-items: center; gap: 5px;
    transition: background .2s, color .2s, border-color .2s;
}
.log-copy-btn:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.log-copy-btn.copied {
    background: #ecfdf5; border-color: #a7f3d0; color: #065f46;
}

/* Admin notes */
.log-notes {
    margin: 0 18px 16px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 12.5px; color: #92400e; line-height: 1.6;
}
.log-notes-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px;
    color: #b45309; margin-bottom: 4px;
}

/* Pending state */
.log-pending-block {
    margin: 0 18px 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex; gap: 14px; align-items: flex-start;
}
.log-pending-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: #fffbeb; border: 1px solid #fcd34d;
    display: flex; align-items: center; justify-content: center;
    color: #d97706; flex-shrink: 0;
}
.log-pending-text h4 {
    font-size: 14px; font-weight: 700; color: #111827; margin: 0 0 5px;
}
.log-pending-text p {
    font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;
}
.log-pending-steps {
    display: flex; gap: 6px; margin-top: 12px; flex-wrap: wrap;
}
.log-pending-step {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; color: #9ca3af;
    background: #fff; border: 1px solid #e5e7eb;
    padding: 4px 10px; border-radius: 20px;
}
.log-pending-step.done {
    color: #065f46; border-color: #a7f3d0; background: #ecfdf5;
}

/* Logs empty state */
.logs-empty {
    padding: 64px 24px; text-align: center;
    display: flex; flex-direction: column; align-items: center; gap: 14px;
}
.logs-empty-icon {
    width: 64px; height: 64px; border-radius: 14px;
    background: rgba(79,70,229,.08); border: 1px solid rgba(79,70,229,.18);
    display: flex; align-items: center; justify-content: center;
    color: #4f46e5; margin-bottom: 4px;
}
.logs-empty h3 { font-size: 16px; font-weight: 700; color: #374151; margin: 0; }
.logs-empty p  { font-size: 13px; color: #9ca3af; max-width: 280px; line-height: 1.7; margin: 0; }
.logs-empty a  {
    display: inline-flex; align-items: center; gap: 7px;
    height: 38px; padding: 0 18px;
    background: rgba(79,70,229,.08); color: #4f46e5;
    border: 1px solid rgba(79,70,229,.2); border-radius: 10px;
    font-size: 13px; font-weight: 700; text-decoration: none;
    transition: background .2s, color .2s;
}
.logs-empty a:hover { background: #4f46e5; color: #fff; }
</style>

{{-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════ --}}
<script>
// ─── Server data ──────────────────────────────────────────────────────────────
const ALL_ACTIVATIONS   = @json($activations);
const ALL_SOCIAL_BOOSTS = @json($socialBoosts);
const ALL_LOGS          = @json($logs ?? []);

// Build a keyed map for quick O(1) lookup in modal
const logsData = {};
ALL_LOGS.forEach(log => { logsData[log.id] = log; });

// ─── State ────────────────────────────────────────────────────────────────────
let pollingInterval         = null;
let currentActivationId     = null;
let currentSocialBoostId    = null;
let globalPollingInterval   = null;
let socialPollingInterval   = null;
let activationPollingStatus = {};   // { activationId: true/false }
let socialPollingStatus     = {};   // { boostId: true/false }
let socialLiveData          = {};   // { boostId: latestDataFromApi }

// ─── Badge helpers (mirrors PHP) ─────────────────────────────────────────────
function badgeClass(status) {
    const map = {
        success:    'bg-green-100 text-green-700',
        completed:  'bg-green-100 text-green-700',
        processing: 'bg-blue-100 text-blue-700',
        pending:    'bg-yellow-100 text-yellow-700',
        partial:    'bg-orange-100 text-orange-700',
        failed:     'bg-red-100 text-red-700',
        cancelled:  'bg-gray-100 text-gray-700',
        refunded:   'bg-purple-100 text-purple-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
}

function statusLabel(status) {
    const map = {
        pending:    'Processing',
        processing: 'In Progress',
        success:    'Completed',
        completed:  'Completed',
        partial:    'Partial',
        failed:     'Failed',
        cancelled:  'Cancelled',
        refunded:   'Refunded',
    };
    return map[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : '');
}

// ═════════════════════════════════════════════════════════════════════════════
// DOM READY
// ═════════════════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {

    // ── Activation background polling ──────────────────────────────────────
    ALL_ACTIVATIONS.forEach(a => {
        if (a.raw_status === 'pending') activationPollingStatus[a.id] = true;
    });
    if (Object.keys(activationPollingStatus).length > 0) startGlobalPolling();

    // ── Social boost background polling ───────────────────────────────────
    const pendingSocials = ALL_SOCIAL_BOOSTS.filter(
        b => b.raw_status === 'pending' || b.raw_status === 'processing'
    );
    if (pendingSocials.length > 0) {
        pendingSocials.forEach(b => { socialPollingStatus[b.id] = true; });
        startSocialPolling();
    }

    // ── Tab switching ──────────────────────────────────────────────────────
    document.getElementById('activationsBtn').addEventListener('click', () => showTab('activations'));
    document.getElementById('socialsBtn').addEventListener('click',     () => showTab('socials'));
    document.getElementById('logsBtn').addEventListener('click',        () => showTab('logs'));

    // ── URL param redirect ─────────────────────────────────────────────────
    const tabParam = new URLSearchParams(window.location.search).get('tab');
    if (tabParam === 'logs')    showTab('logs');
    else if (tabParam === 'socials') showTab('socials');
    else showTab('activations');

    // ── Modal backdrop click ───────────────────────────────────────────────
    document.getElementById('activationModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeActivationModal();
    });
    document.getElementById('socialModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeSocialModal();
    });
});

// ═════════════════════════════════════════════════════════════════════════════
// TAB SWITCHING
// ═════════════════════════════════════════════════════════════════════════════
function showTab(tab) {
    // Hide all content panels
    ['activationsContent', 'socialsContent', 'logsContent'].forEach(id => {
        document.getElementById(id)?.classList.add('hidden');
    });

    // Reset all buttons to inactive style
    const btnIds = { activations: 'activationsBtn', socials: 'socialsBtn', logs: 'logsBtn' };
    Object.values(btnIds).forEach(id => {
        const btn = document.getElementById(id);
        if (!btn) return;
        btn.classList.remove('bg-green-800', 'bg-purple-600', 'bg-indigo-600', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-600');
    });

    // Activate selected tab
    const activeColors = {
        activations: ['bg-green-800', 'text-white'],
        socials:     ['bg-green-800', 'text-white'],
        logs:        ['bg-green-800', 'text-white'],
    };

    const contentId = tab === 'activations' ? 'activationsContent'
                    : tab === 'socials'      ? 'socialsContent'
                    : 'logsContent';

    document.getElementById(contentId)?.classList.remove('hidden');

    const activeBtn = document.getElementById(btnIds[tab]);
    if (activeBtn) {
        activeBtn.classList.remove('bg-gray-200', 'text-gray-600');
        activeColors[tab].forEach(cls => activeBtn.classList.add(cls));
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// LOGS — copy credentials inline (no modal needed)
// ═════════════════════════════════════════════════════════════════════════════
function copyLogCreds(id) {
    const body = document.getElementById('creds-body-' + id);
    const btn  = document.getElementById('copy-btn-' + id);
    if (!body || !btn) return;

    navigator.clipboard.writeText(body.innerText).then(function () {
        btn.classList.add('copied');
        btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg> Copied!`;
        setTimeout(function () {
            btn.classList.remove('copied');
            btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg> Copy`;
        }, 2500);
    }).catch(function () {
        // Fallback: select the text so the user can Ctrl+C
        const range = document.createRange();
        range.selectNodeContents(body);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    });
}

// ═════════════════════════════════════════════════════════════════════════════
// SOCIAL BOOST POLLING
// ═════════════════════════════════════════════════════════════════════════════
function startSocialPolling() {
    if (socialPollingInterval) return;
    document.getElementById('socialPollingIndicator')?.classList.remove('hidden');

    // Fire once immediately, then every 6 s
    Object.keys(socialPollingStatus).forEach(id => pollOneSocialBoost(parseInt(id)));

    socialPollingInterval = setInterval(() => {
        const stillPending = Object.values(socialPollingStatus).some(v => v);
        if (!stillPending) { stopSocialPolling(); return; }
        Object.entries(socialPollingStatus).forEach(([id, active]) => {
            if (active) pollOneSocialBoost(parseInt(id));
        });
    }, 6000);
}

function stopSocialPolling() {
    if (socialPollingInterval) { clearInterval(socialPollingInterval); socialPollingInterval = null; }
    document.getElementById('socialPollingIndicator')?.classList.add('hidden');
}

function pollOneSocialBoost(boostId) {
    fetch('/app/history/get-social-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ transaction_id: boostId }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.data) return;
        const d           = data.data;
        const localStatus = d.local_status;

        socialLiveData[boostId] = d;

        if (['success', 'completed', 'failed', 'cancelled', 'refunded', 'partial'].includes(localStatus)) {
            socialPollingStatus[boostId] = false;
        }

        updateSocialCard(boostId, localStatus, d);

        if (currentSocialBoostId === boostId) updateModalStatus(d);
    })
    .catch(() => {});
}

function updateSocialCard(boostId, localStatus, data) {
    const badge   = document.getElementById(`social-badge-${boostId}`);
    const spinner = document.getElementById(`social-spinner-${boostId}`);

    if (badge) {
        badge.textContent = statusLabel(localStatus);
        badge.className   = `inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 ${badgeClass(localStatus)}`;
    }

    if (spinner && ['success', 'completed', 'failed', 'cancelled', 'refunded'].includes(localStatus)) {
        spinner.classList.add('hidden');
    }

    const qty = ALL_SOCIAL_BOOSTS.find(b => b.id === boostId)?.quantity || 0;
    if (qty > 0 && data.remains !== null && data.remains !== undefined) {
        const remains = parseInt(data.remains);
        const pct     = Math.max(0, Math.min(100, Math.round((qty - remains) / qty * 100)));

        const fill  = document.getElementById(`social-progress-fill-${boostId}`);
        const pctEl = document.getElementById(`social-progress-pct-${boostId}`);
        const bar   = document.getElementById(`social-progress-bar-${boostId}`);

        if (fill)  fill.style.width  = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (bar)   bar.classList.remove('hidden');
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// SOCIAL BOOST MODAL
// ═════════════════════════════════════════════════════════════════════════════
function openSocialModal(boostId) {
    currentSocialBoostId = boostId;
    const modal = document.getElementById('socialModal');
    const boost = ALL_SOCIAL_BOOSTS.find(b => b.id === boostId);
    if (!boost) return;

    const live       = socialLiveData[boostId] || {};
    const remains    = live.remains      ?? boost.remains;
    const startCount = live.start_count  ?? boost.start_count;
    const rawStatus  = live.local_status ?? boost.raw_status;
    const label      = statusLabel(rawStatus);
    const qty        = boost.quantity || 0;

    const pct = (qty > 0 && remains !== null && remains !== undefined)
        ? Math.max(0, Math.min(100, Math.round((qty - parseInt(remains)) / qty * 100)))
        : 0;

    const isActive = rawStatus === 'pending' || rawStatus === 'processing';

    modal.querySelector('.bg-white').innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
                <button onclick="closeSocialModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-2xl shadow-sm">
                            ${boost.service_logo}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">${boost.service_name}</p>
                            <p class="text-xs text-gray-500">${boost.category}</p>
                        </div>
                    </div>
                    <span id="modal-status-badge" class="px-2 py-1 text-xs font-semibold rounded-full ${badgeClass(rawStatus)}">
                        ${label}
                    </span>
                </div>
            </div>

            ${boost.link ? `
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <p class="text-xs text-gray-500 font-medium uppercase mb-1">Target Link</p>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-medium text-gray-900 truncate">${boost.link}</p>
                    <button onclick="copyToClipboard('${boost.link.replace(/'/g,"\\'")}', this)" class="text-purple-600 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>` : ''}

            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500 font-medium uppercase">Order Progress</p>
                    ${isActive ? `
                    <button onclick="checkSocialStatus(${boost.id})" id="refresh-status-btn"
                            class="text-xs text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>` : ''}
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                    <div>
                        <p class="text-gray-500">Quantity</p>
                        <p class="font-semibold text-gray-900">${qty.toLocaleString()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Start Count</p>
                        <p class="font-semibold text-gray-900" id="modal-start-count">
                            ${startCount !== null && startCount !== undefined ? parseInt(startCount).toLocaleString() : '—'}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Remaining</p>
                        <p class="font-semibold text-gray-900" id="modal-remains">
                            ${remains !== null && remains !== undefined ? parseInt(remains).toLocaleString() : '—'}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Delivered</p>
                        <p class="font-semibold text-purple-700" id="modal-delivered">
                            ${(remains !== null && remains !== undefined) ? (qty - parseInt(remains)).toLocaleString() : '—'}
                        </p>
                    </div>
                </div>

                <div id="modal-progress-bar">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="modal-progress-fill"
                             class="h-2.5 rounded-full transition-all duration-500 ${rawStatus === 'success' || rawStatus === 'completed' ? 'bg-green-500' : 'bg-purple-600'}"
                             style="width:${pct}%"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-gray-400">${isActive ? 'In progress...' : label}</p>
                        <p class="text-xs text-gray-500 font-medium" id="modal-progress-text">${pct}% complete</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Amount</p>
                        <p class="font-semibold text-gray-900">${boost.amount}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Service Type</p>
                        <p class="font-semibold text-gray-900">${boost.service_type}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date</p>
                        <p class="font-semibold text-gray-900 text-xs">${boost.date}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Order ID</p>
                        <p class="font-semibold text-gray-900 text-xs">${boost.request_id}</p>
                    </div>
                </div>
            </div>
        </div>`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSocialModal() {
    document.getElementById('socialModal').classList.add('hidden');
    document.getElementById('socialModal').classList.remove('flex');
    currentSocialBoostId = null;
}

function checkSocialStatus(boostId) {
    const btn = document.getElementById('refresh-status-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Checking...'; }

    fetch('/app/history/get-social-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ transaction_id: boostId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data) {
            socialLiveData[boostId] = data.data;
            updateModalStatus(data.data);
            updateSocialCard(boostId, data.data.local_status, data.data);
            showToast('Status updated!', 'success');
        } else {
            showToast(data.message || 'Unable to fetch status', 'error');
        }
    })
    .catch(() => showToast('Error checking status', 'error'))
    .finally(() => {
        if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
    });
}

function updateModalStatus(d) {
    const localStatus = d.local_status;
    const label       = statusLabel(localStatus);

    const badge = document.getElementById('modal-status-badge');
    if (badge) {
        badge.textContent = label;
        badge.className   = `px-2 py-1 text-xs font-semibold rounded-full ${badgeClass(localStatus)}`;
    }

    if (d.start_count !== null && d.start_count !== undefined) {
        const el = document.getElementById('modal-start-count');
        if (el) el.textContent = parseInt(d.start_count).toLocaleString();
    }

    const boostQty = ALL_SOCIAL_BOOSTS.find(b => b.id === currentSocialBoostId)?.quantity || 0;

    if (d.remains !== null && d.remains !== undefined) {
        const remains   = parseInt(d.remains);
        const delivered = Math.max(0, boostQty - remains);
        const pct       = boostQty > 0 ? Math.max(0, Math.min(100, Math.round(delivered / boostQty * 100))) : 0;

        const remainsEl   = document.getElementById('modal-remains');
        const deliveredEl = document.getElementById('modal-delivered');
        const fill        = document.getElementById('modal-progress-fill');
        const pctEl       = document.getElementById('modal-progress-text');

        if (remainsEl)   remainsEl.textContent  = remains.toLocaleString();
        if (deliveredEl) deliveredEl.textContent = delivered.toLocaleString();
        if (fill)        fill.style.width        = pct + '%';
        if (pctEl)       pctEl.textContent       = pct + '% complete';

        if (fill && (localStatus === 'success' || localStatus === 'completed')) {
            fill.classList.replace('bg-purple-600', 'bg-green-500');
        }
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTIVATION BACKGROUND POLLING
// ═════════════════════════════════════════════════════════════════════════════
function startGlobalPolling() {
    if (globalPollingInterval) return;
    document.getElementById('pollingIndicator')?.classList.remove('hidden');

    globalPollingInterval = setInterval(() => {
        let anyPending = false;
        ALL_ACTIVATIONS.forEach(a => {
            if (a.raw_status === 'pending' && activationPollingStatus[a.id]) {
                anyPending = true;
                checkForSmsBackground(a.id);
            }
        });
        if (!anyPending) stopGlobalPolling();
    }, 3000);
}

function stopGlobalPolling() {
    if (globalPollingInterval) { clearInterval(globalPollingInterval); globalPollingInterval = null; }
    document.getElementById('pollingIndicator')?.classList.add('hidden');
}

function checkForSmsBackground(activationId) {
    fetch('/app/history/get-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ activation_id: activationId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.sms_code) {
            activationPollingStatus[activationId] = false;
            updateActivationCardFrontend(activationId, data.sms_code, 'completed');
            if (currentActivationId === activationId) updateOpenModalWithSms(activationId, data.sms_code);
            showToast('📱 SMS received!', 'success');
        } else if (data.status === 'expired') {
            activationPollingStatus[activationId] = false;
            updateActivationCardFrontend(activationId, null, 'expired');
        }
    })
    .catch(() => {});
}

function updateActivationCardFrontend(activationId, smsCode, status) {
    const card  = document.querySelector(`[data-activation-id="${activationId}"]`);
    if (!card) return;
    const badge = card.querySelector('.otp-status');
    if (!badge) return;

    if (status === 'completed') {
        badge.textContent = 'Completed';
        badge.className   = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-green-100 text-green-700';
    } else if (status === 'expired') {
        badge.textContent = 'Expired';
        badge.className   = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-red-100 text-red-700';
    } else if (status === 'cancelled') {
        badge.textContent = 'Cancelled';
        badge.className   = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-gray-100 text-gray-700';
    }
}

function updateOpenModalWithSms(activationId, smsCode) {
    const smsSection = document.getElementById('smsCodeSection');
    if (smsSection) {
        smsSection.innerHTML = `
            <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-3xl font-bold text-green-700 tracking-wider">${smsCode}</p>
                    <button onclick="copyToClipboard('${smsCode}', this)" class="text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>`;
        const statusBadge = document.getElementById('statusBadge');
        if (statusBadge) {
            statusBadge.textContent = 'Completed';
            statusBadge.className   = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700';
        }
    }
    stopPolling();
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTIVATION MODAL
// ═════════════════════════════════════════════════════════════════════════════
function openActivationModal(arg1, arg2) {
    let activationId = typeof arg1 === 'number' ? arg1 : (typeof arg2 === 'number' ? arg2 : null);
    if (!activationId) return;
    try { if (arg1 && arg1.preventDefault) { arg1.preventDefault(); arg1.stopPropagation(); } } catch(e){}

    currentActivationId = activationId;
    const modal      = document.getElementById('activationModal');
    const activation = ALL_ACTIVATIONS.find(a => a.id === activationId);
    if (!activation) return;

    modal.querySelector('.bg-white').innerHTML = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Activation Details</h2>
                <button onclick="closeActivationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        ${activation.service_logo
                            ? `<img src="${activation.service_logo}" alt="${activation.service_name}" class="w-12 h-12 object-contain bg-white rounded-lg p-2">`
                            : `<div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white font-bold">${activation.service_name.substring(0,2).toUpperCase()}</div>`}
                        <div>
                            <p class="text-sm font-semibold text-gray-800">${activation.service_name}</p>
                            <p class="text-xs text-gray-600">${activation.country_name}</p>
                        </div>
                    </div>
                    <img src="https://flagcdn.com/w80/${activation.flag}.png" alt="flag" class="w-12 h-12 rounded-lg object-cover">
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <p class="text-xs text-gray-500 font-medium uppercase mb-1">Phone Number</p>
                <div class="flex items-center justify-between">
                    <p class="text-lg font-bold text-gray-900">${activation.number}</p>
                    <button onclick="copyToClipboard('${activation.number}', this)" class="text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 mb-4" id="smsCodeSection">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500 font-medium uppercase">SMS Code</p>
                    <span id="statusBadge" class="px-2 py-1 text-xs font-semibold rounded-full
                        ${activation.raw_status === 'completed' ? 'bg-green-100 text-green-700'
                          : activation.raw_status === 'pending'  ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-red-100 text-red-700'}">
                        ${activation.status}
                    </span>
                </div>
                ${activation.sms_code ? `
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-bold text-green-700 tracking-wider">${activation.sms_code}</p>
                            <button onclick="copyToClipboard('${activation.sms_code}', this)" class="text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                ` : activation.raw_status === 'pending' ? `
                    <div class="text-center py-6">
                        <div class="flex items-center justify-center mb-3">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                            <span class="ml-2 text-sm text-green-600 font-medium">Waiting for SMS...</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1" id="countdownTimer"></p>
                    </div>
                ` : `
                    <div class="text-center py-4">
                        <p class="text-red-600 font-medium">No SMS received</p>
                    </div>
                `}
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-gray-500">Amount</p><p class="font-semibold text-gray-900">${activation.amount}</p></div>
                    <div><p class="text-gray-500">Date</p><p class="font-semibold text-gray-900">${activation.date}</p></div>
                    <div><p class="text-gray-500">Request ID</p><p class="font-semibold text-gray-900 text-xs">${activation.request_id}</p></div>
                </div>
            </div>

            ${activation.raw_status === 'pending' ? `
                <button onclick="cancelActivation(${activation.id}, this)"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                    Cancel & Refund
                </button>` : ''}
        </div>`;

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    if (activation.raw_status === 'pending') {
        setTimeout(() => updateCountdown(activation.expires_at), 10);
        startPolling(activationId, activation.expires_at);
    }
}

function closeActivationModal() {
    document.getElementById('activationModal').classList.add('hidden');
    document.getElementById('activationModal').classList.remove('flex');
    stopPolling();
    currentActivationId = null;
}

function startPolling(activationId, expiresAt) {
    stopPolling();
    updateCountdown(expiresAt);
    window.countdownInterval = setInterval(() => updateCountdown(expiresAt), 1000);
    pollingInterval          = setInterval(() => checkForSms(activationId), 5000);
}

function stopPolling() {
    if (pollingInterval)          { clearInterval(pollingInterval); pollingInterval = null; }
    if (window.countdownInterval) { clearInterval(window.countdownInterval); window.countdownInterval = null; }
}

function updateCountdown(expiresAt) {
    const timer = document.getElementById('countdownTimer');
    if (!timer) return;
    const remaining = new Date(expiresAt).getTime() - Date.now();
    if (remaining <= 0) { timer.textContent = 'This activation has expired'; stopPolling(); return; }
    const h = Math.floor(remaining / 3600000);
    const m = Math.floor((remaining % 3600000) / 60000);
    const s = Math.floor((remaining % 60000) / 1000);
    timer.textContent = `Expires in ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function checkForSms(activationId) {
    fetch('/app/history/get-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ activation_id: activationId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.sms_code) {
            stopPolling();
            const smsSection = document.getElementById('smsCodeSection');
            if (smsSection) {
                smsSection.innerHTML = `
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-bold text-green-700 tracking-wider">${data.sms_code}</p>
                            <button onclick="copyToClipboard('${data.sms_code}', this)" class="text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>`;
                const badge = document.getElementById('statusBadge');
                if (badge) {
                    badge.textContent = 'Completed';
                    badge.className   = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700';
                }
            }
            updateActivationCardFrontend(activationId, data.sms_code, 'completed');
            showToast('SMS received', 'success');
        } else if (data.status === 'expired') {
            stopPolling();
            const smsSection = document.getElementById('smsCodeSection');
            if (smsSection) smsSection.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-red-600 font-medium">No SMS received</p>
                    <p class="text-sm text-gray-500 mt-1">This activation has expired</p>
                </div>`;
            const badge = document.getElementById('statusBadge');
            if (badge) {
                badge.textContent = 'Expired';
                badge.className   = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700';
            }
            updateActivationCardFrontend(activationId, null, 'expired');
        }
    })
    .catch(() => {});
}

function cancelActivation(activationId, btnEl) {
    if (!confirm('Cancel this activation? Your balance will be refunded.')) return;
    if (btnEl) { btnEl.disabled = true; btnEl.textContent = 'Cancelling...'; }

    fetch('/app/history/cancel-activation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ activation_id: activationId }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Cancelled and refunded', 'success');
            closeActivationModal();
            updateActivationCardFrontend(activationId, null, 'cancelled');
        } else {
            alert(data.message || 'Failed to cancel');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Cancel & Refund'; }
        }
    })
    .catch(() => {
        alert('An error occurred');
        if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Cancel & Refund'; }
    });
}

// ═════════════════════════════════════════════════════════════════════════════
// UTILITIES
// ═════════════════════════════════════════════════════════════════════════════
function copyToClipboard(text, btnEl) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied!', 'success');
        if (btnEl) {
            const orig = btnEl.innerHTML;
            btnEl.innerHTML = '✓';
            setTimeout(() => btnEl.innerHTML = orig, 1500);
        }
    }).catch(() => showToast('Failed to copy', 'error'));
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-in text-sm`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

@endsection