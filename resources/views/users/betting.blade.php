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
            <form id="bettingForm" action="{{ route('fund_bet') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Betting Company Selection -->
                <div class="pt-6 lg:pt-0">
                    <label for="betting_company" class="block text-sm font-semibold text-gray-700 mb-2">Betting
                        Company</label>
                    <div class="relative">
                        <input type="text" id="betting_company_display"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                            placeholder="Select Betting Company" readonly onclick="showBettingModal()">
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
                    <div class="relative">
                        <input type="text" id="customer_id" name="customer_id" placeholder="Enter your betting account ID"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                            required>
                        <button type="button" id="verifyCustomerBtn"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-brand-green text-white text-xs px-3 py-1.5 rounded-full hover:bg-teal-700 transition duration-200 hidden">
                            Verify
                        </button>
                    </div>
                    <div id="customerNameDisplay" class="mt-2 text-sm text-green-600 font-medium hidden"></div>
                </div>

                <!-- Amount Input -->
                <div class="pt-6 lg:pt-0">
                    <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" placeholder="0.00" min="100" max="50000"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        required>
                    <p class="mt-1 text-xs text-gray-500">Min: ₦100 | Max: ₦50,000</p>
                </div>

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
            <div class="bg-white p-4 h-full overflow-y-auto" id="bettingCompaniesList">
                <!-- Will be populated dynamically -->
            </div>
        </div>
    </div>

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

    <style>
        /* Spinner styles */
        .loader {
            border-top-color: #3490dc;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes bounce-in {
            0% { transform: scale(0.9); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fade-out {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .animate-bounce-in {
            animation: bounce-in 0.3s ease-out;
        }
        .animate-fade-out {
            animation: fade-out 0.3s ease-out;
        }
    </style>

    <script>
    // Wallet balance from controller
    const walletBalance = {{ $wallet->balance ?? 0 }};
    
    // Betting companies from controller - parse carefully
    let bettingCompanies = [];
    try {
        const rawData = @json($bettingCompanies ?? []);
        console.log('Raw betting companies data:', rawData);

            if (Array.isArray(rawData)) {
                bettingCompanies = rawData;
            } else if (rawData && typeof rawData === 'object') {
                if (rawData.BETTING_COMPANY) {
                    bettingCompanies = Array.isArray(rawData.BETTING_COMPANY)
                        ? rawData.BETTING_COMPANY
                        : Object.values(rawData.BETTING_COMPANY);
                } else if (rawData.SERVICES) {
                    bettingCompanies = Array.isArray(rawData.SERVICES)
                        ? rawData.SERVICES
                        : Object.values(rawData.SERVICES);
                } else {
                    bettingCompanies = Object.values(rawData);
                }
            }

            if (!Array.isArray(bettingCompanies)) {
                bettingCompanies = [];
            }

            console.log('Processed betting companies:', bettingCompanies);
        } catch (error) {
            console.error('Error parsing betting companies:', error);
            bettingCompanies = [];
        }

    // Show betting modal
    function showBettingModal() {
        const bettingModal = document.getElementById('bettingModal');
        const modalContent = bettingModal.querySelector('div');
        bettingModal.classList.remove('hidden');
        setTimeout(() => {
            bettingModal.classList.add('opacity-100');
            modalContent.classList.remove('translate-y-full');
        }, 10);
    }

    // Hide betting modal
    function hideBettingModal() {
        const bettingModal = document.getElementById('bettingModal');
        const modalContent = bettingModal.querySelector('div');
        bettingModal.classList.remove('opacity-100');
        modalContent.classList.add('translate-y-full');
        setTimeout(() => bettingModal.classList.add('hidden'), 300);
    }

    // Populate betting companies from controller data
    function populateBettingCompanies() {
    const companiesList = document.getElementById('bettingCompaniesList');
    console.log('Populating with companies:', bettingCompanies);

    if (!bettingCompanies || bettingCompanies.length === 0) {
        companiesList.innerHTML = `
            <div class="text-center py-8">
                <p class="text-gray-500 mb-4">No betting companies available</p>
                <button onclick="location.reload()" class="text-brand-green hover:underline">Refresh Page</button>
            </div>
        `;
        return;
    }

    const colors = ['yellow-500', 'purple-600', 'green-600', 'blue-700', 'orange-600', 'red-600', 'teal-600', 'pink-600', 'indigo-600', 'blue-500', 'purple-700', 'green-700'];
    let html = '<ul class="grid grid-cols-1 gap-2">';

    bettingCompanies.forEach((company, index) => {
        let companyCode, displayName, minAmount, maxAmount;

        if (typeof company === 'string') {
            companyCode = company;
            displayName = company.toUpperCase();
            minAmount = 100;
            maxAmount = 50000;
        } else if (company && typeof company === 'object') {
            companyCode = company.PRODUCT_CODE || company.code || company.BETTINGCOMPANY || company.BettingCompany || company.key || company.value || '';
            displayName = company.name || company.NAME || company.DESCRIPTION || company.Description || companyCode.toUpperCase();
            minAmount = company.MINAMOUNT || company.min_amount || company.MIN_AMOUNT || 100;
            maxAmount = company.MAXAMOUNT || company.max_amount || company.MAX_AMOUNT || 50000;
        }

        if (!companyCode) return;

        const color = colors[index % colors.length];

        html += `
            <li class="flex justify-between items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                data-value="${companyCode}" data-display="${displayName}">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-${color} flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">${displayName.substring(0, 2)}</span>
                    </div>
                    <span class="text-gray-800 font-medium">${displayName}</span>
                </div>
                <div class="text-right text-xs text-gray-500">
                    Min: ₦${minAmount} <br> Max: ₦${maxAmount}
                </div>
            </li>
        `;
    });

    html += '</ul>';
    companiesList.innerHTML = html;

    // Add click event
    document.querySelectorAll('.betting-option').forEach(option => {
        option.addEventListener('click', function() {
            const displayValue = this.dataset.display;
            const codeValue = this.dataset.value;

            document.getElementById('betting_company_display').value = displayValue;
            document.getElementById('betting_company').value = codeValue;
            hideBettingModal();

            document.getElementById('customerNameDisplay').classList.add('hidden');
            document.getElementById('customerNameDisplay').textContent = '';

            const event = new Event('change');
            document.getElementById('betting_company').dispatchEvent(event);
        });
    });
}

    document.addEventListener('DOMContentLoaded', function() {


    // Show betting modal
    function showBettingModal() {
        const bettingModal = document.getElementById('bettingModal');
        const modalContent = bettingModal.querySelector('div');
        bettingModal.classList.remove('hidden');
        setTimeout(() => {
            bettingModal.classList.add('opacity-100');
            modalContent.classList.remove('translate-y-full');
        }, 10);
    }

    // Hide betting modal
    function hideBettingModal() {
        const bettingModal = document.getElementById('bettingModal');
        const modalContent = bettingModal.querySelector('div');
        bettingModal.classList.remove('opacity-100');
        modalContent.classList.add('translate-y-full');
        setTimeout(() => bettingModal.classList.add('hidden'), 300);
    }

    // Close betting modal on outside click
    document.getElementById('bettingModal').addEventListener('click', function(event) {
        if (event.target === this) hideBettingModal();
    });

    // Verify customer ID
    async function verifyCustomer() {
        const bettingCompany = document.getElementById('betting_company').value;
        const customerId = document.getElementById('customer_id').value;
        const verifyBtn = document.getElementById('verifyCustomerBtn');
        const customerNameDisplay = document.getElementById('customerNameDisplay');

        if (!bettingCompany || !customerId) {
            showErrorMessage('Please select a betting company and enter customer ID.');
            return;
        }

        // Show loading state
        const originalText = verifyBtn.textContent;
        verifyBtn.textContent = 'Verifying...';
        verifyBtn.disabled = true;

        try {
            const response = await fetch("{{ route('betting.verify-customer') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    betting_company: bettingCompany,
                    customer_id: customerId
                })
            });

            const result = await response.json();

            if (response.ok && result.status) {
                customerNameDisplay.textContent = `✓ Customer: ${result.data.customer_name}`;
                customerNameDisplay.classList.remove('hidden', 'text-red-600');
                customerNameDisplay.classList.add('text-green-600');
            } else {
                customerNameDisplay.textContent = `✗ ${result.message || 'Invalid Customer ID'}`;
                customerNameDisplay.classList.remove('hidden', 'text-green-600');
                customerNameDisplay.classList.add('text-red-600');
            }
        } catch (error) {
            console.error('Verification error:', error);
            customerNameDisplay.textContent = '✗ Verification failed. Please try again.';
            customerNameDisplay.classList.remove('hidden', 'text-green-600');
            customerNameDisplay.classList.add('text-red-600');
        } finally {
            verifyBtn.textContent = originalText;
            verifyBtn.disabled = false;
        }
    }

    // Handle form submission
    function handleFormSubmit(e) {
        e.preventDefault();
        
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
            showErrorMessage(`Insufficient balance. Your wallet balance is ₦${walletBalance.toFixed(2)}. Please fund your wallet.`);
            return;
        }
        
        // Show PIN modal with summary
        document.getElementById('pinModalSummary').innerHTML = `
            <div class="space-y-1">
                <div class="flex justify-between"><span class="font-medium">Betting Company:</span> <span>${bettingCompanyDisplay}</span></div>
                <div class="flex justify-between"><span class="font-medium">Customer ID:</span> <span>${customerId}</span></div>
                <div class="flex justify-between"><span class="font-medium">Amount:</span> <span>₦${amount.toFixed(2)}</span></div>
                <div class="flex justify-between pt-2 border-t"><span class="font-semibold text-green-700">New Balance:</span> <span class="font-semibold text-green-700">₦${(walletBalance - amount).toFixed(2)}</span></div>
            </div>
        `;
        
        // Clear PIN inputs and errors
        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        
        // Show PIN modal
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    }

    // Setup PIN modal
    function setupPinModal() {
        // Close button
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

        // Confirm button
        document.getElementById('confirmPinBtn').addEventListener('click', handlePinConfirmation);

        // Close when clicking outside
        document.getElementById('pinModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('pinModal')) {
                document.getElementById('pinModal').classList.add('hidden');
            }
        });
    }

    // Handle PIN confirmation
    async function handlePinConfirmation(e) {
        e.preventDefault();
        
        const pinInputs = document.querySelectorAll('.pin-input');
        let pin = '';
        pinInputs.forEach(input => pin += input.value);

        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            document.getElementById('pinModalError').textContent = 'Please enter your 4-digit PIN.';
            document.getElementById('pinModalError').classList.remove('hidden');
            return;
        }

        // Show loading state
        const confirmBtn = document.getElementById('confirmPinBtn');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        `;
        confirmBtn.disabled = true;

        // Gather form data
        const formData = new FormData();
        formData.append('betting_company', document.getElementById('betting_company').value);
        formData.append('customer_id', document.getElementById('customer_id').value);
        formData.append('amount', document.getElementById('amount').value);
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
                
                // Show success message
                await showSuccessPartial({
                    title: 'Betting Account Funded Successfully!',
                    message: result.message || 'Your betting account has been funded successfully!',
                    details: {
                        'Transaction Reference': result.data?.reference || 'N/A',
                        'Order ID': result.data?.order_id || 'N/A',
                        'Betting Company': document.getElementById('betting_company_display').value,
                        'Customer ID': document.getElementById('customer_id').value,
                        'Amount': `₦${parseFloat(document.getElementById('amount').value).toFixed(2)}`,
                        'Transaction Status': result.data?.transaction_status?.toUpperCase() || 'PENDING',
                        'New Wallet Balance': result.data?.new_balance ? `₦${parseFloat(result.data.new_balance).toFixed(2)}` : 'N/A',
                        'Date & Time': new Date().toLocaleString()
                    }
                });
                
                // Reset form
                document.getElementById('bettingForm').reset();
                document.getElementById('betting_company_display').value = '';
                document.getElementById('betting_company').value = '';
                document.getElementById('customerNameDisplay').classList.add('hidden');
                
                // Reload page after 3 seconds to refresh balance
                setTimeout(() => window.location.reload(), 3000);
            } else {
                // Handle errors
                let errorMessage = result.message || 'Transaction failed.';
                
                if (result.errors) {
                    errorMessage = Object.values(result.errors).flat().join(', ');
                }
                
                document.getElementById('pinModalError').textContent = errorMessage;
                document.getElementById('pinModalError').classList.remove('hidden');
            }
        } catch (error) {
            console.error('Transaction error:', error);
            document.getElementById('pinModalError').textContent = 'Network error. Please check your connection and try again.';
            document.getElementById('pinModalError').classList.remove('hidden');
        } finally {
            // Reset button state
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        }
    }

    // Show success partial
    async function showSuccessPartial(data) {
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

                const closeModal = () => {
                    modalWrapper.classList.add('animate-fade-out');
                    setTimeout(() => modalWrapper.remove(), 300);
                };
                
                document.getElementById('closePartialModal').addEventListener('click', closeModal);
                document.getElementById('doneBtn').addEventListener('click', closeModal);
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
                showSimpleSuccessModal(data);
            }
        } catch (error) {
            console.error('Error loading success partial:', error);
            showSimpleSuccessModal(data);
        }
    }

    // Fallback success modal
    function showSimpleSuccessModal(data) {
        let detailsText = '';
        if (data.details) {
            detailsText = '\n\nDetails:\n' + Object.entries(data.details)
                .map(([key, value]) => `${key}: ${value}`)
                .join('\n');
        }
        
        alert(`${data.title}\n\n${data.message}${detailsText}`);
    }

    // Show error message
    function showErrorMessage(message) {
        const existingMessages = document.querySelectorAll('.message-alert');
        existingMessages.forEach(msg => msg.remove());

        const messageEl = document.createElement('div');
        messageEl.className = 'message-alert mb-4 p-3 rounded bg-red-100 text-red-700 border border-red-300';
        messageEl.textContent = message;

        const form = document.getElementById('bettingForm');
        form.parentNode.insertBefore(messageEl, form);

        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 5000);
    }
    </script>
@endsection