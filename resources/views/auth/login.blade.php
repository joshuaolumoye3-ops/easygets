@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="easygets-logo.jpg" alt="User avatar" class="w-36 h-36 rounded-full" />
        </div>

        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Sign in</h1>
            <p class="mt-2 text-sm text-gray-600">Login in to your account</p>
        </div>
 
        <form id="otpForm" method="POST" action="{{ route('login.submit') }}" class="mt-8 space-y-6">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    @foreach ($errors->all() as $error)
                        <div class="text-sm">{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-yellow-50 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mb-4">
                    <div class="text-sm">{{ session('warning') }}</div>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <div class="text-sm">{{ session('success') }}</div>
                </div>
            @endif

            @if (session('info'))
                <div class="bg-blue-50 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-4">
                    <div class="text-sm">{{ session('info') }}</div>
                </div>
            @endif

            <div class="flex justify-center">
                <div class="space-y-5 flex flex-col w-full max-w-xs">
                    <input
                        type="text"
                        name="login"
                        placeholder="Email or username"
                        class="w-full px-3 py-3 text-sm tracking-widest bg-white text-black rounded-full md:rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                        required
                        autocomplete="text"
                    />
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        class="w-full px-3 py-3 text-sm tracking-widest bg-white text-black rounded-full md:rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                        required
                        autocomplete="current-password"
                    />
                </div>
            </div>

            <div class="text-center">
                <a href="#" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Forget password?</a>
            </div>

            <button
                type="submit"
                class="w-full py-3 text-base font-semibold bg-brand-green text-white transition-all duration-300 ease-in-out rounded-xl"
            >
                Login
            </button>
            <div class="text-center">
               Don't have an account? <a href="signup" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Sign up</a>
            </div>
        </form>
    </div>
</div>

@endsection
