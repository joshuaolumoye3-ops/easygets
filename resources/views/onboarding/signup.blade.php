@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Create Account</h1>
            <p class="mt-2 text-sm text-gray-500">Join thousands already using EasyGets</p>
        </div>

        @if ($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if (session('warning'))
        <div class="mb-5 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-sm text-yellow-800">{{ session('warning') }}</p>
        </div>
        @endif

        <form action="{{ route('register.submit') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Honeypot --}}
            <input type="text" name="website_url" class="hidden" tabindex="-1" autocomplete="off">

            {{-- Username --}}
            <div>
                <label for="username" class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                <input type="text" id="username" name="username"
                       value="{{ old('username') }}"
                       placeholder="e.g. johndoe123"
                       required
                       class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('username') border-red-400 bg-red-50 @enderror">
                @error('username')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- First & Last Name --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1.5">First Name</label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name') }}"
                           placeholder="John"
                           required
                           class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('first_name') border-red-400 bg-red-50 @enderror">
                    @error('first_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Last Name</label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name') }}"
                           placeholder="Doe"
                           required
                           class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('last_name') border-red-400 bg-red-50 @enderror">
                    @error('last_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       placeholder="john@example.com"
                       required
                       class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('email') border-red-400 bg-red-50 @enderror">
                @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Phone Number — REQUIRED --}}
            <div>
                <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="phone_number" name="phone_number"
                       value="{{ old('phone_number') }}"
                       placeholder="e.g. 08012345678"
                       required
                       inputmode="tel"
                       class="w-full px-4 py-3 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('phone_number') border-red-400 bg-red-50 @enderror">
                @error('phone_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 characters"
                           required minlength="6"
                           class="w-full px-4 py-3 pr-11 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('password') border-red-400 bg-red-50 @enderror">
                    <button type="button" onclick="togglePwd('password', 'eyePwd')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg id="eyePwd" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           placeholder="Re-enter password"
                           required
                           class="w-full px-4 py-3 pr-11 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all">
                    <button type="button" onclick="togglePwd('password_confirmation', 'eyeConfirm')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg id="eyeConfirm" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Referral Code --}}
            <div>
                <label for="referral_code" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Referral Code <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative">
                    <input type="text" id="referral_code" name="referral_code"
                           value="{{ old('referral_code', request()->query('ref')) }}"
                           placeholder="Enter referral username"
                           class="w-full px-4 py-3 pr-10 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] focus:border-transparent transition-all @error('referral_code') border-red-400 bg-red-50 @enderror">
                    {{-- Status indicator --}}
                    <span id="refStatusIcon" class="absolute right-3 top-1/2 -translate-y-1/2 hidden"></span>
                </div>
                <p id="refFeedback" class="text-xs mt-1 hidden"></p>
                @error('referral_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="w-full py-3.5 text-base font-bold text-white rounded-xl transition-all duration-200 active:scale-95 mt-2"
                    style="background: linear-gradient(135deg, #03624C, #047857); box-shadow: 0 4px 14px rgba(3,98,76,0.35);">
                Create Account
            </button>

            <p class="text-center text-sm text-gray-500 pt-1">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-[#03624C] hover:underline">Sign in</a>
            </p>
        </form>
    </div>
</div>

<script>
// ── Toggle password visibility ────────────────────────────
function togglePwd(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

// ── Auto-fill referral code from URL ?ref= ────────────────
document.addEventListener('DOMContentLoaded', function () {
    var urlParams = new URLSearchParams(window.location.search);
    var ref = urlParams.get('ref');
    var refInput = document.getElementById('referral_code');

    if (ref && refInput && !refInput.value) {
        refInput.value = ref;
        validateReferralCode(ref);
    }

    // Live validation as user types
    var debounceTimer;
    refInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var code = refInput.value.trim();
        if (!code) {
            hideFeedback();
            return;
        }
        debounceTimer = setTimeout(function () { validateReferralCode(code); }, 500);
    });
});

function validateReferralCode(code) {
    fetch('{{ route('referral.check') }}?code=' + encodeURIComponent(code), {
        headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        var icon     = document.getElementById('refStatusIcon');
        var feedback = document.getElementById('refFeedback');
        icon.classList.remove('hidden');
        feedback.classList.remove('hidden');

        if (data.valid) {
            icon.textContent     = '✅';
            feedback.textContent = data.message;
            feedback.className   = 'text-xs mt-1 text-green-600';
        } else {
            icon.textContent     = '❌';
            feedback.textContent = data.message;
            feedback.className   = 'text-xs mt-1 text-red-500';
        }
    })
    .catch(function () { hideFeedback(); });
}

function hideFeedback() {
    document.getElementById('refStatusIcon').classList.add('hidden');
    document.getElementById('refFeedback').classList.add('hidden');
}
</script>
@endsection