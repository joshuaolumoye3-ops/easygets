@extends('layouts.app')

@section('title', 'Select Country - Provider 2')

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
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition duration-200"
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
                        <p class="font-bold text-gray-800">Provider 2</p>
                    </div>
                </div>
                <span id="countryCount" class="text-xs font-semibold text-green-700 bg-green-100 px-3 py-1 rounded-full">
                    {{ count($countries) }} countries
                </span>
            </div>
        </div>

        <!-- Countries List -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Countries</h2>

            <div id="countryList" class="space-y-3">
                @forelse ($countries as $country)
                    <button
                        onclick="buyNow('{{ addslashes($country['country_id']) }}', '{{ addslashes($country['title']) }}', '{{ $country['code'] }}')"
                        class="w-full block group country-item"
                        data-country-name="{{ strtolower($country['title']) }}"
                        data-country-code="{{ strtolower($country['code']) }}">

                        <div class="flex items-center justify-between p-4 bg-white border-2 border-gray-100 rounded-xl hover:border-green-500 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-md">
                            <div class="flex items-center space-x-4 flex-1">
                                <img
                                    class="w-12 h-12 rounded-full object-cover shadow-sm"
                                    src="https://flagcdn.com/w80/{{ $country['code'] }}.png"
                                    onerror="this.src='https://via.placeholder.com/48?text={{ strtoupper(substr($country['title'],0,1)) }}'"
                                    alt="{{ $country['title'] }}"
                                >

                                <div class="flex-1">
                                    <span class="font-semibold text-gray-800 group-hover:text-green-700 transition country-name block">
                                        {{ $country['title'] }}
                                    </span>

                                    <div class="flex items-center space-x-3 mt-1">
                                        <span class="text-xs text-gray-500">
                                            Available: {{ number_format($country['total_count']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0 ml-4">
                                <div class="flex items-center space-x-2">
                                    <div>
                                        <p class="text-sm font-semibold text-green-600 whitespace-nowrap">View listings</p>
                                    </div>

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>

                        <p class="text-gray-500 font-medium mb-2">No countries available</p>
                        <p class="text-sm text-gray-400 mb-4">This service may not be available yet</p>

                        <a href="{{ route('services.list') }}" class="inline-block px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                            ← Back to services
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- No Results -->
            <div id="noResults" class="text-center py-12 hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>

                <p class="text-gray-500 font-medium mb-2">No countries found</p>
                <p class="text-sm text-gray-400">Try adjusting your search</p>
            </div>
        </div>
    </div>
</div>

<script>
    const serviceCode = "{{ $serviceCode }}";

    function buyNow(countryId, countryName, countryCode) {
        const url = `/app/services/${serviceCode}/countries-provider2/${countryId}/offers`
            + `?country_name=${encodeURIComponent(countryName)}&country_code=${encodeURIComponent(countryCode)}`;

        window.location.href = url;
    }

    document.getElementById('searchInput').addEventListener('input', function () {
        const raw = this.value || '';
        const q = raw.toLowerCase().trim();
        const items = document.querySelectorAll('.country-item');
        let visible = 0;

        // Simple alias map for common short forms
        const aliasMap = {
            'usa': 'united states', 'us': 'united states', 'u.s.a': 'united states',
            'uk': 'united kingdom', 'gb': 'united kingdom',
            'uae': 'united arab emirates',
            'south korea': 'korea', 'korea': 'korea',
            'drc': 'democratic republic of the congo',
            'ivory coast': "cote d'ivoire", 'cote d ivoire': "cote d'ivoire",
        };

        const norm = (s) => String(s || '').toLowerCase().replace(/[\._,\/\\]/g, ' ').replace(/\s+/g, ' ').trim();
        const mapped = aliasMap[q] || q;

        items.forEach(item => {
            const name = norm(item.getAttribute('data-country-name') || '');
            const code = String(item.getAttribute('data-country-code') || '').toLowerCase();

            const matches = (
                name.includes(mapped) ||
                name.includes(q) ||
                code === q ||
                code === mapped
            );

            item.style.display = matches ? 'block' : 'none';
            if (matches) visible++;
        });

        document.getElementById('countryCount').textContent = visible + ' countries';
        document.getElementById('noResults').classList.toggle('hidden', visible > 0 || items.length === 0);
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