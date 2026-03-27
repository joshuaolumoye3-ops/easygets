@extends('layouts.app')

@section('title', 'Countries')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('dashboard') }}" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">eSIM</h1>
        </div>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if(empty($countries))
            <div class="text-center py-16">
                <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Countries Available</h3>
                <p class="text-gray-500 mb-4">eSIM countries are being set up. Please check back soon.</p>
                <a href="{{ route('dashboard') }}" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Back to Dashboard
                </a>
            </div>
        @else
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

            <!-- All Countries -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4">All Countries ({{ count($countries) }})</h2>
                <div id="productList" class="space-y-4">
                    @foreach ($countries as $country)
                        <a href="{{ route('countries.plan', $country['code']) }}" 
                            class="w-full flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-green-500 transition-all duration-200 product-item group"
                            data-country-code="{{ strtolower($country['code']) }}" 
                            data-country-name="{{ strtolower($country['title']) }}">
                            <div class="flex items-center flex-1">
                                <img class="w-12 h-12 rounded-full mr-4 object-cover ring-2 ring-gray-100 group-hover:ring-green-500 transition"
                                    src="{{ $country['flag_url'] ?? 'https://flagcdn.com/w80/' . strtolower($country['code']) . '.png' }}"
                                    onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($country['code'], 0, 1)) }}'"
                                    alt="{{ $country['title'] }}">
                                <div class="flex-1">
                                    <span class="font-semibold text-gray-700 block product-name">{{ $country['title'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $country['packages_count'] }} {{ Str::plural('package', $country['packages_count']) }} available</span>
                                    @if(!empty($country['local_number_available']))
                                        <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Local number</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-green-600 font-bold text-lg block">₦{{ number_format($country['price'], 2) }}</span>
                                <span class="text-xs text-gray-500">Starting from</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- No Results Message -->
                <div id="noResults" class="hidden text-center py-8">
                    <p class="text-gray-500">No countries match your search</p>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const productItems = document.querySelectorAll('.product-item');
    const noResults = document.getElementById('noResults');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            productItems.forEach(item => {
                const name = item.dataset.countryName;
                const code = item.dataset.countryCode;
                
                if (name.includes(query) || code.includes(query)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (noResults) {
                noResults.classList.toggle('hidden', visibleCount > 0);
            }
        });
    }
</script>
@endsection