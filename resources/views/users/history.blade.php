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
        <!-- Active Polling Indicator -->
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
        @forelse($socialBoosts as $boost)
            <div onclick="openSocialModal({{ $boost['id'] }})"
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
                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1
                        {{ $boost['raw_status'] === 'success' || $boost['raw_status'] === 'completed' 
                            ? 'bg-green-100 text-green-700' :
                        ($boost['raw_status'] === 'processing' ? 'bg-blue-100 text-blue-700' :
                        ($boost['raw_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                        ($boost['raw_status'] === 'partial' ? 'bg-orange-100 text-orange-700' 
                        : 'bg-gray-100 text-gray-700'))) }}
                        {{ $boost['status'] }}
                    </span>
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
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <!-- Modal content will be loaded here -->
    </div>
</div>

<!-- Social Boost Modal -->
<div id="socialModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <!-- Modal content will be loaded here -->
    </div>
</div>

@include('partials.footer')

<script>
let pollingInterval = null;
let currentActivationId = null;
let currentSocialBoostId = null;
let globalPollingInterval = null;
let activationPollingStatus = {};

// ==================== AUTO-POLLING ON PAGE LOAD ====================
document.addEventListener('DOMContentLoaded', function() {
    const activations = @json($activations);
    
    // Start auto-polling for all pending activations
    activations.forEach(activation => {
        if (activation.raw_status === 'pending') {
            activationPollingStatus[activation.id] = true;
        }
    });
    
    // Poll all pending orders every 3 seconds
    if (Object.keys(activationPollingStatus).length > 0) {
        startGlobalPolling();
    }
});

function startGlobalPolling() {
    if (globalPollingInterval) return; // Prevent duplicate intervals
    
    // Show polling indicator
    const indicator = document.getElementById('pollingIndicator');
    if (indicator) document.getElementById('pollingIndicator').classList.remove('hidden');
    
    globalPollingInterval = setInterval(() => {
        const activations = @json($activations);
        let anyPending = false;
        
        activations.forEach(activation => {
            if (activation.raw_status === 'pending' && activationPollingStatus[activation.id]) {
                anyPending = true;
                checkForSmsBackground(activation.id);
            }
        });
        
        // Hide indicator if no more pending activations
        if (!anyPending) {
            stopGlobalPolling();
        }
    }, 3000); // Check every 3 seconds
}

function stopGlobalPolling() {
    if (globalPollingInterval) {
        clearInterval(globalPollingInterval);
        globalPollingInterval = null;
        
        // Hide polling indicator
        const indicator = document.getElementById('pollingIndicator');
        if (indicator) indicator.classList.add('hidden');
    }
}

function checkForSmsBackground(activationId) {
    fetch('/app/history/get-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ activation_id: activationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.sms_code) {
            // SMS received - update the card and modal if open
            activationPollingStatus[activationId] = false;
            updateActivationCardFrontend(activationId, data.sms_code, 'completed');
            
            // If modal is open with this activation, update it
            if (currentActivationId === activationId) {
                updateOpenModalWithSms(activationId, data.sms_code);
            }
            
            showToast('📱 SMS received! Check your history', 'success');
        } else if (data.status === 'expired') {
            activationPollingStatus[activationId] = false;
            updateActivationCardFrontend(activationId, null, 'expired');
            showToast('Activation expired', 'error');
        }
    })
    .catch(error => console.error('Background SMS check error:', error));
}

function updateActivationCardFrontend(activationId, smsCode, status) {
    const card = document.querySelector(`[data-activation-id="${activationId}"]`);
    if (card) {
        const badge = card.querySelector('.otp-status');
        if (badge) {
            if (status === 'completed') {
                badge.textContent = 'Completed';
                badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-green-100 text-green-700';
            } else if (status === 'expired') {
                badge.textContent = 'Expired';
                badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-red-100 text-red-700';
            }
        }
    }
}

function updateOpenModalWithSms(activationId, smsCode) {
    const smsSection = document.getElementById('smsCodeSection');
    if (smsSection) {
        smsSection.innerHTML = `
            <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 animate-pulse">
                <div class="flex items-center justify-between">
                    <p class="text-3xl font-bold text-green-700 tracking-wider">${smsCode}</p>
                    <button onclick="copyToClipboard('${smsCode}')" class="text-green-600 hover:text-green-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>`;

        const statusBadge = document.getElementById('statusBadge');
        if (statusBadge) {
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700';
        }
    }
    
    stopPolling();
}

// ==================== TAB SWITCHING ====================
document.getElementById('activationsBtn').addEventListener('click', () => switchTab('activations'));
document.getElementById('socialsBtn').addEventListener('click', () => switchTab('socials'));

function switchTab(tab) {
    const activationsBtn = document.getElementById('activationsBtn');
    const socialsBtn = document.getElementById('socialsBtn');
    const activationsContent = document.getElementById('activationsContent');
    const socialsContent = document.getElementById('socialsContent');

    if (tab === 'activations') {
        activationsBtn.classList.add('bg-green-800', 'text-white');
        activationsBtn.classList.remove('bg-gray-200', 'text-gray-600');
        socialsBtn.classList.remove('bg-purple-600', 'text-white');
        socialsBtn.classList.add('bg-gray-200', 'text-gray-600');
        activationsContent.classList.remove('hidden');
        socialsContent.classList.add('hidden');
    } else {
        socialsBtn.classList.add('bg-purple-600', 'text-white');
        socialsBtn.classList.remove('bg-gray-200', 'text-gray-600');
        activationsBtn.classList.remove('bg-green-800', 'text-white');
        activationsBtn.classList.add('bg-gray-200', 'text-gray-600');
        socialsContent.classList.remove('hidden');
        activationsContent.classList.add('hidden');
    }
}

// ==================== SOCIAL BOOST MODAL ====================

function openSocialModal(boostId) {
    currentSocialBoostId = boostId;
    const modal = document.getElementById('socialModal');

    const socialBoosts = @json($socialBoosts);
    const boost = socialBoosts.find(b => b.id === boostId);

    if (!boost) {
        alert('Social boost order not found');
        return;
    }

    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-700',
        'processing': 'bg-blue-100 text-blue-700',
        'completed': 'bg-green-100 text-green-700',
        'partial': 'bg-orange-100 text-orange-700',
        'failed': 'bg-red-100 text-red-700',
        'cancelled': 'bg-gray-100 text-gray-700',
        'refunded': 'bg-purple-100 text-purple-700'
    };

    const statusColor = statusColors[boost.raw_status] || 'bg-gray-100 text-gray-700';

    const modalContent = `
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
                <button onclick="closeSocialModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Service Info -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-2xl">
                            ${boost.service_logo}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">${boost.service_name}</p>
                            <p class="text-xs text-gray-600">${boost.category}</p>
                        </div>
                    </div>
                    <span id="modal-status-badge" class="px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                        ${boost.status}
                    </span>
                </div>
            </div>

            <!-- Link -->
            ${boost.link ? `
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <p class="text-xs text-gray-500 font-medium uppercase mb-1">Target Link</p>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 truncate flex-1 mr-2">${boost.link}</p>
                    <button onclick="copyToClipboard('${boost.link}')" class="text-purple-600 hover:text-purple-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            ` : ''}

            <!-- Progress Section -->
            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 mb-4" id="modal-progress-section">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500 font-medium uppercase">Order Progress</p>
                    ${boost.raw_status === 'pending' || boost.raw_status === 'processing' ? `
                    <button onclick="checkSocialStatus(${boost.id})" id="refresh-status-btn" class="text-xs text-purple-600 hover:text-purple-700 font-medium">
                        Refresh Status
                    </button>
                    ` : ''}
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm" id="modal-progress-grid">
                    <div>
                        <p class="text-gray-500">Quantity</p>
                        <p class="font-semibold text-gray-900">${boost.quantity.toLocaleString()}</p>
                    </div>
                    ${boost.start_count ? `
                    <div>
                        <p class="text-gray-500">Start Count</p>
                        <p class="font-semibold text-gray-900" id="modal-start-count">${boost.start_count.toLocaleString()}</p>
                    </div>
                    ` : ''}
                    ${boost.remains !== null && boost.remains !== undefined ? `
                    <div>
                        <p class="text-gray-500">Remaining</p>
                        <p class="font-semibold text-gray-900" id="modal-remains">${boost.remains.toLocaleString()}</p>
                    </div>
                    ` : ''}
                </div>

                ${boost.raw_status === 'processing' || boost.raw_status === 'pending' ? `
                <div class="mt-3" id="modal-progress-bar">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full animate-pulse" id="modal-progress-fill" style="width: ${boost.remains ? ((boost.quantity - boost.remains) / boost.quantity * 100).toFixed(0) : 0}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 text-center" id="modal-progress-text">
                        ${boost.remains ? `${((boost.quantity - boost.remains) / boost.quantity * 100).toFixed(0)}% Complete` : 'Processing...'}
                    </p>
                </div>
                ` : ''}
            </div>

            <!-- Transaction Details -->
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
        </div>
    `;

    modal.querySelector('.bg-white').innerHTML = modalContent;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSocialModal() {
    const modal = document.getElementById('socialModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    currentSocialBoostId = null;
}

function checkSocialStatus(boostId) {
    const button = event?.target || document.getElementById('refresh-status-btn');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Checking...';

    fetch('/app/history/get-social-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ transaction_id: boostId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            updateModalStatus(data.data);
            showToast('Status updated successfully!', 'success');

            if (data.data.local_status === 'completed') {
                setTimeout(() => location.reload(), 2000);
            } else {
                button.disabled = false;
                button.textContent = originalText;
            }
        } else {
            showToast(data.message || 'Unable to fetch status', 'error');
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error checking status:', error);
        showToast('Error checking status', 'error');
        button.disabled = false;
        button.textContent = originalText;
    });
}

function updateModalStatus(statusData) {
    const statusBadge = document.getElementById('modal-status-badge');
    if (statusBadge && statusData.status_label) {
        statusBadge.textContent = statusData.status_label;

        const localStatus = statusData.local_status;
        statusBadge.className = 'px-2 py-1 text-xs font-semibold rounded-full ';

        if (localStatus === 'completed') {
            statusBadge.className += 'bg-green-100 text-green-700';
        } else if (localStatus === 'processing' || localStatus === 'pending') {
            statusBadge.className += 'bg-blue-100 text-blue-700';
        } else if (localStatus === 'partial') {
            statusBadge.className += 'bg-orange-100 text-orange-700';
        } else {
            statusBadge.className += 'bg-red-100 text-red-700';
        }
    }

    if (statusData.start_count) {
        const startCountEl = document.getElementById('modal-start-count');
        if (startCountEl) {
            startCountEl.textContent = parseInt(statusData.start_count).toLocaleString();
        }
    }

    if (statusData.remains !== null && statusData.remains !== undefined) {
        const remainsEl = document.getElementById('modal-remains');
        if (remainsEl) {
            remainsEl.textContent = parseInt(statusData.remains).toLocaleString();
        }

        const progressFill = document.getElementById('modal-progress-fill');
        const progressText = document.getElementById('modal-progress-text');

        if (progressFill && progressText) {
            const socialBoosts = @json($socialBoosts);
            const boost = socialBoosts.find(b => b.id === currentSocialBoostId);

            if (boost) {
                const quantity = boost.quantity;
                const remains = parseInt(statusData.remains);
                const progress = ((quantity - remains) / quantity * 100).toFixed(0);

                progressFill.style.width = progress + '%';
                progressText.textContent = progress + '% Complete';
            }
        }
    }
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-in`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ==================== ACTIVATION MODAL ====================

function openActivationModal(arg1, arg2) {
    let activationId = null;
    if (typeof arg1 === 'number') {
        activationId = arg1;
    } else if (typeof arg2 === 'number') {
        try { arg1.preventDefault(); arg1.stopPropagation(); } catch (e) {}
        activationId = arg2;
    } else {
        return;
    }

    currentActivationId = activationId;
    const modal = document.getElementById('activationModal');

    const activations = @json($activations);
    const activation = activations.find(a => a.id === activationId);

    if (!activation) {
        alert('Activation not found');
        return;
    }

    const modalContent = `
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Activation Details</h2>
                <button onclick="closeActivationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        ${activation.service_logo ?
                            `<img src="${activation.service_logo}" alt="${activation.service_name}" class="w-12 h-12 object-contain bg-white rounded-lg p-2">` :
                            `<div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white font-bold">${activation.service_name.substring(0, 2).toUpperCase()}</div>`
                        }
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
                    <button onclick="copyToClipboard('${activation.number}')" class="text-green-600 hover:text-green-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="bg-white border-2 border-gray-200 rounded-xl p-4 mb-4" id="smsCodeSection">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500 font-medium uppercase">SMS Code</p>
                    <span id="statusBadge" class="px-2 py-1 text-xs font-semibold rounded-full
                        ${activation.raw_status === 'completed' ? 'bg-green-100 text-green-700' :
                          activation.raw_status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                          'bg-red-100 text-red-700'}">
                        ${activation.status}
                    </span>
                </div>

                ${activation.sms_code ? `
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-bold text-green-700 tracking-wider">${activation.sms_code}</p>
                            <button onclick="copyToClipboard('${activation.sms_code}')" class="text-green-600 hover:text-green-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                ` : activation.raw_status === 'pending' ? `
                    <div class="text-center py-6">
                        <div class="flex items-center justify-center mb-3">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                            <span class="ml-2 text-sm text-green-600 font-medium">Checking for SMS...</span>
                        </div>
                        <p class="text-gray-600 font-medium">Waiting for SMS...</p>
                        <p class="text-sm text-gray-500 mt-1" id="countdownTimer"></p>
                    </div>
                ` : `
                    <div class="text-center py-4">
                        <p class="text-red-600 font-medium">No SMS received</p>
                        <p class="text-sm text-gray-500 mt-1" id="countdownTimer">Ensure to use the number before 5mins</p>
                    </div>
                `}
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Amount</p>
                        <p class="font-semibold text-gray-900">${activation.amount}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date</p>
                        <p class="font-semibold text-gray-900">${activation.date}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Request ID</p>
                        <p class="font-semibold text-gray-900 text-xs">${activation.request_id}</p>
                    </div>
                </div>
            </div>

            ${activation.raw_status === 'pending' ? `
                <button onclick="cancelActivation(${activation.id}, this)"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                    Cancel & Refund
                </button>
            ` : ''}
        </div>
    `;

    modal.querySelector('.bg-white').innerHTML = modalContent;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    if (activation.raw_status === 'pending') {
        setTimeout(function() {
            updateCountdown(activation.expires_at);
        }, 10);
        startPolling(activationId, activation.expires_at);

        if (window.countdownExpireCheck) clearInterval(window.countdownExpireCheck);
        window.countdownExpireCheck = setInterval(() => {
            updateCountdown(activation.expires_at);
            const timer = document.getElementById('countdownTimer');
            if (timer && timer.textContent && timer.textContent.includes('expired')) {
                checkForSms(activationId);
                clearInterval(window.countdownExpireCheck);
            }
        }, 1000);
    }
}

function closeActivationModal() {
    const modal = document.getElementById('activationModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    stopPolling();
    currentActivationId = null;
}

function startPolling(activationId, expiresAt) {
    stopPolling();

    updateCountdown(expiresAt);
    const countdownInterval = setInterval(() => updateCountdown(expiresAt), 1000);

    pollingInterval = setInterval(() => {
        checkForSms(activationId);
    }, 5000);

    window.countdownInterval = countdownInterval;
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    if (window.countdownInterval) {
        clearInterval(window.countdownInterval);
        window.countdownInterval = null;
    }
    if (window.countdownExpireCheck) {
        clearInterval(window.countdownExpireCheck);
        window.countdownExpireCheck = null;
    }
}

function updateCountdown(expiresAt) {
    const timer = document.getElementById('countdownTimer');
    if (!timer) return;
    const now = new Date().getTime();
    const expiry = new Date(expiresAt).getTime();
    const remaining = expiry - now;
    if (remaining <= 0) {
        timer.textContent = 'This activation has expired';
        stopPolling();
        return;
    }
    const hours = Math.floor(remaining / 3600000);
    const minutes = Math.floor((remaining % 3600000) / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    timer.textContent = `Expires in ${hours.toString().padStart(2,'0')}:${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
}

function checkForSms(activationId) {
    fetch('/app/history/get-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ activation_id: activationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.sms_code) {
            stopPolling();

            const smsSection = document.getElementById('smsCodeSection');
            if (smsSection) {
                smsSection.innerHTML = `
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-bold text-green-700 tracking-wider">${data.sms_code}</p>
                            <button onclick="copyToClipboard('${data.sms_code}')" class="text-green-600 hover:text-green-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>`;

                const statusBadge = document.getElementById('statusBadge');
                if (statusBadge) {
                    statusBadge.textContent = 'Completed';
                    statusBadge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700';
                }
            }

            const card = document.querySelector(`[data-activation-id="${activationId}"]`);
            if (card) {
                const badge = card.querySelector('.otp-status');
                if (badge) {
                    badge.textContent = 'Completed';
                    badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-green-100 text-green-700';
                }
            }

            showToast('SMS received', 'success');

        } else if (data.status === 'expired') {
            stopPolling();

            const smsSection = document.getElementById('smsCodeSection');
            if (smsSection) {
                smsSection.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-red-600 font-medium">No SMS received</p>
                        <p class="text-sm text-gray-500 mt-1">This activation has expired</p>
                    </div>`;
            }

            const statusBadge = document.getElementById('statusBadge');
            if (statusBadge) {
                statusBadge.textContent = 'Expired';
                statusBadge.className = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700';
            }

            const card = document.querySelector(`[data-activation-id="${activationId}"]`);
            if (card) {
                const badge = card.querySelector('.otp-status');
                if (badge) {
                    badge.textContent = 'Expired';
                    badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-red-100 text-red-700';
                }
            }
        }
    })
    .catch(error => console.error('Error checking SMS:', error));
}

function cancelActivation(activationId, btnEl) {
    if (!confirm('Are you sure you want to cancel this activation? Your balance will be refunded.')) {
        return;
    }

    const button = btnEl || (event && event.target) || null;
    if (button) {
        button.disabled = true;
        button.textContent = 'Cancelling...';
    }

    fetch('/app/history/cancel-activation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ activation_id: activationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Activation cancelled and refunded', 'success');
            closeActivationModal();

            const card = document.querySelector(`[data-activation-id="${activationId}"]`);
            if (card) {
                const badge = card.querySelector('.otp-status');
                if (badge) {
                    badge.textContent = 'Cancelled';
                    badge.className = 'otp-status inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1 bg-gray-100 text-gray-700';
                }
            }
        } else {
            alert(data.message || 'Failed to cancel activation');
            if (button) {
                button.disabled = false;
                button.textContent = 'Cancel & Refund';
            }
        }
    })
    .catch(error => {
        console.error('Error cancelling activation:', error);
        alert('An error occurred while cancelling');
        if (button) {
            button.disabled = false;
            button.textContent = 'Cancel & Refund';
        }
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy to clipboard');
    });
}

// Close modals on outside click
document.getElementById('activationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeActivationModal();
    }
});

document.getElementById('socialModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSocialModal();
    }
});
</script>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes slide-in {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slide-out {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
.animate-slide-in {
    animation: slide-in 0.3s ease-out;
}
.animate-slide-out {
    animation: slide-out 0.3s ease-out;
}
</style>

@endsection