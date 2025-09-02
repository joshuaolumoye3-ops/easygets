@extends('layouts.app')

{{-- The title for the page --}}
@section('title', 'Update Profile')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md">

        <!-- Back Arrow -->
        <div class="mb-6">
            <a href="profile" class="text-green-700 hover:text-green-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
        </div>

        <!-- Profile Header -->
        <div class="text-center mb-8">
            <img 
                class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-gray-200" 
                src="https://placehold.co/100x100/E6F5F1/4A4A4A?text={{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}" 
                alt="Adeleke Adetola's profile picture"
                onerror="this.onerror=null;this.src='https://placehold.co/100x100/cccccc/ffffff?text=U';"
            >
            <h1 class="text-2xl font-bold text-gray-800">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h1>
            <p class="text-gray-500">{{ Auth::user()->email }}</p>
        </div>

        <!-- Update Profile Form -->
        <div>
            
            {{-- You can point this form to your update route --}}
            {{-- <form action="{{ route('profile.update') }}" method="POST"> --}}
           <form action="{{ route('updateProfile.pin') }}" method="POST" class="max-w-md mx-auto bg-white shadow-md rounded-2xl p-8">
                @csrf

                <h2 class="text-2xl font-semibold text-gray-800 text-center mb-6">Create Transaction PIN</h2>
                <p class="text-gray-500 text-center mb-8 text-sm">
                    Your 4-digit PIN will be used to authorize secure transactions.
                </p>

                <div class="space-y-6">
                    <!-- PIN Field -->
                    <div>
                        <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">Enter 4-Digit PIN</label>
                        <input 
                            type="password" 
                            id="pin" 
                            name="pin" 
                            maxlength="4" 
                            class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin') border-red-500 @enderror"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{4}"
                            required
                            autofocus
                        >
                        @error('pin')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm PIN Field -->
                    <div>
                        <label for="pin_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm PIN</label>
                        <input 
                            type="password" 
                            id="pin_confirmation" 
                            name="pin_confirmation" 
                            maxlength="4" 
                            class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin_confirmation') border-red-500 @enderror"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{4}"
                            required
                        >
                        @error('pin_confirmation')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-10">
                    <button 
                        type="submit"
                        class="w-full bg-brand-green text-white text-lg font-semibold py-3 px-4 rounded-xl hover:bg-brand-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition duration-200"
                    >
                        Save PIN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
