@extends('layouts.app')

@section('title', 'Select Number - Provider 2')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        <div class="relative flex items-center justify-center mb-6">
            <a href="javascript:history.back()" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Select Number</h1>
        </div>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 text-sm">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img class="w-10 h-10 rounded-full object-cover shadow-sm"
                         src="https://flagcdn.com/w80/{{ $countryCode }}.png"
                         onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($countryName,0,1)) }}'"
                         alt="{{ $countryName }}">
                    <div>
                        <p class="text-xs text-gray-600">{{ $serviceName }}</p>
                        <p class="font-bold text-gray-800">{{ $countryName }}</p>
                    </div>
                </div>
                <span class="text-xs font-semibold text-green-700 bg-green-100 px-3 py-1 rounded-full">
                    {{ count($offers) }} listings
                </span>
            </div>
        </div>

        <div class="space-y-3">
            @forelse ($offers as $offer)
                <button onclick="buyNow('{{ $offer['provider_id'] }}')" class="w-full block group">
                    <div class="flex items-center justify-between p-4 bg-white border-2 border-gray-100 rounded-xl hover:border-green-500 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-md">
                        <div class="flex items-center space-x-4">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold text-white
                                {{ $offer['rank'] === 'gold' ? 'bg-yellow-500' : ($offer['rank'] === 'silver' ? 'bg-gray-400' : 'bg-orange-500') }}">
                                {{ ucfirst($offer['rank']) }}
                            </span>
                            <div class="text-left">
                                <p class="text-xs text-gray-500">ID: {{ $offer['provider_id'] }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ number_format($offer['count']) }} pcs</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <p class="text-lg font-bold text-green-600">₦{{ number_format($offer['cost'], 2) }}</p>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </button>
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500 font-medium mb-2">No number listings available right now</p>
                    <a href="javascript:history.back()" class="inline-block px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                        ← Back to countries
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>

<form id="purchaseForm" action="{{ route('prepare.purchase.smsbower') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="service"      value="{{ $serviceCode }}">
    <input type="hidden" name="country"      value="{{ $countryId }}">
    <input type="hidden" name="country_name" value="{{ $countryName }}">
    <input type="hidden" name="country_code" value="{{ $countryCode }}">
    <input type="hidden" name="provider_id"  id="formProviderId">
</form>

<script>
    function buyNow(providerId) {
        document.getElementById('formProviderId').value = providerId;
        document.getElementById('purchaseForm').submit();
    }
</script>
@endsection