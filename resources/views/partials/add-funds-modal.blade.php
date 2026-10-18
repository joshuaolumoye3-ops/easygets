{{--
    Add Funds modal: One-time (Korapay checkout) + Dedicated account (Flutterwave).

    USAGE in users/home.blade.php:
      1. Delete the whole existing "ADD FUNDS MODAL" block (div#addFundsModal … its closing </div>).
      2. Put  @include('partials.add-funds-modal')  in its place.
      3. Remove the old switchFundTab() function from home's <script> (this partial has its own).
    Existing ids used by home's script are kept: addFundsBtn (outside), addFundsModal, closeModalBtn,
    korapayCheckoutForm, checkoutSubmitBtn, amount, dedicatedAccNumber, copyDedicatedBtn.

    Needs $dedicatedAccount from Controller::dashboard() (see INTEGRATION.md). Safe if missing.
--}}
@php
    $dedicatedAccount = $dedicatedAccount ?? null;
    $openDedicated    = $errors->has('bvn') || $errors->has('nin');
@endphp

<div id="addFundsModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-md relative shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold leading-none z-10" aria-label="Close">✕</button>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-100 flex-shrink-0 pr-10">
            <button type="button" id="tabBtnOnetime" onclick="fundSwitchTab('onetime')"
                    class="flex-1 py-3 text-sm font-bold text-brand-green border-b-2 border-brand-green transition-colors">
                One-time Payment
            </button>
            <button type="button" id="tabBtnDedicated" onclick="fundSwitchTab('dedicated')"
                    class="flex-1 py-3 text-sm font-bold text-gray-400 border-b-2 border-transparent transition-colors">
                My Dedicated Account
            </button>
        </div>

        <div class="overflow-y-auto">
            {{-- ── Tab 1: One-time checkout ─────────────────────────────── --}}
            <div id="tabOnetime" class="p-6">
                <h2 class="text-lg font-bold mb-4 text-gray-800">Fund via One-time Payment</h2>
                <form id="korapayCheckoutForm" action="{{ route('korapay.checkout.initiate') }}" method="POST">
                    @csrf
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-sm text-blue-800 font-semibold mb-1">How it works:</p>
                        <ul class="text-xs text-blue-700 space-y-1">
                            <li>• Redirected to Korapay's secure payment page</li>
                            <li>• Pay with card, bank transfer, or USSD</li>
                            <li>• Wallet credited automatically once confirmed</li>
                        </ul>
                    </div>
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₦)</label>
                        <input type="number" id="amount" name="amount" required min="100" step="0.01" placeholder="Enter amount e.g. 1000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                        <p class="text-xs text-gray-500 mt-1">Minimum amount: ₦100</p>
                    </div>
                    <button type="submit" id="checkoutSubmitBtn"
                            class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center">
                        Proceed to Payment
                    </button>
                </form>
            </div>

            {{-- ── Tab 2: Dedicated (permanent) account — Flutterwave ───── --}}
            <div id="tabDedicated" class="p-6 hidden">
                @if ($dedicatedAccount)
                    <h2 class="text-lg font-bold mb-1 text-gray-800">Your Dedicated Account</h2>
                    <p class="text-xs text-gray-500 mb-4">Transfer from any bank, any time. Your wallet is credited automatically.</p>

                    <div class="rounded-xl p-4 space-y-2" style="background:linear-gradient(135deg,#03624C,#065f46);">
                        <p class="text-xs" style="color:rgba(255,255,255,0.6);">Bank</p>
                        <p class="font-bold text-white">{{ $dedicatedAccount->bank_name }}</p>
                        <p class="text-xs mt-2" style="color:rgba(255,255,255,0.6);">Account Number</p>
                        <p id="dedicatedAccNumber" class="font-mono font-extrabold text-2xl text-yellow-300 tracking-wide">{{ $dedicatedAccount->account_number }}</p>
                        <p class="text-xs mt-2" style="color:rgba(255,255,255,0.6);">Account Name</p>
                        <p class="font-semibold text-white">{{ $dedicatedAccount->account_name }}</p>
                    </div>

                    <button type="button" id="copyDedicatedBtn" onclick="copyDedicatedAccountNumber()"
                            class="w-full mt-4 py-2.5 rounded-lg font-semibold text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">
                        📋 Copy Account Number
                    </button>
                    <p class="text-[11px] text-gray-400 text-center mt-3">Only transfer Naira (₦) from a bank account in your own name.</p>
                @else
                    <h2 class="text-lg font-bold mb-1 text-gray-800">Get Your Dedicated Account</h2>
                    <p class="text-xs text-gray-500 mb-4">A permanent account number for your wallet. Fund it anytime, as often as you like.</p>

                    <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-lg">
                        <p class="text-xs text-yellow-800">Use either your BVN <em>or</em> your NIN to verify your identity. It is sent securely to our payment partner. We never store it — only the last 4 digits for support reference.</p>
                    </div>

                    <form id="dedicatedForm" action="{{ route('flutterwave.dedicated-account.store') }}" method="POST" class="space-y-4" autocomplete="off">
                        @csrf
                        <div class="flex gap-2">
                            <button type="button" id="useBvnBtn" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-brand-green text-white shadow-sm">Use BVN</button>
                            <button type="button" id="useNinBtn" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-white text-gray-700 hover:bg-gray-50">Use NIN</button>
                        </div>

                        <div id="dedicatedBvnGroup">
                            <label for="dedicatedBvnInput" class="block text-sm font-medium text-gray-700 mb-2">BVN <span class="text-red-500">*</span></label>
                            <input type="password" id="dedicatedBvnInput" name="bvn" maxlength="11" inputmode="numeric" pattern="[0-9]{11}"
                                   autocomplete="off" placeholder="11-digit Bank Verification Number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                            @error('bvn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div id="dedicatedNinGroup" class="hidden">
                            <label for="dedicatedNinInput" class="block text-sm font-medium text-gray-700 mb-2">NIN <span class="text-red-500">*</span></label>
                            <input type="password" id="dedicatedNinInput" name="nin" maxlength="11" inputmode="numeric" pattern="[0-9]{11}"
                                   autocomplete="off" placeholder="11-digit National Identity Number" disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                            @error('nin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <p class="text-xs text-gray-500">Account name will be your profile name: <strong>{{ trim(Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</strong></p>

                        <button type="submit" id="dedicatedSubmitBtn"
                                class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-60">
                            Create Dedicated Account
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var ACTIVE = ['text-brand-green', 'border-brand-green'];
    var IDLE   = ['text-gray-400', 'border-transparent'];

    window.fundSwitchTab = function (tab) {
        var isOne = tab === 'onetime';
        document.getElementById('tabOnetime').classList.toggle('hidden', !isOne);
        document.getElementById('tabDedicated').classList.toggle('hidden', isOne);
        var b1 = document.getElementById('tabBtnOnetime'), b2 = document.getElementById('tabBtnDedicated');
        ACTIVE.concat(IDLE).forEach(function (c) { b1.classList.remove(c); b2.classList.remove(c); });
        (isOne ? ACTIVE : IDLE).forEach(function (c) { b1.classList.add(c); });
        (isOne ? IDLE : ACTIVE).forEach(function (c) { b2.classList.add(c); });
    };

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('dedicatedForm');
        if (form) {
            var bvnBtn = document.getElementById('useBvnBtn'), ninBtn = document.getElementById('useNinBtn');
            var bvnGrp = document.getElementById('dedicatedBvnGroup'), ninGrp = document.getElementById('dedicatedNinGroup');
            var bvnIn = document.getElementById('dedicatedBvnInput'), ninIn = document.getElementById('dedicatedNinInput');
            var on = 'flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-brand-green text-white shadow-sm';
            var off = 'flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-white text-gray-700 hover:bg-gray-50';

            function setMode(mode) {
                var bvn = mode === 'bvn';
                bvnBtn.className = bvn ? on : off; ninBtn.className = bvn ? off : on;
                bvnGrp.classList.toggle('hidden', !bvn); ninGrp.classList.toggle('hidden', bvn);
                // disabled inputs are not submitted => exactly one identifier is ever sent
                bvnIn.disabled = !bvn; bvnIn.required = bvn; if (!bvn) bvnIn.value = '';
                ninIn.disabled = bvn;  ninIn.required = !bvn; if (bvn) ninIn.value = '';
            }
            bvnBtn.addEventListener('click', function () { setMode('bvn'); });
            ninBtn.addEventListener('click', function () { setMode('nin'); });
            setMode({!! $errors->has('nin') ? "'nin'" : "'bvn'" !!});

            [bvnIn, ninIn].forEach(function (el) {
                el.addEventListener('input', function () { el.value = el.value.replace(/\D/g, '').slice(0, 11); });
            });

            form.addEventListener('submit', function () {
                var b = document.getElementById('dedicatedSubmitBtn');
                b.disabled = true; b.textContent = 'Creating your account…';
            });
        }

        @if ($openDedicated)
        var m = document.getElementById('addFundsModal');
        if (m) { m.classList.remove('hidden'); m.classList.add('flex'); fundSwitchTab('dedicated'); }
        @endif
    });
})();
</script>