@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        {{-- Header --}}
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('dashboard') }}" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Services</h1>
        </div>

        {{-- Search --}}
        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input
                id="searchInput"
                type="text"
                placeholder="Search Services..."
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition duration-200"
            >
            <button id="clearSearch" class="absolute inset-y-0 right-0 flex items-center pr-3 hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Popular Services --}}
        <div id="popularSection" class="mb-6 {{ count($popular) > 0 ? '' : 'hidden' }}">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Popular Services</h2>
            <div class="flex space-x-4 overflow-x-auto pb-4 -mx-4 px-4">
                @foreach($popular as $product)
                    <button onclick="showProviderModal('{{ $product['id'] }}', '{{ $product['title'] }}', '{{ strtolower($product['code']) }}')"
                       class="flex-shrink-0 group">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center hover:from-green-200 hover:to-green-100 transition-all duration-200 shadow-sm group-hover:shadow-md">
                            <div class="w-10 h-10 flex items-center justify-center">
                                @if(str_contains($product['large_icon_url'], '.svg'))
                                    <div class="w-8 h-8 service-icon" style="background-image: url('{{ $product['large_icon_url'] }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                                @else
                                    <img src="{{ $product['large_icon_url'] }}" alt="{{ $product['title'] }}" class="w-8 h-8 object-contain" loading="lazy"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">
                                        {{ strtoupper(substr($product['title'], 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-center mt-2 text-gray-600 font-medium">{{ $product['title'] }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- All Services --}}
        <h2 id="servicesTitle" class="text-xl font-bold text-gray-800 mb-4">
            {{ $isSearching ?? false ? 'Search Results' : 'All Services' }}
        </h2>
        <div id="productList" class="space-y-3">
            @forelse($products as $product)
                <button onclick="showProviderModal('{{ $product['id'] }}', '{{ $product['title'] }}', '{{ strtolower($product['code']) }}')" class="w-full block group">
                    <div class="flex items-center space-x-4 p-4 border border-gray-100 rounded-xl hover:border-green-200 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-sm">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-white transition-colors duration-200">
                            @if(str_contains($product['icon_url'], '.svg'))
                                <div class="w-6 h-6 service-icon" style="background-image: url('{{ $product['icon_url'] }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                            @else
                                <img src="{{ $product['icon_url'] }}" alt="{{ $product['title'] }}" class="w-6 h-6 object-contain" loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">
                                    {{ strtoupper(substr($product['title'], 0, 2)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-800 group-hover:text-green-700 transition-colors">
                                {{ $product['title'] }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">{{ strtoupper($product['code']) }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </button>
            @empty
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-gray-500">No services found matching your search.</p>
                </div>
            @endforelse
        </div>

        <div id="loading" class="text-center py-6 hidden">
            <div class="inline-flex items-center space-x-2 text-gray-500">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading more services...</span>
            </div>
        </div>

        <div id="endOfList" class="text-center py-6 text-gray-500 text-sm hidden">
            You've reached the end of the list
        </div>
    </div>
</div>

{{-- ─── Provider Selection Modal ─── --}}
<div id="providerModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 id="modalServiceTitle" class="text-xl font-bold text-white">Select Provider</h2>
                    <p class="text-green-100 text-sm mt-1">Choose your preferred SMS provider</p>
                </div>
                <button onclick="closeProviderModal()" class="text-white hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-4">

            {{-- Provider 1: 5sim --}}
            @if($hasFivesim ?? false)
            <button onclick="selectProvider('provider1')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:shadow-lg transition">
                        EG1
                    </div>
                    <div class="flex-1 text-left">
                        <h3 class="font-bold text-gray-800 group-hover:text-blue-700 transition">Provider 1</h3>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-gray-600">Global coverage, multiple operators</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
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
                            <span class="text-xs text-gray-500">Not configured — contact admin</span>
                        </div>
                    </div>
                </div>
            </button>
            @endif

            {{-- Provider 2: SMSBower (new) --}}
            @if($hasSmsBower ?? false)
            <button onclick="selectProvider('provider2')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all duration-200">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:shadow-lg transition">
                        EG2
                    </div>
                    <div class="flex-1 text-left">
                        <h3 class="font-bold text-gray-800 group-hover:text-green-700 transition">Provider 2</h3>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-gray-600">Fast & reliable</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
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
                            <span class="text-xs text-gray-500">Not configured — contact admin</span>
                        </div>
                    </div>
                </div>
            </button>
            @endif

            {{-- Provider 3: SMS-Bus (was provider2) --}}
            @if($hasSmsbus ?? false)
            <button onclick="selectProvider('provider3')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all duration-200">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:shadow-lg transition">
                        EG3
                    </div>
                    <div class="flex-1 text-left">
                        <h3 class="font-bold text-gray-800 group-hover:text-purple-700 transition">Provider 3</h3>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-xs text-gray-600">Wide country coverage</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </button>
            @else
            <button disabled class="w-full p-5 border-2 border-gray-200 rounded-xl bg-gray-100 text-gray-400 cursor-not-allowed">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gray-400 rounded-lg flex items-center justify-center text-white font-bold text-lg">EG3</div>
                    <div class="flex-1 text-left">
                        <h3 class="font-bold text-gray-600">Provider 3 (Currently not available)</h3>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-block w-2 h-2 bg-red-400 rounded-full"></span>
                            <span class="text-xs text-gray-500">Currently not available — contact admin</span>
                        </div>
                    </div>
                </div>
            </button>
            @endif

        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
            <p class="text-xs text-gray-600 text-center">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                All providers offer secure SMS verification services
            </p>
        </div>
    </div>
</div>

<style>
.service-icon { filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1)); transition: filter 0.2s ease; }
.group:hover .service-icon { filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15)); }
.overflow-x-auto::-webkit-scrollbar { height: 4px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #10b981; border-radius: 2px; }
</style>

<script>
    let nextPage      = {{ $nextPage ?? 'null' }};
    let isLoading     = false;
    let hasMorePages  = {{ $nextPage ? 'true' : 'false' }};
    let isSearching   = {{ $isSearching ?? false ? 'true' : 'false' }};
    let currentSearch = '';

    // Selected service state
    let selectedServiceId   = null;
    let selectedServiceName = null;
    let selectedServiceCode = null;

    function getPreferredProvider(serviceCode, serviceId) {
        try {
            // check specific keys first, then fallback to global empty key
            const keys = [];
            if (serviceCode) keys.push('preferredProvider:' + serviceCode);
            if (serviceId)   keys.push('preferredProvider:' + serviceId);
            keys.push('preferredProvider:');

            for (const k of keys) {
                const v = localStorage.getItem(k);
                if (v) return v;
            }
        } catch (e) { /* ignore storage errors */ }
        return null;
    }

    function showProviderModal(serviceId, serviceName, serviceCode) {
        // skip modal if user already selected a preferred provider for this service or globally
        const saved = getPreferredProvider(serviceCode, serviceId);
        if (saved) {
            navigateToProvider(saved, serviceId, serviceCode);
            return;
        }

        selectedServiceId   = serviceId;
        selectedServiceName = serviceName;
        selectedServiceCode = serviceCode;
        document.getElementById('modalServiceTitle').textContent = serviceName;
        document.getElementById('providerModal').classList.remove('hidden');
    }

    function closeProviderModal() {
        document.getElementById('providerModal').classList.add('hidden');
    }

    function selectProvider(provider) {
        // persist preference (per-service if available, otherwise global)
        try {
            const key = 'preferredProvider:' + (selectedServiceCode || selectedServiceId || '');
            if (key) localStorage.setItem(key, provider);
        } catch(e){}
        closeProviderModal();
        navigateToProvider(provider, selectedServiceId, selectedServiceCode);
    }

    function navigateToProvider(provider, serviceId, serviceCode) {
        if (provider === 'provider1') {
            window.location.href = `/app/services/${serviceCode || serviceId}/provider1/countries`;
        } else if (provider === 'provider2') {
            window.location.href = `/app/services/${serviceCode || serviceId}/provider2/rank`;
        } else {
            window.location.href = `/app/services/${serviceCode || serviceId}/countries-provider3`;
        }
    }

    // Close on escape / outside click
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProviderModal(); });
    document.getElementById('providerModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeProviderModal(); });

    // ── Infinite scroll ──────────────────────────────────────────
    window.addEventListener('scroll', function () {
        if (isLoading || !hasMorePages || !nextPage || isSearching) return;
        const scrollTop      = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight   = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        if (scrollTop + windowHeight >= documentHeight - 400) loadMore();
    });

    function loadMore() {
        if (isLoading || !hasMorePages || !nextPage || isSearching) {
            if (!hasMorePages && !isSearching) document.getElementById('endOfList').classList.remove('hidden');
            return;
        }
        isLoading = true;
        document.getElementById('loading').classList.remove('hidden');

        // include provider filter (URL or preferred) so next pages come from provider-specific endpoint
        const params = new URLSearchParams();
        params.set('page', nextPage);
        params.set('ajax', '1');
        const urlProvider = new URLSearchParams(window.location.search).get('provider');
        const preferred = urlProvider || getPreferredProvider('', '');
        if (preferred) params.set('provider', preferred);

        fetch(`${window.location.pathname}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.html && data.html.trim() !== '') {
                document.getElementById('productList').insertAdjacentHTML('beforeend', data.html);
                nextPage     = data.next_page;
                hasMorePages = data.has_more || (data.next_page !== null);
                if (!hasMorePages) document.getElementById('endOfList').classList.remove('hidden');
            } else {
                hasMorePages = false;
                document.getElementById('endOfList').classList.remove('hidden');
            }
        })
        .catch(() => { hasMorePages = false; })
        .finally(() => {
            isLoading = false;
            document.getElementById('loading').classList.add('hidden');
        });
    }

    // ── Search ───────────────────────────────────────────────────
    let searchTimeout;
    const searchInput    = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', function () {
        const filter = this.value.trim();
        clearSearchBtn.classList.toggle('hidden', !filter);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => performSearch(filter), 300);
    });

    clearSearchBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        performSearch('');
    });

    function performSearch(filter) {
        currentSearch = filter;
        isSearching   = filter !== '';

        const url = new URL(window.location);
        filter ? url.searchParams.set('search', filter) : url.searchParams.delete('search');
        // ensure provider param is preserved (or injected from preference)
        const urlProvider = new URLSearchParams(window.location.search).get('provider');
        const preferred = urlProvider || getPreferredProvider('', '');
        if (preferred) url.searchParams.set('provider', preferred); else url.searchParams.delete('provider');
        window.history.replaceState({}, '', url);

        document.getElementById('loading').classList.remove('hidden');

        // include provider filter from URL or preferred provider
        const params = new URLSearchParams();
        params.set('ajax', '1');
        if (filter) params.set('search', filter);

        // prefer URL provider param, otherwise use stored preferred provider (global)
        const urlProvider = new URLSearchParams(window.location.search).get('provider');
        const preferred = urlProvider || getPreferredProvider('', '');
        if (preferred) params.set('provider', preferred);

        fetch(`?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('productList').innerHTML = data.html || '<div class="text-center py-12"><p class="text-gray-500">No services found.</p></div>';
            document.getElementById('servicesTitle').textContent = filter ? 'Search Results' : 'All Services';
            document.getElementById('popularSection').classList.toggle('hidden', !!filter);
            nextPage     = data.next_page;
            hasMorePages = data.has_more || false;
            isSearching  = data.is_searching || false;
            document.getElementById('endOfList').classList.add('hidden');
        })
        .catch(() => {
            document.getElementById('productList').innerHTML = '<div class="text-center py-12"><p class="text-red-500">Error loading services.</p></div>';
        })
        .finally(() => { document.getElementById('loading').classList.add('hidden'); });
    }

    // Init from URL
    document.addEventListener('DOMContentLoaded', function () {
        const searchTerm = new URLSearchParams(window.location.search).get('search');
        if (searchTerm) {
            searchInput.value = searchTerm;
            currentSearch     = searchTerm;
            isSearching       = true;
            clearSearchBtn.classList.remove('hidden');
        }

        // If user has a preferred provider globally and URL lacks provider=, redirect so server returns provider-scoped services
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const hasProviderParam = urlParams.has('provider');
            const globalPref = getPreferredProvider('', '');
            if (globalPref && !hasProviderParam) {
                urlParams.set('provider', globalPref);
                // preserve existing search param
                const redirectUrl = window.location.pathname + '?' + urlParams.toString();
                window.location.replace(redirectUrl);
            }
        } catch (e) { /* ignore */ }
    });
</script>
@endsection