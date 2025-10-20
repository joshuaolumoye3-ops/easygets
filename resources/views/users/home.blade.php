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

        <!-- Virtual Account Details Card -->
        @if(session('account_data'))
        <div class="bg-white rounded-xl p-5 shadow-lg border border-green-200">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-bold text-lg text-gray-800">Your Virtual Account</h3>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    Active
                </span>
            </div>

            <div class="space-y-4">
                <!-- Account Number -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 mb-1">Account Number</p>
                    <div class="flex items-center justify-between">
                        <p class="font-mono text-xl font-bold text-gray-800">
                            {{ session('account_data')['account_number'] ?? 'N/A' }}
                        </p>
                        <button onclick="copyToClipboard('{{ session('account_data')['account_number'] }}')"
                                class="text-brand-green font-bold text-sm hover:text-green-700 transition-colors">
                            Copy
                        </button>
                    </div>
                </div>

                <!-- Account Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Bank Name</p>
                        <p class="font-semibold text-gray-800">{{ session('account_data')['bank_name'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Account Name</p>
                        <p class="font-semibold text-gray-800">{{ session('account_data')['account_name'] ?? Auth::user()->username . ' Flw' }}</p>
                    </div>
                </div>

                <!-- Amount -->
                <div class="bg-green-50 p-3 rounded-lg">
                    <p class="text-sm text-gray-600">Amount to Fund</p>
                    <p class="text-xl font-bold text-brand-green">
                        ₦{{ number_format(session('account_data')['amount'] ?? 0, 2) }}
                    </p>
                </div>

                <!-- Expiry Information -->
                @if(session('account_data')['expires_at'] ?? false)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-yellow-800">
                            <strong>Expires:</strong> {{ session('account_data')['expires_at'] }}
                        </p>
                    </div>
                </div>
                @endif

                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-sm text-blue-800">
                        <strong>How to fund:</strong> Transfer the exact amount to the account number above from your bank. Your wallet will be credited automatically once payment is received.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <button onclick="closeVirtualAccountCard()"
                            class="flex-1 bg-gray-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-gray-600 transition-colors">
                        Close
                    </button>
                    <button onclick="generateNewAccount()"
                            class="flex-1 bg-brand-green text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        Generate New
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- One-Time Virtual Account Modal -->
        <div id="addFundsModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50">
            <div class="bg-white p-6 rounded-lg w-full max-w-md relative mx-4">
                <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-xl font-bold">✕</button>
                <h2 class="text-lg font-bold mb-4">Add Funds</h2>

                <!-- Display virtual account details if generated -->
                @if(session('account_data'))
                    <div class="virtual-account-info mb-4 p-4 border border-green-200 rounded-lg bg-green-50">
                        <h5 class="text-green-700 font-bold mb-3">One-Time Account Generated!</h5>
                        <p class="text-sm text-gray-600 mb-3">Transfer funds to this account to add money to your wallet:</p>
                        <div class="space-y-2">
                            <div class="bg-white p-2 rounded">
                                <p class="text-xs text-gray-600">Account Number</p>
                                <p class="font-bold text-lg flex items-center justify-between">
                                    <span id="account-number" class="font-mono">{{ session('account_data')['account_number'] ?? 'N/A' }}</span>
                                    <button onclick="copyToClipboard('account-number')" class="text-brand-green font-bold text-sm">Copy</button>
                                </p>
                            </div>
                            <p><strong>Bank Name:</strong> {{ session('account_data')['bank_name'] ?? 'N/A' }}</p>
                            <p><strong>Account Name:</strong> {{ session('account_data')['account_name'] ?? Auth::user()->first_name . ' ' . Auth::user()->last_name }}</p>
                            <p><strong>Amount:</strong> <span class="text-brand-green font-bold">₦{{ number_format(session('account_data')['amount'] ?? 0, 2) }}</span></p>
                            @if(session('account_data')['expires_at'] ?? false)
                                <p class="text-xs text-gray-600 mt-2"><strong>Expires:</strong> {{ session('account_data')['expires_at'] }}</p>
                            @endif
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                            <p class="text-xs text-yellow-800">
                                <strong>Note:</strong> This is a one-time account. Once payment is received, it will be automatically credited to your wallet.
                            </p>
                        </div>
                        <div class="mt-4">
                            <button onclick="closeAndClearModal()"
                                    class="w-full bg-brand-green text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                Done
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Generate One-Time Virtual Account Form -->
                    <form id="generateAccountForm" action="{{ route('virtual-account.create') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-4">
                                Enter the amount you want to add to your wallet. We'll generate a one-time account number for the transfer.
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
                                <p class="text-sm text-blue-800">
                                    <strong>How it works:</strong> Generate a one-time account number, transfer the amount from your bank, and your wallet will be credited automatically.
                                </p>
                            </div>
                        </div>

                        <button type="submit" id="generateBtn"
                                class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-light-green transition-colors flex items-center justify-center">
                            Generate Account Number
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-4">
            <button onclick="window.location.href='{{ url('services') }}'" class="bg-gray-800 text-white font-bold py-3.5 rounded-xl shadow-sm hover:bg-gray-700 transition-colors">
                Get OTP
            </button>
            <button onclick="openSocialModal()" class="bg-gray-800 text-white font-bold py-3.5 rounded-xl shadow-sm hover:bg-gray-700 transition-colors">
                Socials
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
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Google_Voice_icon_%282020%29.svg" class="w-10 h-10" alt="Google Voice">
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
                    <button onclick="selectProvider('5sim')" 
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
                    <button onclick="selectProvider('smsbus')" 
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
                <a href="#" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center">
                        <img src="assets/sim-icon.svg" class="w-8 h-8 text-brand-green" alt="">
                    </div>
                    <span class="text-xs font-medium text-gray-700">eSim Renewal</span>
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

<script>
    function openSocialModal() {
        document.getElementById('socialModal').classList.remove('hidden');
    }

    function closeSocialModal() {
        document.getElementById('socialModal').classList.add('hidden');
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show temporary success message
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                if (button.textContent.includes('Copy')) {
                    const originalText = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.add('text-green-600');

                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('text-green-600');
                    }, 2000);
                }
            });
        }).catch(err => {
            console.error('Failed to copy: ', err);
            alert('Failed to copy. Please copy manually.');
        });
    }

    // Close virtual account card
    function closeVirtualAccountCard() {
        // Clear session data via AJAX
        fetch('{{ route("virtual-account.clear-session") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => {
            console.log('Session cleared');
            window.location.reload();
        }).catch(error => {
            console.error('Error clearing session:', error);
            window.location.reload();
        });
    }

    // Generate new virtual account
    function generateNewAccount() {
        document.getElementById('addFundsModal').classList.remove('hidden');
        document.getElementById('addFundsModal').classList.add('flex');
    }

    // Close modal and clear session
    function closeAndClearModal() {
        const modal = document.getElementById('addFundsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Clear session data via AJAX
        fetch('{{ route("virtual-account.clear-session") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(response => {
            console.log('Session cleared');
            window.location.reload();
        }).catch(error => {
            console.error('Error clearing session:', error);
            window.location.reload();
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addFundsBtn = document.getElementById('addFundsBtn');
        const modal = document.getElementById('addFundsModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const generateForm = document.getElementById('generateAccountForm');

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
                button.disabled = true;
                button.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generating...
                `;
            });
        }

        // Auto-open modal if account was just generated
        @if(session('account_data'))
            // Modal will open automatically if there's account data
        @endif
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
    if (provider === '5sim') {
        window.location.href = `/services/${selectedServiceCode}/5sim/countries`;
    } else {
        window.location.href = `/services/${selectedServiceId}/countries`;
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
