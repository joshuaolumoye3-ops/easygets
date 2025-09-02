@extends('layouts.app')

@section('title', 'Airtime Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Airtime Purchase</h1>
        </div>

        {{-- Show success message --}}
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        {{-- Show error message --}}
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Show validation errors --}}
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="airtimeForm" action="{{ route('airtime.recharge') }}" method="POST">
            @csrf

            <!-- Mobile Network -->
            <div class="pt-6 lg:pt-0 mb-4">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <select
                        id="network"
                        name="network"
                        class="w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                        required
                    >
                        <option value="" disabled {{ old('network') ? '' : 'selected' }}>Choose A Network</option>
                        <option value="01" {{ old('network') == '01' ? 'selected' : '' }}>MTN</option>
                        <option value="02" {{ old('network') == '02' ? 'selected' : '' }}>Glo</option>
                        <option value="03" {{ old('network') == '03' ? 'selected' : '' }}>Airtel</option>
                        <option value="04" {{ old('network') == '04' ? 'selected' : '' }}>9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Mobile Number -->
            <div class="mb-4">
                <label for="recipient" class="block text-sm font-medium text-gray-600 mb-1">Mobile Number</label>
                <input 
                    type="text"
                    name="recipient"
                    id="recipient"
                    placeholder="e.g. 08012345678"
                    class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    value="{{ old('recipient') }}"
                    maxlength="15"
                    pattern="[0-9]+"
                    title="Please enter only numbers"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">Enter phone number without country code</small>
            </div>

            <!-- Amount -->
            <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-600 mb-1">Amount (₦)</label>
                <input 
                    type="number"
                    name="amount"
                    id="amount"
                    placeholder="e.g. 100"
                    min="50"
                    max="50000"
                    step="1"
                    class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    value="{{ old('amount') }}"
                    required
                >
                <small class="text-gray-500 text-xs mt-1">Minimum: ₦50, Maximum: ₦50,000</small>
            </div>

            <button 
                type="submit"
                id="submitBtn"
                class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-green-700 transition duration-200 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
                <span id="btnText">Recharge Airtime</span>
                <span id="loadingText" class="hidden">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
        </form>
    </div>
</div>

<!-- Custom Network Selection Modal -->
<div id="networkModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2" id="modalContent">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-600">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 pb-20 h-full overflow-y-auto">
            <ul class="space-y-2 ">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="01">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">M</span>
                    </div>
                    <span class="text-gray-800 font-medium">MTN</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="02">
                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">G</span>
                    </div>
                    <span class="text-gray-800 font-medium">Glo</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="03">
                    <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">A</span>
                    </div>
                    <span class="text-gray-800 font-medium">Airtel</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option" data-value="04">
                    <div class="w-8 h-8 bg-green-800 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-sm">9</span>
                    </div>
                    <span class="text-gray-800 font-medium">9mobile</span>
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
        const form = document.getElementById('airtimeForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const loadingText = document.getElementById('loadingText');
        const recipientInput = document.getElementById('recipient');
        const amountInput = document.getElementById('amount');

        // Phone number formatting and validation
        recipientInput.addEventListener('input', function(e) {
            // Remove non-digit characters
            let value = e.target.value.replace(/\D/g, '');
            
            // Limit to 15 characters
            if (value.length > 15) {
                value = value.substr(0, 15);
            }
            
            e.target.value = value;
        });

        // Amount validation
        amountInput.addEventListener('input', function(e) {
            const value = parseInt(e.target.value);
            if (value && (value < 50 || value > 50000)) {
                e.target.setCustomValidity('Amount must be between ₦50 and ₦50,000');
            } else {
                e.target.setCustomValidity('');
            }
        });

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

                // Update the hidden select element's value
                networkSelect.value = selectedValue;
                
                // Trigger change event to update any listeners
                networkSelect.dispatchEvent(new Event('change'));

                hideModal(); // Hide the modal
            });
        });

        // Close modal when clicking outside of it
        networkModal.addEventListener('click', function(event) {
            if (event.target === networkModal) {
                hideModal();
            }
        });

        // Form submission handling
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            loadingText.classList.remove('hidden');

            // Get form data
            const formData = new FormData(form);

            // Make AJAX request
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.message, 'success');
                    
                    // Reset form
                    form.reset();
                    networkSelect.selectedIndex = 0;
                } else {
                    // Show error message
                    showMessage(data.message || 'Airtime purchase failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                btnText.classList.remove('hidden');
                loadingText.classList.add('hidden');
            });
        });

        // Function to show success/error messages
        function showMessage(message, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.message-alert');
            existingMessages.forEach(msg => msg.remove());

            // Create new message element
            const messageEl = document.createElement('div');
            messageEl.className = `message-alert mb-4 p-3 rounded ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            messageEl.textContent = message;

            // Insert message before the form
            form.parentNode.insertBefore(messageEl, form);

            // Auto-remove message after 5 seconds
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.remove();
                }
            }, 5000);
        }
    });
</script>
@endsection