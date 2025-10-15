@extends('layouts.app')

@section('title', 'Select Operator - 5sim')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('services.5sim.countries', ['serviceCode' => $serviceCode]) }}" 
               class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Select Operator</h1>
        </div>

        <!-- Country & Service Info -->
        <div class="mb-6 space-y-3">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                <div class="flex items-center space-x-3">
                    <img class="w-10 h-10 rounded-full object-cover shadow-sm" 
                         src="https://flagcdn.com/w80/{{ strtolower($country) }}.png" 
                         onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($country,0,1)) }}'" 
                         alt="{{ ucfirst($country) }}">
                    <div>
                        <p class="text-xs text-gray-600">Selected Country</p>
                        <p class="font-bold text-gray-800">{{ ucfirst($country) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                        <span class="text-lg">📱</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Service</p>
                        <p class="font-bold text-gray-800">{{ ucfirst($serviceCode) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operators List -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Operators</h2>
            
            @forelse ($operators as $operator)
                <div class="mb-3">
                    <button onclick="selectOperator('{{ $operator['name'] }}', '{{ $operator['display_name'] }}', {{ $operator['cost'] }}, {{ $operator['count'] }}, {{ $operator['rate'] ?? 0 }})" 
                            class="w-full block group"
                            @if($operator['count'] <= 0) disabled @endif>
                        <div class="flex items-center justify-between p-4 bg-white border-2 rounded-xl transition-all duration-200 
                                    {{ $operator['count'] > 0 ? 'border-gray-100 hover:border-blue-500 hover:bg-blue-50/50 group-hover:shadow-md cursor-pointer' : 'border-gray-100 bg-gray-50 opacity-60 cursor-not-allowed' }}">
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                    {{ strtoupper(substr($operator['display_name'], -2)) }}
                                </div>
                                <div class="text-left flex-1">
                                    <h3 class="font-semibold text-gray-800 {{ $operator['count'] > 0 ? 'group-hover:text-blue-700' : '' }} transition-colors">
                                        {{ $operator['display_name'] }}
                                    </h3>
                                    <div class="flex items-center space-x-3 mt-1 flex-wrap">
                                        <span class="text-xs {{ $operator['count'] > 0 ? 'text-gray-600' : 'text-gray-400' }}">
                                            {{ $operator['count'] > 0 ? 'Available: ' . number_format($operator['count']) : 'Out of stock' }}
                                        </span>
                                        @if($operator['rate'] > 0)
                                        <span class="inline-flex items-center space-x-1">
                                            <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-xs text-green-600 font-medium">{{ $operator['rate'] }}%</span>
                                        </span>
                                        @endif
                                        @if(isset($operator['rate24']) && $operator['rate24'] > 0)
                                        <span class="text-xs text-blue-600">
                                            24h: {{ $operator['rate24'] }}%
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-lg font-bold text-green-600">${{ number_format($operator['cost'], 2) }}</p>
                                <p class="text-xs text-gray-500">USD</p>
                            </div>
                            @if($operator['count'] > 0)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            @endif
                        </div>
                    </button>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 font-medium mb-2">No operators available</p>
                    <p class="text-sm text-gray-400 mb-4">Please try another country or service</p>
                    <a href="{{ route('services.5sim.countries', ['serviceCode' => $serviceCode]) }}" 
                       class="inline-block px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                        ← Back to countries
                    </a>
                </div>
            @endforelse
        </div>

        @if(count($operators) > 0)
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start space-x-2">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-xs text-yellow-800">
                    <p class="font-medium mb-1">Important Information:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Prices shown in USD will be converted to NGN at checkout</li>
                        <li>Success rate shows delivery reliability</li>
                        <li>Operators with higher success rates are more reliable</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6">
            <h2 class="text-xl font-bold text-white">Confirm Purchase</h2>
            <p class="text-blue-100 text-sm mt-1">Review your selection before proceeding</p>
        </div>
        
        <div class="p-6">
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Service</span>
                    <span class="font-semibold text-gray-800">{{ ucfirst($serviceCode) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Country</span>
                    <span class="font-semibold text-gray-800">{{ ucfirst($country) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Operator</span>
                    <span id="modalOperatorName" class="font-semibold text-gray-800"></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg border-2 border-blue-200">
                    <span class="text-sm text-gray-600 font-medium">Price</span>
                    <span id="modalPrice" class="font-bold text-green-600 text-lg"></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Available</span>
                    <span id="modalCount" class="font-semibold text-gray-800"></span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                    <span class="text-sm text-gray-600">Success Rate</span>
                    <span id="modalRate" class="font-semibold text-green-700"></span>
                </div>
            </div>

            <div class="flex space-x-3 mt-6">
                <button onclick="closeModal()" 
                        class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button onclick="submitPurchase()" 
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-lg hover:from-green-700 hover:to-green-800 transition shadow-md">
                    Proceed
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form -->
<form id="purchaseForm" action="{{ route('prepare.purchase.5sim') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="service" value="{{ $serviceCode }}">
    <input type="hidden" name="country" value="{{ $country }}">
    <input type="hidden" name="operator" id="formOperator">
</form>

<script>
    let selectedOperator = null;

    function selectOperator(operatorName, displayName, cost, count, rate) {
        if (count <= 0) {
            return; // Don't allow selection of out-of-stock operators
        }
        
        selectedOperator = operatorName;
        
        document.getElementById('modalOperatorName').textContent = displayName;
        document.getElementById('modalPrice').textContent = ' + cost.toFixed(2) + ' USD';
        document.getElementById('modalCount').textContent = count > 0 ? count.toLocaleString() + ' numbers' : 'Limited availability';
        document.getElementById('modalRate').textContent = rate > 0 ? rate + '%' : 'N/A';
        
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        selectedOperator = null;
    }

    function submitPurchase() {
        if (!selectedOperator) {
            alert('Please select an operator');
            return;
        }
        
        document.getElementById('formOperator').value = selectedOperator;
        document.getElementById('purchaseForm').submit();
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Close modal on outside click
    document.getElementById('confirmModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<style>
@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#confirmModal > div {
    animation: slideIn 0.3s ease-out;
}

button:disabled {
    cursor: not-allowed;
}
</style>
@endsection