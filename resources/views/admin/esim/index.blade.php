@extends('layouts.admin')
@section('title', 'Manage eSIM Countries')

@section('content')
<main class="pt-[70px] w-full flex flex-col min-h-screen bg-[#0f1117]">
    <div class="p-6 md:p-8 w-full flex-grow max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">eSIM Countries</h1>
                <p class="text-gray-400 text-sm">Manage countries and data packages</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button onclick="seedCountries()" 
                    class="bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/40 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Seed Samples
                </button>
                <button onclick="refreshCountries()" 
                    class="bg-blue-600 hover:bg-blue-700 px-5 py-2.5 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh API
                </button>
            </div>
        </div>

        @if($countries->isEmpty())
            <div class="bg-gradient-to-r from-yellow-500/10 to-amber-500/10 border border-yellow-500/30 backdrop-blur-sm text-yellow-400 p-6 rounded-2xl mb-8 shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="bg-yellow-500/20 rounded-full p-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-lg mb-1">No Countries Found</p>
                        <p class="text-sm text-yellow-300/80">Click "Refresh API" to fetch all available countries or "Seed Samples" for test data</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Search and Filter Bar -->
            <div class="bg-[#191c24] rounded-xl p-4 mb-6 shadow-xl">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="Search countries..."
                            class="w-full px-5 pl-20 pr-4 py-3 bg-[#0f1117] border border-gray-700 rounded-lg text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    <div class="flex gap-2 text-sm text-gray-400">
                        <span class="px-4 py-3 bg-[#0f1117] rounded-lg text-white border border-gray-700">
                            <span class="font-semibold text-white">{{ $countries->count() }}</span> countries
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Countries Grid -->
        <div id="countriesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($countries as $country)
                <div class="country-card bg-gradient-to-br from-[#191c24] to-[#1a1d26] text-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden group border border-gray-800 hover:border-gray-700"
                    data-country-name="{{ strtolower($country->country_name) }}"
                    data-country-code="{{ strtolower($country->country_code) }}">
                    
                    <!-- Country Header -->
                    <div class="p-5 pb-3">
                        <div class="flex items-center gap-3 mb-4">
                            @if($country->flag_url)
                                <div class="relative">
                                    <img src="{{ $country->flag_url }}" 
                                        class="w-14 h-10 rounded-lg object-cover shadow-lg ring-2 ring-gray-700 group-hover:ring-blue-500 transition-all" 
                                        alt="{{ $country->country_name }}">
                                </div>
                            @else
                                <div class="w-14 h-10 bg-gradient-to-br from-gray-700 to-gray-800 rounded-lg flex items-center justify-center text-sm font-bold shadow-lg ring-2 ring-gray-700">
                                    {{ strtoupper(substr($country->country_code, 0, 2)) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h2 class="font-bold text-base text-white truncate group-hover:text-blue-400 transition-colors">
                                    {{ $country->country_name }}
                                </h2>
                                <p class="text-xs text-gray-400 font-mono">{{ $country->country_code }}</p>
                            </div>
                        </div>

                        <!-- Price Form -->
                        <div class="mt-4">
                            <label class="text-xs font-medium text-gray-400 mb-2 block flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Base Price (₦)
                            </label>
                            <form class="flex items-center gap-2 update-price-form" data-id="{{ $country->id }}">
                                @csrf
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    name="base_price"
                                    value="{{ $country->base_price }}" 
                                    class="bg-[#0f1117] text-black px-4 py-2.5 rounded-lg flex-1 text-sm border border-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                    min="0"
                                    required>
                                <button type="submit" 
                                    class="bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/40 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </form>

                            <!-- Local number availability -->
                            <div class="mt-3">
                                <form class="toggle-local-number" data-id="{{ $country->id }}">
                                    @csrf
                                    <button type="button" onclick="toggleLocalNumber({{ $country->id }}, this)" class="px-3 py-2 rounded-lg text-sm {{ $country->local_number_available ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300' }}">
                                        {{ $country->local_number_available ? 'Local numbers: Enabled' : 'Local numbers: Disabled' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Manage Packages Button -->
                    <div class="px-5 pb-5">
                        <button
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 py-3 rounded-xl text-sm font-semibold transition-all duration-200 shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 flex items-center justify-center gap-2"
                            onclick="openPackages({{ $country->id }}, '{{ addslashes($country->country_name) }}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Manage Packages
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty Search Result -->
        <div id="noResults" class="hidden text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-800 rounded-full mb-4">
                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <p class="text-gray-400 text-lg">No countries found</p>
            <p class="text-gray-500 text-sm mt-1">Try adjusting your search</p>
        </div>
    </div>
</main>

<!-- Enhanced Packages Modal -->
<div id="packagesModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 backdrop-blur-md p-4">
    <div class="bg-black from-[#191c24] to-[#1a1d26] rounded-3xl text-white w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl border border-gray-800 animate-modal">
        
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-800">
            <div class="flex items-center gap-3">
                <div class="bg-blue-600/20 p-2.5 rounded-xl">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h2 id="modalTitle" class="text-xl font-bold"></h2>
                    <p class="text-sm text-gray-400">Manage data packages</p>
                </div>
            </div>
            <button onclick="closeModal()" 
                class="text-gray-400 hover:text-white hover:bg-gray-800 p-2 rounded-lg transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
            
            <!-- Packages List -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-400 mb-3 uppercase tracking-wider">Current Packages</h3>
                <div id="packagesList" class="space-y-3">
                    <!-- Packages will be loaded here -->
                </div>
            </div>

            <!-- Add/Edit Package Form -->
            <div class="bg-[#0f1117] rounded-2xl p-6 border border-gray-800">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <h3 class="font-semibold text-white">Add / Edit Package</h3>
                </div>
                
                <form id="addPackageForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="package_id" id="packageId">
                    
                    <div>
                        <label class="text-sm font-medium text-gray-300 mb-2 block">Data Plan</label>
                        <input 
                            type="text" 
                            name="plan" 
                            placeholder="e.g., 5GB, 10GB, Unlimited" 
                            class="w-full px-4 py-3 rounded-xl bg-[#191c24] text-white border border-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all placeholder-gray-500"
                            required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-300 mb-2 block">Validity (days)</label>
                            <input 
                                type="number" 
                                name="validity_days" 
                                placeholder="30" 
                                min="1"
                                class="w-full px-4 py-3 rounded-xl bg-[#191c24] text-white border border-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                required>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-300 mb-2 block">Price (₦)</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="price" 
                                placeholder="5000" 
                                min="0"
                                class="w-full px-4 py-3 rounded-xl bg-[#191c24] text-white border border-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                                required>
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 py-3.5 rounded-xl font-semibold transition-all duration-200 shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/40 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Package
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modal {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-modal {
    animation: modal 0.2s ease-out;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #0f1117;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
</style>

<script>
let currentCountryId = null;
let currentCountryName = null;

// Search functionality
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.country-card');
    const noResults = document.getElementById('noResults');
    let visibleCount = 0;

    cards.forEach(card => {
        const name = card.dataset.countryName;
        const code = card.dataset.countryCode;
        
        if (name.includes(searchTerm) || code.includes(searchTerm)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    noResults.classList.toggle('hidden', visibleCount > 0);
});

async function openPackages(id, name) {
    currentCountryId = id;
    currentCountryName = name;
    
    const modal = document.getElementById('packagesModal');
    document.getElementById('modalTitle').innerText = name;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Clear form
    document.getElementById('packageId').value = '';
    document.getElementById('addPackageForm').reset();

    try {
        const res = await fetch(`/app/admin/esim/${id}/packages`);
        const data = await res.json();
        
        console.log('Packages response:', data); // Debug log
        
        const list = document.getElementById('packagesList');
        list.innerHTML = '';

        if (!data.packages || data.packages.length === 0) {
            list.innerHTML = `
                <div class="text-center py-12 bg-[#0f1117] rounded-xl border border-dashed border-gray-700">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-800 rounded-full mb-3">
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 font-medium">No packages yet</p>
                    <p class="text-gray-500 text-sm mt-1">Add your first package below</p>
                </div>`;
        } else {
            data.packages.forEach(p => {
                list.innerHTML += `
                    <div class="flex justify-between items-center bg-[#0f1117] px-5 py-4 rounded-xl hover:bg-[#141821] transition-all border border-gray-800 hover:border-gray-700 group">
                        <div class="flex-1 flex items-center gap-4">
                            <div class="bg-blue-600/20 p-3 rounded-lg group-hover:bg-blue-600/30 transition-all">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <strong class="text-white font-bold text-base">${p.plan}</strong>
                                    <span class="text-xs bg-blue-600/20 text-blue-400 px-2.5 py-1 rounded-full font-medium">${p.validity_days} days</span>
                                </div>
                                <p class="text-emerald-400 font-semibold text-sm">₦${parseFloat(p.price).toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                class="text-amber-400 hover:text-amber-300 hover:bg-amber-400/10 px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1" 
                                onclick="editPackage(${p.id}, '${p.plan}', ${p.validity_days}, ${p.price})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </button>
                            <button 
                                class="text-red-400 hover:text-red-300 hover:bg-red-400/10 px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-1" 
                                onclick="deletePackage(${p.id})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>`;
            });
        }
    } catch (error) {
        console.error('Error loading packages:', error);
        showNotification('Failed to load packages', 'error');
    }

    // Setup form submission
    document.getElementById('addPackageForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch(`/app/admin/esim/${id}/packages/save`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            });
            
            if (res.ok) {
                await openPackages(id, name);
                e.target.reset();
                document.getElementById('packageId').value = '';
                showNotification('Package saved successfully!', 'success');
            } else {
                showNotification('Failed to save package', 'error');
            }
        } catch (error) {
            console.error('Error saving package:', error);
            showNotification('Failed to save package', 'error');
        }
    };
}

function editPackage(id, plan, validity, price) {
    document.getElementById('packageId').value = id;
    document.querySelector('input[name=plan]').value = plan;
    document.querySelector('input[name=validity_days]').value = validity;
    document.querySelector('input[name=price]').value = price;
    
    document.getElementById('addPackageForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function deletePackage(id) {
    if (confirm('Are you sure you want to delete this package?')) {
        try {
            const res = await fetch(`/app/admin/esim/packages/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json'
                }
            });
            
            if (res.ok) {
                await openPackages(currentCountryId, currentCountryName);
                showNotification('Package deleted successfully!', 'success');
            } else {
                showNotification('Failed to delete package', 'error');
            }
        } catch (error) {
            console.error('Error deleting package:', error);
            showNotification('Failed to delete package', 'error');
        }
    }
}

function closeModal() {
    document.getElementById('packagesModal').classList.add('hidden');
    document.getElementById('packagesModal').classList.remove('flex');
    document.getElementById('addPackageForm').reset();
    document.getElementById('packageId').value = '';
}

// Price update forms - Wrapped in DOMContentLoaded to ensure forms exist
document.addEventListener('DOMContentLoaded', function() {
    // Initialize price update forms
    initializePriceForms();
});

function initializePriceForms() {
    document.querySelectorAll('.update-price-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const id = form.dataset.id;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Disable button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            `;
            
            try {
                const res = await fetch(`/app/admin/esim/${id}/update-price`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                });
                
                if (res.ok) {
                    showNotification('Price updated successfully!', 'success');
                    // Reset button
                    submitButton.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    `;
                } else {
                    showNotification('Failed to update price', 'error');
                    submitButton.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    `;
                }
            } catch (error) {
                console.error('Error updating price:', error);
                showNotification('Failed to update price', 'error');
                submitButton.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                `;
            } finally {
                submitButton.disabled = false;
            }
        });
    });
}

async function seedCountries() {
    if (confirm('This will add 10 sample countries. Continue?')) {
        try {
            const res = await fetch('/app/admin/esim/seed-countries', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json'
                }
            });
            
            if (res.ok) {
                showNotification('Sample countries added successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Failed to seed countries', 'error');
            }
        } catch (error) {
            console.error('Error seeding countries:', error);
            showNotification('Failed to seed countries', 'error');
        }
    }
}

async function refreshCountries() {
    if (confirm('This will fetch all countries from RestCountries API. Continue?')) {
        try {
            const baseUrl = '{{ url('/') }}';
            const res = await fetch(`${baseUrl}/app/admin/esim/refresh-countries`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Content-Type': 'application/json'
                }
            });
            
            if (res.ok) {
                showNotification('Countries refreshed successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Failed to refresh countries', 'error');
            }
        } catch (error) {
            console.error('Error refreshing countries:', error);
            showNotification('Failed to refresh countries', 'error');
        }
    }
}

// Toggle local number availability via admin endpoint
async function toggleLocalNumber(countryId, btn) {
    try {
        btn.disabled = true;
        const originalText = btn.innerText;

        btn.innerText = 'Updating...';

        const res = await fetch(`/app/admin/esim/${countryId}/toggle-local-number`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Content-Type': 'application/json'
            }
        });

        if (!res.ok) {
            const err = await res.text();
            console.error('Toggle failed:', err);
            showNotification('Failed to update local number availability', 'error');
            btn.disabled = false;
            btn.innerText = originalText;
            return;
        }

        const data = await res.json();
        const enabled = !!data.local_number_available;

        btn.classList.toggle('bg-green-600', enabled);
        btn.classList.toggle('text-white', enabled);
        btn.classList.toggle('bg-gray-700', !enabled);
        btn.classList.toggle('text-gray-300', !enabled);
        btn.innerText = enabled ? 'Local numbers: Enabled' : 'Local numbers: Disabled';

        showNotification('Local number availability updated', 'success');
        btn.disabled = false;
    } catch (error) {
        console.error('Error toggling local number:', error);
        showNotification('Failed to update local number availability', 'error');
        btn.disabled = false;
    }
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-red-600 text-white',
        info: 'bg-blue-600 text-white'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed bottom-6 right-6 px-6 py-4 rounded-lg shadow-lg ${colors[type]} opacity-0 transform translate-y-4 transition-all duration-300 z-50`;
    notification.innerText = message;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.remove('opacity-0', 'translate-y-4');
        notification.classList.add('opacity-100', 'translate-y-0');
    }, 100);
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('opacity-100', 'translate-y-0');
        notification.classList.add('opacity-0', 'translate-y-4');
        
        // Remove from DOM
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>
@endsection