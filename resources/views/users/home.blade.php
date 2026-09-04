@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-brand-gray-light mt-0 pt-0">

    <!-- Header Section -->
    <header class="p-4 flex items-center space-x-3 bg-white">
        <img src="https://placehold.co/48x48/E6F5F1/0D5443?text={{ strtoupper(substr(Auth::user()->first_name, 0, 1)) }}"
            alt="User Avatar" class="w-12 h-12 rounded-full">
        <div>
            <p class="text-sm text-gray-500">Welcome Back,</p>
            <h1 class="font-bold text-lg text-gray-800">
                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
            </h1>
        </div>
    </header>

    @include('partials.announcement')

    <div class="p-4 space-y-5">

        @php
            $authUser      = Auth::user();
            $refBal        = $authUser->referral_balance ?? 0;
            $isPartner     = (bool) ($authUser->is_partner ?? false);
            $minWithdrawal = $authUser->partner_min_withdrawal ?? 0;
            $pendingW      = $isPartner
                ? \App\Models\PartnerWithdrawal::where('user_id', $authUser->id)
                    ->where('status', 'pending')->exists()
                : false;
        @endphp

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- COMBINED BALANCE CARD                              --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="rounded-2xl overflow-hidden shadow-xl"
             style="background: linear-gradient(135deg, #03624C 0%, #047857 55%, #065f46 100%);">

            {{-- Top half: wallet balance --}}
            <div class="px-5 pt-5 pb-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-medium" style="color:rgba(255,255,255,0.65);">Wallet Balance</p>
                            {{-- Eye toggle button --}}
                            <button id="toggleBalanceBtn"
                                    onclick="toggleBalanceVisibility()"
                                    class="flex items-center justify-center rounded-full transition-all active:scale-90"
                                    style="background:rgba(255,255,255,0.12);width:26px;height:26px;border:1px solid rgba(255,255,255,0.2);"
                                    title="Toggle balance visibility"
                                    aria-label="Toggle balance visibility">
                                {{-- Eye open icon --}}
                                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.75)" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{-- Eye closed icon --}}
                                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 hidden" fill="none" viewBox="0 0 24 24" stroke="rgba(255,255,255,0.75)" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Wallet balance display --}}
                        <div id="walletBalanceVisible" class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                            ₦{{ number_format($wallet->balance ?? 0, 2) }}
                        </div>
                        <div id="walletBalanceHidden" class="hidden text-2xl sm:text-4xl font-extrabold text-white tracking-tight select-none" style="letter-spacing:0.15em;">
                            ••••••
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2 mt-1 flex-shrink-0">
                        <a href="transactions"
                           class="text-xs font-semibold underline underline-offset-2"
                           style="color:rgba(255,255,255,0.65);">Transactions</a>
                        <button id="addFundsBtn"
                                class="bg-white font-bold py-2 px-5 rounded-full text-sm shadow-md active:scale-95 transition-transform"
                                style="color:#03624C;">
                            + Add Funds
                        </button>
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div class="mx-5" style="height:1px;background:rgba(255,255,255,0.18);"></div>

            {{-- Bottom half: referral / partner balance --}}
            <div class="px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest"
                           style="color:rgba(255,255,255,0.5);">
                            {{ $isPartner ? 'Partner Balance' : 'Referral Balance' }}
                        </p>

                        {{-- Referral balance display --}}
                        <div id="refBalanceVisible" class="text-lg sm:text-2xl font-bold text-white mt-0.5">
                            ₦{{ number_format($refBal, 2) }}
                        </div>
                        <div id="refBalanceHidden" class="hidden text-lg sm:text-2xl font-bold text-white mt-0.5 select-none" style="letter-spacing:0.15em;">
                            ••••••
                        </div>

                        <p class="text-xs mt-0.5 truncate" style="color:rgba(255,255,255,0.4);">
                            Ref code:&nbsp;<span class="font-mono font-bold text-yellow-300">{{ $authUser->username }}</span>
                        </p>
                    </div>

                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        @if ($isPartner)
                            @if ($pendingW)
                                <span class="text-xs px-3 py-1.5 rounded-full font-semibold whitespace-nowrap"
                                      style="background:rgba(251,191,36,0.2);color:#fde68a;border:1px solid rgba(251,191,36,0.35);">
                                    ⏳ Withdrawal pending
                                </span>
                            @else
                                <button id="openWithdrawalModal"
                                        class="text-xs font-bold px-4 py-2 rounded-full whitespace-nowrap active:scale-95 transition-transform"
                                        style="background:rgba(4, 4, 4, 0.4);color:#fff;box-shadow:0 4px 12px rgba(4, 4, 4, 0.4);">
                                    Withdraw
                                </button>
                            @endif
                        @else
                            <span class="text-xs px-3 py-1.5 rounded-full font-semibold whitespace-nowrap"
                                  style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.55);border:1px solid rgba(255,255,255,0.18);">
                                Invite &amp; earn
                            </span>
                        @endif

                        <button onclick="copyReferralLink()" id="copyRefBtn"
                                class="text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap active:scale-95 transition-transform"
                                style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.2);">
                            📋 Copy link
                        </button>
                    </div>
                </div>

                @if (!$isPartner && $refBal > 0)
                <p class="text-xs mt-3 pt-3" style="color:rgba(255,255,255,0.35);border-top:1px solid rgba(255,255,255,0.12);">
                    Referral earnings accumulate here. Contact support to learn about the Partner programme.
                </p>
                @endif
            </div>
        </div>
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- END COMBINED BALANCE CARD                          --}}
        {{-- ═══════════════════════════════════════════════════ --}}

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <div class="flex-1"><p class="text-sm font-medium text-green-800">{{ session('success') }}</p></div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-500 hover:text-green-700 ml-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <div class="flex-1"><p class="text-sm font-medium text-blue-800">{{ session('info') }}</p></div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-blue-500 hover:text-blue-700 ml-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <div class="flex-1"><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- ADD FUNDS MODAL — Korapay: one-time checkout + dedicated account --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div id="addFundsModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50 p-4">
            <div class="bg-white rounded-xl w-full max-w-md relative shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold leading-none z-10">✕</button>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-100 flex-shrink-0">
                    <button type="button" id="tabBtnOnetime"
                            class="flex-1 py-3 text-sm font-bold text-brand-green border-b-2 border-brand-green transition-colors cursor-default">
                        One-time Payment
                    </button>
                </div>

                <div class="overflow-y-auto">
                    {{-- ── Tab 1: One-time checkout ─────────────────────────────── --}}
                    <div id="tabOnetime" class="p-6">
                        <h2 class="text-lg font-bold mb-4 text-gray-800">Fund via One-time Payment</h2>
                        <form id="korapayCheckoutForm" action="{{ route('korapay.checkout.initiate') }}" method="POST">
                            @csrf
                            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start space-x-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <div>
                                    <p class="text-sm text-blue-800 font-semibold mb-1">How it works:</p>
                                    <ul class="text-xs text-blue-700 space-y-1">
                                        <li>• Redirected to Korapay's secure payment page</li>
                                        <li>• Pay with card, bank transfer, or USSD</li>
                                        <li>• Wallet credited automatically once confirmed</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₦)</label>
                                <input type="number" id="amount" name="amount" required min="100" step="0.01"
                                       placeholder="Enter amount e.g. 1000"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                                <p class="text-xs text-gray-500 mt-1">Minimum amount: ₦100</p>
                            </div>
                            <button type="submit" id="checkoutSubmitBtn"
                                    class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Proceed to Payment
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Partner Withdrawal Modal ─────────────────────── --}}
        @if ($isPartner)
        <div id="withdrawalModal"
             class="fixed inset-0 hidden items-center justify-center z-50"
             style="background:rgba(0,0,0,0.65);backdrop-filter:blur(6px);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                <div class="p-5" style="background:linear-gradient(135deg,#1a1a2e,#0f3460);">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Withdraw Partner Balance</h2>
                            <p class="text-xs mt-1" style="color:rgba(255,255,255,0.55);">
                                Available: <strong class="text-yellow-300">₦{{ number_format($refBal, 2) }}</strong>
                                &nbsp;·&nbsp;Min: <strong class="text-yellow-300">₦{{ number_format($minWithdrawal, 2) }}</strong>
                            </p>
                        </div>
                        <button onclick="closeWithdrawalModal()"
                                class="text-white text-2xl leading-none flex items-center justify-center flex-shrink-0"
                                style="background:rgba(255,255,255,0.12);width:34px;height:34px;border-radius:8px;">×</button>
                    </div>
                </div>

                <div id="wFlash" class="hidden mx-5 mt-4 p-3 rounded-xl text-sm font-semibold"></div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Amount (₦)</label>
                        <input type="number" id="wAmount" placeholder="0.00" min="{{ $minWithdrawal }}" step="0.01"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 text-lg font-bold focus:outline-none focus:ring-2 focus:ring-[#0f3460] bg-gray-50"/>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Bank Name</label>
                        <input type="text" id="wBankName" placeholder="e.g. GTBank, Access Bank"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460] bg-gray-50"/>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Account Number</label>
                        <input type="text" id="wAccNumber" placeholder="10-digit account number" maxlength="20"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460] bg-gray-50"/>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Account Name</label>
                        <input type="text" id="wAccName" placeholder="Name on the account"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#0f3460] bg-gray-50"/>
                    </div>
                    <button id="wSubmitBtn" onclick="submitWithdrawal()"
                            class="w-full py-3 rounded-xl font-bold text-white text-sm active:scale-95 transition-transform"
                            style="background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 4px 14px rgba(245,158,11,0.35);">
                        Submit Withdrawal Request
                    </button>
                    <p class="text-xs text-center text-gray-400">
                        Withdrawals are processed manually, typically within 24 hours.
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="grid grid-cols-3 gap-2">
            <button onclick="openGetNumberChoiceModal()"
                    class="bg-gradient-to-br from-[#00df82] via-[#03624c] to-[#030f0f] text-white font-bold py-2 sm:py-3 px-2 sm:px-4 rounded-2xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 sm:gap-3 text-sm sm:text-base whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3"/>
                </svg>
                <span class="truncate">Get Number</span>
            </button>

            <button onclick="showComingSoonToast('Gift Card is coming soon.')"
                    class="bg-gradient-to-br from-[#00df82] via-[#03624c] to-[#030f0f] text-white font-bold py-2 sm:py-3 px-2 sm:px-4 rounded-2xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 sm:gap-3 text-sm sm:text-base whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="truncate">Gift Card</span>
            </button>

            <button onclick="window.location.href='social-boost'"
                    class="bg-gradient-to-br from-[#00df82] via-[#03624c] to-[#030f0f] text-white font-bold py-2 sm:py-3 px-2 sm:px-4 rounded-2xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 sm:gap-3 text-sm sm:text-base whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
                <span class="truncate">Social Boost</span>
            </button>
        </div>

        {{-- Get Number Choice Modal --}}
        <div id="getNumberChoiceModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-green-100">
                <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-emerald-100">Choose one</p>
                            <h2 class="text-2xl font-extrabold text-white mt-1">Get Number</h2>
                        </div>
                        <button type="button" onclick="closeGetNumberChoiceModal()" class="text-white/90 hover:text-white text-2xl font-light leading-none">×</button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <button type="button" onclick="showGetNumberComingSoon()"
                                class="group w-full text-left rounded-2xl border-2 border-emerald-200 bg-gradient-to-r from-emerald-50 to-green-50 p-4 shadow-sm hover:shadow-md hover:border-emerald-400 transition-all duration-200 active:scale-[0.99]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xl font-bold text-gray-900">Buy Number</p>
                                    <p class="text-sm text-gray-600 mt-1">Valid for more than 1 month</p>
                                </div>
                                <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-emerald-600 text-white font-bold text-xs shadow-md">1M+</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs font-semibold">
                                <span class="text-emerald-700">Long-term access</span>
                                <span class="text-gray-400 group-hover:text-emerald-600">›</span>
                            </div>
                        </button>

                        <button type="button" onclick="openOneTimeNumberModal()"
                                class="group w-full text-left rounded-2xl border-2 border-sky-200 bg-gradient-to-r from-sky-50 to-blue-50 p-4 shadow-sm hover:shadow-md hover:border-sky-400 transition-all duration-200 active:scale-[0.99]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xl font-bold text-gray-900">One-time Number</p>
                                    <p class="text-sm text-gray-600 mt-1">Usable once</p>
                                </div>
                                <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-sky-600 text-white font-bold text-xs shadow-md">1X</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs font-semibold">
                                <span class="text-sky-700">Instant verification</span>
                                <span class="text-gray-400 group-hover:text-sky-600">›</span>
                            </div>
                        </button>
                    </div>

                    <div id="getNumberComingSoon" class="hidden mt-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm animate-pulse">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                            <span class="font-semibold">Buy Number is coming soon.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Pick --}}
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Quick Pick</h2>
                <a href="{{ route('services.list') }}" class="text-sm font-semibold text-brand-green">See All</a>
            </div>
            <div class="grid grid-cols-4 gap-4 text-center">
                @if(isset($quickPickServices['facebook']))
                <button onclick="showProviderModalForce('{{ $quickPickServices['facebook']['id'] }}', '{{ $quickPickServices['facebook']['title'] }}', 'fb')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/2021_Facebook_icon.svg" class="w-10 h-10" alt="Facebook">
                    <span class="text-xs text-gray-600">Facebook</span>
                </button>
                @endif
                @if(isset($quickPickServices['whatsapp']))
                <button onclick="showProviderModalForce('{{ $quickPickServices['whatsapp']['id'] }}', '{{ $quickPickServices['whatsapp']['title'] }}', 'wa')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" class="w-10 h-10" alt="WhatsApp">
                    <span class="text-xs text-gray-600">WhatsApp</span>
                </button>
                @endif
                @if(isset($quickPickServices['telegram']))
                <button onclick="showProviderModalForce('{{ $quickPickServices['telegram']['id'] }}', '{{ $quickPickServices['telegram']['title'] }}', 'tg')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/82/Telegram_logo.svg" class="w-10 h-10" alt="Telegram">
                    <span class="text-xs text-gray-600">Telegram</span>
                </button>
                @endif
                @if(isset($quickPickServices['google']))
                <button onclick="showProviderModalForce('{{ $quickPickServices['google']['id'] }}', '{{ $quickPickServices['google']['title'] }}', 'google')"
                        class="flex flex-col items-center justify-center space-y-2">
                    <img src="https://static.cdnlogo.com/logos/g/86/google-voice.svg" class="w-10 h-10" alt="Google Voice">
                    <span class="text-xs text-gray-600">Google Voice</span>
                </button>
                @endif
            </div>
        </div>

        {{-- Provider Selection Modal --}}
        <div id="providerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 id="modalServiceTitle" class="text-xl font-bold text-white">Select Provider</h2>
                            <p class="text-green-100 text-sm mt-1">Choose your preferred SMS provider</p>
                        </div>
                        <button onclick="closeProviderModal()" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4">

                        {{-- Provider 1 --}}
                        @if(!empty($hasFivesim))
                        <button onclick="selectProvider('provider1')"
                            class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md">EG1</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-800 group-hover:text-blue-700">Provider 1</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs text-gray-600">Global coverage, multiple operators</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                    @else
                    <button disabled class="w-full p-5 border-2 border-gray-200 rounded-xl bg-gray-100 text-gray-400 cursor-not-allowed">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gray-400 rounded-lg flex items-center justify-center text-white font-bold text-lg">EG1</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-600">Provider 1 (Unavailable)</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-red-400 rounded-full"></span>
                                    @if(empty($fivesimConfigOk))
                                        <span class="text-xs text-gray-500">Not configured — contact admin</span>
                                    @elseif(!$fivesimProviderAvailable)
                                        <span class="text-xs text-gray-500">Provider offline — try again later</span>
                                    @else
                                        <span class="text-xs text-gray-500">Unavailable — contact support</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </button>
                    @endif

                        {{-- Provider 2 --}}
                        @if(!empty($hasSmsBower))
                        <button onclick="selectProvider('provider2')"
                            class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md">EG2</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-800 group-hover:text-green-700">Provider 2</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs text-gray-600">Fast &amp; reliable</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                    @else
                    <button disabled class="w-full p-5 border-2 border-gray-200 rounded-xl bg-gray-100 text-gray-400 cursor-not-allowed">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gray-400 rounded-lg flex items-center justify-center text-white font-bold text-lg">EG2</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-600">Provider 2 (Unavailable)</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-red-400 rounded-full"></span>
                                    @if(empty($smsbowerConfigOk))
                                        <span class="text-xs text-gray-500">Not configured — contact admin</span>
                                    @elseif(!$smsbowerProviderAvailable)
                                        <span class="text-xs text-gray-500">Provider offline — try again later</span>
                                    @else
                                        <span class="text-xs text-gray-500">Unavailable — contact support</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </button>
                    @endif

                        {{-- Provider 3 --}}
                        @if(!empty($hasSmsbus))
                        <button onclick="selectProvider('provider3')"
                            class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md">EG3</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-800 group-hover:text-purple-700">Provider 3</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                                    <span class="text-xs text-gray-600">Wide country coverage</span>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                    @else
                    <button disabled class="w-full p-5 border-2 border-gray-200 rounded-xl bg-gray-100 text-gray-400 cursor-not-allowed">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gray-400 rounded-lg flex items-center justify-center text-white font-bold text-lg">EG3</div>
                            <div class="flex-1 text-left">
                                <h3 class="font-bold text-gray-600">Provider 3 (Unavailable)</h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-block w-2 h-2 bg-red-400 rounded-full"></span>
                                    @if(empty($smsbusConfigOk))
                                        <span class="text-xs text-gray-500">Not configured — contact admin</span>
                                    @elseif(!$smsbusProviderAvailable)
                                        <span class="text-xs text-gray-500">Provider offline — try again later</span>
                                    @else
                                        <span class="text-xs text-gray-500">Unavailable — contact support</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </button>
                    @endif

                </div>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                    <p class="text-xs text-gray-600 text-center">All providers offer secure SMS verification services</p>
                </div>
            </div>
        </div>

        {{-- Services Grid --}}
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="grid grid-cols-4 gap-x-4 gap-y-6 text-center">
                <a href="data" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/wifi.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">Data</span>
                </a>
                <a href="airtime" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/phone.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">Airtime</span>
                </a>
                <a href="electricity" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/electricity.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">Electricity</span>
                </a>
                <a href="cabletv" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/tv.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">TV/Cable</span>
                </a>
                <a href="esim" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/e-sim.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">eSim</span>
                </a>
                <a href="logs" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/sim-icon.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">Logs</span>
                </a>
                <a href="more-services" class="flex flex-col items-center space-y-2">
                    <div class="bg-brand-green-light rounded-full w-14 h-14 flex items-center justify-center"><img src="assets/more.svg" class="w-8 h-8" alt=""></div>
                    <span class="text-xs font-medium text-gray-700">Others</span>
                </a>
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="space-y-4 pb-20">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Transactions</h2>
                <a href="transactions" class="text-sm font-semibold text-brand-green">View more +</a>
            </div>
            @forelse ($latestTransactions as $txn)
                <div class="flex items-center space-x-4">
                    <div class="bg-brand-green-light rounded-full w-12 h-12 flex-shrink-0 flex items-center justify-center">
                        @if($txn->type === 'payment')
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        @else
                            <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div class="flex-grow min-w-0">
                        <p class="font-semibold text-gray-800 flex items-center justify-between gap-2">
                            <span class="truncate">{{ ucfirst($txn->type) }}{{ $txn->provider ? ' - ' . $txn->provider : '' }}</span>
                            @php
                                $statusColor = match ($txn->status) {
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'success' => 'bg-green-600 text-white',
                                    'failed'  => 'bg-red-500 text-white',
                                    default   => 'bg-gray-300 text-gray-600'
                                };
                            @endphp
                            <span class="flex-shrink-0 px-2 py-0.5 text-xs rounded-full font-medium {{ $statusColor }}">
                                {{ ucfirst($txn->status) }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-500">{{ $txn->created_at->format('h:i A | d M, Y') }}</p>
                    </div>
                    <div class="font-bold flex-shrink-0 {{ $txn->type === 'payment' ? 'text-green-600' : 'text-gray-800' }}">
                        {{ $txn->type === 'payment' ? '+' : '-' }} ₦{{ number_format($txn->amount, 2) }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-8">No transactions yet.</p>
            @endforelse
        </div>
    </div>
</div>

@include('partials.social-form')
@include('partials.footer')

@if (Auth::user() && !Auth::user()->pin)
<div id="pinModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-8 relative">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Set Transaction PIN</h2>
        <p class="text-gray-600 text-center mb-6">For your security, set a 4-digit PIN to authorize transactions.</p>
        <form id="pinFormModal" action="{{ route('updateProfile.pin') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label for="pin_modal" class="block text-sm font-medium text-gray-700 mb-2">Enter 4-Digit PIN</label>
                <input type="password" id="pin_modal" name="pin" maxlength="4" required autofocus inputmode="numeric" pattern="[0-9]{4}"
                    class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin') border-red-500 @enderror">
                @error('pin')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pin_confirmation_modal" class="block text-sm font-medium text-gray-700 mb-2">Confirm PIN</label>
                <input type="password" id="pin_confirmation_modal" name="pin_confirmation" maxlength="4" required inputmode="numeric" pattern="[0-9]{4}"
                    class="block w-full text-center text-3xl tracking-widest bg-gray-100 rounded-xl p-4 border focus:ring-2 focus:ring-green-600 focus:outline-none @error('pin_confirmation') border-red-500 @enderror">
                @error('pin_confirmation')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full bg-brand-green text-white text-lg font-semibold py-3 px-4 rounded-xl hover:bg-green-700 transition duration-200">Save PIN</button>
        </form>
    </div>
</div>
@endif

<script>
var BASE_URL    = '{{ url('/') }}';
var CSRF_TOK    = '{{ csrf_token() }}';
var PARTNER_MIN = {{ $minWithdrawal }};
var PARTNER_BAL = {{ $refBal }};

// ── Balance visibility toggle ─────────────────────────────
var balanceVisible = false; // hidden by default

function toggleBalanceVisibility() {
    balanceVisible = !balanceVisible;

    var walletVisible  = document.getElementById('walletBalanceVisible');
    var walletHidden   = document.getElementById('walletBalanceHidden');
    var refVisible     = document.getElementById('refBalanceVisible');
    var refHidden      = document.getElementById('refBalanceHidden');
    var eyeOpen        = document.getElementById('eyeOpen');
    var eyeClosed      = document.getElementById('eyeClosed');

    if (balanceVisible) {
        walletVisible.classList.remove('hidden');
        walletHidden.classList.add('hidden');
        refVisible.classList.remove('hidden');
        refHidden.classList.add('hidden');
        eyeOpen.classList.remove('hidden');
        eyeClosed.classList.add('hidden');
    } else {
        walletVisible.classList.add('hidden');
        walletHidden.classList.remove('hidden');
        refVisible.classList.add('hidden');
        refHidden.classList.remove('hidden');
        eyeOpen.classList.add('hidden');
        eyeClosed.classList.remove('hidden');
    }
}

// Initialise: hide balances on load
document.addEventListener('DOMContentLoaded', function () {
    // Start hidden
    document.getElementById('walletBalanceVisible').classList.add('hidden');
    document.getElementById('walletBalanceHidden').classList.remove('hidden');
    document.getElementById('refBalanceVisible').classList.add('hidden');
    document.getElementById('refBalanceHidden').classList.remove('hidden');
    document.getElementById('eyeOpen').classList.add('hidden');
    document.getElementById('eyeClosed').classList.remove('hidden');
});

// ── Copy referral link ────────────────────────────────────
function copyReferralLink() {
    var link = BASE_URL + '/app/signup?ref={{ $authUser->username }}';
    var btn  = document.getElementById('copyRefBtn');
    var orig = btn.textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(link).then(function () {
            btn.textContent = '✅ Copied!';
            setTimeout(function () { btn.textContent = orig; }, 2000);
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = link; document.body.appendChild(ta);
        ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✅ Copied!';
        setTimeout(function () { btn.textContent = orig; }, 2000);
    }
}

// ── Copy dedicated account number ─────────────────────────
function copyDedicatedAccountNumber() {
    var el  = document.getElementById('dedicatedAccNumber');
    var btn = document.getElementById('copyDedicatedBtn');
    if (!el || !btn) return;
    var number = el.textContent.trim();
    var orig = btn.textContent;
    var reset = function () { setTimeout(function () { btn.textContent = orig; }, 2000); };

    if (navigator.clipboard) {
        navigator.clipboard.writeText(number).then(function () {
            btn.textContent = '✅ Copied!'; reset();
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = number; document.body.appendChild(ta);
        ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = '✅ Copied!'; reset();
    }
}

// ── Add Funds modal (open/close + tab switching) ──────────
function switchFundTab(tab) {
    if (tab !== 'onetime') return;

    var onetime = document.getElementById('tabOnetime');
    var btnOnetime = document.getElementById('tabBtnOnetime');

    if (onetime) onetime.classList.remove('hidden');
    if (btnOnetime) {
        btnOnetime.classList.add('text-brand-green', 'border-brand-green');
        btnOnetime.classList.remove('text-gray-400', 'border-transparent');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var addFundsBtn   = document.getElementById('addFundsBtn');
    var modal         = document.getElementById('addFundsModal');
    var closeModalBtn = document.getElementById('closeModalBtn');
    var checkoutForm  = document.getElementById('korapayCheckoutForm');

    if (addFundsBtn) addFundsBtn.addEventListener('click', function () { modal.classList.remove('hidden'); modal.classList.add('flex'); });
    if (closeModalBtn) closeModalBtn.addEventListener('click', function () { modal.classList.add('hidden'); modal.classList.remove('flex'); });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } });

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function () {
            var btn = document.getElementById('checkoutSubmitBtn');
            var amt = document.getElementById('amount').value;
            if (!amt || amt < 100) return;
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Redirecting…';
        });
    }

    // ── PIN modal ─────────────────────────────────────────
    @if (Auth::user() && !Auth::user()->pin)
    setTimeout(function () {
        var pinModal = document.getElementById('pinModal');
        if (pinModal) { pinModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }, 5000);
    document.addEventListener('keydown', function (e) {
        var p = document.getElementById('pinModal');
        if (p && p.style.display === 'flex' && e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); return false; }
    }, true);
    document.getElementById('pinModal')?.addEventListener('click', function (e) {
        if (e.target === this) { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); return false; }
    }, true);
    @endif
});

// ── Partner Withdrawal modal ──────────────────────────────
@if ($isPartner)
function openWithdrawalModal() {
    var m = document.getElementById('withdrawalModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeWithdrawalModal() {
    var m = document.getElementById('withdrawalModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
    var fl = document.getElementById('wFlash');
    fl.className = 'hidden mx-5 mt-4 p-3 rounded-xl text-sm font-semibold';
    fl.textContent = '';
}
function flashW(msg, ok) {
    var el = document.getElementById('wFlash');
    el.textContent = msg;
    el.className = 'mx-5 mt-4 p-3 rounded-xl text-sm font-semibold ' + (ok
        ? 'bg-green-50 text-green-700 border border-green-200'
        : 'bg-red-50 text-red-700 border border-red-200');
}
function submitWithdrawal() {
    var amount   = parseFloat(document.getElementById('wAmount').value);
    var bankName = document.getElementById('wBankName').value.trim();
    var accNum   = document.getElementById('wAccNumber').value.trim();
    var accName  = document.getElementById('wAccName').value.trim();
    var btn      = document.getElementById('wSubmitBtn');

    if (!amount || amount < PARTNER_MIN) { flashW('Minimum withdrawal is ₦' + PARTNER_MIN.toLocaleString('en-NG', { minimumFractionDigits: 2 }), false); return; }
    if (amount > PARTNER_BAL)            { flashW('Insufficient partner balance.', false); return; }
    if (!bankName || !accNum || !accName){ flashW('Please fill in all bank details.', false); return; }

    btn.disabled = true; btn.textContent = 'Submitting…';

    fetch(BASE_URL + '/app/partner/withdraw', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOK, 'Accept': 'application/json' },
        body: JSON.stringify({ amount: amount, bank_name: bankName, account_number: accNum, account_name: accName })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        btn.disabled = false; btn.textContent = 'Submit Withdrawal Request';
        if (data.success) {
            flashW(data.message, true);
            PARTNER_BAL = parseFloat((data.new_balance + '').replace(/,/g, '')) || 0;
            setTimeout(function () { window.location.reload(); }, 2000);
        } else {
            flashW(data.message || 'Something went wrong.', false);
        }
    })
    .catch(function () {
        btn.disabled = false; btn.textContent = 'Submit Withdrawal Request';
        flashW('Network error. Please try again.', false);
    });
}
document.getElementById('openWithdrawalModal')?.addEventListener('click', openWithdrawalModal);
document.getElementById('withdrawalModal')?.addEventListener('click', function (e) { if (e.target === this) closeWithdrawalModal(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeWithdrawalModal(); });
@endif

// ── Provider modal ────────────────────────────────────────
var selectedServiceId = null, selectedServiceName = null, selectedServiceCode = null;

function getPreferredProvider(serviceCode, serviceId) {
    try {
        const keys = [];
        if (serviceCode) keys.push('preferredProvider:' + serviceCode);
        if (serviceId)   keys.push('preferredProvider:' + serviceId);
        keys.push('preferredProvider:');
        for (const k of keys) {
            const v = localStorage.getItem(k);
            if (v) return v;
        }
    } catch (e) { /* ignore */ }
    return null;
}

function showProviderModal(serviceId, serviceName, serviceCode) {
    // If the user already picked a provider for this service, skip the modal
    const saved = getPreferredProvider(serviceCode, serviceId);
    if (saved) {
        navigateToProvider(saved, serviceId, serviceCode);
        return;
    }

    selectedServiceId = serviceId; selectedServiceName = serviceName; selectedServiceCode = serviceCode;
    document.getElementById('modalServiceTitle').textContent = serviceName;
    var m = document.getElementById('providerModal'); m.classList.remove('hidden'); m.classList.add('flex');
}
// Force-open provider modal regardless of stored preference (used by Get Number)
function showProviderModalForce(serviceId, serviceName, serviceCode) {
    try {
        selectedServiceId = serviceId; selectedServiceName = serviceName; selectedServiceCode = serviceCode;
        document.getElementById('modalServiceTitle').textContent = serviceName || 'Select Provider';
        var m = document.getElementById('providerModal'); if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
    } catch (e) {
        console.error('showProviderModalForce error', e);
        // fallback to normal flow
        showProviderModal(serviceId, serviceName, serviceCode);
    }
}
function closeProviderModal() { var m = document.getElementById('providerModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
function selectProvider(provider) {
    // persist user's provider choice for this service so modal is skipped next time
    try {
        const key = 'preferredProvider:' + (selectedServiceCode || selectedServiceId || '');
        if (key) localStorage.setItem(key, provider);
    } catch (e) {
        // ignore storage errors
    }

    navigateToProvider(provider, selectedServiceId, selectedServiceCode);
}

function navigateToProvider(provider, serviceId, serviceCode) {
    if (provider === 'provider1' || provider === 'provider2') {
        const target = (serviceCode || serviceId);
        if (!target) {
            window.location.href = '/app/services?provider=' + provider;
            return;
        }
        if (provider === 'provider1') {
            window.location.href = '/app/services/' + target + '/provider1/countries';
        } else {
            window.location.href = '/app/services/' + target + '/provider2/rank';
        }
        return;
    }

    // provider3 = SMS-Bus — MUST use the numeric project id, never the text code,
    // because every SMS-Bus endpoint (prices, purchase) keys off projectId/service:integer.
    if (!serviceId) {
        window.location.href = '/app/services?provider=provider3';
        return;
    }
    window.location.href = '/app/services/' + serviceId + '/countries-provider3';
}
function openSocialModal()  { document.getElementById('socialModal').classList.remove('hidden'); }
function closeSocialModal() { document.getElementById('socialModal').classList.add('hidden'); }
function openGetNumberChoiceModal() {
    closeProviderModal();
    const modal = document.getElementById('getNumberChoiceModal');
    const message = document.getElementById('getNumberComingSoon');
    if (message) message.classList.add('hidden');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeGetNumberChoiceModal() {
    const modal = document.getElementById('getNumberChoiceModal');
    const message = document.getElementById('getNumberComingSoon');
    if (message) message.classList.add('hidden');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function showGetNumberComingSoon() {
    const message = document.getElementById('getNumberComingSoon');
    if (message) {
        message.classList.remove('hidden');
        message.classList.add('block');
    }
}

function showComingSoonToast(message) {
    const existing = document.getElementById('comingSoonToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'comingSoonToast';
    toast.className = 'fixed inset-x-4 bottom-24 z-[60] mx-auto max-w-sm rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 shadow-lg animate-pulse';
    toast.innerHTML = '<div class="flex items-center gap-2"><span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span><span>' + message + '</span></div>';
    document.body.appendChild(toast);

    setTimeout(function () {
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    }, 2200);
}

function openOneTimeNumberModal() {
    closeGetNumberChoiceModal();
    showProviderModalForce('', 'Get Number', '');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('getNumberChoiceModal')?.classList.contains('hidden')) {
            closeGetNumberChoiceModal();
            return;
        }
        closeProviderModal();
    }
});
document.getElementById('providerModal')?.addEventListener('click', function (e) { if (e.target === this) closeProviderModal(); });
document.getElementById('getNumberChoiceModal')?.addEventListener('click', function (e) { if (e.target === this) closeGetNumberChoiceModal(); });
</script>
@endsection