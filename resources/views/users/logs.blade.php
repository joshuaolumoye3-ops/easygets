@extends('layouts.app')

@section('title', 'Buy Logs')

@section('content')
<div class="max-w-xl mx-auto py-0 font-[Inter] bg-[#f0f4f8] min-h-screen">

    {{-- Header --}}
    <div class="flex items-center px-4 pt-8 pb-4 bg-[#f0f4f8]">
        <a href="{{ route('dashboard') }}" class="text-gray-600 mr-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-lg font-semibold text-gray-800 flex-1 text-center pr-5">Buy Logs</h1>
    </div>

    <div class="px-4 pb-8">

        {{-- Search Bar --}}
        <div class="mb-3">
            <div class="relative">
                <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search products..."
                    class="w-full bg-white border border-gray-200 rounded-full px-4 py-3 pl-10 focus:outline-none focus:ring-2 focus:ring-[#00b4a0] text-gray-700 text-sm shadow-sm"
                >
            </div>
        </div>

        {{-- Sort/Filter Row --}}
        <div class="flex items-center gap-2 mb-4">
            <div class="flex items-center bg-white border border-gray-200 rounded-full px-3 py-2 shadow-sm cursor-pointer flex-1">
                <svg class="w-4 h-4 text-gray-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M9 12h6M12 16h0"/>
                </svg>
                <select id="sortSelect" class="bg-transparent text-sm text-gray-700 font-medium w-full focus:outline-none cursor-pointer">
                    <option value="default">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A–Z</option>
                    <option value="name_desc">Name: Z–A</option>
                </select>
            </div>
        </div>

        {{-- Product Count --}}
        <p id="productCount" class="text-sm text-gray-500 mb-4">
            Showing {{ $products->count() }} of {{ $products->total() }} products
        </p>

        {{-- Products Grid --}}
        <div id="productsContainer" class="grid grid-cols-2 gap-3">
            @include('partials.logs-items', ['products' => $products])
        </div>

        {{-- Loading Indicator --}}
        <div id="loadingIndicator" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#00b4a0]"></div>
            <p class="text-gray-400 text-sm mt-2">Loading more products...</p>
        </div>

        {{-- End Message --}}
        <div id="endMessage" class="hidden text-center py-6">
            <p class="text-gray-400 text-sm">You've reached the end of the list</p>
        </div>

    </div>
</div>

<script>
let currentPage = 1;
let isLoading = false;
let hasMore = {{ $products->hasMorePages() ? 'true' : 'false' }};
let searchTimeout = null;

// Search
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => performSearch(e.target.value), 500);
});

// Sort
document.getElementById('sortSelect').addEventListener('change', function() {
    performSearch(document.getElementById('searchInput').value);
});

function performSearch(query) {
    isLoading = true;
    currentPage = 1;
    const sort = document.getElementById('sortSelect').value;

    document.getElementById('loadingIndicator').classList.remove('hidden');
    document.getElementById('endMessage').classList.add('hidden');

    fetch(`{{ route('logs') }}?search=${encodeURIComponent(query)}&sort=${sort}&ajax=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('productsContainer').innerHTML = data.html;
            hasMore = data.has_more;
            currentPage = data.next_page || 1;
            if (data.total !== undefined) {
                document.getElementById('productCount').textContent =
                    `Showing ${data.count} of ${data.total} products`;
            }
        }
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    })
    .catch(() => {
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    });
}

// Infinite scroll
window.addEventListener('scroll', function() {
    if (isLoading || !hasMore) return;
    if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 200) {
        loadMoreProducts();
    }
});

function loadMoreProducts() {
    if (isLoading || !hasMore) return;
    isLoading = true;
    document.getElementById('loadingIndicator').classList.remove('hidden');

    const query = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortSelect').value;

    fetch(`{{ route('logs') }}?page=${currentPage}&search=${encodeURIComponent(query)}&sort=${sort}&ajax=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('productsContainer');
            const temp = document.createElement('div');
            temp.innerHTML = data.html;
            while (temp.firstChild) container.appendChild(temp.firstChild);
            hasMore = data.has_more;
            currentPage = data.next_page || currentPage;
            if (!hasMore) document.getElementById('endMessage').classList.remove('hidden');
        }
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    })
    .catch(() => {
        isLoading = false;
        document.getElementById('loadingIndicator').classList.add('hidden');
    });
}
</script>
@endsection