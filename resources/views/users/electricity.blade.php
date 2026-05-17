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

        <!-- Success/Error Message Container -->
        <div id="messageContainer" class="mb-6 hidden"></div>

        <!-- Electricity Purchase Form -->
        <form id="electricityForm" action="{{ route('electricity.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Step 1: Service Provider -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Electricity Company</label>
                <div id="openServiceProvider"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span class="provider-text text-gray-400">Choose a Provider</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <!-- Holds the numeric company code e.g. "07" -->
                <input type="hidden" id="networkInput" name="electricCompany" required>
            </div>

            <!-- Step 2: Meter Type — must come BEFORE meter number -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Meter Type</label>
                <div id="openPackage"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span class="package-text text-gray-400">Choose a Meter Type</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <!-- Holds the product/meter-type code e.g. "01" -->
                <input type="hidden" name="package" id="packageInput" required>
            </div>

            <!-- Step 3: Meter Number -->
            <div>
                <label for="meterNumber" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">
                    Meter Number
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="meterNumber"
                        name="meterNumber"
                        inputmode="numeric"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                        placeholder="Enter meter number"
                        autocomplete="off"
                    >
                </div>
                <p id="meterHint" class="mt-1 text-xs text-amber-600 hidden">
                    Please select a provider and meter type first.
                </p>
                <div id="meterVerification" class="mt-2 text-sm hidden"></div>
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Amount (₦)</label>
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
                    class="w-full lg:w-72 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== Service Provider Modal ===================== -->
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

<!-- ===================== Package (Meter Type) Modal ===================== -->
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

<!-- ===================== PIN Confirmation Modal ===================== -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 relative">

        <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Electricity Purchase</h2>
        <p id="pinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed bg-gray-50 p-3 rounded-lg"></p>

        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition" required>
            </div>
        </div>

        <button id="confirmPinBtn" type="button"
            class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirm &amp; Purchase
        </button>

        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- ===================== Generic Info Modal ===================== -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full mx-4 relative">
        <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-xl">&times;</button>
        <h2 id="modalTitle" class="text-lg font-bold mb-2"></h2>
        <p id="modalMessage" class="whitespace-pre-wrap text-gray-700"></p>
    </div>
</div>

<!-- ===================== Loading Overlay ===================== -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<style>
    .loader { border-top-color: #0d9488; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
// ============================================================================
// STATE
// ============================================================================
const walletBalance = {{ $wallet->balance ?? 0 }};

/**
 * Normalized plans from backend — each item is:
 *   { code: "07", name: "Ibadan Electric", products: [{ id: "01", type: "Prepaid", min: 1000, max: 200000 }] }
 *
 * "code" is the numeric ElectricCompany value the ClubKonnect API requires.
 */
const plans = @json($plans);

let selectedProvider  = null;   // { code, name, products[] }
let selectedProduct   = null;   // { id, type, min, max }
let meterVerified     = false;
let customerName      = '';
let verifyTimer       = null;

// ============================================================================
// UI HELPERS
// ============================================================================
function showModal(title, message, textColor = 'text-gray-700') {
    document.getElementById('modalTitle').textContent    = title;
    const el = document.getElementById('modalMessage');
    el.textContent = message;
    el.className   = `whitespace-pre-wrap ${textColor}`;
    document.getElementById('customModal').classList.remove('hidden');
}

function toggleLoading(show) {
    document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
}

function showMessage(html, type = 'success') {
    const container = document.getElementById('messageContainer');
    container.innerHTML = html;
    container.classList.remove('hidden');
    if (type === 'success') setTimeout(() => container.classList.add('hidden'), 12000);
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const slideOpen = (modal) => {
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.rounded-t-2xl').classList.remove('translate-y-full');
    }, 10);
};

const slideClose = (modal) => {
    modal.classList.add('opacity-0');
    modal.querySelector('.rounded-t-2xl').classList.add('translate-y-full');
    setTimeout(() => { if (modal.classList.contains('opacity-0')) modal.classList.add('hidden'); }, 300);
};

// ============================================================================
// CLOSE HANDLERS
// ============================================================================
document.getElementById('modalCloseBtn').addEventListener('click', () =>
    document.getElementById('customModal').classList.add('hidden'));

document.getElementById('pinModalCloseBtn').addEventListener('click', () =>
    document.getElementById('pinModal').classList.add('hidden'));

document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = document.getElementById(btn.dataset.modal);
        if (modal) slideClose(modal);
    });
});

document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => { if (e.target === modal) slideClose(modal); });
});

// ============================================================================
// RESET METER VERIFICATION STATE
// ============================================================================
function resetMeterVerification() {
    meterVerified = false;
    customerName  = '';
    const div = document.getElementById('meterVerification');
    div.innerHTML = '';
    div.classList.add('hidden');
}

// ============================================================================
// METER VERIFICATION (backend route → ClubKonnect API)
// Sends: electricCompany (numeric code), meterNo, meterType
// ============================================================================
async function verifyMeter(electricCompanyCode, meterTypeCode, meterNumber) {
    toggleLoading(true);
    resetMeterVerification();

    try {
        const response = await fetch("{{ route('verify.meter') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
            },
            body: JSON.stringify({
                electricCompany: electricCompanyCode,  // numeric e.g. "07"
                meterNo:         meterNumber,
                meterType:       meterTypeCode,        // e.g. "01"
            }),
        });

        const data = await response.json();
        const div  = document.getElementById('meterVerification');

        if (response.ok && data.status) {
            meterVerified = true;
            customerName  = data.customer_name || '';
            div.innerHTML = `
                <span class="text-green-600 flex items-center gap-1">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Verified: <strong>${customerName}</strong>
                </span>`;
        } else {
            meterVerified = false;
            customerName  = '';
            div.innerHTML = `
                <span class="text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    ${data.message || 'Verification failed. Please check the meter number.'}
                </span>`;
        }
        div.classList.remove('hidden');

    } catch (err) {
        console.error('Meter verification error:', err);
        const div = document.getElementById('meterVerification');
        div.innerHTML = `<span class="text-red-600">Verification failed. Please try again.</span>`;
        div.classList.remove('hidden');
        meterVerified = false;
    } finally {
        toggleLoading(false);
    }
}

// ============================================================================
// METER NUMBER INPUT — debounced auto-verify
// ============================================================================
document.getElementById('meterNumber').addEventListener('input', function () {
    const digits = this.value.replace(/\D/g, '');
    this.value   = digits;

    const hint = document.getElementById('meterHint');

    if (!selectedProvider || !selectedProduct) {
        hint.classList.remove('hidden');
        resetMeterVerification();
        return;
    }
    hint.classList.add('hidden');

    if (verifyTimer) clearTimeout(verifyTimer);

    if (digits.length >= 10) {
        verifyTimer = setTimeout(() =>
            verifyMeter(selectedProvider.code, selectedProduct.id, digits), 700);
    } else {
        resetMeterVerification();
    }
});

// ============================================================================
// SERVICE PROVIDER MODAL
// ============================================================================
document.getElementById('openServiceProvider').addEventListener('click', () => {
    const list = document.getElementById('serviceProviderList');
    list.innerHTML = '';

    if (!plans || plans.length === 0) {
        list.innerHTML = '<li class="text-gray-500 p-4 text-center">No electricity providers available right now.</li>';
        slideOpen(document.getElementById('serviceProviderModal'));
        return;
    }

    plans.forEach(plan => {
        const li  = document.createElement('li');
        const btn = document.createElement('button');
        btn.type      = 'button';
        btn.textContent = plan.name;
        btn.className = 'block w-full text-left px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors font-medium';
        btn.onclick = () => {
            selectedProvider = plan;
            selectedProduct  = null;

            document.querySelector('.provider-text').textContent = plan.name;
            document.querySelector('.provider-text').classList.remove('text-gray-400');
            document.getElementById('networkInput').value = plan.code;  // e.g. "07"

            // Reset downstream
            document.querySelector('.package-text').textContent = 'Choose a Meter Type';
            document.querySelector('.package-text').classList.add('text-gray-400');
            document.getElementById('packageInput').value  = '';
            document.getElementById('minAmount').value     = '';
            document.getElementById('maxAmount').value     = '';
            document.getElementById('amountLimits').classList.add('hidden');
            document.getElementById('meterNumber').value  = '';
            resetMeterVerification();

            slideClose(document.getElementById('serviceProviderModal'));
        };
        li.appendChild(btn);
        list.appendChild(li);
    });

    slideOpen(document.getElementById('serviceProviderModal'));
});

// ============================================================================
// PACKAGE (METER TYPE) MODAL
// ============================================================================
document.getElementById('openPackage').addEventListener('click', () => {
    if (!selectedProvider) {
        return showModal('Select Provider First', 'Please choose an electricity company before selecting a meter type.', 'text-red-600');
    }

    const list = document.getElementById('packageList');
    list.innerHTML = '';

    if (!selectedProvider.products || selectedProvider.products.length === 0) {
        list.innerHTML = '<li class="text-gray-500 p-4 text-center">No meter types found for this provider.</li>';
        slideOpen(document.getElementById('packageModal'));
        return;
    }

    selectedProvider.products.forEach(product => {
        const li  = document.createElement('li');
        const btn = document.createElement('button');
        btn.type  = 'button';
        btn.innerHTML = `
            <div class="text-left">
                <div class="font-semibold">${product.type}</div>
                <div class="text-sm text-gray-500">
                    Min: ₦${Math.floor(product.min).toLocaleString()} – Max: ₦${Math.ceil(product.max).toLocaleString()}
                </div>
            </div>`;
        btn.className = 'block w-full px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors';
        btn.onclick = () => {
            selectedProduct = product;

            document.querySelector('.package-text').textContent = product.type;
            document.querySelector('.package-text').classList.remove('text-gray-400');
            document.getElementById('packageInput').value = product.id;  // e.g. "01"

            const amountInput = document.getElementById('amount');
            amountInput.min   = Math.floor(product.min);
            amountInput.max   = Math.ceil(product.max);
            document.getElementById('minAmount').value = product.min;
            document.getElementById('maxAmount').value = product.max;

            document.getElementById('amountLimits').textContent =
                `Min: ₦${Math.floor(product.min).toLocaleString()} – Max: ₦${Math.ceil(product.max).toLocaleString()}`;
            document.getElementById('amountLimits').classList.remove('hidden');

            // Re-verify meter if already entered
            const meterNumber = document.getElementById('meterNumber').value.trim();
            resetMeterVerification();
            if (meterNumber.length >= 10 && selectedProvider) {
                verifyMeter(selectedProvider.code, product.id, meterNumber);
            }

            slideClose(document.getElementById('packageModal'));
        };
        li.appendChild(btn);
        list.appendChild(li);
    });

    slideOpen(document.getElementById('packageModal'));
});

// ============================================================================
// FORM SUBMIT → VALIDATE → PIN MODAL
// ============================================================================
document.addEventListener('DOMContentLoaded', function () {
    const form               = document.getElementById('electricityForm');
    const originalFormAction = form.action;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const meterNumber = document.getElementById('meterNumber').value.trim();
        const amount      = parseFloat(document.getElementById('amount').value) || 0;

        if (!selectedProvider) {
            return showModal('Error', 'Please select an electricity company.', 'text-red-600');
        }
        if (!selectedProduct) {
            return showModal('Error', 'Please select a meter type.', 'text-red-600');
        }
        if (!meterNumber) {
            return showModal('Error', 'Please enter your meter number.', 'text-red-600');
        }
        if (!meterVerified) {
            return showModal('Meter Not Verified', 'Please wait for meter verification to complete before purchasing.', 'text-yellow-600');
        }
        if (!amount || amount < 100) {
            return showModal('Error', 'Please enter a valid amount (minimum ₦100).', 'text-red-600');
        }
        if (selectedProduct.min && amount < selectedProduct.min) {
            return showModal('Error', `Minimum amount for this meter type is ₦${selectedProduct.min.toLocaleString()}.`, 'text-red-600');
        }
        if (selectedProduct.max && amount > selectedProduct.max) {
            return showModal('Error', `Maximum amount for this meter type is ₦${selectedProduct.max.toLocaleString()}.`, 'text-red-600');
        }
        if (walletBalance < amount) {
            return showModal(
                'Insufficient Balance',
                `Your balance (₦${walletBalance.toLocaleString()}) is not enough for ₦${amount.toLocaleString()}. Please fund your wallet.`,
                'text-red-600'
            );
        }

        document.getElementById('pinModalSummary').innerHTML = `
            <div><strong>Company:</strong> ${selectedProvider.name}</div>
            <div><strong>Meter Type:</strong> ${selectedProduct.type}</div>
            <div><strong>Meter Number:</strong> ${meterNumber}</div>
            ${customerName ? `<div><strong>Customer:</strong> ${customerName}</div>` : ''}
            <div><strong>Amount:</strong> ₦${amount.toLocaleString()}</div>
            <div class="mt-2 font-semibold text-green-700"><strong>Wallet Balance:</strong> ₦${walletBalance.toLocaleString()}</div>`;

        document.querySelectorAll('.pin-input').forEach(i => i.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    });

    // PIN box — auto-advance and backspace
    document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function () {
            if (!/^\d$/.test(this.value)) { this.value = ''; return; }
            if (idx < arr.length - 1) arr[idx + 1].focus();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value === '' && idx > 0) arr[idx - 1].focus();
        });
    });

    // PIN confirm & submit
    document.getElementById('confirmPinBtn').addEventListener('click', async () => {
        let pin = '';
        document.querySelectorAll('.pin-input').forEach(i => pin += i.value);

        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            const errEl = document.getElementById('pinModalError');
            errEl.textContent = 'Please enter your 4-digit PIN.';
            errEl.classList.remove('hidden');
            return;
        }

        const formData = new FormData();
        formData.append('electricCompany', selectedProvider.code);   // numeric "07"
        formData.append('package',         selectedProduct.id);       // "01"
        formData.append('meterNumber',     document.getElementById('meterNumber').value);
        formData.append('amount',          document.getElementById('amount').value);
        formData.append('minAmount',       selectedProduct.min);
        formData.append('maxAmount',       selectedProduct.max);
        formData.append('pin',             pin);
        formData.append('_token',          "{{ csrf_token() }}");

        toggleLoading(true);
        document.getElementById('confirmPinBtn').disabled = true;

        try {
            const response = await fetch(originalFormAction, { method: 'POST', body: formData });
            const result   = await response.json();

            if (response.ok && result.status) {
                document.getElementById('pinModal').classList.add('hidden');

                const details = {
                    'Company':        selectedProvider.name,
                    'Meter Type':     selectedProduct.type,
                    'Meter Number':   document.getElementById('meterNumber').value,
                    'Amount':         '₦' + parseFloat(document.getElementById('amount').value).toLocaleString(),
                    'Transaction ID': result.data?.transaction_id || 'N/A',
                    'New Balance':    '₦' + (result.data?.new_balance || 0).toLocaleString(),
                };
                if (result.data?.token)    details['Token']    = result.data.token;
                if (result.data?.order_id) details['Order ID'] = result.data.order_id;

                // Try server-side success partial
                try {
                    const successResp = await fetch("{{ route('electricity.render-success') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        },
                        body: JSON.stringify({
                            title:   '✅ Electricity Purchase Successful!',
                            message: result.message || 'Your purchase was completed successfully.',
                            details,
                        }),
                    });
                    if (successResp.ok) {
                        showMessage(await successResp.text(), 'success');
                    } else {
                        throw new Error();
                    }
                } catch {
                    const tokenRow = result.data?.token
                        ? `<div class="mt-2 p-2 bg-white rounded border text-sm"><strong>Token:</strong> ${result.data.token}</div>`
                        : '';
                    showMessage(`
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-green-800">✅ Electricity Purchase Successful!</h3>
                            </div>
                            <p class="mt-2 text-green-700">${result.message || 'Your purchase was completed successfully.'}</p>
                            ${tokenRow}
                        </div>`, 'success');
                }

                // Reset form
                form.reset();
                selectedProvider = null;
                selectedProduct  = null;
                document.querySelector('.provider-text').textContent = 'Choose a Provider';
                document.querySelector('.provider-text').classList.add('text-gray-400');
                document.querySelector('.package-text').textContent  = 'Choose a Meter Type';
                document.querySelector('.package-text').classList.add('text-gray-400');
                document.getElementById('networkInput').value = '';
                document.getElementById('packageInput').value = '';
                document.getElementById('minAmount').value    = '';
                document.getElementById('maxAmount').value    = '';
                document.getElementById('amountLimits').classList.add('hidden');
                resetMeterVerification();

            } else {
                const errEl = document.getElementById('pinModalError');
                errEl.textContent = result.message || 'Purchase failed. Please try again.';
                errEl.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Purchase error:', err);
            const errEl = document.getElementById('pinModalError');
            errEl.textContent = 'Purchase failed. Please try again.';
            errEl.classList.remove('hidden');
        } finally {
            toggleLoading(false);
            document.getElementById('confirmPinBtn').disabled = false;
        }
    });
});
</script>
@endsection