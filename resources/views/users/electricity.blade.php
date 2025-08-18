@extends('layouts.app')
@section('title', 'Electricity Purchase')

@section('content')
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

        <!-- Electricity Purchase Form -->
        <form id="electricityForm" action="{{ route('electricity.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Service Provider -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Electricity Company</label>
                <div id="openServiceProvider"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Provider
                </div>
                <input type="hidden" id="networkInput" name="electricCompany" required>
            </div>

            <!-- Package -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Meter Type</label>
                <div id="openPackage"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer">
                    Choose a Package
                </div>
                <input type="hidden" name="package" id="packageInput" required>
            </div>

            <!-- Meter Number -->
            <div>
                <label for="meterNumber" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Meter Number</label>
                <input type="text" id="meterNumber" name="meterNumber"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required pattern="\d{10,}" title="Enter a valid meter number (at least 10 digits)">
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Amount</label>
                <input type="number" id="amount" name="amount"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required min="100">
            </div>

            <input type="hidden" id="minAmount" name="minAmount">
            <input type="hidden" id="maxAmount" name="maxAmount">

            <!-- Verify & Pay Buttons -->
            <div class="justify-center flex items-center">
                <button type="button" id="verifyMeterBtn"
                    class="w-full lg:w-72 bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-black/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Verify Meter
                </button>
            </div>
            <div class="justify-center flex items-center">
                <button type="submit"
                    class="w-full lg:w-72 bg-black text-white font-bold py-2 px-4 rounded-lg hover:bg-black/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Service Provider Modal -->
<div id="serviceProviderModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div
        class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Provider</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="serviceProviderList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- Package Modal -->
<div id="packageModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div
        class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Meter Type</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- Custom Modal -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
        <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
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

    document.getElementById('modalCloseBtn').addEventListener('click', () => {
        document.getElementById('customModal').classList.add('hidden');
    });

    // ✅ Verify Meter
    document.getElementById('verifyMeterBtn').addEventListener('click', async (e) => {
        e.preventDefault();

        const electricCompany = document.getElementById('networkInput').value.trim();
        const meterNumber = document.getElementById('meterNumber').value.trim();
        const packageId = document.getElementById('packageInput').value.trim();

        if (!electricCompany) return showModal('Error', 'Please select an electricity provider.', 'text-red-600');
        if (!packageId) return showModal('Error', 'Please choose a meter type.', 'text-red-600');
        if (!meterNumber) return showModal('Error', 'Please enter your meter number.', 'text-red-600');

        toggleLoading(true);

        try {
            const response = await fetch("{{ route('verify.meter') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    electricCompany: electricCompany,
                    meterNo: meterNumber,
                    product_id: packageId
                })
            });

            const data = await response.json();
            console.log("Verification response:", data);

            if (response.ok && data.status === true) {
                showModal(
                    'Verification Successful',
                    `Customer: ${data.customer_name}\nMeter: ${data.meter_number}`,
                    'text-green-600'
                );
            } else {
                showModal('Verification Failed', data.message || 'Invalid meter number', 'text-red-600');
            }

        } catch (error) {
            console.error("Verification error:", error);
            showModal('Error', 'Verification failed. Please try again.', 'text-red-600');
        } finally {
            toggleLoading(false);
        }
    });

    // ✅ Provider and Package Selection
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
            if (modal.classList.contains('opacity-0')) modal.classList.add('hidden');
        }, { once: true });
    };

    // Service Provider
    document.getElementById('openServiceProvider').addEventListener('click', () => {
        const list = document.getElementById('serviceProviderList');
        list.innerHTML = '';

        Object.keys(plans).forEach(providerKey => {
            const company = plans[providerKey][0];
            const btn = document.createElement('button');
            btn.textContent = company.NAME;
            btn.className = 'block w-full text-left px-4 py-2 border-b hover:bg-gray-100';
            btn.onclick = () => {
                document.getElementById('openServiceProvider').textContent = company.NAME;
                document.getElementById('networkInput').value = providerKey;
                modalHide(document.getElementById('serviceProviderModal'));
            };
            list.appendChild(btn);
        });

        modalShow(document.getElementById('serviceProviderModal'));
    });

    // Meter Type (Package)
    document.getElementById('openPackage').addEventListener('click', () => {
        const providerKey = document.getElementById('networkInput').value;
        if (!providerKey) return alert('Please choose a provider first.');

        const products = plans[providerKey][0].PRODUCT;
        const list = document.getElementById('packageList');
        list.innerHTML = '';

        products.forEach(product => {
            const btn = document.createElement('button');
            btn.textContent = `${product.PRODUCT_TYPE} (Min: ₦${product.MINIMUN_AMOUNT}, Max: ₦${product.MAXIMUM_AMOUNT})`;
            btn.className = 'block w-full text-left px-4 py-2 border-b hover:bg-gray-100';
            btn.onclick = () => {
                document.getElementById('openPackage').textContent = product.PRODUCT_TYPE;
                document.getElementById('packageInput').value = product.PRODUCT_ID;
                document.getElementById('minAmount').value = product.MINIMUN_AMOUNT;
                document.getElementById('maxAmount').value = product.MAXIMUM_AMOUNT;

                // update amount input limits
                const amountInput = document.getElementById('amount');
                amountInput.min = product.MINIMUN_AMOUNT;
                amountInput.max = product.MAXIMUM_AMOUNT;

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
</script>
@endsection
