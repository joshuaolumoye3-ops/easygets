@forelse($products as $product)
@php
    $flagCode = null;
    if (!empty($product->country_code)) {
        $flagCode = strtolower($product->country_code);
    } elseif (!empty($product->country)) {
        $flagCode = strtolower(substr($product->country, 0, 2));
    }
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

    {{-- Flag / Image Area --}}
    <div class="flex items-center justify-center bg-gray-50 p-5 h-32">
        @if($flagCode)
            <img
                src="https://flagcdn.com/w80/{{ $flagCode }}.png"
                alt="{{ $product->country ?? 'Country' }} flag"
                class="w-16 h-12 object-cover rounded-lg shadow-sm border border-gray-200"
                onerror="this.onerror=null; this.src='/assets/sim-icon.svg'; this.className='w-12 h-12 object-contain';"
            />
        @else
            <img src="/assets/sim-icon.svg" alt="Log" class="w-12 h-12 object-contain opacity-60" />
        @endif
    </div>

    {{-- Product Info --}}
    <div class="p-3 flex flex-col flex-1">

        {{-- Name --}}
        <h2 class="font-semibold text-gray-900 text-xs leading-snug line-clamp-2 mb-1">
            {{ $product->name }}
        </h2>

        {{-- Description --}}
        @if(!empty($product->description))
            <p class="text-[11px] text-gray-400 line-clamp-1 mb-2">
                {{ Str::limit($product->description, 50) }}
            </p>
        @endif

        {{-- Badges --}}
        <div class="flex items-center gap-1 flex-wrap mb-3">
            @if(!empty($product->country))
                <span class="text-[10px] text-gray-600 bg-gray-100 rounded-full px-2 py-0.5">
                    {{ $product->country }}
                </span>
            @endif
            @if(!empty($product->mode))
                <span class="text-[10px] text-indigo-700 bg-indigo-50 rounded-full px-2 py-0.5">
                    {{ ucfirst($product->mode) }}
                </span>
            @endif
        </div>

        {{-- Price + Buy --}}
        <div class="mt-auto">
            <span class="block font-bold text-brand-green text-sm mb-2">
                ₦{{ number_format($product->price, 2) }}
            </span>
            <a href="{{ route('logs.checkout', $product->id) }}"
               class="flex items-center justify-center w-full bg-brand-green hover:bg-green-700 text-white font-semibold py-2 rounded-xl text-xs transition-colors duration-200">
                Buy
            </a>
        </div>

    </div>
</div>
@empty
<div class="col-span-2 text-center py-16">
    <img src="/assets/empty.svg" class="mx-auto mb-4 w-20 h-20 opacity-50" alt="No logs">
    <h3 class="text-base font-semibold text-gray-600 mb-1">No logs found</h3>
    <p class="text-gray-400 text-sm">Try adjusting your search criteria.</p>
</div>
@endforelse