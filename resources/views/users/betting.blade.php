@extends('layouts.app')

@section('title', 'Betting Purchase')

@section('content')
    <div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
        <div class="container mx-auto px-4 py-8 mt-10 max-w-md w-full">

            <!-- Header -->
            <div class="relative flex items-center justify-center mb-8 sm:mb-12">
                <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Betting</h1>
            </div>

            <!-- Betting Form -->
            <form action="{{ route('fund_bet') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Betting Company Selection -->
                <div class="pt-6 lg:pt-0">
                    <label for="betting_company" class="block text-sm font-semibold text-gray-700 mb-2">Betting
                        Company</label>
                    <div class="relative">
                        <input type="text" id="betting_company_display"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                            placeholder="Select Betting Company" readonly onclick="showModal()">
                        <input type="hidden" id="betting_company" name="betting_company" required>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Customer ID Input -->
                <div class="pt-6 lg:pt-0">
                    <label for="customer_id" class="block text-sm font-semibold text-gray-700 mb-2">Customer ID</label>
                    <input type="text" id="customer_id" name="customer_id" placeholder="Enter your betting account ID"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        required>
                </div>

                <!-- Amount Input -->
                <div class="pt-6 lg:pt-0">
                    <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" placeholder="0.00" min="100" max="50000"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        required>
                </div>

                <!-- Request ID (hidden) -->
                <input type="hidden" name="request_id" value="{{ Str::uuid() }}">

                <!-- Submit Button -->
                <div class="pt-32 lg:pt-20 justify-center flex items-center">
                    <button type="submit"
                        class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                        Fund Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Betting Company Selection Modal -->
    <div id="bettingModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-[70vh]">
        <div class="px-6 py-4 border-b border-gray-200 bg-brand-green">
            <h3 class="text-lg font-semibold text-white text-center">Choose Betting Company</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul class="grid grid-cols-1 gap-2">
                <!-- Main Betting Services -->
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Bet9ja" data-display="Bet9ja">
                    <div class="w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">B9</span>
                    </div>
                    <span class="text-gray-800 font-medium">Bet9ja</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BetKing" data-display="BetKing">
                    <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BK</span>
                    </div>
                    <span class="text-gray-800 font-medium">BetKing</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="1XBet" data-display="1XBet">
                    <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">1X</span>
                    </div>
                    <span class="text-gray-800 font-medium">1XBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="NairaBet" data-display="NairaBet">
                    <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NB</span>
                    </div>
                    <span class="text-gray-800 font-medium">NairaBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BetBiga" data-display="BetBiga">
                    <div class="w-8 h-8 rounded-full bg-orange-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BB</span>
                    </div>
                    <span class="text-gray-800 font-medium">BetBiga</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MerryBet" data-display="MerryBet">
                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">MB</span>
                    </div>
                    <span class="text-gray-800 font-medium">MerryBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="SportyBet" data-display="SportyBet">
                    <div class="w-8 h-8 rounded-full bg-green-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">SB</span>
                    </div>
                    <span class="text-gray-800 font-medium">SportyBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="NaijaBet" data-display="NaijaBet">
                    <div class="w-8 h-8 rounded-full bg-blue-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NJ</span>
                    </div>
                    <span class="text-gray-800 font-medium">NaijaBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Betway" data-display="Betway">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BW</span>
                    </div>
                    <span class="text-gray-800 font-medium">Betway</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BangBet" data-display="BangBet">
                    <div class="w-8 h-8 rounded-full bg-purple-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BG</span>
                    </div>
                    <span class="text-gray-800 font-medium">BangBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MelBet" data-display="MelBet">
                    <div class="w-8 h-8 rounded-full bg-yellow-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">MB</span>
                    </div>
                    <span class="text-gray-800 font-medium">MelBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="LiveScoreBet" data-display="LiveScoreBet">
                    <div class="w-8 h-8 rounded-full bg-green-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">LS</span>
                    </div>
                    <span class="text-gray-800 font-medium">LiveScoreBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Naira-Million" data-display="Naira Million">
                    <div class="w-8 h-8 rounded-full bg-purple-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NM</span>
                    </div>
                    <span class="text-gray-800 font-medium">Naira Million</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="CloudBet" data-display="CloudBet">
                    <div class="w-8 h-8 rounded-full bg-blue-400 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">CB</span>
                    </div>
                    <span class="text-gray-800 font-medium">CloudBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Paripesa" data-display="Paripesa">
                    <div class="w-8 h-8 rounded-full bg-red-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">PP</span>
                    </div>
                    <span class="text-gray-800 font-medium">Paripesa</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MylottoHub" data-display="MyLotto Hub">
                    <div class="w-8 h-8 rounded-full bg-pink-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">ML</span>
                    </div>
                    <span class="text-gray-800 font-medium">MyLotto Hub</span>
                </li>
            </ul>
        </div>
    </div>
</div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const amountInput = document.getElementById('amount');
            amountInput.addEventListener('change', function() {
                if (this.value < 100) this.value = 100;
                if (this.value > 50000) this.value = 50000;
            });


            const bettingModal = document.getElementById('bettingModal');
            const modalContent = document.querySelector('#bettingModal > div');
            const bettingOptions = document.querySelectorAll('.betting-option');
            const bettingCompanyDisplay = document.getElementById('betting_company_display');
            const bettingCompanyInput = document.getElementById('betting_company');

            function showModal() {
                bettingModal.classList.remove('hidden');
                setTimeout(() => {
                    bettingModal.classList.add('opacity-100');
                    modalContent.classList.remove('translate-y-full');
                }, 10);
            }

            function hideModal() {
                bettingModal.classList.remove('opacity-100');
                modalContent.classList.add('translate-y-full');
                setTimeout(() => bettingModal.classList.add('hidden'), 300);
            }

            bettingOptions.forEach(option => {
                option.addEventListener('click', function() {
                    bettingCompanyDisplay.value = this.dataset.display;
                    bettingCompanyInput.value = this.dataset.value;
                    hideModal();
                });
            });

            bettingModal.addEventListener('click', function(event) {
                if (event.target === bettingModal) hideModal();
            });
        });

        function showModal() {
            const bettingModal = document.getElementById('bettingModal');
            const modalContent = document.querySelector('#bettingModal > div');
            bettingModal.classList.remove('hidden');
            setTimeout(() => {
                bettingModal.classList.add('opacity-100');
                modalContent.classList.remove('translate-y-full');
            }, 10);
        }
    </script>
    <!-- PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
    
    <!-- Close button -->
    <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      ✕
    </button>
    
    <!-- Header -->
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Betting Purchase</h2>
    <p id="pinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed"></p>
    
    <!-- PIN Input -->
    <div class="mb-5">
      <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
      <div class="flex space-x-3 justify-center">
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
      </div>
    </div>
    
    <!-- Confirm Button -->
    <button id="confirmPinBtn" type="button" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200">
      ✅ Confirm & Fund Account
    </button>
    
    <!-- Error Message -->
    <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
  <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<!-- Custom modal for success/error messages -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
    <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
    <h2 id="modalTitle" class="text-lg font-bold mb-2"></h2>
    <p id="modalMessage" class="whitespace-pre-wrap text-gray-700"></p>
  </div>
</div>

<style>
  /* Spinner styles */
  .loader {
    border-top-color: #3490dc;
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>
<script>
// Add wallet balance (you'll need to pass this from your controller)
const walletBalance = {{ $wallet->balance ?? 0 }};

document.addEventListener('DOMContentLoaded', function() {
    // Amount validation
    const amountInput = document.getElementById('amount');
    amountInput.addEventListener('change', function() {
        if (this.value < 100) this.value = 100;
        if (this.value > 50000) this.value = 50000;
    });

    // Betting modal functionality
    const bettingModal = document.getElementById('bettingModal');
    const modalContent = document.querySelector('#bettingModal > div');
    const bettingOptions = document.querySelectorAll('.betting-option');
    const bettingCompanyDisplay = document.getElementById('betting_company_display');
    const bettingCompanyInput = document.getElementById('betting_company');

    // Betting modal show/hide functions
    window.showModal = function() {
        bettingModal.classList.remove('hidden');
        setTimeout(() => {
            bettingModal.classList.add('opacity-100');
            modalContent.classList.remove('translate-y-full');
        }, 10);
    };

    function hideModal() {
        bettingModal.classList.remove('opacity-100');
        modalContent.classList.add('translate-y-full');
        setTimeout(() => bettingModal.classList.add('hidden'), 300);
    }

    // Betting options selection
    bettingOptions.forEach(option => {
        option.addEventListener('click', function() {
            bettingCompanyDisplay.value = this.dataset.display;
            bettingCompanyInput.value = this.dataset.value;
            hideModal();
        });
    });

    // Close betting modal on outside click
    bettingModal.addEventListener('click', function(event) {
        if (event.target === bettingModal) hideModal();
    });

    // Function to show error message
    function showErrorMessage(message) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message-alert');
        existingMessages.forEach(msg => msg.remove());

        // Create new message element
        const messageEl = document.createElement('div');
        messageEl.className = 'message-alert mb-4 p-3 rounded bg-red-100 text-red-700';
        messageEl.textContent = message;

        // Insert message before the form
        const form = document.querySelector('form[action="{{ route('fund_bet') }}"]');
        form.parentNode.insertBefore(messageEl, form);

        // Auto-remove message after 5 seconds
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }

    // Override form submission to show PIN modal
    const form = document.querySelector('form[action="{{ route('fund_bet') }}"]');
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Validate form fields
        const bettingCompany = document.getElementById('betting_company').value;
        const bettingCompanyDisplay = document.getElementById('betting_company_display').value;
        const customerId = document.getElementById('customer_id').value;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        
        // Validation
        if (!bettingCompany) {
            showErrorMessage('Please select a betting company.');
            return;
        }
        if (!customerId) {
            showErrorMessage('Please enter your customer ID.');
            return;
        }
        if (!amount || amount < 100) {
            showErrorMessage('Please enter a valid amount (minimum ₦100).');
            return;
        }
        if (amount > 50000) {
            showErrorMessage('Maximum amount allowed is ₦50,000.');
            return;
        }
        
        // Check wallet balance
        if (walletBalance < amount) {
            showErrorMessage(`Your account balance (₦${walletBalance.toFixed(2)}) is not enough to fund ₦${amount.toFixed(2)}. Please fund your wallet.`);
            return;
        }
        
        // Show PIN modal with summary
        document.getElementById('pinModalSummary').innerHTML = `
          <div><strong>Betting Company:</strong> ${bettingCompanyDisplay}</div>
          <div><strong>Customer ID:</strong> ${customerId}</div>
          <div><strong>Amount:</strong> ₦${amount.toFixed(2)}</div>
          <div class="mt-2 font-semibold text-green-700"><strong>Your Balance:</strong> ₦${walletBalance.toFixed(2)}</div>
        `;
        
        // Clear PIN inputs and errors
        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        
        // Show PIN modal
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    });
});

// PIN modal functionality
document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
    document.getElementById('pinModal').classList.add('hidden');
});

// Auto-focus next PIN input
document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
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

// Handle PIN confirmation with loading animation and success partial
document.getElementById('confirmPinBtn').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const pinInputs = document.querySelectorAll('.pin-input');
    let pin = '';
    pinInputs.forEach(input => pin += input.value);

    if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
        document.getElementById('pinModalError').textContent = 'Please enter your 4-digit PIN.';
        document.getElementById('pinModalError').classList.remove('hidden');
        return;
    }

    // Show loading animation on confirm button
    const confirmBtn = document.getElementById('confirmPinBtn');
    const confirmBtnText = confirmBtn.querySelector('span:not(.hidden)') || confirmBtn.childNodes[0];
    
    // Create loading spinner if it doesn't exist
    let loadingSpinner = confirmBtn.querySelector('.loading-spinner');
    if (!loadingSpinner) {
        loadingSpinner = document.createElement('span');
        loadingSpinner.className = 'loading-spinner hidden';
        loadingSpinner.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        `;
        confirmBtn.appendChild(loadingSpinner);
    }
    
    // Show loading state
    confirmBtn.disabled = true;
    if (confirmBtnText && confirmBtnText.nodeType === Node.TEXT_NODE) {
        confirmBtn.innerHTML = '';
        confirmBtn.appendChild(loadingSpinner);
    } else if (confirmBtnText) {
        confirmBtnText.classList.add('hidden');
    }
    loadingSpinner.classList.remove('hidden');

    // Gather form data
    const formData = new FormData();
    formData.append('betting_company', document.getElementById('betting_company').value);
    formData.append('customer_id', document.getElementById('customer_id').value);
    formData.append('amount', document.getElementById('amount').value);
    formData.append('request_id', document.querySelector('input[name="request_id"]').value);
    formData.append('pin', pin);

    try {
        const response = await fetch("{{ route('fund_bet') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });

        const result = await response.json();

        if (response.ok && result.status) {
            // Hide PIN modal
            document.getElementById('pinModal').classList.add('hidden');
            
            // Show success partial with betting-specific data
            await showSuccessPartial({
                title: 'Betting Account Funded Successfully!',
                message: result.message || 'Your betting account has been funded successfully!',
                details: {
                    'Transaction Reference': result.data?.reference || document.querySelector('input[name="request_id"]').value,
                    'Betting Company': document.getElementById('betting_company_display').value,
                    'Customer ID': document.getElementById('customer_id').value,
                    'Amount': `₦${parseFloat(document.getElementById('amount').value).toFixed(2)}`,
                    'New Wallet Balance': result.data?.new_balance ? `₦${parseFloat(result.data.new_balance).toFixed(2)}` : 'N/A',
                    'Date & Time': new Date().toLocaleString()
                }
            });
            
            // Reset form
            document.querySelector('form[action="{{ route('fund_bet') }}"]').reset();
            document.getElementById('betting_company_display').value = '';
            document.getElementById('betting_company').value = '';
        } else {
            // Handle different types of errors
            let errorMessage = result.message || 'Transaction failed.';
            
            document.getElementById('pinModalError').textContent = errorMessage;
            document.getElementById('pinModalError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Transaction error:', error);
        document.getElementById('pinModalError').textContent = 'Network error. Please check your connection and try again.';
        document.getElementById('pinModalError').classList.remove('hidden');
    } finally {
        // Reset button state
        confirmBtn.disabled = false;
        loadingSpinner.classList.add('hidden');
        if (confirmBtnText && confirmBtnText.nodeType === Node.TEXT_NODE) {
            confirmBtn.textContent = '✅ Confirm & Fund Account';
        } else if (confirmBtnText) {
            confirmBtnText.classList.remove('hidden');
        }
    }
});

// Enhanced function to call Laravel partial (same as airtime)
async function showSuccessPartial(data) {
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
            modalWrapper.id = 'successPartialModal';
            modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
            
            modalWrapper.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative transform animate-bounce-in">
                    <button id="closePartialModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold z-10">
                        ✕
                    </button>
                    <div class="p-6">
                        ${partialHtml}
                        <div class="mt-6">
                            <button id="doneBtn" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl hover:bg-teal-700 transition duration-200">
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
            
            document.getElementById('closePartialModal').addEventListener('click', closeModal);
            document.getElementById('doneBtn').addEventListener('click', closeModal);
            
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
            showSimpleSuccessModal(data);
        }
    } catch (error) {
        console.error('Error loading success partial:', error);
        // Fallback to simple success message
        showSimpleSuccessModal(data);
    }
}

// Fallback function for simple success display
function showSimpleSuccessModal(data) {
    let detailsText = '';
    if (data.details) {
        detailsText = '\n\nDetails:\n' + Object.entries(data.details)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\n');
    }
    
    alert(`${data.title}\n\n${data.message}${detailsText}`);
}

// Close PIN modal when clicking outside
document.getElementById('pinModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('pinModal')) {
        document.getElementById('pinModal').classList.add('hidden');
    }
});
</script>

@endsection
