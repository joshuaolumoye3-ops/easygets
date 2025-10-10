@extends('layouts.app')

@section('title', 'Checkout - ' . $country['title'])

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 max-w-md">

        <!-- Header -->
        <div class="flex items-center mb-8">
            <img class="w-8 h-8 mr-3" src="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" alt="{{ $country['title'] }}">
            <h1 class="text-xl font-bold text-gray-800">{{ $country['title'] }}</h1>
        </div>

        <!-- Plan Summary -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Selected Plan</h2>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-700 font-medium">{{ $package['title'] }}</p>
                    <p class="text-sm text-gray-500">Valid for {{ $package['validity'] }}</p>
                </div>
                <span class="text-xl font-bold text-gray-900">${{ $package['price'] }}</span>
            </div>
        </div>

        <!-- PIN Input -->
        <form id="checkoutForm" data-action="{{ route('checkout.process', [$country['code'], $package['id']]) }}">
            @csrf
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Enter Transaction PIN</h2>
                <div class="flex justify-between space-x-3">
                    @for($i = 0; $i < 4; $i++)
                        <input type="password" maxlength="1" 
                               class="pin-input w-14 h-14 border rounded-lg text-center text-xl font-bold focus:ring-2 focus:ring-blue-500"
                               name="pin[]" required />
                    @endfor
                </div>
                <p id="errorMsg" class="text-red-600 text-sm mt-3 hidden"></p>
            </div>

            <!-- Checkout Button -->
            <button type="submit" class="w-full py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition">
                Confirm Purchase
            </button>
        </form>

        <!-- Message Display -->
        <div id="responseMsg" class="hidden mt-6 p-4 rounded-lg text-center"></div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-600"></div>
</div>

<script>
    const form = document.getElementById('checkoutForm');
    const overlay = document.getElementById('loadingOverlay');
    const responseMsg = document.getElementById('responseMsg');
    const errorMsg = document.getElementById('errorMsg');

    // PIN input auto-focus
    const inputs = document.querySelectorAll('.pin-input');
    inputs.forEach((input, idx) => {
        // Move forward on input
        input.addEventListener('input', () => {
            if (input.value && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
            }
        });

        // Move backward on backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === "Backspace" && !input.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });
    }); 

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMsg.classList.add('hidden');
        responseMsg.classList.add('hidden');

        const url = form.dataset.action;
        const formData = new FormData(form);

        try {
            overlay.classList.remove('hidden');

            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": form.querySelector('[name=_token]').value,
                    "Accept": "application/json",
                },
                body: formData
            });

            const result = await response.json();
            overlay.classList.add('hidden');

            if (response.ok) {
                responseMsg.className = "mt-6 p-4 rounded-lg text-center bg-green-100 text-green-800";
                responseMsg.textContent = result.message || "Transaction created successfully and is pending.";
                responseMsg.classList.remove('hidden');
            } else {
                errorMsg.textContent = result.message || "An error occurred. Please try again.";
                errorMsg.classList.remove('hidden');
            }
        } catch (err) {
            overlay.classList.add('hidden');
            errorMsg.textContent = "Network error. Please try again.";
            errorMsg.classList.remove('hidden');
        }
    });
</script>
@endsection
