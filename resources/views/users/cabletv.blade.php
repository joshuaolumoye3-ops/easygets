@extends('layouts.app')

@section('title', 'cable Purchase')

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
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">TV/Cable</h1>
        </div>

        <!-- Airtime Purchase Form -->
        <form action="{{ route('cabletv.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Service Provider -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Service Provider</label>
                <div id="openServiceProvider" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Provider
                </div>
                <input type="hidden" name="network" id="networkInput">
            </div>

            <!-- Package -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Package</label>
                <div id="openPackage" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Package
                </div>
                <input type="hidden" name="package" id="packageInput">
            </div>

            <!-- Subscription Type (Optional) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Subscription Type <span class="text-gray-400 text-xs">(Optional)</span></label>
                <div id="openSubscriptionType" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Subscription Type (Optional)
                </div>
                <input type="hidden" name="subscriptionTypeInput" id="subscriptionTypeInput">
            </div>

            <!-- Smartcard/IUC Number -->
            <div>
                <label for="smartcardNumber" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Smartcard/IUC Number</label>
                <input type="number" id="smartcardNumber" name="smartcardNumber" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200" required>
            </div>

            <!-- Amount -->
            <div>
            <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Amount</label>
                <div id="amountDisplay" 
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 placeholder-gray-400 
                            focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    aria-live="polite" role="textbox" tabindex="0" aria-label="Amount">
                    N0.00
                </div>
                <input type="hidden" id="amount" name="amount" value="0" required>
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phoneNumber" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Phone Number</label>
                <input type="tel" id="phoneNumber" name="phoneNumber" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200" required>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 justify-center flex items-center">
                <button type="button" id="verifySmartcardBtn"  class="w-full lg:w-72 bg-brand-green text-white font-bold py-2 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Verify Smartcard/IUC Number
                </button>
            </div>

            <div class="justify-center flex items-center">
                <button type="button" id="payNowBtn" class="w-full lg:w-72 bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-black/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modals -->
<div id="serviceProviderModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Provider</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="serviceProviderList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<div id="packageModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Package</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<div id="subscriptionTypeModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose Subscription Type</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="subscriptionTypeList" class="space-y-2"></ul>
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
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Purchase</h2>
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
        Confirm & Pay
    </button>
    
    <!-- Error Message -->
    <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<!-- Custom modal -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
    <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
    <h2 id="modalTitle" class="text-lg font-bold mb-2"></h2>
    <p id="modalMessage" class="whitespace-pre-wrap text-gray-700"></p>
  </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
  <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<style>
  /* Tailwind doesn't have spinner out of the box, this is pure CSS spinner */
  .loader {
    border-top-color: #3490dc; /* Tailwind blue-500 */
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
</style>

<script>
document.getElementById('verifySmartcardBtn').addEventListener('click', async () => {
  const network = document.getElementById('networkInput').value.trim();
  const smartcardNumber = document.getElementById('smartcardNumber').value.trim();

  if (!network) return showModal('Error', 'Please select a service provider first.', 'text-red-600');
  if (!smartcardNumber) return showModal('Error', 'Please enter your Smartcard/IUC Number.', 'text-red-600');

  // Show loading animation
  toggleLoading(true);

  try {
    const response = await fetch("{{ route('verify.smartcard') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': "{{ csrf_token() }}"
      },
      body: JSON.stringify({
        cableTV: network,
        smartCardNo: smartcardNumber
      })
    });

    const data = await response.json();

    if (response.ok && data.status) {
      showModal('Verification Successful', data.customer_name || data.message || 'Smartcard verified.', 'text-green-600');
    } else {
      showModal('Verification Failed', data.message || 'Unknown error', 'text-red-600');
    }
  } catch (error) {
    showModal('Error', 'Verification failed. Please try again.', 'text-red-600');
    console.error(error);
  } finally {
    toggleLoading(false);
  }
});

function showModal(title, message, textClass = '') {
  const modal = document.getElementById('customModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalMessage = document.getElementById('modalMessage');

  modalTitle.textContent = title;
  modalMessage.textContent = message;

  modalMessage.className = 'whitespace-pre-wrap ' + textClass;

  modal.classList.remove('hidden');
}

// Close modal on button click
document.getElementById('modalCloseBtn').addEventListener('click', () => {
  document.getElementById('customModal').classList.add('hidden');
});

// Loading toggle
function toggleLoading(show) {
  const loading = document.getElementById('loadingOverlay');
  if(show) {
    loading.classList.remove('hidden');
  } else {
    loading.classList.add('hidden');
  }
}
</script>

<script>
    const plans = @json($plans);

    const modalShow = (modal) => {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div').classList.remove('translate-y-full');
        }, 10);
    };

    const modalHide = (modal) => {
        modal.classList.add('opacity-0');
        modal.querySelector('div').classList.add('translate-y-full');
        modal.addEventListener('transitionend', () => {
            if (modal.classList.contains('opacity-0')) {
                modal.classList.add('hidden');
            }
        }, { once: true });
    };

    // Trigger modals
    document.getElementById('openServiceProvider').addEventListener('click', () => {
        const list = document.getElementById('serviceProviderList');
        list.innerHTML = '';
        Object.keys(plans).forEach(provider => {
            const btn = document.createElement('button');
            btn.textContent = provider;
            btn.className = 'block w-full text-left px-4 py-2 border-b hover:bg-gray-100';
            btn.onclick = () => {
                // The lowercase code (e.g. "dstv") is what ClubKonnect's API requires
                // as the CableTV parameter — never send the capitalized display name.
                const code = (plans[provider][0] && plans[provider][0]['ID'])
                    ? plans[provider][0]['ID']
                    : provider.toLowerCase();

                document.getElementById('openServiceProvider').textContent = provider;
                document.getElementById('networkInput').value = code;
                document.getElementById('networkInput').dataset.planKey = provider; // for local plans[] lookups only

                // Reset downstream selections since the provider changed
                document.getElementById('openPackage').textContent = 'Choose a Package';
                document.getElementById('packageInput').value = '';
                document.getElementById('packageInput').dataset.subscriptionTypes = '';
                document.getElementById('openSubscriptionType').textContent = 'Choose a Subscription Type (Optional)';
                document.getElementById('subscriptionTypeInput').value = '';
                document.getElementById('amountDisplay').textContent = 'N0.00';
                document.getElementById('amount').value = '0';

                modalHide(document.getElementById('serviceProviderModal'));
            };
            list.appendChild(btn);
        });
        modalShow(document.getElementById('serviceProviderModal'));
    });

    document.getElementById('openPackage').addEventListener('click', () => {
        // Use the display-name key (stored separately) to look up plans[],
        // since networkInput.value now holds the lowercase API code.
        const provider = document.getElementById('networkInput').dataset.planKey;
        if (!provider) return alert('Please choose a provider first.');
        const list = document.getElementById('packageList');
        list.innerHTML = '';

        plans[provider][0]['PRODUCT'].forEach(pkg => {
            const btn = document.createElement('button');
            btn.textContent = pkg.PACKAGE_NAME;
            btn.className = 'block w-full text-left px-4 py-2 border-b hover:bg-gray-100';

            btn.onclick = () => {
                document.getElementById('openPackage').textContent = pkg.PACKAGE_NAME;
                document.getElementById('packageInput').value = pkg.PACKAGE_ID;
                document.getElementById('packageInput').dataset.subscriptionTypes = JSON.stringify(pkg.SUBSCRIPTION_TYPE || []);

                const amountDisplay = document.getElementById('amountDisplay');
                const amountInput = document.getElementById('amount');
                amountDisplay.textContent = `N${parseFloat(pkg.PACKAGE_AMOUNT).toFixed(2)}`;
                amountInput.value = pkg.PACKAGE_AMOUNT;

                modalHide(document.getElementById('packageModal'));
            };
            list.appendChild(btn);
        });
        modalShow(document.getElementById('packageModal'));
    });

    document.getElementById('openSubscriptionType').addEventListener('click', () => {
        const list = document.getElementById('subscriptionTypeList');
        list.innerHTML = '';

        const defaultSubscriptionTypes = ['Prepaid', 'Postpaid'];
        const packageEl = document.getElementById('packageInput');
        let subscriptionTypes = defaultSubscriptionTypes;

        if (packageEl.value && packageEl.dataset.subscriptionTypes) {
            const packageSubscriptionTypes = JSON.parse(packageEl.dataset.subscriptionTypes || '[]');
            if (packageSubscriptionTypes.length > 0) {
                subscriptionTypes = packageSubscriptionTypes;
            }
        }

        subscriptionTypes.forEach(sub => {
            const btn = document.createElement('button');
            btn.textContent = sub.SUBSCRIPTION_TYPE || sub;
            btn.className = 'block w-full text-left px-4 py-2 border-b hover:bg-gray-100';
            btn.onclick = () => {
                document.getElementById('openSubscriptionType').textContent = sub.SUBSCRIPTION_TYPE || sub;
                document.getElementById('subscriptionTypeInput').value = sub.SUBSCRIPTION_TYPE_ID || sub;
                modalHide(document.getElementById('subscriptionTypeModal'));
            };
            list.appendChild(btn);
        });

        modalShow(document.getElementById('subscriptionTypeModal'));
    });

    // Close on click outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modalHide(modal);
        });
    });
</script>
<script>
const walletBalance = {{ $wallet->balance ?? 0 }}; // Pass user's wallet balance from controller

document.getElementById('payNowBtn').addEventListener('click', () => {
    // Gather selected details and validate
    const provider = document.getElementById('networkInput').dataset.planKey || document.getElementById('networkInput').value;
    const packageName = document.getElementById('openPackage').textContent;
    const packageId = document.getElementById('packageInput').value;
    const subscriptionType = document.getElementById('openSubscriptionType').textContent;
    const subscriptionTypeId = document.getElementById('subscriptionTypeInput').value;
    const smartcardNumber = document.getElementById('smartcardNumber').value;
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const phoneNumber = document.getElementById('phoneNumber').value;

    // Validate all required fields
    if (!provider) return showModal('Error', 'Please select a service provider.', 'text-red-600');
    if (!packageId) return showModal('Error', 'Please select a package.', 'text-red-600');
    // Subscription type is optional, so no validation required
    if (!smartcardNumber) return showModal('Error', 'Please enter your Smartcard/IUC Number.', 'text-red-600');
    if (!phoneNumber) return showModal('Error', 'Please enter your phone number.', 'text-red-600');
    if (amount <= 0) return showModal('Error', 'Invalid amount selected.', 'text-red-600');

    // Check if balance is enough
    if (walletBalance < amount) {
        showModal(
            'Insufficient Balance',
            `Your account balance (₦${walletBalance.toFixed(2)}) is not enough to pay ₦${amount.toFixed(2)}. Please fund your wallet.`,
            'text-red-600'
        );
        return;
    }

    // Show summary in modal
    const subscriptionTypeDisplay = subscriptionType === 'Choose a Subscription Type (Optional)' || !subscriptionType ? 'Not Selected' : subscriptionType;
    
    document.getElementById('pinModalSummary').innerHTML = `
      <div><strong>Provider:</strong> ${provider}</div>
      <div><strong>Package:</strong> ${packageName}</div>
      <div><strong>Subscription Type:</strong> ${subscriptionTypeDisplay}</div>
      <div><strong>Smartcard/IUC Number:</strong> ${smartcardNumber}</div>
      <div><strong>Amount:</strong> ₦${amount.toFixed(2)}</div>
      <div><strong>Phone Number:</strong> ${phoneNumber}</div>
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

// Close modal
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

// Handle PIN confirmation with loading animation and success partial
document.getElementById('confirmPinBtn').addEventListener('click', async (e) => {
    e.preventDefault(); // Prevent default form submission
    
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
    
    // Store original button text
    const originalText = confirmBtn.textContent.trim();
    
    // Show loading state
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '';
    confirmBtn.appendChild(loadingSpinner);
    loadingSpinner.classList.remove('hidden');

    // Gather all form data
    const formData = new FormData();
    formData.append('network', document.getElementById('networkInput').value);
    formData.append('package', document.getElementById('packageInput').value);
    formData.append('subscriptionTypeInput', document.getElementById('subscriptionTypeInput').value || '');
    formData.append('smartcardNumber', document.getElementById('smartcardNumber').value);
    formData.append('amount', document.getElementById('amount').value);
    formData.append('phoneNumber', document.getElementById('phoneNumber').value);
    formData.append('pin', pin);

    try {
        const response = await fetch("{{ route('cabletv.purchase') }}", {
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
            
            // Show success partial with cable TV-specific data
            await showSuccessPartial({
                title: 'Cable TV Purchase Successful!',
                message: result.message || 'Your cable TV subscription has been purchased successfully!',
                details: {
                    'Transaction Reference': result.data?.reference || 'N/A',
                    'Service Provider': document.getElementById('networkInput').value,
                    'Package': document.getElementById('openPackage').textContent,
                    'Subscription Type': document.getElementById('openSubscriptionType').textContent === 'Choose a Subscription Type (Optional)' ? 'Not Selected' : document.getElementById('openSubscriptionType').textContent,
                    'Smartcard/IUC Number': document.getElementById('smartcardNumber').value,
                    'Amount': `₦${parseFloat(document.getElementById('amount').value).toFixed(2)}`,
                    'Phone Number': document.getElementById('phoneNumber').value,
                    'New Wallet Balance': result.data?.new_balance ? `₦${parseFloat(result.data.new_balance).toFixed(2)}` : 'N/A',
                    'Date & Time': new Date().toLocaleString()
                }
            });
            
            // Reset form
            resetForm();
        } else {
            // Handle different types of errors
            let errorMessage = result.message || 'Payment failed. Please try again.';
            
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
        console.error('Payment error:', error);
        document.getElementById('pinModalError').textContent = 'Network error. Please check your connection and try again.';
        document.getElementById('pinModalError').classList.remove('hidden');
    } finally {
        // Reset button state
        confirmBtn.disabled = false;
        loadingSpinner.classList.add('hidden');
        confirmBtn.textContent = originalText;
    }
});

// Enhanced function to call Laravel partial (same as airtime and betting)
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

// Function to reset form after successful transaction
function resetForm() {
    // Reset form inputs
    document.getElementById('openServiceProvider').textContent = 'Choose a Provider';
    document.getElementById('networkInput').value = '';
    
    document.getElementById('openPackage').textContent = 'Choose a Package';
    document.getElementById('packageInput').value = '';
    
    document.getElementById('openSubscriptionType').textContent = 'Choose a Subscription Type (Optional)';
    document.getElementById('subscriptionTypeInput').value = '';
    
    document.getElementById('smartcardNumber').value = '';
    document.getElementById('phoneNumber').value = '';
    
    document.getElementById('amountDisplay').textContent = 'N0.00';
    document.getElementById('amount').value = '0';
}

// Close PIN modal when clicking outside
document.getElementById('pinModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('pinModal')) {
        document.getElementById('pinModal').classList.add('hidden');
    }
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

@keyframes fade-out {
    from { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
    to   { 
        opacity: 0; 
        transform: translateY(-10px) scale(0.95); 
    }
}

.animate-bounce-in {
    animation: bounce-in 0.6s ease-out;
}

.animate-fade-out {
    animation: fade-out 0.3s ease-in forwards;
}

/* Spinner styles */
.loader {
    border-top-color: #3490dc;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

@endsection