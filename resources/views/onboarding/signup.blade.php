@extends('layouts.app')
@section('title', 'Sign Up')
@section('content')

<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="easygets-logo.jpg" alt="Logo" class="w-36 h-36 rounded-full" />
        </div>

        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Sign Up</h1>
            <p id="step-subtitle" class="mt-2 text-sm text-black font-semibold"></p>
        </div>

        {{-- Server-side validation errors --}}
        @if ($errors->any())
            <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4">
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form id="signupForm" class="mt-8 space-y-6" novalidate
              action="{{ route('register.submit') }}" method="POST">
            @csrf

            {{-- Honeypot --}}
            <input type="text" name="website_url" style="display:none;" tabindex="-1" autocomplete="off" />

            {{-- Step 1: Email --}}
            <div class="step" data-step="0">
                <div class="flex justify-center">
                    <div class="w-full max-w-xs">
                        <input type="email" name="email" id="emailInput" placeholder="Email address"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="email" value="{{ old('email') }}" />
                        <p class="error-text text-xs text-red-600 mt-2 hidden">Please enter a valid email address.</p>
                    </div>
                </div>
                <div class="mt-6 text-center text-sm text-gray-700 space-y-2">
                    <div>Already Signed Up? <a href="#" class="text-blue-600 hover:underline">Verify Email</a></div>
                    <div>Already Have An Account? <a href="/" class="text-blue-600 hover:underline">Login Here</a></div>
                </div>
            </div>

            {{-- Step 2: Username --}}
            <div class="step hidden" data-step="1">
                <div class="flex justify-center">
                    <div class="w-full max-w-xs">
                        <input type="text" name="username" id="usernameInput" placeholder="Username"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="username" value="{{ old('username') }}" />
                        <p class="error-text text-red-600 pt-2 text-xs hidden">Username must be at least 3 characters.</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            {{-- Step 3: Contact Info --}}
            <div class="step hidden" data-step="2">
                <div class="flex justify-center">
                    <div class="space-y-5 flex flex-col w-full max-w-xs">
                        <input type="text" name="first_name" id="firstNameInput" placeholder="First name"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="given-name" value="{{ old('first_name') }}" />
                        <input type="text" name="last_name" id="lastNameInput" placeholder="Last name"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="family-name" value="{{ old('last_name') }}" />

                        {{-- Phone — now REQUIRED --}}
                        <div>
                            <input type="tel" name="phone_number" id="phoneInput"
                                placeholder="Phone number"
                                class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                                autocomplete="tel" value="{{ old('phone_number') }}" />
                            <p class="text-xs text-gray-400 mt-1 pl-1">Required — used for account recovery</p>
                        </div>

                        <p class="error-text hidden text-xs text-red-600">Please fill out first name, last name, and phone number.</p>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            {{-- Step 4: Password --}}
            <div class="step hidden" data-step="3">
                <div class="flex justify-center">
                    <div class="space-y-3 flex flex-col w-full max-w-xs">

                        {{-- Password --}}
                        <div class="relative w-full">
                            <input type="password" name="password" id="passwordInput" placeholder="Password"
                                class="step-input w-full px-4 py-3 pr-10 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                                autocomplete="new-password" />
                            <button type="button" onclick="togglePw('passwordInput','eyeOpen1','eyeClosed1')"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                aria-label="Toggle password visibility">
                                <svg id="eyeOpen1" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeClosed1" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.168-3.411M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="relative w-full">
                            <input type="password" name="password_confirmation" id="passwordConfirmInput" placeholder="Confirm Password"
                                class="step-input w-full px-4 py-3 pr-10 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                                autocomplete="new-password" />
                            <button type="button" onclick="togglePw('passwordConfirmInput','eyeOpen2','eyeClosed2')"
                                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                aria-label="Toggle confirm password visibility">
                                <svg id="eyeOpen2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeClosed2" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.168-3.411M9.88 9.88a3 3 0 104.243 4.243M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>

                        <p class="error-text text-xs text-red-600 hidden">Passwords must match and be at least 6 characters.</p>
                        <div class="flex items-center space-x-2 text-sm text-gray-700">
                            <input type="checkbox" id="termsCheckbox" name="terms" class="form-checkbox text-[#03624C] rounded focus:ring-[#03624C]" />
                            <span>Accept terms and conditions. <a href="#" class="text-blue-600 hover:underline">See here</a></span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            {{-- Step 5: Referral Code (Optional) --}}
            <div class="step hidden" data-step="4">
                <div class="flex justify-center">
                    <div class="w-full max-w-xs">
                        <p class="text-sm text-gray-500 mb-3 text-center">
                            Have a referral code? Enter it below — it's <strong>optional</strong>.
                        </p>

                        <div class="relative">
                            <input type="text" name="referral_code" id="referralCodeInput"
                                placeholder="Enter referral username"
                                class="step-input w-full px-4 py-3 pr-12 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                                autocomplete="off" value="{{ old('referral_code') }}" />

                            {{-- Status indicators --}}
                            <div id="referralSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                            <div id="referralCheck" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-green-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div id="referralX" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-red-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </div>

                        <p id="referralMsg" class="text-xs mt-2 text-center text-gray-500"></p>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            {{-- Next / Submit Button --}}
            <div class="pt-2">
                <button id="nextBtn" type="button"
                    class="w-full py-3 text-base font-semibold text-white transition-all duration-300 ease-in-out rounded-xl bg-gray-300 cursor-not-allowed"
                    disabled>
                    Next
                </button>
                <button id="hiddenSubmitBtn" type="submit" class="hidden"></button>
            </div>
        </form>
    </div>
</div>

<style>
.step-input { display: block; width: 100%; }
.step-input.input-error { border-color: #ef4444; box-shadow: 0 0 0 1px #ef4444; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF        = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var BASE        = '{{ url('/') }}';
    var form        = document.getElementById('signupForm');
    var nextBtn     = document.getElementById('nextBtn');
    var subtitle    = document.getElementById('step-subtitle');
    var steps       = Array.from(document.querySelectorAll('.step'));
    var currentStep = 0;

    var referralValid = null; // null=empty/unchecked, true=valid, false=invalid
    var referralTimer = null;

    var subheadings = [
        "Enter your email address",
        "Choose a unique username",
        "Enter your contact information",
        "Create a secure password",
        "Got a referral code? (optional)"
    ];

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim());
    }

    function setBtn(enabled) {
        nextBtn.disabled = !enabled;
        if (enabled) {
            nextBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            nextBtn.classList.add('bg-[#03624C]', 'hover:bg-opacity-90', 'cursor-pointer');
        } else {
            nextBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
            nextBtn.classList.remove('bg-[#03624C]', 'hover:bg-opacity-90', 'cursor-pointer');
        }
    }

    function showStep(index) {
        steps.forEach(function (s, i) {
            s.classList.toggle('hidden', i !== index);
        });
        subtitle.textContent = subheadings[index];
        nextBtn.textContent  = (index === steps.length - 1) ? 'Create Account' : 'Next';
        validate();
        var first = steps[index].querySelector('input');
        if (first) setTimeout(function () { first.focus(); }, 50);
    }

    function validate() {
        var ok = false;

        if (currentStep === 0) {
            ok = isValidEmail(document.getElementById('emailInput').value);

        } else if (currentStep === 1) {
            ok = document.getElementById('usernameInput').value.trim().length >= 3;

        } else if (currentStep === 2) {
            var fn    = document.getElementById('firstNameInput').value.trim();
            var ln    = document.getElementById('lastNameInput').value.trim();
            var phone = document.getElementById('phoneInput').value.trim();
            // Phone is now required — all three must be filled
            ok = fn.length > 0 && ln.length > 0 && phone.length >= 7;

        } else if (currentStep === 3) {
            var pass    = document.getElementById('passwordInput').value;
            var confirm = document.getElementById('passwordConfirmInput').value;
            var terms   = document.getElementById('termsCheckbox').checked;
            ok = pass.length >= 6 && pass === confirm && terms;

        } else if (currentStep === 4) {
            var code = document.getElementById('referralCodeInput').value.trim();
            if (!code) {
                ok = true;                     // empty = skip, perfectly fine
            } else if (referralValid === true) {
                ok = true;                     // typed a valid code
            } else {
                ok = false;                    // invalid or still checking
            }
        }

        setBtn(ok);
        return ok;
    }

    // ── Referral UI helpers ───────────────────────────────────
    function resetReferralUI() {
        document.getElementById('referralSpinner').classList.add('hidden');
        document.getElementById('referralCheck').classList.add('hidden');
        document.getElementById('referralX').classList.add('hidden');
        var msg = document.getElementById('referralMsg');
        msg.textContent = '';
        msg.className   = 'text-xs mt-2 text-center text-gray-500';
        var inp = document.getElementById('referralCodeInput');
        inp.classList.remove('border-green-500', 'border-red-400');
    }

    function doReferralCheck(code) {
        document.getElementById('referralSpinner').classList.remove('hidden');
        document.getElementById('referralCheck').classList.add('hidden');
        document.getElementById('referralX').classList.add('hidden');
        referralValid = null;
        validate();

        fetch(BASE + '/app/check-referral?code=' + encodeURIComponent(code), {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('referralSpinner').classList.add('hidden');
            var inp = document.getElementById('referralCodeInput');
            var msg = document.getElementById('referralMsg');
            if (data.valid) {
                referralValid = true;
                document.getElementById('referralCheck').classList.remove('hidden');
                msg.textContent = data.message;
                msg.className   = 'text-xs mt-2 text-center text-green-600 font-semibold';
                inp.classList.remove('border-red-400');
                inp.classList.add('border-green-500');
            } else {
                referralValid = false;
                document.getElementById('referralX').classList.remove('hidden');
                msg.textContent = data.message;
                msg.className   = 'text-xs mt-2 text-center text-red-600';
                inp.classList.remove('border-green-500');
                inp.classList.add('border-red-400');
            }
            validate();
        })
        .catch(function () {
            document.getElementById('referralSpinner').classList.add('hidden');
            referralValid = null;
            validate();
        });
    }

    // ── Referral input: live-check as user types ──────────────
    var referralInput = document.getElementById('referralCodeInput');
    if (referralInput) {
        referralInput.addEventListener('input', function () {
            clearTimeout(referralTimer);
            var code = this.value.trim();
            resetReferralUI();

            if (!code) {
                referralValid = null;
                validate();
                return;
            }

            referralTimer = setTimeout(function () { doReferralCheck(code); }, 500);
        });
    }

    // ── Auto-fill referral code from ?ref= URL param ──────────
    // Called the moment the user lands on step 4 via the Next button.
    function prefillReferralFromUrl() {
        var ref = new URLSearchParams(window.location.search).get('ref');
        if (!ref) return;

        var inp = document.getElementById('referralCodeInput');
        // Only fill if the server hasn't already restored an old() value
        if (!inp.value) {
            inp.value = ref;
        }
        // Fire the validation check immediately so the green tick appears
        doReferralCheck(inp.value.trim());
    }

    // ── Next / Submit button ──────────────────────────────────
    nextBtn.addEventListener('click', function () {
        if (!validate()) return;

        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);

            // When arriving at the referral step, auto-fill from URL
            if (currentStep === 4) {
                prefillReferralFromUrl();
            }
        } else {
            // Final step — submit
            var token    = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            var csrfInp  = form.querySelector('input[name="_token"]');
            if (token && csrfInp) csrfInp.value = token;

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                document.getElementById('hiddenSubmitBtn').click();
            }
        }
    });

    // ── Back links ────────────────────────────────────────────
    document.querySelectorAll('.back-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (currentStep > 0) { currentStep--; showStep(currentStep); }
        });
    });

    form.addEventListener('input',  validate);
    form.addEventListener('change', validate);

    // ── On server validation error, restore the correct step ─
    @if($errors->has('referral_code'))
        currentStep = 4;
        showStep(currentStep);
        prefillReferralFromUrl();
        var existingCode = document.getElementById('referralCodeInput').value.trim();
        if (existingCode) { doReferralCheck(existingCode); }
    @elseif($errors->has('password') || $errors->has('password_confirmation'))
        currentStep = 3; showStep(currentStep);
    @elseif($errors->has('first_name') || $errors->has('last_name') || $errors->has('phone_number'))
        currentStep = 2; showStep(currentStep);
    @elseif($errors->has('username'))
        currentStep = 1; showStep(currentStep);
    @else
        showStep(currentStep);
    @endif
});

function togglePw(inputId, openId, closedId) {
    var input    = document.getElementById(inputId);
    var isHidden = input.type === 'password';
    input.type   = isHidden ? 'text' : 'password';
    document.getElementById(openId).classList.toggle('hidden', isHidden);
    document.getElementById(closedId).classList.toggle('hidden', !isHidden);
}
</script>

@endsection