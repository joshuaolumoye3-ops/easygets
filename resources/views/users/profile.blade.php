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

    <div class="bg-brand-green text-white rounded-2xl p-6 mb-6 flex flex-row items-center justify-between">
        <div>
            <p class="text-sm">Wallet Balance</p>
            <h2 class="text-3xl font-bold">₦50,000</h2>
        </div>
        <div class="flex flex-col items-end space-y-2">
            <a href="#" class="text-sm whitespace-nowrap">Transactions ></a>
            <button class="bg-white text-brand-green px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap">+ Add Funds</button>
        </div>
    </div>

    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-4">Account Information</h3>
        <div class="divide-y rounded-xl bg-brand-gray-light border border-gray-200 overflow-hidden">
            <a href="personal-information" class="flex justify-between items-center p-4 hover:bg-gray-50">
                <span>Personal Information</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
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
            <a href="#" class="flex justify-between items-center p-4 hover:bg-gray-50">
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

</div>
@include('partials.footer')
@endsection
