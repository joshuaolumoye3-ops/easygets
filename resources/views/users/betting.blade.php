@extends('layouts.app')

@section('title', 'Betting Purchase')

@section('content')
    <div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
        <div class="container mx-auto px-4 py-8 mt-10 max-w-md w-full">

            <!-- Header -->
            <div class="relative flex items-center justify-center mb-8 sm:mb-12">
                <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Betting</h1>
            </div>

            <!-- Betting Form -->
            <form action="{{ route('fund_bet') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Betting Company Selection -->
                <div class="pt-6 lg:pt-0">
                    <label for="betting_company" class="block text-sm font-semibold text-gray-700 mb-2">Betting
                        Company</label>
                    <div class="relative">
                        <input type="text" id="betting_company_display"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200 cursor-pointer"
                            placeholder="Select Betting Company" readonly onclick="showModal()">
                        <input type="hidden" id="betting_company" name="betting_company" required>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Customer ID Input -->
                <div class="pt-6 lg:pt-0">
                    <label for="customer_id" class="block text-sm font-semibold text-gray-700 mb-2">Customer ID</label>
                    <input type="text" id="customer_id" name="customer_id" placeholder="Enter your betting account ID"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        required>
                </div>

                <!-- Amount Input -->
                <div class="pt-6 lg:pt-0">
                    <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" placeholder="0.00" min="100" max="50000"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        required>
                </div>

                <!-- Request ID (hidden) -->
                <input type="hidden" name="request_id" value="{{ Str::uuid() }}">

                <!-- Submit Button -->
                <div class="pt-32 lg:pt-20 justify-center flex items-center">
                    <button type="submit"
                        class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                        Fund Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Betting Company Selection Modal -->
    <div id="bettingModal" class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-[70vh]">
        <div class="px-6 py-4 border-b border-gray-200 bg-brand-green">
            <h3 class="text-lg font-semibold text-white text-center">Choose Betting Company</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto">
            <ul class="grid grid-cols-1 gap-2">
                <!-- Main Betting Services -->
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Bet9ja" data-display="Bet9ja">
                    <div class="w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">B9</span>
                    </div>
                    <span class="text-gray-800 font-medium">Bet9ja</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BetKing" data-display="BetKing">
                    <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BK</span>
                    </div>
                    <span class="text-gray-800 font-medium">BetKing</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="1XBet" data-display="1XBet">
                    <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">1X</span>
                    </div>
                    <span class="text-gray-800 font-medium">1XBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="NairaBet" data-display="NairaBet">
                    <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NB</span>
                    </div>
                    <span class="text-gray-800 font-medium">NairaBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BetBiga" data-display="BetBiga">
                    <div class="w-8 h-8 rounded-full bg-orange-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BB</span>
                    </div>
                    <span class="text-gray-800 font-medium">BetBiga</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MerryBet" data-display="MerryBet">
                    <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">MB</span>
                    </div>
                    <span class="text-gray-800 font-medium">MerryBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="SportyBet" data-display="SportyBet">
                    <div class="w-8 h-8 rounded-full bg-green-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">SB</span>
                    </div>
                    <span class="text-gray-800 font-medium">SportyBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="NaijaBet" data-display="NaijaBet">
                    <div class="w-8 h-8 rounded-full bg-blue-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NJ</span>
                    </div>
                    <span class="text-gray-800 font-medium">NaijaBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Betway" data-display="Betway">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BW</span>
                    </div>
                    <span class="text-gray-800 font-medium">Betway</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="BangBet" data-display="BangBet">
                    <div class="w-8 h-8 rounded-full bg-purple-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">BG</span>
                    </div>
                    <span class="text-gray-800 font-medium">BangBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MelBet" data-display="MelBet">
                    <div class="w-8 h-8 rounded-full bg-yellow-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">MB</span>
                    </div>
                    <span class="text-gray-800 font-medium">MelBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="LiveScoreBet" data-display="LiveScoreBet">
                    <div class="w-8 h-8 rounded-full bg-green-800 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">LS</span>
                    </div>
                    <span class="text-gray-800 font-medium">LiveScoreBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Naira-Million" data-display="Naira Million">
                    <div class="w-8 h-8 rounded-full bg-purple-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">NM</span>
                    </div>
                    <span class="text-gray-800 font-medium">Naira Million</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="CloudBet" data-display="CloudBet">
                    <div class="w-8 h-8 rounded-full bg-blue-400 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">CB</span>
                    </div>
                    <span class="text-gray-800 font-medium">CloudBet</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="Paripesa" data-display="Paripesa">
                    <div class="w-8 h-8 rounded-full bg-red-700 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">PP</span>
                    </div>
                    <span class="text-gray-800 font-medium">Paripesa</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-200 hover:bg-gray-100 transition duration-150 betting-option"
                    data-value="MylottoHub" data-display="MyLotto Hub">
                    <div class="w-8 h-8 rounded-full bg-pink-600 flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xs">ML</span>
                    </div>
                    <span class="text-gray-800 font-medium">MyLotto Hub</span>
                </li>
            </ul>
        </div>
    </div>
</div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const amountInput = document.getElementById('amount');
            amountInput.addEventListener('change', function() {
                if (this.value < 100) this.value = 100;
                if (this.value > 50000) this.value = 50000;
            });


            const bettingModal = document.getElementById('bettingModal');
            const modalContent = document.querySelector('#bettingModal > div');
            const bettingOptions = document.querySelectorAll('.betting-option');
            const bettingCompanyDisplay = document.getElementById('betting_company_display');
            const bettingCompanyInput = document.getElementById('betting_company');

            function showModal() {
                bettingModal.classList.remove('hidden');
                setTimeout(() => {
                    bettingModal.classList.add('opacity-100');
                    modalContent.classList.remove('translate-y-full');
                }, 10);
            }

            function hideModal() {
                bettingModal.classList.remove('opacity-100');
                modalContent.classList.add('translate-y-full');
                setTimeout(() => bettingModal.classList.add('hidden'), 300);
            }

            bettingOptions.forEach(option => {
                option.addEventListener('click', function() {
                    bettingCompanyDisplay.value = this.dataset.display;
                    bettingCompanyInput.value = this.dataset.value;
                    hideModal();
                });
            });

            bettingModal.addEventListener('click', function(event) {
                if (event.target === bettingModal) hideModal();
            });
        });

        function showModal() {
            const bettingModal = document.getElementById('bettingModal');
            const modalContent = document.querySelector('#bettingModal > div');
            bettingModal.classList.remove('hidden');
            setTimeout(() => {
                bettingModal.classList.add('opacity-100');
                modalContent.classList.remove('translate-y-full');
            }, 10);
        }
    </script>
@endsection
