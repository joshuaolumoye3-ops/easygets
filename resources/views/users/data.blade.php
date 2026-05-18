@extends('layouts.app')

@section('title', 'Data Purchase')

@section('content')

<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-16 md:mt-0 pt-0">
    <div class="container mx-auto px-4 py-8 max-w-md w-full">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-8 sm:mb-12">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Data Purchase</h1>
        </div>

        <!-- Data Purchase Form -->
        <form id="dataPurchaseForm" action="{{ route('data.purchase') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Network Selection -->
            <div class="pt-6 lg:pt-0">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                <div class="relative">
                    <input type="hidden" name="network" id="networkInput">
                    <select id="network"
                        class="custom-select w-full appearance-none px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200">
                        <option value="" disabled selected>Choose A Network</option>
                        <option value="mtn">MTN</option>
                        <option value="glo">Glo</option>
                        <option value="airtel">Airtel</option>
                        <option value="9mobile">9mobile</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Plan Duration Selection -->
            <div class="pt-6 lg:pt-0">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Plan Duration</label>
                <div id="typeSelect"
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-pointer focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200">
                    Choose Plan Duration
                </div>
                <input type="hidden" name="data_type" id="typeInput">
            </div>

            <!-- Package Selection -->
            <div class="pt-6 lg:pt-0">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Package</label>
                <div id="packageSelect"
                    class="custom-select w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full text-gray-500 cursor-pointer focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200">
                    Choose a Package
                </div>
                <input type="hidden" name="data_plan" id="packageInput">
                <input type="hidden" name="amount" id="amountInput">
            </div>

            <!-- Recipient -->
            <div class="pt-6 lg:pt-0">
                <label for="recipient" class="block text-sm font-semibold text-gray-700 mb-2">Recipient</label>
                <input type="tel" id="recipient" name="recipient" placeholder="Phone Number"
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-full placeholder-gray-400 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200">
            </div>

            <!-- Submit -->
            <div class="pt-32 lg:pt-20 justify-center flex items-center">
                <button type="submit"
                    class="w-full lg:w-52 bg-brand-green text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-700 transition duration-200 shadow-lg shadow-teal-700/20">
                    Pay Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== NETWORK MODAL ===================== -->
<div id="networkModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-1/2"
        id="modalContent">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-800">
            <h3 class="text-lg font-semibold text-white text-center">Choose a Network</h3>
        </div>
        <div class="bg-white p-4 h-full overflow-y-auto pb-16">
            <ul class="space-y-2">
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option"
                    data-value="airtel" data-label="Airtel NG Data">
                    <span
                        class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-bold text-sm mr-3">A</span>
                    <span class="text-gray-800 font-medium">Airtel NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option"
                    data-value="mtn" data-label="MTN NG Data">
                    <span
                        class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center font-bold text-sm mr-3">M</span>
                    <span class="text-gray-800 font-medium">MTN NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition duration-150 network-option"
                    data-value="9mobile" data-label="9Mobile NG Data">
                    <span
                        class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center font-bold text-sm mr-3">9</span>
                    <span class="text-gray-800 font-medium">9Mobile NG Data</span>
                </li>
                <li class="flex items-center p-3 rounded-lg cursor-pointer hover:bg-gray-50 transition duration-150 network-option"
                    data-value="glo" data-label="Glo NG Data">
                    <span
                        class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold text-sm mr-3">G</span>
                    <span class="text-gray-800 font-medium">Glo NG Data</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- ===================== TYPE MODAL ===================== -->
<div id="typeModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="typeModalContent"
        class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full h-auto pb-16">
        <div class="px-6 py-4 pb-5 bg-gray-800">
            <h3 id="typeModalTitle" class="text-lg font-semibold text-white text-center">Choose Plan Duration</h3>
        </div>
        <div class="bg-white p-4 overflow-y-auto max-h-96">
            <ul id="typeList" class="space-y-1"></ul>
        </div>
    </div>
</div>

<!-- ===================== PACKAGE MODAL ===================== -->
<div id="packageModal"
    class="fixed inset-0 z-50 flex items-end justify-center hidden modal-overlay bg-black/60 opacity-0 transition-opacity duration-300">
    <div id="packageModalContent"
        class="rounded-t-2xl shadow-xl w-full max-w-md mx-auto overflow-hidden transform transition-all duration-300 ease-out translate-y-full pb-16"
        style="max-height: 70vh;">
        <div class="px-6 py-4 pb-5 bg-gray-800">
            <h3 id="packageModalTitle" class="text-lg font-semibold text-white text-center">Choose a Package</h3>
        </div>
        <div class="bg-white p-4 overflow-y-auto" style="max-height: calc(70vh - 60px);">
            <ul id="packageList" class="space-y-1"></ul>
        </div>
    </div>
</div>

<!-- ===================== PIN MODAL ===================== -->
<div id="dataPinModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative">
        <button id="dataPinModalCloseBtn"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-lg font-bold">✕</button>
        <h2 class="text-xl font-semibold text-center text-gray-800 mb-3">🔒 Confirm Purchase</h2>
        <div id="dataPinModalSummary"
            class="mb-5 text-gray-600 text-sm leading-relaxed bg-gray-50 rounded-xl p-4 space-y-1"></div>
        <div class="mb-5">
            <label class="block text-gray-700 font-medium mb-3 text-center text-sm">Enter your 4-digit PIN</label>
            <div class="flex space-x-3 justify-center">
                <input type="password" maxlength="1" inputmode="numeric"
                    class="data-pin-input w-12 h-12 text-center border-2 border-gray-200 rounded-xl text-xl font-bold focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric"
                    class="data-pin-input w-12 h-12 text-center border-2 border-gray-200 rounded-xl text-xl font-bold focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric"
                    class="data-pin-input w-12 h-12 text-center border-2 border-gray-200 rounded-xl text-xl font-bold focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
                <input type="password" maxlength="1" inputmode="numeric"
                    class="data-pin-input w-12 h-12 text-center border-2 border-gray-200 rounded-xl text-xl font-bold focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition">
            </div>
        </div>
        <button id="confirmDataPinBtn" type="button"
            class="w-full bg-brand-green text-white font-semibold py-3 px-4 rounded-xl shadow hover:bg-teal-700 transition duration-200">
            ✅ Confirm & Purchase Data
        </button>
        <div id="dataPinModalError" class="text-red-600 mt-3 text-center text-sm font-medium hidden"></div>
    </div>
</div>

<!-- ===================== ERROR/INFO MODAL ===================== -->
<div id="dataCustomModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative">
        <button id="dataModalCloseBtn"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition text-lg font-bold">✕</button>
        <h2 id="dataModalTitle" class="text-lg font-bold mb-2"></h2>
        <p id="dataModalMessage" class="whitespace-pre-wrap text-gray-700 text-sm"></p>
        <button id="dataModalOkBtn"
            class="mt-5 w-full bg-gray-800 text-white font-semibold py-2.5 px-4 rounded-xl hover:bg-gray-700 transition duration-200">
            OK
        </button>
    </div>
</div>

<!-- ===================== LOADING OVERLAY ===================== -->
<div id="dataLoadingOverlay"
    class="fixed inset-0 bg-black bg-opacity-40 flex flex-col items-center justify-center hidden z-[60]">
    <div class="data-loader ease-linear rounded-full border-8 border-t-8 border-gray-200 h-16 w-16 mb-4"></div>
    <p class="text-white font-medium text-sm">Processing your purchase...</p>
</div>

<style>
    .data-loader {
        border-top-color: #059669;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .modal-overlay {
        transition: opacity 0.3s ease;
    }
</style>

<script>
    window.dataPlans = @json($plans);
    const dataWalletBalance = {{ $wallet->balance ?? 0 }};
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const networkSelect       = document.getElementById('network');
    const networkInput        = document.getElementById('networkInput');
    const typeSelectDiv       = document.getElementById('typeSelect');
    const typeInput           = document.getElementById('typeInput');
    const packageSelectDiv    = document.getElementById('packageSelect');
    const packageInput        = document.getElementById('packageInput');
    const amountInput         = document.getElementById('amountInput');
    const recipientInput      = document.getElementById('recipient');

    const networkModal        = document.getElementById('networkModal');
    const modalContent        = document.getElementById('modalContent');
    const typeModal           = document.getElementById('typeModal');
    const typeModalContent    = document.getElementById('typeModalContent');
    const typeModalTitle      = document.getElementById('typeModalTitle');
    const packageModal        = document.getElementById('packageModal');
    const packageModalContent = document.getElementById('packageModalContent');
    const packageModalTitle   = document.getElementById('packageModalTitle');
    const typeList            = document.getElementById('typeList');
    const packageList         = document.getElementById('packageList');

    const dataPinModal        = document.getElementById('dataPinModal');
    const dataCustomModal     = document.getElementById('dataCustomModal');
    const dataLoadingOverlay  = document.getElementById('dataLoadingOverlay');

    const dataPlans = window.dataPlans || {};
    let selectedNetwork = '';
    let selectedType    = '';

    // ── Modal helpers ─────────────────────────────────────────────────────────
    function showModal(modal, content) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.add('opacity-100');
            content.classList.remove('translate-y-full');
        });
    }

    function hideModal(modal, content) {
        modal.classList.remove('opacity-100');
        content.classList.add('translate-y-full');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    // Close bottom-sheet modals on backdrop click
    [
        { modal: networkModal,  content: modalContent },
        { modal: typeModal,     content: typeModalContent },
        { modal: packageModal,  content: packageModalContent },
    ].forEach(({ modal, content }) => {
        modal.addEventListener('click', e => {
            if (e.target === modal) hideModal(modal, content);
        });
    });

    // ── Data plan helpers ─────────────────────────────────────────────────────
    function normalizeNetworkKey(key) {
        // Try direct key match first, then partial include
        const lk = key.toLowerCase();
        return Object.keys(dataPlans).find(k => k.toLowerCase() === lk)
            || Object.keys(dataPlans).find(k => k.toLowerCase().includes(lk))
            || Object.keys(dataPlans).find(k => lk.includes(k.toLowerCase()));
    }

    function getProducts(networkKey) {
        const normalized = normalizeNetworkKey(networkKey);
        if (!normalized) return [];
        const raw = dataPlans[normalized];
        // Support both array-wrapped and direct formats
        if (Array.isArray(raw)) {
            return raw[0]?.PRODUCT || raw[0]?.products || raw || [];
        }
        return raw?.PRODUCT || raw?.products || [];
    }

    function getAvailableTypes(networkKey) {
        const products = getProducts(networkKey);
        const typesSet = new Set(products.map(p => p.PRODUCT_TYPE).filter(Boolean));
        const typeOrder = ['Daily', '2-Day', '3-Day', 'Weekly', 'Bi-Monthly', 'Monthly', 'Quarterly', 'Yearly'];
        const ordered = typeOrder.filter(t => typesSet.has(t));
        // Append any remaining types not in the predefined order
        typesSet.forEach(t => { if (!ordered.includes(t)) ordered.push(t); });
        return ordered;
    }

    function filterPackagesByType(networkKey, planType) {
        return getProducts(networkKey).filter(p => p.PRODUCT_TYPE === planType);
    }

    // ── Reset helpers ─────────────────────────────────────────────────────────
    function resetTypeUI() {
        typeSelectDiv.textContent = 'Choose Plan Duration';
        typeInput.value = '';
        typeSelectDiv.classList.replace('text-gray-800', 'text-gray-500') || typeSelectDiv.classList.add('text-gray-500');
        selectedType = '';
        resetPackageUI();
    }

    function resetPackageUI() {
        packageSelectDiv.textContent = 'Choose a Package';
        packageInput.value = '';
        amountInput.value = '';
        packageSelectDiv.classList.replace('text-gray-800', 'text-gray-500') || packageSelectDiv.classList.add('text-gray-500');
    }

    function setDivSelected(div) {
        div.classList.remove('text-gray-500');
        div.classList.add('text-gray-800');
    }

    // ── Network select: intercept native dropdown ─────────────────────────────
    networkSelect.addEventListener('mousedown', function (e) {
        e.preventDefault();
        showModal(networkModal, modalContent);
    });
    networkSelect.addEventListener('focus', function () {
        this.blur();
        showModal(networkModal, modalContent);
    });

    // ── Network option chosen ─────────────────────────────────────────────────
    document.querySelectorAll('.network-option').forEach(option => {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const label = this.dataset.label || this.querySelector('span:last-child')?.textContent || value;

            selectedNetwork = value;
            networkInput.value = value;
            typeSelectDiv.dataset.networkName = label;

            networkSelect.querySelectorAll('option').forEach(opt => {
                opt.selected = (opt.value === value);
            });
            networkSelect.classList.remove('text-gray-500');
            networkSelect.classList.add('text-gray-800');

            resetTypeUI();
            hideModal(networkModal, modalContent);
        });
    });

    // ── Type select clicked ───────────────────────────────────────────────────
    typeSelectDiv.addEventListener('click', function () {
        if (!selectedNetwork) {
            showDataCustomModal('Select Network', 'Please select a network first.', 'text-orange-500');
            return;
        }

        const availableTypes = getAvailableTypes(selectedNetwork);
        if (!availableTypes.length) {
            showDataCustomModal('No Plans', 'No plan durations found for this network. Please try another network.', 'text-red-600');
            return;
        }

        const networkName = this.dataset.networkName || selectedNetwork.toUpperCase();
        typeModalTitle.textContent = `${networkName} — Choose Duration`;
        typeList.innerHTML = '';

        availableTypes.forEach(type => {
            const li = document.createElement('li');
            li.className = 'flex items-center p-4 rounded-xl cursor-pointer border-b border-gray-100 hover:bg-green-50 active:bg-green-100 transition duration-150 type-option';
            li.dataset.type = type;
            li.innerHTML = `<span class="text-gray-800 font-medium">${type}</span>
                            <svg class="ml-auto w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>`;
            typeList.appendChild(li);
        });

        showModal(typeModal, typeModalContent);
    });

    // ── Type option chosen ────────────────────────────────────────────────────
    typeList.addEventListener('click', function (e) {
        const target = e.target.closest('.type-option');
        if (!target) return;

        selectedType = target.dataset.type;
        typeInput.value = selectedType;
        typeSelectDiv.textContent = selectedType;
        setDivSelected(typeSelectDiv);
        resetPackageUI();
        hideModal(typeModal, typeModalContent);
    });

    // ── Package select clicked ────────────────────────────────────────────────
    packageSelectDiv.addEventListener('click', function () {
        if (!selectedNetwork) {
            showDataCustomModal('Select Network', 'Please select a network first.', 'text-orange-500');
            return;
        }
        if (!selectedType) {
            showDataCustomModal('Select Duration', 'Please select a plan duration first.', 'text-orange-500');
            return;
        }

        const plans = filterPackagesByType(selectedNetwork, selectedType);
        if (!plans.length) {
            showDataCustomModal('No Packages', `No ${selectedType} packages found for this network.`, 'text-red-600');
            return;
        }

        const networkName = typeSelectDiv.dataset.networkName || selectedNetwork.toUpperCase();
        packageModalTitle.textContent = `${networkName} — ${selectedType}`;
        packageList.innerHTML = '';

        plans.forEach(plan => {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between p-4 rounded-xl cursor-pointer border-b border-gray-100 hover:bg-green-50 active:bg-green-100 transition duration-150 package-option';
            li.dataset.value  = plan.PRODUCT_CODE;
            li.dataset.amount = plan.PRODUCT_AMOUNT;
            li.dataset.name   = plan.PRODUCT_NAME;
            li.innerHTML = `
                <div>
                    <p class="text-gray-800 font-semibold text-sm">${plan.PRODUCT_NAME}</p>
                    <p class="text-gray-500 text-xs mt-0.5">${plan.PRODUCT_TYPE || selectedType}</p>
                </div>
                <span class="text-green-700 font-bold text-sm">₦${parseFloat(plan.PRODUCT_AMOUNT).toLocaleString('en-NG', {minimumFractionDigits: 0})}</span>`;
            packageList.appendChild(li);
        });

        showModal(packageModal, packageModalContent);
    });

    // ── Package option chosen ─────────────────────────────────────────────────
    packageList.addEventListener('click', function (e) {
        const target = e.target.closest('.package-option');
        if (!target) return;

        const code   = target.dataset.value;
        const amount = target.dataset.amount;
        const name   = target.dataset.name;

        packageInput.value = code;
        amountInput.value  = amount;
        packageSelectDiv.textContent = `${name} — ₦${parseFloat(amount).toLocaleString('en-NG', {minimumFractionDigits: 0})}`;
        setDivSelected(packageSelectDiv);

        hideModal(packageModal, packageModalContent);
    });

    // ── Auto-detect network from phone number ─────────────────────────────────
    recipientInput.addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '');
        if (value.startsWith('234')) value = '0' + value.slice(3);
        if (value.length < 4) return;

        const prefix = value.slice(0, 4);
        const networks = {
            mtn:     ['0803','0806','0703','0706','0813','0816','0810','0814','0903','0906','0913','0916'],
            glo:     ['0805','0807','0815','0811','0905','0915'],
            airtel:  ['0802','0808','0708','0812','0701','0902','0907','0912'],
            '9mobile':['0809','0817','0818','0909','0908','0917'],
        };

        let detected = null;
        for (const [net, prefixes] of Object.entries(networks)) {
            if (prefixes.includes(prefix)) { detected = net; break; }
        }

        if (detected && detected !== selectedNetwork) {
            selectedNetwork = detected;
            networkInput.value = detected;
            networkSelect.querySelectorAll('option').forEach(opt => {
                opt.selected = (opt.value === detected);
            });
            networkSelect.classList.remove('text-gray-500');
            networkSelect.classList.add('text-gray-800');
            typeSelectDiv.dataset.networkName = detected.toUpperCase();
            resetTypeUI();
        }
    });

    // ── Form submit → show PIN modal ──────────────────────────────────────────
    document.getElementById('dataPurchaseForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const network   = networkInput.value.trim();
        const dataPlan  = packageInput.value.trim();
        const recipient = recipientInput.value.trim();
        const amount    = parseFloat(amountInput.value) || 0;
        const packageText = packageSelectDiv.textContent.trim();
        const networkDisplay = networkSelect.options[networkSelect.selectedIndex]?.text || network.toUpperCase();

        if (!network)    return showDataCustomModal('Missing Network',   'Please select a network.',               'text-red-600');
        if (!dataPlan)   return showDataCustomModal('Missing Package',   'Please select a data package.',          'text-red-600');
        if (!recipient)  return showDataCustomModal('Missing Recipient', 'Please enter the recipient phone number.','text-red-600');
        if (amount <= 0) return showDataCustomModal('Invalid Package',   'Selected package has an invalid amount.','text-red-600');

        const phoneRegex = /^(\+?234|0)[789][01]\d{8}$/;
        if (!phoneRegex.test(recipient.replace(/\s/g, ''))) {
            return showDataCustomModal('Invalid Number', 'Please enter a valid Nigerian phone number.', 'text-red-600');
        }

        if (dataWalletBalance < amount) {
            return showDataCustomModal(
                'Insufficient Balance',
                `Your wallet balance (₦${dataWalletBalance.toLocaleString('en-NG', {minimumFractionDigits: 2})}) is not enough to purchase this plan (₦${amount.toLocaleString('en-NG', {minimumFractionDigits: 2})}).\n\nPlease fund your wallet and try again.`,
                'text-red-600'
            );
        }

        // Build summary
        document.getElementById('dataPinModalSummary').innerHTML = `
            <div class="flex justify-between"><span class="text-gray-500">Network</span><span class="font-semibold text-gray-800">${networkDisplay}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Package</span><span class="font-semibold text-gray-800 text-right max-w-[55%]">${packageText}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Recipient</span><span class="font-semibold text-gray-800">${recipient}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="font-bold text-green-700">₦${amount.toLocaleString('en-NG', {minimumFractionDigits: 2})}</span></div>
            <div class="flex justify-between border-t border-gray-200 pt-2 mt-1"><span class="text-gray-500">Wallet Balance</span><span class="font-semibold text-gray-700">₦${dataWalletBalance.toLocaleString('en-NG', {minimumFractionDigits: 2})}</span></div>
        `;

        document.querySelectorAll('.data-pin-input').forEach(i => i.value = '');
        document.getElementById('dataPinModalError').classList.add('hidden');
        dataPinModal.classList.remove('hidden');
        document.querySelector('.data-pin-input').focus();
    });

    // ── PIN modal close ───────────────────────────────────────────────────────
    document.getElementById('dataPinModalCloseBtn').addEventListener('click', () => {
        dataPinModal.classList.add('hidden');
    });
    dataPinModal.addEventListener('click', e => {
        if (e.target === dataPinModal) dataPinModal.classList.add('hidden');
    });

    // ── PIN input auto-advance ────────────────────────────────────────────────
    document.querySelectorAll('.data-pin-input').forEach((input, idx, arr) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            if (this.value && idx < arr.length - 1) arr[idx + 1].focus();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) arr[idx - 1].focus();
        });
    });

    // ── Confirm purchase ──────────────────────────────────────────────────────
    document.getElementById('confirmDataPinBtn').addEventListener('click', async function () {
        let pin = '';
        document.querySelectorAll('.data-pin-input').forEach(i => pin += i.value);

        const errorEl = document.getElementById('dataPinModalError');
        if (pin.length !== 4 || !/^\d{4}$/.test(pin)) {
            errorEl.textContent = 'Please enter all 4 PIN digits.';
            errorEl.classList.remove('hidden');
            return;
        }
        errorEl.classList.add('hidden');

        const originalHTML = this.innerHTML;
        this.disabled = true;
        this.innerHTML = `<svg class="animate-spin h-5 w-5 text-white inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>Processing...`;

        dataLoadingOverlay.classList.remove('hidden');

        const formData = new FormData();
        formData.append('network',    networkInput.value);
        formData.append('data_plan',  packageInput.value);
        formData.append('recipient',  recipientInput.value);
        formData.append('amount',     amountInput.value);
        formData.append('pin',        pin);

        try {
            const response = await fetch("{{ route('data.purchase') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await response.json();
            dataLoadingOverlay.classList.add('hidden');

            if (response.ok && result.success) {
                dataPinModal.classList.add('hidden');

                const networkDisplay = networkSelect.options[networkSelect.selectedIndex]?.text
                    || networkInput.value.toUpperCase();

                showSuccessModal({
                    title:   'Data Purchase Successful! 🎉',
                    message: result.message || 'Your data has been sent successfully.',
                    details: {
                        'Transaction ID': result.data?.transaction_reference || 'N/A',
                        'Network':        result.data?.network        || networkDisplay,
                        'Package':        result.data?.package_name   || packageSelectDiv.textContent,
                        'Phone Number':   result.data?.recipient      || recipientInput.value,
                        'Amount':         result.data?.formatted_amount || `₦${parseFloat(amountInput.value||0).toLocaleString('en-NG',{minimumFractionDigits:2})}`,
                        'New Balance':    result.data?.formatted_balance || 'N/A',
                        'Date & Time':    result.data?.transaction_time
                            ? new Date(result.data.transaction_time).toLocaleString()
                            : new Date().toLocaleString(),
                    },
                });

                // Reset form
                document.getElementById('dataPurchaseForm').reset();
                networkInput.value = '';
                packageInput.value = '';
                amountInput.value  = '';
                selectedNetwork = '';
                selectedType = '';
                resetTypeUI();
                networkSelect.classList.remove('text-gray-800');
                networkSelect.classList.add('text-gray-500');

            } else {
                const msg = result.message || 'Data purchase failed. Please try again.';
                errorEl.textContent = msg;
                errorEl.classList.remove('hidden');
            }

        } catch (err) {
            dataLoadingOverlay.classList.add('hidden');
            errorEl.textContent = 'Network error. Please check your connection and try again.';
            errorEl.classList.remove('hidden');
            console.error('Data purchase error:', err);
        } finally {
            this.disabled = false;
            this.innerHTML = originalHTML;
        }
    });

    // ── Error/info modal close ────────────────────────────────────────────────
    document.getElementById('dataModalCloseBtn').addEventListener('click', () => {
        dataCustomModal.classList.add('hidden');
    });
    document.getElementById('dataModalOkBtn').addEventListener('click', () => {
        dataCustomModal.classList.add('hidden');
    });
    dataCustomModal.addEventListener('click', e => {
        if (e.target === dataCustomModal) dataCustomModal.classList.add('hidden');
    });

    // ── Helpers exposed to this scope ─────────────────────────────────────────
    window.showDataCustomModal = function (title, message, colorClass = 'text-gray-800') {
        document.getElementById('dataModalTitle').textContent = title;
        document.getElementById('dataModalTitle').className = `text-lg font-bold mb-2 ${colorClass}`;
        document.getElementById('dataModalMessage').textContent = message;
        dataCustomModal.classList.remove('hidden');
    };

    function showSuccessModal(data) {
        // Try the server-rendered partial first, fall back to inline
        fetch('/render-success-partial', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            body: JSON.stringify({ title: data.title, message: data.message, details: data.details }),
        })
        .then(r => r.ok ? r.text() : Promise.reject())
        .then(html => buildSuccessModal(html, true))
        .catch(() => buildSuccessModal(null, false, data));
    }

    function buildSuccessModal(html, fromPartial, data = {}) {
        const wrapper = document.createElement('div');
        wrapper.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 px-4';

        const detailsHTML = data.details
            ? Object.entries(data.details).map(([k, v]) =>
                `<div class="flex justify-between py-1.5 border-b border-gray-100 last:border-0">
                    <span class="text-gray-500 text-sm">${k}</span>
                    <span class="font-semibold text-gray-800 text-sm text-right max-w-[55%]">${v}</span>
                 </div>`).join('')
            : '';

        wrapper.innerHTML = fromPartial
            ? `<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full relative">
                   <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold" onclick="this.closest('.fixed').remove()">✕</button>
                   <div class="p-6">${html}
                       <button class="mt-5 w-full bg-brand-green text-white font-semibold py-3 rounded-xl hover:bg-teal-700 transition" onclick="this.closest('.fixed').remove()">Done</button>
                   </div>
               </div>`
            : `<div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative max-h-screen overflow-y-auto">
                   <button class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-xl font-bold" onclick="this.closest('.fixed').remove()">✕</button>
                   <div class="text-center mb-4">
                       <div class="text-6xl mb-3">✅</div>
                       <h2 class="text-xl font-bold text-gray-800">${data.title || 'Success!'}</h2>
                       <p class="text-gray-500 text-sm mt-1">${data.message || ''}</p>
                   </div>
                   ${detailsHTML ? `<div class="bg-gray-50 rounded-xl p-4 mb-4">${detailsHTML}</div>` : ''}
                   <button class="w-full bg-brand-green text-white font-semibold py-3 rounded-xl hover:bg-teal-700 transition" onclick="this.closest('.fixed').remove()">Done</button>
               </div>`;

        document.body.appendChild(wrapper);
        wrapper.addEventListener('click', e => { if (e.target === wrapper) wrapper.remove(); });

        // Escape key
        const esc = e => { if (e.key === 'Escape') { wrapper.remove(); document.removeEventListener('keydown', esc); } };
        document.addEventListener('keydown', esc);
    }

}); // end DOMContentLoaded
</script>

@endsection