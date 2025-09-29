@extends('layouts.app')
@section('title', 'Electricity Purchase')

@section('content')
{{-- {{dd($plans)}} --}}
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Electricity</h1>
        </div>

        <!-- Success/Error Message Container -->
        <div id="messageContainer" class="mb-6 hidden"></div>

        <!-- Electricity Purchase Form -->
        <form id="electricityForm" action="{{ route('electricity.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Service Provider -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Electricity Company</label>
                <div id="openServiceProvider"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span class="provider-text">Choose a Provider</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <input type="hidden" id="networkInput" name="electricCompany" required>
            </div>

            <!-- Package -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Meter Type</label>
                <div id="openPackage"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span class="package-text">Choose a Package</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <input type="hidden" name="package" id="packageInput" required>
            </div>

            <!-- Meter Number -->
            <div>
                <label for="meterNumber" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">
                    Meter Number
                </label>
                <div class="relative">
                    <input
                    type="text"
                    id="meterNumber"
                    name="meterNumber"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    placeholder="Enter meter number"
                    >
                    <button
                    type="button"
                    id="verifyMeterBtn"
                    disabled
                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gray-50 text-gray-400 px-4 py-1 rounded-full text-sm cursor-not-allowed"
                    >
                    {{-- Verify --}}
                    </button>
                </div>
                <div id="meterVerification" class="mt-2 text-sm hidden"></div>
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Amount</label>
                <input type="number" id="amount" name="amount"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    placeholder="Enter amount" required min="100">
                <div id="amountLimits" class="mt-1 text-xs text-gray-500 hidden"></div>
            </div>

            <input type="hidden" id="minAmount" name="minAmount">
            <input type="hidden" id="maxAmount" name="maxAmount">

            <!-- Pay Button -->
            <div class="justify-center flex items-center">
                <button type="submit"
                    class="w-full lg:w-72 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Service Provider Modal -->
<div id="serviceProviderModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-2/3 max-h-96">
        <div class="bg-brand-green px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">Choose a Provider</h3>
                <button class="close-modal text-white hover:text-gray-200" data-modal="serviceProviderModal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-4 h-full overflow-y-auto">
            <ul id="serviceProviderList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- Package Modal -->
<div id="packageModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-2/3 max-h-96">
        <div class="bg-brand-green px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">Choose a Meter Type</h3>
                <button class="close-modal text-white hover:text-gray-200" data-modal="packageModal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 relative transform scale-95 transition-transform duration-300">
        
        <!-- Close button -->
        <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <!-- Header -->
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Electricity Purchase</h2>
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
            ✅ Confirm & Purchase
        </button>
        
        <!-- Error Message -->
        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- Custom Modal -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full mx-4 relative">
        <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
        <h2 id="modalTitle" class="text-lg font-bold mb-2"></h2>
        <p id="modalMessage" class="whitespace-pre-wrap text-gray-700"></p>
    </div>
</div>

<!-- Loader -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<style>
    .loader {
        border-top-color: #3490dc;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    // Wallet balance from server
    const walletBalance = {{ $wallet->balance ?? 0 }};
    let meterVerified = false;
    let customerName = '';

    function showModal(title, message, textColor = 'text-gray-700') {
        const modal = document.getElementById('customModal');
        document.getElementById('modalTitle').textContent = title;
        const messageEl = document.getElementById('modalMessage');
        messageEl.textContent = message;
        messageEl.className = `whitespace-pre-wrap ${textColor}`;
        modal.classList.remove('hidden');
    }

    function toggleLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.toggle('hidden', !show);
    }

    function showMessage(content, type = 'success') {
        const container = document.getElementById('messageContainer');
        container.innerHTML = content;
        container.classList.remove('hidden');
        
        // Auto-hide after 10 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                container.classList.add('hidden');
            }, 10000);
        }
    }

    // Modal close handlers
    document.getElementById('modalCloseBtn').addEventListener('click', () => {
        document.getElementById('customModal').classList.add('hidden');
    });

    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });

    // Close modal handlers
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modalId = e.target.closest('.close-modal').dataset.modal;
            const modal = document.getElementById(modalId);
            modalHide(modal);
        });
    });

    // Verify Meter
    document.getElementById('verifyMeterBtn').addEventListener('click', async () => {
        const electricCompany = document.getElementById('networkInput').value.trim();
        const meterNumber = document.getElementById('meterNumber').value.trim();
        const verificationDiv = document.getElementById('meterVerification');

        if (!electricCompany) {
            showModal('Error', 'Please select your Electric Company first.', 'text-red-600');
            return;
        }
        if (!meterNumber) {
            showModal('Error', 'Please enter your Meter Number.', 'text-red-600');
            return;
        }

        toggleLoading(true);

        try {
            const response = await fetch("{{ route('verify.meter') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    electricCompany: electricCompany,
                    meterNo: meterNumber
                })
            });

            const data = await response.json();

            if (response.ok && data.status) {
                meterVerified = true;
                customerName = data.customer_name;
                verificationDiv.innerHTML = `<span class="text-green-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Verified: ${data.customer_name}</span>`;
                verificationDiv.classList.remove('hidden');
                
                showModal('Verification Successful', `Customer Name: ${data.customer_name}\nMeter Number: ${data.meter_number}`, 'text-green-600');
            } else {
                meterVerified = false;
                customerName = '';
                verificationDiv.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>${data.message || 'Verification failed'}</span>`;
                verificationDiv.classList.remove('hidden');
                
                showModal('Verification Failed', data.message || 'Invalid meter number', 'text-red-600');
            }
        } catch (error) {
            meterVerified = false;
            customerName = '';
            verificationDiv.innerHTML = `<span class="text-red-600 flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>Verification failed</span>`;
            verificationDiv.classList.remove('hidden');
            
            showModal('Error', 'Verification failed. Please try again.', 'text-red-600');
            console.error(error);
        } finally {
            toggleLoading(false);
        }
    });

    // Provider and Package Selection
    const plans = @json($plans);

    const modalShow = (modal) => {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('.rounded-t-2xl').classList.remove('translate-y-full');
        }, 10);
    };

    const modalHide = (modal) => {
        modal.classList.add('opacity-0');
        modal.querySelector('.rounded-t-2xl').classList.add('translate-y-full');
        setTimeout(() => {
            if (modal.classList.contains('opacity-0')) {
                modal.classList.add('hidden');
            }
        }, 300);
    };

    // Service Provider
    document.getElementById('openServiceProvider').addEventListener('click', () => {
        const list = document.getElementById('serviceProviderList');
        list.innerHTML = '';

        Object.keys(plans).forEach(providerKey => {
            const company = plans[providerKey][0];
            const btn = document.createElement('button');
            btn.textContent = company.NAME;
            btn.className = 'block w-full text-left px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors';
            btn.onclick = () => {
                document.querySelector('.provider-text').textContent = company.NAME;
                document.getElementById('networkInput').value = providerKey;
                
                // Reset package selection
                document.querySelector('.package-text').textContent = 'Choose a Package';
                document.getElementById('packageInput').value = '';
                document.getElementById('minAmount').value = '';
                document.getElementById('maxAmount').value = '';
                document.getElementById('amountLimits').classList.add('hidden');
                
                // Reset meter verification
                meterVerified = false;
                customerName = '';
                document.getElementById('meterVerification').classList.add('hidden');
                
                modalHide(document.getElementById('serviceProviderModal'));
            };
            list.appendChild(btn);
        });

        modalShow(document.getElementById('serviceProviderModal'));
    });

    // Meter Type (Package)
    document.getElementById('openPackage').addEventListener('click', () => {
        const providerKey = document.getElementById('networkInput').value;
        if (!providerKey) {
            showModal('Error', 'Please choose a provider first.', 'text-red-600');
            return;
        }

        const products = plans[providerKey][0].PRODUCT;
        const list = document.getElementById('packageList');
        list.innerHTML = '';

        products.forEach(product => {
            const btn = document.createElement('button');
            btn.innerHTML = `
                <div class="text-left">
                    <div class="font-semibold">${product.PRODUCT_TYPE}</div>
                    <div class="text-sm text-gray-500">Min: ₦${parseInt(product.MINIMUN_AMOUNT).toLocaleString()} - Max: ₦${parseInt(product.MAXIMUM_AMOUNT).toLocaleString()}</div>
                </div>
            `;
            btn.className = 'block w-full px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors';
            btn.onclick = () => {
                document.querySelector('.package-text').textContent = product.PRODUCT_TYPE;
                document.getElementById('packageInput').value = product.PRODUCT_ID;
                document.getElementById('minAmount').value = product.MINIMUN_AMOUNT;
                document.getElementById('maxAmount').value = product.MAXIMUM_AMOUNT;

                // Update amount input limits
                const amountInput = document.getElementById('amount');
                amountInput.min = product.MINIMUN_AMOUNT;
                amountInput.max = product.MAXIMUM_AMOUNT;
                
                // Show amount limits
                document.getElementById('amountLimits').textContent = `Min: ₦${parseInt(product.MINIMUN_AMOUNT).toLocaleString()} - Max: ₦${parseInt(product.MAXIMUM_AMOUNT).toLocaleString()}`;
                document.getElementById('amountLimits').classList.remove('hidden');

                modalHide(document.getElementById('packageModal'));
            };
            list.appendChild(btn);
        });

        modalShow(document.getElementById('packageModal'));
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modalHide(modal);
        });
    });

    // Form submission with PIN modal
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('electricityForm');
        const originalFormAction = form.action;
        
        // Override form submission to show PIN modal
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form fields
            const electricCompany = document.getElementById('networkInput').value;
            const electricCompanyName = document.querySelector('.provider-text').textContent;
            const packageType = document.getElementById('packageInput').value;
            const packageName = document.querySelector('.package-text').textContent;
            const meterNumber = document.getElementById('meterNumber').value;
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const minAmount = parseFloat(document.getElementById('minAmount').value) || 0;
            const maxAmount = parseFloat(document.getElementById('maxAmount').value) || 0;
            
            // Validation
            if (!electricCompany || electricCompanyName === 'Choose a Provider') {
                return showModal('Error', 'Please select an electricity company.', 'text-red-600');
            }
            if (!packageType || packageName === 'Choose a Package') {
                return showModal('Error', 'Please select a meter type.', 'text-red-600');
            }
            if (!meterNumber) {
                return showModal('Error', 'Please enter your meter number.', 'text-red-600');
            }
            if (!amount || amount < 100) {
                return showModal('Error', 'Please enter a valid amount (minimum ₦100).', 'text-red-600');
            }
            if (minAmount && amount < minAmount) {
                return showModal('Error', `Minimum amount for this meter type is ₦${minAmount.toLocaleString()}.`, 'text-red-600');
            }
            if (maxAmount && amount > maxAmount) {
                return showModal('Error', `Maximum amount for this meter type is ₦${maxAmount.toLocaleString()}.`, 'text-red-600');
            }
            
            // Check wallet balance
            if (walletBalance < amount) {
                showModal(
                    'Insufficient Balance',
                    `Your account balance (₦${walletBalance.toLocaleString()}) is not enough for ₦${amount.toLocaleString()}. Please fund your wallet.`,
                    'text-red-600'
                );
                return;
            }
            
            // Show PIN modal with summary
            document.getElementById('pinModalSummary').innerHTML = `
                <div><strong>Company:</strong> ${electricCompanyName}</div>
                <div><strong>Meter Type:</strong> ${packageName}</div>
                <div><strong>Meter Number:</strong> ${meterNumber}</div>
                ${customerName ? `<div><strong>Customer:</strong> ${customerName}</div>` : ''}
                <div><strong>Amount:</strong> ₦${amount.toLocaleString()}</div>
                <div class="mt-2 font-semibold text-green-700"><strong>Your Balance:</strong> ₦${walletBalance.toLocaleString()}</div>
            `;
            
            // Clear PIN inputs and errors
            document.querySelectorAll('.pin-input').forEach(input => input.value = '');
            document.getElementById('pinModalError').classList.add('hidden');
            
            // Show PIN modal
            document.getElementById('pinModal').classList.remove('hidden');
            document.querySelector('.pin-input').focus();
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

        // Handle PIN confirmation
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

            // Gather form data
            const formData = new FormData();
            formData.append('electricCompany', document.getElementById('networkInput').value);
            formData.append('package', document.getElementById('packageInput').value);
            formData.append('meterNumber', document.getElementById('meterNumber').value);
            formData.append('amount', document.getElementById('amount').value);
            formData.append('minAmount', document.getElementById('minAmount').value);
            formData.append('maxAmount', document.getElementById('maxAmount').value);
            formData.append('pin', pin);
            formData.append('_token', "{{ csrf_token() }}");

            // Show loading
            toggleLoading(true);

            try {
                const response = await fetch(originalFormAction, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status) {
                    document.getElementById('pinModal').classList.add('hidden');
                    
                    // Prepare success details
                    const details = {
                        'Company': document.querySelector('.provider-text').textContent,
                        'Meter Type': document.querySelector('.package-text').textContent,
                        'Meter Number': document.getElementById('meterNumber').value,
                        'Amount': '₦' + parseFloat(document.getElementById('amount').value).toLocaleString(),
                        'Transaction ID': result.data?.transaction_id || 'N/A',
                        'New Balance': '₦' + (result.data?.new_balance || 0).toLocaleString()
                    };

                    // Add token if available
                    if (result.data?.token) {
                        details['Token'] = result.data.token;
                    }

                    // Render success partial
                    try {
                        const successResponse = await fetch("{{ route('electricity.render-success') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                title: '✅ Electricity Purchase Successful!',
                                message: result.message || 'Your electricity purchase was completed successfully.',
                                details: details
                            })
                        });

                        if (successResponse.ok) {
                            const successHtml = await successResponse.text();
                            showMessage(successHtml, 'success');
                        } else {
                            // Fallback success message
                            showMessage(`
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <h3 class="text-lg font-semibold text-green-800">✅ Electricity Purchase Successful!</h3>
                                    </div>
                                    <p class="mt-2 text-green-700">${result.message || 'Your electricity purchase was completed successfully.'}</p>
                                    ${result.data?.token ? `<div class="mt-2 p-2 bg-white rounded border"><strong>Token:</strong> ${result.data.token}</div>` : ''}
                                </div>
                            `, 'success');
                        }
                    } catch (renderError) {
                        console.error('Success render error:', renderError);
                        // Fallback success message
                        showMessage(`
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <h3 class="text-lg font-semibold text-green-800">✅ Electricity Purchase Successful!</h3>
                                </div>
                                <p class="mt-2 text-green-700">${result.message || 'Your electricity purchase was completed successfully.'}</p>
                                ${result.data?.token ? `<div class="mt-2 p-2 bg-white rounded border"><strong>Token:</strong> ${result.data.token}</div>` : ''}
                            </div>
                        `, 'success');
                    }
                    
                    // Reset form
                    form.reset();
                    document.querySelector('.provider-text').textContent = 'Choose a Provider';
                    document.querySelector('.package-text').textContent = 'Choose a Package';
                    document.getElementById('networkInput').value = '';
                    document.getElementById('packageInput').value = '';
                    document.getElementById('minAmount').value = '';
                    document.getElementById('maxAmount').value = '';
                    document.getElementById('amountLimits').classList.add('hidden');
                    
                } else {
                    document.getElementById('pinModalError').textContent = result.message || 'Purchase failed.';
                    document.getElementById('pinModalError').classList.remove('hidden');
                }
            } catch (error) {
                console.error('Purchase error:', error);
                document.getElementById('pinModalError').textContent = 'Purchase failed. Please try again.';
                document.getElementById('pinModalError').classList.remove('hidden');
            } finally {
                toggleLoading(false);
            }
        });
    });
</script>
@endsection