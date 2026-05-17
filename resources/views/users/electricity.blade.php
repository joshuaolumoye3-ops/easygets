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
                    <span class="provider-text">Choose a Provider</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <input type="hidden" id="networkInput" name="electricCompany" required>
            </div>

            <!-- Step 2: Meter Type — must come BEFORE meter number so we can pass it to verify -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-6">Meter Type</label>
                <div id="openPackage"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span class="package-text">Choose a Meter Type</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <input type="hidden" name="package" id="packageInput" required>
                {{-- meterTypeCode is what gets forwarded to the ClubKonnect verify & purchase APIs --}}
                <input type="hidden" id="meterTypeCode" name="meterTypeCode">
            </div>

            <!-- Step 3: Meter Number — verify fires only after company + meter type are both set -->
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
                <p id="meterHint" class="mt-1 text-xs text-gray-400 hidden">
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
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 relative transform scale-95 transition-transform duration-300">

        <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Electricity Purchase</h2>
        <p id="pinModalSummary" class="mb-4 text-gray-600 text-center text-sm leading-relaxed"></p>

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
            class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200">
            ✅ Confirm &amp; Purchase
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
    .loader {
        border-top-color: #3490dc;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    const walletBalance = {{ $wallet->balance ?? 0 }};
    let meterVerified   = false;
    let customerName    = '';
    let verifyTimer     = null;

    // -------------------------------------------------------------------------
    // UI helpers
    // -------------------------------------------------------------------------
    function showModal(title, message, textColor = 'text-gray-700') {
        document.getElementById('modalTitle').textContent = title;
        const el = document.getElementById('modalMessage');
        el.textContent = message;
        el.className = `whitespace-pre-wrap ${textColor}`;
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
        setTimeout(() => { if (modal.classList.contains('opacity-0')) modal.classList.add('hidden'); }, 300);
    };

    // -------------------------------------------------------------------------
    // Close handlers
    // -------------------------------------------------------------------------
    document.getElementById('modalCloseBtn').addEventListener('click', () => {
        document.getElementById('customModal').classList.add('hidden');
    });

    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = document.getElementById(e.target.closest('.close-modal').dataset.modal);
            modalHide(modal);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => { if (e.target === modal) modalHide(modal); });
    });

    // -------------------------------------------------------------------------
    // Provider data (rendered server-side — no API keys exposed here)
    // -------------------------------------------------------------------------
    const plans = @json($plans);

    const isObj = (v) => v && typeof v === 'object' && !Array.isArray(v);
    const providers = [];

    if (Array.isArray(plans)) {
        plans.forEach((item, idx) => {
            const company = Array.isArray(item) ? item[0] : item;
            if (isObj(company)) {
                providers.push({
                    key:  company.ELECTRIC_COMPANY ?? company.ID ?? String(idx),
                    name: company.NAME || company.ELECTRIC_COMPANY || `Provider ${idx + 1}`,
                    raw:  company,
                });
            }
        });
    } else if (isObj(plans)) {
        Object.entries(plans).forEach(([providerKey, item]) => {
            const company = Array.isArray(item) ? item[0] : item;
            if (isObj(company)) {
                providers.push({
                    key:  providerKey,
                    name: company.NAME || company.ELECTRIC_COMPANY || providerKey,
                    raw:  company,
                });
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helper: extract a field trying several candidate keys
    // -------------------------------------------------------------------------
    function getField(obj, candidates) {
        if (!obj) return null;
        const norm = {};
        Object.keys(obj).forEach(k => norm[k.toLowerCase()] = obj[k]);
        for (const c of candidates) {
            const v = norm[c.toLowerCase()];
            if (v !== undefined && v !== null) return v;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Reset meter verification state
    // -------------------------------------------------------------------------
    function resetMeterVerification() {
        meterVerified = false;
        customerName  = '';
        const div = document.getElementById('meterVerification');
        div.innerHTML = '';
        div.classList.add('hidden');
    }

    // -------------------------------------------------------------------------
    // Meter verification
    // Requires both electricCompany AND meterTypeCode to be set first.
    // -------------------------------------------------------------------------
    async function verifyMeter(electricCompany, meterTypeCode, meterNumber) {
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
                    electricCompany,
                    meterNo:   meterNumber,
                    meterType: meterTypeCode,   // ← now required by the API
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
                div.classList.remove('hidden');
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
                div.classList.remove('hidden');
            }
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

    // -------------------------------------------------------------------------
    // Meter input: auto-verify once prerequisites are met
    // -------------------------------------------------------------------------
    document.getElementById('meterNumber').addEventListener('input', function () {
        // Strip non-digits
        const digits = this.value.replace(/\D/g, '');
        this.value   = digits;

        const electricCompany = document.getElementById('networkInput').value.trim();
        const meterTypeCode   = document.getElementById('meterTypeCode').value.trim();
        const hint            = document.getElementById('meterHint');

        // If no provider/meter type chosen yet, show hint and bail
        if (!electricCompany || !meterTypeCode) {
            hint.classList.remove('hidden');
            resetMeterVerification();
            return;
        }
        hint.classList.add('hidden');

        // Debounced auto-verify when meter number looks complete (≥ 10 digits)
        if (verifyTimer) clearTimeout(verifyTimer);
        if (digits.length >= 10) {
            verifyTimer = setTimeout(() => verifyMeter(electricCompany, meterTypeCode, digits), 700);
        } else {
            resetMeterVerification();
        }
    });

    // -------------------------------------------------------------------------
    // Service Provider modal
    // -------------------------------------------------------------------------
    document.getElementById('openServiceProvider').addEventListener('click', () => {
        const list = document.getElementById('serviceProviderList');
        list.innerHTML = '';

        if (providers.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-gray-500 p-4 text-center';
            empty.textContent = 'No electricity providers available right now.';
            list.appendChild(empty);
            modalShow(document.getElementById('serviceProviderModal'));
            return;
        }

        providers.forEach(provider => {
            const li  = document.createElement('li');
            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.textContent = provider.name;
            btn.className = 'block w-full text-left px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors';
            btn.onclick = () => {
                document.querySelector('.provider-text').textContent = provider.name;
                document.getElementById('networkInput').value = provider.key;

                // Reset downstream selections
                document.querySelector('.package-text').textContent = 'Choose a Meter Type';
                document.getElementById('packageInput').value  = '';
                document.getElementById('meterTypeCode').value = '';
                document.getElementById('minAmount').value     = '';
                document.getElementById('maxAmount').value     = '';
                document.getElementById('amountLimits').classList.add('hidden');
                document.getElementById('meterNumber').value  = '';
                resetMeterVerification();

                modalHide(document.getElementById('serviceProviderModal'));
            };
            li.appendChild(btn);
            list.appendChild(li);
        });

        modalShow(document.getElementById('serviceProviderModal'));
    });

    // -------------------------------------------------------------------------
    // Package (Meter Type) modal
    // -------------------------------------------------------------------------
    document.getElementById('openPackage').addEventListener('click', () => {
        const providerKey = document.getElementById('networkInput').value;
        if (!providerKey) {
            return showModal('Select Provider First', 'Please choose an electricity company before selecting a meter type.', 'text-red-600');
        }

        const provider = providers.find(p => String(p.key) === String(providerKey));
        if (!provider) {
            return showModal('Error', 'Selected provider is unavailable. Please choose another.', 'text-red-600');
        }

        const products = provider.raw.PRODUCT || provider.raw.products || [];
        const list     = document.getElementById('packageList');
        list.innerHTML = '';

        if (!products.length) {
            const empty = document.createElement('li');
            empty.className    = 'text-gray-500 p-4 text-center';
            empty.textContent  = 'No meter types found for this provider.';
            list.appendChild(empty);
            modalShow(document.getElementById('packageModal'));
            return;
        }

        products.forEach(product => {
            const productType = getField(product, ['PRODUCT_TYPE','PRODUCT_TYPE_NAME','PRODUCT_NAME','PRODUCT_DESCRIPTION']) || 'Meter';
            // productId is the MeterType code sent to the API (e.g. "01", "02")
            const productId   = getField(product, ['PRODUCT_ID','PRODUCT_CODE','PRODUCT_SNO','ID']) || '';

            let minAmt = getField(product, ['MINIMUM_AMOUNT','MINIMUN_AMOUNT','MINAMOUNT','MIN_AMOUNT','MIN']);
            let maxAmt = getField(product, ['MAXIMUM_AMOUNT','MAXAMOUNT','MAX_AMOUNT','MAX']);

            minAmt = minAmt !== null ? parseFloat(String(minAmt).replace(/[^0-9.]/g, '')) : NaN;
            maxAmt = maxAmt !== null ? parseFloat(String(maxAmt).replace(/[^0-9.]/g, '')) : NaN;

            if (!minAmt || isNaN(minAmt) || minAmt <= 0) minAmt = 100;
            if (!maxAmt || isNaN(maxAmt) || maxAmt <= 0) maxAmt = 200000;

            const li  = document.createElement('li');
            const btn = document.createElement('button');
            btn.type  = 'button';
            btn.innerHTML = `
                <div class="text-left">
                    <div class="font-semibold">${productType}</div>
                    <div class="text-sm text-gray-500">Min: ₦${Math.floor(minAmt).toLocaleString()} – Max: ₦${Math.ceil(maxAmt).toLocaleString()}</div>
                </div>`;
            btn.className = 'block w-full px-4 py-3 border-b hover:bg-gray-100 rounded transition-colors';
            btn.onclick = () => {
                document.querySelector('.package-text').textContent = productType;
                document.getElementById('packageInput').value  = productId;
                document.getElementById('meterTypeCode').value = productId;  // forwarded to verify API
                document.getElementById('minAmount').value     = minAmt;
                document.getElementById('maxAmount').value     = maxAmt;

                const amountInput = document.getElementById('amount');
                amountInput.min = Math.floor(minAmt);
                amountInput.max = Math.ceil(maxAmt);

                document.getElementById('amountLimits').textContent =
                    `Min: ₦${Math.floor(minAmt).toLocaleString()} – Max: ₦${Math.ceil(maxAmt).toLocaleString()}`;
                document.getElementById('amountLimits').classList.remove('hidden');

                // If a meter number was already entered, re-verify with the new meter type
                const meterNumber    = document.getElementById('meterNumber').value.trim();
                const electricCompany = document.getElementById('networkInput').value.trim();
                resetMeterVerification();
                if (meterNumber.length >= 10 && electricCompany) {
                    verifyMeter(electricCompany, productId, meterNumber);
                }

                modalHide(document.getElementById('packageModal'));
            };
            li.appendChild(btn);
            list.appendChild(li);
        });

        modalShow(document.getElementById('packageModal'));
    });

    // -------------------------------------------------------------------------
    // Form submit → validation → PIN modal
    // -------------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        const form               = document.getElementById('electricityForm');
        const originalFormAction = form.action;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const electricCompany     = document.getElementById('networkInput').value;
            const electricCompanyName = document.querySelector('.provider-text').textContent;
            const packageId           = document.getElementById('packageInput').value;
            const packageName         = document.querySelector('.package-text').textContent;
            const meterNumber         = document.getElementById('meterNumber').value.trim();
            const amount              = parseFloat(document.getElementById('amount').value) || 0;
            const minAmount           = parseFloat(document.getElementById('minAmount').value) || 0;
            const maxAmount           = parseFloat(document.getElementById('maxAmount').value) || 0;

            if (!electricCompany || electricCompanyName === 'Choose a Provider') {
                return showModal('Error', 'Please select an electricity company.', 'text-red-600');
            }
            if (!packageId || packageName === 'Choose a Meter Type') {
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
            if (minAmount && amount < minAmount) {
                return showModal('Error', `Minimum amount for this meter type is ₦${minAmount.toLocaleString()}.`, 'text-red-600');
            }
            if (maxAmount && amount > maxAmount) {
                return showModal('Error', `Maximum amount for this meter type is ₦${maxAmount.toLocaleString()}.`, 'text-red-600');
            }
            if (walletBalance < amount) {
                return showModal(
                    'Insufficient Balance',
                    `Your balance (₦${walletBalance.toLocaleString()}) is not enough for ₦${amount.toLocaleString()}. Please fund your wallet.`,
                    'text-red-600'
                );
            }

            // Populate PIN modal summary
            document.getElementById('pinModalSummary').innerHTML = `
                <div><strong>Company:</strong> ${electricCompanyName}</div>
                <div><strong>Meter Type:</strong> ${packageName}</div>
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
                if (this.value.length === 1 && idx < arr.length - 1) arr[idx + 1].focus();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && this.value === '' && idx > 0) arr[idx - 1].focus();
            });
        });

        // PIN confirm
        document.getElementById('confirmPinBtn').addEventListener('click', async () => {
            let pin = '';
            document.querySelectorAll('.pin-input').forEach(i => pin += i.value);

            if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
                document.getElementById('pinModalError').textContent = 'Please enter your 4-digit PIN.';
                document.getElementById('pinModalError').classList.remove('hidden');
                return;
            }

            const formData = new FormData();
            formData.append('electricCompany', document.getElementById('networkInput').value);
            formData.append('package',         document.getElementById('packageInput').value);
            formData.append('meterNumber',      document.getElementById('meterNumber').value);
            formData.append('amount',           document.getElementById('amount').value);
            formData.append('minAmount',        document.getElementById('minAmount').value);
            formData.append('maxAmount',        document.getElementById('maxAmount').value);
            formData.append('pin',              pin);
            formData.append('_token',           "{{ csrf_token() }}");

            toggleLoading(true);

            try {
                const response = await fetch(originalFormAction, { method: 'POST', body: formData });
                const result   = await response.json();

                if (response.ok && result.status) {
                    document.getElementById('pinModal').classList.add('hidden');

                    const details = {
                        'Company':        document.querySelector('.provider-text').textContent,
                        'Meter Type':     document.querySelector('.package-text').textContent,
                        'Meter Number':   document.getElementById('meterNumber').value,
                        'Amount':         '₦' + parseFloat(document.getElementById('amount').value).toLocaleString(),
                        'Transaction ID': result.data?.transaction_id || 'N/A',
                        'New Balance':    '₦' + (result.data?.new_balance || 0).toLocaleString(),
                    };
                    if (result.data?.token) details['Token'] = result.data.token;
                    if (result.data?.order_id) details['Order ID'] = result.data.order_id;

                    // Try to render server-side success partial
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
                            throw new Error('Partial render failed');
                        }
                    } catch {
                        // Inline fallback
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
                    document.querySelector('.provider-text').textContent  = 'Choose a Provider';
                    document.querySelector('.package-text').textContent   = 'Choose a Meter Type';
                    document.getElementById('networkInput').value         = '';
                    document.getElementById('packageInput').value         = '';
                    document.getElementById('meterTypeCode').value        = '';
                    document.getElementById('minAmount').value            = '';
                    document.getElementById('maxAmount').value            = '';
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
            }
        });
    });
</script>
@endsection