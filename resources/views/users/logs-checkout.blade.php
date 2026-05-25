@extends('layouts.app')

@section('title', 'Checkout - {{ $product->name }}')

@section('content')
<div class="max-w-lg mx-auto py-8 px-4 sm:px-6 lg:px-8 font-[Inter]">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">

        {{-- Header --}}
        <div class="flex items-center mb-6">
            <a href="{{ route('logs') }}" class="mr-3 text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Checkout</h1>
                <p class="text-sm text-gray-500">Review your order before purchasing</p>
            </div>
        </div>

        {{-- Product Card --}}
        <div class="border border-gray-100 rounded-xl p-5 mb-6 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-block bg-green-100 rounded-full px-3 py-1 text-xs font-semibold text-green-700">
                    {{ $product->country }}
                </span>
                <span class="text-xs font-mono font-bold text-gray-400 uppercase">{{ $product->country_code }}</span>
            </div>
            <h2 class="font-bold text-gray-800 text-lg mb-2">{{ $product->name }}</h2>
            <p class="text-gray-500 text-sm mb-4">{{ $product->description }}</p>
            <div class="text-2xl font-bold text-green-600">₦{{ number_format($product->price, 2) }}</div>
        </div>

        {{-- Quantity --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
            <div class="flex items-center gap-3">
                <button type="button" id="qty-minus"
                    class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition font-bold text-lg">−</button>
                <input type="number" id="quantity" value="1" min="1" max="10" readonly
                    class="w-16 text-center border border-gray-200 rounded-lg h-10 font-bold text-gray-800 focus:outline-none">
                <button type="button" id="qty-plus"
                    class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition font-bold text-lg">+</button>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Price per unit</span>
                <span class="font-semibold">₦{{ number_format($product->price, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Quantity</span>
                <span class="font-semibold" id="summary-qty">1</span>
            </div>
            <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between font-bold text-gray-800">
                <span>Total</span>
                <span class="text-green-600" id="summary-total">₦{{ number_format($product->price, 2) }}</span>
            </div>
        </div>

        {{-- PIN Entry --}}
        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Enter your 4-digit PIN</label>
            <div class="flex gap-3 justify-center" id="pin-inputs">
                @for($i = 0; $i < 4; $i++)
                    <input type="password" maxlength="1" inputmode="numeric" pattern="[0-9]"
                        class="pin-digit w-12 h-12 text-center text-xl font-bold border-2 border-gray-200 rounded-xl focus:border-green-500 focus:outline-none transition"
                        data-index="{{ $i }}">
                @endfor
            </div>
        </div>

        {{-- Error / Success Messages --}}
        <div id="error-msg" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3 mb-4"></div>
        <div id="success-msg" class="hidden bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-3 mb-4"></div>

        {{-- Submit Button --}}
        <button id="buy-btn" type="button" onclick="submitPurchase()"
            class="w-full bg-brand-green hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Confirm Purchase
        </button>
    </div>

    <div class="text-center text-xs text-gray-400">Powered by HecksTechie &mdash; Secure logs delivery</div>
</div>

<script>
const unitPrice = {{ $product->price }};
const productId = {{ $product->id }};

// Quantity controls
document.getElementById('qty-minus').addEventListener('click', function() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateSummary();
    }
});

document.getElementById('qty-plus').addEventListener('click', function() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) < 10) {
        input.value = parseInt(input.value) + 1;
        updateSummary();
    }
});

function updateSummary() {
    const qty = parseInt(document.getElementById('quantity').value);
    const total = unitPrice * qty;
    document.getElementById('summary-qty').textContent = qty;
    document.getElementById('summary-total').textContent = '₦' + total.toLocaleString('en-NG', { minimumFractionDigits: 2 });
}

// PIN input auto-advance
document.querySelectorAll('.pin-digit').forEach(function(input, index, inputs) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(-1);
        if (this.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

function getPin() {
    return Array.from(document.querySelectorAll('.pin-digit')).map(i => i.value);
}

function showError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
    document.getElementById('success-msg').classList.add('hidden');
}

function showSuccess(msg) {
    const el = document.getElementById('success-msg');
    el.textContent = msg;
    el.classList.remove('hidden');
    document.getElementById('error-msg').classList.add('hidden');
}

function submitPurchase() {
    const pin = getPin();

    if (pin.some(d => d === '')) {
        showError('Please enter your full 4-digit PIN.');
        return;
    }

    const btn = document.getElementById('buy-btn');
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Processing...`;

    fetch(`/logs/${productId}/purchase`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            quantity: parseInt(document.getElementById('quantity').value),
            pin: pin
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => window.location.href = '{{ route("history") }}', 1500);
        } else {
            showError(data.message || 'Purchase failed. Please try again.');
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Confirm Purchase`;
            // Clear PIN on failure
            document.querySelectorAll('.pin-digit').forEach(i => i.value = '');
            document.querySelector('.pin-digit').focus();
        }
    })
    .catch(() => {
        showError('A network error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Confirm Purchase`;
    });
}
</script>
@endsection