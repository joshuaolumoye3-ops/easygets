@extends('layouts.app')

@section('title', 'Confirm Payment')

@section('content')
{{-- {{dd($purchase)}} --}}
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm min-h-screen">
        
        <!-- Back Arrow -->
        <div class="w-full mb-6">
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                <span class="text-sm font-medium">Back</span>
            </a>
        </div>

        <!-- Header Text -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Confirm Payment</h1>
            <p class="text-gray-500">Review your purchase details</p>
        </div>

        <!-- Service Details Card -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6 mb-6 shadow-lg border border-green-200">
            <div class="flex items-center justify-between mb-6">
                <!-- Service Info -->
                <div class="flex items-center space-x-4">
                    @if(isset($purchase['service_logo']) && $purchase['service_logo'])
                        <div class="w-14 h-14 rounded-xl bg-white shadow-md flex items-center justify-center p-2">
                            <img src="{{ $purchase['service_logo'] }}" 
                                 alt="{{ $purchase['service_name'] ?? 'Service' }}" 
                                 class="w-10 h-10 object-contain"
                                 onerror="this.parentElement.innerHTML='<div class=\'w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold text-lg\'>{{ strtoupper(substr($purchase['service_name'] ?? 'S', 0, 2)) }}</div>'">
                        </div>
                    @else
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-md flex items-center justify-center text-white font-bold text-xl">
                            {{ strtoupper(substr($purchase['service_name'] ?? 'S', 0, 2)) }}
                        </div>
                    @endif
                    
                    <div>
                        <p class="text-xs text-green-700 font-medium uppercase tracking-wide">Service</p>
                        <h2 class="text-xl font-bold text-gray-900">{{ $purchase['service_name'] ?? 'Unknown Service' }}</h2>
                    </div>
                </div>
                
                <!-- Country Flag -->
                @if(isset($purchase['country_code']) && $purchase['country_code'])
                    <div class="flex-shrink-0">
                        <img src="https://flagcdn.com/w80/{{ strtolower($purchase['country_code']) }}.png" 
                             alt="{{ $purchase['country_name'] ?? 'Country' }}" 
                             class="w-16 h-16 rounded-xl object-cover shadow-lg border-2 border-white"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($purchase['country_name'] ?? 'UK', 0, 2)) }}&background=10b981&color=ffffff&size=64&rounded=true'">
                    </div>
                @endif
            </div>
            
            <!-- Country Name -->
            <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 mb-4">
                <p class="text-xs text-green-700 font-medium uppercase tracking-wide mb-1">Country</p>
                <p class="text-lg font-semibold text-gray-900">{{ $purchase['country_name'] ?? 'Unknown Country' }}</p>
            </div>
            
            <!-- Provider Info (if 5sim) -->
            @if(isset($purchase['provider']) && $purchase['provider'] === '5sim')
                @if(isset($purchase['operator_name']))
                    <div class="bg-white/60 backdrop-blur-sm rounded-xl p-4 mb-4">
                        <p class="text-xs text-green-700 font-medium uppercase tracking-wide mb-1">Operator</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $purchase['operator_name'] }}</p>
                    </div>
                @endif
            @endif
            
            <!-- Availability Status -->
            <div class="flex items-center justify-between">
                @if(isset($purchase['total_count']) && $purchase['total_count'] > 0)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-green-600 text-white shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ number_format($purchase['total_count']) }} available
                    </span>
                @elseif(isset($purchase['total_count']) && $purchase['total_count'] <= 0)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-yellow-500 text-white shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Limited availability
                    </span>
                @endif
                
                @if(isset($purchase['provider']))
                    <span class="text-xs text-green-700 font-medium uppercase tracking-wide">
                        {{ $purchase['provider'] === '5sim' ? 'Provider 1' : 'Provider 2' }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Amount Section -->
        <div class="bg-white rounded-2xl p-6 mb-6 shadow-lg border border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-500 font-medium uppercase tracking-wide mb-2">Total Amount</p>
                <div class="relative inline-block">
                    <p class="text-5xl font-bold text-gray-900">₦{{ number_format($purchase['cost'] ?? 0, 2) }}</p>
                    <div class="absolute -right-2 -top-2 w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                
                {{-- @if(isset($purchase['original_cost_usd']))
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                            <span>USD ${{ number_format($purchase['original_cost_usd'], 2) }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <span>NGN ₦{{ number_format($purchase['cost'] ?? 0, 2) }}</span>
                        </div>
                        @if(isset($purchase['exchange_rate']))
                            <p class="text-xs text-gray-400 mt-2">
                                Exchange rate: ₦{{ number_format($purchase['exchange_rate'], 2) }}
                            </p>
                        @endif
                    </div>
                @endif --}}
            </div>
        </div>

        <!-- Balance Check -->
        @auth
            <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Your Balance</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">₦{{ number_format($wallet->balance, 2) }}</p>
                    </div>
                    @if($wallet->balance >= ($purchase['cost'] ?? 0))
                        <div class="flex items-center space-x-2 text-green-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-semibold">Sufficient</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-2 text-red-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-semibold">Insufficient</span>
                        </div>
                    @endif
                </div>
                
                @if($wallet->balance < ($purchase['cost'] ?? 0))
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-red-600 mb-3">
                            <strong>You need ₦{{ number_format(($purchase['cost'] ?? 0) - $wallet->balance, 2) }} more</strong>
                        </p>
                        <a href="" class="inline-flex items-center justify-center w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2.5 px-4 rounded-lg transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Top Up Wallet
                        </a>
                    </div>
                @endif
            </div>
        @endauth

        <!-- Error Messages -->
        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            @foreach($errors->all() as $error)
                                {{ $error }}
                                @if(!$loop->last)<br>@endif
                            @endforeach
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- PIN Section -->
        <form method="POST" action="{{ route('payments.process') }}" id="paymentForm">
            @csrf
            
            <!-- Security token -->
            @if(isset($purchase['session_token']))
                <input type="hidden" name="session_token" value="{{ $purchase['session_token'] }}">
            @endif

            <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-200">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Enter Your PIN</h3>
                    <p class="text-sm text-gray-500">Enter your 4-digit transaction PIN</p>
                </div>

                <!-- PIN Input -->
                <div class="mb-6">
                    <input 
                        type="password" 
                        id="pin" 
                        name="pin" 
                        maxlength="4" 
                        class="w-full text-center text-3xl tracking-[1em] bg-gray-50 rounded-xl py-4 border-2 border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all @error('pin') border-red-500 ring-2 ring-red-200 @enderror"
                        style="text-indent: 1em; font-family: 'Courier New', monospace;"
                        autocomplete="off"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        required
                        autofocus
                        @if($wallet->balance < ($purchase['cost'] ?? 0)) disabled @endif
                        placeholder="••••"
                    >
                    @error('pin')
                        <p class="text-red-500 text-sm mt-2 text-center">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Proceed Button -->
                <button type="submit" 
                        id="proceedBtn"
                        class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 disabled:hover:shadow-lg"
                        @if($wallet->balance < ($purchase['cost'] ?? 0)) disabled @endif>
                    <span id="btnText" class="flex items-center justify-center">
                        @auth
                            @if($wallet->balance < ($purchase['cost'] ?? 0))
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                </svg>
                                Insufficient Balance - Top Up Required
                            @else
                                Confirm & Pay ₦{{ number_format($purchase['cost'] ?? 0, 2) }}
                            @endif
                        @else
                            Confirm Payment
                        @endauth
                    </span>
                    <svg id="btnSpinner" class="animate-spin h-5 w-5 text-white hidden mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>

                <p class="text-xs text-gray-500 text-center mt-4">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Your transaction is secure and encrypted
                </p>
            </div>
        </form>

        <!-- Session Timeout Warning -->
        @if(isset($purchase['timestamp']))
            <div id="timeoutWarning" class="mt-6 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-amber-800">Session Active</p>
                        <p class="text-sm text-amber-700 mt-1">
                            This session expires in <span id="timeoutCountdown" class="font-semibold">30:00</span>
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Form submission handling
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('proceedBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    if (btn && !btn.disabled) {
        btn.disabled = true;
        btnText.classList.add('hidden');
        btnSpinner.classList.remove('hidden');
    }
});

// PIN input handling
const pinInput = document.getElementById('pin');
if (pinInput) {
    pinInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
    });
    
    pinInput.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key) && e.key !== 'Enter' && e.key !== 'Backspace') {
            e.preventDefault();
        }
    });
}

// Session timeout countdown
@if(isset($purchase['timestamp']))
const startTime = {{ $purchase['timestamp'] }};
const sessionDuration = 30 * 60; // 30 minutes in seconds

function updateCountdown() {
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - startTime;
    const remaining = sessionDuration - elapsed;
    
    if (remaining <= 0) {
        window.location.href = "{{ route('services.list') }}";
        return;
    }
    
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    const countdownEl = document.getElementById('timeoutCountdown');
    
    if (countdownEl) {
        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Show warning when less than 5 minutes remain
        if (remaining <= 300) {
            document.getElementById('timeoutWarning')?.classList.remove('bg-amber-50', 'border-amber-500');
            document.getElementById('timeoutWarning')?.classList.add('bg-red-50', 'border-red-500');
        }
    }
}

updateCountdown();
setInterval(updateCountdown, 1000);
@endif
</script>



<style>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* PIN input styling */
input[type="password"]::placeholder {
    color: #D1D5DB;
    font-size: 2rem;
}

/* Smooth transitions */
button {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
@endsection