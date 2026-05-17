@extends('layouts.app')

@section('title', 'OTP Verification')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="https://i.ibb.co/6gT3YvJ/avatar.png" alt="User avatar" class="w-20 h-20 rounded-full" />
        </div>

        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Verify Enter</h1>
            <p class="mt-2 text-sm text-gray-600">Enter the OTP sent to your email</p>
        </div>

        <form id="otpForm" action="{{ route('otp.verify') }}" method="POST" class="mt-8 space-y-6">
            @csrf
            <input type="hidden" name="email" value="{{ session('email') }}">
            <div class="flex justify-center">
                <input
                    id="otpInput"
                    type="text"
                    maxlength="6"
                    pattern="\d*"
                    name="otp"
                    placeholder="OTP"
                    class="w-full px-3 py-3 text-sm tracking-widest bg-white text-black rounded-full md:rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                    required
                />
            </div>

            <button
                type="submit"
                id="submitBtn"
                class="w-full py-3 text-base font-semibold text-white transition-all duration-300 ease-in-out rounded-xl bg-gray-300 cursor-not-allowed"
                disabled
            >
                Verify
            </button>
            <div class="text-center">
                <a href="{{ route('login') }}" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Back to login</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const otpInput = document.getElementById('otpInput');
        const submitBtn = document.getElementById('submitBtn');

        otpInput.addEventListener('input', () => {
            const value = otpInput.value.trim();

            if (/^\d{6}$/.test(value)) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
                submitBtn.classList.add('bg-[#03624C]', 'hover:bg-opacity-90');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-[#03624C]', 'hover:bg-opacity-90');
            }
        });
    });
</script>
@endsection
