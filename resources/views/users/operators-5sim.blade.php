@extends('layouts.app')

@section('title', 'Select Operator - 5sim')

@section('content')
<div class="bg-gray-50 min-h-screen font-[Inter]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-lg bg-white shadow-sm">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('services.5sim.countries', $serviceId) }}" class="absolute left-0 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Select Operator</h1>
        </div>

        <!-- Provider Badge -->
        <div class="mb-6 flex flex-col items-center space-y-3">
            <div class="inline-flex items-center space-x-2 bg-blue-50 px-4 py-2 rounded-full">
                <div class="w-6 h-6 bg-blue-600 rounded-md flex items-center justify-center text-white text-xs font-bold">
                    5S
                </div>
                <span class="text-sm font-medium text-blue-900">Provider 1 (5sim.net)</span>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Country: <span class="font-semibold">{{ ucfirst($country) }}</span></p>
                <p class="text-sm text-gray-600">Service: <span class="font-semibold">{{ ucfirst($serviceId) }}</span></p>
            </div>
        </div>

        <!-- Operators List -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Available Operators</h2>
            <div class="space-y-3">
                @forelse ($operators as $operator)
                    <button onclick="selectOperator('{{ $operator['name'] }}', {{ $operator['cost'] }}, {{ $operator['count'] }})" 
                            class="w-full group">
                        <div class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl hover:border-blue-200 hover:bg-blue-50/50 transition-all duration-200 group-hover:shadow-sm">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-blue-700 transition capitalize">
                                        {{ ucfirst($operator['name']) }}
                                    </h3>
                                    <div class="flex items-center space-x-3 mt-1">
                                        <p class="text-sm text-green-600 font-semibold">
                                            ${{ number_format($operator['cost'], 2) }}
                                        </p>
                                        <span class="text-xs text-gray-500">•</span>
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($operator['count']) }} available
                                        </p>
                                    </div>
                                    @if(isset($operator['rate']) && $operator['rate'] > 0)
                                        <div class="flex items-center mt-1">
                                            <svg class="w-3 h-3 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                            <span class="text-xs text-gray-600">{{ number_format($operator['rate'], 1) }}% success rate</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-500">No operators available for this country and service.</p>
                        <a href="{{ route('services.5sim.countries', $serviceId) }}" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">
                            ← Back to countries
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Info Box -->
        @if($operators->isNotEmpty())
        <div class="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">About Operators</p>
                    <p>Different operators may have varying success rates and availability. Choose an operator with good availability for the best experience.</p>
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
        </div>
        
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600">Service:</span>
                    <span class="font-semibold">{{ ucfirst($serviceId) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600">Country:</span>
                    <span class="font-semibold">{{ ucfirst($country) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600">Operator:</span>
                    <span id="modalOperator" class="font-semibold">-</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="text-gray-600">Available:</span>
                    <span id="modalCount" class="font-semibold text-green-600">-</span>
                </div>
                <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-4">
                    <span class="text-gray-700 font-semibold">Price:</span>
                    <span id="modalPrice" class="text-xl font-bold text-blue-600">-</span>
                </div>
            </div>

            <div class="mt-6 flex space-x-3">
                <button onclick="closeModal()" 
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                    Cancel
                </button>
                <button onclick="confirmPurchase()" 
                        class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Proceed
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form -->
<form id="purchaseForm" action="{{ route('prepare.purchase.5sim') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="service" value="{{ $serviceId }}">
    <input type="hidden" name="country" value="{{ $country }}">
    <input type="hidden" name="operator" id="formOperator">
</form>

<script>
    let selectedOperator = null;
    let selectedPrice = 0;
    let selectedCount = 0;

    function selectOperator(operatorName, price, count) {
        selectedOperator = operatorName;
        selectedPrice = price;
        selectedCount = count;

        document.getElementById('modalOperator').textContent = operatorName.charAt(0).toUpperCase() + operatorName.slice(1);
        document.getElementById('modalPrice').textContent = '$' + price.toFixed(2);
        document.getElementById('modalCount').textContent = count.toLocaleString();
        
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
    }

    function confirmPurchase() {
        if (selectedOperator) {
            document.getElementById('formOperator').value = selectedOperator;
            document.getElementById('purchaseForm').submit();
        }
    }

    // Close modal on escape
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
@endsection