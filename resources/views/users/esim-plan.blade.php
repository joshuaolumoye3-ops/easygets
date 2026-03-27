@extends('layouts.app')

@section('title', $country['title'])

@section('content')
<div class="bg-slate-50 min-h-screen font-[Inter]">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="{{ url()->previous() }}" class="flex items-center text-sm text-slate-500 hover:text-slate-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>

            <span class="text-sm font-medium text-slate-500">USD</span>
        </div>

        <!-- Country Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-8 flex flex-col gap-3">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center">
                    <img class="w-8 h-8 rounded-sm mr-3" src="https://flagcdn.com/w40/{{ strtolower($country['code']) }}.png" alt="{{ $country['title'] }}">
                    <h1 class="text-xl font-semibold text-slate-800">{{ $country['title'] }}</h1>

                    @if(!empty($country['local_number_available']))
                        <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                            Local number available
                        </span>
                    @endif
                </div>

                <div class="text-sm text-slate-500">
                    Phone number:
                    <span class="font-semibold text-slate-800">{{ $features['phone'] }}</span>
                </div>
            </div>
        </div>

        <!-- Packages -->
        <div class="mb-10">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">
                {{ count($packages) }} packages available
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                @foreach ($packages as $package)
                    <button
                        class="package-btn group relative w-full rounded-2xl border border-slate-200 bg-white p-5 text-left transition-all hover:border-blue-600 hover:shadow-md focus:outline-none"
                        data-id="{{ $package['id'] }}"
                        data-title="{{ $package['title'] }}"
                        data-price="{{ $package['price'] }}"
                        data-validity="{{ $package['validity'] }}">

                        @if(!empty($package['badge']))
                            <span class="absolute top-3 right-3 text-xs px-2 py-0.5 rounded-full {{ $package['badgeColor'] }} text-white">
                                {{ $package['badge'] }}
                            </span>
                        @endif

                        <h3 class="text-base font-semibold text-slate-800 mb-1">
                            {{ $package['title'] }}
                        </h3>

                        <p class="text-sm text-slate-500 mb-4">
                            Valid for {{ $package['validity'] }}
                        </p>

                        <span class="text-lg font-bold text-slate-900">
                            ₦{{ $package['price'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- eSIM Features -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-24">
            <h3 class="text-lg font-semibold text-slate-800 mb-5">eSIM features</h3>

            <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="font-medium text-slate-600">Network</p>
                    <p class="text-slate-800">{{ $features['network'] }}</p>
                </div>
                <div>
                    <p class="font-medium text-slate-600">Speed</p>
                    <p class="text-slate-800">{{ $features['speed'] }}</p>
                </div>
                <div>
                    <p class="font-medium text-slate-600">Phone number</p>
                    <p class="text-slate-800">{{ $features['phone'] }}</p>
                </div>
                <div>
                    <p class="font-medium text-slate-600">Top-up</p>
                    <p class="text-slate-800">{{ $features['topup'] }}</p>
                </div>
            </div>
        </div>

        <!-- Checkout Bar -->
        <div id="checkoutBar" class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-lg">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <p id="selectedPrice" class="text-lg font-semibold text-slate-900">
                        Select a package
                    </p>
                    <p id="selectedInfo" class="text-sm text-slate-500"></p>
                </div>

                <button
                    id="checkoutBtn"
                    class="px-7 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Checkout
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    const packageButtons = document.querySelectorAll('.package-btn');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const selectedPrice = document.getElementById('selectedPrice');
    const selectedInfo = document.getElementById('selectedInfo');

    let selectedPackage = null;
    const countryCode = @json($country['code']);

    packageButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            packageButtons.forEach(b => b.classList.remove('border-blue-600', 'ring-1', 'ring-blue-600'));
            btn.classList.add('border-blue-600', 'ring-1', 'ring-blue-600');

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
            window.location.href = `/app/checkout/${countryCode}/${selectedPackage.id}`;
        }
    });
</script>
@endsection
