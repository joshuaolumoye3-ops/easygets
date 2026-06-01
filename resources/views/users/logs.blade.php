@extends('layouts.app')

@section('title', 'Buy Logs')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-[Inter] relative">
    <button onclick="history.back()" aria-label="Back" class="absolute left-4 top-4 bg-white p-2 rounded-full shadow hover:bg-gray-100 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="bg-brand-green-light rounded-full w-12 h-12 flex items-center justify-center mr-4">
                <img src="/assets/sim-icon.svg" class="w-7 h-7 text-brand-green" alt="Logs">
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Buy Logs</h1>
                <p class="text-sm text-gray-500">Get premium logs for your needs. Fast, reliable, and secure delivery.</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search by name, country, or description..."
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 pl-10 focus:outline-none focus:ring-2 focus:ring-brand-green text-gray-800 font-medium"
                >
                <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Products List -->
        <div id="productsContainer" class="space-y-4 mt-6">
            @include('partials.logs-items', ['products' => $products])
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-brand-green"></div>
            <p class="text-gray-500 mt-2">Loading more products...</p>
        </div>

        <!-- No More Products -->
        <div id="endMessage" class="hidden text-center py-8">
            <p class="text-gray-400 text-sm">You've reached the end of the list</p>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let isLoading = false;
let hasMore = {{ $products->hasMorePages() ? 'true' : 'false' }};
let searchTimeout = null;

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(e.target.value);
    }, 500); // Debounce search by 500ms
});

function performSearch(query) {
    isLoading = true;
    currentPage = 1;
    
    document.getElementById('loadingIndicator').classList.remove('hidden');
    document.getElementById('endMessage').classList.add('hidden');
    
    fetch(`{{ route('logs') }}?search=${encodeURIComponent(query)}&ajax=1`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('productsContainer').innerHTML = data.html;
            hasMore = data.has_more;
            currentPage = data.next_page || 1;
        }
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    })
    .catch(error => {
        console.error('Search error:', error);
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    });
}

// Infinite scroll
window.addEventListener('scroll', function() {
    if (isLoading || !hasMore) return;
    
    const scrollPosition = window.innerHeight + window.scrollY;
    const documentHeight = document.documentElement.scrollHeight;
    
    // Load more when user is 200px from bottom
    if (scrollPosition >= documentHeight - 200) {
        loadMoreProducts();
    }
});

function loadMoreProducts() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    document.getElementById('loadingIndicator').classList.remove('hidden');
    
    const searchQuery = document.getElementById('searchInput').value;
    const url = `{{ route('logs') }}?page=${currentPage}&search=${encodeURIComponent(searchQuery)}&ajax=1`;
    
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Append new products
            const container = document.getElementById('productsContainer');
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            
            while (tempDiv.firstChild) {
                container.appendChild(tempDiv.firstChild);
            }
            
            hasMore = data.has_more;
            currentPage = data.next_page || currentPage;
            
            if (!hasMore) {
                document.getElementById('endMessage').classList.remove('hidden');
            }
        }
        
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    })
    .catch(error => {
        console.error('Load more error:', error);
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    });
}
</script>
@endsection