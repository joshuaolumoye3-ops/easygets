{{-- resources/views/users/partials/service_items.blade.php --}}
@foreach($products as $product)
    <a href="{{ route('services.countries', $product['id']) }}" class="block group">
        <div class="flex items-center space-x-4 p-4 border border-gray-100 rounded-xl hover:border-green-200 hover:bg-green-50/50 transition-all duration-200 group-hover:shadow-sm">
            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-white transition-colors duration-200">
                @if(str_contains($product['icon_url'], '.svg'))
                    <div class="w-6 h-6 service-icon" style="background-image: url('{{ $product['icon_url'] }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                @else
                    <img src="{{ $product['icon_url'] }}" 
                         alt="{{ $product['title'] }}"
                         class="w-6 h-6 object-contain"
                         loading="lazy"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold" style="display: none;">
                        {{ strtoupper(substr($product['title'], 0, 2)) }}
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-gray-800 group-hover:text-green-700 transition-colors">
                    {{ $product['title'] }}
                </h3>
                <p class="text-xs text-gray-500 mt-1">
                    {{ strtoupper($product['code']) }}
                </p>
            </div>
            <div class="flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>
    </a>
@endforeach