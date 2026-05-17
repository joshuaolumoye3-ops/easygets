@extends('layouts.app')

@section('title', 'JAMB e-PIN Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="{{ route('dashboard') }}" class="absolute left-0 text-gray-600 hover:text-gray-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl pt-7 font-bold text-gray-800">JAMB e-PIN Purchase</h1>
                <p class="block text-sm text-center font-semibold text-gray-700 mb-2">Secure &amp; Instant</p>
            </div>
        </div>

        <!-- JAMB e-PIN Purchase Form -->
        <form id="jambForm" class="space-y-6">
            @csrf

            <!-- Exam Type Selection -->
            <div>
                <label for="exam_type" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    Select Exam Type
                </label>
                <select
                    name="exam_type"
                    id="exam_type"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    required
                >
                    <option value="">-- Choose Exam Type --</option>
                    <option value="utme-mock">UTME e-PIN (with Mock) — ₦3,500</option>
                    <option value="utme-no-mock">UTME e-PIN (without Mock) — ₦3,000</option>
                    <option value="de">Direct Entry (DE) e-PIN — ₦2,500</option>
                </select>
                <small class="text-gray-500 text-xs mt-1">Choose the appropriate exam type for your registration</small>
            </div>

            <!-- Phone Number Input -->
            <div>
                <label for="phone_number" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    Phone Number (Recipient)
                </label>
                <input
                    type="tel"
                    name="phone_number"
                    id="phone_number"
                    placeholder="08123456789"
                    maxlength="11"
                    inputmode="numeric"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    required
                >
                <div id="phoneStatus" class="mt-1 text-xs font-medium hidden"></div>
                <small class="text-gray-500 text-xs mt-1">The e-PIN will be sent to this number via SMS</small>
            </div>

            <!-- Profile ID (Optional, with auto-verify) -->
            <div>
                <label for="profile_id" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5m-4 0V5a2 2 0 10-4 0v1m4 0a2 2 0 104 0m0 0V5a2 2 0 00-4 0v1" />
                    </svg>
                    JAMB Profile ID
                    <span class="ml-1 text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        name="profile_id"
                        id="profile_id"
                        placeholder="Enter profile ID to auto-verify"
                        maxlength="20"
                        class="w-full px-4 py-3 pr-10 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    >
                    <!-- Spinner shown while verifying -->
                    <div id="profileSpinner" class="absolute right-3 top-3.5 hidden">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
                <div id="profileStatus" class="mt-2 text-xs font-medium hidden py-2 px-3 rounded-lg"></div>
                <small class="text-gray-500 text-xs mt-1">Automatically verified as you type — leave blank to skip</small>
            </div>

            <!-- Amount (auto-filled based on Exam Type) -->
            <div>
                <label for="amount" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Amount
                </label>
                <div class="px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg font-bold text-gray-800 select-none">
                    ₦<span id="amountDisplay">3,500</span>
                </div>
                <input type="hidden" name="amount" id="amount" value="3500" required>
                <small class="text-gray-500 text-xs mt-1">Amount is set automatically based on exam type</small>
            </div>

            <!-- Security Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <div>
                        <p class="text-sm text-blue-900 font-medium">Transaction Security</p>
                        <p class="text-xs text-blue-800 mt-1">You'll be asked to enter your 4-digit PIN to complete the purchase. Your funds are protected.</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2 flex items-center justify-center">
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition duration-200 shadow-lg flex items-center justify-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Proceed to Payment
                </button>
            </div>

            <p class="text-xs text-center text-gray-500 mt-2">
                ✓ Secure payment &nbsp;|&nbsp; ✓ No hidden charges &nbsp;|&nbsp; ✓ Instant delivery
            </p>
        </form>
    </div>
</div>

<!-- ===================== PIN Confirmation Modal ===================== -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4 relative">

        <button id="pinModalCloseBtn" type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-800">Confirm Purchase</h2>
        </div>

        <p id="pinModalSummary" class="mb-5 text-gray-600 text-sm leading-relaxed bg-gray-50 p-4 rounded-lg"></p>

        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-3 text-center">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric">
            </div>
        </div>

        <button id="confirmPinBtn" type="button"
            class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-blue-700 transition duration-200 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirm &amp; Purchase
        </button>

        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- ===================== Loading Overlay ===================== -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="flex flex-col items-center gap-3">
        <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
        <p class="text-white font-semibold text-sm">Processing your purchase…</p>
    </div>
</div>

<style>
    .loader { border-top-color: #2563eb; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script src="{{ asset('js/error-handler.js') }}"></script>
<script src="{{ asset('js/success-handler.js') }}"></script>

<script>
// ============================================================================
// CONSTANTS
// ============================================================================
const AMOUNT_MAP = {
    'utme-mock':    3500,
    'utme-no-mock': 3000,
    'de':           2500,
};

const EXAM_LABELS = {
    'utme-mock':    'UTME e-PIN (with Mock)',
    'utme-no-mock': 'UTME e-PIN (without Mock)',
    'de':           'Direct Entry (DE) e-PIN',
};

const PHONE_RE   = /^0[7-9]\d{9}$/;
const walletBalance = {{ $wallet->balance ?? 0 }};

// ============================================================================
// STATE
// ============================================================================
let profileVerifyTimer = null;
let profileIsValid     = false;   // true only when backend confirms profile ID

// ============================================================================
// HELPERS
// ============================================================================
function toggleLoading(show) {
    document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
}

function setPhoneStatus(msg, type) {
    const el = document.getElementById('phoneStatus');
    if (!msg) { el.classList.add('hidden'); return; }
    const styles = {
        success: 'text-green-700',
        error:   'text-red-600',
        warn:    'text-amber-600',
    };
    el.textContent = msg;
    el.className   = `mt-1 text-xs font-medium ${styles[type] || 'text-gray-600'}`;
    el.classList.remove('hidden');
}

function setProfileStatus(msg, type) {
    const el = document.getElementById('profileStatus');
    if (!msg) { el.classList.add('hidden'); return; }
    const styles = {
        success: 'bg-green-50 text-green-800 border border-green-200',
        error:   'bg-red-50 text-red-800 border border-red-200',
        warn:    'bg-amber-50 text-amber-800 border border-amber-200',
        info:    'bg-blue-50 text-blue-800 border border-blue-200',
    };
    el.textContent = msg;
    el.className   = `mt-2 text-xs font-medium py-2 px-3 rounded-lg ${styles[type] || ''}`;
    el.classList.remove('hidden');
}

// ============================================================================
// EXAM TYPE → AMOUNT
// ============================================================================
document.getElementById('exam_type').addEventListener('change', function () {
    const amt = AMOUNT_MAP[this.value] || 0;
    document.getElementById('amount').value = amt;
    document.getElementById('amountDisplay').textContent =
        amt ? new Intl.NumberFormat('en-NG').format(amt) : '—';

    // Re-verify profile if already entered (exam type affects profile validation)
    const profileId = document.getElementById('profile_id').value.trim();
    if (profileId.length >= 4 && this.value) {
        scheduleProfileVerify();
    }
});

// ============================================================================
// PHONE NUMBER — live format validation (no API call needed)
// ============================================================================
document.getElementById('phone_number').addEventListener('input', function () {
    const val = this.value.replace(/\D/g, '').slice(0, 11);
    this.value = val;

    if (val.length === 0) {
        setPhoneStatus(null);
    } else if (val.length < 11) {
        setPhoneStatus(`${11 - val.length} digit${11 - val.length !== 1 ? 's' : ''} remaining`, 'warn');
    } else if (PHONE_RE.test(val)) {
        setPhoneStatus('✓ Valid Nigerian number', 'success');
    } else {
        setPhoneStatus('Must start with 070, 080, 081, 090, or 091', 'error');
    }
});

// ============================================================================
// PROFILE ID — auto-verify (debounced, via backend)
// ============================================================================
document.getElementById('profile_id').addEventListener('input', function () {
    profileIsValid = false;

    const val      = this.value.trim();
    const examType = document.getElementById('exam_type').value;

    if (!val) {
        setProfileStatus(null);
        return;
    }

    if (!examType) {
        setProfileStatus('Select an exam type first to verify your profile ID.', 'warn');
        return;
    }

    if (val.length < 4) {
        setProfileStatus('Keep typing your profile ID…', 'info');
        return;
    }

    scheduleProfileVerify();
});

function scheduleProfileVerify() {
    if (profileVerifyTimer) clearTimeout(profileVerifyTimer);
    profileVerifyTimer = setTimeout(runProfileVerify, 700);
}

async function runProfileVerify() {
    const profileId = document.getElementById('profile_id').value.trim();
    const examType  = document.getElementById('exam_type').value;

    if (!profileId || !examType) return;

    document.getElementById('profileSpinner').classList.remove('hidden');
    setProfileStatus('Verifying…', 'info');
    profileIsValid = false;

    try {
        const response = await fetch('{{ route("jamb.verify-profile") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ exam_type: examType, profile_id: profileId }),
        });

        const result = await response.json();

        if (result.success && result.name) {
            profileIsValid = true;
            setProfileStatus(`✓ Verified: ${result.name}`, 'success');
        } else {
            profileIsValid = false;
            setProfileStatus(result.message || 'Profile ID not found. You can still proceed without it.', 'warn');
        }
    } catch {
        profileIsValid = false;
        setProfileStatus('Verification service unavailable. You can still proceed.', 'warn');
    } finally {
        document.getElementById('profileSpinner').classList.add('hidden');
    }
}

// ============================================================================
// FORM SUBMIT → VALIDATE → PIN MODAL
// ============================================================================
document.getElementById('jambForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const examType    = document.getElementById('exam_type').value;
    const phoneNumber = document.getElementById('phone_number').value.trim();
    const amount      = parseFloat(document.getElementById('amount').value) || 0;
    const profileId   = document.getElementById('profile_id').value.trim();

    if (!examType) {
        alert('Please select an exam type.');
        return;
    }

    if (!phoneNumber || !PHONE_RE.test(phoneNumber)) {
        alert('Please enter a valid Nigerian phone number (e.g. 08123456789).');
        return;
    }

    if (!amount || amount < 1000) {
        alert('Invalid amount. Please re-select the exam type.');
        return;
    }

    if (walletBalance < amount) {
        alert(`Insufficient balance. You need ₦${amount.toLocaleString()} but have ₦${walletBalance.toLocaleString()}.`);
        return;
    }

    // Show PIN modal
    const profileLine = profileId
        ? `<div><strong>Profile ID:</strong> ${profileId}${profileIsValid ? ' <span class="text-green-600">✓</span>' : ''}</div>`
        : '';

    document.getElementById('pinModalSummary').innerHTML = `
        <div><strong>Exam Type:</strong></div>
        <div class="text-base font-semibold text-gray-800 mb-2">${EXAM_LABELS[examType] || examType}</div>
        <div><strong>Phone Number:</strong> ${phoneNumber}</div>
        ${profileLine}
        <div class="mt-2"><strong>Amount:</strong>
            <span class="text-xl font-bold text-blue-600 ml-1">₦${amount.toLocaleString('en-NG')}</span>
        </div>`;

    document.querySelectorAll('.pin-input').forEach(i => i.value = '');
    document.getElementById('pinModalError').classList.add('hidden');
    document.getElementById('pinModal').classList.remove('hidden');
    document.querySelector('.pin-input').focus();
});

// ============================================================================
// PIN MODAL — close, auto-advance, submit
// ============================================================================
document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
    document.getElementById('pinModal').classList.add('hidden');
});

document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
    input.addEventListener('input', function () {
        if (!/^\d$/.test(this.value)) { this.value = ''; return; }
        if (idx < arr.length - 1) arr[idx + 1].focus();
    });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && this.value === '' && idx > 0) arr[idx - 1].focus();
    });
});

document.getElementById('confirmPinBtn').addEventListener('click', async () => {
    let pin = '';
    document.querySelectorAll('.pin-input').forEach(i => pin += i.value);

    if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
        const errEl = document.getElementById('pinModalError');
        errEl.textContent = 'Please enter your complete 4-digit PIN.';
        errEl.classList.remove('hidden');
        return;
    }

    const formData = new FormData();
    formData.append('exam_type',    document.getElementById('exam_type').value);
    formData.append('phone_number', document.getElementById('phone_number').value.trim());
    formData.append('amount',       document.getElementById('amount').value);
    formData.append('profile_id',   document.getElementById('profile_id').value.trim());
    formData.append('pin',          pin);
    formData.append('_token',       '{{ csrf_token() }}');

    toggleLoading(true);
    document.getElementById('confirmPinBtn').disabled = true;

    try {
        const response = await fetch('{{ route("jamb.purchase") }}', {
            method: 'POST',
            body:   formData,
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('pinModal').classList.add('hidden');

            if (typeof window.handlePurchaseSuccess === 'function') {
                window.handlePurchaseSuccess(result);
            } else {
                // Inline success fallback
                const cardDetails = result.transaction?.card_details
                    ? `<div class="mt-2 p-3 bg-white rounded border text-xs font-mono break-all"><strong>Card Details:</strong><br>${result.transaction.card_details}</div>`
                    : '';

                const container = document.createElement('div');
                container.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
                container.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">
                        <div class="text-center mb-4">
                            <div class="text-4xl mb-2">✅</div>
                            <h3 class="text-lg font-bold text-gray-800">Purchase Successful!</h3>
                        </div>
                        <p class="text-gray-600 text-sm text-center mb-3">${result.message}</p>
                        ${cardDetails}
                        <div class="mt-4 text-xs text-gray-500 space-y-1">
                            <div><strong>Reference:</strong> ${result.transaction?.reference || 'N/A'}</div>
                            <div><strong>Status:</strong> ${result.transaction?.status || 'N/A'}</div>
                            <div><strong>New Balance:</strong> ₦${(result.transaction?.new_balance || 0).toLocaleString()}</div>
                        </div>
                        <button onclick="this.closest('.fixed').remove(); document.getElementById('jambForm').reset();"
                            class="mt-5 w-full bg-blue-600 text-white font-semibold py-2.5 rounded-xl hover:bg-blue-700 transition">
                            Done
                        </button>
                    </div>`;
                document.body.appendChild(container);
            }

            document.getElementById('jambForm').reset();
            document.getElementById('amount').value = 3500;
            document.getElementById('amountDisplay').textContent = '3,500';
            setPhoneStatus(null);
            setProfileStatus(null);
            profileIsValid = false;

        } else {
            const errEl = document.getElementById('pinModalError');
            errEl.textContent = result.message || 'Purchase failed. Please try again.';
            errEl.classList.remove('hidden');

            if (typeof window.handlePurchaseError === 'function') {
                window.handlePurchaseError(result);
            }
        }
    } catch (error) {
        console.error('Purchase error:', error);
        const errEl = document.getElementById('pinModalError');
        errEl.textContent = 'An error occurred. Please try again.';
        errEl.classList.remove('hidden');
    } finally {
        toggleLoading(false);
        document.getElementById('confirmPinBtn').disabled = false;
    }
});
</script>
@endsection