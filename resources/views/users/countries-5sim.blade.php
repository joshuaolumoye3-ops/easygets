@extends('layouts.app')

@section('title', 'Countries - Provider 1')

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
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition duration-200"
            >
        </div>

        <!-- Service Info -->
        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <span class="text-lg">📱</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Selected Service</p>
                        <p class="font-bold text-gray-800">{{ ucfirst($serviceCode) }}</p>
                    </div>
                </div>
                <span id="countryCount" class="text-xs font-semibold text-blue-700 bg-blue-100 px-3 py-1 rounded-full">
                    {{ count($countries) }} countries
                </span>
            </div>
        </div>

        <!-- All Countries -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Countries</h2>
            <div id="countryList" class="space-y-3">
                @forelse ($countries as $country)
                    <a href="{{ route('operators.provider1', ['serviceCode' => $serviceCode, 'country' => $country['name']]) }} " 
                       class="block group country-item" data-country-name="{{ strtolower($country['name']) }}" data-country-code="{{ strtolower($country['code']) }}">
                        <div class="flex items-center justify-between p-4 bg-white border-2 border-gray-100 rounded-xl hover:border-blue-500 hover:bg-blue-50/50 transition-all duration-200 group-hover:shadow-md">
                            <div class="flex items-center space-x-4 flex-1">
                                <img class="w-12 h-12 rounded-full object-cover shadow-sm" 
                                     src="https://flagcdn.com/w80/{{ $country['code'] }}.png" 
                                     onerror="this.src='https://via.placeholder.com/48?text={{ strtoupper(substr($country['name'],0,1)) }}'" 
                                     alt="{{ ucfirst($country['name']) }}">
                                <div class="flex-1">
                                    <span class="font-semibold text-gray-800 group-hover:text-blue-700 transition country-name block">
                                        {{ ucfirst($country['name']) }}
                                    </span>
                                    <div class="flex items-center space-x-3 mt-1">
                                        <span class="text-xs text-gray-500">
                                            Available: {{ number_format($country['qty']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <div class="flex items-center space-x-2">
                                    <div>
                                        <p class="text-lg font-bold text-green-600">
                                            View Listing
                                        </p>
                                        {{-- <p class="text-xs text-gray-400 mt-0.5">
                                            ${{ number_format($country['price_usd'], 2) }}
                                        </p> --}}
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-12" id="emptyState">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        <p class="text-gray-500 font-medium mb-2">No countries available</p>
                        <p class="text-sm text-gray-400 mb-4">This service may not be available yet</p>
                        <a href="{{ route('services.list') }}" class="inline-block px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                            ← Back to services
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- No Results Message -->
            <div id="noResults" class="text-center py-12 hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="text-gray-500 font-medium mb-2">No countries found</p>
                <p class="text-sm text-gray-400">Try adjusting your search</p>
            </div>
        </div>

        {{-- @if(count($countries) > 0)
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start space-x-2">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-xs text-blue-800">
                    <p class="font-medium mb-1">Pricing Information:</p>
                    <p>Prices shown in Naira (₦) with USD reference. Exchange rate: ₦{{ number_format($exchangeRate ?? 1600, 2) }}</p>
                </div>
            </div>
        </div>
        @endif --}}
    </div>
</div>

<script>
    const serviceCode = "{{ $serviceCode }}";

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function () {
        const raw = this.value || '';
        const q = raw.toLowerCase().trim();
        const items = document.querySelectorAll('.country-item');
        let visibleCount = 0;

        const aliasMap = {
            'usa': 'united states', 'us': 'united states', 'u.s.a': 'united states',
            'uk': 'united kingdom', 'gb': 'united kingdom',
            'uae': 'united arab emirates',
            'drc': 'democratic republic of the congo',
            'ivory coast': "cote d'ivoire", 'cote d ivoire': "cote d'ivoire",
        };

        const norm = (s) => String(s || '').toLowerCase().replace(/[\._,\/\\]/g, ' ').replace(/\s+/g, ' ').trim();
        const mapped = aliasMap[q] || q;

        items.forEach(item => {
            const countryName = norm(item.getAttribute('data-country-name') || '');
            const countryCode = String(item.getAttribute('data-country-code') || '').toLowerCase();

            const match = (
                countryName.includes(mapped) ||
                countryName.includes(q) ||
                countryCode === q ||
                countryCode === mapped
            );

            if (match) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Update count
        document.getElementById('countryCount').textContent = visibleCount + ' countries';

        // Show/hide no results message
        const noResults = document.getElementById('noResults');
        const emptyState = document.getElementById('emptyState');
        
        if (visibleCount === 0 && items.length > 0) {
            noResults.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        } else {
            noResults.classList.add('hidden');
            if (emptyState && items.length === 0) emptyState.classList.remove('hidden');
        }
    });
</script>

<style>
.country-item {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.country-item:hover {
    transform: translateY(-2px);
}
</style>
@endsection