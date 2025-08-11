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
            <h2 class="text-xl font-bold text-gray-800 mb-6">Update Profile</h2>
            
            {{-- You can point this form to your update route --}}
            {{-- <form action="{{ route('profile.update') }}" method="POST"> --}}
            <form action="{{ route('updateProfile.submit') }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="space-y-6">
                    <!-- First Name Field -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-600 mb-1">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            value="{{ old('first_name', Auth::user()->first_name) }}"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full md:rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        >
                    </div>

                    <!-- Last Name Field -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-600 mb-1">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            value="{{ old('last_name', Auth::user()->last_name) }}"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full md:rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        >
                    </div>

                    <!-- Phone Number Field -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-600 mb-1">Phone Number</label>
                        <input 
                            type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            value="{{ old('phone_number', Auth::user()->phone_number) }}"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full md:rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-32">
                    <button 
                        type="submit"
                        class="w-full bg-brand-green text-white font-bold py-3 px-4 rounded-full md:rounded-xl hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-700 transition duration-200"
                    >
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
