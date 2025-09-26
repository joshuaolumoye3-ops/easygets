@extends('layouts.admin')
@section('title', 'Manage Prices')

@section('content')
    <main class="pt-[70px] w-full flex flex-col">
        <div class="p-[1.875rem_1.75rem] w-full flex-grow">
            <h1 class="text-xl font-bold text-white mb-6">Plans for {{ $provider->name }}</h1>

            <!-- Bulk Save Form -->
            <form action="{{ route('admin.products.save', $provider->id) }}" method="POST"
                class="mb-6 bg-[#191c24] p-4 rounded-xl text-white">
                @csrf
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <input type="number" step="0.01" id="price_input" name="price_input"
                        @if ($provider->percentage == 1)
                            placeholder="Enter percentage (e.g., 70 for 70%)"
                        @else
                            placeholder="Enter client price"
                        @endif
                        class="px-3 py-2 rounded text-black w-full md:w-1/3" required>

                    <input type="hidden" id="client_price" name="client_price">

                    <button type="submit" class="bg-green px-4 py-2 rounded-lg">Save All Plans</button>
                </div>

                <script>
                    document.querySelector('form').addEventListener('submit', function(e) {
                        const priceInput = document.getElementById('price_input');
                        const clientPriceInput = document.getElementById('client_price');
                        const inputValue = parseFloat(priceInput.value) || 0;

                        @if ($provider->percentage == 1)
                            const multiplier = 1 + (inputValue / 100);
                            clientPriceInput.value = multiplier.toFixed(2);
                            console.log('Percentage mode:', inputValue + '% → Multiplier:', multiplier);
                        @else
                            clientPriceInput.value = inputValue.toFixed(2);
                            console.log('Normal mode: Client price:', inputValue);
                        @endif
                    });
                </script>
            </form>

            <!-- Search Input -->
            <div class="mb-4">
                <input type="text" id="searchInput" placeholder="Search plans..."
                    class="w-full md:w-1/3 px-3 py-2 rounded text-black">
            </div>

            <!-- Products Table -->
            <div class="bg-[#191c24] rounded-xl shadow overflow-x-auto">
                <table class="w-full text-left text-white" id="productsTable">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">API Price</th>
                            <th class="px-4 py-3">Client Price</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr class="border-b border-gray-700">
                                <td class="px-4 py-3">{{ $product->category }}</td>
                                <td class="px-4 py-3">{{ $product->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-lg text-sm font-semibold"
                                        style="background-color:#03624C;">
                                        ₦{{ number_format($product->api_price, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($product->id)
                                        <form action="{{ route('admin.products.update', ['id' => $product->id]) }}"
                                            method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="number" step="0.01" id="price_input_{{ $product->id }}"
                                                name="price_input"
                                                value="{{ $provider->percentage == 1 ? ($product->client_price - 1) * 100 : $product->client_price }}"
                                                class="px-2 py-1 rounded text-black w-32"
                                                @if ($provider->percentage == 1)
                                                    placeholder="Percentage"
                                                @else
                                                    placeholder="Client Price"
                                                @endif>

                                            <input type="hidden" id="client_price_{{ $product->id }}" name="client_price">

                                            <button type="submit" class="bg-green px-3 py-1 rounded-lg">Save</button>
                                        </form>

                                        <script>
                                            document.getElementById('price_input_{{ $product->id }}').closest('form').addEventListener('submit', function(e) {
                                                const priceInput = document.getElementById('price_input_{{ $product->id }}');
                                                const clientPriceInput = document.getElementById('client_price_{{ $product->id }}');
                                                const inputValue = parseFloat(priceInput.value) || 0;

                                                @if ($provider->percentage == 1)
                                                    // Percentage mode: convert to multiplier (e.g., 70 → 1.70)
                                                    const multiplier = 1 + (inputValue / 100);
                                                    clientPriceInput.value = multiplier.toFixed(2);
                                                @else
                                                    // Normal mode: use client price directly
                                                    clientPriceInput.value = inputValue.toFixed(2);
                                                @endif
                                            });
                                        </script>
                                    @else
                                        <span class="text-gray-400 text-sm">Not saved yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 flex items-center gap-3">
                                    <span class="text-sm text-gray-400">
                                        Last Updated: {{ $product->updated_at->diffForHumans() }}
                                    </span>

                                    <!-- Delete Button -->
                                    @if ($product->id)
                                        <form action="{{ route('admin.products.delete', ['id' => $product->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-red-600 text-white px-3 py-1 rounded-lg">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    {{-- JS Search Filter --}}
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#productsTable tbody tr');

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
@endsection
