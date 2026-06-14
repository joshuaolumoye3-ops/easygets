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

        <!-- PIN Input -->
        <form id="checkoutForm" data-action="{{ route('checkout.process', [$country['code'], $package['id']]) }}">
            @csrf
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Enter Transaction PIN</h2>
                <div class="flex justify-between space-x-3">
                    @for($i = 0; $i < 4; $i++)
                        <input type="password"
                               inputmode="numeric"
                               maxlength="1"
                               class="pin-input w-14 h-14 border rounded-lg text-center text-xl font-bold focus:ring-2 focus:ring-blue-500"
                               data-index="{{ $i }}"
                               required />
                    @endfor
                </div>
                <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
            </div>

            <button type="submit" id="submitBtn"
                class="w-full py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                Confirm Purchase
            </button>
        </form>

        <div id="responseMsg" class="hidden mt-6 p-4 rounded-lg text-center"></div>

    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-600"></div>
</div>

<script>
    const form        = document.getElementById('checkoutForm');
    const overlay     = document.getElementById('loadingOverlay');
    const responseMsg = document.getElementById('responseMsg');
    const errorMsg    = document.getElementById('errorMsg');
    const submitBtn   = document.getElementById('submitBtn');
    const inputs      = document.querySelectorAll('.pin-input');

    inputs.forEach((input, idx) => {
        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && idx > 0) inputs[idx - 1].focus();
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const digits = e.clipboardData.getData('text').replace(/[^0-9]/g, '').split('').slice(0, 4);
            digits.forEach((d, i) => { if (inputs[i]) inputs[i].value = d; });
            if (digits.length === 4) inputs[3].focus();
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMsg.classList.add('hidden');
        responseMsg.classList.add('hidden');

        const pinValues = Array.from(inputs).map(i => i.value);
        if (pinValues.some(v => !v)) {
            errorMsg.textContent = 'Please enter all 4 digits of your PIN.';
            errorMsg.classList.remove('hidden');
            return;
        }

        const formData = new FormData();
        formData.append('_token', form.querySelector('[name=_token]').value);
        pinValues.forEach((v, i) => formData.append(`pin[${i}]`, v));

        try {
            submitBtn.disabled    = true;
            submitBtn.textContent = 'Processing…';
            overlay.classList.remove('hidden');

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response  = await fetch(form.dataset.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken },
                credentials: 'same-origin',
                body: formData,
            });

            overlay.classList.add('hidden');
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Confirm Purchase';

            const result = await response.json();

            if (response.ok && result.success) {
                responseMsg.className   = 'mt-6 p-4 rounded-lg text-center bg-green-100 text-green-800 font-medium';
                responseMsg.innerHTML   = `<div class="mb-2">${result.message || 'eSIM purchase successful!'}</div>${result.data ? `<div class="text-sm mt-2">Reference: ${result.data.transaction_reference}</div>` : ''}`;
                responseMsg.classList.remove('hidden');
                inputs.forEach(i => i.value = '');
                inputs[0].focus();
                setTimeout(() => { window.location.href = '/app/transactions'; }, 3000);
            } else {
                errorMsg.textContent = result.message || 'An error occurred. Please try again.';
                errorMsg.classList.remove('hidden');
                inputs.forEach(i => i.value = '');
                inputs[0].focus();
            }
        } catch(err) {
            overlay.classList.add('hidden');
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Confirm Purchase';
            errorMsg.textContent  = 'Network error. Please check your connection and try again.';
            errorMsg.classList.remove('hidden');
        }
    });
</script>
@endsection