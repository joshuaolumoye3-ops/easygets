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

        <!-- Alert container -->
        <div id="alertContainer" class="mb-4 hidden"></div>

        <!-- Betting Form -->
        <form id="bettingForm" action="{{ route('fund_bet') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Betting Company -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Betting Company</label>
                <div id="openBettingModal"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-700 cursor-pointer flex justify-between items-center hover:bg-gray-100 transition-colors">
                    <span id="companyDisplayText" class="text-gray-400">Choose a Betting Company</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                <input type="hidden" id="betting_company" name="betting_company" required>
            </div>

            <!-- Customer ID -->
            <div>
                <label for="customer_id" class="block text-sm font-semibold text-gray-700 mb-2">Customer ID</label>
                <input type="text" id="customer_id" name="customer_id"
                    placeholder="Enter your betting account ID"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required autocomplete="off">

                <!-- Verification status -->
                <div id="verifyStatus" class="mt-2 text-sm hidden flex items-center gap-1.5"></div>
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount (₦)</label>
                <input type="number" id="amount" name="amount"
                    placeholder="Enter amount" min="100" max="50000"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-brand-green focus:border-brand-green outline-none transition duration-200"
                    required>
                <p id="amountLimits" class="mt-1 text-xs text-gray-500">Min: ₦100 | Max: ₦50,000</p>
            </div>

            <!-- Submit -->
            <div class="pt-6 flex justify-center">
                <button type="submit"
                    class="w-full lg:w-72 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Fund Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== Betting Company Modal ===================== -->
<div id="bettingModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full"
        style="height: 70vh; max-height: 520px;">
        <div class="bg-brand-green px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">Choose Betting Company</h3>
                <button class="close-modal text-white hover:text-gray-200" data-modal="bettingModal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Search inside modal -->
            <div class="mt-3">
                <input type="text" id="companySearch" placeholder="Search company..."
                    class="w-full px-3 py-2 rounded-full text-sm bg-white/20 text-white placeholder-white/70 border border-white/30 outline-none focus:bg-white/30 transition">
            </div>
        </div>
        <div class="p-3 h-full overflow-y-auto pb-20">
            <ul id="bettingCompaniesList" class="space-y-1"></ul>
            <p id="noResultsMsg" class="text-gray-400 text-sm text-center py-6 hidden">No companies match your search.</p>
        </div>
    </div>
</div>

<!-- ===================== PIN Confirmation Modal ===================== -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 relative">
        <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Betting Fund</h2>
        <div id="pinModalSummary" class="mb-4 text-gray-600 text-sm leading-relaxed bg-gray-50 p-3 rounded-lg space-y-1"></div>

        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green outline-none transition">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green outline-none transition">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green outline-none transition">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-brand-green outline-none transition">
            </div>
        </div>

        <button id="confirmPinBtn" type="button"
            class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 transition duration-200 flex items-center justify-center gap-2">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirm &amp; Fund Account
        </button>

        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- ===================== Loading Overlay ===================== -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<style>
    .loader { border-top-color: #0d9488; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .company-logo {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 13px; flex-shrink: 0;
        color: #fff; letter-spacing: 0.5px;
    }
</style>

<script>
// ============================================================================
// STATE
// ============================================================================
const walletBalance = {{ $wallet->balance ?? 0 }};

// Normalize companies from PHP — always an array of { code, name, min, max }
const bettingCompanies = (function () {
    const raw = @json($bettingCompanies ?? []);
    if (!Array.isArray(raw)) return [];
    return raw.filter(c => c && (c.code || c.PRODUCT_CODE)).map(c => ({
        code: c.code || c.PRODUCT_CODE || c.BETTINGCOMPANY || '',
        name: c.name || c.NAME || c.DESCRIPTION || c.code || '',
        min:  parseInt(c.min  || c.MINAMOUNT  || 100),
        max:  parseInt(c.max  || c.MAXAMOUNT  || 50000),
    }));
})();

let selectedCompany   = null;
let customerVerified  = false;
let customerName      = '';
let verifyTimer       = null;

// Brand colors for company logos — cycles through them
const logoColors = [
    '#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444',
    '#06b6d4','#ec4899','#84cc16','#f97316','#6366f1',
    '#14b8a6','#a855f7','#eab308','#0ea5e9','#d946ef',
];

// ============================================================================
// HELPERS
// ============================================================================
function toggleLoading(show) {
    document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
}

function showAlert(message, type = 'error') {
    const container = document.getElementById('alertContainer');
    const colors = type === 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-green-50 border-green-200 text-green-700';
    container.innerHTML = `<div class="border rounded-lg p-3 text-sm ${colors}">${message}</div>`;
    container.classList.remove('hidden');
    if (type !== 'error') setTimeout(() => container.classList.add('hidden'), 8000);
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideAlert() {
    document.getElementById('alertContainer').classList.add('hidden');
}

const slideOpen = (modal) => {
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.rounded-t-2xl')?.classList.remove('translate-y-full');
    }, 10);
};

const slideClose = (modal) => {
    modal.classList.add('opacity-0');
    modal.querySelector('.rounded-t-2xl')?.classList.add('translate-y-full');
    setTimeout(() => { if (modal.classList.contains('opacity-0')) modal.classList.add('hidden'); }, 300);
};

// ============================================================================
// BUILD COMPANIES LIST
// ============================================================================
function buildCompaniesList(filter = '') {
    const list   = document.getElementById('bettingCompaniesList');
    const noMsg  = document.getElementById('noResultsMsg');
    const search = filter.toLowerCase();

    const filtered = bettingCompanies.filter(c =>
        c.name.toLowerCase().includes(search) || c.code.toLowerCase().includes(search)
    );

    if (filtered.length === 0) {
        list.innerHTML = '';
        noMsg.classList.remove('hidden');
        return;
    }

    noMsg.classList.add('hidden');
    list.innerHTML = filtered.map((company, i) => {
        const color    = logoColors[bettingCompanies.indexOf(company) % logoColors.length];
        const initials = company.name.replace(/[^a-zA-Z0-9 ]/g, '').split(' ')
                            .slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '??';

        return `
        <li>
            <button type="button"
                class="company-item w-full flex items-center gap-3 px-3 py-3 rounded-xl hover:bg-gray-50 active:bg-gray-100 transition-colors text-left border border-transparent hover:border-gray-200"
                data-code="${company.code}"
                data-name="${company.name}"
                data-min="${company.min}"
                data-max="${company.max}">
                <div class="company-logo" style="background: ${color};">${initials}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-800 text-sm truncate">${company.name}</div>
                    <div class="text-xs text-gray-400 mt-0.5">Min: ₦${company.min.toLocaleString()} · Max: ₦${company.max.toLocaleString()}</div>
                </div>
                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </li>`;
    }).join('');

    // Attach click handlers
    list.querySelectorAll('.company-item').forEach(btn => {
        btn.addEventListener('click', function () {
            selectedCompany = {
                code: this.dataset.code,
                name: this.dataset.name,
                min:  parseInt(this.dataset.min),
                max:  parseInt(this.dataset.max),
            };

            document.getElementById('companyDisplayText').textContent = selectedCompany.name;
            document.getElementById('companyDisplayText').classList.remove('text-gray-400');
            document.getElementById('betting_company').value = selectedCompany.code;

            // Update amount limits
            document.getElementById('amountLimits').textContent =
                `Min: ₦${selectedCompany.min.toLocaleString()} · Max: ₦${selectedCompany.max.toLocaleString()}`;
            document.getElementById('amount').min = selectedCompany.min;
            document.getElementById('amount').max = selectedCompany.max;

            // Reset verification when company changes
            resetVerification();

            // Re-verify if customer_id already has a value
            const customerId = document.getElementById('customer_id').value.trim();
            if (customerId.length >= 3) {
                scheduleVerify(customerId);
            }

            slideClose(document.getElementById('bettingModal'));
        });
    });
}

// ============================================================================
// CUSTOMER VERIFICATION
// ============================================================================
function resetVerification() {
    customerVerified = false;
    customerName     = '';
    const el = document.getElementById('verifyStatus');
    el.innerHTML = '';
    el.classList.add('hidden');
}

function setVerifyStatus(type, message) {
    const el = document.getElementById('verifyStatus');
    el.classList.remove('hidden');

    if (type === 'loading') {
        el.innerHTML = `
            <svg class="animate-spin h-4 w-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <span class="text-gray-400">Verifying customer...</span>`;
    } else if (type === 'success') {
        el.innerHTML = `
            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-green-600 font-medium">${message}</span>`;
    } else {
        el.innerHTML = `
            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="text-red-600">${message}</span>`;
    }
}

async function verifyCustomer(companyCode, customerId) {
    setVerifyStatus('loading');
    customerVerified = false;
    customerName     = '';

    try {
        const response = await fetch("{{ route('betting.verify-customer') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json',
            },
            body: JSON.stringify({ betting_company: companyCode, customer_id: customerId }),
        });

        const result = await response.json();

        if (response.ok && result.status && result.data?.customer_name) {
            customerVerified = true;
            customerName     = result.data.customer_name;
            setVerifyStatus('success', `Verified: ${customerName}`);
        } else {
            setVerifyStatus('error', result.message || 'Invalid Customer ID');
        }
    } catch (err) {
        console.error('Verification error:', err);
        setVerifyStatus('error', 'Verification failed. Please try again.');
    }
}

function scheduleVerify(customerId) {
    if (verifyTimer) clearTimeout(verifyTimer);
    verifyTimer = setTimeout(() => {
        if (selectedCompany && customerId.length >= 3) {
            verifyCustomer(selectedCompany.code, customerId);
        }
    }, 700);
}

// ============================================================================
// MODAL CONTROLS
// ============================================================================
document.getElementById('openBettingModal').addEventListener('click', () => {
    buildCompaniesList();
    document.getElementById('companySearch').value = '';
    slideOpen(document.getElementById('bettingModal'));
});

document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = document.getElementById(btn.dataset.modal);
        if (modal) slideClose(modal);
    });
});

document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', e => { if (e.target === modal) slideClose(modal); });
});

document.getElementById('companySearch').addEventListener('input', function () {
    buildCompaniesList(this.value);
});

// ============================================================================
// CUSTOMER ID INPUT — debounced auto-verify
// ============================================================================
document.getElementById('customer_id').addEventListener('input', function () {
    const val = this.value.trim();
    resetVerification();

    if (!selectedCompany) {
        if (val.length > 0) {
            setVerifyStatus('error', 'Please select a betting company first.');
        }
        return;
    }

    if (val.length >= 3) {
        scheduleVerify(val);
    }
});

// ============================================================================
// FORM SUBMIT
// ============================================================================
document.addEventListener('DOMContentLoaded', function () {
    const form               = document.getElementById('electricityForm') || document.getElementById('bettingForm');
    const originalFormAction = "{{ route('fund_bet') }}";

    document.getElementById('bettingForm').addEventListener('submit', function (e) {
        e.preventDefault();
        hideAlert();

        const customerId = document.getElementById('customer_id').value.trim();
        const amount     = parseFloat(document.getElementById('amount').value) || 0;

        if (!selectedCompany) return showAlert('Please select a betting company.');
        if (!customerId)       return showAlert('Please enter your customer ID.');
        if (!customerVerified) return showAlert('Please wait for customer ID verification to complete.');
        if (amount < 100)      return showAlert('Minimum amount is ₦100.');
        if (selectedCompany.max && amount > selectedCompany.max)
            return showAlert(`Maximum amount for ${selectedCompany.name} is ₦${selectedCompany.max.toLocaleString()}.`);
        if (selectedCompany.min && amount < selectedCompany.min)
            return showAlert(`Minimum amount for ${selectedCompany.name} is ₦${selectedCompany.min.toLocaleString()}.`);
        if (walletBalance < amount)
            return showAlert(`Insufficient balance. Your wallet has ₦${walletBalance.toLocaleString()}.`);

        // Populate PIN modal summary
        document.getElementById('pinModalSummary').innerHTML = `
            <div class="flex justify-between"><span class="text-gray-500">Company</span><span class="font-medium">${selectedCompany.name}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Customer</span><span class="font-medium">${customerName || customerId}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="font-medium">₦${amount.toLocaleString()}</span></div>
            <div class="flex justify-between pt-2 border-t mt-1"><span class="font-semibold text-green-700">Wallet Balance</span><span class="font-semibold text-green-700">₦${walletBalance.toLocaleString()}</span></div>`;

        document.querySelectorAll('.pin-input').forEach(i => i.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    });

    // PIN close
    document.getElementById('pinModalCloseBtn').addEventListener('click', () =>
        document.getElementById('pinModal').classList.add('hidden'));

    document.getElementById('pinModal').addEventListener('click', e => {
        if (e.target === document.getElementById('pinModal'))
            document.getElementById('pinModal').classList.add('hidden');
    });

    // PIN input auto-advance
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
    document.getElementById('confirmPinBtn').addEventListener('click', async function () {
        let pin = '';
        document.querySelectorAll('.pin-input').forEach(i => pin += i.value);

        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            const errEl = document.getElementById('pinModalError');
            errEl.textContent = 'Please enter your 4-digit PIN.';
            errEl.classList.remove('hidden');
            return;
        }

        const formData = new FormData();
        formData.append('betting_company', selectedCompany.code);
        formData.append('customer_id',     document.getElementById('customer_id').value.trim());
        formData.append('amount',          document.getElementById('amount').value);
        formData.append('pin',             pin);
        formData.append('_token',          "{{ csrf_token() }}");

        toggleLoading(true);
        this.disabled = true;

        try {
            const response = await fetch(originalFormAction, { method: 'POST', body: formData });
            const result   = await response.json();

            if ((response.ok && result.status) || result.success) {
                document.getElementById('pinModal').classList.add('hidden');

                // Show success
                const details = {
                    'Betting Company': selectedCompany.name,
                    'Customer ID':     document.getElementById('customer_id').value.trim(),
                    'Amount':          '₦' + parseFloat(document.getElementById('amount').value).toLocaleString(),
                    'Order ID':        result.data?.order_id || 'N/A',
                    'Status':          (result.data?.transaction_status || 'pending').toUpperCase(),
                    'New Balance':     result.data?.new_balance ? '₦' + parseFloat(result.data.new_balance).toLocaleString() : 'N/A',
                };

                showAlert(
                    `<strong>✅ ${result.title || 'Funding Successful'}!</strong><br>${result.message || 'Your betting account has been funded.'}<br><br>` +
                    Object.entries(details).map(([k, v]) => `<span class="text-gray-500">${k}:</span> <strong>${v}</strong>`).join('<br>'),
                    'success'
                );

                // Reset form
                document.getElementById('bettingForm').reset();
                selectedCompany  = null;
                customerVerified = false;
                customerName     = '';
                document.getElementById('companyDisplayText').textContent = 'Choose a Betting Company';
                document.getElementById('companyDisplayText').classList.add('text-gray-400');
                document.getElementById('betting_company').value = '';
                document.getElementById('verifyStatus').classList.add('hidden');
                document.getElementById('amountLimits').textContent = 'Min: ₦100 | Max: ₦50,000';

            } else {
                const errEl = document.getElementById('pinModalError');
                errEl.textContent = result.message || 'Transaction failed. Please try again.';
                errEl.classList.remove('hidden');
            }
        } catch (err) {
            console.error('Fund error:', err);
            const errEl = document.getElementById('pinModalError');
            errEl.textContent = 'Network error. Please check your connection and try again.';
            errEl.classList.remove('hidden');
        } finally {
            toggleLoading(false);
            this.disabled = false;
        }
    });
});
</script>
@endsection