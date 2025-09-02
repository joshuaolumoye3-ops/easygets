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
            <h1 class="text-2xl font-bold text-gray-800">Countries</h1>
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
                    @include('partials.country-card', ['country' => $country])
                @endforeach
            </div>
            <!-- Loader -->
            <div id="loader" class="text-center py-4 hidden text-gray-400">Loading more...</div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="priceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-lg max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="modalTitle" class="text-xl font-bold text-gray-800">Country Prices</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div id="modalContent" class="space-y-2 text-gray-700">
            <p class="text-center text-gray-400">Loading...</p>
        </div>
    </div>
</div>

<!-- Hidden Form for Buy Now -->
<form id="purchaseForm" action="{{ route('prepare.purchase') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="service" id="formService">
    <input type="hidden" name="country" id="formCountry">
</form>

<script>
    const serviceId = "{{ $serviceId }}";
    let currentPage = {{ $countries->currentPage() }};
    let lastPage = {{ $countries->lastPage() }};
    let loading = false;
    let searchQuery = '';

    // ---- Country Click Binding ----
    function bindCountryClick() {
        document.querySelectorAll(".product-item").forEach(btn => {
            btn.onclick = async () => {
                const countryId = btn.dataset.countryId;
                const countryName = btn.dataset.countryName;
                const modal = document.getElementById("priceModal");
                const title = document.getElementById("modalTitle");
                const content = document.getElementById("modalContent");

                title.textContent = countryName + " - Price";
                content.innerHTML = "<p class='text-center text-gray-400'>Loading...</p>";
                modal.classList.remove("hidden");

                try {
                    const res = await fetch(`/services/${serviceId}/countries/${countryId}/prices`);
                    const result = await res.json();

                    if (result.status && result.data.length > 0) {
                        const item = result.data[0];
                        content.innerHTML = `
                            <div class="p-3 border rounded-lg mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium">${item.name}</span>
                                    <div class="text-right">
                                        <p class="text-green-600 font-semibold">$${item.cost.toLocaleString()}</p>
                                        <p class="text-sm text-gray-500">Total: ${item.count}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <button 
                                    onclick="buyNow(${item.project_id}, ${countryId})"
                                    class="mt-2 bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-green-700">
                                    Buy Now
                                </button>
                            </div>
                        `;
                    } else {
                        content.innerHTML = "<p class='text-center text-gray-500'>No price found for this service in this country.</p>";
                    }
                } catch (err) {
                    content.innerHTML = "<p class='text-center text-red-500'>Error fetching price.</p>";
                }
            };
        });
    }
    bindCountryClick();

    function closeModal() {
        document.getElementById("priceModal").classList.add("hidden");
    }

    // ---- Buy Now Action ----
    function buyNow(serviceId, countryId) {
        const form = document.getElementById('purchaseForm');
        document.getElementById('formService').value = serviceId;
        document.getElementById('formCountry').value = countryId;
        form.submit();
    }

    // ---- Helper: Render Country ----
    function renderCountry(country) {
        return `
        <button class="w-full flex items-center justify-between p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200 product-item"
            data-country-id="${country.id}" data-country-name="${country.title}">
            <div class="flex items-center">
                <img class="w-10 h-10 rounded-full mr-4 object-cover" 
                    src="https://flagcdn.com/w80/${country.code.toLowerCase()}.png" 
                    onerror="this.src='https://via.placeholder.com/40?text=${country.code.charAt(0).toUpperCase()}'" 
                    alt="${country.title}">
                <span class="font-semibold text-gray-700 product-name">${country.title}</span>
            </div>
        </button>`;
    }

    // ---- Infinite Scroll ----
    window.addEventListener('scroll', async () => {
        if (loading || searchQuery) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            if (currentPage < lastPage) {
                loading = true;
                document.getElementById('loader').classList.remove('hidden');
                try {
                    const res = await fetch(`/services/${serviceId}/countries/load?page=${currentPage + 1}`);
                    const result = await res.json();
                    lastPage = result.last_page;

                    result.data.forEach(country => {
                        document.getElementById('productList').insertAdjacentHTML('beforeend', renderCountry(country));
                    });
                    bindCountryClick();
                    currentPage++;
                } catch (e) {
                    console.error("Failed to load more countries", e);
                }
                document.getElementById('loader').classList.add('hidden');
                loading = false;
            }
        }
    });

    // ---- Search ----
    document.getElementById('searchInput').addEventListener('input', async function () {
        searchQuery = this.value.trim();
        const list = document.getElementById('productList');
        list.innerHTML = "<p class='text-center text-gray-400'>Searching...</p>";
        try {
            const res = await fetch(`/services/${serviceId}/countries/load?search=${encodeURIComponent(searchQuery)}`);
            const result = await res.json();
            lastPage = result.last_page;
            currentPage = 1;

            list.innerHTML = "";
            result.data.forEach(country => {
                list.insertAdjacentHTML('beforeend', renderCountry(country));
            });
            bindCountryClick();
        } catch (e) {
            list.innerHTML = "<p class='text-center text-red-500'>Error searching countries.</p>";
        }
    });
</script>
@endsection
