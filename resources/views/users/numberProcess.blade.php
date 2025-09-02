@extends('layouts.app')

@section('title', 'Confirm Payment')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">
        
        <!-- Back Arrow -->
        <div class="w-full mb-8">
            <a href="{{ url()->previous() }}" class="text-gray-700 hover:text-gray-900 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
        </div>

        <!-- Header Text -->
        <div class="text-center mb-8">
            <p class="text-md text-gray-500">Confirm</p>
            <p class="text-md text-gray-500">To</p>
            <h1 class="text-2xl font-bold text-gray-800">Pay For</h1>
        </div>

        <!-- Payment Details Section -->
        <div class="relative bg-green-50 rounded-xl pt-12 pb-6 mb-6 text-center">
            <!-- Flag -->
            @if(isset($purchase['flag']))
                <div class="absolute -top-6 left-1/2 -translate-x-1/2">
                    <img src="{{ $purchase['flag'] }}" 
                         alt="{{ $purchase['country_name'] ?? 'Country' }} Flag" 
                         class="w-12 h-12 rounded-full border-4 border-white shadow-md object-cover"
                         onerror="this.src='https://via.placeholder.com/48?text={{ substr($purchase['country_name'] ?? 'UK', 0, 2) }}'">
                </div>
            @endif
            
            <!-- Service Logo (if available) -->
            @if(isset($purchase['service_logo']) && $purchase['service_logo'])
                <div class="absolute -top-3 right-6">
                    <img src="{{ $purchase['service_logo'] }}" 
                         alt="{{ $purchase['service_name'] ?? 'Service' }} Logo" 
                         class="w-8 h-8 rounded-lg border-2 border-white shadow-sm object-cover"
                         onerror="this.style.display='none'">
                </div>
            @endif
            
            <p class="text-sm text-gray-600">Service</p>
            <p class="font-semibold text-xl text-gray-900 mt-1">{{ $purchase['service_name'] ?? 'Unknown Service' }}</p>
            <p class="text-md text-gray-500 mt-1">{{ $purchase['country_name'] ?? 'Unknown Country' }}</p>
            
            <!-- Availability Status -->
            @if(isset($purchase['total_count']) && $purchase['total_count'] > 0)
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ $purchase['total_count'] }} numbers available
                    </span>
                </div>
            @elseif(isset($purchase['total_count']) && $purchase['total_count'] <= 0)
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Limited availability
                    </span>
                </div>
            @endif
        </div>

        <!-- Amount Section -->
        <div class="bg-gray-100 rounded-xl p-6 mb-6 text-center">
            <p class="text-md text-gray-500">Amount</p>
            <p class="text-4xl font-bold text-gray-900 mt-1">₦{{ number_format($purchase['cost'] ?? 0, 2) }}</p>
            @if(isset($purchase['original_cost_usd']))
                <p class="text-sm text-gray-400 mt-2">
                    USD ${{ number_format($purchase['original_cost_usd'], 2) }} 
                    @if(isset($purchase['exchange_rate']))
                        (Rate: ₦{{ number_format($purchase['exchange_rate'], 2) }})
                    @endif
                </p>
            @endif
        </div>

        <!-- Balance Check -->
        @auth
            @if(auth()->user()->wallet->balance < ($purchase['cost'] ?? 0))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>Insufficient Balance:</strong> You need ₦{{ number_format(($purchase['cost'] ?? 0) - auth()->user()->balance, 2) }} more to complete this purchase.
                                <a href="" class="underline font-medium">Top up your wallet</a>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Error Messages -->
        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                {{ $error }}<br>
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

            <div class="text-center">
                <p class="text-md text-gray-500 mb-4">Enter PIN to Proceed</p>
                <!-- PIN Input -->
                <div class="my-6">
                    <input 
                        type="password" 
                        id="pin" 
                        name="pin" 
                        maxlength="4" 
                        class="w-1/2 mx-auto text-center text-4xl tracking-[0.5em] bg-gray-100 rounded-lg p-2 border-0 focus:ring-2 focus:ring-brand-green focus:outline-none @error('pin') ring-2 ring-red-500 @enderror"
                        style="text-indent: 0.5em;"
                        autocomplete="off"
                        inputmode="numeric"
                        pattern="[0-9]{4}"
                        required
                        autofocus
                        @if(auth()->user()->wallet->balance < ($purchase['cost'] ?? 0)) disabled @endif
                    >
                    @error('pin')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Proceed Button -->
                <button type="submit" 
                        id="proceedBtn"
                        class="w-full bg-brand-green hover:bg-green-800 text-white font-bold py-3 px-4 rounded-xl transition duration-300 mt-4 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(auth()->user()->wallet->balance < ($purchase['cost'] ?? 0)) disabled @endif>
                    <span id="btnText">
                        @auth
                            @if(auth()->user()->balance < ($purchase['cost'] ?? 0))
                                Insufficient Balance
                            @else
                                Proceed
                            @endif
                        @else
                            Proceed
                        @endauth
                    </span>
                    <svg id="btnSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Session Timeout Warning -->
        <div id="timeoutWarning" class="mt-6 p-4 bg-brand-green border border-amber-200 rounded-lg hidden">
            <p class="text-sm text-white">
                <strong>Session Timeout:</strong> This session will expire in <span id="timeoutCountdown">30:00</span> minutes.
            </p>
        </div>
    </div>
</div>
@endsection