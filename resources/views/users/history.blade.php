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
        <button id="socialsBtn" class="flex-1 text-center py-3 rounded-full font-semibold bg-black text-white opacity-50 cursor-not-allowed" disabled>Socials (Coming Soon)</button>
    </div>

    <!-- Activations Content -->
    <div id="activationsContent" class="space-y-3 px-4 transition-all duration-500 ease-in-out">
        @forelse($activations as $activation)
        <div onclick="openActivationModal({{ $activation['id'] }})" 
             class="cursor-pointer flex items-center justify-between bg-white p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow">
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
                <p class="font-semibold text-gray-800">{{ $activation['amount'] }}</p>
                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1
                    @if($activation['raw_status'] === 'completed') bg-green-100 text-green-700
                    @elseif($activation['raw_status'] === 'pending') bg-yellow-100 text-yellow-700
                    @else bg-red-100 text-red-700
                    @endif">
                    {{ $activation['status'] }}
                </span>
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 font-medium">No activation history yet</p>
            <p class="text-sm text-gray-400 mt-1">Your activations will appear here</p>
            <a href="{{ route('services.list') }}" class="inline-block mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Get Started
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

@include('partials.footer')

<script>
let pollingInterval = null;
let currentActivationId = null;

// Auto-open modal for new activation
@if($newActivationId)
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            openActivationModal({{ $newActivationId }});
        }, 500);
    });
@endif

function openActivationModal(activationId) {
    currentActivationId = activationId;
    const modal = document.getElementById('activationModal');
    
    // Find activation data
    const activations = @json($activations);
    const activation = activations.find(a => a.id === activationId);
    
    if (!activation) {
        alert('Activation not found');
        return;
    }
    
    // Build modal content
    const modalContent = `
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Activation Details</h2>
                <button onclick="closeActivationModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Service Info -->
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
            
            <!-- Phone Number -->
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
            
            <!-- SMS Code Section -->
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
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-3"></div>
                        <p class="text-gray-600 font-medium">Waiting for SMS...</p>
                        <p class="text-sm text-gray-500 mt-1" id="countdownTimer"></p>
                    </div>
                ` : `
                    <div class="text-center py-4">
                        <p class="text-red-600 font-medium">No SMS received</p>
                        <p class="text-sm text-gray-500 mt-1">This activation has ${activation.raw_status}</p>
                    </div>
                `}
            </div>
            
            <!-- Transaction Details -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Amount</p>
                        <p class="font-semibold text-gray-900">${activation.amount}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Provider</p>
                        <p class="font-semibold text-gray-900">${activation.provider === 'smsbus' ? 'SMS-Bus' : '5sim'}</p>
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
            
            <!-- Actions -->
            ${activation.raw_status === 'pending' ? `
                <button onclick="cancelActivation(${activation.id})" 
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                    Cancel & Refund
                </button>
            ` : ''}
        </div>
    `;
    
    modal.querySelector('.bg-white').innerHTML = modalContent;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Start polling if pending
    if (activation.raw_status === 'pending') {
        startPolling(activationId, activation.expires_at);
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
    
    // Update countdown
    updateCountdown(expiresAt);
    const countdownInterval = setInterval(() => updateCountdown(expiresAt), 1000);
    
    // Poll for SMS every 5 seconds
    pollingInterval = setInterval(() => {
        checkForSms(activationId);
    }, 5000);
    
    // Store countdown interval
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
}

function updateCountdown(expiresAt) {
    const timer = document.getElementById('countdownTimer');
    if (!timer) return;
    
    const now = new Date().getTime();
    const expiry = new Date(expiresAt).getTime();
    const remaining = expiry - now;
    
    if (remaining <= 0) {
        timer.textContent = 'Expired';
        stopPolling();
        location.reload();
        return;
    }
    
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    timer.textContent = `Expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
}

function checkForSms(activationId) {
    fetch('/history/get-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ activation_id: activationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.sms_code) {
            stopPolling();
            location.reload();
        } else if (data.status === 'expired') {
            stopPolling();
            location.reload();
        }
    })
    .catch(error => console.error('Error checking SMS:', error));
}

function cancelActivation(activationId) {
    if (!confirm('Are you sure you want to cancel this activation? Your balance will be refunded.')) {
        return;
    }
    
    const button = event.target;
    button.disabled = true;
    button.textContent = 'Cancelling...';
    
    fetch('/history/cancel-activation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ activation_id: activationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to cancel activation');
            button.disabled = false;
            button.textContent = 'Cancel & Refund';
        }
    })
    .catch(error => {
        console.error('Error cancelling activation:', error);
        alert('An error occurred while cancelling');
        button.disabled = false;
        button.textContent = 'Cancel & Refund';
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        toast.textContent = 'Copied to clipboard!';
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy to clipboard');
    });
}

// Close modal on outside click
document.getElementById('activationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeActivationModal();
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
</style>
@endsection