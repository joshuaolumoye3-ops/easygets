@extends('layouts.app')

@section('title', 'Jamb Pin Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl pt-7 font-bold text-gray-800">Purchase JAMB e-PIN</h1>
            <p class="block text-sm text-center font-semibold text-gray-700 mb-2">Instant Activation</p>
            </div>

        </div>

        <!-- Airtime Purchase Form -->
        {{-- You can point this form to your purchase route --}}
        {{-- <form action="{{ route('airtime.purchase') }}" method="POST"> --}}
        <form action="{{ route('jamb') }}" method="POST" class="space-y-8">
    @csrf

    <!-- Exam Type Selection -->
    <div>
        <label for="exam_type" class="block text-sm font-semibold text-gray-700 mb-2">Exam Type</label>
        <select
            name="exam_type"
            id="exam_type"
            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
        >
            <option value="utme" selected>UTME</option>
            <option value="de">Direct Entry</option>
        </select>
    </div>

    <!-- Phone Number Input -->
    <div>
        <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
        <input
            type="tel"
            name="phone_number"
            id="phone_number"
            value="{{ old('phone_number') }}"
            placeholder="08123456789"
            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
            required
        >
        @error('phone_number')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Amount Input -->
    <div>
        <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
        <input
            type="number"
            name="amount"
            id="amount"
            value="{{ old('amount', '3500') }}"
            min="1000"
            max="10000"
            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
            required
        >
        @error('amount')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Submit Button -->
    <div class="pt-32 lg:pt-20 justify-center flex items-center">
        <button
            type="submit"
            class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20"
        >
            Pay Now
        </button>
    </div>
</form>
    </div>
</div>

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
@endsection
{{-- @endsection --}}
