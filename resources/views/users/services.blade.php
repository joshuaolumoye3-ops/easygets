@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        {{-- Header --}}
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('dashboard') }}" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Services</h1>
        </div>

        {{-- Search --}}
        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input 
                id="searchInput"
                type="text" 
                placeholder="Search Services..."
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition duration-200"
            >
        </div>

        {{-- Popular Services --}}
        @if(!request('search') && count($popular) > 0)
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Popular Services</h2>
            <div class="flex space-x-4 overflow-x-auto pb-4 -mx-4 px-4">
                @foreach($popular as $product)
                    <a href="{{ route('services.countries', $product['id']) }}" 
                       class="flex-shrink-0 group">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-100 to-green-50 flex items-center justify-center hover:from-green-200 hover:to-green-100 transition-all duration-200 shadow-sm group-hover:shadow-md">
                            <div class="w-10 h-10 flex items-center justify-center">
                                @if(str_contains($product['large_icon_url'], '.svg'))
                                    <div class="w-8 h-8 service-icon" style="background-image: url('{{ $product['large_icon_url'] }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                                @else
                                    <img src="{{ $product['large_icon_url'] }}" 
                                         alt="{{ $product['title'] }}"
                                         class="w-8 h-8 object-contain"
                                         loading="lazy"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">
                                        {{ strtoupper(substr($product['title'], 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-center mt-2 text-gray-600 font-medium">{{ $product['title'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- All Services --}}
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            {{ request('search') ? 'Search Results' : 'All Services' }}
        </h2>
        <div id="productList" class="space-y-3">
            @forelse($products as $product)
                <a href="{{ route('services.countries', $product['id']) }}" class="block group">
                    <div class="flex items-center space-x-4 p-4 border border-gray-100 rounded-xl hover:border-green-200 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-sm">
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-white transition-colors duration-200">
                            @if(str_contains($product['icon_url'], '.svg'))
                                <div class="w-6 h-6 service-icon" style="background-image: url('{{ $product['icon_url'] }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                            @else
                                <img src="{{ $product['icon_url'] }}" 
                                     alt="{{ $product['title'] }}"
                                     class="w-6 h-6 object-contain"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">
                                    {{ strtoupper(substr($product['title'], 0, 2)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-800 group-hover:text-green-700 transition-colors">
                                {{ $product['title'] }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ strtoupper($product['code']) }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-gray-500">No services found matching your search.</p>
                </div>
            @endforelse
        </div>

        <div id="loading" class="text-center py-6 hidden">
            <div class="inline-flex items-center space-x-2 text-gray-500">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading more services...</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Service icon styling for SVGs */
.service-icon {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
    transition: filter 0.2s ease;
}

.group:hover .service-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
}

/* Smooth scrollbar for popular services */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #059669;
}
</style>

<script>
    let nextPage = {{ $nextPage ?? 'null' }};
    let isLoading = false;

    // Infinite scroll
    window.addEventListener('scroll', function() {
        if (isLoading || !nextPage) return;

        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
            loadMore();
        }
    });

    function loadMore() {
        if (isLoading || !nextPage) return;
        
        isLoading = true;
        document.getElementById('loading').classList.remove('hidden');

        fetch(`?page=${nextPage}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.html) {
                document.getElementById('productList').insertAdjacentHTML('beforeend', data.html);
                nextPage = data.next_page;
            }
        })
        .catch(error => {
            console.error('Error loading more services:', error);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('loading').classList.add('hidden');
        });
    }

    // Global search with debouncing
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const filter = this.value.trim();
        
        searchTimeout = setTimeout(() => {
            const url = new URL(window.location);
            if (filter) {
                url.searchParams.set('search', filter);
            } else {
                url.searchParams.delete('search');
            }
            
            // Update URL without page reload
            window.history.replaceState({}, '', url);
            
            fetch(`?search=${encodeURIComponent(filter)}`)
                .then(res => res.text())
                .then(html => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, 'text/html');
                    let newContent = doc.getElementById('productList');
                    if (newContent) {
                        document.getElementById('productList').innerHTML = newContent.innerHTML;
                    }
                    // Reset pagination
                    nextPage = filter ? null : 2;
                })
                .catch(error => {
                    console.error('Error searching services:', error);
                });
        }, 300); // 300ms delay
    });

    // Initialize search input from URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('search');
        if (searchTerm) {
            document.getElementById('searchInput').value = searchTerm;
        }
    });
</script>
@endsection