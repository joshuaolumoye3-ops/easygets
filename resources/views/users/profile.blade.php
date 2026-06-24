@extends('layouts.app')

@section('title', 'History')

@section('content')
<div class="max-w-md mx-auto bg-white mt-0 py-8 px-2">
    <div class="flex items-center space-x-4 mb-6">
        <img src="https://placehold.co/48x48/E6F5F1/0D5443?text={{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}" alt="{{ Auth::user()->username }}" class="w-12 h-12 rounded-full">
        <div>
            <h1 class="text-lg font-semibold">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h1>
            <p class="text-sm text-gray-500">{{ Auth::user()->username }} </p>
        </div>
    </div>

     <div class="bg-brand-green text-white rounded-xl p-5 shadow-lg">
            {{-- @if($wallet && $wallet->virtualAccounts->count() > 0) --}}
                <!-- User has a wallet and at least one virtual account -->
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm opacity-80">Wallet Balance</p>
                        <p class="text-3xl font-bold mt-1">₦{{ number_format($wallet->balance ?? 0, 2) }}</p>
                    </div>
                    <div class="flex flex-col items-end space-y-2">
                        <a href="transactions" class="text-sm">Transactions</a>
                        <a href="dashboard"
                                class="bg-white text-[#03624C] font-bold py-2 px-4 rounded-full text-sm hover:bg-white/30 transition-colors">
                            + Add Funds
                    </a>
                    </div>
                </div>
            {{-- @else --}}
                <!-- No account yet -->
                {{-- <div class="text-center">
                    <p>No wallet found.</p>
                    <button id="addFundsBtn"
                            data-has-account="false"
                            class="bg-white text-[#03624C] font-bold py-2 px-4 rounded-full text-sm hover:bg-white/30 transition-colors">
                        Create Wallet
                    </button>
                </div>
            @endif --}}
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

    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Account Information</h3>
        <div class="divide-y rounded-xl bg-brand-gray-light border border-gray-200 overflow-hidden">
            <a href="personal-information" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Personal Information</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <a href="pin" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Account Security</span>
                <span class="text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
        </div>
    </div>

    <div class="mb-6">
        <div class="divide-y bg-brand-gray-light rounded-xl border border-gray-200 overflow-hidden">
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Terms And Conditions</span>
                <span class="text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Privacy Policy</span>
                <span class="text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Help Center</span>
                <span class="text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>About Us</span>
                <span class="text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Frequently Asked Questions</span>
                <span class="text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
           <a 
                href="https://wa.me/2348024339657?text=Hello%20EasyGets%20Support" 
                target="_blank"
                class="flex justify-between items-center p-4 hover:bg-gray-50"
            >
                <span>Contact Us</span>
                <span class="text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
        </div>
    </div>

     <div class="text-center bg-brand-gray-light border border-gray-200 rounded-2xl mb-16 p-4 w-full">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="inline">
            @csrf
            <button 
                type="button" 
                id="logout-btn" 
                class="text-red-500 font-medium hover:underline"
            >
                Log Out
            </button>
        </form>
    </div>

    <script>
        document.getElementById('logout-btn').addEventListener('click', function () {
            if (confirm('Are you sure you want to log out?')) {
                document.getElementById('logout-form').submit();
            }
        });
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
</div>
@include('partials.footer')
@endsection
