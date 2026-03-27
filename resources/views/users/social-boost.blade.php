@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6 sm:mb-8">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Social Media Boost</h1>
        </div>

        <!-- Tier Legend -->
        <div class="mb-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 text-center">Service Quality Tiers</h3>
            <div class="grid grid-cols-3 gap-3">
                <!-- Basic Tier -->
                <div class="bg-white rounded-xl p-3 shadow-sm hover:shadow-md transition cursor-pointer tier-legend-item" data-tier="Basic">
                    <div class="flex flex-col items-center">
                        🟡
                        <p class="text-xs font-semibold text-gray-800">Basic</p>
                        <p class="text-[10px] text-gray-500 text-center mt-1">Budget</p>
                    </div>
                </div>

                <!-- Medium Tier -->
                <div class="bg-white rounded-xl p-3 shadow-sm hover:shadow-md transition cursor-pointer tier-legend-item" data-tier="Medium">
                    <div class="flex flex-col items-center">
                        🟢
                        <p class="text-xs font-semibold text-gray-800">Medium</p>
                        <p class="text-[10px] text-gray-500 text-center mt-1">Quality</p>
                    </div>
                </div>

                <!-- Elite Tier -->
                <div class="bg-white rounded-xl p-3 shadow-sm hover:shadow-md transition cursor-pointer tier-legend-item" data-tier="Elite">
                    <div class="flex flex-col items-center">
                        🔵
                        <p class="text-xs font-semibold text-gray-800">Elite</p>
                        <p class="text-[10px] text-gray-500 text-center mt-1">Premium</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 text-center mt-3">
                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Tap any tier to learn more
            </p>
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

            <!-- Selected service description (shows when a service is chosen) -->
            <div id="selectedServiceDescription" class="hidden text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
            </div>

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
            {{-- <p id="serviceModalDescription" class="text-sm text-white/90 mt-2 text-center px-2"></p> --}}
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <!-- Search input for services -->
            <div class="mb-3">
                <input id="serviceSearch" type="search" placeholder="Search services (name, type, category)" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200">
            </div>
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

function getServiceDescription(service, platform = '') {
    return (service.description || '').trim();
}
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
    const serviceSearch = document.getElementById('serviceSearch');
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

    // Get tier information based on rate
    function getTierInfo(rate) {
        if (rate <= 50) {
            return {
                name: 'Basic',
                icon: 'B',
                bgColor: 'bg-yellow-400',
                textColor: 'text-white',
                fullBgColor: 'bg-yellow-50',
                accentColor: 'text-yellow-600',
                borderColor: 'border-yellow-400'
            };
        } else if (rate <= 150) {
            return {
                name: 'Medium',
                icon: 'M',
                bgColor: 'bg-green-500',
                textColor: 'text-white',
                fullBgColor: 'bg-green-50',
                accentColor: 'text-green-600',
                borderColor: 'border-green-500'
            };
        } else {
            return {
                name: 'Elite',
                icon: 'E',
                bgColor: 'bg-blue-500',
                textColor: 'text-white',
                fullBgColor: 'bg-blue-50',
                accentColor: 'text-blue-600',
                borderColor: 'border-blue-500'
            };
        }
    }

    // Show tier information modal
    function showTierInfoModal(tierInfo) {
        const tierDescriptions = {
            'Basic': {
                title: 'Basic Plan',
                emoji: '💛',
                description: 'Most affordable boost services',
                features: [
                    'Budget-friendly pricing',
                    'Fairly lower quality engagement',
                    'May experience occasional drops',
                    'Possible delivery slowdowns',
                    'No quality guarantee for this tier'
                ],
                note: 'Best for: Testing services or tight budgets'
            },
            'Medium': {
                title: 'Medium Plan',
                emoji: '💚',
                description: 'Where quality meets reliability',
                features: [
                    'Solid and realistic engagement',
                    'Consistent delivery speed',
                    'Reliable performance',
                    'Minimal to no drops',
                    'Great value for money'
                ],
                note: 'Best for: Regular boost campaigns and steady growth'
            },
            'Elite': {
                title: 'Elite Plan',
                emoji: '💙',
                description: 'Premium quality for maximum impact',
                features: [
                    'Highest quality engagement available',
                    'Premium organic-looking profiles',
                    'Guaranteed delivery and retention',
                    'Priority processing',
                    'Zero drops with full support'
                ],
                note: 'Best for: Professional campaigns and brand reputation'
            }
        };

        const info = tierDescriptions[tierInfo.name];
        
        const modalWrapper = document.createElement('div');
        modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-60 z-[60] p-4 tier-modal';
        
        modalWrapper.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative transform animate-bounce-in">
                <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition text-xl font-bold z-10 close-tier-modal">
                    ✕
                </button>
                
                <div class="p-6">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-20 h-20 rounded-full ${tierInfo.bgColor} flex items-center justify-center shadow-lg">
                            <span class="text-4xl">${info.emoji}</span>
                        </div>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-center ${tierInfo.accentColor} mb-2">
                        ${info.title}
                    </h2>
                    
                    <p class="text-center text-gray-700 font-medium mb-4">
                        ${info.description}
                    </p>
                    
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 ${tierInfo.accentColor}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            Features
                        </h3>
                        <ul class="space-y-2">
                            ${info.features.map(feature => `
                                <li class="flex items-start text-sm text-gray-700">
                                    <svg class="w-5 h-5 ${tierInfo.accentColor} mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>${feature}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                    
                    <div class="${tierInfo.fullBgColor} border ${tierInfo.borderColor} rounded-lg p-3 mb-4">
                        <p class="text-sm ${tierInfo.accentColor} font-medium">
                            💡 ${info.note}
                        </p>
                    </div>
                    
                    <button class="w-full ${tierInfo.bgColor} ${tierInfo.textColor} font-semibold py-3 px-4 rounded-xl hover:opacity-90 transition duration-200 shadow-md close-tier-modal">
                        Got it!
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modalWrapper);
        
        const closeModal = () => {
            modalWrapper.classList.add('animate-fade-out');
            setTimeout(() => modalWrapper.remove(), 300);
        };
        
        modalWrapper.querySelectorAll('.close-tier-modal').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });
        
        modalWrapper.addEventListener('click', (e) => {
            if (e.target === modalWrapper) closeModal();
        });
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
            // searchable fields
            li.dataset.search = `${service.name} ${service.type} ${service.category || ''}`.toLowerCase();
            li.dataset.description = getServiceDescription(service, selectedPlatform) || '';
            
            // Determine tier based on rate
            let tierInfo = getTierInfo(service.rate_local);
            
            // Create the list item with tier icon
            const tierIconDiv = document.createElement('div');
            tierIconDiv.className = 'flex-shrink-0 mt-1';
            tierIconDiv.innerHTML = `
                <div class="w-10 h-10 rounded-full ${tierInfo.bgColor} flex items-center justify-center cursor-help tier-icon shadow-md" 
                     data-tier="${tierInfo.name}"
                     title="Click for ${tierInfo.name} tier details">
                    <span class="text-base font-bold ${tierInfo.textColor}">${tierInfo.icon}</span>
                </div>
            `;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'flex-1 min-w-0';
            contentDiv.innerHTML = `
                <p class="text-gray-800 font-medium truncate">${service.name}</p>
                <p class="text-xs text-gray-500 mt-1">${service.type}</p>
                <p class="text-xs text-gray-500">Min: ${service.min.toLocaleString()} | Max: ${service.max.toLocaleString()}</p>
            `;
            
            const priceDiv = document.createElement('div');
            priceDiv.className = 'text-right flex-shrink-0';
            priceDiv.innerHTML = `
                <p class="text-brand-green font-semibold whitespace-nowrap">₦${service.rate_local.toFixed(2)}/1k</p>
            `;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'flex justify-between items-start gap-3';
            wrapper.appendChild(tierIconDiv);
            wrapper.appendChild(contentDiv);
            wrapper.appendChild(priceDiv);
            
            li.appendChild(wrapper);
            
            // Add click listener for tier icon
            const tierIcon = tierIconDiv.querySelector('.tier-icon');
            tierIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                showTierInfoModal(tierInfo);
            });
            
            // Add click listener for service selection (but not on tier icon)
            li.addEventListener('click', (e) => {
                if (!e.target.closest('.tier-icon')) {
                    selectService(service);
                }
            });
            
            // Instead of placing descriptions inside each list item, show a preview
            // in the modal header when the user hovers a service.
            li.addEventListener('mouseenter', () => {
                const desc = getServiceDescription(service, selectedPlatform);
                const modalDescEl = document.getElementById('serviceModalDescription');
                if (modalDescEl) modalDescEl.textContent = desc || '';
            });
            li.addEventListener('mouseleave', () => {
                const modalDescEl = document.getElementById('serviceModalDescription');
                if (modalDescEl) modalDescEl.textContent = '';
            });

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
        
        // Show additional fields based on full service object (type, name, description)
        renderAdditionalFields(service);
        
        // Calculate cost immediately
        calculateCost();
        
        hideModal(serviceModal, serviceModalContent);
        // Show the selected service description prominently above the form
        const selectedDescEl = document.getElementById('selectedServiceDescription');
        const desc = getServiceDescription(service, selectedPlatform) || '';
        if (desc && selectedDescEl) {
            selectedDescEl.innerHTML = `<strong class="block mb-1">Service Description:</strong><div class="text-sm text-gray-700">${desc.replace(/\n/g, '<br>')}</div>`;
            selectedDescEl.classList.remove('hidden');
        } else if (selectedDescEl) {
            selectedDescEl.classList.add('hidden');
            selectedDescEl.innerHTML = '';
        }
    }

    // Render additional fields based on service object (type, name, description)
    function renderAdditionalFields(service) {
        additionalFields.innerHTML = '';

        const type = (service.type || '').toLowerCase();
        const name = (service.name || '').toLowerCase();
        const desc = getServiceDescription(service, selectedPlatform) || '';

        // Custom comments
        if (type.includes('custom comments') || name.includes('comments')) {
            additionalFields.innerHTML = `
                <div class="pt-6 lg:pt-0">
                    <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Comments (one per line)</label>
                    <textarea id="comments" name="comments" rows="4" placeholder="Great post!&#10;Amazing content!&#10;Love this!" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Enter one comment per line</p>
                </div>
            `;
        }

        // Mentions and username lists
        if (type.includes('mentions') || name.includes('mentions') || type.includes('username') || name.includes('username')) {
            additionalFields.innerHTML += `
                <div class="pt-6 lg:pt-0">
                    <label for="usernames" class="block text-sm font-semibold text-gray-700 mb-2">Usernames (one per line or comma separated)</label>
                    <textarea id="usernames" name="usernames" rows="3" placeholder="user1&#10;user2&#10;user3" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"></textarea>
                    <p class="text-xs text-gray-500 mt-1">For single username-based services use the "username" field (below)</p>
                </div>
                <div class="pt-4">
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Single Username (optional)</label>
                    <input type="text" id="username" name="username" placeholder="username" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200">
                    <p class="text-xs text-gray-500 mt-1">Enter username if the service requires a single username</p>
                </div>
            `;
        }

        // Groups input (used by Snapchat DM, PayPal package delivery info, Invites etc.)
        if (type.includes('groups') || name.includes('snapchat') || name.includes('paypal') || name.includes('invites') || desc.toLowerCase().includes('groups')) {
            additionalFields.innerHTML += `
                <div class="pt-6 lg:pt-0">
                    <label for="groups" class="block text-sm font-semibold text-gray-700 mb-2">Groups / Additional Info</label>
                    <textarea id="groups" name="groups" rows="5" placeholder="Provide target country, message, or account details (one per line). For PayPal provide: Full Name\nAddress\nAge\nTimezone" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use this field per service instructions. See service description for guidance.</p>
                </div>
            `;
        }

        // Media (URLs to images/videos)
        if (type.includes('media') || name.includes('media') || desc.toLowerCase().includes('media') || name.includes('snapchat') ) {
            additionalFields.innerHTML += `
                <div class="pt-4">
                    <label for="media" class="block text-sm font-semibold text-gray-700 mb-2">Media URL (optional)</label>
                    <input type="url" id="media" name="media" placeholder="https://example.com/media.mp4" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200">
                    <p class="text-xs text-gray-500 mt-1">Provide direct link to image/video if required by service.</p>
                </div>
            `;
        }

        // Hashtags / keywords
        if (type.includes('seo') || name.includes('seo') || desc.toLowerCase().includes('keywords') || desc.toLowerCase().includes('hashtags')) {
            additionalFields.innerHTML += `
                <div class="pt-4">
                    <label for="keywords" class="block text-sm font-semibold text-gray-700 mb-2">Keywords / Hashtags</label>
                    <input type="text" id="keywords" name="keywords" placeholder="test, example, #hashtag" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200">
                    <p class="text-xs text-gray-500 mt-1">Comma separated keywords or hashtags</p>
                </div>
            `;
        }

        // Drip-feed: runs & interval
        if (desc.toLowerCase().includes('drip') || name.includes('drip') || desc.toLowerCase().includes('runs')) {
            additionalFields.innerHTML += `
                <div class="grid grid-cols-2 gap-3 pt-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Runs</label>
                        <input type="number" id="runs" name="runs" min="1" placeholder="e.g. 10" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Interval (sec)</label>
                        <input type="number" id="interval" name="interval" min="1" placeholder="e.g. 60" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full">
                    </div>
                </div>
            `;
        }

        // Poll / answer_number
        if (name.includes('poll') || desc.toLowerCase().includes('poll')) {
            additionalFields.innerHTML += `
                <div class="pt-4">
                    <label for="answer_number" class="block text-sm font-semibold text-gray-700 mb-2">Answer Number</label>
                    <input type="text" id="answer_number" name="answer_number" placeholder="e.g. 7" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full">
                    <p class="text-xs text-gray-500 mt-1">Provide the answer number for poll services if required.</p>
                </div>
            `;
        }

        // Show service description/instructions (use fallback mapping if API missing it)
        // if (desc) {
        //     additionalFields.innerHTML += `
        //         <div class="pt-4 text-xs text-gray-600 bg-gray-50 p-3 rounded-lg">
        //             <strong>Service Info:</strong>
        //             <div class="mt-1">${desc}</div>
        //         </div>
        //     `;
        // }
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
        
        // Use client_price from database if available, otherwise fall back to API rate calculation
        let cost = 0;
        if (selectedService.client_price !== undefined && !isNaN(selectedService.client_price)) {
            // Use database-driven per-unit price from products table
            cost = quantity * parseFloat(selectedService.client_price);
        } else if (selectedService.rate_local_per_unit && !isNaN(selectedService.rate_local_per_unit)) {
            // Fallback: per-unit rate when provided by backend
            cost = quantity * parseFloat(selectedService.rate_local_per_unit);
        } else {
            // Last resort: (quantity / 1000) * rate_local (rate_local is per-1000 units)
            cost = (quantity / 1000) * selectedService.rate_local;
        }
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

    // Filter services in modal by search input
    if (serviceSearch) {
        serviceSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            serviceList.querySelectorAll('li.service-option').forEach(li => {
                if (!q) {
                    li.style.display = '';
                    return;
                }
                const hay = li.dataset.search || '';
                const desc = (li.dataset.description || '').toLowerCase();
                li.style.display = (hay.includes(q) || desc.includes(q)) ? '' : 'none';
            });
        });
    }

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

    // Add event listeners for tier legend items
    document.querySelectorAll('.tier-legend-item').forEach(item => {
        item.addEventListener('click', function() {
            const tierName = this.dataset.tier;
            const tierInfo = {
                'Basic': {
                    name: 'Basic',
                    icon: 'B',
                    bgColor: 'bg-yellow-400',
                    textColor: 'text-white',
                    fullBgColor: 'bg-yellow-50',
                    accentColor: 'text-yellow-600',
                    borderColor: 'border-yellow-400'
                },
                'Medium': {
                    name: 'Medium',
                    icon: 'M',
                    bgColor: 'bg-green-500',
                    textColor: 'text-white',
                    fullBgColor: 'bg-green-50',
                    accentColor: 'text-green-600',
                    borderColor: 'border-green-500'
                },
                'Elite': {
                    name: 'Elite',
                    icon: 'E',
                    bgColor: 'bg-blue-500',
                    textColor: 'text-white',
                    fullBgColor: 'bg-blue-50',
                    accentColor: 'text-blue-600',
                    borderColor: 'border-blue-500'
                }
            };
            
            showTierInfoModal(tierInfo[tierName]);
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
            
            // Check for additional required fields based on rendered inputs
            const commentsEl = document.getElementById('comments');
            if (commentsEl && commentsEl.value.trim() === '') {
                commentsEl.focus();
                return showSocialCustomModal('Error', 'Please enter at least one comment.', 'text-red-600');
            }

            const usernameEl = document.getElementById('username');
            const usernamesEl = document.getElementById('usernames');
            if ((usernameEl || usernamesEl)) {
                const usernameVal = usernameEl ? usernameEl.value.trim() : '';
                const usernamesVal = usernamesEl ? usernamesEl.value.trim() : '';
                if (!usernameVal && !usernamesVal) {
                    (usernameEl || usernamesEl).focus();
                    return showSocialCustomModal('Error', 'Please enter a username or usernames list.', 'text-red-600');
                }
            }

            const groupsEl = document.getElementById('groups');
            if (groupsEl && groupsEl.value.trim() === '') {
                groupsEl.focus();
                return showSocialCustomModal('Error', 'Please provide required additional information in the Groups field.', 'text-red-600');
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