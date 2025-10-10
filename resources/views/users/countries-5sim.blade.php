@extends('layouts.app')

@section('title', 'Countries - 5sim')

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

        <!-- Provider Badge -->
        <div class="mb-6 flex items-center justify-center">
            <div class="inline-flex items-center space-x-2 bg-blue-50 px-4 py-2 rounded-full">
                <div class="w-6 h-6 bg-blue-600 rounded-md flex items-center justify-center text-white text-xs font-bold">
                    5S
                </div>
                <span class="text-sm font-medium text-blue-900">Provider 1 (5sim.net)</span>
            </div>
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
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition duration-200"
            >
        </div>

        <!-- All Countries -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Countries</h2>
            <div id="countryList" class="space-y-3">
                @forelse ($countries as $country)
                    <a href="{{ route('services.5sim.operators', ['serviceId' => $serviceId, 'country' => $country['name']]) }}" 
                       class="block group">
                        <div class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl hover:border-blue-200 hover:bg-blue-50/50 transition-all duration-200 group-hover:shadow-sm">
                            <div class="flex items-center space-x-4">
                                <img class="w-10 h-10 rounded-full object-cover shadow-sm" 
                                     src="https://flagcdn.com/w80/{{ $country['code'] }}.png" 
                                     onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($country['name'],0,1)) }}'" 
                                     alt="{{ ucfirst($country['name']) }}">
                                <div>
                                    <span class="font-semibold text-gray-800 group-hover:text-blue-700 transition">
                                        {{ ucfirst($country['name']) }}
                                    </span>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ count($country['operators']) }} operator(s) available
                                    </p>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                        </svg>
                        <p class="text-gray-500">No countries available for this service.</p>
                        <a href="{{ route('services.list') }}" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">
                            ← Back to services
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($countries->hasPages())
        <div class="mt-6">
            {{ $countries->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    const serviceId = "{{ $serviceId }}";

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function () {
        const filter = this.value.toLowerCase().trim();
        const items = document.querySelectorAll('#countryList > a');
        
        items.forEach(item => {
            const countryName = item.textContent.toLowerCase();
            if (countryName.includes(filter)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endsection