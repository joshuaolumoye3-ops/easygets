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
            <h1 class="text-2xl font-bold text-gray-800">Esim</h1>
        </div>

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
            <h2 class="text-xl font-bold text-gray-800 mb-4">All Countries</h2>
            <div id="productList" class="space-y-4">
                @foreach ($countries as $country)
                    <a href="{{ route('countries.plan', $country['code']) }}" 
                        class="w-full flex items-center justify-between p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200 product-item"
                        data-country-code="{{ $country['code'] }}" data-country-name="{{ strtolower($country['title']) }}">
                            <div class="flex items-center">
                                <img class="w-10 h-10 rounded-full mr-4 object-cover"
                                    src="https://flagcdn.com/w80/{{ strtolower($country['code']) }}.png"
                                    onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($country['code'], 0, 1)) }}'"
                                    alt="{{ $country['title'] }}">
                                <span class="font-semibold text-gray-700 product-name">{{ $country['title'] }}</span>
                            </div>
                            <span class="ml-4 text-green-600 font-bold">${{ $country['price'] }}</span>
                    </a>
                @endforeach
            </div>
            <!-- Loader -->
            <div id="loader" class="text-center py-4 hidden text-gray-400">Loading more...</div>
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const productItems = document.querySelectorAll('.product-item');

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();

        productItems.forEach(item => {
            const name = item.dataset.countryName;
            if (name.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endsection
