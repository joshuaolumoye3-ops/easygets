@extends('layouts.app')

@section('title', 'Sign Up')

@section('content')

<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="easygets-logo.jpg" alt="User avatar" class="w-36 h-36 rounded-full" />
        </div>

        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Sign Up</h1>
            <p id="step-subtitle" class="mt-2 text-sm text-black font-semibold"></p>
        </div>

        <form id="signupForm"
              class="mt-8 space-y-6"
              novalidate
              action="{{ route('register.submit') }}"
              method="POST">
            @csrf

            <!-- Honeypot field (hidden from real users) -->
            <input type="text" name="website_url" style="display:none;" tabindex="-1" autocomplete="off" />

            <!-- Step 1: Email -->
            <div class="step" data-step="0">
                <div class="flex justify-center">
                    <div class="w-full max-w-xs">
                        <input
                            type="email"
                            name="email"
                            id="emailInput"
                            placeholder="Email address"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="email"
                        />
                        <p class="error-text text-xs text-red-600 mt-2 hidden">
                            Please enter a valid email address.
                        </p>
                    </div>
                </div>

                <div class="mt-6 text-center text-sm text-gray-700 space-y-2">
                    <div>
                        Already Signed Up?
                        <a href="#" class="text-blue-600 hover:underline">Verify Email</a>
                    </div>
                    <div>
                        Already Have An Account?
                        <a href="/" class="text-blue-600 hover:underline">Login Here</a>
                    </div>
                </div>
            </div>

            <!-- Step 2: Username -->
            <div class="step hidden" data-step="1">
                <div class="flex justify-center">
                    <div class="w-full max-w-xs">
                        <input
                            type="text"
                            name="username"
                            id="usernameInput"
                            placeholder="Username"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="username"
                        />
                        <p class="error-text text-red-600 pt-2 text-xs hidden">Username must be at least 3 characters.</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            <!-- Step 3: Contact Info -->
            <div class="step hidden" data-step="2">
                <div class="flex justify-center">
                    <div class="space-y-5 flex flex-col w-full max-w-xs">
                        <input
                            type="text"
                            name="first_name"
                            id="firstNameInput"
                            placeholder="First name"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="given-name"
                        />
                        <input
                            type="text"
                            name="last_name"
                            id="lastNameInput"
                            placeholder="Last name"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="family-name"
                        />
                        <input
                            type="tel"
                            name="phone_number"
                            id="phoneInput"
                            placeholder="Phone number (optional)"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="tel"
                        />
                        <p class="error-text hidden text-xs text-red-600">
                            Please fill out first and last name.
                        </p>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            <!-- Step 4: Password -->
            <div class="step hidden" data-step="3">
                <div class="flex justify-center">
                    <div class="space-y-3 flex flex-col w-full max-w-xs">
                        <input
                            type="password"
                            name="password"
                            id="passwordInput"
                            placeholder="Password"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="new-password"
                        />
                        <input
                            type="password"
                            name="password_confirmation"
                            id="passwordConfirmInput"
                            placeholder="Confirm Password"
                            class="step-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            autocomplete="new-password"
                        />
                        <p class="error-text text-xs text-red-600 hidden">
                            Passwords must match and be at least 6 characters long.
                        </p>

                        <div class="flex items-center space-x-2 text-sm text-gray-700">
                            <input type="checkbox" id="termsCheckbox" name="terms" class="form-checkbox text-[#03624C] rounded focus:ring-[#03624C]" />
                            <span>
                                Accept terms and conditions.
                                <a href="#" class="text-blue-600 hover:underline">See here</a>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="back-link text-sm font-medium text-blue-600 hover:text-gray-800">Back</a>
                </div>
            </div>

            <!-- Next / Submit Button -->
            <div class="pt-2">
                <button
                    id="nextBtn"
                    type="button"
                    class="w-full py-3 text-base font-semibold text-white transition-all duration-300 ease-in-out rounded-xl bg-gray-300 cursor-not-allowed"
                    disabled>
                    Next
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .step-input {
        display: block;
        width: 100%;
    }
    .step-input.input-error {
        border-color: #ef4444;
        box-shadow: 0 0 0 1px #ef4444;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var form        = document.getElementById('signupForm');
    var nextBtn     = document.getElementById('nextBtn');
    var subtitle    = document.getElementById('step-subtitle');
    var steps       = Array.from(document.querySelectorAll('.step'));
    var currentStep = 0;

    var subheadings = [
        "Enter your email address",
        "Choose a unique username",
        "Enter your contact information",
        "Create a secure password"
    ];

    /* ── helpers ─────────────────────────────────────────── */

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
            if (i === index) {
                s.classList.remove('hidden');
            } else {
                s.classList.add('hidden');
            }
        });
        subtitle.textContent = subheadings[index];
        nextBtn.textContent  = (index === steps.length - 1) ? 'Sign Up' : 'Next';
        validate();

        // focus the first input of the new step
        var first = steps[index].querySelector('input');
        if (first) { setTimeout(function(){ first.focus(); }, 50); }
    }

    /* ── validation ──────────────────────────────────────── */

    function validate() {
        var ok = false;

        if (currentStep === 0) {
            var email = document.getElementById('emailInput').value;
            ok = isValidEmail(email);

        } else if (currentStep === 1) {
            var username = document.getElementById('usernameInput').value.trim();
            ok = username.length >= 3;

        } else if (currentStep === 2) {
            var fn = document.getElementById('firstNameInput').value.trim();
            var ln = document.getElementById('lastNameInput').value.trim();
            ok = fn.length > 0 && ln.length > 0;

        } else if (currentStep === 3) {
            var pass    = document.getElementById('passwordInput').value;
            var confirm = document.getElementById('passwordConfirmInput').value;
            var terms   = document.getElementById('termsCheckbox').checked;
            ok = pass.length >= 6 && pass === confirm && terms;
        }

        setBtn(ok);
        return ok;
    }

    /* ── button click: advance or submit ─────────────────── */

    nextBtn.addEventListener('click', function () {
        if (!validate()) return;

        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        } else {
            // Final step — actually submit to Laravel
            form.submit();
        }
    });

    /* ── back links ──────────────────────────────────────── */

    document.querySelectorAll('.back-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    /* ── live validation on any input / change ───────────── */

    form.addEventListener('input',  function () { validate(); });
    form.addEventListener('change', function () { validate(); });

    /* ── initialise ──────────────────────────────────────── */

    showStep(0);
});
</script>

@endsection