@extends('layouts.app')

@section('title', 'Checkout - ' . $country['title'])

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 max-w-md">

        <!-- Header -->
        <div class="flex items-center mb-8">
            <a href="{{ url()->previous() }}" class="mr-3 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <img class="w-8 h-8 rounded mr-3" src="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" alt="{{ $country['title'] }}">
            <h1 class="text-xl font-bold text-gray-800">{{ $country['title'] }}</h1>
        </div>

        <!-- Plan Summary -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-5">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Selected Plan</h2>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-700 font-medium">{{ $package['title'] }}</p>
                    <p class="text-sm text-gray-500">Valid for {{ $package['validity'] }}</p>
                </div>
                <span class="text-xl font-bold text-gray-900">₦{{ $package['price'] }}</span>
            </div>
        </div>

        {{-- Description (only shown if admin set one) --}}
        @if(!empty($country['description']))
        <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-5">
            <svg class="w-4 h-4 text-blue-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-blue-700 leading-relaxed">{{ $country['description'] }}</p>
        </div>
        @endif

        {{-- Referral balance toggle --}}
        @include('partials.referral-balance-toggle', [
            'referralBalance' => auth()->user()->referral_balance ?? 0,
            'isPartner'       => auth()->user()->is_partner       ?? false,
        ])

        <!-- PIN Input form (submit handled by JS) -->
        <form id="checkoutForm" data-action="{{ route('checkout.process', [$country['code'], $package['id']]) }}">
            @csrf
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Enter Transaction PIN</h2>
                <div class="flex justify-between space-x-3">
                    @for($i = 0; $i < 4; $i++)
                        <input type="password"
                               inputmode="numeric"
                               maxlength="1"
                               class="pin-input w-14 h-14 border rounded-lg text-center text-xl font-bold
                                      focus:ring-2 focus:ring-blue-500 outline-none transition"
                               data-index="{{ $i }}"
                               required />
                    @endfor
                </div>
                <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
            </div>

            <button type="submit" id="submitBtn"
                class="w-full py-4 bg-blue-600 text-white font-semibold rounded-xl
                       hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                Confirm Purchase
            </button>
        </form>

        <div id="responseMsg" class="hidden mt-6 p-4 rounded-lg text-center"></div>

    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay"
     class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-600"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Config from Blade ─────────────────────────────────────────────────────
    const walletBalance = {{ $wallet->balance ?? 0 }};
    const packagePrice  = {{ (float) str_replace(',', '', $package['price']) }};

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const form        = document.getElementById('checkoutForm');
    const overlay     = document.getElementById('loadingOverlay');
    const responseMsg = document.getElementById('responseMsg');
    const errorMsg    = document.getElementById('errorMsg');
    const submitBtn   = document.getElementById('submitBtn');
    const inputs      = document.querySelectorAll('.pin-input');

    // ── Seed the referral hint on load ────────────────────────────────────────
    if (window.ReferralToggle) ReferralToggle.updateHint(packagePrice);

    document.addEventListener('referralToggleChanged', function () {
        if (window.ReferralToggle) ReferralToggle.updateHint(packagePrice);
    });

    // ── PIN input: auto-advance + backspace + paste ───────────────────────────
    inputs.forEach((input, idx) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) inputs[idx - 1].focus();
        });
        input.addEventListener('paste', e => {
            e.preventDefault();
            const digits = e.clipboardData.getData('text').replace(/[^0-9]/g, '').split('').slice(0, 4);
            digits.forEach((d, i) => { if (inputs[i]) inputs[i].value = d; });
            if (digits.length === 4) inputs[3].focus();
        });
    });

    // ── Submit ────────────────────────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        errorMsg.classList.add('hidden');
        responseMsg.classList.add('hidden');

        // Validate PIN
        const pinValues = Array.from(inputs).map(i => i.value);
        if (pinValues.some(v => !v)) {
            errorMsg.textContent = 'Please enter all 4 digits of your PIN.';
            errorMsg.classList.remove('hidden');
            return;
        }

        // ── Referral-aware balance check ──────────────────────────────────────
        const split = window.ReferralToggle
            ? ReferralToggle.getSummary(packagePrice)
            : { useReferral: false, referralDeduction: 0, walletDeduction: packagePrice };

        if (walletBalance < split.walletDeduction) {
            const needed = split.walletDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            const bal    = walletBalance.toLocaleString('en-NG', { minimumFractionDigits: 2 });

            if (split.useReferral && split.referralDeduction > 0) {
                errorMsg.textContent =
                    `Your referral balance covers ₦${split.referralDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 })},`
                    + ` but your wallet still needs ₦${needed} (balance: ₦${bal}). Please fund your wallet.`;
            } else {
                errorMsg.textContent =
                    `Insufficient balance. You need ₦${needed} but have ₦${bal}. Please fund your wallet.`;
            }
            errorMsg.classList.remove('hidden');
            return;
        }

        // ── Build FormData ────────────────────────────────────────────────────
        const formData = new FormData();
        formData.append('_token', form.querySelector('[name=_token]').value);
        pinValues.forEach((v, i) => formData.append(`pin[${i}]`, v));

        // Append referral flag from the hidden input set by the partial
        const refInput = document.getElementById('useReferralBalanceInput');
        if (refInput) formData.append('use_referral_balance', refInput.value);

        // ── Fire request ──────────────────────────────────────────────────────
        submitBtn.disabled    = true;
        submitBtn.textContent = 'Processing…';
        overlay.classList.remove('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response  = await fetch(form.dataset.action, {
                method:      'POST',
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken,
                },
                credentials: 'same-origin',
                body:        formData,
            });

            overlay.classList.add('hidden');
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Confirm Purchase';

            const result = await response.json();

            if (response.ok && result.success) {

                // ── Build success details ─────────────────────────────────────
                const details = {
                    'Transaction ID': result.data?.transaction_reference || 'N/A',
                    'Country':        result.data?.country               || '{{ $country['title'] }}',
                    'Plan':           result.data?.package               || '{{ $package['title'] }}',
                    'Validity':       result.data?.validity              || '{{ $package['validity'] }}',
                    'Amount':         result.data?.formatted_amount      || '₦{{ $package['price'] }}',
                    'New Balance':    result.data?.formatted_balance     || 'N/A',
                };

                if (result.data?.referral_deducted > 0) {
                    details['Referral Used']    = '₦' + parseFloat(result.data.referral_deducted)
                        .toLocaleString('en-NG', { minimumFractionDigits: 2 });
                    details['Paid from Wallet'] = '₦' + parseFloat(result.data.wallet_deducted)
                        .toLocaleString('en-NG', { minimumFractionDigits: 2 });
                }

                // ── Show success modal ────────────────────────────────────────
                showSuccessModal({
                    title:   'eSIM Purchase Successful!',
                    message: result.message || 'Your eSIM purchase was processed successfully.',
                    details: details,
                });

                inputs.forEach(i => i.value = '');

            } else {
                errorMsg.textContent = result.message || 'An error occurred. Please try again.';
                errorMsg.classList.remove('hidden');
                inputs.forEach(i => i.value = '');
                inputs[0].focus();
            }

        } catch (err) {
            overlay.classList.add('hidden');
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Confirm Purchase';
            errorMsg.textContent  = 'Network error. Please check your connection and try again.';
            errorMsg.classList.remove('hidden');
        }
    });

    // ── Success modal ─────────────────────────────────────────────────────────
    function showSuccessModal(data) {
        const detailsHtml = data.details
            ? Object.entries(data.details).map(([k, v]) =>
                `<div class="flex justify-between py-1.5 border-b border-gray-100 last:border-0">
                    <span class="text-gray-500 text-sm">${escHtml(k)}</span>
                    <span class="font-semibold text-gray-800 text-sm text-right max-w-[55%]">${escHtml(String(v))}</span>
                 </div>`).join('')
            : '';

        const wrapper = document.createElement('div');
        wrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 px-4';
        wrapper.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative max-h-screen overflow-y-auto">
                <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold"
                        onclick="this.closest('.fixed').remove()">✕</button>
                <div class="text-center mb-4">
                    <div class="text-5xl mb-3">✅</div>
                    <h2 class="text-xl font-bold text-gray-800">${escHtml(data.title || 'Success!')}</h2>
                    <p class="text-gray-500 text-sm mt-1">${escHtml(data.message || '')}</p>
                </div>
                ${detailsHtml
                    ? `<div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-0.5">${detailsHtml}</div>`
                    : ''}
                <button class="w-full bg-blue-600 text-white font-semibold py-3 rounded-xl
                               hover:bg-blue-700 transition"
                        onclick="window.location.href='/app/transactions'">View Transactions</button>
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
@endsection