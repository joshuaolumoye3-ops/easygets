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
        <button id="socialsBtn" class="flex-1 text-center py-3 rounded-full font-semibold bg-gray-200 text-gray-600">Socials</button>
    </div>

    <!-- Activations Content -->
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

    <!-- Socials Content -->
    <div id="socialsContent" class="space-y-3 px-4 transition-all duration-500 ease-in-out hidden">

        <!-- Social polling indicator -->
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
                    <!-- progress mini bar -->
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
</div>

<!-- Activation Modal -->
<div id="activationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"></div>
</div>

<!-- Social Boost Modal -->
<div id="socialModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"></div>
</div>

@include('partials.footer')

@php
// Helper used above in the blade loop — inline so no separate file needed
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

<script>
// ─── Data from server ────────────────────────────────────────────────────────
const ALL_ACTIVATIONS  = @json($activations);
const ALL_SOCIAL_BOOSTS = @json($socialBoosts);

// ─── State ───────────────────────────────────────────────────────────────────
let pollingInterval        = null;
let currentActivationId    = null;
let currentSocialBoostId   = null;
let globalPollingInterval  = null;
let socialPollingInterval  = null;
let activationPollingStatus = {};

// Track which social boosts we're still polling (stops once success/failed)
let socialPollingStatus = {};   // { id: true/false }
// Live data updated by polling — used to refresh open modal
let socialLiveData = {};        // { id: { remains, start_count, local_status, ... } }

// ─── Badge CSS helper (mirrors PHP) ──────────────────────────────────────────
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
    return map[status] || status.charAt(0).toUpperCase() + status.slice(1);
}

// ─── DOM ready ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    // --- Activation polling ---
    ALL_ACTIVATIONS.forEach(a => {
        if (a.raw_status === 'pending') activationPollingStatus[a.id] = true;
    });
    if (Object.keys(activationPollingStatus).length > 0) startGlobalPolling();

    // --- Social boost auto-polling ---
    const pendingSocials = ALL_SOCIAL_BOOSTS.filter(
        b => b.raw_status === 'pending' || b.raw_status === 'processing'
    );

    if (pendingSocials.length > 0) {
        pendingSocials.forEach(b => { socialPollingStatus[b.id] = true; });
        startSocialPolling();
    }
});

// ════════════════════════════════════════════════════════════════════════
// SOCIAL BOOST POLLING
// ════════════════════════════════════════════════════════════════════════

function startSocialPolling() {
    if (socialPollingInterval) return;

    document.getElementById('socialPollingIndicator')?.classList.remove('hidden');

    socialPollingInterval = setInterval(() => {
        const stillPending = Object.values(socialPollingStatus).some(v => v);
        if (!stillPending) {
            stopSocialPolling();
            return;
        }

        Object.entries(socialPollingStatus).forEach(([id, active]) => {
            if (active) pollOneSocialBoost(parseInt(id));
        });
    }, 6000); // every 6 s — MTP doesn't need hammering

    // Also fire immediately so we don't wait 6 s for first update
    Object.keys(socialPollingStatus).forEach(id => pollOneSocialBoost(parseInt(id)));
}

function stopSocialPolling() {
    if (socialPollingInterval) {
        clearInterval(socialPollingInterval);
        socialPollingInterval = null;
    }
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

        const d          = data.data;
        const localStatus = d.local_status;

        // Cache live data for modal refreshing
        socialLiveData[boostId] = d;

        // Stop polling this boost once it reaches a terminal state
        if (['success', 'completed', 'failed', 'cancelled', 'refunded', 'partial'].includes(localStatus)) {
            socialPollingStatus[boostId] = false;
        }

        // Update card badge
        updateSocialCard(boostId, localStatus, d);

        // If this boost's modal is currently open, refresh it
        if (currentSocialBoostId === boostId) {
            updateModalStatus(d);
        }
    })
    .catch(() => {}); // silent — user shouldn't see polling errors
}

function updateSocialCard(boostId, localStatus, data) {
    const badge   = document.getElementById(`social-badge-${boostId}`);
    const spinner = document.getElementById(`social-spinner-${boostId}`);

    if (badge) {
        badge.textContent  = statusLabel(localStatus);
        badge.className    = `inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 ${badgeClass(localStatus)}`;
    }

    // Hide spinner once terminal
    if (spinner && ['success', 'completed', 'failed', 'cancelled', 'refunded'].includes(localStatus)) {
        spinner.classList.add('hidden');
    }

    // Update mini progress bar if we have remains
    const qty = ALL_SOCIAL_BOOSTS.find(b => b.id === boostId)?.quantity || 0;
    if (qty > 0 && data.remains !== null && data.remains !== undefined) {
        const remains  = parseInt(data.remains);
        const pct      = Math.max(0, Math.min(100, Math.round((qty - remains) / qty * 100)));

        const fill = document.getElementById(`social-progress-fill-${boostId}`);
        const pctEl = document.getElementById(`social-progress-pct-${boostId}`);

        if (fill)  fill.style.width   = pct + '%';
        if (pctEl) pctEl.textContent  = pct + '%';

        // Show bar if it wasn't rendered server-side (remains was null initially)
        const bar = document.getElementById(`social-progress-bar-${boostId}`);
        if (bar) bar.classList.remove('hidden');
    }
}

// ════════════════════════════════════════════════════════════════════════
// SOCIAL BOOST MODAL
// ════════════════════════════════════════════════════════════════════════

function openSocialModal(boostId) {
    currentSocialBoostId = boostId;
    const modal = document.getElementById('socialModal');
    const boost = ALL_SOCIAL_BOOSTS.find(b => b.id === boostId);
    if (!boost) return;

    // Merge any live-polled data we already have
    const live = socialLiveData[boostId] || {};
    const remains   = live.remains     ?? boost.remains;
    const startCount= live.start_count ?? boost.start_count;
    const rawStatus = live.local_status ?? boost.raw_status;
    const label     = statusLabel(rawStatus);
    const qty       = boost.quantity || 0;

    const pct = (qty > 0 && remains !== null && remains !== undefined)
        ? Math.max(0, Math.min(100, Math.round((qty - parseInt(remains)) / qty * 100)))
        : 0;

    const isActive = rawStatus === 'pending' || rawStatus === 'processing';

    const modalContent = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
                <button onclick="closeSocialModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Service header -->
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

            <!-- Link -->
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
            </div>
            ` : ''}

            <!-- Progress -->
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

                <div class="grid grid-cols-2 gap-3 text-sm mb-3" id="modal-progress-grid">
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

                <!-- Progress bar -->
                <div id="modal-progress-bar">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="modal-progress-fill"
                             class="h-2.5 rounded-full transition-all duration-500 ${rawStatus === 'success' || rawStatus === 'completed' ? 'bg-green-500' : 'bg-purple-600'}"
                             style="width: ${pct}%"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-gray-400">${isActive ? 'In progress...' : label}</p>
                        <p class="text-xs text-gray-500 font-medium" id="modal-progress-text">${pct}% complete</p>
                    </div>
                </div>
            </div>

            <!-- Transaction details -->
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

    modal.querySelector('.bg-white').innerHTML = modalContent;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSocialModal() {
    document.getElementById('socialModal').classList.add('hidden');
    document.getElementById('socialModal').classList.remove('flex');
    currentSocialBoostId = null;
}

// Manual refresh button inside modal
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

// Update the OPEN modal with fresh data
function updateModalStatus(d) {
    const localStatus = d.local_status;
    const label       = statusLabel(localStatus);

    // Status badge
    const badge = document.getElementById('modal-status-badge');
    if (badge) {
        badge.textContent = label;
        badge.className   = `px-2 py-1 text-xs font-semibold rounded-full ${badgeClass(localStatus)}`;
    }

    // Counts
    if (d.start_count !== null && d.start_count !== undefined) {
        const el = document.getElementById('modal-start-count');
        if (el) el.textContent = parseInt(d.start_count).toLocaleString();
    }

    const boostQty = ALL_SOCIAL_BOOSTS.find(b => b.id === currentSocialBoostId)?.quantity || 0;

    if (d.remains !== null && d.remains !== undefined) {
        const remains  = parseInt(d.remains);
        const delivered = Math.max(0, boostQty - remains);
        const pct      = boostQty > 0 ? Math.max(0, Math.min(100, Math.round(delivered / boostQty * 100))) : 0;

        const remainsEl   = document.getElementById('modal-remains');
        const deliveredEl = document.getElementById('modal-delivered');
        const fill        = document.getElementById('modal-progress-fill');
        const pctEl       = document.getElementById('modal-progress-text');

        if (remainsEl)   remainsEl.textContent   = remains.toLocaleString();
        if (deliveredEl) deliveredEl.textContent  = delivered.toLocaleString();
        if (fill)        fill.style.width         = pct + '%';
        if (pctEl)       pctEl.textContent        = pct + '% complete';

        // Turn bar green on completion
        if (fill && (localStatus === 'success' || localStatus === 'completed')) {
            fill.classList.replace('bg-purple-600', 'bg-green-500');
        }
    }
}

// ════════════════════════════════════════════════════════════════════════
// ACTIVATION POLLING (unchanged logic)
// ════════════════════════════════════════════════════════════════════════

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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ activation_id: activationId })
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
    const card = document.querySelector(`[data-activation-id="${activationId}"]`);
    if (!card) return;
    const badge = card.querySelector('.otp-status');
    if (!badge) return;
    if (status === 'completed') {
        badge.textContent = 'Completed';
        badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-green-100 text-green-700';
    } else if (status === 'expired') {
        badge.textContent = 'Expired';
        badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-red-100 text-red-700';
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>`;
        const statusBadge = document.getElementById('statusBadge');
        if (statusBadge) { statusBadge.textContent = 'Completed'; statusBadge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700'; }
    }
    stopPolling();
}

// ════════════════════════════════════════════════════════════════════════
// TAB SWITCHING
// ════════════════════════════════════════════════════════════════════════

document.getElementById('activationsBtn').addEventListener('click', () => switchTab('activations'));
document.getElementById('socialsBtn').addEventListener('click', () => switchTab('socials'));

function switchTab(tab) {
    if (tab === 'activations') {
        document.getElementById('activationsBtn').className = 'flex-1 text-center py-3 rounded-full font-semibold bg-green-800 text-white mr-2';
        document.getElementById('socialsBtn').className     = 'flex-1 text-center py-3 rounded-full font-semibold bg-gray-200 text-gray-600';
        document.getElementById('activationsContent').classList.remove('hidden');
        document.getElementById('socialsContent').classList.add('hidden');
    } else {
        document.getElementById('socialsBtn').className     = 'flex-1 text-center py-3 rounded-full font-semibold bg-purple-600 text-white';
        document.getElementById('activationsBtn').className = 'flex-1 text-center py-3 rounded-full font-semibold bg-gray-200 text-gray-600';
        document.getElementById('socialsContent').classList.remove('hidden');
        document.getElementById('activationsContent').classList.add('hidden');
    }
}

// ════════════════════════════════════════════════════════════════════════
// ACTIVATION MODAL (unchanged)
// ════════════════════════════════════════════════════════════════════════

function openActivationModal(arg1, arg2) {
    let activationId = typeof arg1 === 'number' ? arg1 : (typeof arg2 === 'number' ? arg2 : null);
    if (!activationId) return;
    try { if (arg1 && arg1.preventDefault) { arg1.preventDefault(); arg1.stopPropagation(); } } catch(e){}

    currentActivationId = activationId;
    const modal      = document.getElementById('activationModal');
    const activation = ALL_ACTIVATIONS.find(a => a.id === activationId);
    if (!activation) return;

    const modalContent = `
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 mb-4" id="smsCodeSection">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500 font-medium uppercase">SMS Code</p>
                    <span id="statusBadge" class="px-2 py-1 text-xs font-semibold rounded-full
                        ${activation.raw_status === 'completed' ? 'bg-green-100 text-green-700' : activation.raw_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'}">
                        ${activation.status}
                    </span>
                </div>
                ${activation.sms_code ? `
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-bold text-green-700 tracking-wider">${activation.sms_code}</p>
                            <button onclick="copyToClipboard('${activation.sms_code}', this)" class="text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
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

    modal.querySelector('.bg-white').innerHTML = modalContent;
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
    pollingInterval = setInterval(() => checkForSms(activationId), 5000);
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ activation_id: activationId })
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>`;
                const badge = document.getElementById('statusBadge');
                if (badge) { badge.textContent = 'Completed'; badge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700'; }
            }
            updateActivationCardFrontend(activationId, data.sms_code, 'completed');
            showToast('SMS received', 'success');
        } else if (data.status === 'expired') {
            stopPolling();
            const smsSection = document.getElementById('smsCodeSection');
            if (smsSection) smsSection.innerHTML = `<div class="text-center py-4"><p class="text-red-600 font-medium">No SMS received</p><p class="text-sm text-gray-500 mt-1">This activation has expired</p></div>`;
            const badge = document.getElementById('statusBadge');
            if (badge) { badge.textContent = 'Expired'; badge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700'; }
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ activation_id: activationId })
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
    .catch(() => { alert('An error occurred'); if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Cancel & Refund'; } });
}

// ════════════════════════════════════════════════════════════════════════
// UTILITIES
// ════════════════════════════════════════════════════════════════════════

function copyToClipboard(text, btnEl) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied!', 'success');
        if (btnEl) { const orig = btnEl.innerHTML; btnEl.innerHTML = '✓'; setTimeout(() => btnEl.innerHTML = orig, 1500); }
    }).catch(() => showToast('Failed to copy', 'error'));
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-in text-sm`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.add('animate-slide-out'); setTimeout(() => toast.remove(), 300); }, 3000);
}

// Close modals on backdrop click
document.getElementById('activationModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeActivationModal(); });
document.getElementById('socialModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeSocialModal(); });
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:.5} }
@keyframes slide-in { from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1} }
@keyframes slide-out { from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0} }
.animate-spin  { animation: spin 1s linear infinite; }
.animate-pulse { animation: pulse 2s cubic-bezier(.4,0,.6,1) infinite; }
.animate-slide-in  { animation: slide-in .3s ease-out; }
.animate-slide-out { animation: slide-out .3s ease-out; }
</style>
@endsection