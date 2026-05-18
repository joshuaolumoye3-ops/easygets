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
            <p id="step-subtitle" class="mt-2 text-12 text-black font-semibold leading-[100%] tracking-[0] font-[sans-serif]"></p>
        </div>

        {{--  Add form action to RegisterController --}}
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
                            placeholder="Email address" 
                            class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all" 
                            required 
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
                        <input type="text" name="username" placeholder="Username" 
                        class="form-input w-full max-w-xs px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all" 
                        required minlength="3" />
                        <p class="error-text text-red-600 pt-2 text-xs hidden">Username must be at least 3 characters.</p>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="#" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Back</a>
                </div>
            </div>

            <!-- Step 3: Contact Info -->
            <div class="step hidden" data-step="2">
                <div class="flex justify-center">
                    <div class="space-y-5 flex flex-col w-full max-w-xs">
                        <input
                            type="text"
                            name="first_name"
                            placeholder="First name"
                             class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            required
                        />
                        <input
                            type="text"
                            name="last_name"
                            placeholder="Last name"
                             class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                            required
                        />
                        <input
                            type="tel"
                            name="phone_number"
                            placeholder="Phone number"
                             class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"
                        />
                        <p class="error-text hidden text-xs text-red-600 mt-2">
                            Please fill out all fields.
                        </p>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="#" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Back</a>
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
                            class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"                    
                            required 
                        />
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            id="passwordConfirmInput"
                            placeholder="Confirm Password" 
                            class="form-input w-full px-4 py-3 bg-white text-gray-800 rounded-xl placeholder:text-gray-500 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all"                    
                            required 
                        />
                        <p class="error-text text-xs text-red-600 hidden">
                            Passwords must match and be at least 6 characters long.
                        </p>

                        <div class="flex items-center space-x-2 text-sm text-gray-700">
                            <input type="checkbox" name="terms" required class="form-checkbox text-[#03624C] rounded focus:ring-[#03624C]" />
                            <span>
                                Accept terms and conditions. 
                                <a href="#" class="text-blue-600 hover:underline">See here</a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="#" id="backLink" class="text-sm font-medium text-blue-600 transition-colors hover:text-gray-800">Back</a>
                </div>
            </div>

            <!-- Button -->
            <div class="pt-2">
                <button id="nextBtn" type="submit" class="w-full py-3 text-base font-semibold text-white transition-all duration-300 ease-in-out rounded-xl bg-gray-300 cursor-not-allowed" disabled>
                    Next
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-input {
        @apply w-full px-4 py-3 text-base text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#03624C] transition-all;
    }
    .form-input.error {
        @apply border-red-500 ring-1 ring-red-500;
    }
    .error-text {
        @apply mt-2 text-sm text-center text-red-600;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('signupForm');
        const steps = Array.from(document.querySelectorAll('.step'));
        const nextBtn = document.getElementById('nextBtn');
        const subtitle = document.getElementById('step-subtitle');
        const backLinks = document.querySelectorAll('#backLink');

        let currentStep = 0;

        const subheadings = [
            "Enter your email address",
            "Choose a unique username",
            "Enter your contact information",
            "Create a secure password"
        ];

        const validateEmail = (email) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email.trim());
        };

        const updateFormState = () => {
            steps.forEach((step, index) => {
                step.classList.toggle('hidden', index !== currentStep);
            });
            subtitle.textContent = subheadings[currentStep];
            nextBtn.textContent = currentStep === steps.length - 1 ? 'Sign Up' : 'Next';
            validateCurrentStep();
        };

        const validateCurrentStep = () => {
            const step = steps[currentStep];
            const inputs = Array.from(step.querySelectorAll('input[type!="checkbox"]:not([type="hidden"])'));
            let isValid = true;

            step.querySelector('.error-text')?.classList.add('hidden');
            inputs.forEach(input => input.classList.remove('error'));

            if (currentStep === 0) {
                const email = inputs[0].value.trim();
                if (!validateEmail(email)) {
                    isValid = false;
                }
            } else if (currentStep === 1) {
                const username = inputs[0].value.trim();
                if (username.length < 3) {
                    isValid = false;
                }
            } else if (currentStep === 2) {
                inputs.forEach(input => {
                    if (input.name !== 'phone_number' && input.value.trim() === '') {
                        isValid = false;
                    }
                });
            } else if (currentStep === 3) {
                const pass = document.getElementById('passwordInput').value;
                const confirm = document.getElementById('passwordConfirmInput').value;
                const termsChecked = step.querySelector('input[name="terms"]').checked;
                if (pass.length < 6 || pass !== confirm || !termsChecked) {
                    isValid = false;
                }
            }

            nextBtn.disabled = !isValid;
            if (isValid) {
                nextBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
                nextBtn.classList.add('bg-[#03624C]', 'hover:bg-opacity-90');
            } else {
                nextBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
                nextBtn.classList.remove('bg-[#03624C]', 'hover:bg-opacity-90');
            }
        };

        // ✅ KEY FIX: Use click handler on button instead of form submit
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            validateCurrentStep();
            if (nextBtn.disabled) return;

            if (currentStep < steps.length - 1) {
                // Not last step — advance
                currentStep++;
                updateFormState();
            } else {
                // Last step — submit the form
                form.submit();
            }
        });

        backLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentStep > 0) {
                    currentStep--;
                    updateFormState();
                }
            });
        });

        form.addEventListener('input', () => {
            setTimeout(validateCurrentStep, 0);
        });

        form.addEventListener('change', validateCurrentStep);

        updateFormState();
    });
</script>

@endsection
