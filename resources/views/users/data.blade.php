@extends('layouts.app')

@section('title', 'Data Purchase')

@section('content')
{{-- {{dd($plans)}} --}}
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Data Purchase</h1>
        </div>

        <!-- Airtime Purchase Form -->
        <form action="{{ route('data.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Network Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <input type="hidden" name="network" id="networkInput">
                    <select 
                        id="network" 
                        class="custom-select w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    >
                        <option value="" disabled selected>Choose A Network</option>
                        <option value="mtn">MTN</option>
                        <option value="glo">Glo</option>
                        <option value="airtel">Airtel</option>
                        <option value="9mobile">9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Package Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="package" class="block text-sm font-semibold text-gray-700 mb-2">Package</label>
                <div 
                    id="packageSelect" 
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-pointer focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200"
                >
                    Choose a Package
                </div>
                <!-- Hidden input to hold the selected PRODUCT_CODE -->
                <input type="hidden" name="data_plan" id="packageInput">
                <input type="hidden" name="amount" id="amountInput">
            </div>

            <!-- Recipient Input -->
            <div class="pt-6 lg:pt-0">
                <label for="recipient" class="block text-sm font-semibold text-gray-700 mb-2">Recipient</label>
                <input 
                    type="tel" 
                    id="recipient" 
                    name="recipient" 
                    placeholder="Phone Number"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                >
            </div>

            <!-- Submit Button -->
             <div class="pt-32 lg:pt-20 justify-center flex items-center">
                <button 
                    type="submit"
                    class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20"
                >
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<div id="networkModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2" id="modalContent">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto pb-16">
            <ul class="space-y-2">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200-5 pt hover:bg-gray-100 transition duration-150 network-option" data-value="airtel">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">Airtel NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="mtn">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">MTN NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="9mobile">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">9Mobile NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="glo">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">Glo NG Data</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Package Modal -->
<div id="packageModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="packageModalContent" class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-2/3 pb-16">
        <div class="px-6 py-4 pb-5">
            <h3 id="packageModalTitle" class="text-lg font-semibold text-white text-center">Choose a Package</h3>   
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const networkSelect = document.getElementById('network');
    const networkInput = document.getElementById('networkInput');
    const packageTrigger = document.getElementById('packageSelect');
    const packageInput = document.getElementById('packageInput');
    const amountInput = document.getElementById('amountInput'); // New: amount input

    const networkModal = document.getElementById('networkModal');
    const packageModal = document.getElementById('packageModal');
    const modalContent = document.getElementById('modalContent');
    const packageModalContent = document.getElementById('packageModalContent');
    const packageModalTitle = document.getElementById('packageModalTitle');

    const networkOptions = document.querySelectorAll('.network-option');
    const packageList = document.getElementById('packageList');

    const dataPlans = window.dataPlans || {};
    let selectedNetwork = '';

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

    function normalizeNetworkKey(key) {
        return Object.keys(dataPlans).find(k => k.toLowerCase().includes(key.toLowerCase()));
    }

    function populatePackages(networkKey) {
        const normalized = normalizeNetworkKey(networkKey);
        if (!normalized) return [];
        const raw = dataPlans[normalized];
        return raw?.[0]?.PRODUCT || [];
    }

    // Prevent native <select> dropdown and open modal instead
    networkSelect.addEventListener('mousedown', function (e) {
        e.preventDefault(); // Prevent default dropdown
        showModal(networkModal, modalContent);
    });

    // Handle network option click
    networkOptions.forEach(option => {
        option.addEventListener('click', function () {
            const selectedValue = this.dataset.value;
            const selectedText = this.querySelector('span')?.textContent || selectedValue;

            selectedNetwork = selectedValue;
            networkInput.value = selectedValue;
            packageTrigger.dataset.networkName = selectedText;

            // Update visible <select> for UI consistency
            networkSelect.querySelectorAll('option').forEach(opt => {
                opt.selected = (opt.value === selectedValue);
            });
            networkSelect.classList.remove('text-gray-500');
            networkSelect.classList.add('text-gray-800');

            // Reset package UI
            packageTrigger.textContent = 'Choose a Package';
            packageInput.value = '';
            amountInput.value = ''; // Reset amount too

            hideModal(networkModal, modalContent);
        });
    });

    // Show package modal
    packageTrigger.addEventListener('click', function () {
        if (!selectedNetwork) {
            alert('Please select a network first.');
            return;
        }

        const plans = populatePackages(selectedNetwork);
        const networkName = packageTrigger.dataset.networkName || 'Selected';

        packageModalTitle.textContent = `${networkName} Packages`;
        packageList.innerHTML = '';

        plans.forEach(plan => {
            const li = document.createElement('li');
            li.className = 'flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 package-option';
            li.dataset.value = plan.PRODUCT_CODE;
            li.dataset.amount = plan.PRODUCT_AMOUNT; // Store amount as data attribute

            li.innerHTML = `
                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-800 font-medium">${plan.PRODUCT_NAME} - ₦${parseFloat(plan.PRODUCT_AMOUNT).toFixed(0)}</span>
            `;
            packageList.appendChild(li);
        });

        showModal(packageModal, packageModalContent);
    });

    // Handle package selection
    packageList.addEventListener('click', function (e) {
        const target = e.target.closest('.package-option');
        if (target) {
            const selectedValue = target.dataset.value;
            const selectedAmount = target.dataset.amount;
            const selectedText = target.textContent.trim();

            packageInput.value = selectedValue;
            amountInput.value = selectedAmount; // Set the amount input value
            packageTrigger.textContent = selectedText;
            packageTrigger.classList.remove('text-gray-500');
            packageTrigger.classList.add('text-gray-800');

            hideModal(packageModal, packageModalContent);
        }
    });

    // Close modals on background click
    [networkModal, packageModal].forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                const content = modal === networkModal ? modalContent : packageModalContent;
                hideModal(modal, content);
            }
        });
    });
});
</script>

<script>
    window.dataPlans = @json($plans);
</script>
<!-- PIN Confirmation Modal -->
<div id="dataPinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
    
    <!-- Close button -->
    <button id="dataPinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      ✕
    </button>
    
    <!-- Header -->
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Data Purchase</h2>
    <p id="dataPinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed"></p>
    
    <!-- PIN Input -->
    <div class="mb-5">
      <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
      <div class="flex space-x-3 justify-center">
        <input type="password" maxlength="1" class="data-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="data-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="data-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="data-pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
      </div>
    </div>
    
    <!-- Confirm Button -->
    <button id="confirmDataPinBtn" type="button" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200">
      ✅ Confirm & Purchase Data
    </button>
    
    <!-- Error Message -->
    <div id="dataPinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<!-- Loading overlay -->
<div id="dataLoadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
  <div class="data-loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<!-- Custom modal for success/error messages -->
<div id="dataCustomModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
    <button id="dataModalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
    <h2 id="dataModalTitle" class="text-lg font-bold mb-2"></h2>
    <p id="dataModalMessage" class="whitespace-pre-wrap text-gray-700"></p>
  </div>
</div>

<style>
  /* Spinner styles */
  .data-loader {
    border-top-color: #3490dc;
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>

<script>
// Add wallet balance (you'll need to pass this from your controller)
const dataWalletBalance = {{ $wallet->balance ?? 0 }};

// Add PIN functionality after existing DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Your existing data purchase code remains unchanged...
    
    // Override form submission to show PIN modal
    const dataForm = document.querySelector('form[action="{{ route('data.purchase') }}"]');
    if (dataForm) {
        dataForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            // Get form values
            const network = document.getElementById('networkInput').value;
            const networkDisplay = document.querySelector('#network option:checked')?.textContent || network;
            const dataPlan = document.getElementById('packageInput').value;
            const packageText = document.getElementById('packageSelect').textContent;
            const recipient = document.getElementById('recipient').value;
            const amount = parseFloat(document.getElementById('amountInput').value) || 0;
            
            // Validation
            if (!network) return showDataCustomModal('Error', 'Please select a network.', 'text-red-600');
            if (!dataPlan) return showDataCustomModal('Error', 'Please select a data package.', 'text-red-600');
            if (!recipient) return showDataCustomModal('Error', 'Please enter recipient phone number.', 'text-red-600');
            if (!amount || amount <= 0) return showDataCustomModal('Error', 'Invalid package selected.', 'text-red-600');
            
            // Phone number validation
            const phoneRegex = /^(\+234|234|0)?[789][01]\d{8}$/;
            if (!phoneRegex.test(recipient)) {
                return showDataCustomModal('Error', 'Please enter a valid Nigerian phone number.', 'text-red-600');
            }
            
            // Check wallet balance
            if (dataWalletBalance < amount) {
                showDataCustomModal(
                    'Insufficient Balance',
                    `Your account balance (₦${dataWalletBalance.toFixed(2)}) is not enough to purchase ₦${amount.toFixed(2)} data. Please fund your wallet.`,
                    'text-red-600'
                );
                return;
            }
            
            
            // Show PIN modal with summary
            document.getElementById('dataPinModalSummary').innerHTML = `
              <div><strong>Network:</strong> ${networkDisplay}</div>
              <div><strong>Package:</strong> ${packageText}</div>
              <div><strong>Recipient:</strong> ${recipient}</div>
              <div><strong>Amount:</strong> ₦${amount.toFixed(2)}</div>
              <div class="mt-2 font-semibold text-green-700"><strong>Your Balance:</strong> ₦${dataWalletBalance.toFixed(2)}</div>
            `;
            
            // Clear PIN inputs and errors
            document.querySelectorAll('.data-pin-input').forEach(input => input.value = '');
            document.getElementById('dataPinModalError').classList.add('hidden');
            
            // Show PIN modal
            document.getElementById('dataPinModal').classList.remove('hidden');
            document.querySelector('.data-pin-input').focus();
        });
    }
});

// Data PIN modal functionality
document.getElementById('dataPinModalCloseBtn').addEventListener('click', () => {
    document.getElementById('dataPinModal').classList.add('hidden');
});

// Auto-focus next PIN input for data
document.querySelectorAll('.data-pin-input').forEach((input, idx, arr) => {
    input.addEventListener('input', function() {
        if (this.value.length === 1 && idx < arr.length - 1) {
            arr[idx + 1].focus();
        }
    });
    
    // Handle backspace
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && idx > 0) {
            arr[idx - 1].focus();
        }
    });
});
// Handle data PIN confirmation with success partial integration
document.getElementById('confirmDataPinBtn').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const pinInputs = document.querySelectorAll('.data-pin-input');
    let pin = '';
    pinInputs.forEach(input => pin += input.value);

    if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
        document.getElementById('dataPinModalError').textContent = 'Please enter your 4-digit PIN.';
        document.getElementById('dataPinModalError').classList.remove('hidden');
        return;
    }

    // Show loading animation on confirm button
    const confirmBtn = document.getElementById('confirmDataPinBtn');
    const originalBtnText = confirmBtn.innerHTML;
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;

    // Gather form data
    const formData = new FormData();
    formData.append('network', document.getElementById('networkInput').value);
    formData.append('data_plan', document.getElementById('packageInput').value);
    formData.append('recipient', document.getElementById('recipient').value);
    formData.append('pin', pin);

    try {
        const response = await fetch("{{ route('data.purchase') }}", {
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
            // Hide PIN modal
            document.getElementById('dataPinModal').classList.add('hidden');
            
            // Get selected package text for display
            const packageText = document.getElementById('packageSelect').textContent;
            const networkDisplay = document.querySelector('#network option:checked')?.textContent || 
                                 document.getElementById('networkInput').value.toUpperCase();
            
            // Call the success partial with enhanced data
            await showDataSuccessPartial({
                title: 'Data Purchase Successful!',
                message: result.message,
                details: {
                    'Transaction ID': result.data?.transaction_reference || 'N/A',
                    'Network': result.data?.network || networkDisplay,
                    'Package': result.data?.package_name || packageText,
                    'Phone Number': result.data?.recipient || document.getElementById('recipient').value,
                    'Amount': result.data?.formatted_amount || `₦${parseFloat(document.getElementById('amountInput').value || 0).toFixed(2)}`,
                    'New Balance': result.data?.formatted_balance || 'N/A',
                    'Date & Time': result.data?.transaction_time ? 
                        new Date(result.data.transaction_time).toLocaleString() : 
                        new Date().toLocaleString()
                }
            });
            
            // Reset form
            const dataForm = document.querySelector('form[action="{{ route('data.purchase') }}"]');
            if (dataForm) {
                dataForm.reset();
            }
            document.getElementById('networkInput').value = '';
            document.getElementById('packageInput').value = '';
            document.getElementById('amountInput').value = '';
            document.getElementById('packageSelect').textContent = 'Choose a Package';
            document.getElementById('network').selectedIndex = 0;
            
        } else {
            // Handle different types of errors
            let errorMessage = result.message || 'Data purchase failed.';
            
            // Show more specific error for PIN validation
            if (result.error_type === 'pin_validation') {
                errorMessage = result.message;
            } else if (result.error_type === 'validation') {
                errorMessage = 'Please check your input and try again.';
            }
            
            document.getElementById('dataPinModalError').textContent = errorMessage;
            document.getElementById('dataPinModalError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Data purchase error:', error);
        document.getElementById('dataPinModalError').textContent = 'Network error. Please check your connection and try again.';
        document.getElementById('dataPinModalError').classList.remove('hidden');
    } finally {
        // Reset button state
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnText;
    }
});

// Enhanced function to call Laravel success partial for data purchases
async function showDataSuccessPartial(data) {
    try {
        // Make AJAX request to get the rendered partial
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
            
            // Create modal wrapper for the partial
            const modalWrapper = document.createElement('div');
            modalWrapper.id = 'dataSuccessPartialModal';
            modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
            
            modalWrapper.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative transform animate-bounce-in">
                    <button id="closeDataPartialModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold z-10">
                        ✕
                    </button>
                    <div class="p-6">
                        ${partialHtml}
                        <div class="mt-6">
                            <button id="dataDoneBtn" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl hover:bg-teal-700 transition duration-200">
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modalWrapper);

            // Add close functionality
            const closeModal = () => {
                modalWrapper.classList.add('animate-fade-out');
                setTimeout(() => modalWrapper.remove(), 300);
            };
            
            document.getElementById('closeDataPartialModal').addEventListener('click', closeModal);
            document.getElementById('dataDoneBtn').addEventListener('click', closeModal);
            
            // Close when clicking outside
            modalWrapper.addEventListener('click', (e) => {
                if (e.target === modalWrapper) closeModal();
            });

            // Close with Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);

        } else {
            // Fallback to simple success message if partial loading fails
            showSimpleDataSuccessModal(data);
        }
    } catch (error) {
        console.error('Error loading data success partial:', error);
        // Fallback to simple success message
        showSimpleDataSuccessModal(data);
    }
}

// Fallback function for simple success display
function showSimpleDataSuccessModal(data) {
    let detailsText = '';
    if (data.details) {
        detailsText = '\n\nDetails:\n' + Object.entries(data.details)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');
    }
    
    // Create a simple modal instead of alert
    const modalWrapper = document.createElement('div');
    modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
    
    modalWrapper.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative">
            <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold" onclick="this.closest('.fixed').remove()">
                ✕
            </button>
            <div class="text-center">
                <div class="text-green-600 text-6xl mb-4">✅</div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">${data.title}</h2>
                <p class="text-gray-600 mb-4">${data.message}</p>
                ${data.details ? `
                <div class="text-left bg-gray-50 p-3 rounded-lg text-sm">
                    ${Object.entries(data.details).map(([key, value]) => 
                        `<div class="flex justify-between py-1"><span class="font-medium">${key}:</span><span>${value}</span></div>`
                    ).join('')}
                </div>
                ` : ''}
                <button class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl hover:bg-teal-700 transition duration-200 mt-4" onclick="this.closest('.fixed').remove()">
                    Done
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalWrapper);
    
    // Close when clicking outside
    modalWrapper.addEventListener('click', (e) => {
        if (e.target === modalWrapper) modalWrapper.remove();
    });
}
</script>
@endsection
