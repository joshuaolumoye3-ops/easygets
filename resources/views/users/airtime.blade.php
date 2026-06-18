@extends('layouts.app')

@section('title', 'Airtime Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        {{-- Header --}}
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Airtime Purchase</h1>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form id="airtimeForm" action="{{ route('airtime.recharge') }}" method="POST">
            @csrf

            {{-- Network --}}
            <div class="pt-6 lg:pt-0 mb-4">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <select id="network" name="network"
                            class="w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200
                                   rounded-full text-gray-500 focus:ring-2 focus:ring-green-500
                                   focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                            required>
                        <option value="" disabled {{ old('network') ? '' : 'selected' }}>Choose A Network</option>
                        <option value="01" {{ old('network') == '01' ? 'selected' : '' }}>MTN</option>
                        <option value="02" {{ old('network') == '02' ? 'selected' : '' }}>Glo</option>
                        <option value="03" {{ old('network') == '03' ? 'selected' : '' }}>Airtel</option>
                        <option value="04" {{ old('network') == '04' ? 'selected' : '' }}>9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Mobile Number --}}
            <div class="mb-4">
                <label for="recipient" class="block text-sm font-medium text-gray-600 mb-1">Mobile Number</label>
                <input type="text" name="recipient" id="recipient"
                       placeholder="e.g. 08012345678"
                       class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500
                              focus:border-green-500 outline-none transition duration-200"
                       value="{{ old('recipient') }}" maxlength="15" required>
                <small class="text-gray-500 text-xs mt-1">Enter phone number without country code</small>
            </div>

            {{-- Amount --}}
            <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-600 mb-1">Amount (₦)</label>
                <input type="number" name="amount" id="amount"
                       placeholder="e.g. 100" min="50" max="50000" step="1"
                       class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500
                              focus:border-green-500 outline-none transition duration-200"
                       value="{{ old('amount') }}" required>
                <small class="text-gray-500 text-xs mt-1">Minimum: ₦50, Maximum: ₦50,000</small>
            </div>

            {{-- ✅ Referral balance toggle --}}
            @include('partials.referral-balance-toggle', [
                'referralBalance' => auth()->user()->referral_balance ?? 0,
                'isPartner'       => auth()->user()->is_partner       ?? false,
            ])

            <button type="button" id="submitBtn"
                    class="w-full bg-brand-green text-white font-bold py-3 px-4 rounded-xl
                           hover:bg-green-700 transition duration-200">
                Recharge Airtime
            </button>
        </form>
    </div>
</div>

{{-- ── Network Modal ──────────────────────────────────────────────────────── --}}
<div id="networkModal"
     class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay
            bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="modalContent"
         class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden
                transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4 border-b border-gray-200 bg-brand-green">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 pb-20 h-full overflow-y-auto">
            <ul class="space-y-2">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100
                           hover:bg-gray-50 transition duration-150 network-option" data-value="01">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">M</span>
                    </div>
                    <span class="text-gray-800 font-medium">MTN</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100
                           hover:bg-gray-50 transition duration-150 network-option" data-value="02">
                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">G</span>
                    </div>
                    <span class="text-gray-800 font-medium">Glo</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100
                           hover:bg-gray-50 transition duration-150 network-option" data-value="03">
                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">A</span>
                    </div>
                    <span class="text-gray-800 font-medium">Airtel</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100
                           hover:bg-gray-50 transition duration-150 network-option" data-value="04">
                    <div class="w-8 h-8 bg-green-800 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">9</span>
                    </div>
                    <span class="text-gray-800 font-medium">9mobile</span>
                </li>
            </ul>
        </div>
    </div>
</div>

{{-- ── PIN Modal ──────────────────────────────────────────────────────────── --}}
<div id="pinModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50
            hidden z-50 transition-opacity duration-300 px-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative
                transform scale-95 transition-transform duration-300">

        <button id="pinModalCloseBtn"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">✕</button>

        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Purchase</h2>

        <div id="pinModalSummary"
             class="mb-4 text-gray-600 text-sm leading-relaxed bg-gray-50 rounded-xl p-4 space-y-1">
        </div>

        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-2 text-center text-sm">
                Enter your 4-digit PIN
            </label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                       class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                       class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                       class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]*"
                       class="pin-input w-12 h-12 text-center border border-gray-300 rounded-lg text-2xl
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
            </div>
        </div>

        <button id="confirmPinBtn" type="button"
                class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow
                       hover:bg-green-700 hover:shadow-lg transition duration-200
                       disabled:bg-gray-400 disabled:cursor-not-allowed">
            <span id="confirmBtnText">Confirm & Pay</span>
            <span id="confirmBtnLoading" class="hidden">
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962
                             7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>

        <div id="pinModalError"
             class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Config passed from Blade ──────────────────────────────────────────────
    const walletBalance = {{ $wallet->balance ?? 0 }};

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const networkSelect  = document.getElementById('network');
    const networkModal   = document.getElementById('networkModal');
    const modalContent   = document.getElementById('modalContent');
    const form           = document.getElementById('airtimeForm');
    const submitBtn      = document.getElementById('submitBtn');
    const recipientInput = document.getElementById('recipient');
    const amountInput    = document.getElementById('amount');

    // ── Network modal helpers ─────────────────────────────────────────────────
    function showNetworkModal() {
        networkModal.classList.remove('hidden');
        setTimeout(() => {
            networkModal.classList.add('opacity-100');
            modalContent.classList.remove('translate-y-full');
        }, 10);
    }

    function hideNetworkModal() {
        networkModal.classList.remove('opacity-100');
        modalContent.classList.add('translate-y-full');
        setTimeout(() => networkModal.classList.add('hidden'), 300);
    }

    // ── Phone input: digits only + auto-detect network ────────────────────────
    recipientInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '').substring(0, 15);
        this.value = val;

        const detected = detectNetwork(val);
        if (detected) {
            const map = { mtn: '01', glo: '02', airtel: '03', '9mobile': '04' };
            const code = map[detected];
            if (code && networkSelect.value !== code) {
                networkSelect.value = code;
            }
        }

        // Update referral hint whenever phone changes (amount may already be set)
        const amt = parseFloat(amountInput.value) || 0;
        if (window.ReferralToggle) ReferralToggle.updateHint(amt);
    });

    function detectNetwork(raw) {
        if (!raw) return null;
        let n = raw;
        if (n.startsWith('+234')) n = '0' + n.slice(4);
        if (n.startsWith('234'))  n = '0' + n.slice(3);
        if (!n.startsWith('0'))   return null;

        const p = n.slice(0, 4);
        if (['0803','0806','0703','0706','0813','0816','0810','0814','0903','0906','0913','0916'].includes(p)) return 'mtn';
        if (['0805','0807','0815','0811','0905','0915'].includes(p)) return 'glo';
        if (['0802','0808','0708','0812','0701','0902','0907','0912'].includes(p)) return 'airtel';
        if (['0809','0817','0818','0909','0908','0917'].includes(p)) return '9mobile';
        return null;
    }

    // ── Amount input: update referral hint live ───────────────────────────────
    amountInput.addEventListener('input', function () {
        const val = parseInt(this.value);
        if (val && (val < 50 || val > 50000)) {
            this.setCustomValidity('Amount must be between ₦50 and ₦50,000');
        } else {
            this.setCustomValidity('');
        }
        if (window.ReferralToggle) ReferralToggle.updateHint(parseFloat(this.value) || 0);
    });

    // ── Also update hint when toggle fires ───────────────────────────────────
    document.addEventListener('referralToggleChanged', function () {
        const amt = parseFloat(amountInput.value) || 0;
        if (window.ReferralToggle) ReferralToggle.updateHint(amt);
    });

    // ── Network modal: intercept native select ────────────────────────────────
    networkSelect.addEventListener('mousedown', function (e) {
        e.preventDefault();
        showNetworkModal();
    });

    document.querySelectorAll('.network-option').forEach(option => {
        option.addEventListener('click', function () {
            networkSelect.value = this.dataset.value;
            networkSelect.dispatchEvent(new Event('change'));
            hideNetworkModal();
        });
    });

    networkModal.addEventListener('click', e => {
        if (e.target === networkModal) hideNetworkModal();
    });

    // ── Error helper ──────────────────────────────────────────────────────────
    function showErrorMessage(message) {
        document.querySelectorAll('.message-alert').forEach(el => el.remove());
        const el = document.createElement('div');
        el.className = 'message-alert mb-4 p-3 rounded bg-red-100 text-red-700 text-sm';
        el.textContent = message;
        form.parentNode.insertBefore(el, form);
        setTimeout(() => { if (el.parentNode) el.remove(); }, 6000);
    }

    // ── Submit button: validate then show PIN modal ───────────────────────────
    submitBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const network   = networkSelect.value;
        const recipient = recipientInput.value.trim();
        const amount    = parseFloat(amountInput.value) || 0;

        if (!network)                            return showErrorMessage('Please select a network.');
        if (!recipient)                          return showErrorMessage('Please enter a phone number.');
        if (amount < 50 || amount > 50000)       return showErrorMessage('Amount must be between ₦50 and ₦50,000.');

        // ── Referral-aware balance check ──────────────────────────────────────
        const split = window.ReferralToggle
            ? ReferralToggle.getSummary(amount)
            : { useReferral: false, referralDeduction: 0, walletDeduction: amount };

        if (walletBalance < split.walletDeduction) {
            const needed = split.walletDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            const bal    = walletBalance.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            if (split.useReferral && split.referralDeduction > 0) {
                showErrorMessage(
                    `Your referral balance covers ₦${split.referralDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 })},`
                    + ` but your wallet still needs ₦${needed} (balance: ₦${bal}). Please fund your wallet.`
                );
            } else {
                showErrorMessage(`Insufficient balance. You need ₦${needed} but have ₦${bal}. Please fund your wallet.`);
            }
            return;
        }

        // ── Build PIN modal summary ───────────────────────────────────────────
        const networkName = networkSelect.options[networkSelect.selectedIndex].text;
        const refHtml     = window.ReferralToggle ? ReferralToggle.getSummaryHtml(amount) : '';

        document.getElementById('pinModalSummary').innerHTML = `
            <div class="flex justify-between">
                <span class="font-medium">Network:</span>
                <span>${networkName}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Phone Number:</span>
                <span>${recipient}</span>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-2 mt-1">
                <span class="font-medium">Total Amount:</span>
                <span class="font-bold text-green-700">₦${amount.toLocaleString('en-NG', { minimumFractionDigits: 2 })}</span>
            </div>
            ${refHtml}
            <div class="flex justify-between text-gray-500 text-xs mt-1">
                <span>Wallet balance:</span>
                <span>₦${walletBalance.toLocaleString('en-NG', { minimumFractionDigits: 2 })}</span>
            </div>
        `;

        document.querySelectorAll('.pin-input').forEach(i => i.value = '');
        document.getElementById('pinModalError').classList.add('hidden');
        document.getElementById('pinModal').classList.remove('hidden');
        document.querySelector('.pin-input').focus();
    });

    // ── PIN modal: close ──────────────────────────────────────────────────────
    document.getElementById('pinModalCloseBtn').addEventListener('click', () => {
        document.getElementById('pinModal').classList.add('hidden');
    });
    document.getElementById('pinModal').addEventListener('click', e => {
        if (e.target === document.getElementById('pinModal')) {
            document.getElementById('pinModal').classList.add('hidden');
        }
    });

    // ── PIN inputs: auto-advance + backspace ──────────────────────────────────
    document.querySelectorAll('.pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            if (this.value && idx < arr.length - 1) arr[idx + 1].focus();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) arr[idx - 1].focus();
        });
    });

    // ── Confirm & submit ──────────────────────────────────────────────────────
    document.getElementById('confirmPinBtn').addEventListener('click', async function () {
        let pin = '';
        document.querySelectorAll('.pin-input').forEach(i => pin += i.value);

        const errorEl = document.getElementById('pinModalError');

        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            errorEl.textContent = 'Please enter all 4 PIN digits.';
            errorEl.classList.remove('hidden');
            return;
        }
        errorEl.classList.add('hidden');

        const confirmBtn     = this;
        const confirmBtnText = document.getElementById('confirmBtnText');
        const confirmLoading = document.getElementById('confirmBtnLoading');

        confirmBtn.disabled = true;
        confirmBtnText.classList.add('hidden');
        confirmLoading.classList.remove('hidden');

        const formData = new FormData(form);
        formData.append('pin', pin);
        // use_referral_balance is already appended via the hidden input in the partial

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                },
                body: formData,
            });

            const result = await response.json();

            if (response.ok && result.success) {
                document.getElementById('pinModal').classList.add('hidden');

                // Build details — include referral breakdown if used
                const details = {
                    'Transaction ID': result.data?.transaction_reference || 'N/A',
                    'Network':        result.data?.network               || networkSelect.options[networkSelect.selectedIndex].text,
                    'Phone Number':   result.data?.recipient             || recipientInput.value,
                    'Amount':         result.data?.formatted_amount      || '₦' + (parseFloat(amountInput.value) || 0).toFixed(2),
                    'New Balance':    result.data?.formatted_balance     || 'N/A',
                    'Date & Time':    result.data?.transaction_time
                                      ? new Date(result.data.transaction_time).toLocaleString()
                                      : new Date().toLocaleString(),
                };

                if (result.data?.referral_deducted > 0) {
                    details['Referral Used'] = '₦' + parseFloat(result.data.referral_deducted)
                        .toLocaleString('en-NG', { minimumFractionDigits: 2 });
                    details['Paid from Wallet'] = '₦' + parseFloat(result.data.wallet_deducted)
                        .toLocaleString('en-NG', { minimumFractionDigits: 2 });
                }

                await showSuccessPartial({
                    title:   'Airtime Purchase Successful!',
                    message: result.message,
                    details: details,
                });

                form.reset();
                networkSelect.selectedIndex = 0;

            } else {
                let errorMessage = result.message || 'Purchase failed. Please try again.';
                if (result.error_type === 'validation') errorMessage = 'Please check your input and try again.';
                errorEl.textContent = errorMessage;
                errorEl.classList.remove('hidden');
            }

        } catch (err) {
            console.error('Airtime purchase error:', err);
            errorEl.textContent = 'Network error. Please check your connection and try again.';
            errorEl.classList.remove('hidden');
        } finally {
            confirmBtn.disabled = false;
            confirmBtnText.classList.remove('hidden');
            confirmLoading.classList.add('hidden');
        }
    });

    // ── Success partial (server-rendered with client fallback) ────────────────
    async function showSuccessPartial(data) {
        try {
            const response = await fetch('/render-success-partial', {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'text/html',
                },
                body: JSON.stringify({ title: data.title, message: data.message, details: data.details }),
            });

            const html = response.ok ? await response.text() : null;
            buildSuccessModal(html, !!html, data);

        } catch (err) {
            buildSuccessModal(null, false, data);
        }
    }

    function buildSuccessModal(html, fromServer, data = {}) {
        const wrapper = document.createElement('div');
        wrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 px-4';

        const detailsHtml = data.details
            ? Object.entries(data.details).map(([k, v]) =>
                `<div class="flex justify-between py-1.5 border-b border-gray-100 last:border-0">
                    <span class="text-gray-500 text-sm">${escHtml(k)}</span>
                    <span class="font-semibold text-gray-800 text-sm text-right max-w-[55%]">${escHtml(String(v))}</span>
                 </div>`).join('')
            : '';

        wrapper.innerHTML = fromServer
            ? `<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative max-h-screen overflow-y-auto">
                   <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold z-10"
                           onclick="this.closest('.fixed').remove()">✕</button>
                   <div class="p-6">
                       ${html}
                       <button class="mt-5 w-full bg-brand-green text-white font-semibold py-3 rounded-xl
                                      hover:bg-green-700 transition"
                               onclick="this.closest('.fixed').remove()">Done</button>
                   </div>
               </div>`
            : `<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative max-h-screen overflow-y-auto">
                   <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold"
                           onclick="this.closest('.fixed').remove()">✕</button>
                   <div class="text-center mb-4">
                       <div class="text-5xl mb-3">✅</div>
                       <h2 class="text-xl font-bold text-gray-800">${escHtml(data.title || 'Success!')}</h2>
                       <p class="text-gray-500 text-sm mt-1">${escHtml(data.message || '')}</p>
                   </div>
                   ${detailsHtml ? `<div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-0.5">${detailsHtml}</div>` : ''}
                   <button class="w-full bg-brand-green text-white font-semibold py-3 rounded-xl
                                  hover:bg-green-700 transition"
                           onclick="this.closest('.fixed').remove()">Done</button>
               </div>`;

        document.body.appendChild(wrapper);

        wrapper.addEventListener('click', e => { if (e.target === wrapper) wrapper.remove(); });

        const esc = e => {
            if (e.key === 'Escape') { wrapper.remove(); document.removeEventListener('keydown', esc); }
        };
        document.addEventListener('keydown', esc);
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

}); // end DOMContentLoaded
</script>

<style>
@keyframes bounce-in {
    0%   { transform: scale(0.9); opacity: 0; }
    50%  { transform: scale(1.05); }
    100% { transform: scale(1);   opacity: 1; }
}
.animate-bounce-in { animation: bounce-in 0.3s ease-out; }
</style>
@endsection