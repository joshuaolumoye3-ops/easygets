@extends('layouts.app')

{{-- You can set a unique title for each page --}}
@section('title', 'Dashboard')

@section('content')
<!-- Main container that simulates a mobile screen on larger displays -->
<!-- The `pb-28` adds padding at the bottom to prevent content being hidden by the fixed footer -->
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
            @if($wallet && $wallet->virtualAccounts->count() > 0)
                <!-- User has a wallet and at least one virtual account -->
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm opacity-80">Wallet Balance</p>
                        <p class="text-3xl font-bold mt-1">₦{{ number_format($wallet->balance, 2) }}</p>
                    </div>
                    <div class="flex flex-col items-end space-y-2">
                        <a href="transactions" class="text-sm">Transactions</a>
                        <button id="addFundsBtn"
                                data-has-account="true"
                                class="bg-white text-[#03624C] font-bold py-2 px-4 rounded-full text-sm hover:bg-white/30 transition-colors">
                            + Add Funds
                        </button>
                    </div>
                </div>
            @else
                <!-- No account yet -->
                <div class="text-center">
                    <p>No wallet found.</p>
                    <button id="addFundsBtn"
                            data-has-account="false"
                            class="bg-white text-[#03624C] font-bold py-2 px-4 rounded-full text-sm hover:bg-white/30 transition-colors">
                        Create Wallet
                    </button>
                </div>
            @endif
        </div>

        <!-- BVN/NIN Input Modal -->
        <div id="bvnNinModal" class="fixed inset-0 bg-black/50 hidden flex justify-center items-center">
            <div class="bg-white p-6 rounded-lg w-full max-w-md relative">
                <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-800" onclick="closeModal('bvnNinModal')">✕</button>
                <h2 class="text-lg font-bold mb-4">Create a Virtual Account</h2>
                <form id="bvnNinForm" action="{{ route('wallets.createAccount') }}" method="POST">
                    @csrf
                    <div class="flex gap-4 mb-4">
                        <label class="flex-1">
                            <input type="radio" name="id_type" value="bvn" checked> Use BVN
                        </label>
                        <label class="flex-1">
                            <input type="radio" name="id_type" value="nin"> Use NIN
                        </label>
                    </div>
                    <input type="text" name="id_value" required
                        class="border rounded px-3 py-2 w-full"
                        placeholder="Enter your BVN or NIN">
                    <button type="submit"
                            class="mt-4 bg-brand-green text-white py-2 px-4 rounded-lg w-full">
                        Continue
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Details Modal -->
        @if($wallet && $wallet->virtualAccounts->count() > 0)
        <div id="accountDetailsModal" class="fixed inset-0 bg-black/50 hidden flex justify-center items-center">
            <div class="bg-white p-6 rounded-lg w-full max-w-md relative">
                <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-800" onclick="closeModal('accountDetailsModal')">✕</button>
                <h2 class="text-lg font-bold mb-4">Your Virtual Account Details</h2>
                @foreach($wallet->virtualAccounts as $account)
                    <div class="mb-4 p-3 border rounded-lg">
                        <p class="font-semibold">{{ $account->bank_name }}</p>
                        <p class="text-sm">Account Number: 
                            <span id="acct-num-{{ $account->id }}">{{ $account->account_number }}</span>
                            <button onclick="copyToClipboard('acct-num-{{ $account->id }}')" class="text-brand-green font-bold text-sm ml-2">Copy</button>
                        </p>
                        <p class="text-sm">Account Name: 
                            <span id="acct-num-{{ $account->id }}">{{ $account->customer_name }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

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
                <a href="#" class="text-sm font-semibold text-brand-green">See All</a>
            </div>
            <div class="grid grid-cols-4 gap-4 text-center">
                <!-- Quick Pick Item -->
                <a href="#" class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/2021_Facebook_icon.svg" class="w-10 h-10" alt="Facebook">
                    <span class="text-xs text-gray-600">Facebook</span>
                </a>
                <!-- Quick Pick Item -->
                 <a href="#" class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" class="w-10 h-10" alt="WhatsApp">
                    <span class="text-xs text-gray-600">WhatsApp</span>
                </a>
                 <!-- Quick Pick Item -->
                 <a href="#" class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/82/Telegram_logo.svg" class="w-10 h-10" alt="Telegram">
                    <span class="text-xs text-gray-600">Telegram</span>
                </a>
                 <!-- Quick Pick Item -->
                 <a href="#" class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Google_Voice_icon_%282020%29.svg" class="w-10 h-10" alt="Google Voice">
                    <span class="text-xs text-gray-600">Google Voice</span>
                </a>
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
                    <span class="text-xs font-medium text-gray-700">Internet</span>
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
                 <a href="#" class="flex flex-col items-center space-y-2">
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
</script>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied!');
    });
}

document.getElementById('addFundsBtn').addEventListener('click', function () {
    const hasAccount = this.getAttribute('data-has-account') === 'true';
    if (hasAccount) {
        document.getElementById('accountDetailsModal').classList.remove('hidden');
    } else {
        document.getElementById('bvnNinModal').classList.remove('hidden');
    }
});
</script>



@endsection
