@extends('layouts.app')

@section('title', 'Airtime Purchase')

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

            <!-- Subscription Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Subscription Type</label>
                <div id="openSubscriptionType" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Subscription Type
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
                <button type="submit" class="w-full lg:w-72 bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-black/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
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
            <h3 class="text-lg font-semibold text-white text-center">Choose a Provider</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<div id="subscriptionTypeModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Provider</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="subscriptionTypeList" class="space-y-2"></ul>
        </div>
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
      showModal('Verification Successful', data.status, 'text-green-600');
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
                document.getElementById('openServiceProvider').textContent = provider;
                document.getElementById('networkInput').value = provider;
                modalHide(document.getElementById('serviceProviderModal'));
            };
            list.appendChild(btn);
        });
        modalShow(document.getElementById('serviceProviderModal'));
    });

    document.getElementById('openPackage').addEventListener('click', () => {
        const provider = document.getElementById('networkInput').value;
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
                
                // Update amount display and hidden input here:
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
        const packageEl = document.getElementById('packageInput');
        if (!packageEl.value) return alert('Please choose a package first.');
        const subscriptionTypes = JSON.parse(packageEl.dataset.subscriptionTypes || '[]');
        const list = document.getElementById('subscriptionTypeList');
        list.innerHTML = '';
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
@endsection
