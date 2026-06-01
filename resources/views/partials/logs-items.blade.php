@forelse($products as $product)
<div class="border rounded-3xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white shadow-sm hover:shadow-lg transition">
    <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-3xl bg-slate-50 flex items-center justify-center overflow-hidden border border-gray-100">
            @if(!empty($product->country_code))
                <img src="https://flagcdn.com/w40/{{ strtolower($product->country_code) }}.png" alt="{{ $product->country }} flag" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/sim-icon.svg'" />
            @else
                <img src="/assets/sim-icon.svg" alt="Country" class="w-8 h-8" />
            @endif
        </div>
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <h2 class="font-semibold text-gray-900 text-base">{{ $product->name }}</h2>
                @if(!empty($product->country))
                    <span class="text-xs text-gray-500 bg-gray-100 rounded-full px-2 py-1">{{ $product->country }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mb-2">{{ Str::limit($product->description ?? '', 100) }}</p>
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-400">
                @if(!empty($product->country_code))
                    <span class="uppercase tracking-wider">{{ $product->country_code }}</span>
                @endif
                @if(!empty($product->mode))
                    <span class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700">{{ ucfirst($product->mode) }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex flex-col items-start sm:items-end gap-3">
        <span class="font-semibold text-brand-green text-lg">₦{{ number_format($product->price, 2) }}</span>
        <a href="{{ route('logs.checkout', $product->id) }}" class="inline-flex items-center justify-center bg-brand-green hover:bg-green-700 text-white font-semibold py-2 px-5 rounded-full transition">Buy Now</a>
    </div>
</div>
@empty
<div class="text-center py-12">
    <img src="/assets/empty.svg" class="mx-auto mb-4 w-20 h-20 opacity-60" alt="No logs">
    <h3 class="text-lg font-semibold text-gray-600 mb-2">No logs found</h3>
    <p class="text-gray-400">Try adjusting your search criteria.</p>
</div>
@endforelse