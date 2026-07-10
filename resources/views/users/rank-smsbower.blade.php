@extends('layouts.app')

@section('title', 'Select Rank - Provider 2')

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
            <h1 class="text-2xl font-bold text-gray-800">Select Rank</h1>
        </div>

        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                    <span class="text-lg">📱</span>
                </div>
                <div>
                    <p class="text-xs text-gray-600">Selected Service</p>
                    <p class="font-bold text-gray-800">{{ ucfirst($serviceCode) }}</p>
                </div>
            </div>
        </div>

        <p class="text-sm text-gray-500 mb-6">
            Choose a position rank. Higher ranks generally mean more available numbers.
        </p>

        <div class="space-y-4">

            {{-- All --}}
            <button onclick="selectRank('all')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-gray-400 hover:bg-gray-50 transition-all duration-200 text-left">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800">All</h3>
                        <p class="text-xs text-gray-500 mt-1">Show every available position, no filtering</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </button>

            {{-- Gold --}}
            <button onclick="selectRank('gold')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-yellow-500 hover:bg-yellow-50 transition-all duration-200 text-left">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                        GOLD
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 group-hover:text-yellow-700 transition">Gold</h3>
                        <p class="text-xs text-gray-600 mt-1">Highest volume — best availability</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </button>

            {{-- Silver --}}
            <button onclick="selectRank('silver')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-gray-400 hover:bg-gray-100 transition-all duration-200 text-left">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-gray-400 to-gray-500 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                        SILVER
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 transition">Silver</h3>
                        <p class="text-xs text-gray-600 mt-1">Good volume, solid availability</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </button>

            {{-- Bronze --}}
            <button onclick="selectRank('bronze')"
                    class="w-full group p-5 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all duration-200 text-left">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                        BRZ
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 group-hover:text-orange-700 transition">Bronze</h3>
                        <p class="text-xs text-gray-600 mt-1">Rare countries or limited budget</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </button>

        </div>
    </div>
</div>

<script>
    const serviceCode = "{{ $serviceCode }}";

    function selectRank(rank) {
        const url = rank === 'all'
            ? `/app/services/${serviceCode}/countries-provider2`
            : `/app/services/${serviceCode}/countries-provider2?rank=${rank}`;
        window.location.href = url;
    }
</script>
@endsection