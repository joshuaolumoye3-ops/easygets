{{--
    Replaces the old "Add Funds Modal" block in users/home.blade.php.

    REQUIRES: pass a $dedicatedAccount variable from Controller::dashboard(),
    e.g.:
        $dedicatedAccount = \App\Models\DedicatedAccount::where('user_id', $user->id)
            ->where('status', 'active')->first();
    ...and add 'dedicatedAccount' to the compact(...) call in that method.
--}}

<div id="addFundsModal" class="fixed inset-0 bg-black/50 hidden justify-center items-center z-50">
    <div class="bg-white rounded-xl w-full max-w-md relative mx-4 shadow-2xl overflow-hidden">
        <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold leading-none z-10">✕</button>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-100">
            <button type="button" onclick="switchFundTab('onetime')" id="tabBtnOnetime"
                    class="flex-1 py-3 text-sm font-bold text-brand-green border-b-2 border-brand-green">
                One-time Payment
            </button>
            <button type="button" onclick="switchFundTab('dedicated')" id="tabBtnDedicated"
                    class="flex-1 py-3 text-sm font-bold text-gray-400 border-b-2 border-transparent">
                My Dedicated Account
            </button>
        </div>

        {{-- ── Tab 1: One-time checkout ─────────────────────────────── --}}
        <div id="tabOnetime" class="p-6">
            <h2 class="text-lg font-bold mb-4 text-gray-800">Fund via One-time Payment</h2>
            <form action="{{ route('korapay.checkout.initiate') }}" method="POST">
                @csrf
                <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <p class="text-xs text-blue-700">Pay securely via card, bank transfer, or USSD through Korapay. Your wallet is credited automatically once payment is confirmed.</p>
                </div>
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" required min="100" step="0.01"
                           placeholder="Enter amount e.g. 1000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                    <p class="text-xs text-gray-500 mt-1">Minimum amount: ₦100</p>
                </div>
                <button type="submit" id="generateBtn"
                        class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Proceed to Payment
                </button>
            </form>
        </div>

        {{-- ── Tab 2: Dedicated (permanent) account ─────────────────── --}}
        <div id="tabDedicated" class="p-6 hidden">
            @if ($dedicatedAccount ?? null)
                <h2 class="text-lg font-bold mb-1 text-gray-800">Your Dedicated Account</h2>
                <p class="text-xs text-gray-500 mb-4">Transfer to this account anytime — your wallet is credited automatically.</p>
                <div class="rounded-xl p-4 space-y-2" style="background:linear-gradient(135deg,#03624C,#065f46);">
                    <p class="text-xs" style="color:rgba(255,255,255,0.6);">Bank</p>
                    <p class="font-bold text-white">{{ $dedicatedAccount->bank_name }}</p>
                    <p class="text-xs mt-2" style="color:rgba(255,255,255,0.6);">Account Number</p>
                    <p class="font-mono font-extrabold text-2xl text-yellow-300 tracking-wide">{{ $dedicatedAccount->account_number }}</p>
                    <p class="text-xs mt-2" style="color:rgba(255,255,255,0.6);">Account Name</p>
                    <p class="font-semibold text-white">{{ $dedicatedAccount->account_name }}</p>
                </div>
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $dedicatedAccount->account_number }}'); this.textContent='✅ Copied!'"
                        class="w-full mt-4 py-2.5 rounded-lg font-semibold text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">
                    📋 Copy Account Number
                </button>
            @else
                <h2 class="text-lg font-bold mb-1 text-gray-800">Get Your Dedicated Account</h2>
                <p class="text-xs text-gray-500 mb-4">Create a permanent account number in your name. Fund it anytime, as many times as you like.</p>

                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded-lg">
                    <p class="text-xs text-yellow-800">Choose either your BVN or your NIN to verify your identity. One of them is required, and we never store your full details — only the last 4 digits, for support reference.</p>
                </div>

                <form action="{{ route('korapay.dedicated-account.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name on Account</label>
                        <input type="text" name="account_name" required maxlength="100"
                               value="{{ old('account_name', trim(Auth::user()->first_name . ' ' . Auth::user()->last_name)) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                        @error('account_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="useBvnBtn" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-brand-green text-white shadow-sm">
                            Use BVN
                        </button>
                        <button type="button" id="useNinBtn" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors bg-white text-gray-700 hover:bg-gray-50">
                            Use NIN
                        </button>
                    </div>
                    <div id="dedicatedBvnGroup" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">BVN <span class="text-red-500">*</span></label>
                        <input type="text" id="dedicatedBvnInput" name="bvn" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" value="{{ old('bvn') }}"
                               placeholder="11-digit Bank Verification Number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                        @error('bvn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div id="dedicatedNinGroup" class="space-y-2 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">NIN <span class="text-red-500">*</span></label>
                        <input type="text" id="dedicatedNinInput" name="nin" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" value="{{ old('nin') }}"
                               placeholder="11-digit National Identity Number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green">
                        @error('nin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <input type="hidden" id="dedicatedIdMethod" value="{{ old('nin') ? 'nin' : (old('bvn') ? 'bvn' : 'bvn') }}">
                    <button type="submit"
                            class="w-full bg-brand-green text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        Create Dedicated Account
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
function switchFundTab(tab) {
    var onetime = document.getElementById('tabOnetime');
    var dedicated = document.getElementById('tabDedicated');
    var btnOnetime = document.getElementById('tabBtnOnetime');
    var btnDedicated = document.getElementById('tabBtnDedicated');

    if (tab === 'onetime') {
        onetime.classList.remove('hidden'); dedicated.classList.add('hidden');
        btnOnetime.classList.add('text-brand-green', 'border-brand-green');
        btnOnetime.classList.remove('text-gray-400', 'border-transparent');
        btnDedicated.classList.add('text-gray-400', 'border-transparent');
        btnDedicated.classList.remove('text-brand-green', 'border-brand-green');
    } else {
        dedicated.classList.remove('hidden'); onetime.classList.add('hidden');
        btnDedicated.classList.add('text-brand-green', 'border-brand-green');
        btnDedicated.classList.remove('text-gray-400', 'border-transparent');
        btnOnetime.classList.add('text-gray-400', 'border-transparent');
        btnOnetime.classList.remove('text-brand-green', 'border-brand-green');
    }
}

function initDedicatedIdentitySwitcher() {
    var dedicatedIdentityMode = @json(old('nin') ? 'nin' : (old('bvn') ? 'bvn' : 'bvn'));
    var dedicatedBvnBtn = document.getElementById('useBvnBtn');
    var dedicatedNinBtn = document.getElementById('useNinBtn');
    var dedicatedBvnGroup = document.getElementById('dedicatedBvnGroup');
    var dedicatedNinGroup = document.getElementById('dedicatedNinGroup');
    var dedicatedBvnInput = document.getElementById('dedicatedBvnInput');
    var dedicatedNinInput = document.getElementById('dedicatedNinInput');
    var dedicatedIdMethod = document.getElementById('dedicatedIdMethod');

    function setDedicatedIdentityMode(mode) {
        var isBvn = mode === 'bvn';
        if (dedicatedBvnBtn) dedicatedBvnBtn.className = 'flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors ' + (isBvn ? 'bg-brand-green text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50');
        if (dedicatedNinBtn) dedicatedNinBtn.className = 'flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold transition-colors ' + (!isBvn ? 'bg-brand-green text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50');
        if (dedicatedBvnGroup) dedicatedBvnGroup.classList.toggle('hidden', !isBvn);
        if (dedicatedNinGroup) dedicatedNinGroup.classList.toggle('hidden', isBvn);
        if (dedicatedBvnInput) {
            dedicatedBvnInput.disabled = !isBvn;
            dedicatedBvnInput.required = isBvn;
            if (!isBvn) dedicatedBvnInput.value = '';
        }
        if (dedicatedNinInput) {
            dedicatedNinInput.disabled = isBvn;
            dedicatedNinInput.required = !isBvn;
            if (isBvn) dedicatedNinInput.value = '';
        }
        if (dedicatedIdMethod) dedicatedIdMethod.value = mode;
    }

    if (dedicatedBvnBtn && dedicatedNinBtn) {
        dedicatedBvnBtn.addEventListener('click', function () { setDedicatedIdentityMode('bvn'); });
        dedicatedNinBtn.addEventListener('click', function () { setDedicatedIdentityMode('nin'); });
        setDedicatedIdentityMode(dedicatedIdentityMode || 'bvn');
    }
}

window.addEventListener('DOMContentLoaded', initDedicatedIdentitySwitcher);
</script>
