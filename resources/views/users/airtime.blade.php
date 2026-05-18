@extends('layouts.app')

@section('title', 'Airtime Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Airtime Purchase</h1>
        </div>

        {{-- Show success message --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Show error message --}}
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Show validation errors --}}
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="airtimeForm" action="{{ route('airtime.recharge') }}" method="POST">
            @csrf

            <!-- Mobile Network -->
            <div class="pt-6 lg:pt-0 mb-4">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <select
                        id="network"
                        name="network"
                        class="w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                        required
                    >
                        <option value="" disabled {{ old('network') ? '' : 'selected' }}>Choose A Network</option>
                        <option value="01" {{ old('network') == '01' ? 'selected' : '' }}>MTN</option>
                        <option value="02" {{ old('network') == '02' ? 'selected' : '' }}>Glo</option>
                        <option value="03" {{ old('network') == '03' ? 'selected' : '' }}>Airtel</option>
                        <option value="04" {{ old('network') == '04' ? 'selected' : '' }}>9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Mobile Number -->
            <div class="mb-4">
                <label for="recipient" class="block text-sm font-medium text-gray-600 mb-1">Mobile Number</label>
                <input 
                    type="text"
                    name="recipient"
                    id="recipient"
                    placeholder="e.g. 08012345678"
                    class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    value="{{ old('recipient') }}"
                    maxlength="15"
                    pattern="[0-9]+"
                    title="Please enter only numbers"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">Enter phone number without country code</small>
            </div>

            <!-- Amount -->
            <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-600 mb-1">Amount (₦)</label>
                <input 
                    type="number"
                    name="amount"
                    id="amount"
                    placeholder="e.g. 100"
                    min="50"
                    max="50000"
                    step="1"
                    class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    value="{{ old('amount') }}"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">Minimum: ₦50, Maximum: ₦50,000</small>
            </div>

            <button 
                type="button"
                id="submitBtn"
                class="w-full bg-brand-green text-white font-bold py-3 px-4 rounded-xl hover:bg-green-700 transition duration-200"
            >
                Recharge Airtime
            </button>
        </form>
    </div>
</div>

<!-- Custom Network Selection Modal -->
<div id="networkModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2" id="modalContent">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-600">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 pb-20 h-full overflow-y-auto">
            <ul class="space-y-2 ">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="01">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">M</span>
                    </div>
                    <span class="text-gray-800 font-medium">MTN</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="02">
                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">G</span>
                    </div>
                    <span class="text-gray-800 font-medium">Glo</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="03">
                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">A</span>
                    </div>
                    <span class="text-gray-800 font-medium">Airtel</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="04">
                    <div class="w-8 h-8 bg-green-800 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">9</span>
                    </div>
                    <span class="text-gray-800 font-medium">9mobile</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const networkSelect = document.getElementById('network');
        const networkModal = document.getElementById('networkModal');
        const modalContent = document.getElementById('modalContent');
        const networkOptions = document.querySelectorAll('.network-option');
        const form = document.getElementById('airtimeForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const loadingText = document.getElementById('loadingText');
        const recipientInput = document.getElementById('recipient');
        const amountInput = document.getElementById('amount');

        // Phone number formatting and validation
        recipientInput.addEventListener('input', function(e) {
            // Remove non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 15 characters
            if (value.length > 15) {
                value = value.substr(0, 15);
            }
            
            e.target.value = value;

            // Auto-detect network from number and update network select
            const detected = detectNetworkFromNumber(value);
            if (detected) {
                // mapping to the select option codes used in this view
                const map = { mtn: '01', glo: '02', airtel: '03', '9mobile': '04' };
                const code = map[detected];
                if (code) {
                    networkSelect.value = code;
                    networkSelect.dispatchEvent(new Event('change'));
                }
            }
        });

        // Amount validation
        amountInput.addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            if (value && (value < 50 || value > 50000)) {
                e.target.setCustomValidity('Amount must be between ₦50 and ₦50,000');
            } else {
                e.target.setCustomValidity('');
            }
        });

        // Function to show the modal with slide-up animation and dimming
        function showModal() {
            networkModal.classList.remove('hidden');
            // Allow a brief moment for the 'hidden' class to be removed before starting transition
            setTimeout(() => {
                networkModal.classList.add('opacity-100'); // Fade in the overlay
                modalContent.classList.remove('translate-y-full'); // Slide up the content
            }, 10);
        }

        // Detect network by Nigerian prefixes (supports +234 and 234 formats)
        function detectNetworkFromNumber(raw) {
            if (!raw) return null;
            let num = raw.trim();
            if (num.startsWith('+234')) num = '0' + num.slice(4);
            if (num.startsWith('234')) num = '0' + num.slice(3);
            if (!num.startsWith('0')) {
                return null;
            }
            const prefix4 = num.slice(0,4);
            const mtn = ['0803','0806','0703','0706','0813','0816','0810','0814','0903','0906','0913','0916'];
            const glo = ['0805','0807','0815','0811','0905','0915'];
            const airtel = ['0802','0808','0708','0812','0701','0902','0907','0912'];
            const nine = ['0809','0817','0818','0909','0908','0917'];

            if (mtn.includes(prefix4)) return 'mtn';
            if (glo.includes(prefix4)) return 'glo';
            if (airtel.includes(prefix4)) return 'airtel';
            if (nine.includes(prefix4)) return '9mobile';
            return null;
        }

        // Function to hide the modal with slide-down animation and undimming
        function hideModal() {
            networkModal.classList.remove('opacity-100'); // Fade out the overlay
            modalContent.classList.add('translate-y-full'); // Slide down the content
            // Wait for the transition to complete before hiding the modal entirely
            setTimeout(() => {
                networkModal.classList.add('hidden');
            }, 300); // This duration should match the CSS transition duration
        }

        // Prevent the default dropdown behavior and show custom modal
        networkSelect.addEventListener('mousedown', function(event) {
            event.preventDefault(); // Stop the default select dropdown from opening
            showModal();
        });

        // Handle selection from the custom modal
        networkOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedValue = this.dataset.value;
                const selectedText = this.querySelector('span').textContent;

                // Update the hidden select element's value
                networkSelect.value = selectedValue;
                
                // Trigger change event to update any listeners
                networkSelect.dispatchEvent(new Event('change'));

                hideModal(); // Hide the modal
            });
        });

        // Close modal when clicking outside of it
        networkModal.addEventListener('click', function(event) {
            if (event.target === networkModal) {
                hideModal();
            }
        });

        // Form submission handling
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            loadingText.classList.remove('hidden');

            // Get form data
            const formData = new FormData(form);

            // Make AJAX request
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.message, 'success');
                    
                    // Reset form
                    form.reset();
                    networkSelect.selectedIndex = 0;
                } else {
                    // Show error message
                    showMessage(data.message || 'Airtime purchase failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                btnText.classList.remove('hidden');
                loadingText.classList.add('hidden');
            });
        });

        // Function to show success/error messages
        function showMessage(message, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.message-alert');
            existingMessages.forEach(msg => msg.remove());

            // Create new message element
            const messageEl = document.createElement('div');
            messageEl.className = `message-alert mb-4 p-3 rounded ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            messageEl.textContent = message;

            // Insert message before the form
            form.parentNode.insertBefore(messageEl, form);

            // Auto-remove message after 5 seconds
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.remove();
                }
            }, 5000);
        }
    });
</script>

<!-- Updated PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
    
    <!-- Close button -->
    <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      ✕
    </button>
    
    <!-- Header -->
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Purchase</h2>
    <p id="pinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed"></p>
    
    <!-- PIN Input -->
    <div class="mb-5">
      <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
      <div class="flex space-x-3 justify-center">
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" required>
        <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" required>
      </div>
    </div>
    
    <!-- Confirm Button with Loading Animation -->
    <button id="confirmPinBtn" type="button" class="w-full bg-green-600 text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-green-700 hover:shadow-lg transition duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed">
        <span id="confirmBtnText">Confirm & Pay</span>
        <span id="confirmBtnLoading" class="hidden">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        </span>
    </button>
    
    <!-- Error Message -->
    <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const walletBalance = {{ $wallet->balance ?? 0 }}; // Get actual wallet balance from controller
    const networkSelect = document.getElementById('network');
    const networkModal = document.getElementById('networkModal');
    const modalContent = document.getElementById('modalContent');
    const networkOptions = document.querySelectorAll('.network-option');
    const form = document.getElementById('airtimeForm');
    const submitBtn = document.getElementById('submitBtn');
    const recipientInput = document.getElementById('recipient');
    const amountInput = document.getElementById('amount');

    // Phone number formatting and validation
    recipientInput.addEventListener('input', function(e) {
        // Remove non-digit characters
        let value = e.target.value.replace(/\D/g, '');
        
        // Limit to 15 characters
        if (value.length > 15) {
            value = value.substr(0, 15);
        }
        
        e.target.value = value;
    });

    // Amount validation
    amountInput.addEventListener('input', function(e) {
        const value = parseInt(e.target.value);
        if (value && (value < 50 || value > 50000)) {
            e.target.setCustomValidity('Amount must be between ₦50 and ₦50,000');
        } else {
            e.target.setCustomValidity('');
        }
    });

    // Function to show the network modal with slide-up animation and dimming
    function showNetworkModal() {
        networkModal.classList.remove('hidden');
        // Allow a brief moment for the 'hidden' class to be removed before starting transition
        setTimeout(() => {
            networkModal.classList.add('opacity-100'); // Fade in the overlay
            modalContent.classList.remove('translate-y-full'); // Slide up the content
        }, 10);
    }

    // Function to hide the network modal with slide-down animation and undimming
    function hideNetworkModal() {
        networkModal.classList.remove('opacity-100'); // Fade out the overlay
        modalContent.classList.add('translate-y-full'); // Slide down the content
        // Wait for the transition to complete before hiding the modal entirely
        setTimeout(() => {
            networkModal.classList.add('hidden');
        }, 300); // This duration should match the CSS transition duration
    }

    // Function to show error message (this was missing)
    function showErrorMessage(message) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message-alert');
        existingMessages.forEach(msg => msg.remove());

        // Create new message element
        const messageEl = document.createElement('div');
        messageEl.className = 'message-alert mb-4 p-3 rounded bg-red-100 text-red-700';
        messageEl.textContent = message;

        // Insert message before the form
        form.parentNode.insertBefore(messageEl, form);

        // Auto-remove message after 5 seconds
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }

    // Prevent the default dropdown behavior and show custom modal
    networkSelect.addEventListener('mousedown', function(event) {
        event.preventDefault(); // Stop the default select dropdown from opening
        showNetworkModal();
    });

    // Handle selection from the custom modal
    networkOptions.forEach(option => {
        option.addEventListener('click', function() {
            const selectedValue = this.dataset.value;
            const selectedText = this.querySelector('span').textContent;

            // Update the hidden select element's value
            networkSelect.value = selectedValue;
            
            // Trigger change event to update any listeners
            networkSelect.dispatchEvent(new Event('change'));

            hideNetworkModal(); // Hide the modal
        });
    });

    // Close network modal when clicking outside of it
    networkModal.addEventListener('click', function(event) {
        if (event.target === networkModal) {
            hideNetworkModal();
        }
    });

    // Show PIN modal when main button is clicked (FIXED VERSION)
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // Validate form fields
        const network = networkSelect.value;
        const recipient = recipientInput.value.trim();
        const amount = parseFloat(amountInput.value) || 0;

        if (!network) {
            showErrorMessage('Please select a network.');
            return;
        }
        if (!recipient) {
            showErrorMessage('Please enter a phone number.');
            return;
        }
        if (amount < 50 || amount > 50000) {
            showErrorMessage('Amount must be between ₦50 and ₦50,000.');
            return;
        }

        // Check wallet balance
        if (walletBalance < amount) {
            showErrorMessage(`Your account balance (₦${walletBalance.toFixed(2)}) is not enough to pay ₦${amount.toFixed(2)}. Please fund your wallet.`);
            return;
        }

        // Get network name for display
        const networkName = networkSelect.options[networkSelect.selectedIndex].text;

        // Show summary in PIN modal
        document.getElementById('pinModalSummary').innerHTML = `
          <div><strong>Network:</strong> ${networkName}</div>
          <div><strong>Phone Number:</strong> ${recipient}</div>
          <div><strong>Amount:</strong> ₦${amount.toFixed(2)}</div>
          <div class="mt-2 font-semibold text-green-700"><strong>Your Balance:</strong> ₦${walletBalance.toFixed(2)}</div>
        `;
        
        // Clear any previous PIN entries and errors
        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        
        // Show PIN modal
        document.getElementById('pinModal').classList.remove('hidden');
        
        // Focus first PIN input
        document.querySelector('.pin-input').focus();
    });

    // Close PIN modal
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
        
        // Handle backspace to go to previous input
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && idx > 0) {
                arr[idx - 1].focus();
            }
        });
    });

    // Handle PIN confirmation with loading animation
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
        const confirmBtnText = document.getElementById('confirmBtnText');
        const confirmBtnLoading = document.getElementById('confirmBtnLoading');
        
        confirmBtn.disabled = true;
        confirmBtnText.classList.add('hidden');
        confirmBtnLoading.classList.remove('hidden');

        // Prepare form data with PIN
        const formData = new FormData(form);
        formData.append('pin', pin);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Hide PIN modal
                document.getElementById('pinModal').classList.add('hidden');
                
                // Call the success partial with enhanced data
                await showSuccessPartial({
                    title: 'Airtime Purchase Successful!',
                    message: result.message,
                    details: {
                        'Transaction ID': result.data?.transaction_reference || 'N/A',
                        'Network': result.data?.network || networkSelect.options[networkSelect.selectedIndex].text,
                        'Phone Number': result.data?.recipient || recipientInput.value,
                        'Amount': result.data?.formatted_amount || `₦${parseFloat(amountInput.value).toFixed(2)}`,
                        'New Balance': result.data?.formatted_balance || 'N/A',
                        'Date & Time': result.data?.transaction_time ? 
                            new Date(result.data.transaction_time).toLocaleString() : 
                            new Date().toLocaleString()
                    }
                });
                
                // Reset form
                form.reset();
                networkSelect.selectedIndex = 0;
                
            } else {
                // Handle different types of errors
                let errorMessage = result.message || 'Purchase failed.';
                
                // Show more specific error for PIN validation
                if (result.error_type === 'pin_validation') {
                    errorMessage = result.message;
                } else if (result.error_type === 'validation') {
                    errorMessage = 'Please check your input and try again.';
                }
                
                document.getElementById('pinModalError').textContent = errorMessage;
                document.getElementById('pinModalError').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Purchase error:', error);
            document.getElementById('pinModalError').textContent = 'Network error. Please check your connection and try again.';
            document.getElementById('pinModalError').classList.remove('hidden');
        } finally {
            // Reset button state
            confirmBtn.disabled = false;
            confirmBtnText.classList.remove('hidden');
            confirmBtnLoading.classList.add('hidden');
        }
    });

    // Enhanced function to call Laravel partial
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
                                <button id="doneBtn" class="w-full bg-green-600 text-white font-semibold py-3 px-4 rounded-xl hover:bg-green-700 transition duration-200">
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
                // Fallback: build client-side partial that matches `partials.success`
                showClientSuccessPartial(data);
            }
        } catch (error) {
            console.error('Error loading success partial:', error);
            // Fallback to client-side partial to ensure consistent design
            showClientSuccessPartial(data);
        }
    }

    // Build and show a client-side success partial with matching styling
    function showClientSuccessPartial(data) {
        const details = data.details || {};

        const detailRows = Object.entries(details).map(([k, v]) => `
            <div class="flex justify-between items-center py-1">
                <span class="text-sm text-gray-600">${escapeHtml(k)}:</span>
                <span class="text-sm font-semibold text-gray-800 text-right ml-2">${escapeHtml(v)}</span>
            </div>
        `).join('<hr class="border-gray-100">');

        const partialHtml = `
            <div class="flex flex-col gap-4 p-6 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-green-800">${escapeHtml(data.title || 'Success!')}</h3>
                        <p class="mt-1 text-sm text-green-700 leading-relaxed">${escapeHtml(data.message || '')}</p>
                    </div>
                </div>
                ${Object.keys(details).length ? `
                <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Transaction Details
                    </h4>
                    <div class="space-y-2">
                        ${detailRows}
                    </div>
                </div>
                ` : ''}
                <div class="flex items-center justify-center pt-2">
                    <div class="flex items-center text-xs text-brand-green">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Transaction completed securely
                    </div>
                </div>
            </div>
        `;

        const modalWrapper = document.createElement('div');
        modalWrapper.id = 'clientSuccessPartialModal';
        modalWrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4';
        modalWrapper.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative transform animate-bounce-in">
                <button id="closeClientPartialModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-xl font-bold z-10">✕</button>
                <div class="p-6">
                    ${partialHtml}
                    <div class="mt-6">
                        <button id="clientDoneBtn" class="w-full bg-green-600 text-white font-semibold py-3 px-4 rounded-xl hover:bg-green-700 transition duration-200">Done</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modalWrapper);

        const close = () => {
            modalWrapper.classList.add('animate-fade-out');
            setTimeout(() => modalWrapper.remove(), 300);
        };
        document.getElementById('closeClientPartialModal').addEventListener('click', close);
        document.getElementById('clientDoneBtn').addEventListener('click', close);
        modalWrapper.addEventListener('click', (e) => { if (e.target === modalWrapper) close(); });
    }

    // Simple HTML escape to avoid injection when building client-side partial
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
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

    // Close modals when clicking outside
    document.getElementById('pinModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('pinModal')) {
            document.getElementById('pinModal').classList.add('hidden');
        }
    });
});
</script>

<style>
@keyframes bounce-in {
    0% {
        transform: scale(0.3) translate(-50%, -50%);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-bounce-in {
    animation: bounce-in 0.6s ease-out;
}
</style>
@endsection