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
                <p class="block text-sm text-center font-semibold text-gray-700 mb-2">Secure & Instant</p>
            </div>
        </div>

        <!-- JAMB e-PIN Purchase Form -->
        <form id="jambForm" class="space-y-6">
            @csrf

            <!-- Exam Type Selection -->
            <div>
                <label for="exam_type" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 8.997 2 12.5s4.5 6.247 10 6.247m0-13c5.5 0 10 2.744 10 6.247m0 0c0 3.503-4.5 6.247-10 6.247m0-13V6.5" />
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
                    <option value="utme-mock">UTME e-PIN (with Mock) - ₦3,500</option>
                    <option value="utme-no-mock">UTME e-PIN (without Mock) - ₦3,000</option>
                    <option value="de">Direct Entry (DE) e-PIN - ₦2,500</option>
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
                    value="{{ old('phone_number') }}"
                    placeholder="08123456789"
                    pattern="^0[7-9]\d{9}$"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">The e-PIN will be sent to this number</small>
            </div>

            <!-- Profile ID (Optional) -->
            <div>
                <label for="profile_id" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5m-4 0V5a2 2 0 10-4 0v1m4 0a2 2 0 104 0m0 0V5a2 2 0 00-4 0v1" />
                    </svg>
                    JAMB Profile ID (Optional)
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="profile_id"
                        id="profile_id"
                        placeholder="Enter 10-digit profile ID"
                        maxlength="20"
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    >
                    <button
                        type="button"
                        id="verifyBtn"
                        class="px-4 py-3 bg-blue-50 border border-blue-200 text-blue-600 font-semibold rounded-lg hover:bg-blue-100 transition duration-200 flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Verify
                    </button>
                </div>
                <div id="verifyResult" class="mt-2 text-xs font-medium hidden py-2 px-3 rounded-lg"></div>
                <small class="text-gray-500 text-xs mt-1">Verify your profile before purchase (optional)</small>
            </div>

            <!-- Amount (Auto-filled based on Exam Type) -->
            <div>
                <label for="amount" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Amount
                </label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg font-bold text-gray-800">
                    ₦<span id="amountDisplay">3,500</span>
                </div>
                <input type="hidden" name="amount" id="amount" value="3500" required>
                <small class="text-gray-500 text-xs mt-1">Amount automatically set based on exam type</small>
            </div>

            <!-- Security Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <div>
                        <p class="text-sm text-blue-900 font-medium">Transaction Security</p>
                        <p class="text-xs text-blue-800 mt-1">You'll be asked to enter your 4-digit PIN to complete the purchase. Your funds are protected.</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 flex items-center justify-center">
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition duration-200 shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Proceed to Payment
                </button>
            </div>

            <p class="text-xs text-center text-gray-600 mt-4">
                ✓ Secure payment | ✓ No hidden charges | ✓ Instant delivery
            </p>
        </form>
    </div>
</div>

<!-- PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
        
        <!-- Close button -->
        <button id="pinModalCloseBtn" type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <!-- Header -->
        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-800">Confirm Purchase</h2>
        </div>
        
        <p id="pinModalSummary" class="mb-6 text-gray-600 text-center text-sm leading-relaxed bg-gray-50 p-4 rounded-lg"></p>
        
        <!-- PIN Input -->
        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
            </div>
        </div>
        
        <!-- Confirm Button -->
        <button id="confirmPinBtn" type="button" class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-blue-700 hover:shadow-lg transition duration-200 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirm & Purchase
        </button>
        
        <!-- Error Message -->
        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="flex flex-col items-center gap-3">
        <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
        <p class="text-white font-semibold">Processing your purchase...</p>
    </div>
</div>

<style>
    .loader {
        border-top-color: #2563eb;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script src="{{ asset('js/error-handler.js') }}"></script>
<script src="{{ asset('js/success-handler.js') }}"></script>

<script>
const amountMapping = {
    'utme-mock': 3500,
    'utme-no-mock': 3000,
    'de': 2500
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('jambForm');
    const examType = document.getElementById('exam_type');
    const amount = document.getElementById('amount');
    const amountDisplay = document.getElementById('amountDisplay');
    const profileId = document.getElementById('profile_id');
    const verifyBtn = document.getElementById('verifyBtn');
    const verifyResult = document.getElementById('verifyResult');

    // === AUTO-UPDATE AMOUNT BASED ON EXAM TYPE ===
    examType.addEventListener('change', function(e) {
        const selectedAmount = amountMapping[this.value] || 3500;
        amount.value = selectedAmount;
        amountDisplay.textContent = new Intl.NumberFormat('en-NG', { style: 'decimal' }).format(selectedAmount);
    });

    // === VERIFY PROFILE ID ===
    verifyBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const profileIdValue = profileId.value.trim();
        const examTypeValue = examType.value;

        if (!profileIdValue) {
            showVerifyResult('Please enter a profile ID', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
            return;
        }

        if (!examTypeValue) {
            showVerifyResult('Please select an exam type first', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6.364 1.636l-.707-.707M20 12h1m-1.364 6.364l-.707.707M12 20v1m-6.364-1.636l.707.707M4 12H3m1.364-6.364l.707-.707" /></svg> Verifying...';

        try {
            const response = await fetch('{{ route("jamb.verify-profile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    exam_type: examTypeValue,
                    profile_id: profileIdValue
                })
            });

            const result = await response.json();

            if (result.success && result.name) {
                showVerifyResult('Profile verified: ' + result.name, 'bg-green-50 text-green-800 border border-green-200');
            } else {
                showVerifyResult(result.message || 'Verification failed', 'bg-red-50 text-red-800 border border-red-200');
            }
        } catch (error) {
            console.error('Verification error:', error);
            showVerifyResult('Verification service unavailable', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Verify';
        }
    });

    function showVerifyResult(message, classes) {
        verifyResult.textContent = message;
        verifyResult.className = 'mt-2 text-xs font-medium ' + classes;
        verifyResult.classList.remove('hidden');
    }

    // === FORM SUBMISSION ===
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const examTypeValue = examType.value;
        const phoneNumber = document.getElementById('phone_number').value;
        const amountValue = parseFloat(amount.value);

        // Validation
        if (!examTypeValue) {
            alert('Please select an exam type.');
            return;
        }

        if (!phoneNumber) {
            alert('Please enter a phone number.');
            return;
        }

        if (!/^0[7-9]\d{9}$/.test(phoneNumber)) {
            alert('Please enter a valid Nigerian phone number (format: 08123456789)');
            return;
        }

        // Show PIN modal
        showPinModal(examTypeValue, phoneNumber, amountValue);
    });

    function showPinModal(examTypeLabel, phone, amt) {
        const labelMap = {
            'utme-mock': 'UTME e-PIN (with Mock)',
            'utme-no-mock': 'UTME e-PIN (without Mock)',
            'de': 'Direct Entry (DE) e-PIN'
        };

        document.getElementById('pinModalSummary').innerHTML = `
            <div><strong>Exam Type:</strong> <br><span class="text-lg">${labelMap[examTypeLabel] || examTypeLabel}</span></div>
            <div class="mt-3"><strong>Phone Number:</strong> <br><span class="text-lg">${phone}</span></div>
            <div class="mt-3"><strong>Amount:</strong> <br><span class="text-xl font-bold text-blue-600">₦${amt.toLocaleString('en-NG')}</span></div>
        `;

        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    }

    // === PIN MODAL CLOSE ===
    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });

    // === PIN INPUT AUTO-FOCUS ===
    document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function() {
            if (/\D/.test(this.value)) {
                this.value = '';
                return;
            }
            if (this.value.length === 1 && idx < arr.length - 1) {
                arr[idx + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && idx > 0) {
                arr[idx - 1].focus();
            }
        });
    });

    // === CONFIRM PIN AND SUBMIT ===
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

        const formData = new FormData();
        formData.append('exam_type', document.getElementById('exam_type').value);
        formData.append('phone_number', document.getElementById('phone_number').value);
        formData.append('amount', document.getElementById('amount').value);
        formData.append('profile_id', document.getElementById('profile_id').value || '');
        formData.append('pin', pin);
        formData.append('_token', '{{ csrf_token() }}');

        toggleLoading(true);
        document.getElementById('confirmPinBtn').disabled = true;

        try {
            const response = await fetch('{{ route("jamb.purchase") }}', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('pinModal').classList.add('hidden');
                window.handlePurchaseSuccess(result);
            } else {
                document.getElementById('pinModalError').textContent = result.message || 'Purchase failed. Please try again.';
                document.getElementById('pinModalError').classList.remove('hidden');
                if (window.handlePurchaseError) {
                    window.handlePurchaseError(result);
                }
            }
        } catch (error) {
            console.error('Purchase error:', error);
            document.getElementById('pinModalError').textContent = 'An error occurred. Please try again.';
            document.getElementById('pinModalError').classList.remove('hidden');
        } finally {
            toggleLoading(false);
            document.getElementById('confirmPinBtn').disabled = false;
        }
    });

    function toggleLoading(show) {
        const loading = document.getElementById('loadingOverlay');
        if(show) {
            loading.classList.remove('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }
});
</script>
@endsection
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
                <p class="block text-sm text-center font-semibold text-gray-700 mb-2">Secure & Instant</p>
            </div>
        </div>

        <!-- JAMB e-PIN Purchase Form -->
        <form id="jambForm" class="space-y-6">
            @csrf

            <!-- Exam Type Selection -->
            <div>
                <label for="exam_type" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 8.997 2 12.5s4.5 6.247 10 6.247m0-13c5.5 0 10 2.744 10 6.247m0 0c0 3.503-4.5 6.247-10 6.247m0-13V6.5" />
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
                    <option value="utme-mock">UTME e-PIN (with Mock) - ₦3,500</option>
                    <option value="utme-no-mock">UTME e-PIN (without Mock) - ₦3,000</option>
                    <option value="de">Direct Entry (DE) e-PIN - ₦2,500</option>
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
                    value="{{ old('phone_number') }}"
                    placeholder="08123456789"
                    pattern="^0[7-9]\d{9}$"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">The e-PIN will be sent to this number</small>
            </div>

            <!-- Profile ID (Optional) -->
            <div>
                <label for="profile_id" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5m-4 0V5a2 2 0 10-4 0v1m4 0a2 2 0 104 0m0 0V5a2 2 0 00-4 0v1" />
                    </svg>
                    JAMB Profile ID (Optional)
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="profile_id"
                        id="profile_id"
                        placeholder="Enter 10-digit profile ID"
                        maxlength="20"
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 font-medium"
                    >
                    <button
                        type="button"
                        id="verifyBtn"
                        class="px-4 py-3 bg-blue-50 border border-blue-200 text-blue-600 font-semibold rounded-lg hover:bg-blue-100 transition duration-200 flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Verify
                    </button>
                </div>
                <div id="verifyResult" class="mt-2 text-xs font-medium hidden py-2 px-3 rounded-lg"></div>
                <small class="text-gray-500 text-xs mt-1">Verify your profile before purchase (optional)</small>
            </div>

            <!-- Amount (Auto-filled based on Exam Type) -->
            <div>
                <label for="amount" class="flex items-center text-sm font-semibold text-gray-700 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Amount
                </label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg font-bold text-gray-800">
                    ₦<span id="amountDisplay">3,500</span>
                </div>
                <input type="hidden" name="amount" id="amount" value="3500" required>
                <small class="text-gray-500 text-xs mt-1">Amount automatically set based on exam type</small>
            </div>

            <!-- Security Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <div>
                        <p class="text-sm text-blue-900 font-medium">Transaction Security</p>
                        <p class="text-xs text-blue-800 mt-1">You'll be asked to enter your 4-digit PIN to complete the purchase. Your funds are protected.</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 flex items-center justify-center">
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition duration-200 shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Proceed to Payment
                </button>
            </div>

            <p class="text-xs text-center text-gray-600 mt-4">
                ✓ Secure payment | ✓ No hidden charges | ✓ Instant delivery
            </p>
        </form>
    </div>
</div>

<!-- PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
        
        <!-- Close button -->
        <button id="pinModalCloseBtn" type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <!-- Header -->
        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-800">Confirm Purchase</h2>
        </div>
        
        <p id="pinModalSummary" class="mb-6 text-gray-600 text-center text-sm leading-relaxed bg-gray-50 p-4 rounded-lg"></p>
        
        <!-- PIN Input -->
        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2 text-center">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
                <input type="password" maxlength="1" class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" inputmode="numeric" required>
            </div>
        </div>
        
        <!-- Confirm Button -->
        <button id="confirmPinBtn" type="button" class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-blue-700 hover:shadow-lg transition duration-200 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Confirm & Purchase
        </button>
        
        <!-- Error Message -->
        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="flex flex-col items-center gap-3">
        <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
        <p class="text-white font-semibold">Processing your purchase...</p>
    </div>
</div>

<style>
    .loader {
        border-top-color: #2563eb;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script src="{{ asset('js/error-handler.js') }}"></script>
<script src="{{ asset('js/success-handler.js') }}"></script>

<script>
const amountMapping = {
    'utme-mock': 3500,
    'utme-no-mock': 3000,
    'de': 2500
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('jambForm');
    const examType = document.getElementById('exam_type');
    const amount = document.getElementById('amount');
    const amountDisplay = document.getElementById('amountDisplay');
    const profileId = document.getElementById('profile_id');
    const verifyBtn = document.getElementById('verifyBtn');
    const verifyResult = document.getElementById('verifyResult');

    // === AUTO-UPDATE AMOUNT BASED ON EXAM TYPE ===
    examType.addEventListener('change', function(e) {
        const selectedAmount = amountMapping[this.value] || 3500;
        amount.value = selectedAmount;
        amountDisplay.textContent = new Intl.NumberFormat('en-NG', { style: 'decimal' }).format(selectedAmount);
    });

    // === VERIFY PROFILE ID ===
    verifyBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const profileIdValue = profileId.value.trim();
        const examTypeValue = examType.value;

        if (!profileIdValue) {
            showVerifyResult('Please enter a profile ID', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
            return;
        }

        if (!examTypeValue) {
            showVerifyResult('Please select an exam type first', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6.364 1.636l-.707-.707M20 12h1m-1.364 6.364l-.707.707M12 20v1m-6.364-1.636l.707.707M4 12H3m1.364-6.364l.707-.707" /></svg> Verifying...';

        try {
            const response = await fetch('{{ route("jamb.verify-profile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    exam_type: examTypeValue,
                    profile_id: profileIdValue
                })
            });

            const result = await response.json();

            if (result.success && result.name) {
                showVerifyResult('Profile verified: ' + result.name, 'bg-green-50 text-green-800 border border-green-200');
            } else {
                showVerifyResult(result.message || 'Verification failed', 'bg-red-50 text-red-800 border border-red-200');
            }
        } catch (error) {
            console.error('Verification error:', error);
            showVerifyResult('Verification service unavailable', 'bg-yellow-50 text-yellow-800 border border-yellow-200');
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Verify';
        }
    });

    function showVerifyResult(message, classes) {
        verifyResult.textContent = message;
        verifyResult.className = 'mt-2 text-xs font-medium ' + classes;
        verifyResult.classList.remove('hidden');
    }

    // === FORM SUBMISSION ===
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const examTypeValue = examType.value;
        const phoneNumber = document.getElementById('phone_number').value;
        const amountValue = parseFloat(amount.value);

        // Validation
        if (!examTypeValue) {
            alert('Please select an exam type.');
            return;
        }

        if (!phoneNumber) {
            alert('Please enter a phone number.');
            return;
        }

        if (!/^0[7-9]\d{9}$/.test(phoneNumber)) {
            alert('Please enter a valid Nigerian phone number (format: 08123456789)');
            return;
        }

        // Show PIN modal
        showPinModal(examTypeValue, phoneNumber, amountValue);
    });

    function showPinModal(examTypeLabel, phone, amt) {
        const labelMap = {
            'utme-mock': 'UTME e-PIN (with Mock)',
            'utme-no-mock': 'UTME e-PIN (without Mock)',
            'de': 'Direct Entry (DE) e-PIN'
        };

        document.getElementById('pinModalSummary').innerHTML = `
            <div><strong>Exam Type:</strong> <br><span class="text-lg">${labelMap[examTypeLabel] || examTypeLabel}</span></div>
            <div class="mt-3"><strong>Phone Number:</strong> <br><span class="text-lg">${phone}</span></div>
            <div class="mt-3"><strong>Amount:</strong> <br><span class="text-xl font-bold text-blue-600">₦${amt.toLocaleString('en-NG')}</span></div>
        `;

        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    }

    // === PIN MODAL CLOSE ===
    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });

    // === PIN INPUT AUTO-FOCUS ===
    document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function() {
            if (/\D/.test(this.value)) {
                this.value = '';
                return;
            }
            if (this.value.length === 1 && idx < arr.length - 1) {
                arr[idx + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && idx > 0) {
                arr[idx - 1].focus();
            }
        });
    });

    // === CONFIRM PIN AND SUBMIT ===
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

        const formData = new FormData();
        formData.append('exam_type', document.getElementById('exam_type').value);
        formData.append('phone_number', document.getElementById('phone_number').value);
        formData.append('amount', document.getElementById('amount').value);
        formData.append('profile_id', document.getElementById('profile_id').value || '');
        formData.append('pin', pin);
        formData.append('_token', '{{ csrf_token() }}');

        toggleLoading(true);
        document.getElementById('confirmPinBtn').disabled = true;

        try {
            const response = await fetch('{{ route("jamb.purchase") }}', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('pinModal').classList.add('hidden');
                window.handlePurchaseSuccess(result);
            } else {
                document.getElementById('pinModalError').textContent = result.message || 'Purchase failed. Please try again.';
                document.getElementById('pinModalError').classList.remove('hidden');
                if (window.handlePurchaseError) {
                    window.handlePurchaseError(result);
                }
            }
        } catch (error) {
            console.error('Purchase error:', error);
            document.getElementById('pinModalError').textContent = 'An error occurred. Please try again.';
            document.getElementById('pinModalError').classList.remove('hidden');
        } finally {
            toggleLoading(false);
            document.getElementById('confirmPinBtn').disabled = false;
        }
    });

    function toggleLoading(show) {
        const loading = document.getElementById('loadingOverlay');
        if(show) {
            loading.classList.remove('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }
});
</script>
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
                <h1 class="text-xl sm:text-2xl pt-7 font-bold text-gray-800">🎓 Purchase JAMB e-PIN</h1>
                <p class="block text-sm text-center font-semibold text-gray-700 mb-2">Instant & Secure</p>
            </div>
        </div>

        <!-- Wallet Balance Card -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-4 mb-8 border border-green-200">
            <p class="text-xs text-gray-600 font-medium">💰 Wallet Balance</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">₦{{ number_format($wallet->balance, 2) }}</p>
        </div>

        <!-- JAMB e-PIN Purchase Form -->
        <form id="jambForm" class="space-y-6">
            @csrf

            <!-- Exam Type Selection -->
            <div>
                <label for="exam_type" class="block text-sm font-semibold text-gray-700 mb-3">📚 Select Exam Type *</label>
                <select
                    name="exam_type"
                    id="exam_type"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 font-medium"
                    required
                >
                    <option value="">-- Choose Exam Type --</option>
                    <option value="utme-mock">UTME e-PIN (with Mock) - ₦3,500</option>
                    <option value="utme-no-mock">UTME e-PIN (without Mock) - ₦3,000</option>
                    <option value="de">Direct Entry (DE) e-PIN - ₦2,500</option>
                </select>
                <small class="text-gray-500 text-xs mt-1">ℹ️ Choose your exam type to receive the appropriate e-PIN</small>
            </div>

            <!-- Phone Number Input -->
            <div>
                <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-3">📱 Phone Number (Recipient) *</label>
                <input
                    type="tel"
                    name="phone_number"
                    id="phone_number"
                    value="{{ old('phone_number') }}"
                    placeholder="08123456789"
                    pattern="^0[7-9]\d{9}$"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 font-medium"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">ℹ️ The e-PIN will be sent to this number</small>
            </div>

            <!-- Profile ID (Optional) -->
            <div>
                <label for="profile_id" class="block text-sm font-semibold text-gray-700 mb-3">🆔 JAMB Profile ID (Optional)</label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        name="profile_id"
                        id="profile_id"
                        placeholder="Enter your 10-digit profile ID"
                        maxlength="20"
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 font-medium"
                    >
                    <button
                        type="button"
                        id="verifyBtn"
                        class="px-4 py-3 bg-blue-50 border border-blue-200 text-blue-600 font-semibold rounded-lg hover:bg-blue-100 transition duration-200"
                    >
                        ✓ Verify
                    </button>
                </div>
                <div id="verifyResult" class="mt-2 text-xs font-medium hidden"></div>
                <small class="text-gray-500 text-xs mt-1">ℹ️ Optional: Verify your profile before purchase</small>
            </div>

            <!-- Amount (Auto-filled based on Exam Type) -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-3">💵 Amount *</label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg font-bold text-gray-800">
                    ₦<span id="amountDisplay">3500</span>
                </div>
                <input type="hidden" name="amount" id="amount" value="3500" required>
                <small class="text-gray-500 text-xs mt-1">ℹ️ Amount automatically set based on exam type</small>
            </div>

            <!-- PIN Confirmation Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-900 font-medium">🔒 Transaction Security</p>
                <p class="text-xs text-blue-800 mt-1">You'll be asked to enter your 4-digit transaction PIN to complete the purchase. Your wallet will only be deducted upon successful verification.</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 flex items-center justify-center">
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-600 transition duration-200 shadow-lg shadow-teal-700/20 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    💳 Proceed to Payment
                </button>
            </div>

            <p class="text-xs text-center text-gray-600 mt-4">
                ✓ Secure payment | ✓ No hidden charges | ✓ Instant delivery
            </p>
        </form>
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
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm JAMB e-PIN Purchase</h2>
        <p id="pinModalSummary" class="mb-6 text-gray-600 text-center text-sm leading-relaxed bg-gray-50 p-4 rounded-lg"></p>
        
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
        
        <!-- Confirm Button -->
        <button id="confirmPinBtn" type="button" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200">
            ✅ Confirm & Purchase
        </button>
        
        <!-- Error Message -->
        <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
    <div class="flex flex-col items-center gap-3">
        <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
        <p class="text-white font-semibold">Processing your purchase...</p>
    </div>
</div>

<script src="{{ asset('js/error-handler.js') }}"></script>
<script src="{{ asset('js/success-handler.js') }}"></script>

<style>
    /* Spinner styles */
    .loader {
        border-top-color: #16a34a;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script>
const walletBalance = {{ $wallet->balance ?? 0 }};
const amountMapping = {
    'utme-mock': 3500,
    'utme-no-mock': 3000,
    'de': 2500
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('jambForm');
    const examType = document.getElementById('exam_type');
    const amount = document.getElementById('amount');
    const amountDisplay = document.getElementById('amountDisplay');
    const profileId = document.getElementById('profile_id');
    const verifyBtn = document.getElementById('verifyBtn');
    const verifyResult = document.getElementById('verifyResult');

    // === AUTO-UPDATE AMOUNT BASED ON EXAM TYPE ===
    examType.addEventListener('change', function(e) {
        const selectedAmount = amountMapping[this.value] || 3500;
        amount.value = selectedAmount;
        amountDisplay.textContent = new Intl.NumberFormat('en-NG').format(selectedAmount);
    });

    // === VERIFY PROFILE ID ===
    verifyBtn.addEventListener('click', async function() {
        const profileIdValue = profileId.value.trim();
        const examTypeValue = examType.value;

        if (!profileIdValue) {
            showVerifyResult('⚠️ Please enter a profile ID', 'text-gray-600');
            return;
        }

        if (!examTypeValue) {
            showVerifyResult('⚠️ Please select an exam type first', 'text-gray-600');
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '⏳ Verifying...';

        try {
            const response = await fetch('{{ route("jamb.verify-profile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    exam_type: examTypeValue,
                    profile_id: profileIdValue
                })
            });

            const result = await response.json();

            if (result.success && result.name) {
                showVerifyResult('✅ ' + result.name, 'text-green-600');
            } else {
                showVerifyResult('❌ ' + (result.message || 'Verification failed'), 'text-red-600');
            }
        } catch (error) {
            console.error('Verification error:', error);
            showVerifyResult('⚠️ Verification service unavailable', 'text-gray-600');
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '✓ Verify';
        }
    });

    function showVerifyResult(message, textClass) {
        verifyResult.textContent = message;
        verifyResult.className = 'mt-2 text-xs font-medium ' + textClass;
        verifyResult.classList.remove('hidden');
    }

    // === FORM SUBMISSION WITH PIN VALIDATION ===
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const examTypeValue = examType.value;
        const phoneNumber = document.getElementById('phone_number').value;
        const amountValue = parseFloat(amount.value);

        // Validation
        if (!examTypeValue) {
            window.handlePurchaseError({
                error_type: 'validation',
                title: '❌ MISSING SELECTION',
                message: 'Please select an exam type.',
                suggestion: 'Choose the exam type from the dropdown above.'
            });
            return;
        }

        if (!phoneNumber) {
            window.handlePurchaseError({
                error_type: 'validation',
                title: '❌ MISSING PHONE',
                message: 'Please enter a phone number.',
                suggestion: 'Phone number is required to receive your e-PIN.'
            });
            return;
        }

        if (!/^0[7-9]\d{9}$/.test(phoneNumber)) {
            window.handlePurchaseError({
                error_type: 'validation',
                title: '❌ INVALID PHONE',
                message: 'Please enter a valid Nigerian phone number.',
                suggestion: 'Format: 08123456789 (11 digits starting with 0)'
            });
            return;
        }

        if (walletBalance < amountValue) {
            const shortfall = amountValue - walletBalance;
            window.handlePurchaseError({
                error_type: 'insufficient_balance',
                title: '💰 INSUFFICIENT BALANCE',
                message: 'Your wallet balance is not enough for this purchase.',
                details: `Required: ₦${amountValue.toFixed(2)} | Available: ₦${walletBalance.toFixed(2)} | Shortfall: ₦${shortfall.toFixed(2)}`,
                suggestion: `Please fund your wallet with at least ₦${shortfall.toFixed(2)}`
            });
            return;
        }

        // Show PIN modal with summary
        showPinModal(examTypeValue, phoneNumber, amountValue);
    });

    function showPinModal(examTypeLabel, phone, amt) {
        const labelMap = {
            'utme-mock': 'UTME e-PIN (with Mock)',
            'utme-no-mock': 'UTME e-PIN (without Mock)',
            'de': 'Direct Entry (DE) e-PIN'
        };

        document.getElementById('pinModalSummary').innerHTML = `
            <div><strong>Exam Type:</strong> <br><span class="text-lg">${labelMap[examTypeLabel] || examTypeLabel}</span></div>
            <div class="mt-3"><strong>Phone Number:</strong> <br><span class="text-lg">${phone}</span></div>
            <div class="mt-3"><strong>Amount:</strong> <br><span class="text-xl font-bold text-green-600">₦${amt.toFixed(2)}</span></div>
            <div class="mt-3 font-semibold text-green-700 bg-white p-2 rounded"><strong>Balance:</strong> ₦${walletBalance.toFixed(2)}</div>
        `;

        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    }

    // === PIN MODAL CLOSE ===
    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });

    // === PIN INPUT AUTO-FOCUS ===
    document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function() {
            if (this.value.length === 1 && idx < arr.length - 1) {
                arr[idx + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && idx > 0) {
                arr[idx - 1].focus();
            }
        });
    });

    // === CONFIRM PIN AND SUBMIT ===
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

        const formData = new FormData();
        formData.append('exam_type', document.getElementById('exam_type').value);
        formData.append('phone_number', document.getElementById('phone_number').value);
        formData.append('amount', document.getElementById('amount').value);
        formData.append('profile_id', document.getElementById('profile_id').value || null);
        formData.append('pin', pin);
        formData.append('_token', '{{ csrf_token() }}');

        toggleLoading(true);

        try {
            const response = await fetch('{{ route("jamb.purchase") }}', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                document.getElementById('pinModal').classList.add('hidden');
                window.handlePurchaseSuccess(result);
            } else {
                document.getElementById('pinModalError').textContent = result.message || 'Purchase failed. Please try again.';
                document.getElementById('pinModalError').classList.remove('hidden');
                window.handlePurchaseError(result);
            }
        } catch (error) {
            console.error('Purchase error:', error);
            window.handlePurchaseError({
                error_type: 'system_error',
                title: '⚠️ SYSTEM ERROR',
                message: 'Something went wrong during the purchase.',
                suggestion: 'Please try again or contact support.'
            });
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(show) {
        const loading = document.getElementById('loadingOverlay');
        if(show) {
            loading.classList.remove('hidden');
        } else {
            loading.classList.add('hidden');
        }
    }
});
</script>
@endsection

<div id="networkModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2" id="modalContent">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul class="space-y-2">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200-5 pt hover:bg-gray-100 transition duration-150 network-option" data-value="airtel">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">Airtel NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="mtn">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">MTN NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="9mobile">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">9Mobile NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 network-option" data-value="glo">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-800 font-medium">Glo NG Data</span>
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

        // Function to show the modal with slide-up animation and dimming
        function showModal() {
            networkModal.classList.remove('hidden');
            // Allow a brief moment for the 'hidden' class to be removed before starting transition
            setTimeout(() => {
                networkModal.classList.add('opacity-100'); // Fade in the overlay
                modalContent.classList.remove('translate-y-full'); // Slide up the content
            }, 10);
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

                // Update the hidden select element's value and text
                Array.from(networkSelect.options).forEach(opt => {
                    if (opt.value === selectedValue) {
                        opt.selected = true;
                    } else {
                        opt.selected = false;
                    }
                });

                hideModal(); // Hide the modal
            });
        });

        // Close modal when clicking outside of it
        networkModal.addEventListener('click', function(event) {
            if (event.target === networkModal) {
                hideModal();
            }
        });
    });
</script>
<!-- PIN Confirmation Modal -->
<div id="pinModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300">
  <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform scale-95 transition-transform duration-300">
    
    <!-- Close button -->
    <button id="pinModalCloseBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      ✕
    </button>
    
    <!-- Header -->
    <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm JAMB e-PIN Purchase</h2>
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
    
    <!-- Confirm Button -->
    <button id="confirmPinBtn" type="button" class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 hover:shadow-lg transition duration-200">
      ✅ Confirm & Purchase
    </button>
    
    <!-- Error Message -->
    <div id="pinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
  </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-40">
  <div class="loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16"></div>
</div>

<!-- Custom modal for success/error messages -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full relative">
    <button id="modalCloseBtn" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">&times;</button>
    <h2 id="modalTitle" class="text-lg font-bold mb-2"></h2>
    <p id="modalMessage" class="whitespace-pre-wrap text-gray-700"></p>
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
</style>

<script>
// Add wallet balance (you'll need to pass this from your controller)
const walletBalance = {{ $wallet->balance ?? 0 }};

document.addEventListener('DOMContentLoaded', function() {
    // Override form submission to show PIN modal
    const form = document.querySelector('form[action="{{ route('jamb') }}"]');
    const originalFormAction = form.action;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Validate form fields
        const examType = document.getElementById('exam_type').value;
        const phoneNumber = document.getElementById('phone_number').value;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        
        // Validation
        if (!examType) return showCustomModal('Error', 'Please select an exam type.', 'text-red-600');
        if (!phoneNumber) return showCustomModal('Error', 'Please enter your phone number.', 'text-red-600');
        if (!amount || amount < 1000) return showCustomModal('Error', 'Please enter a valid amount (minimum ₦1,000).', 'text-red-600');
        if (amount > 10000) return showCustomModal('Error', 'Maximum amount allowed is ₦10,000.', 'text-red-600');
        
        // Check wallet balance
        if (walletBalance < amount) {
            showCustomModal(
                'Insufficient Balance',
                `Your account balance (₦${walletBalance.toFixed(2)}) is not enough for ₦${amount.toFixed(2)}. Please fund your wallet.`,
                'text-red-600'
            );
            return;
        }
        
        // Show PIN modal with summary
        document.getElementById('pinModalSummary').innerHTML = `
          <div><strong>Exam Type:</strong> ${examType.toUpperCase()}</div>
          <div><strong>Phone Number:</strong> ${phoneNumber}</div>
          <div><strong>Amount:</strong> ₦${amount.toFixed(2)}</div>
          <div class="mt-2 font-semibold text-green-700"><strong>Your Balance:</strong> ₦${walletBalance.toFixed(2)}</div>
        `;
        
        // Clear PIN inputs and errors
        document.querySelectorAll('.pin-input').forEach(input => input.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        
        // Show PIN modal
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    });

    // PIN modal close functionality
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
        formData.append('exam_type', document.getElementById('exam_type').value);
        formData.append('phone_number', document.getElementById('phone_number').value);
        formData.append('amount', document.getElementById('amount').value);
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
                showCustomModal('✅ Success', result.message || 'JAMB e-PIN purchased successfully!', 'text-green-600');
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

    // Close custom modal
    document.getElementById('modalCloseBtn').addEventListener('click', () => {
        document.getElementById('customModal').classList.add('hidden');
    });
});

// Utility functions
function showCustomModal(title, message, textClass = '') {
    const modal = document.getElementById('customModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modalMessage.className = 'whitespace-pre-wrap ' + textClass;

    modal.classList.remove('hidden');
}

function toggleLoading(show) {
    const loading = document.getElementById('loadingOverlay');
    if(show) {
        loading.classList.remove('hidden');
    } else {
        loading.classList.add('hidden');
    }
}
</script>
{{-- @endsection --}}
