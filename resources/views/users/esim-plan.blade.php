@extends('layouts.app')

@section('title', $country['title'])

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-3xl">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
            <h1 class="text-xl font-bold text-gray-800 flex items-center">
                <img class="w-6 h-6 mr-2" src="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" alt="{{ $country['title'] }}">
                {{ $country['title'] }}
            </h1>
            <span class="text-sm text-gray-500">USD</span>
        </div>

        <!-- Packages -->
        <h2 class="text-lg font-bold text-gray-800 mt-4 mb-4">{{ count($packages) }} packages available</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            @foreach ($packages as $package)
                <button 
                    class="p-4 border rounded-xl flex flex-col items-start justify-between hover:shadow-md transition relative package-btn"
                    data-id="{{ $package['id'] }}" 
                    data-title="{{ $package['title'] }}" 
                    data-price="{{ $package['price'] }}" 
                    data-validity="{{ $package['validity'] }}">
                    
                    @if(!empty($package['badge']))
                        <span class="absolute top-2 right-2 text-xs px-2 py-1 rounded-full {{ $package['badgeColor'] }} text-white">
                            {{ $package['badge'] }}
                        </span>
                    @endif

                    <h3 class="text-lg font-bold text-gray-800">{{ $package['title'] }}</h3>
                    <p class="text-sm text-gray-500 mb-2">Valid for {{ $package['validity'] }}</p>
                    <span class="text-gray-900 font-bold">₦{{ $package['price'] }}</span>
                </button>
            @endforeach
        </div>

        <!-- eSIM Features -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-16">
            <h3 class="text-lg font-bold mb-4">eSIM features</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="font-semibold">Network</p>
                    <p>{{ $features['network'] }}</p>
                </div>
                <div>
                    <p class="font-semibold">Speed</p>
                    <p>{{ $features['speed'] }}</p>
                </div>
                <div>
                    <p class="font-semibold">Phone number</p>
                    <p>{{ $features['phone'] }}</p>
                </div>
                <div>
                    <p class="font-semibold">Top-up</p>
                    <p>{{ $features['topup'] }}</p>
                </div>
            </div>
        </div>

        <!-- Footer Checkout -->
        <div id="checkoutBar" class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-sm p-4 flex items-center justify-between">
            <div>
                <p id="selectedPrice" class="text-lg font-bold">Select a package</p>
                <p id="selectedInfo" class="text-sm text-gray-500"></p>
            </div>
            <button id="checkoutBtn" 
                class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg disabled:opacity-50" 
                disabled>Checkout</button>
        </div>

    </div>
</div>

<script>
    const packageButtons = document.querySelectorAll('.package-btn');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const selectedPrice = document.getElementById('selectedPrice');
    const selectedInfo = document.getElementById('selectedInfo');

    let selectedPackage = null;
    const countryCode = @json($country['code']); // Pass country code to JS

    packageButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            packageButtons.forEach(b => b.classList.remove('border-blue-600'));
            btn.classList.add('border-blue-600');

            selectedPackage = {
                id: btn.dataset.id,
                title: btn.dataset.title,
                price: btn.dataset.price,
                validity: btn.dataset.validity
            };

            selectedPrice.textContent = `₦${selectedPackage.price}`;
            selectedInfo.textContent = `${selectedPackage.title} / ${selectedPackage.validity}`;
            checkoutBtn.disabled = false;
        });
    });

    checkoutBtn.addEventListener('click', () => {
        if (selectedPackage) {
            window.location.href = `/checkout/${countryCode}/${selectedPackage.id}`;
        }
    });
</script>
@endsection
