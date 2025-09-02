<button 
    class="w-full flex items-center justify-between p-3 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200 product-item"
    data-country-id="{{ $country['id'] }}"
    data-country-name="{{ $country['title'] }}"
>
    <div class="flex items-center">
        <img class="w-10 h-10 rounded-full mr-4 object-cover" 
             src="https://flagcdn.com/w80/{{ strtolower($country['code']) }}.png" 
             onerror="this.src='https://via.placeholder.com/40?text={{ strtoupper(substr($country['code'],0,1)) }}'" 
             alt="{{ $country['title'] }}">
        <span class="font-semibold text-gray-700 product-name">{{ $country['title'] }}</span>
    </div>
    <div class="flex items-center space-x-2 text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28l1.5 4.5-2.25 1.13A11 11 0 0015 19h-1a2 2 0 01-2-2v-1l4.5 1.5 1.5-4.5h3.28a2 2 0 012 2v1c0 8-6.28 14-14 14H5a2 2 0 01-2-2v-1" />
        </svg>
        <span class="font-semibold text-green-500">{{ number_format(rand(5000, 35000)) }}</span>
    </div>
</button>
