@extends('layouts.app')

@section('title', 'Countries')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('services.list') }}" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Select Country</h1>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
            </div>
        @endif

        {{-- <!-- Exchange Rate Info -->
        @if(isset($exchangeRate))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center space-x-2 text-sm text-blue-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Exchange Rate: ₦{{ number_format($exchangeRate, 2) }}/USD</span>
                </div>
            </div>
        @endif --}}

        <!-- Search Bar -->
        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input 
                id="searchInput"
                type="text" 
                placeholder="Search countries..."
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition duration-200"
            >
        </div>

        <!-- Countries List -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Countries</h2>
            <div id="productList" class="space-y-3">
                @forelse ($countries as $country)
                    <button onclick="buyNow({{ $country['project_id'] }}, {{ $country['country_id'] }}, '{{ addslashes($country['title']) }}', '{{ $country['code'] ?? '' }}')" 
                            class="w-full group country-item">
                        <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-center space-x-4 flex-1">
                                <img class="w-12 h-12 rounded-full object-cover shadow-sm" 
                                    src="{{ $country['flag_url'] }}" 
                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($country['title'], 0, 2)) }}&background=10b981&color=ffffff&size=48'" 
                                    alt="{{ $country['title'] }}">
                                <div class="flex-1 text-left">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-green-700 transition-colors country-name">
                                        {{ $country['title'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Available: <span class="font-medium text-green-600">{{ number_format($country['total_count']) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <div class="flex items-center space-x-2">
                                    <div>
                                        <p class="text-lg font-bold text-green-600">
                                            ₦{{ number_format($country['cost'], 2) }}
                                        </p>
                                        {{-- @if(isset($country['ws_cost']) && $country['ws_cost'] < $country['cost'])
                                            <p class="text-xs text-gray-500 line-through">
                                                ₦{{ number_format($country['ws_cost'], 2) }}
                                            </p>
                                        @endif --}}
                                        {{-- @if(isset($country['cost_usd']))
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                ${{ number_format($country['cost_usd'], 2) }}
                                            </p>
                                        @endif --}}
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 20h.01" />
                        </svg>
                        <p class="text-gray-500">No countries available for this service.</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Loader -->
            <div id="loader" class="text-center py-6 hidden">
                <div class="inline-flex items-center space-x-2 text-gray-500">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading more countries...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Purchase -->
<form id="purchaseForm" action="{{ route('prepare.purchase') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="service" id="formService">
    <input type="hidden" name="country" id="formCountry">
    <input type="hidden" name="country_name" id="formCountryName">
    <input type="hidden" name="country_code" id="formCountryCode">
</form>

<script>
    const serviceId = "{{ $serviceId }}";
    let currentPage = {{ $countries->currentPage() }};
    let lastPage = {{ $countries->lastPage() }};
    let loading = false;
    let searchQuery = '';

    // Buy Now Action
    function buyNow(projectId, countryId, countryName, countryCode) {
        const form = document.getElementById('purchaseForm');
        document.getElementById('formService').value = projectId;
        document.getElementById('formCountry').value = countryId;
        document.getElementById('formCountryName').value = countryName;
        document.getElementById('formCountryCode').value = countryCode;
        form.submit();
    }

    // Helper: Render Country
    function renderCountry(country) {
        const flagUrl = `https://flagcdn.com/w80/${country.code}.png`;
        const fallbackUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(country.title.substring(0, 2))}&background=10b981&color=ffffff&size=48`;
        const escapedTitle = country.title.replace(/'/g, "\\'");
        
        // Format NGN prices
        const costNGN = new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 2
        }).format(country.cost);
        
        const wsCostNGN = country.ws_cost ? new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 2
        }).format(country.ws_cost) : null;
        
        const costUSD = country.cost_usd ? `$${country.cost_usd.toFixed(2)}` : '';
        
        return `
        <button onclick="buyNow(${country.project_id}, ${country.country_id}, '${escapedTitle}', '${country.code || ''}')" 
                class="w-full group country-item">
            <div class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all duration-200 hover:shadow-md">
                <div class="flex items-center space-x-4 flex-1">
                    <img class="w-12 h-12 rounded-full object-cover shadow-sm" 
                        src="${flagUrl}" 
                        onerror="this.src='${fallbackUrl}'" 
                        alt="${country.title}">
                    <div class="flex-1 text-left">
                        <h3 class="font-semibold text-gray-800 group-hover:text-green-700 transition-colors country-name">
                            ${country.title}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Available: <span class="font-medium text-green-600">${country.total_count.toLocaleString()}</span>
                        </p>
                    </div>
                </div>
                <div class="text-right flex-shrink-0 ml-4">
                    <div class="flex items-center space-x-2">
                        <div>
                            <p class="text-lg font-bold text-green-600">
                                ${costNGN}
                            </p>
                           
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </button>`;
    }

    // Infinite Scroll
    window.addEventListener('scroll', async () => {
        if (loading || searchQuery || currentPage >= lastPage) return;
        
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            loading = true;
            document.getElementById('loader').classList.remove('hidden');
            
            try {
                const res = await fetch(`/services/${serviceId}/countries?page=${currentPage + 1}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await res.json();
                
                if (result.success && result.data) {
                    result.data.forEach(country => {
                        document.getElementById('productList').insertAdjacentHTML('beforeend', renderCountry(country));
                    });
                    currentPage = result.current_page;
                    lastPage = result.last_page;
                }
            } catch (e) {
                console.error("Failed to load more countries", e);
            }
            
            document.getElementById('loader').classList.add('hidden');
            loading = false;
        }
    });

    // Search with debouncing
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', async function () {
        clearTimeout(searchTimeout);
        searchQuery = this.value.trim();
        
        searchTimeout = setTimeout(async () => {
            const list = document.getElementById('productList');
            list.innerHTML = "<p class='text-center text-gray-400 py-8'>Searching...</p>";
            
            try {
                const res = await fetch(`/services/${serviceId}/countries?search=${encodeURIComponent(searchQuery)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await res.json();
                
                list.innerHTML = "";
                
                if (result.success && result.data && result.data.length > 0) {
                    result.data.forEach(country => {
                        list.insertAdjacentHTML('beforeend', renderCountry(country));
                    });
                    currentPage = result.current_page;
                    lastPage = result.last_page;
                } else {
                    list.innerHTML = `
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="text-gray-500">No countries found matching your search.</p>
                        </div>`;
                }
            } catch (e) {
                list.innerHTML = "<p class='text-center text-red-500 py-8'>Error searching countries.</p>";
                console.error("Search error:", e);
            }
        }, 300);
    });
</script>

<style>
.country-item {
    transition: transform 0.2s ease;
}

.country-item:active {
    transform: scale(0.98);
}
</style>
@endsection