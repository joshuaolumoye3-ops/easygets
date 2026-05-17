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
                <span class="text-xl font-bold text-gray-900">₦{{ $package['price'] }}</span>
            </div>
        </div>

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

            <!-- Checkout Button -->
            <button type="submit" id="submitBtn" class="w-full py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
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
    const submitBtn = document.getElementById('submitBtn');

    // PIN input auto-focus
    const inputs = document.querySelectorAll('.pin-input');
    
    inputs.forEach((input, idx) => {
        // Only allow numbers
        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/[^0-9]/g, '');
            
            // Move forward on input
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

        // Paste handling - distribute digits across inputs
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            const digits = pasteData.split('').slice(0, 4);
            
            digits.forEach((digit, i) => {
                if (inputs[i]) {
                    inputs[i].value = digit;
                }
            });
            
            if (digits.length === 4) {
                inputs[3].focus();
            }
        });
    }); 

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorMsg.classList.add('hidden');
        responseMsg.classList.add('hidden');

        // Collect PIN values
        const pinValues = Array.from(inputs).map(input => input.value);
        
        // Validate all 4 digits are entered
        if (pinValues.some(val => !val)) {
            errorMsg.textContent = "Please enter all 4 digits of your PIN.";
            errorMsg.classList.remove('hidden');
            return;
        }

        const url = form.dataset.action;
        
        // Build FormData with pin array
        const formData = new FormData();
        formData.append('_token', form.querySelector('[name=_token]').value);
        pinValues.forEach((value, index) => {
            formData.append(`pin[${index}]`, value);
        });

        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            overlay.classList.remove('hidden');

            // Debug: Log what we're sending
            console.log('Submitting to:', url);
            console.log('PIN values:', pinValues);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                },
                credentials: 'same-origin',
                body: formData
            });

            // Debug: Log response status
            console.log('Response status:', response.status);
            
            const result = await response.json();
            console.log('Response data:', result);
            
            overlay.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Purchase';

            if (response.status === 419) {
                errorMsg.textContent = "Your session has expired. Please refresh the page and try again.";
                errorMsg.classList.remove('hidden');
                return;
            }

            if (response.ok && result.success) {
                responseMsg.className = "mt-6 p-4 rounded-lg text-center bg-green-100 text-green-800 font-medium";
                responseMsg.innerHTML = `
                    <div class="mb-2">${result.message || "eSIM purchase successful!"}</div>
                    ${result.data ? `<div class="text-sm mt-2">Reference: ${result.data.transaction_reference}</div>` : ''}
                `;
                responseMsg.classList.remove('hidden');

                // Clear PIN inputs
                inputs.forEach(input => input.value = '');
                inputs[0].focus();

                // Redirect to transactions after 3 seconds
                setTimeout(() => {
                    window.location.href = '/app/transactions';
                }, 3000);
            } else {
                errorMsg.textContent = result.message || "An error occurred. Please try again.";
                errorMsg.classList.remove('hidden');
                
                // Clear PIN on error
                inputs.forEach(input => input.value = '');
                inputs[0].focus();
            }
        } catch (err) {
            overlay.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Purchase';
            errorMsg.textContent = "Network error. Please check your connection and try again.";
            errorMsg.classList.remove('hidden');
            console.error('Error:', err);
        }
    });
</script>
@endsection