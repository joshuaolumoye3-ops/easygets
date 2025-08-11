@extends('layouts.app')

@section('title', 'Airtime Purchase')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 mt-10 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Betting</h1>
        </div>

        <!-- Airtime Purchase Form -->
        {{-- You can point this form to your purchase route --}}
        {{-- <form action="{{ route('airtime.purchase') }}" method="POST"> --}}
        <form action="#" method="POST" class="space-y-8">
            @csrf

            <!-- Network Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Service Provider</label>
                <div class="relative">
                    <select 
                        id="network" 
                        name="network"
                        class="w-full appearance-none px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    >
                        <option value="" disabled selected>Choose A Network</option>
                        {{-- You would populate these from your database --}}
                        <option value="mtn">sporty</option>
                        <option value="glo">bet9ja</option>
                        <option value="airtel">99bet</option>
                        <option value="9mobile">olobamonie bet</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Amount Input -->
            <div class="pt-6 lg:pt-0">
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">User ID</label>
                <input 
                    type="text" 
                    id="id" 
                    name="userid" 
                    placeholder="Customer id"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                >
            </div>

            <!-- Recipient Input -->
            <div class="pt-6 lg:pt-0">
                <label for="recipient" class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
                <input 
                    type="number" 
                    id="number" 
                    name="number" 
                    placeholder="N0.00"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                >
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
@endsection