@extends('layouts.app')

@section('title', 'Airtime Purchase')

@section('content')
{{-- {{dd($plans)}} --}}
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Data Purchase</h1>
        </div>

        <!-- Airtime Purchase Form -->
        <form action="{{ route('data.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Network Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="network" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <input type="hidden" name="network" id="networkInput">
                    <select 
                        id="network" 
                        class="custom-select w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                    >
                        <option value="" disabled selected>Choose A Network</option>
                        <option value="mtn">MTN</option>
                        <option value="glo">Glo</option>
                        <option value="airtel">Airtel</option>
                        <option value="9mobile">9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Package Selection -->
            <div class="pt-6 lg:pt-0">
                <label for="package" class="block text-sm font-semibold text-gray-700 mb-2">Package</label>
                <div 
                    id="packageSelect" 
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-pointer focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200"
                >
                    Choose a Package
                </div>
                <!-- Hidden input to hold the selected PRODUCT_CODE -->
                <input type="hidden" name="data_plan" id="packageInput">
                <input type="hidden" name="amount" id="amountInput">
            </div>

            <!-- Recipient Input -->
            <div class="pt-6 lg:pt-0">
                <label for="recipient" class="block text-sm font-semibold text-gray-700 mb-2">Recipient</label>
                <input 
                    type="tel" 
                    id="recipient" 
                    name="recipient" 
                    placeholder="Phone Number"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
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
<!-- Package Modal -->
<div id="packageModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="packageModalContent" class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2">
        <div class="px-6 py-4">
            <h3 id="packageModalTitle" class="text-lg font-semibold text-white text-center">Choose a Package</h3>   
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul id="packageList" class="space-y-2"></ul>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const networkSelect = document.getElementById('network');
    const networkInput = document.getElementById('networkInput');
    const packageTrigger = document.getElementById('packageSelect');
    const packageInput = document.getElementById('packageInput');
    const amountInput = document.getElementById('amountInput'); // New: amount input

    const networkModal = document.getElementById('networkModal');
    const packageModal = document.getElementById('packageModal');
    const modalContent = document.getElementById('modalContent');
    const packageModalContent = document.getElementById('packageModalContent');
    const packageModalTitle = document.getElementById('packageModalTitle');

    const networkOptions = document.querySelectorAll('.network-option');
    const packageList = document.getElementById('packageList');

    const dataPlans = window.dataPlans || {};
    let selectedNetwork = '';

    function showModal(modal, content) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            content.classList.remove('translate-y-full');
        }, 10);
    }

    function hideModal(modal, content) {
        modal.classList.remove('opacity-100');
        content.classList.add('translate-y-full');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function normalizeNetworkKey(key) {
        return Object.keys(dataPlans).find(k => k.toLowerCase().includes(key.toLowerCase()));
    }

    function populatePackages(networkKey) {
        const normalized = normalizeNetworkKey(networkKey);
        if (!normalized) return [];
        const raw = dataPlans[normalized];
        return raw?.[0]?.PRODUCT || [];
    }

    // Prevent native <select> dropdown and open modal instead
    networkSelect.addEventListener('mousedown', function (e) {
        e.preventDefault(); // Prevent default dropdown
        showModal(networkModal, modalContent);
    });

    // Handle network option click
    networkOptions.forEach(option => {
        option.addEventListener('click', function () {
            const selectedValue = this.dataset.value;
            const selectedText = this.querySelector('span')?.textContent || selectedValue;

            selectedNetwork = selectedValue;
            networkInput.value = selectedValue;
            packageTrigger.dataset.networkName = selectedText;

            // Update visible <select> for UI consistency
            networkSelect.querySelectorAll('option').forEach(opt => {
                opt.selected = (opt.value === selectedValue);
            });
            networkSelect.classList.remove('text-gray-500');
            networkSelect.classList.add('text-gray-800');

            // Reset package UI
            packageTrigger.textContent = 'Choose a Package';
            packageInput.value = '';
            amountInput.value = ''; // Reset amount too

            hideModal(networkModal, modalContent);
        });
    });

    // Show package modal
    packageTrigger.addEventListener('click', function () {
        if (!selectedNetwork) {
            alert('Please select a network first.');
            return;
        }

        const plans = populatePackages(selectedNetwork);
        const networkName = packageTrigger.dataset.networkName || 'Selected';

        packageModalTitle.textContent = `${networkName} Packages`;
        packageList.innerHTML = '';

        plans.forEach(plan => {
            const li = document.createElement('li');
            li.className = 'flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 pt-5 hover:bg-gray-100 transition duration-150 package-option';
            li.dataset.value = plan.PRODUCT_CODE;
            li.dataset.amount = plan.PRODUCT_AMOUNT; // Store amount as data attribute

            li.innerHTML = `
                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.264-4.296-15.56 0L2 8.444l.222.222c3.904 3.904 10.232 3.904 14.136 0l.222-.222-.222-.222zM10 12a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 4a6 6 0 100 12 6 6 0 000-12z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-800 font-medium">${plan.PRODUCT_NAME} - ₦${parseFloat(plan.PRODUCT_AMOUNT).toFixed(0)}</span>
            `;
            packageList.appendChild(li);
        });

        showModal(packageModal, packageModalContent);
    });

    // Handle package selection
    packageList.addEventListener('click', function (e) {
        const target = e.target.closest('.package-option');
        if (target) {
            const selectedValue = target.dataset.value;
            const selectedAmount = target.dataset.amount;
            const selectedText = target.textContent.trim();

            packageInput.value = selectedValue;
            amountInput.value = selectedAmount; // Set the amount input value
            packageTrigger.textContent = selectedText;
            packageTrigger.classList.remove('text-gray-500');
            packageTrigger.classList.add('text-gray-800');

            hideModal(packageModal, packageModalContent);
        }
    });

    // Close modals on background click
    [networkModal, packageModal].forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                const content = modal === networkModal ? modalContent : packageModalContent;
                hideModal(modal, content);
            }
        });
    });
});
</script>

<script>
    window.dataPlans = @json($plans);
</script>
@endsection
