@forelse($products as $product)
<div class="border border-gray-200 rounded-2xl p-3 flex items-center justify-between gap-3 bg-white shadow-xs hover:shadow-md transition">
    <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden border border-gray-200">
            @php
                $flagCode = null;
                if (!empty($product->country_code)) {
                    $flagCode = strtolower($product->country_code);
                } elseif (!empty($product->country)) {
                    // fallback: use first two letters of country name
                    $flagCode = strtolower(substr($product->country, 0, 2));
                }
            @endphp
            @if($flagCode)
                <img src="https://flagcdn.com/w32/{{ $flagCode }}.png" alt="{{ $product->country ?? 'Country' }} flag" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='/assets/sim-icon.svg'" />
            @else
                <img src="/assets/sim-icon.svg" alt="Country" class="w-6 h-6" />
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="font-semibold text-gray-900 text-sm">{{ $product->name }}</h2>
                @if(!empty($product->country))
                    <span class="text-xs text-gray-600 bg-gray-100 rounded-full px-1.5 py-0.5 flex-shrink-0">{{ $product->country }}</span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ Str::limit($product->description ?? '', 60) }}</p>
            @if(!empty($product->mode))
                <span class="text-xs mt-1 inline-block px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">{{ ucfirst($product->mode) }}</span>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        <span class="font-semibold text-brand-green text-sm min-w-fit">₦{{ number_format($product->price, 2) }}</span>
        <a href="{{ route('logs.checkout', $product->id) }}" class="inline-flex items-center justify-center bg-brand-green hover:bg-green-700 text-white font-semibold py-1.5 px-4 rounded-lg text-sm transition">Buy</a>
    </div>
</div>
@empty
<div class="text-center py-12">
    <img src="/assets/empty.svg" class="mx-auto mb-4 w-20 h-20 opacity-60" alt="No logs">
    <h3 class="text-lg font-semibold text-gray-600 mb-2">No logs found</h3> 
    <p class="text-gray-400">Try adjusting your search criteria.</p>
</div>
@endforelse