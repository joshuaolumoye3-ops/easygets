

@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Social Media Boost</h1>
        </div>

        @if(isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">{{ $error }}</p>
            <button onclick="location.reload()" class="mt-2 text-sm underline">Try Again</button>
        </div>
        @endif

        @if(empty($groupedServices))
        <div class="text-center py-12">
            <div class="text-6xl mb-4">⚠️</div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Services Not Available</h3>
            <p class="text-gray-600 mb-4">We're unable to load services at the moment.</p>
            <button onclick="clearCacheAndReload()" class="bg-brand-green text-white px-6 py-2 rounded-lg hover:bg-brand-green transition">
                Refresh Services
            </button>
        </div>
        @else

        <!-- Social Boost Form -->
        <form id="socialBoostForm" method="POST" class="space-y-8">
            @csrf

            <!-- Platform Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="platform" class="block text-sm font-semibold text-gray-700 mb-2">Platform</label>
                <div 
                    id="platformSelect" 
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-pointer focus:ring-2 focus:ring-brand-green focus:border-brand-green transition duration-200"
                >
                    Choose a Platform
                </div>
                <input type="hidden" name="platform" id="platformInput">
            </div>

            <!-- Service Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="service" class="block text-sm font-semibold text-gray-700 mb-2">Service</label>
                <div 
                    id="serviceSelect" 
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-not-allowed focus:ring-2 focus:ring-brand-green focus:border-brand-green transition duration-200 opacity-60"
                >
                    Choose a Service
                </div>
                <input type="hidden" name="service_id" id="serviceInput">
            </div>

            <!-- Link Input -->
            <div class="pt-6 lg:pt-0">
                <label for="link" class="block text-sm font-semibold text-gray-700 mb-2">Link/URL</label>
                <input 
                    type="url" 
                    id="link" 
                    name="link" 
                    placeholder="https://instagram.com/username"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required
                >
                <p class="text-xs text-gray-500 mt-1">Enter the profile, post, or video URL</p>
            </div>

            <!-- Quantity Input -->
            <div class="pt-6 lg:pt-0">
                <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                <input 
                    type="number" 
                    id="quantity" 
                    name="quantity" 
                    placeholder="1000"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required
                >
                <p id="quantityInfo" class="text-xs text-gray-500 mt-1"></p>
                <p id="costEstimate" class="text-sm font-semibold text-brand-green mt-1"></p>
            </div>

            <!-- Additional Fields (dynamic based on service type) -->
            <div id="additionalFields" class="space-y-6"></div>

            <!-- Submit Button -->
            <div class="pt-32 lg:pt-20 justify-center flex items-center">
                <button 
                    type="submit"
                    id="submitBtn"
                    class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-brand-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green transition duration-200 shadow-lg shadow-purple-600/20"
                >
                    Order Now
                </button>
            </div>
        </form>
        @endif
    </div>
</div>

<!-- Platform Modal -->
<div id="platformModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2" id="platformModalContent">
        <div class="px-6 py-4 border-b border-gray-200 bg-brand-green">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Platform</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto pb-16">
            <ul id="platformList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- Service Modal -->
<div id="serviceModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="serviceModalContent" class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-2/3 pb-16">
        <div class="px-6 py-4 pb-5 bg-brand-green">
            <h3 id="serviceModalTitle" class="text-lg font-semibold text-white text-center">Choose a Service</h3>   
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="serviceList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- PIN Confirmation Modal -->
<div id="socialPinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
    
    <button id="socialPinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      ✕
    </button>
    
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🚀 Confirm Social Boost</h2>
    <p id="socialPinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed"></p>
    
    <div class="mb-5">
      <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
      <div class="flex space-x-3 justify-center">
        <input type="password" maxlength="1" class="social-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="social-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="social-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="social-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
      </div>
    </div>
    
    <button id="confirmSocialPinBtn" type="button" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-brand-green hover:shadow-lg transition duration-200">
      ✅ Confirm & Boost
    </button>
    
    <div id="socialPinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<script>
// Pass services data from controller
window.socialServices = @json($groupedServices);
window.socialWalletBalance = {{ $wallet->balance ?? 0 }};
const EXCHANGE_RATE = {{ $exchangeRate ?? 1500 }};

document.addEventListener('DOMContentLoaded', function () {
    const platformSelect = document.getElementById('platformSelect');
    const platformInput = document.getElementById('platformInput');
    const serviceSelect = document.getElementById('serviceSelect');
    const serviceInput = document.getElementById('serviceInput');
    const linkInput = document.getElementById('link');
    const quantityInput = document.getElementById('quantity');
    
    const platformModal = document.getElementById('platformModal');
    const serviceModal = document.getElementById('serviceModal');
    const platformModalContent = document.getElementById('platformModalContent');
    const serviceModalContent = document.getElementById('serviceModalContent');
    
    const platformList = document.getElementById('platformList');
    const serviceList = document.getElementById('serviceList');
    const serviceModalTitle = document.getElementById('serviceModalTitle');
    
    const quantityInfo = document.getElementById('quantityInfo');
    const costEstimate = document.getElementById('costEstimate');
    const additionalFields = document.getElementById('additionalFields');

    let selectedPlatform = '';
    let selectedService = null;
    let currentServices = [];

    // Modal functions
    function showModal(modal, content) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            content.classList.remove('translate-y-full');
        }, 10);
    }

    function hideModal(modal, content) {
        modal.classList.remove('opacity-100');
        content.classList.add('translate-y-full');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Populate platforms
    function populatePlatforms() {
        platformList.innerHTML = '';
        const platforms = Object.keys(window.socialServices);
        
        if (platforms.length === 0) {
            platformList.innerHTML = '<li class="text-center text-gray-500 py-4">No platforms available</li>';
            return;
        }
        
        platforms.forEach(platform => {
            const li = document.createElement('li');
            li.className = 'flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 platform-option';
            li.dataset.value = platform;
            
            li.innerHTML = `
                <svg class="w-6 h-6 text-brand-green mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 10a2 2 0 114 0 2 2 0 01-4 0z"/>
                </svg>
                <span class="text-gray-800 font-medium">${platform}</span>
            `;
            
            li.addEventListener('click', () => selectPlatform(platform, platform));
            platformList.appendChild(li);
        });
    }

    // Select platform
    function selectPlatform(platform, displayName) {
        selectedPlatform = platform;
        platformInput.value = platform;
        platformSelect.textContent = displayName;
        platformSelect.classList.remove('text-gray-500');
        platformSelect.classList.add('text-gray-800');
        
        // Enable service select
        serviceSelect.classList.remove('cursor-not-allowed', 'opacity-60');
        serviceSelect.classList.add('cursor-pointer');
        
        // Reset service
        serviceSelect.textContent = 'Choose a Service';
        serviceSelect.classList.remove('text-gray-800');
        serviceSelect.classList.add('text-gray-500');
        serviceInput.value = '';
        selectedService = null;
        additionalFields.innerHTML = '';
        quantityInfo.textContent = '';
        costEstimate.textContent = '';
        
        hideModal(platformModal, platformModalContent);
    }

    // Populate services for selected platform
    function populateServices() {
        if (!selectedPlatform) {
            showSocialCustomModal('Error', 'Please select a platform first.', 'text-red-600');
            return;
        }

        currentServices = window.socialServices[selectedPlatform] || [];
        serviceList.innerHTML = '';
        serviceModalTitle.textContent = `${selectedPlatform} Services`;

        if (currentServices.length === 0) {
            serviceList.innerHTML = '<li class="text-center text-gray-500 py-4">No services available for this platform</li>';
            showModal(serviceModal, serviceModalContent);
            return;
        }

        currentServices.forEach(service => {
            const li = document.createElement('li');
            li.className = 'p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 service-option';
            li.dataset.serviceId = service.service_id;
            
            li.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-800 font-medium">${service.name}</p>
                        <p class="text-xs text-gray-500 mt-1">${service.type}</p>
                        <p class="text-xs text-gray-500">Min: ${service.min.toLocaleString()} | Max: ${service.max.toLocaleString()}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-brand-green font-semibold">₦${service.rate_local.toFixed(2)}/1k</p>
                    </div>
                </div>
            `;
            
            li.addEventListener('click', () => selectService(service));
            serviceList.appendChild(li);
        });

        showModal(serviceModal, serviceModalContent);
    }

    // Select service
    function selectService(service) {
        selectedService = service;
        serviceInput.value = service.service_id;
        serviceSelect.textContent = service.name;
        serviceSelect.classList.remove('text-gray-500');
        serviceSelect.classList.add('text-gray-800');
        
        // Update quantity info
        quantityInfo.textContent = `Min: ${service.min.toLocaleString()} | Max: ${service.max.toLocaleString()}`;
        quantityInput.setAttribute('min', service.min);
        quantityInput.setAttribute('max', service.max);
        quantityInput.value = service.min;
        
        // Show additional fields based on service type
        renderAdditionalFields(service.type);
        
        // Calculate cost immediately
        calculateCost();
        
        hideModal(serviceModal, serviceModalContent);
    }

    // Render additional fields based on service type
    function renderAdditionalFields(type) {
        additionalFields.innerHTML = '';
        
        if (type === 'Custom Comments') {
            additionalFields.innerHTML = `
                <div class="pt-6 lg:pt-0">
                    <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Comments (one per line)</label>
                    <textarea 
                        id="comments" 
                        name="comments" 
                        rows="4"
                        placeholder="Great post!&#10;Amazing content!&#10;Love this!"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                        required
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">Enter one comment per line</p>
                </div>
            `;
        } else if (type.includes('Mentions') || type.includes('Mention')) {
            additionalFields.innerHTML = `
                <div class="pt-6 lg:pt-0">
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="username"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Enter username without @</p>
                </div>
            `;
        }
    }

    // Calculate cost
    function calculateCost() {
        const quantity = parseInt(quantityInput.value);
        if (!selectedService || !quantity || isNaN(quantity)) {
            costEstimate.textContent = '';
            return;
        }
        
        // Validate quantity
        if (quantity < selectedService.min || quantity > selectedService.max) {
            costEstimate.innerHTML = `<span class="text-red-600">⚠️ Quantity must be between ${selectedService.min.toLocaleString()} and ${selectedService.max.toLocaleString()}</span>`;
            return;
        }
        
        // Calculate: (quantity / 1000) * rate_local
        const cost = (quantity / 1000) * selectedService.rate_local;
        costEstimate.textContent = `Estimated Cost: ₦${cost.toFixed(2)}`;
        
        // Warn if insufficient balance
        if (cost > window.socialWalletBalance) {
            costEstimate.innerHTML += `<br><span class="text-red-600 text-xs">⚠️ Insufficient balance (₦${window.socialWalletBalance.toFixed(2)})</span>`;
        }
    }

    // Event listeners
    platformSelect.addEventListener('click', function() {
        populatePlatforms();
        showModal(platformModal, platformModalContent);
    });

    serviceSelect.addEventListener('click', function() {
        if (!selectedPlatform) {
            showSocialCustomModal('Error', 'Please select a platform first.', 'text-red-600');
            return;
        }
        populateServices();
    });

    quantityInput.addEventListener('input', calculateCost);

    // Close modals on background click
    [platformModal, serviceModal].forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                const content = modal === platformModal ? platformModalContent : serviceModalContent;
                hideModal(modal, content);
            }
        });
    });

    // Form submission
    const socialForm = document.getElementById('socialBoostForm');
    if (socialForm) {
        socialForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation
            if (!selectedService) {
                return showSocialCustomModal('Error', 'Please select a service.', 'text-red-600');
            }
            
            const link = linkInput.value.trim();
            const quantity = parseInt(quantityInput.value);
            
            if (!link) {
                linkInput.focus();
                return showSocialCustomModal('Error', 'Please enter a valid URL.', 'text-red-600');
            }
            
            if (!quantity || quantity < selectedService.min || quantity > selectedService.max) {
                quantityInput.focus();
                return showSocialCustomModal('Error', `Quantity must be between ${selectedService.min.toLocaleString()} and ${selectedService.max.toLocaleString()}.`, 'text-red-600');
            }
            
            // Check for additional required fields
            if (selectedService.type === 'Custom Comments') {
                const comments = document.getElementById('comments')?.value.trim();
                if (!comments) {
                    document.getElementById('comments')?.focus();
                    return showSocialCustomModal('Error', 'Please enter at least one comment.', 'text-red-600');
                }
            }
            
            if (selectedService.type.includes('Mentions')) {
                const username = document.getElementById('username')?.value.trim();
                if (!username) {
                    document.getElementById('username')?.focus();
                    return showSocialCustomModal('Error', 'Please enter a username.', 'text-red-600');
                }
            }
            
            const cost = (quantity / 1000) * selectedService.rate_local;
            
            if (window.socialWalletBalance < cost) {
                return showSocialCustomModal(
                    'Insufficient Balance',
                    `Your account balance (₦${window.socialWalletBalance.toFixed(2)}) is not enough. Required: ₦${cost.toFixed(2)}. Please fund your wallet.`,
                    'text-red-600'
                );
            }
            
            // Show PIN modal
            document.getElementById('socialPinModalSummary').innerHTML = `
                <div class="text-left space-y-1">
                    <div class="flex justify-between"><span class="font-medium">Platform:</span> <span>${selectedPlatform}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Service:</span> <span class="text-right ml-2">${selectedService.name}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Link:</span> <span class="text-xs text-right ml-2 truncate max-w-[180px]">${link}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Quantity:</span> <span>${quantity.toLocaleString()}</span></div>
                    <div class="flex justify-between border-t pt-2 mt-2"><span class="font-bold">Amount:</span> <span class="font-bold text-brand-green">₦${cost.toFixed(2)}</span></div>
                    <div class="flex justify-between text-green-700"><span class="font-medium">Your Balance:</span> <span>₦${window.socialWalletBalance.toFixed(2)}</span></div>
                    <div class="flex justify-between text-blue-700"><span class="font-medium">New Balance:</span> <span>₦${(window.socialWalletBalance - cost).toFixed(2)}</span></div>
                </div>
            `;
            
            document.querySelectorAll('.social-pin-input').forEach(input => input.value = '');
            document.getElementById('socialPinModalError').classList.add('hidden');
            document.getElementById('socialPinModal').classList.remove('hidden');
            document.querySelector('.social-pin-input').focus();
        });
    }
});

// PIN modal functionality
document.getElementById('socialPinModalCloseBtn').addEventListener('click', () => {
    document.getElementById('socialPinModal').classList.add('hidden');
});

// Auto-focus next PIN input
document.querySelectorAll('.social-pin-input').forEach((input, idx, arr) => {
    input.addEventListener('input', function() {
        if (this.value.length === 1 && idx < arr.length - 1) {
            arr[idx + 1].focus();
        }
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && idx > 0) {
            arr[idx - 1].focus();
        }
    });
});

// Handle PIN confirmation
document.getElementById('confirmSocialPinBtn').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const pinInputs = document.querySelectorAll('.social-pin-input');
    let pin = '';
    pinInputs.forEach(input => pin += input.value);

    if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
        document.getElementById('socialPinModalError').textContent = 'Please enter your 4-digit PIN.';
        document.getElementById('socialPinModalError').classList.remove('hidden');
        return;
    }

    const confirmBtn = document.getElementById('confirmSocialPinBtn');
    const originalBtnText = confirmBtn.innerHTML;
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;

    const formData = new FormData(document.getElementById('socialBoostForm'));
    formData.append('pin', pin);

    try {
        const response = await fetch("{{ route('social-boost.purchase') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });

        const result = await response.json();

        if (response.ok && result.success) {
            document.getElementById('socialPinModal').classList.add('hidden');
            
            // Update wallet balance
            if (result.data?.formatted_balance) {
                const newBalance = parseFloat(result.data.formatted_balance.replace(/[₦,]/g, ''));
                window.socialWalletBalance = newBalance;
            }
            
            await showSocialSuccessPartial({
                title: 'Boost Order Successful!',
                message: result.message,
                details: {
                    'Transaction ID': result.data?.transaction_reference || 'N/A',
                    'Order ID': result.data?.mtp_order_id || 'N/A',
                    'Service': result.data?.service_name || 'N/A',
                    'Quantity': result.data?.quantity || 'N/A',
                    'Link': result.data?.link || 'N/A',
                    'Amount': result.data?.formatted_amount || 'N/A',
                    'New Balance': result.data?.formatted_balance || 'N/A',
                    'Date & Time': result.data?.transaction_time ? 
                        new Date(result.data.transaction_time).toLocaleString() : 
                        new Date().toLocaleString()
                }
            });
            
            // Reset form
            document.getElementById('socialBoostForm').reset();
            document.getElementById('platformInput').value = '';
            document.getElementById('serviceInput').value = '';
            document.getElementById('platformSelect').textContent = 'Choose a Platform';
            document.getElementById('platformSelect').classList.remove('text-gray-800');
            document.getElementById('platformSelect').classList.add('text-gray-500');
            document.getElementById('serviceSelect').textContent = 'Choose a Service';
            document.getElementById('serviceSelect').classList.remove('text-gray-800', 'cursor-pointer');
            document.getElementById('serviceSelect').classList.add('text-gray-500', 'cursor-not-allowed', 'opacity-60');
            document.getElementById('additionalFields').innerHTML = '';
            document.getElementById('quantityInfo').textContent = '';
            document.getElementById('costEstimate').textContent = '';
            
        } else {
            let errorMessage = result.message || 'Order failed. Please try again.';
            
            // Handle validation errors
            if (result.errors) {
                errorMessage = Object.values(result.errors).flat().join(', ');
            }
            
            document.getElementById('socialPinModalError').textContent = errorMessage;
            document.getElementById('socialPinModalError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Social boost error:', error);
        document.getElementById('socialPinModalError').textContent = 'Network error. Please check your connection and try again.';
        document.getElementById('socialPinModalError').classList.remove('hidden');
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    }
});

// Success partial function
async function showSocialSuccessPartial(data) {
    try {
        const response = await fetch('/render-success-partial', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            body: JSON.stringify({
                title: data.title,
                message: data.message,
                details: data.details || {}
            })
        });

        if (response.ok) {
            const partialHtml = await response.text();
            
            const modalWrapper = document.createElement('div');
            modalWrapper.id = 'socialSuccessPartialModal';
            modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
            
            modalWrapper.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative transform animate-bounce-in">
                    <button id="closeSocialPartialModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold z-10">
                        ✕
                    </button>
                    <div class="p-6">
                        ${partialHtml}
                        <div class="mt-6">
                            <button id="socialDoneBtn" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl hover:bg-brand-green transition duration-200">
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modalWrapper);

            const closeModal = () => {
                modalWrapper.classList.add('animate-fade-out');
                setTimeout(() => modalWrapper.remove(), 300);
            };
            
            document.getElementById('closeSocialPartialModal').addEventListener('click', closeModal);
            document.getElementById('socialDoneBtn').addEventListener('click', closeModal);
            
            modalWrapper.addEventListener('click', (e) => {
                if (e.target === modalWrapper) closeModal();
            });

            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);

        } else {
            showSimpleSocialSuccessModal(data);
        }
    } catch (error) {
        console.error('Error loading success partial:', error);
        showSimpleSocialSuccessModal(data);
    }
}

function showSimpleSocialSuccessModal(data) {
    const modalWrapper = document.createElement('div');
    modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    
    modalWrapper.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative">
            <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold" onclick="this.closest('.fixed').remove()">
                ✕
            </button>
            <div class="text-center">
                <div class="text-brand-green text-6xl mb-4">🚀</div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">${data.title}</h2>
                <p class="text-gray-600 mb-4">${data.message}</p>
                ${data.details ? `
                <div class="text-left bg-gray-50 p-3 rounded-lg text-sm space-y-1">
                    ${Object.entries(data.details).map(([key, value]) => 
                        `<div class="flex justify-between py-1 border-b border-gray-200 last:border-0">
                            <span class="font-medium text-gray-700">${key}:</span>
                            <span class="text-right ml-2 text-gray-900">${value}</span>
                        </div>`
                    ).join('')}
                </div>
                ` : ''}
                <button class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl hover:bg-brand-green transition duration-200 mt-4" onclick="this.closest('.fixed').remove()">
                    Done
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalWrapper);
    
    modalWrapper.addEventListener('click', (e) => {
        if (e.target === modalWrapper) modalWrapper.remove();
    });
}

function showSocialCustomModal(title, message, titleClass = '') {
    const modalWrapper = document.createElement('div');
    modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    
    modalWrapper.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 relative">
            <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700" onclick="this.closest('.fixed').remove()">✕</button>
            <h2 class="text-lg font-bold mb-2 ${titleClass}">${title}</h2>
            <p class="text-gray-700">${message}</p>
            <button class="w-full bg-brand-green text-white py-2 px-4 rounded-lg mt-4 hover:bg-brand-green transition" onclick="this.closest('.fixed').remove()">
                OK
            </button>
        </div>
    `;
    
    document.body.appendChild(modalWrapper);
}
</script>

<style>
@keyframes bounce-in {
    0% { transform: scale(0.9); opacity: 0; }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}
.animate-bounce-in { animation: bounce-in 0.3s ease-out; }
.animate-fade-out { animation: fade-out 0.3s ease-out; }
</style>
@endsection