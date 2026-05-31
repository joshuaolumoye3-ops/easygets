@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Main container that simulates a mobile screen on larger displays -->
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-brand-gray-light mt-0 pt-0">

    <!-- Header Section -->
    <header class="p-4 flex items-center space-x-3 bg-white">
        <img src="https://placehold.co/48x48/E6F5F1/0D5443?text={{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}"
            alt="User Avatar"
            class="w-12 h-12 rounded-full">
        <div>
            <p class="text-sm text-gray-500">Welcome Back,</p>
            <h1 class="font-bold text-lg text-gray-800">
                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
            </h1>
        </div>
    </header>

    <!-- Page-specific content starts here -->
    <div class="p-4 space-y-5">
        <!-- Wallet Balance Card -->
        <div class="bg-brand-green text-white rounded-xl p-5 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-80">Wallet Balance</p>
                    <p class="text-3xl font-bold mt-1">₦{{ number_format($wallet->balance ?? 0, 2) }}</p>
                </div>
                <div class="flex flex-col items-end space-y-2">
                    <a href="transactions" class="text-sm underline hover:no-underline">Transactions</a>
                    <button id="addFundsBtn"
                            class="bg-white text-[#03624C] font-bold py-2 px-4 rounded-full text-sm hover:bg-opacity-90 transition-colors">
                        + Add Funds
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Success/Error Messages -->
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Flutterwave Checkout Modal -->
        <div id="addFundsModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50">
            <div class="bg-white p-6 rounded-lg w-full max-w-md relative mx-4">
                <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl font-bold">✕</button>
                <h2 class="text-lg font-bold mb-4">Add Funds to Wallet</h2>

                <!-- Flutterwave Checkout Form -->
                <form id="generateAccountForm" action="{{ route('virtual-account.create') }}" method="POST">
                    @csrf
                    <!-- One-Time Account Warning -->
                    <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg flex items-start space-x-3">
                        <svg class="w-6 h-6 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.676-1.36 3.442 0l6.518 11.59c.75 1.334-.213 2.91-1.721 2.91H3.46c-1.508 0-2.47-1.576-1.72-2.91l6.517-11.59zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v3a1 1 0 01-1 1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-bold text-yellow-800 mb-1">One-Time Account Notice</p>
                            <p class="text-yellow-700 text-sm">The account number you are about to generate is for <span class="font-semibold">one-time use only</span>. It cannot be reused for future transactions. You must generate a new account number each time you want to fund your wallet.</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-4">
                            Enter the amount you want to add to your wallet. You'll be redirected to Flutterwave's secure checkout page.
                        </p>

                        <!-- Amount Input -->
                        <div class="mb-4">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Amount (₦)
                            </label>
                            <input type="number"
                                   id="amount"
                                   name="amount"
                                   required
                                   min="100"
                                   step="0.01"
                                   placeholder="Enter amount e.g. 1000"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Minimum amount: ₦100</p>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm text-blue-800 font-semibold mb-1">How it works:</p>
                                    <ul class="text-xs text-blue-700 space-y-1">
                                        <li>• You'll be redirected to Flutterwave's secure payment page</li>
                                        <li>• Complete payment with card, bank transfer, or USSD</li>
                                        <li>• Your wallet will be credited automatically</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mb-4">
                            <p class="text-xs text-gray-600 mb-2">Supported payment methods:</p>
                            <div class="flex items-center space-x-3">
                                <div class="flex items-center space-x-1 text-xs text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Card</span>
                                </div>
                                <div class="flex items-center space-x-1 text-xs text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Bank Transfer</span>
                                </div>
                                <div class="flex items-center space-x-1 text-xs text-gray-600">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                    <span>USSD</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="generateBtn"
                            class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Proceed to Payment
                    </button>
                </form>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-4">
            <button onclick="window.location.href='services'" class="bg-gray-800 text-white font-bold py-3.5 rounded-xl shadow-sm hover:bg-gray-700 transition-colors">
                Get OTP
            </button>
            <button onclick="window.location.href='social-boost'" class="bg-gray-800 text-white font-bold py-3.5 rounded-xl shadow-sm hover:bg-gray-700 transition-colors">
                Social Boosting
            </button>
        </div>

        <!-- Quick Pick Section -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Quick Pick</h2>
                <a href="{{ route('services.list') }}" class="text-sm font-semibold text-brand-green">See All</a>
            </div>
            <div class="grid grid-cols-4 gap-4 text-center">
                <!-- Facebook -->
                @if(isset($quickPickServices['facebook']))
                <button onclick="showProviderModal('{{ $quickPickServices['facebook']['id'] }}', '{{ $quickPickServices['facebook']['title'] }}', 'fb')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/2021_Facebook_icon.svg" class="w-10 h-10" alt="Facebook">
                    <span class="text-xs text-gray-600">Facebook</span>
                </button>
                @endif

                <!-- WhatsApp -->
                @if(isset($quickPickServices['whatsapp']))
                <button onclick="showProviderModal('{{ $quickPickServices['whatsapp']['id'] }}', '{{ $quickPickServices['whatsapp']['title'] }}', 'wa')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" class="w-10 h-10" alt="WhatsApp">
                    <span class="text-xs text-gray-600">WhatsApp</span>
                </button>
                @endif

                <!-- Telegram -->
                @if(isset($quickPickServices['telegram']))
                <button onclick="showProviderModal('{{ $quickPickServices['telegram']['id'] }}', '{{ $quickPickServices['telegram']['title'] }}', 'tg')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/82/Telegram_logo.svg" class="w-10 h-10" alt="Telegram">
                    <span class="text-xs text-gray-600">Telegram</span>
                </button>
                @endif

                <!-- Google Voice -->
                @if(isset($quickPickServices['google']))
                <button onclick="showProviderModal('{{ $quickPickServices['google']['id'] }}', '{{ $quickPickServices['google']['title'] }}', 'google')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://static.cdnlogo.com/logos/g/86/google-voice.svg" class="w-10 h-10" alt="Google Voice">
                    <span class="text-xs text-gray-600">Google Voice</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Provider Selection Modal --}}
        <div id="providerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 id="modalServiceTitle" class="text-xl font-bold text-white">Select Provider</h2>
                            <p class="text-green-100 text-sm mt-1">Choose your preferred SMS provider</p>
                        </div>
                        <button onclick="closeProviderModal()" class="text-white hover:text-gray-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    {{-- Provider 1: 5sim.net --}}
                    <button onclick="selectProvider('provider1')"
                            class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:shadow-lg transition">
                                EG1
                            </div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-800 group-hover:text-blue-700 transition">Provider 1</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs text-gray-600">Global coverage, multiple operators</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </button>

                    {{-- Provider 2: SMS-Bus --}}
                    <button onclick="selectProvider('provider2')"
                            class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:shadow-lg transition">
                                EG2
                            </div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-800 group-hover:text-green-700 transition">Provider 2</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs text-gray-600">Fast & reliable</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </button>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                    <p class="text-xs text-gray-600 text-center">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        Both providers offer secure SMS verification services
                    </p>
                </div>
            </div>
        </div>

        <!-- Services Grid -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
             <div class="grid grid-cols-4 gap-x-4 gap-y-6 text-center">
                <!-- Service Item -->
                <a href="data" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/wifi.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Data</span>
                </a>
                 <!-- Service Item -->
                 <a href="airtime" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/phone.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Airtime</span>
                </a>
                 <!-- Service Item -->
                 <a href="electricity" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/electricity.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Electricity</span>
                </a>
                 <!-- Service Item -->
                <a href="cabletv" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/tv.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">TV/Cable</span>
                </a>
                 <!-- betting Item -->
                <a href="betting" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/ball.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Betting</span>
                </a>
                 <!-- Service Item -->
                 <a href="esim" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/e-sim.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">eSim</span>
                </a>
                 <!-- Service Item -->
                <a href="logs" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/sim-icon.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Logs</span>
                </a>
                 <!-- Service Item -->
                <a href="more-services" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/more.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">Others</span>
                </a>
             </div>
        </div>

        <!-- Transactions List -->
        <div class="space-y-4 pb-20">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Transactions</h2>
                <a href="transactions" class="text-sm font-semibold text-brand-green">View more +</a>
            </div>
            @forelse ($latestTransactions as $txn)
                <div class="flex items-center space-x-4">
                    <div class="bg-brand-green-light rounded-full w-12 h-12 flex-shrink-0 flex items-center justify-center">
                        @if($txn->type === 'payment')
                            <!-- Top-up Icon -->
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        @else
                            <!-- Generic Transaction Icon -->
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>

                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800 flex items-center justify-between">
                            <span>
                                {{ ucfirst($txn->type) }} {{ $txn->provider ? ' - ' . $txn->provider : '' }}
                            </span>

                            {{-- Status Badge --}}
                            @php
                                $statusColor = match ($txn->status) {
                                    'pending' => 'bg-green-100 text-green-600',
                                    'success' => 'bg-green-600 text-white',
                                    'failed'  => 'bg-red-500 text-white',
                                    default   => 'bg-gray-300 text-gray-600'
                                };
                            @endphp
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full font-medium {{ $statusColor }}">
                                {{ ucfirst($txn->status) }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $txn->created_at->format('h:i A | d M, Y') }}
                        </p>
                    </div>

                    <div class="font-bold {{ $txn->type === 'payment' ? 'text-green-600' : 'text-gray-800' }}">
                        {{ $txn->type === 'payment' ? '+' : '-' }} ₦{{ number_format($txn->amount, 2) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No transactions yet.</p>
            @endforelse
        </div>
    </div>
</div>

@include('partials.social-form')
@include('partials.footer')

@if (Auth::user() && !Auth::user()->pin)
<!-- Transaction PIN Modal -->
<div id="pinModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-8 relative">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Set Transaction PIN</h2>
        <p class="text-gray-600 text-center mb-6">For your security, set a 4-digit PIN to authorize transactions.</p>
        <form id="pinFormModal" action="{{ route('updateProfile.pin') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="pin_modal" class="block text-sm font-medium text-gray-700 mb-2">Enter 4-Digit PIN</label>
                <input type="password" id="pin_modal" name="pin" maxlength="4" required autofocus inputmode="numeric" pattern="[0-9]{4}"
                    class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin') border-red-500 @enderror">
                @error('pin')
                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="pin_confirmation_modal" class="block text-sm font-medium text-gray-700 mb-2">Confirm PIN</label>
                <input type="password" id="pin_confirmation_modal" name="pin_confirmation" maxlength="4" required inputmode="numeric" pattern="[0-9]{4}"
                    class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin_confirmation') border-red-500 @enderror">
                @error('pin_confirmation')
                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full bg-brand-green text-white text-lg font-semibold py-3 px-4 rounded-xl hover:bg-brand-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition duration-200">Save PIN</button>
        </form>
    </div>
</div>
@endif

<script>
    function openSocialModal() {
        document.getElementById('socialModal').classList.remove('hidden');
    }

    function closeSocialModal() {
        document.getElementById('socialModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addFundsBtn = document.getElementById('addFundsBtn');
        const modal = document.getElementById('addFundsModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const generateForm = document.getElementById('generateAccountForm');

        // Transaction PIN Modal logic
        @if (Auth::user() && !Auth::user()->pin)
        setTimeout(function() {
            const pinModal = document.getElementById('pinModal');
            if (pinModal) {
                pinModal.style.display = 'flex';
                // Prevent background scroll
                document.body.style.overflow = 'hidden';
            }
        }, 5000); // Show after 5 seconds

        // Prevent closing modal by clicking outside or pressing ESC
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('pinModal')?.style.display === 'flex') {
                if (e.key === 'Escape') {
                    e.preventDefault();
                }
            }
        });
        document.getElementById('pinModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                e.stopPropagation();
            }
        });
        // On submit, allow scroll again
        document.getElementById('pinFormModal')?.addEventListener('submit', function() {
            document.body.style.overflow = '';
        });
        @endif

        // Open modal when Add Funds button is clicked
        if (addFundsBtn) {
            addFundsBtn.addEventListener('click', function() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        }

        // Close modal button
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }

        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });

        // Add loading state to the form
        if (generateForm) {
            generateForm.addEventListener('submit', function() {
                const button = document.getElementById('generateBtn');
                const amount = document.getElementById('amount').value;

                if (!amount || amount < 100) {
                    return;
                }

                button.disabled = true;
                button.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Redirecting to Payment...
                `;
            });
        }
    });
</script>
<script>
    let selectedServiceId = null;
let selectedServiceName = null;
let selectedServiceCode = null;

function showProviderModal(serviceId, serviceName, serviceCode) {
    selectedServiceId = serviceId;
    selectedServiceName = serviceName;
    selectedServiceCode = serviceCode;

    document.getElementById('modalServiceTitle').textContent = serviceName;
    const modal = document.getElementById('providerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeProviderModal() {
    const modal = document.getElementById('providerModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function selectProvider(provider) {
    if (provider === 'provider1') {
        window.location.href = `/app/services/${selectedServiceCode}/provider1/countries`;
    } else {
        window.location.href = `/app/services/${selectedServiceId}/countries-provider2`;
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProviderModal();
    }
});

// Close modal on outside click
document.getElementById('providerModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeProviderModal();
    }
});
</script>
@endsection