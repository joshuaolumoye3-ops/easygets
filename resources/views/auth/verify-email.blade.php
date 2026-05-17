@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')

<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="/easygets-logo.jpg" alt="Logo" class="w-36 h-36 rounded-full" />
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Verify Your Email</h1>
            <p class="mt-2 text-sm text-gray-600">We've sent a 6-digit verification code to<br><strong>{{ $user->email }}</strong></p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-600">{{ session('success') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('verify.email.submit') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="user_id" value="{{ $user->id }}" />

            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">Enter Verification Code</label>
                <input
                    type="text"
                    id="otp"
                    name="otp"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="000000"
                    class="w-full px-4 py-3 text-center text-2xl tracking-widest bg-white text-gray-800 rounded-xl border {{ $errors->has('otp') ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-300 focus:ring-2 focus:ring-[#03624C]' }} focus:outline-none transition-all"
                    required
                    autofocus
                />
                @error('otp')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full py-3 text-base font-semibold text-white bg-[#03624C] rounded-xl hover:bg-opacity-90 transition-all duration-300"
            >
                Verify Email
            </button>

            <div class="space-y-3 text-center">
                <p class="text-sm text-gray-600">Didn't receive the code?</p>

                <form method="POST" action="{{ route('verify.email.resend') }}" id="resendForm" class="inline">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}" />
                    <button
                        type="submit"
                        class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                    >
                        Send a new verification code
                    </button>
                </form>

                <div class="pt-2">
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800 transition-colors">
                        Back to Sign In
                    </a>
                </div>
            </div>
        </form>

        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-blue-700 text-center">
                <strong>Need help?</strong> Check your spam folder or contact support at support@easygets.com
            </p>
        </div>
    </div>
</div>

<script>
    // Auto-format OTP input (only digits, max 6)
    document.getElementById('otp').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
    });

    // Remove form action duplication - handle resend via separate form
    document.getElementById('resendForm').addEventListener('submit', function(e) {
        e.preventDefault();
        this.submit();
    });
</script>

@endsection
